import React, {Component} from 'react'
import ReactDom from 'react-dom'
import {Tabs, Tab, Row, Col, Button} from 'react-bootstrap'
import MapTab from './MapTab'
import SettingsTab from './SettingsTab'

var polygonNextIndex = 0;

export default class Ratesheet extends Component {
    constructor() {
        super()
        this.state = {
            key: 'settings',
            formType: null,
            ratesheetId: null,
            name: '',
            holidayRate: '',
            weekendRate: '',
            useInternalZonesCalc: true,
            deliveryTypes: [],
            timeRates: [],
            weightRates: [],
            zoneRates: [],
            mapZones: [],
            polyColours: {internalStroke : '#3651c9', internalFill: '#8491c9', outlyingStroke: '#d16b0c', outlyingFill: '#e8a466', peripheralStroke:'#2c9122', peripheralFill: '#3bd82d'},
            mapCenter: new google.maps.LatLng(53.544389, -113.49092669999999),
            mapZoom: 12,
            map: undefined,
            mapDrawingManager: undefined,
            defaultZoneType: 'internal',
            useSnapping: true
        }
        this.handleChange = this.handleChange.bind(this)
        this.handleTimeRateChange = this.handleTimeRateChange.bind(this)
        this.handleWeightRateChange = this.handleWeightRateChange.bind(this)
        this.handleZoneRateChange = this.handleZoneRateChange.bind(this)
        this.deleteZone = this.deleteZone.bind(this)
        this.editZone = this.editZone.bind(this)
        this.updateZone = this.updateZone.bind(this)
        this.store = this.store.bind(this)
    }

    componentDidMount() {
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
        const formType = window.location.href.indexOf('create') > -1 ? 'create' : 'edit'
        const ratesheetId = formType === 'edit' ? window.location.href.substring(window.location.href.lastIndexOf('/') + 1) : null
        this.setState({map: map, mapDrawingManager: drawingManager, formType: formType, ratesheetId: ratesheetId}, () => {
            document.title = formType === 'create' ? 'Create Ratesheet - ' + document.title : 'Edit Ratesheet ' + this.state.ratesheetId + ' - ' + document.title
            fetch(ratesheetId === null ? '/ratesheets/getModel/' : '/ratesheets/getModel/' + ratesheetId)
                .then(response => {
                    return response.json()
                })
                .then((data) => {
                    this.setState({deliveryTypes: data.deliveryTypes, name: data.name, timeRates: data.timeRates, weightRates: data.weightRates, zoneRates: data.zoneRates, useInternalZonesCalc: data.useInternalZonesCalc})
                    if(formType === 'edit') {
                        data.mapZones.map(zone => {
                            const polygon = new google.maps.Polygon({
                                    paths: zone.coordinates.map(coord => {return {lat: parseFloat(coord.lat), lng: parseFloat(coord.lng)}}),
                                })
                            polygon.setMap(this.state.map)
                            this.createPolygon(polygon, zone)
                        })
                        this.state.mapDrawingManager.setOptions({polygonOptions: {clickable: true, zIndex: data.mapZones.length + 1}});
                    }
                })
        })
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
        type === 'checkbox' ? this.setState({ [name]: checked }) : this.setState({ [name]: value})
    }

    isEmpty(value){
        if(value === '' || value === undefined || value === 0)
            return true
        return false
    }

    handleTimeRateChange(event, id) {
        const {name, value} = event.target
        var next
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

    createPolygon(polygon, zone = null){
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
        var newZone = {
            id: polygon.zIndex, 
            name : zone ? zone.name : this.state.defaultZoneType + '_zone_' + polygon.zIndex, 
            type: type, 
            polygon: polygon,
            viewDetails: false,
            coordinates: this.getCoordinates(polygon),
        }
        if(type === 'peripheral') {
            newZone.cost = zone ? zone.cost : ''
            newZone.additionalTime = zone ? zone.additionalTime : ''
        } else if (type === 'outlying') {
            newZone.regularCost = zone ? zone.regularCost : ''
            newZone.rushCost = zone ? zone.rushCost : ''
            newZone.directCost = zone ? zone.directCost : ''
            newZone.directRushCost = zone ? zone.directRushCost : ''
            newZone.additionalTime = zone ? zone.additionalTime : ''
        }
        this.setState({mapZones: this.state.mapZones.concat([newZone])})
        this.updateZone(polygon.zIndex)
        name === '' ? this.editZone(polygon.zIndex) : null;
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
        return polygon.getPath().g.map(point => {return {lat: parseFloat(point.lat()), lng: parseFloat(point.lng())}})
    }

    updateZone(id) {
        const updated = this.state.mapZones.map(zone => {
            if(zone.id === id) {
                if(this.state.useSnapping) {
                    var temp = zone.polygon.getPath()
                    zone.polygon.getPath().forEach((coord1, i) => {
                        this.state.mapZones.map((compZone, j) => {
                            if(compZone.id === id)
                                return
                            compZone.polygon.getPath().forEach((coord2, j) => {
                                if(google.maps.geometry.spherical.computeDistanceBetween(coord1, coord2) < 200)
                                    temp.j[i] = coord2
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
        var data = {
            name: this.state.name,
            holidayRate: this.state.holidayRate,
            weekendRate: this.state.weekendRate,
            ratesheetId : this.state.ratesheetId,
            useInternalZonesCalc: this.state.useInternalZonesCalc,
            deliveryTypes : this.state.deliveryTypes.slice(),
            weightRates : this.state.weightRates.slice(),
            zoneRates : this.state.zoneRates.slice(),
            mapZones : this.state.mapZones.map(zone => {return {...zone, polygon : null}}),
            timeRates: this.state.timeRates.slice()
        }
        $.ajax({
            'url': '/ratesheets/store',
            'type': 'POST',
            'data': data,
            'success': () => {
                toastr.clear()
                if(this.state.formType === 'edit')
                    toastr.success(this.state.name + ' was successfully updated!', 'Success')
                else
                    toastr.success(this.state.name + ' was successfully created', 'Success', {
                        'progressBar': true,
                        'positionClass': 'toast-top-full-width',
                        'showDuration': 500,
                    })
            },
            'error': (response) => handleErrorResponse(response)
        })
    }

    render() {
        return (
            <Row md={11} className='justify-content-md-center'>
                <Col md={11}>
                    <Tabs id='ratesheet-tabs' className='nav-justified' activeKey={this.state.key} onSelect={key => this.setState({key})}>
                        <Tab eventKey='settings' title={<h3><i className='fas fa-cog'></i> Settings</h3>}>
                            <SettingsTab 
                                name= {this.state.name}
                                deliveryTypes= {this.state.deliveryTypes}
                                timeRates = {this.state.timeRates} 
                                weightRates= {this.state.weightRates}
                                useInternalZonesCalc= {this.state.useInternalZonesCalc}
                                handleChange= {this.handleChange}
                                handleTimeRateChange = {this.handleTimeRateChange}
                                handleWeightRateChange = {this.handleWeightRateChange}
                                handleZoneRateChange = {this.handleZoneRateChange}
                                zoneRates = {this.state.zoneRates}
                            />
                        </Tab>
                        <Tab eventKey='map' title={<h3><i className='fas fa-map'></i> Map</h3>}>
                            <MapTab 
                                polyColours = {this.state.polyColours}
                                defaultZoneType = {this.state.defaultZoneType}
                                handleChange={this.handleChange}
                                mapZones={this.state.mapZones}
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

ReactDom.render(<Ratesheet />, document.getElementById('ratesheet'))
