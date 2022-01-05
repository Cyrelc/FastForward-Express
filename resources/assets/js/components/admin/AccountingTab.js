import React from 'react'
import {Card, Row, Col, InputGroup, FormControl} from 'react-bootstrap'
import Select from 'react-select'

export default function AccountingTab(props) {
    return (
        <Card border='dark'>
            <Card.Header><Card.Title>Accounting</Card.Title></Card.Header>
            <Card.Body>
                <Row>
                    <Col md={2}><h4 className='text-muted'>Taxes</h4></Col>
                    <Col md={10}>
                        <InputGroup>
                            <InputGroup.Text>GST: </InputGroup.Text>
                            <FormControl
                                type='number'
                                min={0}
                                max={100}
                                value={props.gst}
                                name='gst'
                                onChange={props.handleChange}
                            />
                            <InputGroup.Text> %</InputGroup.Text>
                        </InputGroup>
                    </Col>
                </Row>
                <hr/>
                <Row>
                    <Col md={2}><h4 className='text-muted'>Default Ratesheets For Payment Types</h4></Col>
                    <Col md={10}>
                        {props.paymentTypes && Object.keys(props.paymentTypes).map(index =>
                            <InputGroup key={'paymentType' + index}>
                                <InputGroup.Text style={{width: '20%'}}>{props.paymentTypes[index].name}</InputGroup.Text>
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
