import React from 'react'
import {Card, Row, Col, InputGroup, FormControl} from 'react-bootstrap'
import DatePicker from 'react-datepicker'

export default function MiscTab(props) {
    return(
        <Card border='dark'>
            <Card.Header><h2 className='text-muted'>Miscellanous</h2>></Card.Header>
            <Card.Body>
                <Row>
                    <Col md={2}><h4 className='text-muted'>Business Hours</h4></Col>
                    <Col md={10}>
                        <InputGroup>
                            <InputGroup.Prepend><InputGroup.Text>Earliest Pickup: </InputGroup.Text></InputGroup.Prepend>
                            <DatePicker
                                showTimeSelect
                                showTimeSelectOnly
                                timeIntervals={15}
                                dateFormat='h:mm aa'
                                selected={props.businessHoursOpen}
                                value={props.businessHoursOpen}
                                onChange={datetime => props.handleChange({target: {name: 'businessHoursOpen', type:'date', value: datetime}})}
                                className='form-control'
                            />
                            <InputGroup.Append><InputGroup.Text> Last Delivery: </InputGroup.Text></InputGroup.Append>
                            <DatePicker
                                showTimeSelect
                                showTimeSelectOnly
                                timeIntervals={15}
                                dateFormat='h:mm aa'
                                selected={props.businessHoursClose}
                                value={props.businessHoursClose}
                                onChange={datetime => props.handleChange({target: {name: 'businessHoursClose', type:'date', value: datetime}})}
                                className='form-control'
                            />
                        </InputGroup>
                    </Col>
                </Row>
            </Card.Body>
        </Card>
    )
}

