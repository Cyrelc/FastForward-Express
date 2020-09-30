import React from 'react'
import {Card, Row, Col, InputGroup, FormControl, Form} from 'react-bootstrap'
import DatePicker from 'react-datepicker'

export default function AdministrationTab(props) {
    return (
        <Card border='dark'>
            <Card.Header>
                <Row>
                    <Col md={3}>
                        <InputGroup>
                            <InputGroup.Prepend>
                                <InputGroup.Text>Employee Number</InputGroup.Text>
                            </InputGroup.Prepend>
                            <FormControl
                                name='employeeNumber'
                                onChange={props.handleChanges}
                                placeholder='Employee Number'
                                readOnly={props.readOnly}
                                value={props.employeeNumber}
                            />
                        </InputGroup>
                    </Col>
                    <Col md={3}>
                        <Form.Check
                            checked={props.active}
                            name='active'
                            value={props.active}
                            onChange={props.handleChanges}
                            label='Active'
                        />
                    </Col>
                    <Col md={3}>
                        <Form.Check
                            checked={props.driver}
                            name='driver'
                            value={props.driver}
                            onChange={props.handleChanges}
                            label='Is Driver'
                        />
                    </Col>
                </Row>
                <Row>
                    <Col md={2}><h4 className='text-muted'>Additional Info</h4></Col>
                    <Col md={3}>
                        <InputGroup>
                            <InputGroup.Prepend><InputGroup.Text>SIN</InputGroup.Text></InputGroup.Prepend>
                            <FormControl
                                name='SIN'
                                placeholder='Social Insurance Number'
                                value={props.SIN}
                                onChange={props.handleChanges}
                                readOnly={props.readOnly}
                            />
                        </InputGroup>
                    </Col>
                    <Col md={3}>
                        <InputGroup>
                            <InputGroup.Prepend><InputGroup.Text>Birth Date</InputGroup.Text></InputGroup.Prepend>
                            <DatePicker 
                                dateFormat='MMMM d, yyyy'
                                onChange={value => props.handleChanges({target: {name: 'birthDate', value: value}})}
                                showMonthDropdown
                                showYearDropdown
                                monthDropdownItemNumber={15}
                                scrollableMonthDropdown
                                selected={props.birthDate}
                                className='form-control'
                            />
                        </InputGroup>
                    </Col>
                    <Col md={4}>
                        <InputGroup>
                            <InputGroup.Prepend><InputGroup.Text>Start Date</InputGroup.Text></InputGroup.Prepend>
                            <DatePicker 
                                dateFormat='MMMM d, yyyy'
                                onChange={value => props.handleChanges({target: {name: 'startDate', value: value}})}
                                showMonthDropdown
                                showYearDropdown
                                monthDropdownItemNumber={15}
                                scrollableMonthDropdown
                                selected={props.startDate}
                                className='form-control'
                            />
                        </InputGroup>
                    </Col>
                </Row>
            </Card.Header>
        </Card>
    )
}
