import React from 'react'
import {Button, ButtonGroup, Col, Form, InputGroup, ListGroup, Modal, Row} from 'react-bootstrap'
import Select from 'react-select'

export default function ImportRatesModal(props) {
    const importTypes = [
        {label: 'Miscellanous', value: 'miscRates'},
        {label: 'Weight Rates', value: 'weightRates'},
        {label: 'Time Rates', value: 'timeRates'},
        {label: 'Map Zones', value: 'mapZones'}
    ]

    function deselectAll() {
        props.handleChange({target: {name: 'selectedImports', type: 'array', value: []}})
    }

    function getRatesheet(ratesheetId) {
        if(props.importRatesheet && ratesheetId === props.importRatesheet.ratesheet_id)
            return
        makeAjaxRequest('/ratesheets/getModel/' + ratesheetId, 'GET', null, response => {
            props.handleChange({target: {name: 'importRatesheet', type: 'object', value: JSON.parse(response)}})
        })
    }

    function handleImportTypeChange(importType) {
        props.handleChange({target: {name: 'selectedImports', type: 'array', value: []}})
        props.handleChange({target: {name: 'importType', type: 'string', value: importType.value}})
    }

    function handleSelection(event) {
        const element = props.importRatesheet[props.importType].sortBy('name')[event.target.value];
        if(props.selectedImports.some(element => element.sortedIndex === event.target.value))
            props.handleChange({target: {name: 'selectedImports', type: 'array', value: props.selectedImports.filter(element => element.sortedIndex != event.target.value)}})
        else
            props.handleChange({target: {name: 'selectedImports', type: 'array', value: props.selectedImports.concat([{...element, sortedIndex: event.target.value}])}})
    }

    function selectAll() {
        const elements = props.importRatesheet[props.importType].sortBy('name').map((element, index) => {return {...element, sortedIndex: index}})
        props.handleChange({target: {name: 'selectedImports', type: 'array', value: elements}})
    }

    return (
        <Modal show={props.showImportModal} onHide={() => props.handleChange({target: {name: 'showImportModal', type: 'boolean', value: false}})} dialogClassName='modal-80w'>
            <Modal.Header closeButton><Modal.Title>Import</Modal.Title></Modal.Header>
            <Modal.Body>
                <Row>
                    <Col md={4}>
                        <InputGroup>
                            <InputGroup.Text>Ratesheet: </InputGroup.Text>
                            <Select
                                options={props.ratesheets}
                                getOptionLabel={ratesheet => ratesheet.ratesheet_id + ' - ' + ratesheet.name}
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
                        <Button variant='primary' onClick={selectAll} disabled={!props.importRatesheet || !props.importType}>Select All</Button>
                        <Button variant='info' onClick={deselectAll} disabled={!props.importRatesheet || !props.importType || props.selectedImports.length === 0}>Deselect All</Button>
                        <Button variant='success' onClick={props.handleImport} disabled={!props.importRatesheet || !props.importType || !props.selectedImports || props.selectedImports.length == 0}>Import Only</Button>
                        <Button variant='success' onClick={() => props.handleImport(true)} disabled={!props.importRatesheet || !props.importType || !props.selectedImports || props.selectedImports.length == 0}>Import & Replace</Button>
                    </Col>
                </Row>
                <hr/>
                <Row>
                    <ListGroup horizontal style={{flexWrap: 'inherit'}}>
                        {(props.importRatesheet && props.importType && props.importRatesheet[props.importType]) && (props.importRatesheet[props.importType]).sortBy('name').map((importOption, index) => {
                            const nameTaken = props.originalRates[props.importType].find(original => original.name === importOption.name)
                            return (
                                <ListGroup.Item style={{width: '25%'}} key={importOption.name + index}>
                                    <Form.Check key={importOption.name + index} style={{display: 'inline-flex', marginRight: '1em'}}>
                                        <Form.Check.Input type='checkbox' id={importOption.name + index} value={index} onChange={handleSelection} checked={props.selectedImports.some(element => element.sortedIndex == index)}/>
                                        <Form.Check.Label style={{paddingLeft: '1em'}}>{importOption.name}</Form.Check.Label>
                                    </Form.Check>
                                    {nameTaken && <i className='fas fa-exclamation-circle' title='A rate or zone with this name already exists' style={{color: 'red', float: 'right'}}></i>}
                                </ListGroup.Item>
                            )
                        })}
                    </ListGroup>
                </Row>
            </Modal.Body>
        </Modal>
    )
}

