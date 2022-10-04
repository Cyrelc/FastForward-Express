import React, {Component} from 'react'
import {Button, Col, Modal, Row, Tabs, Tab} from 'react-bootstrap'
import SnazzyInfoWindow from 'snazzy-info-window'
import polylabel from 'polylabel'

import PolySnapper from '../../../../../public/js/polysnapper-master/polysnapper.js'

import BasicRatesTab from './BasicRatesTab'
import ImportRatesModal from './ImportRatesModal'
import MapTab from './MapTab'
import TimeRatesTab from './TimeRatesTab'
import VolumeRatesTab from './VolumeRatesTab'
import WeightRatesTab from './WeightRatesTab'
import ZoneDistanceRatesTab from './ZoneDistanceRatesTab'

const polyColours = {
    internalStroke : '#3651c9',
    internalFill: '#8491c9',
    outlyingStroke: '#d16b0c',
    outlyingFill: '#e8a466',
    peripheralStroke:'#2c9122',
    peripheralFill: '#3bd82d'
}

var polygonNextIndex = 0;

export default class Ratesheet extends Component {
    constructor() {
        super()
        this.state = {
            key: 'basic',
            bypassDedupWarning: false,
            defaultZoneType: 'internal',
            deliveryTypes: [],
            drawingMap: 0,
            latLngPrecision: 5,
            map: undefined,
            mapCenter: new google.maps.LatLng(53.544389, -113.4909266),
            mapDrawingManager: undefined,
            mapZones: [],
            mapZoom: 11,
            miscRates: [],
            polySnapper: null,
            ratesheetId: null,
            ratesheetName: '',
            ratesheets: [],
            savingMap: 100,
            snapPrecision: 100,
            timeRates: [],
            useInternalZonesCalc: true,
            volumeRates: [],
            weightRates: undefined,
            zoneRates: [],
            //import Variables
            importRatesheet: undefined,
            importType: undefined,
            selectedImports: [],
            showImportModal: false,
            showReplaceModal: false
        }
        this.createPolygon = this.createPolygon.bind(this)
        this.deleteZone = this.deleteZone.bind(this)
        this.editZone = this.editZone.bind(this)
        this.handleChange = this.handleChange.bind(this)
        this.handleImport = this.handleImport.bind(this)
        this.handleZoneRateChange = this.handleZoneRateChange.bind(this)
        this.zoneRemoveDuplicates = this.zoneRemoveDuplicates.bind(this)
        this.store = this.store.bind(this)
    }

    componentDidMount() {
        const {match: {params}} = this.props
        const map = new google.maps.Map(document.getElementById('googleMap'), {center: this.state.mapCenter, zoom: this.state.mapZoom, disableDefaultUI: true})
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
                    return {...rate, brackets: rate.brackets.map(bracket => {
                        return {...bracket, startTime: bracket.startTime ? new Date(bracket.startTime) : null, endTime: bracket.endTime ? new Date(bracket.endTime) : null}
                    })
                }})
                this.setState({
                    deliveryTypes: response.deliveryTypes,
                    key: window.location.hash ? window.location.hash.substr(1) : 'basic',
                    ratesheetName: response.name,
                    miscRates: response.miscRates ?? [],
                    palletRate: response.palletRate,
                    ratesheets: response.ratesheets.filter(ratesheet => ratesheet.ratesheet_id != params.ratesheetId),
                    timeRates: timeRates,
                    weightRates: response.weightRates,
                    volumeRates: [],
                    zoneRates: response.zoneRates,
                    useInternalZonesCalc: response.useInternalZonesCalc
                })
                if(params.ratesheetId) {
                    response.mapZones.forEach(mapZone => {
                        const polygon = new google.maps.Polygon({
                                paths: mapZone.coordinates.map(coord => {return {lat: parseFloat(coord.lat), lng: parseFloat(coord.lng)}})
                            })
                        polygon.setMap(this.state.map)
                        this.createPolygon(polygon, mapZone)
                    })
                    this.state.mapDrawingManager.setOptions({polygonOptions: {clickable: true, zIndex: response.mapZones.length + 1}});
                }
                this.setState({drawingMap: 100})
            })
        })
    }

    componentDidUpdate(prevProps) {
        const {match: {params}} = this.props
        if(prevProps.match.params.ratesheetId != params.ratesheetId)
            window.location.reload()
    }

    createPolygon(polygon, zone = null) {
        var strokeColour, fillColour, type
        if(zone) {
            strokeColour = polyColours[`${zone.type}Stroke`]
            fillColour = polyColours[`${zone.type}Fill`]
            type = zone.type
        } else {
            strokeColour = polyColours[`${this.state.defaultZoneType}Stroke'`]
            fillColour = polyColours[`${this.state.defaultZoneType}Fill`]
            type = this.state.defaultZoneType
        }
        polygon.setOptions({strokeColor: strokeColour, fillColor: fillColour, zIndex: polygonNextIndex++})
        polygon.addListener('click', () => this.editZone(polygon.zIndex))
        google.maps.event.addListener(polygon, 'rightclick', (point) => this.deletePolyPoint(point, polygon.zIndex));
        const coordinates = this.getCoordinates(polygon).map(coordinatePair => {
            return [coordinatePair.lat, coordinatePair.lng]
        })
        const name = zone ? zone.name : this.state.defaultZoneType + '_zone_' + polygon.zIndex
        const position = polylabel([coordinates])
        const neighbourLabel = new google.maps.Marker({
            map: null,
            label: 'A',
            position: {lat: position[0], lng: position[1]},
        })
        var newZone = {
            id: polygon.zIndex,
            name : name,
            type: type,
            polygon: polygon,
            viewDetails: false,
            coordinates: coordinates,
            neighbourLabel: neighbourLabel,
            zoneId: zone ? zone.zone_id : null,
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
            newZone.directRushCost = costs ? costs.direct_rush : ''
            newZone.additionalTime = zone ? zone.additional_time : ''
        }
        this.setState((prevState, props) => ({mapZones: prevState.mapZones.concat(newZone)}))
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
        if(confirm('Are you sure you wish to delete this map zone?\n This action can not be undone')) {
            var deleteIndex = null
            this.state.mapZones.map((zone, index) => {
                if(zone.id === id) {
                    deleteIndex = index
                    zone.polygon.setMap(null)
                    // zone.polyLabel.setMap(null)
                }
            })
            this.setState({mapZones: this.state.mapZones.filter((zone, index) => index !== deleteIndex)})
        }
    }

    editZone(id) {
        const activeZone = this.state.mapZones.filter(zone => zone.id === id)[0]
        const updated = this.state.mapZones.map(zone => {
            if(zone.id === id) {
                zone.polygon.setOptions({editable: true, snapable: false})
                zone.neighbourLabel.setMap(null)
                return {...zone, viewDetails: true}
            } else if(this.findCommonCoordinates(this.getCoordinates(activeZone.polygon), this.getCoordinates(zone.polygon)))
                zone.neighbourLabel.setMap(this.state.map)
            else
                zone.neighbourLabel.setMap(null)
            zone.polygon.setOptions({editable: false, snapable: true})
            return {...zone, viewDetails: false}
        })
        const polySnapper = new PolySnapper({
            map: this.state.map,
            threshold: this.state.snapPrecision,
            polygons: this.state.mapZones.map(mapZone => {return mapZone.polygon}),
            hidePOI: true,
        })
        polySnapper.enable(this.state.mapZones.filter(mapZone => mapZone.id === id)[0].polygon.zIndex)
        this.setState({mapZones: updated, polySnapper: polySnapper})
    }

    findCommonCoordinates(coordinates1, coordinates2) {
        return coordinates1.some(coord1 => {
            return coordinates2.some(coord2 => coord1.lat === coord2.lat && coord1.lng === coord2.lng)
        })
    }

    getCoordinates(polygon) {
        return polygon.getPath().getArray().map(point => {return {lat: parseFloat(point.lat().toFixed(this.state.latLngPrecision)), lng: parseFloat(point.lng().toFixed(this.state.latLngPrecision))}})
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
        type === 'checkbox' ? this.setState({ [name]: checked }) : this.setState({ [name]: value })
    }

    handleImport(event, replace = false) {
        if(!this.state.selectedImports) {
            console.log('ERROR - Selected imports value is invalid or empty array. Aborting.')
            return
        }
        switch(this.state.importType) {
            case 'mapZones':
                this.state.selectedImports.forEach(importZone => {
                    console.log('attempting to parse new mapzone')
                    const polygon = new google.maps.Polygon({
                        paths: importZone.coordinates.map(coord => {return {lat: parseFloat(coord.lat), lng: parseFloat(coord.lng)}})
                    })
                    polygon.setMap(this.state.map)
                    const oldZone = this.state.mapZones.find(zone => zone.name === importZone.name)
                    if(oldZone && replace) {
                        const temp_id = oldZone.zone_id;
                        this.deleteZone(oldZone.zone_id)
                        this.createPolygon(polygon, {...importZone, zone_id: temp_id})
                    } else
                        this.createPolygon(polygon, {...importZone, name: oldZone ? importZone.name + '(copy)' : importZone.name, zone_id: null})
                })
                break;
            case 'timeRates':
                let timeRates = this.state.timeRates
                this.state.selectedImports.forEach(importRate => {
                    const oldRateIndex = timeRates.findIndex(timeRate => timeRate.name === importRate.name)
                    const brackets = importRate.brackets.map(bracket => { return {...bracket, startTime: new Date(bracket.startTime), endTime: new Date(bracket.endTime)}})
                    if(oldRateIndex >= 0 && replace) {
                        timeRates[oldRateIndex] = {...importRate, brackets: brackets}
                    } else
                        timeRates.push({...importRate, name: oldRateIndex >= 0 ? importRate.name + ' (copy)' : importRate.name, brackets: brackets})
                })
                this.setState({timeRates: timeRates})
                break;
            case 'miscRates':
                let miscRates = this.state.miscRates
                this.state.selectedImports.forEach(importRate => {
                    const oldRateIndex = miscRates.findIndex(miscRate => miscRate.name === importRate.name)
                    if(oldRateIndex >= 0 && replace) {
                        miscRates[oldRateIndex] = importRate
                    } else
                        miscRates.push({...importRate, name: oldRateIndex >= 0 ? importRate.name + ' (copy)' : importRate.name})
                })
                this.setState({miscRates: miscRates})
                break
            case 'weightRates':
                let weightRates = this.state.weightRates
                this.state.selectedImports.forEach(importRate => {
                    const oldRateIndex = weightRates.findIndex(weightRate => weightRate.name === importRate.name)
                    if(oldRateIndex >= 0 && replace) {
                        weightRates[oldRateIndex] = importRate
                    } else
                        weightRates.push({...importRate, name: oldRateIndex >= 0 ? importRate.name + ' (copy)' : importRate.name})
                })
                this.setState({weightRates: weightRates})
                break;
            default:
                return
        }

        this.setState({
            addAll: false,
            replaceAll: false,
            selectedImports: [],
            showImportModal: false,
        })
    }

    handleZoneRateChange(event, id) {
        const {name, value} = event.target
        const updated = this.state.zoneRates.map(zoneRate => {
            if(zoneRate.id == id) {
                return {...zoneRate, [name]: value}
            }
            return zoneRate
        })
        this.setState({zoneRates: updated})
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

    store() {
        this.setState({savingMap: 0})
        var data = {
            name: this.state.ratesheetName,
            ratesheet_id: this.state.ratesheetId,
            useInternalZonesCalc: this.state.useInternalZonesCalc,
            deliveryTypes: this.state.deliveryTypes.slice(),
            weightRates: this.state.weightRates.slice(),
            zoneRates: this.state.zoneRates.slice(),
            mapZones: this.state.mapZones.map(zone => this.prepareZoneForStore(zone)),
            timeRates: this.state.timeRates.slice(),
            miscRates: this.state.miscRates.slice()
        }
        makeAjaxRequest('/ratesheets/store', 'POST', data, response => {
            toastr.clear()
            this.setState({savingMap: 100})
            if(this.state.ratesheetId) {
                toastr.success(this.state.ratesheetName + ' was successfully updated!', 'Success', {'onHidden': function(){location.reload()}})
            } else {
                toastr.success(this.state.ratesheetName + ' was successfully created', 'Success', {
                    'progressBar': true,
                    'positionClass': 'toast-top-full-width',
                    'showDuration': 500,
                })
            }
        }, errorResponse => {
            this.setState({savingMap: 100})
        })
    }

    zoneRemoveDuplicates(id) {
        if(!this.state.bypassDedupWarning){
            if(confirm('Checks the selected zone for duplicate points and removes them - warning. If you are Ritchie, talk to Brandon before using'))
                this.handleChange({target: {name: 'bypassDedupWarning', type: 'boolean', value: true}})
            else
                return
        }

        const dedup = this.state.mapZones.map(zone => {
            if(zone.id == id) {
                let filtered = []
                let duplicates = []
                let count = 0
                const cleanedPath = zone.polygon.getPath().forEach(polyPoint => {
                    if(filtered.find(testPoint => testPoint.lat() === polyPoint.lat() && testPoint.lng() === polyPoint.lng()) === undefined)
                        filtered.push(polyPoint)
                    else {
                        duplicates.push({lat: polyPoint.lat(), lng: polyPoint.lng()})
                        count++
                    }
                })
                console.log(`${count} duplicates found`, filtered.length, duplicates)
                zone.polygon.setPath(filtered)
                return zone
            }
            return zone
        })
    }

    render() {
        return (
            <Row className='justify-content-md-center'>
                <Modal show={this.state.drawingMap < 100}>
                    <h4>Drawing map, please wait... <i className='fas fa-spinner fa-spin'></i></h4>
                </Modal>
                <Modal show={this.state.savingMap < 100}>
                    <h4>Saving map, please wait... <i className='fas fa-spinner fa-spin'></i></h4>
                </Modal>
                <Col md={12}>
                    <Tabs id='ratesheet-tabs' className='nav-justified' activeKey={this.state.key} onSelect={key => this.handleChange({target: {name: 'key', type: 'string', value: key}})}>
                        <Tab eventKey='basic' title={<h4><i className='fas fa-cog'></i> Basic</h4>}>
                            <BasicRatesTab
                                deliveryTypes={this.state.deliveryTypes}
                                miscRates={this.state.miscRates}
                                ratesheetName={this.state.ratesheetName}
                                ratesheets={this.state.ratesheets}
                                showImportModal={this.state.showImportModal}
                                useInternalZonesCalc={this.state.useInternalZonesCalc}

                                selectedImports={this.state.selectedImports}
                                importRatesheet={this.state.importRatesheet}
                                importType={this.state.importType}

                                handleChange={this.handleChange}
                                handleImport={this.handleImport}
                                handleZoneRateChange={this.handleZoneRateChange}
                            />
                        </Tab>
                        <Tab eventKey='weight' title={<h4><i className='fas fa-weight'></i> Weight Rates</h4>}>
                            <WeightRatesTab
                                handleChange={this.handleChange}
                                weightRates={this.state.weightRates}
                            />
                        </Tab>
                        <Tab eventKey='time' title={<h4><i className='fas fa-clock'></i> Time Rates</h4>}>
                            <TimeRatesTab
                                timeRates={this.state.timeRates}
                                handleChange={this.handleChange}
                            />
                        </Tab>
                        <Tab eventKey='volume' title={<h4><i className='fas fa-ruler-combined'></i> Volume Rates</h4>}>
                            <VolumeRatesTab

                            />
                        </Tab>
                        <Tab eventKey='map' title={<h4><i className='fas fa-map'></i> Map</h4>}>
                            <MapTab
                                polyColours = {polyColours}
                                defaultZoneType = {this.state.defaultZoneType}
                                snapPrecision={this.state.snapPrecision}
                                mapZones={this.state.mapZones}

                                handleChange={this.handleChange}
                                deleteZone={this.deleteZone}
                                editZone={this.editZone}
                                zoneRemoveDuplicates={this.zoneRemoveDuplicates}
                            />
                        </Tab>
                        <Tab
                            title={
                                <Button
                                    variant='secondary'
                                    onClick={() => {
                                        this.handleChange({target: {name: 'showImportModal', type: 'boolean', value: true}});
                                        this.handleChange({target: {name: 'selectedImports', type: 'array', value: []}})}
                                    }
                                ><i className='fas fa-solid fa-file-import'/> Import</Button>
                            }
                        ></Tab>
                    </Tabs>
                    <Row className='justify-content-md-center'>
                        <Col md={1}>
                            <Button onClick={this.store}>Save</Button>
                        </Col>
                    </Row>
                    <ImportRatesModal
                        ratesheets={this.state.ratesheets}
                        importRatesheet={this.state.importRatesheet}
                        importType={this.state.importType}
                        selectedImports={this.state.selectedImports}
                        showImportModal={this.state.showImportModal}
                        originalRates={{
                            mapZones: this.state.mapZones,
                            miscRates: this.state.miscRates,
                            timeRates: this.state.timeRates,
                            weightRates: this.state.weightRates
                        }}
                        type='miscRates'

                        handleChange={this.handleChange}
                        handleImport={this.handleImport}
                    />
                </Col>
            </Row>
        )
    }
}
