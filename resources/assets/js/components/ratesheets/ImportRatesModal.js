import React from 'react'
import {Button, ButtonGroup, Col, Form, InputGroup, Modal, Row} from 'react-bootstrap'
import Select from 'react-select'

export default function ImportRatesModal(props) {

    const importTypes = [
        {label: 'Miscellanous', value: 'miscRates'},
        {label: 'Weight Rates', value: 'weightRates'},
        {label: 'Time Rates', value: 'timeRates'},
        {label: 'Map Zones', value: 'mapZones'}
    ]

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

    return (
        <Modal show={props.showImportModal} onHide={() => props.handleChange({target: {name: 'showImportModal', type: 'boolean', value: false}})} size='lg'>
            <Modal.Header closeButton><Modal.Title>Import</Modal.Title></Modal.Header>
            <Modal.Body>
                <Row>
                    <Col md={6}>
                        <InputGroup>
                            <InputGroup.Prepend><InputGroup.Text>Ratesheet: </InputGroup.Text></InputGroup.Prepend>
                            <Select
                                options={props.ratesheets}
                                getOptionLabel={ratesheet => ratesheet.ratesheet_id + ' - ' + ratesheet.name}
                                getOptionValue={ratesheet => ratesheet.ratesheet_id}
                                className='formcontrol'
                                onChange={ratesheet => getRatesheet(ratesheet.ratesheet_id)}
                            />
                        </InputGroup>
                    </Col>
                    <Col md={6}>
                        <InputGroup>
                            <InputGroup.Prepend><InputGroup.Text>Type: </InputGroup.Text></InputGroup.Prepend>
                            <Select
                                options={importTypes}
                                value={importTypes.filter(importType => props.importType == importType.value)}
                                onChange={importType => handleImportTypeChange(importType)}
                            />
                        </InputGroup>
                    </Col>
                </Row>
                <hr/>
                <Row>
                    <ul style={{listStyle: 'none'}}>
                        {(props.importRatesheet && props.importType && props.importRatesheet[props.importType]) && (props.importRatesheet[props.importType]).sortBy('name').map((importOption, index) => 
                            <li key={importOption.name + '.' + index}>
                                <Form.Check
                                    label={importOption.name}
                                    value={index}
                                    onChange={handleSelection}
                                    checked={props.selectedImports.some(element => element.sortedIndex == index)}
                                />
                            </li>
                        )}
                    </ul>
                </Row>
            </Modal.Body>
            <Modal.Footer>
                <ButtonGroup style={{float: 'right'}}>
                    <Button variant='success' onClick={props.handleImport}>Import Selected</Button>
                    <Button variant='light' onClick={() => props.handleChange({target: {name: 'showImportModal', type: 'boolean', value: false}})}>Cancel</Button>
                </ButtonGroup>
            </Modal.Footer>
        </Modal>
    )
}

