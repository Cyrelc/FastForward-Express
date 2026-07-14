import React, {useEffect, useState} from 'react'
import {Button, Col, Modal, ProgressBar, Row, Tabs, Tab} from 'react-bootstrap'
import {Prompt} from 'react-router-dom'
import {toast} from 'react-toastify'
import BasicRatesTab from './BasicRatesTab'
import DocumentationTab from './DocumentationTab'
import PricingConditionalsTab from './PricingConditionals/PricingConditionalsTab'
import ImportFromRatesheetModal from './ImportFromRatesheetModal'
import MapTab from './MapTab'
import TimingConditionalsTab from './TimingConditionals/TimingConditionalsTab'
import useImport from './Hooks/useImportFromRatesheet'
import useMap from './Hooks/useMap'
import useRatesheet from './Hooks/useRatesheet'

import {useAPI} from '../../contexts/APIContext'

const sleep = ms => {
    return new Promise(resolve => setTimeout(resolve, ms))
}

export default function Ratesheet(props) {
    const api = useAPI()
    const importFromRatesheet = useImport()
    const mapState = useMap()
    // Refs for child tabs to allow imperative reload
    const conditionalsTabRef = React.useRef()
    const timingConditionalsTabRef = React.useRef()
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
        if(location.hash.substring(1))
            setKey(location.hash.substring(1))
        else
            setKey('basic')
    }, [location.hash])

    // Zones and groups save individually now, so it is possible to edit several and save none of
    // them. Closing the tab with unsaved map changes should not silently bin them.
    useEffect(() => {
        const warn = (event) => {
            if(!mapState.hasUnsavedChanges)
                return
            event.preventDefault()
            event.returnValue = ''
            return ''
        }

        window.addEventListener('beforeunload', warn)
        return () => window.removeEventListener('beforeunload', warn)
    }, [mapState.hasUnsavedChanges])

    useEffect(() => {
        if(mapState.map) {
            const {match: {params}} = props
            api.get(params.ratesheetId ? `/ratesheets/${params.ratesheetId}` : '/ratesheets/create')
                .then(async(response) => {
                    ratesheetState.setDeliveryTypes(response.deliveryTypes)
                    ratesheetState.setName(response.name)
                    ratesheetState.setMiscRates(response.miscRates ?? [])
                    ratesheetState.setConditionals(response.conditionals ?? [])
                    ratesheetState.setTimingConditionals(response.timingConditionals ?? [])
                    importFromRatesheet.setRatesheets(response.ratesheets.filter(ratesheet => ratesheet.ratesheet_id != params.ratesheetId))
                    ratesheetState.setVolumeRates(response.volumeRates)
                    // Groups must land before the zones are drawn: a zone with no colour of its own
                    // renders in its group's colour.
                    mapState.setZoneGroups(response.zoneGroups ?? [])
                    if(params.ratesheetId) {
                        const mapZonesLength = response.mapZones.length
                        for (let index = 0; index < mapZonesLength; index++) {
                            const mapZone = response.mapZones[index]
                            const percentComplete = (index / mapZonesLength).toPrecision(2) * 100
                            mapState.setDrawingMap(percentComplete)
                            mapState.createZone(null, mapZone)
                        }
                        mapState.setDrawingMap(100)
                    } else {
                        // For a new ratesheet (create route) there are no mapZones to iterate;
                        // ensure the drawing progress modal finishes.
                        mapState.setDrawingMap(100)
                    }
                })
                .catch(error => {
                    toast.error('Failed to load ratesheet. Please check the server or database. (' + (error?.cause?.message || error.message || 'Unknown error') + ')', {autoClose: false})
                    mapState.setDrawingMap(100)
                })
        }
    }, [mapState.map])

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
                    const coordinates = Array.isArray(importZone.coordinates)
                        ? importZone.coordinates
                        : JSON.parse(importZone.coordinates ?? '[]')
                    const polygon = new google.maps.Polygon({
                        paths: coordinates.map(coord => {return {lat: parseFloat(coord.lat), lng: parseFloat(coord.lng)}})
                    })
                    polygon.setMap(mapState.map)
                    const oldZone = mapState.mapZones.find(zone => zone.name === importZone.name)
                    const shouldReplace = oldZone && replace
                    if(shouldReplace)
                        mapState.deleteZone(oldZone.polygon.zIndex)

                    const normalizedZone = {
                        ...importZone,
                        name: shouldReplace ? importZone.name : (oldZone ? `${importZone.name} (copy)` : importZone.name),
                        zoneId: shouldReplace ? oldZone.zoneId : null,
                        coordinates: coordinates,
                        delivery_type_modifiers: importZone.delivery_type_modifiers
                            ?? (importZone.deliveryTypeModifiers ? JSON.stringify(importZone.deliveryTypeModifiers) : null),
                        neighbours: importZone.neighbours ?? [],
                        // Groups belong to the ratesheet that owns them, so membership cannot travel
                        // with an imported zone: the source sheet's ids are meaningless here and could
                        // collide with a real group on this one. Re-assign the group by hand.
                        groupIds: [],
                    }
                    mapState.createZone(polygon, normalizedZone)
                })
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
            case 'conditionals':
                api.post('/ratesheets/conditionals/import', {
                    target_ratesheet_id: ratesheetState.ratesheetId,
                    source_ratesheet_id: importFromRatesheet.importRatesheet.ratesheet_id,
                    conditional_ids: importFromRatesheet.selectedImports.map(conditional => conditional.conditional_id),
                    replace: replace
                }).then(() => {
                    if (conditionalsTabRef.current && conditionalsTabRef.current.reload) {
                        conditionalsTabRef.current.reload()
                    }
                })
                break;
            case 'timingConditionals':
                api.post('/ratesheets/time-conditionals/import', {
                    target_ratesheet_id: ratesheetState.ratesheetId,
                    source_ratesheet_id: importFromRatesheet.importRatesheet.ratesheet_id,
                    timing_conditional_ids: importFromRatesheet.selectedImports.map(conditional => conditional.timing_conditional_id),
                    replace: replace
                }).then(() => {
                    if (timingConditionalsTabRef.current && timingConditionalsTabRef.current.reload) {
                        timingConditionalsTabRef.current.reload()
                    }
                })
                break;
            default:
                return
        }
        importFromRatesheet.reset()
    }

    /**
     * Saves the ratesheet itself, then whatever the map has outstanding.
     *
     * The ratesheet save no longer carries zones or groups: they are addressed individually, and only
     * the changed ones are sent. The old behaviour ("here is every zone; delete anything not in this
     * list") meant a partial payload could silently wipe a map, and two people editing one map meant
     * last-save-wins.
     */
    const store = async () => {
        mapState.setSavingMap(0)

        var data = {
            name: ratesheetState.name,
            ratesheet_id: ratesheetState.ratesheetId,
            deliveryTypes: ratesheetState.deliveryTypes,
            miscRates: ratesheetState.miscRates
        }

        try {
            const response = await api.post('/ratesheets', data)
            // A brand new ratesheet has no id until now, and its zones need one to save against.
            const ratesheetId = ratesheetState.ratesheetId ?? response.ratesheet_id
            if(!ratesheetState.ratesheetId)
                ratesheetState.setRatesheetId(ratesheetId)

            await mapState.saveMap(api, ratesheetId)

            mapState.setSavingMap(100)
            toast.dismiss()
            if(ratesheetState.ratesheetId) {
                toast.success(`${ratesheetState.name} was successfully updated!`)
            } else {
                toast.success(`${ratesheetState.name} was successfully created`, {
                    position: 'top-center',
                    autoClose: 5000,
                })
            }
        } catch(error) {
            mapState.setSavingMap(100)
        }
    }

    return (
        <Row className='justify-content-md-center'>
            {/*
              * Navigating away inside the app does not fire beforeunload, so leaving the ratesheet
              * with unsaved zones would silently bin them.
              *
              * The tabs navigate by assigning window.location.hash, which the router also sees as a
              * location change - so only a real change of page is blocked, or every tab switch would
              * nag about unsaved work.
              */}
            <Prompt
                when={mapState.hasUnsavedChanges}
                message={location => location.pathname === props.location.pathname
                    ? true
                    : 'This ratesheet has unsaved map changes. Leave anyway and lose them?'
                }
            />
            <Modal show={mapState.drawingMap < 100}>
                <h4>Drawing map, please wait... <i className='fas fa-spinner fa-spin'></i></h4>
                <ProgressBar now={mapState.drawingMap} />
            </Modal>
            <Modal show={mapState.savingMap < 100}>
                <h4>Saving map, please wait... <i className='fas fa-spinner fa-spin'></i></h4>
            </Modal>
            <Col md={12}>
                <Tabs id='ratesheet-tabs' className='nav-justified' activeKey={key} onSelect={newKey => {window.location.hash = newKey}}>
                    <Tab eventKey='basic' title={<h4><i className='fas fa-cog'></i> Basic</h4>}>
                        <BasicRatesTab
                            ratesheetState={ratesheetState}
                            openImport={() => importFromRatesheet.setImportType('miscRates')}
                        />
                    </Tab>
                    {ratesheetState.ratesheetId &&
                        <Tab eventKey='conditionals' title={<h4><i className='fas fa-code-branch'></i> Pricing Conditionals</h4>}>
                            <PricingConditionalsTab
                                deliveryTypes={ratesheetState.deliveryTypes}
                                mapZones={mapState.mapZones}
                                ratesheetId={ratesheetState.ratesheetId}
                                zoneGroups={mapState.zoneGroups}
                                openImport={() => importFromRatesheet.setImportType('conditionals')}
                            />
                        </Tab>
                    }
                    {ratesheetState.ratesheetId &&
                        <Tab eventKey='timing-conditionals' title={<h4><i className='fas fa-stopwatch'></i> Timing Conditionals{timingConditionalsTabRef.current && timingConditionalsTabRef.current.loadError ? ' !' : ''}</h4>}>
                            <TimingConditionalsTab
                                ref={timingConditionalsTabRef}
                                deliveryTypes={ratesheetState.deliveryTypes}
                                mapZones={mapState.mapZones}
                                ratesheetId={ratesheetState.ratesheetId}
                                zoneGroups={mapState.zoneGroups}
                                openImport={() => importFromRatesheet.setImportType('timingConditionals')}
                            />
                        </Tab>
                    }
                    <Tab eventKey='map' title={<h4><i className='fas fa-map'></i> Map</h4>}>
                        <MapTab
                            mapState={mapState}
                            deliveryTypes={ratesheetState.deliveryTypes}
                            ratesheetId={ratesheetState.ratesheetId}
                            openImport={() => importFromRatesheet.setImportType('mapZones')}
                        />
                    </Tab>
                    <Tab eventKey='documentation' title={<h4><i className='fas fa-book'></i> Documentation</h4>}>
                        <DocumentationTab />
                    </Tab>
                </Tabs>
                {/*
                  * One modal, opened from whichever tab owns the thing being imported. Lives here
                  * rather than in each tab because handleImport writes into this component's state.
                  */}
                <ImportFromRatesheetModal
                    show={!!importFromRatesheet.importType}
                    importType={importFromRatesheet.importType}
                    onHide={() => importFromRatesheet.reset()}
                    importFromRatesheet={importFromRatesheet}
                    handleImport={handleImport}
                    originalRates={{
                        mapZones: mapState.mapZones,
                        miscRates: ratesheetState.miscRates,
                        conditionals: ratesheetState.conditionals ?? [],
                        timingConditionals: ratesheetState.timingConditionals ?? [],
                    }}
                />
                <Row className='justify-content-md-center'>
                    <Col md={1}>
                        <Button onClick={store}>Save</Button>
                    </Col>
                </Row>
            </Col>
        </Row>
    )
}
