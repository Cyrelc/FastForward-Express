import React from 'react'
import {Button, Col, Form, InputGroup, ListGroup, Modal, Row} from 'react-bootstrap'
import Select from 'react-select'

import {useAPI} from '../../contexts/APIContext'

/**
 * Copies items of ONE kind from another ratesheet into this one.
 *
 * This used to be a whole tab that also asked you which kind of thing you wanted - which meant it had
 * to know about every rate type on the sheet, and lived nowhere near any of them. It is now opened
 * from the tab that owns the thing being imported, so the kind is a given and the conflict decisions
 * ("this name is already taken") are made in front of the list they apply to.
 *
 * Not to be confused with the conditional tabs' other import, which pastes a single exported rule
 * string. This one reads from another ratesheet.
 */
const TYPE_LABELS = {
    miscRates: 'Miscellaneous Rates',
    mapZones: 'Map Zones',
    conditionals: 'Pricing Conditionals',
    timingConditionals: 'Timing Conditionals',
}

export default function ImportFromRatesheetModal(props) {
    const api = useAPI()
    const {importType, show, onHide} = props
    const {
        importRatesheet,
        selectedImports,
        setSelectedImports,
        ratesheets,
        setImportRatesheet,
    } = props.importFromRatesheet

    if(!show)
        return null

    const available = (importRatesheet && importRatesheet[importType]) ? importRatesheet[importType] : null
    const existing = props.originalRates[importType] ?? []

    const getRatesheet = (ratesheetId) => {
        if(importRatesheet && ratesheetId === importRatesheet.ratesheet_id)
            return
        setSelectedImports([])
        api.get(`/ratesheets/${ratesheetId}`).then(response => setImportRatesheet(response))
    }

    const handleSelection = (event) => {
        const element = available.sortBy('name')[event.target.value]
        if(selectedImports.some(selected => selected.sortedIndex === event.target.value))
            setSelectedImports(selectedImports.filter(selected => selected.sortedIndex != event.target.value))
        else
            setSelectedImports(selectedImports.concat([{...element, sortedIndex: event.target.value}]))
    }

    const selectAll = () => {
        setSelectedImports(available.sortBy('name').map((element, index) => ({...element, sortedIndex: index})))
    }

    const importAnd = (replace) => (event) => {
        props.handleImport(event, replace)
        onHide()
    }

    const nothingSelected = !selectedImports || selectedImports.length === 0

    return (
        <Modal show={show} onHide={onHide} size='lg'>
            <Modal.Header closeButton>
                <Modal.Title>Import {TYPE_LABELS[importType] ?? importType}</Modal.Title>
            </Modal.Header>
            <Modal.Body>
                <InputGroup>
                    <InputGroup.Text>From ratesheet</InputGroup.Text>
                    <div style={{flex: 1}}>
                        <Select
                            options={ratesheets}
                            getOptionLabel={ratesheet => `${ratesheet.ratesheet_id} - ${ratesheet.name}`}
                            getOptionValue={ratesheet => ratesheet.ratesheet_id}
                            onChange={ratesheet => getRatesheet(ratesheet.ratesheet_id)}
                            placeholder='Choose a ratesheet to copy from'
                        />
                    </div>
                </InputGroup>

                {available &&
                    <>
                        <hr/>
                        <Row>
                            <Col>
                                <Button size='sm' variant='outline-primary' onClick={selectAll} style={{marginRight: '4px'}}>
                                    Select All
                                </Button>
                                <Button size='sm' variant='outline-secondary' onClick={() => setSelectedImports([])} disabled={nothingSelected}>
                                    Deselect All
                                </Button>
                            </Col>
                        </Row>
                        <ListGroup style={{marginTop: '8px', maxHeight: '40vh', overflowY: 'auto'}}>
                            {available.sortBy('name').map((importOption, index) => {
                                const nameTaken = existing.find(original => original.name === importOption.name)
                                return (
                                    <ListGroup.Item key={importOption.name + index}>
                                        <Form.Check style={{display: 'inline-flex', marginRight: '1em'}}>
                                            <Form.Check.Input
                                                type='checkbox'
                                                id={importOption.name + index}
                                                value={index}
                                                onChange={handleSelection}
                                                checked={selectedImports.some(selected => selected.sortedIndex == index)}
                                            />
                                            <Form.Check.Label style={{paddingLeft: '1em'}}>{importOption.name}</Form.Check.Label>
                                        </Form.Check>
                                        {nameTaken &&
                                            <i
                                                className='fas fa-exclamation-circle'
                                                title='Something with this name already exists here — "Import Only" will add a copy, "Import & Replace" will overwrite it'
                                                style={{color: 'red', float: 'right'}}
                                            />
                                        }
                                    </ListGroup.Item>
                                )
                            })}
                        </ListGroup>
                    </>
                }

                {importRatesheet && !available &&
                    <p className='text-muted' style={{marginTop: '12px'}}>
                        <em>That ratesheet has no {TYPE_LABELS[importType] ?? importType} to import.</em>
                    </p>
                }
            </Modal.Body>
            <Modal.Footer>
                <Button variant='secondary' onClick={onHide}>Cancel</Button>
                <Button variant='success' onClick={importAnd(false)} disabled={nothingSelected}>
                    Import Only
                </Button>
                <Button variant='success' onClick={importAnd(true)} disabled={nothingSelected}>
                    Import &amp; Replace
                </Button>
            </Modal.Footer>
        </Modal>
    )
}
