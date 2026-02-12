import RBush from "rbush";
import * as turf from "@turf/turf";

/**
 * Normalize polygon boundaries by snapping points, detecting shared segments,
 * and removing redundant points ONLY along shared boundaries.
 *
 * @param {Array<Array<[number, number]>>} polygons
 * @param {number} toleranceMeters
 * @returns {Array<Array<[number, number]>>}
 */
export function normalizePolygons(polygons, toleranceMeters) {
  const toleranceDeg = metersToDegrees(toleranceMeters);

  /* ============================================================
     1) CANONICAL POINT REGISTRY (SNAPPING)
  ============================================================ */

  const canonicalPoints = [];
  const grid = new Map();

  function snapPoint(lat, lng) {
    const key = gridKey(lat, lng, toleranceDeg);

    if (!grid.has(key)) {
      const p = { lat, lng, id: canonicalPoints.length };
      canonicalPoints.push(p);
      grid.set(key, [p]);
      return p;
    }

    for (const p of grid.get(key)) {
      if (distanceMeters(lat, lng, p.lat, p.lng) <= toleranceMeters) {
        return p;
      }
    }

    const p = { lat, lng, id: canonicalPoints.length };
    canonicalPoints.push(p);
    grid.get(key).push(p);
    return p;
  }

  const polyPoints = polygons.map(poly =>
    poly.map(([lat, lng]) => snapPoint(lat, lng))
  );

  /* ============================================================
     2) EDGE EXTRACTION
  ============================================================ */

  const edges = [];
  let edgeId = 0;

  polyPoints.forEach((poly, polyId) => {
    for (let i = 0; i < poly.length; i++) {
      const a = poly[i];
      const b = poly[(i + 1) % poly.length];

      edges.push({
        id: edgeId++,
        polyId,
        a,
        b,
        minX: Math.min(a.lng, b.lng),
        minY: Math.min(a.lat, b.lat),
        maxX: Math.max(a.lng, b.lng),
        maxY: Math.max(a.lat, b.lat),
      });
    }
  });

  /* ============================================================
     3) SPATIAL INDEX
  ============================================================ */

  const tree = new RBush();
  tree.load(edges);

  /* ============================================================
     4) DETECT SHARED SEGMENTS
  ============================================================ */

  // edgeId -> [{startT, endT, S, E}]
  const sharedSegments = new Map();

  function recordShared(edge, startT, endT, S, E) {
    if (!sharedSegments.has(edge.id)) sharedSegments.set(edge.id, []);
    sharedSegments.get(edge.id).push({ startT, endT, S, E });
  }

  for (const e1 of edges) {
    const candidates = tree.search({
      minX: e1.minX - toleranceDeg,
      minY: e1.minY - toleranceDeg,
      maxX: e1.maxX + toleranceDeg,
      maxY: e1.maxY + toleranceDeg,
    });

    for (const e2 of candidates) {
      if (e1.id >= e2.id) continue;
      if (e1.polyId === e2.polyId) continue;

      const overlap = segmentOverlap(e1.a, e1.b, e2.a, e2.b, toleranceMeters);
      if (!overlap) continue;

      const S = snapPoint(overlap.start.lat, overlap.start.lng);
      const E = snapPoint(overlap.end.lat, overlap.end.lng);

      recordShared(e1, overlap.startT, overlap.endT, S, E);
      recordShared(e2, overlap.otherStartT, overlap.otherEndT, S, E);
    }
  }

  /* ============================================================
     5) SPLIT EDGES AT SHARED SEGMENT BOUNDARIES
  ============================================================ */

  const newPolys = polyPoints.map(poly => [...poly]);

  function splitEdge(poly, index, points) {
    const a = poly[index];
    const b = poly[(index + 1) % poly.length];

    const dir = directionVector(a, b);

    const sorted = points
      .map(p => ({ p, t: projectionParam(a, dir, p) }))
      .filter(x => x.t > 0 && x.t < 1)
      .sort((x, y) => x.t - y.t)
      .map(x => x.p);

    if (sorted.length > 0) {
      poly.splice(index + 1, 0, ...sorted);
    }
  }

  for (const edge of edges) {
    const segs = sharedSegments.get(edge.id);
    if (!segs) continue;

    const poly = newPolys[edge.polyId];
    const index = poly.findIndex(p => p.id === edge.a.id);

    const splitPoints = segs.flatMap(s => [s.S, s.E]);
    splitEdge(poly, index, splitPoints);
  }

  /* ============================================================
     6) REMOVE REDUNDANT POINTS ONLY INSIDE SHARED SEGMENTS
  ============================================================ */

  function shouldRemovePoint(prev, curr, next, edgeSegs) {
    if (!edgeSegs) return false;

    // Must be collinear with the segment line
    if (pointLineDistanceMeters(prev, next, curr) > toleranceMeters) {
      return false;
    }

    const dir = directionVector(prev, next);
    const t = projectionParam(prev, dir, curr);

    // Must lie strictly inside at least one shared segment interval
    return edgeSegs.some(seg => t > seg.startT && t < seg.endT);
  }

  const simplified = newPolys.map((poly, polyId) => {
    const result = [];

    for (let i = 0; i < poly.length; i++) {
      const prev = poly[(i - 1 + poly.length) % poly.length];
      const curr = poly[i];
      const next = poly[(i + 1) % poly.length];

      const edge = findEdge(polyId, prev, curr, edges);
      const segs = edge ? sharedSegments.get(edge.id) : null;

      if (!shouldRemovePoint(prev, curr, next, segs)) {
        result.push(curr);
      }
    }

    return result;
  });

  /* ============================================================
     7) OUTPUT
  ============================================================ */

  return simplified.map(poly => poly.map(p => [p.lat, p.lng]));
}

/* ============================================================
   HELPERS
============================================================ */

function metersToDegrees(m) {
  return m / 111320;
}

function gridKey(lat, lng, tol) {
  return `${Math.round(lat / tol)},${Math.round(lng / tol)}`;
}

function distanceMeters(lat1, lng1, lat2, lng2) {
  return turf.distance([lng1, lat1], [lng2, lat2], { units: "meters" });
}

function directionVector(a, b) {
  return { x: b.lng - a.lng, y: b.lat - a.lat };
}

function projectionParam(a, dir, p) {
  const dx = p.lng - a.lng;
  const dy = p.lat - a.lat;
  const len2 = dir.x * dir.x + dir.y * dir.y;
  return len2 === 0 ? 0 : (dx * dir.x + dy * dir.y) / len2;
}

function pointLineDistanceMeters(a, b, p) {
  const line = turf.lineString([[a.lng, a.lat], [b.lng, b.lat]]);
  const pt = turf.point([p.lng, p.lat]);
  return turf.pointToLineDistance(pt, line, { units: "meters" });
}

function findEdge(polyId, a, b, edges) {
  return edges.find(e => e.polyId === polyId && e.a.id === a.id && e.b.id === b.id);
}

function segmentOverlap(a1, a2, b1, b2, tolMeters) {
  if (pointLineDistanceMeters(a1, a2, b1) > tolMeters) return null;

  const dirA = directionVector(a1, a2);
  const dirB = directionVector(b1, b2);

  const tA1 = projectionParam(a1, dirA, b1);
  const tA2 = projectionParam(a1, dirA, b2);

  const startT = Math.max(0, Math.min(tA1, tA2));
  const endT = Math.min(1, Math.max(tA1, tA2));

  if (endT <= startT) return null;

  const tB1 = projectionParam(b1, dirB, a1);
  const tB2 = projectionParam(b1, dirB, a2);

  const otherStartT = Math.max(0, Math.min(tB1, tB2));
  const otherEndT = Math.min(1, Math.max(tB1, tB2));

  const start = {
    lat: a1.lat + (a2.lat - a1.lat) * startT,
    lng: a1.lng + (a2.lng - a1.lng) * startT,
  };

  const end = {
    lat: a1.lat + (a2.lat - a1.lat) * endT,
    lng: a1.lng + (a2.lng - a1.lng) * endT,
  };

  return { startT, endT, otherStartT, otherEndT, start, end };
}
