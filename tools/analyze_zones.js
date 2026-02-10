const fs = require('fs')
const path = require('path')

function readZonesTemp(){
  const p = path.resolve(__dirname, '..', 'zones_temp.txt')
  const txt = fs.readFileSync(p,'utf8')
  // try to capture arrays that sit inside the table pipes: | [ {"lat":... } ] |
  const regex = /\|(\s*\[\{\"lat\"[\s\S]*?\}\])\s*\|/g
  const results = []
  let m
  while((m = regex.exec(txt)) !== null){
    try{ results.push(JSON.parse(m[1])) } catch(e){ /* ignore parse errors */ }
  }
  return results
}

function coordKey(c){ return `${c.lat.toFixed(6)},${c.lng.toFixed(6)}` }

function findSharedRuns(a,b){
  const bIndex = new Map()
  for(let i=0;i<b.length;i++) bIndex.set(coordKey(b[i]), i)
  const runs = []
  for(let i=0;i<a.length;i++){
    const k = coordKey(a[i])
    if(!bIndex.has(k)) continue
    // try to grow run forward in both arrays
    let ai = i, bi = bIndex.get(k)
    const run = []
    while(ai < a.length && bi < b.length && coordKey(a[ai]) === coordKey(b[bi])){
      run.push({lat: a[ai].lat, lng: a[ai].lng})
      ai++; bi++
    }
    if(run.length) runs.push({startA: i, startB: bIndex.get(k), length: run.length, coords: run})
  }
  return runs
}

function analyze(){
  const zones = readZonesTemp()
  if(zones.length < 2){ console.error('need at least 2 zone arrays in zones_temp.txt'); process.exit(1) }
  const [z1,z2] = zones
  console.log('zone A points:', z1.length)
  console.log('zone B points:', z2.length)
  const shared = findSharedRuns(z1,z2)
  console.log('\nFound shared contiguous runs (exact coordinate equality):\n')
  shared.sort((a,b) => b.length - a.length)
  shared.forEach((r, idx) => {
    console.log(`${idx+1}. startA=${r.startA}, startB=${r.startB}, length=${r.length}`)
  })
  // show the top 5 runs coords
  console.log('\nTop runs sample coords:\n')
  shared.slice(0,5).forEach((r,idx) => {
    console.log(`Run ${idx+1} length ${r.length}`)
    console.log(r.coords.slice(0,5).map(c => coordKey(c)).join(' | '))
  })

  // compute nearby shared using tolerant distance (meters)
  // use simple haversine
  function haversine(a,b){
    const R = 6371000
    const toRad = v => v * Math.PI/180
    const dLat = toRad(b.lat-a.lat)
    const dLon = toRad(b.lng-a.lng)
    const la = toRad(a.lat), lb = toRad(b.lat)
    const sinDlat = Math.sin(dLat/2)
    const sinDlon = Math.sin(dLon/2)
    const c = 2 * Math.atan2(Math.sqrt(sinDlat*sinDlat + Math.cos(la)*Math.cos(lb)*sinDlon*sinDlon), Math.sqrt(1 - (sinDlat*sinDlat + Math.cos(la)*Math.cos(lb)*sinDlon*sinDlon)))
    return R * c
  }

  const toleranceMeters = 5
  const nearbyPairs = []
  for(let i=0;i<z1.length;i++){
    for(let j=0;j<z2.length;j++){
      const d = haversine(z1[i], z2[j])
      if(d <= toleranceMeters) nearbyPairs.push({i,j,d})
    }
  }
  console.log(`\nNearby vertex pairs within ${toleranceMeters}m: ${nearbyPairs.length}`)
  if(nearbyPairs.length > 0) console.log('Sample:', nearbyPairs.slice(0,10))

  // recommendations
  console.log('\nRecommendations:')
  console.log('- There are contiguous identical vertex sequences; prefer edge-based snapping and insertion rather than merging entire long segments')
  console.log('- Require minimal run length (e.g., >3 vertices or >50m) before merging segments')
  console.log('- Consider snapping tolerance of 1-5 meters, and a pre-simplify epsilon to remove redundant points before matching')
  console.log('- For robust results, build planar graph (TopoJSON/JSTS) and reconstruct polygons from shared arcs rather than greedy segment merges')
}

analyze()
