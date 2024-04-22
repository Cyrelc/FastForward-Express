import React, {useEffect, useState} from 'react'
import {Button, Col, Modal, ProgressBar, Row, Tabs, Tab} from 'react-bootstrap'
import {toast} from 'react-toastify'

import Zone, {polyColours} from './Classes/Zone'

import BasicRatesTab from './BasicRatesTab'
import ConditionalsTab from './Conditionals/ConditionalsTab'
import ImportRatesTab from './ImportRatesTab'
import MapTab from './MapTab'
import TimeRatesTab from './TimeRatesTab'
import WeightRatesTab from './WeightRatesTab'
import DistanceRatesTab from './DistanceRatesTab'
// import ZoneDistanceRatesTab from './ZoneDistanceRatesTab'
import useImport from './Hooks/useImportFromRatesheet'
import useMap from './Hooks/useMap'
import useRatesheet from './Hooks/useRatesheet'

import {useAPI} from '../../contexts/APIContext'

const sleep = ms => {
    return new Promise(resolve => setTimeout(resolve, ms))
}

var nextPolygonIndex = 0;

export default function Ratesheet(props) {
    const api = useAPI()
    const importFromRatesheet = useImport()
    const mapState = useMap()
    const ratesheetState = useRatesheet()

    const [key, setKey] = useState('basic')

    useEffect(() => {
        const {match: {params}} = props
        document.title = params.ratesheetId ? `Edit Ratesheet - ${params.ratesheetId}` : 'Create Ratesheet'
        if(window.location.hash)
            setKey(window.location.hash.substr(1))
        ratesheetState.setRatesheetId(params.ratesheetId)
    }, [])

    useEffect(() => {
        if(mapState.map && mapState.drawingManager) {
            const {match: {params}} = props
            api.get(params.ratesheetId ? `/ratesheets/${params.ratesheetId}` : '/ratesheets/create').then(async(response) => {
                var timeRates = response.timeRates.map(rate => {
                    return {...rate, brackets: rate.brackets.map(bracket => {
                        return {...bracket, startTime: bracket.startTime ? new Date(bracket.startTime) : null, endTime: bracket.endTime ? new Date(bracket.endTime) : null}
                    })
                }})
                ratesheetState.setDeliveryTypes(response.deliveryTypes)
                ratesheetState.setName(response.name)
                ratesheetState.setMiscRates(response.miscRates ?? [])
                ratesheetState.setPalletRate(response.palletRate)
                importFromRatesheet.setRatesheets(response.ratesheets.filter(ratesheet => ratesheet.ratesheet_id != params.ratesheetId))
                ratesheetState.setTimeRates(timeRates)
                ratesheetState.setWeightRates(response.weightRates)
                ratesheetState.setVolumeRates(response.volumeRates)
                ratesheetState.setZoneRates(response.zoneRates)
                ratesheetState.setUseInternalZonesCalc(response.useInternalZonesCalc)
                if(params.ratesheetId) {
                    const mapZonesLength = response.mapZones.length
                    for (let index = 0; index < mapZonesLength; index++) {
                        const mapZone = response.mapZones[index]
                        const percentComplete = (index / mapZonesLength).toPrecision(2) * 100
                        mapState.setDrawingMap(percentComplete)
                        const zIndex = nextPolygonIndex++
                        mapZone.polygon = new google.maps.Polygon({
                            paths: mapZone.coordinates.map(coord => {return {lat: parseFloat(coord.lat), lng: parseFloat(coord.lng)}}),
                            map: mapState.map,
                            zIndex: zIndex
                        })
                        mapZone.polygon.addListener('click', () => mapState.setEditZoneZIndex(zIndex))
                        mapZone.additionalCosts = JSON.parse(mapZone.additional_costs)
                        mapZone.additionalTime = parseFloat(mapZone.additional_time)
                        const newZone = new Zone(mapZone)
                        mapState.setMapZones(prevMapZones => prevMapZones.concat(newZone))
                    }
                    mapState.setDrawingMap(100)
                }
            })
        }
    }, [mapState.map, mapState.drawingManager])

    const deleteZone = zIndex => {
        const name = mapState.mapZones.find(zone => zone.zIndex == zIndex).name
        if(confirm(`Are you sure you wish to delete zone "${name}"?\n This action can not be undone`)) {
            var deleteIndex = null
            mapState.mapZones.map((zone, index) => {
                if(zone.zIndex === zIndex) {
                    deleteIndex = index
                    zone.polygon.setMap(null)
                    // zone.polyLabel.setMap(null)
                }
            })
            mapState.setMapZones(mapState.mapZones.filter((zone, index) => index !== deleteIndex))
        }
    }

    // const handleChange = (event, section, id) => {
    //     const {name, value, type, checked} = event.target
    //     if(section) {
    //         const updated = this.state[section].map(obj => {
    //             if(obj.id === id)
    //                 return type === 'checkbox' ? {...obj, [name]: checked} : {...obj, [name]: value}
    //             return obj
    //         })
    //         this.setState({[section] : updated})
    //     } else
    //         if(name === 'key')
    //             window.location.hash = value
    //     type === 'checkbox' ? this.setState({ [name]: checked }) : this.setState({ [name]: value })
    // }

    const handleImport = (event, replace = false) => {
        if(!importFromRatesheet.selectedImports) {
            console.log('ERROR - Selected imports value is invalid or empty array. Aborting.')
            return
        }
        switch(importFromRatesheet.importType) {
            case 'mapZones':
                importFromRatesheet.selectedImports.forEach(importZone => {
                    console.log(`attempting to parse new mapzone: ${importZone.name}`)
                    const polygon = new google.maps.Polygon({
                        paths: importZone.coordinates.map(coord => {return {lat: parseFloat(coord.lat), lng: parseFloat(coord.lng)}})
                    })
                    polygon.setMap(mapState.map)
                    const oldZone = mapState.mapZones.find(zone => zone.name === importZone.name)
                    if(oldZone && replace) {
                        const temp_id = oldZone.zoneId;
                        deleteZone(oldZone.zoneId, oldZone.name)
                        // createZone(polygon, {...importZone, zone_id: temp_id})
                    } else
                        console.log('test')
                        // createZone(polygon, {...importZone, name: oldZone ? `${importZone.name} (copy)` : importZone.name, zoneId: null})
                })
                break;
            case 'timeRates':
                let timeRates = ratesheetState.timeRates
                importFromRatesheet.selectedImports.forEach(importRate => {
                    const oldRateIndex = timeRates.findIndex(timeRate => timeRate.name === importRate.name)
                    const brackets = importRate.brackets.map(bracket => { return {...bracket, startTime: new Date(bracket.startTime), endTime: new Date(bracket.endTime)}})
                    if(oldRateIndex >= 0 && replace) {
                        timeRates[oldRateIndex] = {...importRate, brackets: brackets}
                    } else
                        timeRates.push({...importRate, name: oldRateIndex >= 0 ? `${importRate.name} (copy)` : importRate.name, brackets: brackets})
                })
                ratesheetState.setTimeRates(timeRates)
                break;
            case 'miscRates':
                let miscRates = ratesheetState.miscRates
                importFromRatesheet.selectedImports.forEach(importRate => {
                    const oldRateIndex = miscRates.findIndex(miscRate => miscRate.name === importRate.name)
                    if(oldRateIndex >= 0 && replace) {
                        miscRates[oldRateIndex] = importRate
                    } else
                        miscRates.push({...importRate, name: oldRateIndex >= 0 ? `${importRate.name} (copy)` : importRate.name})
                })
                ratesheetState.setMiscRates(miscRates)
                break
            case 'weightRates':
                let weightRates = ratesheetState.weightRates
                importFromRatesheet.selectedImports.forEach(importRate => {
                    const oldRateIndex = weightRates.findIndex(weightRate => weightRate.name === importRate.name)
                    if(oldRateIndex >= 0 && replace) {
                        weightRates[oldRateIndex] = importRate
                    } else
                        weightRates.push({...importRate, name: oldRateIndex >= 0 ? `${importRate.name} (copy)` : importRate.name})
                })
                ratesheetState.setWeightRates(weightRates)
                break;
            default:
                return
        }
        importFromRatesheet.reset()
    }

    const handleZoneTypeChange = (event, zIndex) => {
        const {value} = event.target
        const strokeColour = polyColours[`${value}Stroke`]
        const fillColour = polyColours[`${value}Fill`]

        const updated = mapState.mapZones.map(mapZone => {
            if(mapZone.zIndex == zIndex) {
                mapZone.polygon.setOptions({strokeColor: strokeColour, fillColor: fillColour})
                return {...mapZone, type: value}
            }
            return mapZone
        })
        mapState.setMapZones(updated)
    }

    const store = () => {
        mapState.setSavingMap(0)
        var data = {
            name: ratesheetState.name,
            ratesheet_id: ratesheetState.ratesheetId,
            useInternalZonesCalc: ratesheetState.useInternalZonesCalc,
            deliveryTypes: ratesheetState.deliveryTypes,
            weightRates: ratesheetState.weightRates,
            zoneRates: ratesheetState.zoneRates,
            mapZones: mapState.mapZones.map(zone => zone.collect()),
            timeRates: ratesheetState.timeRates,
            miscRates: ratesheetState.miscRates
        }
        api.post('/ratesheets', data).then(response => {
            mapState.setSavingMap(100)
            if(ratesheetState.ratesheetId) {
                toast.success(`${ratesheetState.name} was successfully updated!`, {onClose: location.reload})
            } else {
                toast.success(`${ratesheetState.name} was successfully created`, {
                    position: 'top-center',
                    autoClose: 5000,
                })
            }
        }, errorResponse => {
            mapState.setSavingMap(100)
        })
    }

    return (
        <Row className='justify-content-md-center'>
            <Modal show={mapState.drawingMap < 100}>
                <h4>Drawing map, please wait... <i className='fas fa-spinner fa-spin'></i></h4>
                <ProgressBar now={mapState.drawingMap} />
            </Modal>
            <Modal show={mapState.savingMap < 100}>
                <h4>Saving map, please wait... <i className='fas fa-spinner fa-spin'></i></h4>
            </Modal>
            <Col md={12}>
                <Tabs id='ratesheet-tabs' className='nav-justified' activeKey={key} onSelect={newKey => setKey(newKey)}>
                    <Tab eventKey='basic' title={<h4><i className='fas fa-cog'></i> Basic</h4>}>
                        <BasicRatesTab
                            ratesheetState={ratesheetState}
                        />
                    </Tab>
                    {ratesheetState.useInternalZonesCalc &&
                        <Tab eventKey='distance' title={<h4><i className='fas fa-map'></i>Distance Rates</h4>}>
                            <DistanceRatesTab
                                ratesheetState={ratesheetState}
                                // handleZoneRateChange={handleZoneRateChange}
                            />
                        </Tab>
                    }
                    <Tab eventKey='weight' title={<h4><i className='fas fa-weight'></i> Weight Rates</h4>}>
                        <WeightRatesTab
                            setWeightRates={ratesheetState.setWeightRates}
                            weightRates={ratesheetState.weightRates}
                        />
                    </Tab>
                    <Tab eventKey='time' title={<h4><i className='fas fa-clock'></i> Time Rates</h4>}>
                        <TimeRatesTab
                            setTimeRates={ratesheetState.setTimeRates}
                            timeRates={ratesheetState.timeRates}
                        />
                    </Tab>
                    {ratesheetState.ratesheetId &&
                        <Tab eventKey='conditionals' title={<h4><i className='fas fa-code-branch'></i> Conditionals</h4>}>
                            <ConditionalsTab
                                mapZones={mapState.mapZones}
                                ratesheetId={ratesheetState.ratesheetId}
                            />
                        </Tab>
                    }
                    <Tab eventKey='map' title={<h4><i className='fas fa-map'></i> Map</h4>}>
                        <MapTab
                            polyColours={polyColours}
                            mapState={mapState}
                        />
                    </Tab>
                    <Tab
                        eventKey='import'
                        title={<h4><i className='fas fa-solid fa-file-import' /> Import</h4>}
                    >
                        <ImportRatesTab
                            importFromRatesheet={importFromRatesheet}
                            originalRates={{
                                mapZones: mapState.mapZones,
                                miscRates: ratesheetState.miscRates,
                                timeRates: ratesheetState.timeRates,
                                weightRates: ratesheetState.weightRates
                            }}
                            type='miscRates'

                            handleImport={handleImport}
                        />
                    </Tab>
                </Tabs>
                <Row className='justify-content-md-center'>
                    <Col md={1}>
                        <Button onClick={store}>Save</Button>
                    </Col>
                </Row>
            </Col>
        </Row>
    )
}
