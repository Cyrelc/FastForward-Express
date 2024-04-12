import React from 'react'
import {Button, Card, Col, Form, InputGroup, ListGroup, Row} from 'react-bootstrap'
import Select from 'react-select'

import {useAPI} from '../../contexts/APIContext'

export default function ImportRatesTab(props) {
    const api = useAPI()
    const {
        importRatesheet,
        importType,
        selectedImports,
        setImportType,
        setSelectedImports,
        ratesheets,
        setImportRatesheet,
    } = props.importFromRatesheet

    const importTypes = [
        {label: 'Miscellanous', value: 'miscRates'},
        {label: 'Weight Rates', value: 'weightRates'},
        {label: 'Time Rates', value: 'timeRates'},
        {label: 'Map Zones', value: 'mapZones'}
    ]

    function getRatesheet(ratesheetId) {
        if(importRatesheet && ratesheetId === importRatesheet.ratesheet_id)
            return
        api.get(`/ratesheets/${ratesheetId}`).then(response => {
            setImportRatesheet(response)
        })
    }

    function handleImportTypeChange(importType) {
        setSelectedImports([])
        setImportType(importType.value)
    }

    function handleSelection(event) {
        const element = importRatesheet[importType].sortBy('name')[event.target.value];
        if(selectedImports.some(element => element.sortedIndex === event.target.value))
            setSelectedImports(selectedImports.filter(element => element.sortedIndex != event.target.value))
        else
            setSelectedImports(selectedImports.concat([{...element, sortedIndex: event.target.value}]))
    }

    function selectAll() {
        const elements = importRatesheet[importType].sortBy('name').map((element, index) => {return {...element, sortedIndex: index}})
        setSelectedImports(elements)
    }

    return (
        <Card>
            <Card.Header>
                <Card.Title>Import</Card.Title>
            </Card.Header>
            <Card.Body>
                <Row>
                    <Col md={4}>
                        <InputGroup>
                            <InputGroup.Text>Ratesheet: </InputGroup.Text>
                            <Select
                                options={ratesheets}
                                getOptionLabel={ratesheet => `${ratesheet.ratesheet_id} - ${ratesheet.name}`}
                                getOptionValue={ratesheet => ratesheet.ratesheet_id}
                                className='formcontrol'
                                onChange={ratesheet => getRatesheet(ratesheet.ratesheet_id)}
                            />
                        </InputGroup>
                    </Col>
                    <Col md={4}>
                        <InputGroup>
                            <InputGroup.Text>Type: </InputGroup.Text>
                            <Select
                                options={importTypes}
                                value={importTypes.filter(importType => props.importType == importType.value)}
                                onChange={importType => handleImportTypeChange(importType)}
                            />
                        </InputGroup>
                    </Col>
                    <Col md={4} align='right'>
                        <Button variant='primary' onClick={selectAll} disabled={!importRatesheet || !importType}>Select All</Button>
                        <Button variant='info' onClick={() => setSelectedImports([])} disabled={!importRatesheet || !importType || selectedImports.length === 0}>Deselect All</Button>
                        <Button variant='success' onClick={props.handleImport} disabled={!importRatesheet || !importType || !selectedImports || selectedImports.length == 0}>Import Only</Button>
                        <Button variant='success' onClick={(event) => props.handleImport(event, true)} disabled={!importRatesheet || !importType || !selectedImports || selectedImports.length == 0}>Import & Replace</Button>
                    </Col>
                </Row>
                <hr/>
                <Row>
                    <ListGroup horizontal style={{flexWrap: 'inherit'}}>
                        {(importRatesheet && importType && importRatesheet[importType]) &&
                            (importRatesheet[importType]).sortBy('name').map((importOption, index) => {
                                const nameTaken = props.originalRates[importType].find(original => original.name === importOption.name)
                                return (
                                    <ListGroup.Item style={{width: '25%'}} key={importOption.name + index}>
                                        <Form.Check key={importOption.name + index} style={{display: 'inline-flex', marginRight: '1em'}}>
                                            <Form.Check.Input type='checkbox' id={importOption.name + index} value={index} onChange={handleSelection} checked={selectedImports.some(element => element.sortedIndex == index)}/>
                                            <Form.Check.Label style={{paddingLeft: '1em'}}>{importOption.name}</Form.Check.Label>
                                        </Form.Check>
                                        {nameTaken && <i className='fas fa-exclamation-circle' title='A rate or zone with this name already exists' style={{color: 'red', float: 'right'}}></i>}
                                    </ListGroup.Item>
                                )
                            }
                        )}
                    </ListGroup>
                </Row>
            </Card.Body>
        </Card>
    )
}

