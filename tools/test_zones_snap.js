const fs = require('fs')
const RBush = require('rbush')

function readZonesFromFile(path){
    const txt = fs.readFileSync(path, 'utf8')
    // find JSON arrays that start with [{"lat"
    const re = /\[\{\"lat\"[\s\S]*?\}\]/g
    const matches = txt.match(re)
    if(!matches) return []
    return matches.map(m => JSON.parse(m))
}

function metersToLatDeg(m){ return m / 111320.0 }
function metersToLngDeg(m, lat){ return m / (111320.0 * Math.cos(lat * Math.PI / 180.0)) }

function haversineDistance(a, b){
    const R = 6371000
    const toRad = v => v * Math.PI / 180
    const dLat = toRad(b.lat - a.lat)
    const dLon = toRad(b.lng - a.lng)
    const lat1 = toRad(a.lat)
    const lat2 = toRad(b.lat)
    const sinDLat = Math.sin(dLat/2)
    const sinDLon = Math.sin(dLon/2)
    const aa = sinDLat*sinDLat + Math.cos(lat1)*Math.cos(lat2)*sinDLon*sinDLon
    const c = 2 * Math.atan2(Math.sqrt(aa), Math.sqrt(1-aa))
    return R * c
}

function buildSegmentIndex(zones){
    const idx = new RBush()
    const items = []
    zones.forEach((zone, zidx) => {
        for(let i=0;i<zone.length;i++){
            const a = zone[i]
            const b = zone[(i+1)%zone.length]
            const minLat = Math.min(a.lat, b.lat)
            const maxLat = Math.max(a.lat, b.lat)
            const minLng = Math.min(a.lng, b.lng)
            const maxLng = Math.max(a.lng, b.lng)
            items.push({minX: minLng, minY: minLat, maxX: maxLng, maxY: maxLat, zoneIndex: zidx, segIndex: i, a, b})
        }
    })
    idx.load(items)
    return idx
}

function querySegmentsNearby(index, lat, lng, meters){
    const latDelta = metersToLatDeg(meters)
    const lngDelta = metersToLngDeg(meters, lat)
    const bbox = { minX: lng - lngDelta, minY: lat - latDelta, maxX: lng + lngDelta, maxY: lat + latDelta }
    return index.search(bbox)
}

function projectToSegment(plat, plng, a, b){
    const metersPerDegLat = 111320.0
    const refLat = (a.lat + b.lat + plat) / 3.0
    const scaleLon = 111320.0 * Math.cos(refLat * Math.PI / 180.0)
    const mx = plng * scaleLon
    const my = plat * metersPerDegLat
    const ax = a.lng * scaleLon
    const ay = a.lat * metersPerDegLat
    const bx = b.lng * scaleLon
    const by = b.lat * metersPerDegLat
    const vx = bx - ax, vy = by - ay
    const wx = mx - ax, wy = my - ay
    const vv = vx*vx + vy*vy
    let t = 0
    if(vv > 0) t = (wx*vx + wy*vy) / vv
    t = Math.max(0, Math.min(1, t))
    const px = ax + t*vx
    const py = ay + t*vy
    const projLat = py / metersPerDegLat
    const projLng = px / scaleLon
    const dist = haversineDistance({lat: plat, lng: plng}, {lat: projLat, lng: projLng})
    return { lat: projLat, lng: projLng, t, dist }
}

function round6(p){ return { lat: +p.lat.toFixed(6), lng: +p.lng.toFixed(6) } }

function runTest(){
    const zones = readZonesFromFile(__dirname + '/../zones_temp.txt')
    if(!zones || zones.length === 0){
        console.error('No zones parsed')
        process.exit(1)
    }
    console.log('Parsed', zones.length, 'zones')
    const beforeCounts = zones.map(z => z.length)

    const segIndex = buildSegmentIndex(zones)

    const threshold = 1.0 // meters

    // operate on deep copies so we can compare before/after
    const zonesCopy = zones.map(z => z.map(p => ({lat: p.lat, lng: p.lng})))

    // For each vertex in each zone, find nearest segment from other zones and insert projection if within threshold
    for(let zi=0; zi<zonesCopy.length; zi++){
        const zone = zonesCopy[zi]
        for(let vi=0; vi<zone.length; vi++){
            const pt = zone[vi]
            const candidates = querySegmentsNearby(segIndex, pt.lat, pt.lng, threshold)
            let best = null
            for(let c of candidates){
                if(c.zoneIndex === zi) continue
                const p = projectToSegment(pt.lat, pt.lng, c.a, c.b)
                if(p.dist <= threshold){
                    if(!best || p.dist < best.p.dist) best = { seg: c, p }
                }
            }
            if(best){
                // snap vertex
                zone[vi].lat = best.p.lat
                zone[vi].lng = best.p.lng
                // insert into neighbour zone if not near existing vertex
                const neighbour = zonesCopy[best.seg.zoneIndex]
                const newPt = {lat: best.p.lat, lng: best.p.lng}
                let needInsert = true
                for(let k=0;k<neighbour.length;k++){
                    if(haversineDistance(neighbour[k], newPt) <= 0.5){ needInsert = false; break }
                }
                if(needInsert){
                    const insertPos = Math.min(best.seg.segIndex + 1, neighbour.length)
                    neighbour.splice(insertPos, 0, newPt)
                    // update segment index: rebuild fully later
                }
            }
        }
    }

    // rebuild segment index from modified zones
    const segIndex2 = buildSegmentIndex(zonesCopy)

    const afterCounts = zonesCopy.map(z => z.length)

    console.log('Vertex counts before:', beforeCounts)
    console.log('Vertex counts after :', afterCounts)

    // compute shared vertices (exact match rounded 6 decimals)
    function sharedVertices(z1, z2){
        const s = new Set(z1.map(p => `${p.lat.toFixed(6)},${p.lng.toFixed(6)}`))
        let count = 0
        for(const p of z2){ if(s.has(`${p.lat.toFixed(6)},${p.lng.toFixed(6)}`)) count++ }
        return count
    }

    for(let i=0;i<zonesCopy.length;i++){
        for(let j=i+1;j<zonesCopy.length;j++){
            console.log(`Shared vertices between zone ${i} and ${j}: before=${sharedVertices(zones[i], zones[j])} after=${sharedVertices(zonesCopy[i], zonesCopy[j])}`)
        }
    }

    // write modified zones to file for inspection
    fs.writeFileSync(__dirname + '/zones_temp_modified.json', JSON.stringify(zonesCopy, null, 2))
    console.log('Wrote modified zones to tools/zones_temp_modified.json')
}

runTest()
