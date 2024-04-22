import React, {useEffect, useState} from 'react'
import PolySnapper from '../../../../../../public/js/polysnapper-master/polysnapper.js'

export default function useMap() {
    const [defaultZoneType, setDefaultZoneType] = useState('internal')
    const [drawingMap, setDrawingMap] = useState(0)
    const [editZoneZIndex, setEditZoneZIndex] = useState(null)
    const [latLngPrecision, setLatLngPrecision] = useState(5)
    const [map, setMap] = useState(undefined)
    const [mapCenter, setMapCenter] = useState(new google.maps.LatLng(53.544389, -113.4909266))
    const [drawingManager, setDrawingManager] = useState(undefined)
    const [mapZones, setMapZones] = useState([])
    const [mapZoom, setMapZoom] = useState(11)
    const [polySnapper, setPolySnapper] = useState(null)
    const [savingMap, setSavingMap] = useState(100)
    const [snapPrecision, setSnapPrecision] = useState(100)

    useEffect(() => {
        const activeZone = mapZones.find(zone => zone.polygon.zIndex == editZoneZIndex)
        if(!activeZone)
            return
        mapZones.forEach(zone => {
            if(zone.polygon.zIndex === editZoneZIndex)
                zone.edit()
            else {
                zone.neighbourLabel.setMap(activeZone.getCommonCoordinates(zone).length ? map : null)
                zone.polygon.setOptions({editable: false, snapable: true})
                zone.viewDetails = false
            }
        })
        const polySnapper = new PolySnapper({
            map: map,
            threshold: snapPrecision,
            polygons: mapZones.map(mapZone => mapZone.polygon),
            hidePOI: true,
        })
        polySnapper.enable(activeZone.polygon.zIndex)
        setPolySnapper(polySnapper)
    }, [editZoneZIndex])

    const createZone = () => {}

    const deleteZone = () => {}

    const handleZoneChange = (event, zIndex) => {
        
    }

    return {
        defaultZoneType,
        drawingManager,
        drawingMap,
        editZoneZIndex,
        latLngPrecision,
        map,
        mapCenter,
        mapZones,
        mapZoom,
        polySnapper,
        savingMap,
        setDefaultZoneType,
        setDrawingMap,
        setDrawingManager,
        setEditZoneZIndex,
        setLatLngPrecision,
        setMap,
        setMapCenter,
        setMapZones,
        setMapZoom,
        setPolySnapper,
        setSavingMap,
        setSnapPrecision,
        snapPrecision,
    }
}

