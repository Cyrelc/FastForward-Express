import {topology} from 'topojson-server'
import {presimplify, simplify as topoSimplify} from 'topojson-simplify'
import {feature as topoFeature} from 'topojson-client'

// Convert mapZones (array of Zone instances) into a GeoJSON FeatureCollection
function zonesToFeatureCollection(mapZones){
    const features = mapZones.map(zone => {
        const coords = zone.getCoordinates(8).map(pt => [pt.lng, pt.lat])
        // ensure ring is closed
        if(coords.length && (coords[0][0] !== coords[coords.length-1][0] || coords[0][1] !== coords[coords.length-1][1])){
            coords.push([coords[0][0], coords[0][1]])
        }
        return {
            type: 'Feature',
            properties: { zIndex: zone.polygon.zIndex },
            geometry: { type: 'Polygon', coordinates: [coords] }
        }
    })
    return { type: 'FeatureCollection', features }
}

// Simplify all zones using TopoJSON simplifier to preserve shared arcs/topology
export function simplifyZones(mapZones, tolerance = 1e-6){
    if(!mapZones || !mapZones.length) return
    const geo = zonesToFeatureCollection(mapZones)
    // create topology
    // choose a quantization value based on tolerance to avoid topology artifacts
    // quantization must be a positive integer; larger values preserve more precision
    const quant = Math.max(1, Math.round(1 / Math.max(tolerance, 1e-9)))
    const topo = topology({ zones: geo }, { quantization: quant })
    // presimplify and simplify; tolerance is applied after presimplify
    presimplify(topo)
    topoSimplify(topo, tolerance)
    // convert back
    const simplified = topoFeature(topo, topo.objects.zones)
    // simplified may be a FeatureCollection or Feature
    const features = (simplified.type === 'FeatureCollection') ? simplified.features : [simplified]
    // Map simplified geometries back to zones using zIndex property for robust mapping
    const featureMap = new Map()
    features.forEach(feat => {
        const z = feat && feat.properties && typeof feat.properties.zIndex !== 'undefined' ? feat.properties.zIndex : null
        if(z !== null) featureMap.set(Number(z), feat)
    })
    for(let i=0;i<mapZones.length;i++){
        const zone = mapZones[i]
        const feat = featureMap.get(zone.polygon.zIndex)
        if(!feat || !feat.geometry || !feat.geometry.coordinates) continue
        const polyCoords = feat.geometry.coordinates[0]
        const path = polyCoords.map(([lng, lat]) => ({ lat: lat, lng: lng }))
        try{
            zone.polygon.setPath(path)
            // update spatial index if present
            try{ 
                const spatialIndex = require('./spatialIndex').default
                spatialIndex.updateZone(zone)
            } catch(e){ /* ignore if not available */ }
        } catch(e){ console.error('Failed to set simplified path for zone', zone, e) }
    }
}

export default { simplifyZones }
