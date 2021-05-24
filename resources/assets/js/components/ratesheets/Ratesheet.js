import React, {Component} from 'react'
import {Button, Col, Modal, ProgressBar, Row, Tabs, Tab} from 'react-bootstrap'
import MapTab from './MapTab'
import SettingsTab from './SettingsTab'

var polygonNextIndex = 0;

export default class Ratesheet extends Component {
    constructor() {
        super()
        this.state = {
            key: 'settings',
            defaultZoneType: 'internal',
            deliveryTypes: [],
            holidayRate: '',
            latLngPrecision: 5,
            drawingMap: 0,
            map: undefined,
            mapCenter: new google.maps.LatLng(53.544389, -113.4909266),
            mapDrawingManager: undefined,
            mapZones: [],
            mapZoom: 12,
            name: '',
            palletRate: {},
            polyColours: {internalStroke : '#3651c9', internalFill: '#8491c9', outlyingStroke: '#d16b0c', outlyingFill: '#e8a466', peripheralStroke:'#2c9122', peripheralFill: '#3bd82d'},
            ratesheetId: null,
            savingMap: 100,
            snapPrecision: 200,
            timeRates: [],
            useInternalZonesCalc: true,
            weekendRate: '',
            weightRates: [],
            zoneRates: [],
        }
        this.handleChange = this.handleChange.bind(this)
        this.handlePalletRateChange = this.handlePalletRateChange.bind(this)
        this.handleTimeRateChange = this.handleTimeRateChange.bind(this)
        this.handleWeightRateChange = this.handleWeightRateChange.bind(this)
        this.handleZoneRateChange = this.handleZoneRateChange.bind(this)
        this.deleteZone = this.deleteZone.bind(this)
        this.editZone = this.editZone.bind(this)
        this.updateZone = this.updateZone.bind(this)
        this.store = this.store.bind(this)
    }

    componentDidMount() {
        const {match: {params}} = this.props
        const map = new google.maps.Map(document.getElementById('map'), {center: this.state.mapCenter, zoom: this.state.mapZoom, disableDefaultUI: true})
        const drawingManager = new google.maps.drawing.DrawingManager({
            drawingControlOptions: {
                drawingModes: ['polygon'],
                position: google.maps.ControlPosition.TOP_CENTER
            },
            polygonOptions: {clickable: true}
        })
        drawingManager.setMap(map)
        google.maps.event.addListener(drawingManager, 'polygoncomplete', event => {this.createPolygon(event)})
        this.setState({map: map, mapDrawingManager: drawingManager, ratesheetId: params.ratesheetId}, () => {
            document.title = params.ratesheetId ? 'Edit Ratesheet - ' + params.ratesheetId : 'Create Ratesheet'
            makeAjaxRequest(params.ratesheetId ? '/ratesheets/getModel/'  + params.ratesheetId : '/ratesheets/getModel/', 'GET', null, response => {
                response = JSON.parse(response)
                var timeRates = response.timeRates.map(rate => {
                    return {...rate, startTime: rate.startTime ? new Date(rate.startTime) : null, endTime: rate.endTime ? new Date(rate.endTime) : null}
                })
                this.setState({
                    deliveryTypes: response.deliveryTypes,
                    key: window.location.hash ? window.location.hash.substr(1) : 'settings',
                    name: response.name,
                    palletRate: response.palletRate,
                    timeRates: timeRates,
                    weightRates: response.weightRates,
                    zoneRates: response.zoneRates,
                    useInternalZonesCalc: response.useInternalZonesCalc
                })
                if(params.ratesheetId) {
                    response.mapZones.forEach((mapZone, zoneIndex) => {
                        const polygon = new google.maps.Polygon({
                                paths: mapZone.coordinates.map(coord => {return {lat: parseFloat(coord.lat), lng: parseFloat(coord.lng)}}),
                            })
                        polygon.setMap(this.state.map)
                        this.createPolygon(polygon, mapZone)
                    })
                    this.setState({drawingMap: 100})
                    this.state.mapDrawingManager.setOptions({polygonOptions: {clickable: true, zIndex: response.mapZones.length + 1}});
                }
            })
        })
    }

    componentDidUpdate(prevProps) {
        const {match: {params}} = this.props
        if(prevProps.match.params.ratesheetId != params.ratesheetId)
            window.location.reload()
    }

    handleChange(event, section, id) {
        const {name, value, type, checked} = event.target
        if(section) {
            const updated = this.state[section].map(obj => {
                if(obj.id === id)
                    return type === 'checkbox' ? {...obj, [name]: checked} : {...obj, [name]: value}
                return obj
            })
            this.setState({[section] : updated})
        } else
        if(name === 'key')
            window.location.hash = value
        type === 'checkbox' ? this.setState({ [name]: checked }) : this.setState({ [name]: value})
    }

    isEmpty(value){
        if(value === '' || value === undefined || value === 0)
            return true
        return false
    }

    handlePalletRateChange(event) {
        const { name, value } = event.target
        if(name === 'palletAdditionalWeightKgs')
            this.setState({palletRate: {...this.state.palletRate, [name]: value, palletAdditionalWeightLbs: kilogramsToPounds(value)}})
        else if (name === 'palletAdditionalWeightLbs')
            this.setState({palletRate: {...this.state.palletRate, [name] : value, palletAdditionalWeightKgs: poundsToKilograms(value)}})
        else if (name === 'palletBaseWeightKgs')
            this.setState({palletRate: {...this.state.palletRate, [name]: value, palletBaseWeightLbs: kilogramsToPounds(value)}})
        else if (name === 'palletBaseWeightLbs')
            this.setState({palletRate: {...this.state.palletRate, [name]: value, palletBaseWeightKgs: poundsToKilograms(value)}})
        else
            this.setState({palletRate: {...this.state.palletRate, [name] : value}})
    }

    handleTimeRateChange(event, id) {
        const {name, value} = event.target
        const updated = this.state.timeRates.map((obj, index, arr) => {
            if(obj.id === id)
                return {...obj, [name]: value}
            else 
                return obj
        })
        this.setState({timeRates: updated})
    }

    handleWeightRateChange(event, id) {
        const {name, value} = event.target
        const pounds = name === 'lbmax' ? value : value === '' ? value : Math.round(kilogramsToPounds(value))
        const kilograms = name === 'kgmax' ? value : value === '' ? value : Math.round(poundsToKilograms(value))
        var next
        var updated = this.state.weightRates.map((obj, index, arr) => {
            //if we've found the object
            if(obj.id === id) {
                //is there a next object?
                if(arr[index + 1] !== undefined)
                    //yes? store the index, so it can be updated next
                    next = index + 1
                return {...obj, lbmax: pounds === '' ? '' : +pounds, kgmax: kilograms === '' ? '' : +kilograms}
            } else if (index === next)
                return {...obj, lbmin: pounds === '' ? '' : (+pounds + 1), kgmin: kilograms == '' ? '' : (+kilograms + 1)}
            return obj
        })
        if(!this.isEmpty(kilograms) && updated[updated.length - 1].id === id) {
            //if this was the last element, and it's value is NO LONGER empty, we need a new row. Concatenate it.
            updated = updated.concat([{id: this.state.weightRates.length, lbmin: +pounds + 1, lbmax: '', kgmin: +kilograms + 1, kgmax: '', cost: undefined}])
        }
        else if (this.isEmpty(kilograms))
            //if the field was emptied, starting at the end of the list, remove all empty fields that have more than one empty field preceeding them
            while(typeof updated[updated.length - 2] != 'undefined' && this.isEmpty(updated[updated.length - 2].kgmax))
                updated.pop()
        this.setState({weightRates: updated})
    }

    handleZoneRateChange(event, id) {
        const {name, value} = event.target
        var index
        var updated = this.state.zoneRates.map((obj, i) => {
            if(obj.id == id) {
                index = i
                return {...obj, [name]: value}
            }
            return obj
        })
        if(!this.isEmpty(value) && updated[updated.length -1].id === id)
            updated = updated.concat([{id: this.state.zoneRates.length, cost: undefined, zones: this.state.zoneRates.length + 1}])
        else if(this.isEmpty(updated[index]['regularCost']) && this.isEmpty(updated[index]['rushCost'] && this.isEmpty(updated[index]['directCost'] && this.isEmpty(updated[index]['directCost']))))
            while(typeof updated[updated.length - 2] != 'undefined' 
                && this.isEmpty(updated[updated.length - 2]['regularCost']) 
                && this.isEmpty(updated[updated.length - 2]['rushCost']) 
                && this.isEmpty(updated[updated.length - 2]['directCost']) 
                && this.isEmpty(updated[updated.length - 2]['directRushCost']))
                updated.pop()
        this.setState({zoneRates: updated})
    }

    createPolygon(polygon, zone = null) {
        var strokeColour, fillColour, type
        if(zone) {
            strokeColour = this.state.polyColours[zone.type + 'Stroke']
            fillColour = this.state.polyColours[zone.type + 'Fill']
            type = zone.type
        } else {
            strokeColour = this.state.polyColours[this.state.defaultZoneType + 'Stroke']
            fillColour = this.state.polyColours[this.state.defaultZoneType + 'Fill']
            type = this.state.defaultZoneType
        }
        polygon.setOptions({strokeColor: strokeColour, fillColor: fillColour, zIndex: polygonNextIndex++})
        polygon.addListener('click', () => this.editZone(polygon.zIndex))
        google.maps.event.addListener(polygon.getPath(), 'insert_at', () => this.updateZone(polygon.zIndex))
        google.maps.event.addListener(polygon.getPath(), 'set_at', () => this.updateZone(polygon.zIndex))
        google.maps.event.addListener(polygon, 'rightclick', (point) => this.deletePolyPoint(point, polygon.zIndex));
        var newZone = {
            id: polygon.zIndex,
            name : zone ? zone.name : this.state.defaultZoneType + '_zone_' + polygon.zIndex,
            type: type,
            polygon: polygon,
            viewDetails: false,
            coordinates: this.getCoordinates(polygon),
            zoneId: zone ? zone.zone_id : null
        }
        if(type === 'peripheral') {
            const cost = zone ? JSON.parse(zone.additional_costs) : null
            newZone.regularCost = cost ? cost.regular : ''
            newZone.additionalTime = zone ? zone.additional_time : ''
        } else if (type === 'outlying') {
            const costs = zone ? JSON.parse(zone.additional_costs) : null
            newZone.regularCost = costs ? costs.regular : ''
            newZone.rushCost = costs ? costs.rush : ''
            newZone.directCost = costs ? costs.direct : ''
            newZone.directRushCost = costs ? costs.directRush : ''
            newZone.additionalTime = zone ? zone.additional_time : ''
        }
        this.setState({mapZones: this.state.mapZones.concat([newZone])})
        this.updateZone(polygon.zIndex, !zone)
        name === '' ? this.editZone(polygon.zIndex) : null;
    }

    deletePolyPoint(point, id) {
        if(point.vertex != null)
            this.state.mapZones.map((zone, index) => {
                if(zone.id === id) {
                    zone.polygon.getPath().removeAt(point.vertex);
                }
            })
    }

    deleteZone(id) {
        var deleteIndex = null
        this.state.mapZones.map((zone, index) => {
            if(zone.id === id) {
                deleteIndex = index
                zone.polygon.setMap(null)
            }
        })
        this.setState({mapZones: this.state.mapZones.filter((zone, index) => index !== deleteIndex)})
    }

    editZone(id) {
        const updated = this.state.mapZones.map(zone => {
            if(zone.id === id) {
                zone.polygon.setOptions({editable: true})
                return {...zone, viewDetails: true}
            }
            zone.polygon.setOptions({editable: false})
            return {...zone, viewDetails: false}
        })
        this.setState({mapZones: updated})
    }

    getCoordinates(polygon) {
        return polygon.getPath().getArray().map(point => {return {lat: parseFloat(point.lat().toFixed(this.state.latLngPrecision)), lng: parseFloat(point.lng().toFixed(this.state.latLngPrecision))}})
    }

    prepareZoneForStore(zone) {
        var storeZone = {id: zone.id, name: zone.name.slice(), type: zone.type.slice(), coordinates: JSON.stringify(this.getCoordinates(zone.polygon)), zoneId: zone.zoneId}
        if(storeZone.type === 'peripheral') {
            storeZone.regularCost = zone.regularCost
            storeZone.additionalTime = zone.additionalTime
        } else if(storeZone.type === 'outlying') {
            storeZone.additionalTime = zone.additionalTime
            storeZone.directCost = zone.directCost
            storeZone.directRushCost = zone.directRushCost
            storeZone.rushCost = zone.rushCost
            storeZone.regularCost = zone.regularCost
        }
        return storeZone
    }

    updateZone(id, useSnapping = true) {
        const updated = this.state.mapZones.map(zone => {
            if(zone.id === id) {
                if(useSnapping && this.state.snapPrecision > 0) {
                    var temp = zone.polygon.getPath()
                    zone.polygon.getPath().forEach((coord1, i) => {
                        var currentClosestDistance = this.state.snapPrecision;
                        this.state.mapZones.map((compZone) => {
                            if(compZone.id === id)
                                return
                            compZone.polygon.getPath().forEach((coord2) => {
                                const distanceBetween = google.maps.geometry.spherical.computeDistanceBetween(coord1, coord2);
                                if(distanceBetween < currentClosestDistance) {
                                    currentClosestDistance = distanceBetween;
                                    temp.i[i] = coord2
                                }
                            })
                        })
                    })
                    zone.polygon.setPath(temp)
                }
                return {...zone, coordinates: this.getCoordinates(zone.polygon)}
            } 
            return zone
        })
        this.setState({mapZones: updated})
    }

    store(){
        this.setState({savingMap: 0})
        var data = {
            name: this.state.name,
            holidayRate: this.state.holidayRate,
            weekendRate: this.state.weekendRate,
            palletRate: this.state.palletRate,
            ratesheetId : this.state.ratesheetId,
            useInternalZonesCalc: this.state.useInternalZonesCalc,
            deliveryTypes : this.state.deliveryTypes.slice(),
            weightRates : this.state.weightRates.slice(),
            zoneRates : this.state.zoneRates.slice(),
            mapZones : this.state.mapZones.map(zone => this.prepareZoneForStore(zone)),
            timeRates: this.state.timeRates.slice(),
        }
        makeAjaxRequest('/ratesheets/store', 'POST', data, response => {
            toastr.clear()
            this.setState({savingMap: 100})
            if(this.state.ratesheetId) {
                toastr.success(this.state.name + ' was successfully updated!', 'Success', {'onHidden': function(){location.reload()}})
            } else {
                toastr.success(this.state.name + ' was successfully created', 'Success', {
                    'progressBar': true,
                    'positionClass': 'toast-top-full-width',
                    'showDuration': 500,
                })
            }
        })
    }

    render() {
        return (
            <Row md={11} className='justify-content-md-center'>
                <Modal show={this.state.drawingMap < 100}>
                    <h4>Drawing map, please wait... <i className='fas fa-spinner fa-spin'></i></h4>
                </Modal>
                <Modal show={this.state.savingMap < 100}>
                    <h4>Saving map, please wait... <i className='fas fa-spinner fa-spin'></i></h4>
                </Modal>
                <Col md={11}>
                    <Tabs id='ratesheet-tabs' className='nav-justified' activeKey={this.state.key} onSelect={key => this.handleChange({target: {name: 'key', type: 'string', value: key}})}>
                        <Tab eventKey='settings' title={<h3><i className='fas fa-cog'></i> Settings</h3>}>
                            <SettingsTab
                                name= {this.state.name}
                                deliveryTypes= {this.state.deliveryTypes}
                                palletRate={this.state.palletRate}
                                timeRates = {this.state.timeRates} 
                                weightRates= {this.state.weightRates}
                                useInternalZonesCalc= {this.state.useInternalZonesCalc}
                                zoneRates = {this.state.zoneRates}

                                handleChange= {this.handleChange}
                                handlePalletRateChange = {this.handlePalletRateChange}
                                handleTimeRateChange = {this.handleTimeRateChange}
                                handleWeightRateChange = {this.handleWeightRateChange}
                                handleZoneRateChange = {this.handleZoneRateChange}
                            />
                        </Tab>
                        <Tab eventKey='map' title={<h3><i className='fas fa-map'></i> Map</h3>}>
                            <MapTab 
                                polyColours = {this.state.polyColours}
                                defaultZoneType = {this.state.defaultZoneType}
                                snapPrecision={this.state.snapPrecision}
                                mapZones={this.state.mapZones}

                                handleChange={this.handleChange}
                                deleteZone={this.deleteZone}
                                editZone={this.editZone}
                            />
                        </Tab>
                    </Tabs>
                    <Row className='justify-content-md-center'>
                        <Button onClick={this.store}>Save</Button>
                    </Row>
                </Col>
            </Row>
        )
    }
}
