const fs = require('fs')

function readZonesFromFile(path){
    const txt = fs.readFileSync(path, 'utf8')
    const re = /\[\{\"lat\"[\s\S]*?\}\]/g
    const matches = txt.match(re)
    if(!matches) return []
    return matches.map(m => JSON.parse(m))
}

function metersPerDegLat(){ return 111320.0 }

function toMetersXY(lat, lng, refLat){
    const m = metersPerDegLat()
    return { x: lat * m, y: lng * (m * Math.cos(refLat * Math.PI / 180.0)) }
}

function distancePointToLineMeters(p, a, c){
    const refLat = (p.lat + a.lat + c.lat) / 3.0
    const P = toMetersXY(p.lat, p.lng, refLat)
    const A = toMetersXY(a.lat, a.lng, refLat)
    const C = toMetersXY(c.lat, c.lng, refLat)
    const vx = C.x - A.x, vy = C.y - A.y
    const wx = P.x - A.x, wy = P.y - A.y
    const vv = vx*vx + vy*vy
    if(vv === 0) return Math.hypot(wx, wy)
    const cross = Math.abs(vx*wy - vy*wx)
    return cross / Math.sqrt(vv)
}

function simplifyStreaming(zone, thresholdMeters){
    if(zone.length <= 2) return zone.slice()
    const out = []
    out.push(zone[0])
    let A = zone[0]
    let Bidx = 1
    const n = zone.length
    while(Bidx < n){
        const B = zone[Bidx]
        const Cidx = Bidx + 1
        if(Cidx >= n){ out.push(B); break }
        const C = zone[Cidx]
        const d = distancePointToLineMeters(B, A, C)
        if(d <= thresholdMeters){
            // drop B
            Bidx = Cidx
            continue
        } else {
            out.push(B)
            A = B
            Bidx = Cidx
        }
    }
    // ensure last
    const last = zone[n-1]
    const keyLast = `${last.lat.toFixed(8)},${last.lng.toFixed(8)}`
    const hasLast = out.some(p => `${p.lat.toFixed(8)},${p.lng.toFixed(8)}` === keyLast)
    if(!hasLast) out.push(last)
    return out
}

function run(){
    const zones = readZonesFromFile(__dirname + '/../zones_temp.txt')
    if(!zones || zones.length === 0){ console.error('no zones'); process.exit(1) }
    console.log('Parsed', zones.length, 'zones')
    const before = zones.map(z => z.length)
    const threshold = process.argv[2] ? parseFloat(process.argv[2]) : 2.0
    const afterZones = zones.map(z => simplifyStreaming(z, threshold))
    const after = afterZones.map(z => z.length)
    console.log('Vertex counts before:', before)
    console.log('Vertex counts after :', after)
    // compute removed points count
    before.forEach((b,i) => console.log(`Zone ${i} removed ${b - after[i]} points with threshold ${threshold}m`))
    fs.writeFileSync(__dirname + '/zones_stream_simplified.json', JSON.stringify(afterZones, null, 2))
    console.log('Wrote tools/zones_stream_simplified.json')
}

run()
