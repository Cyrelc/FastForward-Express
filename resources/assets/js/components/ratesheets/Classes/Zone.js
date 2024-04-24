import polylabel from 'polylabel'
import simplify from 'simplify'

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
        this.additionalCosts = mapZone.additionalCosts
        this.additionalTime = mapZone.additionalTime
        this.fillColour = polyColours[`${mapZone.type}Fill`]
        this.name = mapZone.name
        this.neighbours = mapZone.neighbours
        this.polygon = mapZone.polygon
        this.strokeColour = polyColours[`${mapZone.type}Stroke`]
        this.type = mapZone.type
        this.zoneId = mapZone.zone_id

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
        return this.getCoordinates().filter(coord1 => {
            return otherZone.getCoordinates().some(coord2 => coord1.lat == coord2.lat && coord1.lng == coord2.lng)
        })
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
            if(mergedSegment.findIndex(point => point.lat == coordinates[0].lat && point.lng == coordinates[0].lng) > -1) {
                console.error('replacement segment contains start and end of polygon - special case', coordinates[0], mergedSegment)
                return
            }
            const startIndex = coordinates.findIndex(p => p.lat === startPoint.lat && p.lng === startPoint.lng)
            const endIndex = coordinates.findIndex(p => p.lat === endPoint.lat && p.lng === endPoint.lng)
            let newPath = []
            if(startIndex > endIndex) {
                newPath = Array.prototype.concat(coordinates.slice(0, endIndex), mergedSegment.reverse(), coordinates.slice(startIndex))
            } else 
                newPath = Array.prototype.concat(coordinates.slice(0, startIndex), mergedSegment, coordinates.slice(endIndex))
            zone.polygon.setPath(newPath)
        }

        allMapZones.forEach(zone => {
            if(zone.polygon.zIndex == this.polygon.zIndex)
                return
            const commonPoints = this.getCommonCoordinates(zone)
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

    removeDuplicates = () => {
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

    smooth = variance => {
        const myCoordinates = this.getCoordinates().map(({lat, lng}) => {return {x: lat, y: lng}})
        this.polygon.setPath(simplify(myCoordinates, 0.0001).map(({x, y}) => {return {lat: x, lng: y}}))
    }
}

