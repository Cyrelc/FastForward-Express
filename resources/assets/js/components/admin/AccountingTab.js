import React from 'react'
import {Card, Row, Col, InputGroup, FormControl} from 'react-bootstrap'
import Select from 'react-select'

export default function AccountingTab(props) {
    return (
        <Card border='dark'>
            <Card.Header><h2 className='text-muted'>Accounting</h2></Card.Header>
            <Card.Body>
                <Row>
                    <Col md={2}><h4 className='text-muted'>Taxes</h4></Col>
                    <Col md={10}>
                        <InputGroup>
                            <InputGroup.Prepend style={{width: '20%'}}>
                                <InputGroup.Text style={{width: '100%'}}>GST: </InputGroup.Text>
                            </InputGroup.Prepend>
                            <FormControl
                                type='number'
                                min={0}
                                max={100}
                                value={props.gst}
                                name='gst'
                                onChange={props.handleChange}
                            />
                            <InputGroup.Append>
                                <InputGroup.Text> %</InputGroup.Text>
                            </InputGroup.Append>
                        </InputGroup>
                    </Col>
                </Row>
                <hr/>
                <Row>
                    <Col md={2}><h4 className='text-muted'>Payment Types</h4></Col>
                    <Col md={10}>
                        {Object.keys(props.paymentTypes).map(index => 
                            <InputGroup key={'paymentType' + index}>
                                <InputGroup.Prepend style={{width: '20%'}}>
                                    <InputGroup.Text style={{width: '100%'}}>{props.paymentTypes[index].name}</InputGroup.Text>
                                </InputGroup.Prepend>
                                <Select
                                    options={props.ratesheets}
                                    getOptionLabel={ratesheet => ratesheet.ratesheet_id + ' - ' + ratesheet.name}
                                    getOptionValue={ratesheet => ratesheet.ratesheet_id}
                                    value={props.ratesheets.filter(ratesheet => ratesheet.ratesheet_id === props.paymentTypes[index].default_ratesheet_id)}
                                    onChange={ratesheet => props.handleChange({target: {name: 'default_ratesheet_id', type: 'text', value: ratesheet.ratesheet_id, paymentTypeId: props.paymentTypes[index].payment_type_id}})}
                                />
                            </InputGroup>
                        )}
                    </Col>
                </Row>
            </Card.Body>
        </Card>
    )
}
