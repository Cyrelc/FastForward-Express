import React, {useState} from 'react'

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
    const [nextPolygonIndex, setNextPolygonIndex] = useState(0)
    const [polySnapper, setPolySnapper] = useState(null)
    const [savingMap, setSavingMap] = useState(100)
    const [snapPrecision, setSnapPrecision] = useState(100)

    const getNextPolygonIndex = () => {
        let newIndex = null
        setNextPolygonIndex(prevIndex => {
            newIndex = prevIndex + 1
            return newIndex
        })
        return newIndex
    }

    return {
        defaultZoneType,
        drawingManager,
        drawingMap,
        editZoneZIndex,
        latLngPrecision,
        getNextPolygonIndex,
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

