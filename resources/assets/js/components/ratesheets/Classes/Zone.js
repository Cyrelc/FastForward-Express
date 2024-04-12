import polylabel from 'polylabel'

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
        this.additionalCosts = mapZone.additional_costs
        this.additionalTime = mapZone.additional_time
        this.id = mapZone.id
        this.zoneId = mapZone.zone_id
        this.name = mapZone.name
        this.neighbours = mapZone.neighbours
        this.neighbourLabel = 
        this.type = mapZone.type
        if(mapZone.polygon)
            this.polygon = mapZone.polygon
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

    deletePolyPoint = (point) => {
        if(!this.polygon) {
            console.error('Unable to delete polypoint, polygon has not been created')
            return
        }
        this.polygon.getPath().removeAt(point.vertex);
    }

    getForStore() {
        var storeZone = {
            id: this.id,
            name: this.name,
            type: this.type,
            coordinates: JSON.stringify(this.getCoordinates()),
            zoneId: this.id
        }
        if(storeZone.type === 'peripheral') {
            storeZone.regularCost = this.additionalCosts.regular
            storeZone.additionalTime = this.additionalTime
        } else if(storeZone.type === 'outlying') {
            storeZone.additionalTime = this.additionalTime
            storeZone.directCost = this.additionalCosts.directCost
            storeZone.directRushCost = this.additionalCosts.directRushCost
            storeZone.rushCost = this.additionalCosts.rushCost
            storeZone.regularCost = this.additionalCosts.regularCost
        }
        return storeZone
    }

    // handleEdit() {
    //     const updated = mapState.mapZones.map(zone => {
    //         if(zone.id === id) {
    //             zone.polygon.setOptions({editable: true, snapable: false})
    //             zone.neighbourLabel.setMap(null)
    //             return {...zone, viewDetails: true}
    //         } else if(findCommonCoordinates(getCoordinates(activeZone.polygon), getCoordinates(zone.polygon)))
    //             zone.neighbourLabel.setMap(mapState.map)
    //         else
    //             zone.neighbourLabel.setMap(null)
    //         zone.polygon.setOptions({editable: false, snapable: true})
    //         return {...zone, viewDetails: false}
    //     })
    //     const polySnapper = new PolySnapper({
    //         map: mapState.map,
    //         threshold: mapState.snapPrecision,
    //         polygons: mapState.mapZones.map(mapZone => {return mapZone.polygon}),
    //         hidePOI: true,
    //     })
    //     polySnapper.enable(mapState.mapZones.filter(mapZone => mapZone.id === id)[0].polygon.zIndex)
    //     mapState.setMapZones(updated)
    //     mapState.setPolySnapper(polySnapper)
    // }

    hasCommonCoordinates = otherZone => {
        return this.getCoordinates().some(coord1 => {
            return otherZone.getCoordinates().some(coord2 => coord1.lat === coord2.lat && coord1.lng === coord2.lng)
        })
    }

    removeDuplicates() {
        let filtered = []
        let duplicates = []
        let count = 0
        const cleanedPath = this.polygon.getPath().forEach(polyPoint => {
            if(filtered.find(testPoint => testPoint.lat() === polyPoint.lat() && testPoint.lng() === polyPoint.lng()) === undefined)
                filtered.push(polyPoint)
            else {
                duplicates.push({lat: polyPoint.lat(), lng: polyPoint.lng()})
                count++
            }
        })
        console.log(`${count} duplicates found`, filtered.length, duplicates)
        this.polygon.setPath(filtered)
    }
}

