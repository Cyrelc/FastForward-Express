import polylabel from 'polylabel'
import simplify from 'simplify'
import spatialIndex from '../utils/spatialIndex.js'

export const polyColours = {
    internalStroke : '#3651c9',
    internalFill: '#8491c9',
    outlyingStroke: '#d16b0c',
    outlyingFill: '#e8a466',
    peripheralStroke:'#2c9122',
    peripheralFill: '#3bd82d'
}

export default class Zone {
    constructor(mapZone) {
        const resolvedType = mapZone.type ?? 'internal'
        this.additionalCosts = mapZone.additionalCosts ?? {regular: null, rush: null, direct_rush: null, direct: null}
        this.additionalTime = mapZone.additionalTime
        this.fillColour = polyColours[`${resolvedType}Fill`] ?? polyColours.internalFill
        this.name = mapZone.name
        this.neighbours = mapZone.neighbours
        this.polygon = mapZone.polygon
        this.strokeColour = polyColours[`${resolvedType}Stroke`] ?? polyColours.internalStroke
        this.type = resolvedType
        this.zoneId = mapZone.zoneId ?? null

        this.polygon.setOptions({strokeColor: this.strokeColour, fillColor: this.fillColour})

        const coordinates = this.getCoordinates().map(coordinatePair => {
            return [coordinatePair.lat, coordinatePair.lng]
        })
        const neighbourLabelPosition = polylabel([coordinates])
        this.neighbourLabel = new google.maps.Marker({
            map: null,
            label: 'A',
            position: {lat: neighbourLabelPosition[0], lng: neighbourLabelPosition[1]},
        })
        google.maps.event.addListener(this.polygon, 'rightclick', (point) => this.deletePolyPoint(point));
    }

    collect = () => {
        return {
            additionalTime: this.additionalTime,
            coordinates: JSON.stringify(this.getCoordinates()),
            directCost: this.additionalCosts.direct,
            directRushCost: this.additionalCosts.direct_rush,
            name: this.name,
            regularCost: this.additionalCosts.regular,
            rushCost: this.additionalCosts.rush,
            type: this.type,
            zoneId: this.zoneId,
        }
    }

    delete = () => {
        this.polygon.setMap(null)
        this.neighbourLabel.setMap(null)
    }

    deletePolyPoint = (point) => {
        if(!this.polygon) {
            console.error('Unable to delete polypoint, polygon has not been created')
            return
        }
        this.polygon.getPath().removeAt(point.vertex);
    }

    edit = () => {
        this.polygon.setOptions({editable: true, snapable: false})
        this.neighbourLabel.setMap(null)
    }

    getCommonCoordinates = otherZone => {
        // default exact match behaviour
        return this.getCoordinates().filter(coord1 => {
            return otherZone.getCoordinates().some(coord2 => coord1.lat == coord2.lat && coord1.lng == coord2.lng)
        })
    }

    getCommonCoordinatesWithTolerance = (otherZone, thresholdMeters = 1) => {
        // returns coordinates from otherZone which are within thresholdMeters of any vertex in this zone
        const myCoords = this.getCoordinates()
        const otherCoords = otherZone.getCoordinates()
        const common = []
        for(let i = 0; i < myCoords.length; i++){
            const coord1 = myCoords[i]
            for(let j = 0; j < otherCoords.length; j++){
                const coord2 = otherCoords[j]
                const d = google.maps.geometry.spherical.computeDistanceBetween(
                    new google.maps.LatLng(coord1.lat, coord1.lng),
                    new google.maps.LatLng(coord2.lat, coord2.lng)
                )
                if(d <= thresholdMeters){
                    common.push(coord1)
                    break
                }
            }
        }
        return common
    }

    getSharedVertexIndices = (otherZones, thresholdMeters = 2) => {
        // Returns a Set of vertex indices that are shared with any neighbor
        // These should be preserved during simplification
        const path = this.polygon.getPath()
        const sharedIndices = new Set()

        for(let i = 0; i < path.getLength(); i++){
            const pt = path.getAt(i)
            const ptLatLng = new google.maps.LatLng(pt.lat(), pt.lng())

            for(let other of otherZones){
                if(other.polygon.zIndex === this.polygon.zIndex) continue
                const otherPath = other.polygon.getPath()

                for(let j = 0; j < otherPath.getLength(); j++){
                    const otherPt = otherPath.getAt(j)
                    const d = google.maps.geometry.spherical.computeDistanceBetween(
                        ptLatLng,
                        new google.maps.LatLng(otherPt.lat(), otherPt.lng())
                    )
                    if(d <= thresholdMeters){
                        sharedIndices.add(i)
                        break
                    }
                }
                if(sharedIndices.has(i)) break
            }
        }

        return sharedIndices
    }

    getSharedVertexKeysExact = (otherZones) => {
        const coords = this.getCoordinates(8)
        if(!coords || !coords.length)
            return new Set()
        const sharedKeys = new Set()
        const toKey = (point) => `${point.lat.toFixed(8)},${point.lng.toFixed(8)}`

        for(let i = 0; i < coords.length; i++) {
            const coord = coords[i]
            for(let j = 0; j < otherZones.length; j++) {
                const other = otherZones[j]
                if(other.polygon.zIndex === this.polygon.zIndex)
                    continue
                const otherCoords = other.getCoordinates(8)
                if(otherCoords.some(otherCoord => otherCoord.lat === coord.lat && otherCoord.lng === coord.lng)) {
                    sharedKeys.add(toKey(coord))
                    break
                }
            }
        }

        return sharedKeys
    }

    getCoordinateCount = () => {
        return this.polygon.getPath().length
    }

    getCoordinates = (latLngPrecision = 5) => {
        if(!this.polygon) {
            console.error('Unable to get coordinates for polypoint, polygon has not been created')
            return
        }
        return this.polygon.getPath().getArray().map(point => {
            return {
                lat: parseFloat(point.lat().toFixed(latLngPrecision)),
                lng: parseFloat(point.lng().toFixed(latLngPrecision))
            }
        })
    }

    // Would eventually call "removeDuplicates()", followed by "match()", and finally "smooth()"
    // to be triggered whenever editing a zone ends, or when hitting submit (on the current zone being edited)
    // TODO - problem, is that "this" does not have a link to all mapZones (for Match), would have to be passed in as a parameter?
    // finalize = (mapZones) => {
        // this.removeDuplicates()
        // this.match(mapZones)
        // this.smooth()
    // }

    // TODO - this feature may **never** work - Google attempts to match a path to the nearest roads, but it has a few limitations
    // 1) This is meant to be for driven coordinates, and as such doesn't really fit polygons
    // 2) It has a limit of 100 points
    // snapToRoads = async () => {
    //     const path = (this.getCoordinates()).map(point => `${point.lat},${point.lng}`).join('|')
    //     const url = `https://roads.googleapis.com/v1/snapToRoads?path=${encodeURIComponent(path)}&key=AIzaSyBaijyvCruJ9RD1d40rahp8yYafw0xYH6U`

    //     try {
    //         const response = await fetch(url)
    //         const json = await response.json()
    //         const snappedCoordinates = json.snappedPoints.map(point => ({
    //             lat: point.location.latitude,
    //             lng: point.location.longitude
    //         }))
    //         if(snappedCoordinates)
    //             this.polygon.setPath(snappedCoordinates)
    //         else
    //             console.error('error retrieving snapped coordinates')
    //     } catch (error) {
    //         console.error('Error snapping to roads: ', error)
    //         return null
    //     }
    // }

    match = allMapZones => {
        const calculateDistance = (point1, point2) => {
            const deltaX = Math.abs(point2.lat - point1.lat)
            const deltaY = Math.abs(point2.lng - point1.lng)
            return Math.sqrt(deltaX * deltaY + deltaY * deltaX)
        }

        const calculateSegmentLength = segment => {
            let totalLength = 0
            //special case if there are only two points in the list
            if(segment.length == 2) {
                return calculateDistance(segment[0], segment[1])
            }

            for (let i = 0; i < segment.length - 2; i++)
                totalLength += calculateDistance(segment[i], segment[i + 1])
            return totalLength
        }

        const extractSubsegment = (coordinates, start, end) => {
            // To solve the "wrap around the start point" issue, we can use the starting coordinate AS the start point, to avoid it happening!
            // This will let us consider the two "halves" of the polygon as determined by the end point for length
            // and assume the shorter one is correct.
            // We do still have to consider order when creating this new coordinate array however
            let startIndex = coordinates.findIndex(p => p.lat === start.lat && p.lng === start.lng)
            let endIndex = coordinates.findIndex(p => p.lat === end.lat && p.lng === end.lng)
                if(startIndex === -1 || endIndex === -1) {
                    return []
                }
            // we take from the start point of the matched segment, to the end of the list, then from the beginning of the list to the startIndex
            coordinates = Array.prototype.concat(coordinates.slice(startIndex), coordinates.slice(0, startIndex))
            // So start index will now always be zero, which prevents wrapping around, and end index MUST be contained within the array somewhere
            endIndex = coordinates.findIndex(p => p.lat === end.lat && p.lng === end.lng)
            // Now we have two possible paths between the two points
            // we choose the "shortest" of the two as the most likely candidate
            const path1 = coordinates.slice(0, endIndex + 1)
            // We have to duplicate the start point here to include the final path
            const path2 = Array.prototype.concat(coordinates.slice(endIndex), [coordinates[0]])
            if(calculateSegmentLength(path1) < calculateSegmentLength(path2))
                return path1
            return path2
        }

        /**
         * Takes two arrays of lat lng with shared first and last coordinates, and combines them to make a singular path
         * This allows streamlining and straightening of coordinates before saving, and should also clean up whitespace between zones
         * @param {Array[{lat, lng}]} segment1
         * @param {Array[{lat, lng}]} segment2
         */
        const mergeSegments = (segment1, segment2) => {
            // If the two segments are going in opposite directions, reverse one of them
            if(segment1[0].lat == segment2[segment2.length - 1].lat && segment1[0].lng == segment2[segment2.length - 1].lng)
                segment2 = segment2.reverse()

            // Filter out all duplicates from segment 2 (treat segment1 as a sort of "master")
            segment2 = segment2.filter(p2 => {
                return !segment1.some(p1 => p2.lat == p1.lat && p2.lng == p1.lng)
            })

            // pop the first element from segment1, as it is now the only instance of the starting point
            let mergedSegment = [segment1.shift()]
            while(segment1.length > 0 || segment2.length > 0) {
                //if either segment is empty, simply shift from the other one
                if(segment2.length == 0) {
                    mergedSegment.push(segment1.shift())
                } else if(segment1.length == 0) {
                    mergedSegment.push(segment2.shift())
                } else {
                    //otherwise, compute the distance between the next points to see which is closer, and shift that one
                    const lastPoint = mergedSegment[mergedSegment.length - 1]
                    const distanceToNextPoint1 = google.maps.geometry.spherical.computeDistanceBetween(segment1[0], lastPoint)
                    const distanceToNextPoint2 = google.maps.geometry.spherical.computeDistanceBetween(segment2[0], lastPoint)
                    if(distanceToNextPoint1 < distanceToNextPoint2)
                        mergedSegment.push(segment1.shift())
                    else
                        mergedSegment.push(segment2.shift())
                }
            }
            //repeat until both arrays are empty, and return the result
            return mergedSegment
        }

        const replaceSegment = (zone, mergedSegment) => {
                const startPoint = mergedSegment[0]
                const endPoint = mergedSegment[mergedSegment.length - 1]
                if(!startPoint || !endPoint) {
                    console.error('replaceSegment error', zone.name, mergedSegment)
                    return
                }
                const coordinates = zone.getCoordinates()
                const startIndex = coordinates.findIndex(p => p.lat === startPoint.lat && p.lng === startPoint.lng)
                const endIndex = coordinates.findIndex(p => p.lat === endPoint.lat && p.lng === endPoint.lng)
                if(startIndex === -1 || endIndex === -1){
                    console.error('replaceSegment could not find start/end in zone', zone.name)
                    return
                }

                // Rotate coordinates so startIndex becomes 0 to avoid wrap-around special cases
                const rotated = Array.prototype.concat(coordinates.slice(startIndex), coordinates.slice(0, startIndex))
                const newEndIndex = rotated.findIndex(p => p.lat === endPoint.lat && p.lng === endPoint.lng)
                if(newEndIndex === -1){
                    console.error('replaceSegment unable to locate end after rotation', zone.name)
                    return
                }
                // Build new rotated path: replace the segment [0..newEndIndex] with mergedSegment
                const newRotatedPath = Array.prototype.concat(mergedSegment, rotated.slice(newEndIndex + 1))

                // Rotate back so that the polygon has the same starting vertex as before
                const originalFirst = coordinates[0]
                const origPos = newRotatedPath.findIndex(p => p.lat === originalFirst.lat && p.lng === originalFirst.lng)
                let newPath = []
                if(origPos === -1){
                    // can't find original first point; just use the new rotated path
                    newPath = newRotatedPath
                } else {
                    newPath = Array.prototype.concat(newRotatedPath.slice(origPos), newRotatedPath.slice(0, origPos))
                }
                zone.polygon.setPath(newPath)
        }

        allMapZones.forEach(zone => {
            if(zone.polygon.zIndex == this.polygon.zIndex)
                return
            const commonPoints = this.getCommonCoordinatesWithTolerance(zone, 1)
            if(commonPoints.length <= 1)
                return
            for (let i = 0; i < commonPoints.length - 2; i++) {
                const start = commonPoints[i];
                const end = commonPoints[i + 1];

                const segment1 = extractSubsegment(this.getCoordinates(), start, end);
                const segment2 = extractSubsegment(zone.getCoordinates(), start, end);
                const mergedSegment = mergeSegments(segment1, segment2)
                if(mergedSegment && mergedSegment.length) {
                    replaceSegment(zone, mergedSegment)
                    replaceSegment(this, mergedSegment)
                }
            }
        })
    }

    removeDuplicates = (options = {}) => {
        // remove near-duplicate vertices within a small tolerance (meters)
        const tolMeters = 0.5
        const preserveKeys = options.preserveKeys ?? new Set()
        const toKey = (point) => `${point.lat().toFixed(8)},${point.lng().toFixed(8)}`
        const path = this.polygon.getPath()
        const filtered = []
        for(let i=0;i<path.getLength();i++){
            const pt = path.getAt(i)
            const ptKey = toKey(pt)
            if(preserveKeys.has(ptKey)) {
                filtered.push(pt)
                continue
            }
            let keep = true
            for(let j=0;j<filtered.length;j++){
                const f = filtered[j]
                const fKey = toKey(f)
                const d = google.maps.geometry.spherical.computeDistanceBetween(pt, f)
                if(d <= tolMeters){
                    if(preserveKeys.has(fKey)) {
                        keep = false
                        break
                    }
                    keep = false
                    break
                }
            }
            if(keep) filtered.push(pt)
        }
        this.polygon.setPath(filtered)
        try{ spatialIndex.updateZone(this) } catch(e){}
    }

    smooth = (variance, otherZones = null) => {
        // variance is expected in meters (user-facing). If not provided, default to 2 meters.
        // otherZones: if provided, preserve vertices shared with neighbors
        const toleranceMeters = (typeof variance === 'number' && !isNaN(variance)) ? variance : 2
        const coords = this.getCoordinates(8)
        if(!coords || coords.length < 3) return

        // Identify shared vertices to preserve
        const sharedIndices = otherZones ? this.getSharedVertexIndices(otherZones, toleranceMeters) : new Set()

        // compute reference latitude (mean) to scale longitude degrees to approximate meters
        const refLat = coords.reduce((s,c) => s + c.lat, 0) / coords.length
        const lngScale = Math.cos(refLat * Math.PI / 180)

        // convert meter tolerance to degree-of-latitude equivalent
        const metersPerDegLat = 111320.0
        const tolDeg = Math.max(1e-9, toleranceMeters / metersPerDegLat)

        // prepare points scaled so lat and (lng * lngScale) are comparable in degree units
        // Mark shared vertices so simplify preserves them
        const pts = coords.map(({lat, lng}, idx) => ({ 
            x: lat, 
            y: lng * lngScale,
            _preserve: sharedIndices.has(idx)
        }))

        // run Douglas-Peucker simplify (simplify expects x/y units matching tolerance)
        // Modified simplify call to respect _preserve flag
        const simplified = this.simplifyWithPreserve(pts, tolDeg)
        if(!simplified || simplified.length < 3) return

        // convert back to lat/lng
        const path = simplified.map(p => ({ lat: p.x, lng: p.y / lngScale }))
        this.polygon.setPath(path)
        try{ spatialIndex.updateZone(this) } catch(e){}
    }

    simplifyWithPreserve = (points, tolerance) => {
        // Douglas-Peucker simplification that preserves points marked with _preserve
        if(points.length <= 2) return points

        // First, run standard simplify
        const simplified = simplify(points, tolerance)

        // Then, reinsert any preserved points that were removed
        const result = []
        let simpleIdx = 0

        for(let i = 0; i < points.length; i++){
            if(points[i]._preserve){
                // This point must be included
                // Find its position in the simplified array or insert it
                if(simpleIdx < simplified.length && 
                   simplified[simpleIdx].x === points[i].x && 
                   simplified[simpleIdx].y === points[i].y){
                    // Point was preserved by simplify
                    result.push(simplified[simpleIdx])
                    simpleIdx++
                } else {
                    // Point was removed, reinsert it
                    result.push(points[i])
                }
            } else if(simpleIdx < simplified.length &&
                      simplified[simpleIdx].x === points[i].x &&
                      simplified[simpleIdx].y === points[i].y){
                // Non-preserved point that simplify kept
                result.push(simplified[simpleIdx])
                simpleIdx++
            }
        }

        return result.length >= 3 ? result : points
    }

    snapVerticesToIndex = (thresholdMeters = 2) => {
        // For each vertex in this polygon, if there exists a nearby vertex from another zone within threshold, snap to it
        const path = this.polygon.getPath()
        const len = path.getLength()
        for(let i=0;i<len;i++){
            const pt = path.getAt(i)
            const candidates = spatialIndex.queryNearby(pt.lat(), pt.lng(), thresholdMeters)
            let best = null
            let bestDist = Infinity
            for(let j=0;j<candidates.length;j++){
                const cand = candidates[j]
                if(cand.zoneZIndex === this.polygon.zIndex) continue
                const candLatLng = new google.maps.LatLng(cand.lat, cand.lng)
                const d = google.maps.geometry.spherical.computeDistanceBetween(pt, candLatLng)
                if(d < bestDist){ bestDist = d; best = cand }
            }
            if(best && bestDist <= thresholdMeters){
                path.setAt(i, new google.maps.LatLng(best.lat, best.lng))
            }
        }
        try{ spatialIndex.updateZone(this) } catch(e){}
    }

    detectSharedBorders = (otherZones, thresholdMeters = 2) => {
        // Detect contiguous sequences of segments that form shared borders
        // Returns: [{neighbourZone, myBorderRun: {startIdx, endIdx, vertices}, theirBorderRun: {startIdx, endIdx, vertices}}]
        const myPath = this.polygon.getPath()
        const myLen = myPath.getLength()
        if(myLen < 2) return []

        const metersPerDegLat = 111320.0
        const toMetersXY = (lat, lng, refLat) => {
            const x = lng * (111320.0 * Math.cos(refLat * Math.PI / 180.0))
            const y = lat * metersPerDegLat
            return {x, y}
        }

        const distancePointToSegment = (px, py, ax, ay, bx, by) => {
            const vx = bx - ax, vy = by - ay
            const wx = px - ax, wy = py - ay
            const c1 = wx*vx + wy*vy
            if(c1 <= 0) return Math.sqrt((px-ax)*(px-ax) + (py-ay)*(py-ay))
            const c2 = vx*vx + vy*vy
            if(c1 >= c2) return Math.sqrt((px-bx)*(px-bx) + (py-by)*(py-by))
            const t = c1 / c2
            const projX = ax + t*vx, projY = ay + t*vy
            return Math.sqrt((px-projX)*(px-projX) + (py-projY)*(py-projY))
        }

        const sharedBorders = []

        for(let other of otherZones){
            if(other.polygon.zIndex === this.polygon.zIndex) continue
            const otherPath = other.polygon.getPath()
            const otherLen = otherPath.getLength()
            if(otherLen < 2) continue

            const refLat = (myPath.getAt(0).lat() + otherPath.getAt(0).lat()) / 2.0

            // Build a map of which of my segments match which of their segments
            const segmentMatches = [] // [{myIdx, theirIdx}]

            for(let i=0; i<myLen; i++){
                const mA = myPath.getAt(i)
                const mB = myPath.getAt((i+1) % myLen)
                const mAm = toMetersXY(mA.lat(), mA.lng(), refLat)
                const mBm = toMetersXY(mB.lat(), mB.lng(), refLat)

                for(let j=0; j<otherLen; j++){
                    const oA = otherPath.getAt(j)
                    const oB = otherPath.getAt((j+1) % otherLen)
                    const oAm = toMetersXY(oA.lat(), oA.lng(), refLat)
                    const oBm = toMetersXY(oB.lat(), oB.lng(), refLat)

                    const dist_mA_to_oSeg = distancePointToSegment(mAm.x, mAm.y, oAm.x, oAm.y, oBm.x, oBm.y)
                    const dist_mB_to_oSeg = distancePointToSegment(mBm.x, mBm.y, oAm.x, oAm.y, oBm.x, oBm.y)
                    const dist_oA_to_mSeg = distancePointToSegment(oAm.x, oAm.y, mAm.x, mAm.y, mBm.x, mBm.y)
                    const dist_oB_to_mSeg = distancePointToSegment(oBm.x, oBm.y, mAm.x, mAm.y, mBm.x, mBm.y)

                    const isCollinear = (dist_mA_to_oSeg <= thresholdMeters && dist_mB_to_oSeg <= thresholdMeters) ||
                                       (dist_oA_to_mSeg <= thresholdMeters && dist_oB_to_mSeg <= thresholdMeters)

                    if(isCollinear){
                        segmentMatches.push({myIdx: i, theirIdx: j})
                    }
                }
            }

            if(segmentMatches.length === 0) continue

            // Group matches into contiguous runs
            // Sort by myIdx to find consecutive sequences
            segmentMatches.sort((a, b) => a.myIdx - b.myIdx)

            let currentRun = null
            for(let match of segmentMatches){
                if(!currentRun || match.myIdx !== currentRun.myEnd + 1){
                    // Start a new run
                    if(currentRun){
                        sharedBorders.push({
                            neighbourZone: other,
                            myBorderRun: currentRun,
                            theirBorderRun: currentRun.theirIndices
                        })
                    }
                    currentRun = {
                        myStart: match.myIdx,
                        myEnd: match.myIdx,
                        theirIndices: [match.theirIdx]
                    }
                } else {
                    // Continue current run
                    currentRun.myEnd = match.myIdx
                    currentRun.theirIndices.push(match.theirIdx)
                }
            }
            // Push the last run
            if(currentRun){
                sharedBorders.push({
                    neighbourZone: other,
                    myBorderRun: currentRun,
                    theirBorderRun: currentRun.theirIndices
                })
            }
        }

        return sharedBorders
    }

    mergeSharedBorderVertices = (otherZones, thresholdMeters = 2) => {
        // Replace entire border sections atomically to ensure both zones share exact same vertices
        const sharedBorders = this.detectSharedBorders(otherZones, thresholdMeters)
        if(sharedBorders.length === 0) return

        console.log(`[${this.name}] Found ${sharedBorders.length} shared borders`)

        // Filter out overlapping borders - if two borders cover the same index range, keep only the longer one
        const filteredBorders = []
        const processedRanges = []
        for(let border of sharedBorders){
            const myStart = border.myBorderRun.myStart
            const myEnd = border.myBorderRun.myEnd
            const overlaps = processedRanges.some(range => 
                (myStart >= range.start && myStart <= range.end) ||
                (myEnd >= range.start && myEnd <= range.end) ||
                (myStart <= range.start && myEnd >= range.end)
            )
            if(!overlaps){
                filteredBorders.push(border)
                processedRanges.push({start: myStart, end: myEnd})
            } else {
                console.log(`  Skipping overlapping border: indices ${myStart} to ${myEnd}`)
            }
        }

        const metersPerDegLat = 111320.0
        const toMetersXY = (lat, lng, refLat) => {
            const x = lng * (111320.0 * Math.cos(refLat * Math.PI / 180.0))
            const y = lat * metersPerDegLat
            return {x, y}
        }

        for(let border of filteredBorders){
            console.log(`[${this.name}] Processing border with ${border.neighbourZone.name}`)
            console.log(`  My indices: ${border.myBorderRun.myStart} to ${border.myBorderRun.myEnd}`)
            console.log(`  Their indices:`, border.myBorderRun.theirIndices)
            const other = border.neighbourZone
            const myPath = this.polygon.getPath()
            const otherPath = other.polygon.getPath()
            const refLat = (myPath.getAt(0).lat() + otherPath.getAt(0).lat()) / 2.0

            // Extract vertex sequences along the shared border
            const myBorder = border.myBorderRun
            const theirBorder = border.theirBorderRun

            // Collect all unique vertices from both border runs
            // For a border from index A to B, we need vertices at A, A+1, ..., B, B+1
            // because each segment index i represents the edge from vertex i to vertex i+1
            const myVertices = []
            for(let i = myBorder.myStart; i <= myBorder.myEnd + 1; i++){
                const idx = i % myPath.getLength()
                const pt = myPath.getAt(idx)
                myVertices.push({lat: pt.lat(), lng: pt.lng()})
            }

            const theirVertices = []
            const theirIndices = theirBorder.sort((a, b) => a - b) // Sort to find range
            const theirStart = theirIndices[0]
            const theirEnd = theirIndices[theirIndices.length - 1]
            for(let i = theirStart; i <= theirEnd + 1; i++){
                const idx = i % otherPath.getLength()
                const pt = otherPath.getAt(idx)
                theirVertices.push({lat: pt.lat(), lng: pt.lng()})
            }

            // Merge the two vertex lists: union of all points, sorted by position along the border
            // Use the first and last points to define a "border axis"
            const borderStart = myVertices[0]
            const borderEnd = myVertices[myVertices.length - 1]
            const bsm = toMetersXY(borderStart.lat, borderStart.lng, refLat)
            const bem = toMetersXY(borderEnd.lat, borderEnd.lng, refLat)
            const axisVx = bem.x - bsm.x, axisVy = bem.y - bsm.y
            const axisLen = Math.sqrt(axisVx*axisVx + axisVy*axisVy)

            const allVertices = new Map() // key: position along axis -> {lat, lng}

            const addVertex = (v) => {
                const vm = toMetersXY(v.lat, v.lng, refLat)
                const dx = vm.x - bsm.x, dy = vm.y - bsm.y
                const pos = (axisLen > 0) ? (dx*axisVx + dy*axisVy) / (axisLen*axisLen) : 0
                const key = pos.toFixed(8)
                if(!allVertices.has(key)){
                    allVertices.set(key, {lat: v.lat, lng: v.lng, pos})
                }
            }

            myVertices.forEach(addVertex)
            theirVertices.forEach(addVertex)

            // Sort by position along border
            const mergedVertices = Array.from(allVertices.values()).sort((a, b) => a.pos - b.pos)

            console.log(`  Before merge: my path has ${myPath.getLength()} vertices, their path has ${otherPath.getLength()} vertices`)
            console.log(`  Merged border will have ${mergedVertices.length} vertices`)
            console.log(`  First merged vertex:`, mergedVertices[0])
            console.log(`  Last merged vertex:`, mergedVertices[mergedVertices.length - 1])

            // Replace the border sections in both polygons
            // Remove old vertices from myStart to myEnd+1 (inclusive) and insert merged sequence
            const myNewPath = []
            for(let i = 0; i < myPath.getLength(); i++){
                if(i < myBorder.myStart || i > myBorder.myEnd + 1){
                    // Keep vertices outside the border range
                    const pt = myPath.getAt(i)
                    myNewPath.push(new google.maps.LatLng(pt.lat(), pt.lng()))
                } else if(i === myBorder.myStart){
                    // At the start of the border, insert all merged vertices
                    mergedVertices.forEach(v => myNewPath.push(new google.maps.LatLng(v.lat, v.lng)))
                }
                // Skip indices myStart+1 through myEnd+1 (they're being replaced by merged vertices)
            }
            myPath.clear()
            myNewPath.forEach(pt => myPath.push(pt))

            console.log(`  After replacement: my path now has ${myPath.getLength()} vertices`)

            // For their polygon: similar replacement
            // Need to reverse merged vertices if their border runs in opposite direction
            const theirNewPath = []
            for(let i = 0; i < otherPath.getLength(); i++){
                if(i < theirStart || i > theirEnd + 1){
                    // Keep vertices outside the border range
                    const pt = otherPath.getAt(i)
                    theirNewPath.push(new google.maps.LatLng(pt.lat(), pt.lng()))
                } else if(i === theirStart){
                    // At the start of the border, insert all merged vertices (may need to reverse)
                    mergedVertices.forEach(v => theirNewPath.push(new google.maps.LatLng(v.lat, v.lng)))
                }
                // Skip indices theirStart+1 through theirEnd+1
            }
            otherPath.clear()
            theirNewPath.forEach(pt => otherPath.push(pt))

            console.log(`  After replacement: their path now has ${otherPath.getLength()} vertices`)

            // Update spatial indices
            try{ spatialIndex.updateZone(other) } catch(e){}
        }

        try{ spatialIndex.updateZone(this) } catch(e){}
    }

    simplifyStreaming = (thresholdMeters = 2, options = {}) => {
        // Anchor-based streaming straight-line simplifier
        // Keep first point. Use anchor A and candidate B; iterate C through subsequent points.
        // If distance from B to line A->C <= thresholdMeters, drop B; otherwise emit B and advance A to B.
        const path = this.polygon.getPath()
        const n = path.getLength()
        if(n <= 2) return

        const preserveKeys = options.preserveSharedVertices && options.otherZones
            ? this.getSharedVertexKeysExact(options.otherZones)
            : new Set()
        const toKey = (point) => `${point.lat.toFixed(8)},${point.lng.toFixed(8)}`

        const metersPerDegLat = 111320.0

        const toMetersXY = (lat, lng, refLat) => {
            const x = lat * metersPerDegLat
            const y = lng * (metersPerDegLat * Math.cos(refLat * Math.PI / 180.0))
            return { x, y }
        }

        const distancePointToLineMeters = (p, a, c) => {
            // use mean lat for scaling
            const refLat = (p.lat + a.lat + c.lat) / 3.0
            const P = toMetersXY(p.lat, p.lng, refLat)
            const A = toMetersXY(a.lat, a.lng, refLat)
            const C = toMetersXY(c.lat, c.lng, refLat)
            const vx = C.x - A.x, vy = C.y - A.y
            const wx = P.x - A.x, wy = P.y - A.y
            const vv = vx*vx + vy*vy
            if(vv === 0) return Math.hypot(wx, wy)
            // perpendicular distance to infinite line
            const cross = Math.abs(vx*wy - vy*wx)
            return cross / Math.sqrt(vv)
        }

        // Build simplified list of LatLng objects
        let out = []
        const first = path.getAt(0)
        out.push({ lat: first.lat(), lng: first.lng() })

        let A = { lat: first.lat(), lng: first.lng() }
        let Bidx = 1
        while(Bidx < n) {
            const B = { lat: path.getAt(Bidx).lat(), lng: path.getAt(Bidx).lng() }
            let Cidx = Bidx + 1
            // if B is last, keep it and break
            if(Cidx >= n){ out.push(B); break }

            let C = { lat: path.getAt(Cidx).lat(), lng: path.getAt(Cidx).lng() }
            const d = distancePointToLineMeters(B, A, C)
            if(d <= thresholdMeters) {
                // drop B; move Bidx forward (anchor A remains)
                Bidx = Cidx
                continue
            } else {
                // keep B, advance anchor
                out.push(B)
                A = B
                Bidx = Cidx
            }
        }

        // Ensure last point is present
        const last = path.getAt(n-1)
        const lastObj = { lat: last.lat(), lng: last.lng() }
        const lastKey = `${lastObj.lat.toFixed(8)},${lastObj.lng.toFixed(8)}`
        const hasLast = out.some(p => `${p.lat.toFixed(8)},${p.lng.toFixed(8)}` === lastKey)
        if(!hasLast) out.push(lastObj)

        if(preserveKeys.size) {
            const outKeys = new Set(out.map(point => toKey(point)))
            const merged = []
            const mergedKeys = new Set()
            const original = this.getCoordinates(8)
            original.forEach(point => {
                const key = toKey(point)
                if((outKeys.has(key) || preserveKeys.has(key)) && !mergedKeys.has(key)) {
                    merged.push(point)
                    mergedKeys.add(key)
                }
            })
            if(merged.length >= 3)
                out = merged
        }

        // Replace polygon path with simplified points
        try{
            this.polygon.setPath(out)
        } catch(e){
            console.error('simplifyStreaming setPath error', e)
        }
        try{ spatialIndex.updateZone(this) } catch(e){}
    }
}

