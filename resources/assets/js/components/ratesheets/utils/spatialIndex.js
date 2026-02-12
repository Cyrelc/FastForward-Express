import RBush from 'rbush'

// Simple spatial index wrapper for polygon vertices.
// Items use lng as X and lat as Y (minX/minY/maxX/maxY)

const index = new RBush()

// separate index for segments
const segmentIndex = new RBush()

// Keep track of per-zone items so we can remove/update them
const zoneItems = new Map()

function pointItem(zone, vertexIndex, lat, lng){
    return {
        minX: lng,
        minY: lat,
        maxX: lng,
        maxY: lat,
        zoneZIndex: zone.polygon.zIndex,
        vertexIndex,
        lat,
        lng,
        _zoneRef: zone
    }
}

function metersToLatDeg(m){
    return m / 111320.0
}

function metersToLngDeg(m, lat){
    return m / (111320.0 * Math.cos(lat * Math.PI / 180.0))
}

function addZone(zone){
    // remove existing entries for zone
    removeZone(zone)
    if(!zone || !zone.polygon) return
    const path = zone.polygon.getPath().getArray()
    const items = path.map((pt, idx) => pointItem(zone, idx, pt.lat(), pt.lng()))
    if(items.length) {
        index.load(items)
        zoneItems.set(zone.polygon.zIndex, items)
    }

    // add segments
    const segs = buildSegmentItems(zone)
    if(segs.length){
        segmentIndex.load(segs)
        zoneSegmentItems.set(zone.polygon.zIndex, segs)
    }

    // attach listeners to keep index in sync on edit
    try{
        const mvc = zone.polygon.getPath()
        // we'll attach once listeners for set_at, insert_at, remove_at
        // When triggered, rebuild zone entries
        const rebuild = () => { updateZone(zone) }
        mvc.addListener('set_at', rebuild)
        mvc.addListener('insert_at', rebuild)
        mvc.addListener('remove_at', rebuild)
    } catch(e){
        // ignore if polygon not standard
    }
}

function updateZone(zone){
    removeZone(zone)
    const path = zone.polygon.getPath().getArray()
    const items = path.map((pt, idx) => pointItem(zone, idx, pt.lat(), pt.lng()))
    if(items.length){
        index.load(items)
        zoneItems.set(zone.polygon.zIndex, items)
    }

    // rebuild segments
    const segs = buildSegmentItems(zone)
    if(segs.length){
        segmentIndex.load(segs)
        zoneSegmentItems.set(zone.polygon.zIndex, segs)
    }
}

function removeZone(zone){
    if(!zone) return
    const existing = zoneItems.get(zone.polygon.zIndex)
    if(existing && existing.length){
        existing.forEach(item => index.remove(item, (a,b) => a === b))
        zoneItems.delete(zone.polygon.zIndex)
    }
    const existingSeg = zoneSegmentItems.get(zone.polygon.zIndex)
    if(existingSeg && existingSeg.length){
        existingSeg.forEach(item => segmentIndex.remove(item, (a,b) => a === b))
        zoneSegmentItems.delete(zone.polygon.zIndex)
    }
}


// segment index helpers
const zoneSegmentItems = new Map()

function segmentItem(zone, segIndex, lat1, lng1, lat2, lng2){
    const minLat = Math.min(lat1, lat2)
    const maxLat = Math.max(lat1, lat2)
    const minLng = Math.min(lng1, lng2)
    const maxLng = Math.max(lng1, lng2)
    return {
        minX: minLng,
        minY: minLat,
        maxX: maxLng,
        maxY: maxLat,
        zoneZIndex: zone.polygon.zIndex,
        segIndex,
        lat1, lng1, lat2, lng2,
        _zoneRef: zone
    }
}

function buildSegmentItems(zone){
    if(!zone || !zone.polygon) return []
    const path = zone.polygon.getPath().getArray()
    const out = []
    for(let i=0;i<path.length;i++){
        const a = path[i]
        const b = path[(i+1) % path.length]
        out.push(segmentItem(zone, i, a.lat(), a.lng(), b.lat(), b.lng()))
    }
    return out
}

function queryNearbySegments(lat, lng, meters){
    if(typeof lat !== 'number' || typeof lng !== 'number') return []
    const latDelta = metersToLatDeg(meters)
    const lngDelta = metersToLngDeg(meters, lat)
    const bbox = { minX: lng - lngDelta, minY: lat - latDelta, maxX: lng + lngDelta, maxY: lat + latDelta }
    return segmentIndex.search(bbox)
}
function queryNearby(lat, lng, meters){
    if(typeof lat !== 'number' || typeof lng !== 'number') return []
    const latDelta = metersToLatDeg(meters)
    const lngDelta = metersToLngDeg(meters, lat)
    const bbox = { minX: lng - lngDelta, minY: lat - latDelta, maxX: lng + lngDelta, maxY: lat + latDelta }
    return index.search(bbox)
}

export default {
    addZone,
    updateZone,
    removeZone,
    queryNearby,
    queryNearbySegments,
}
