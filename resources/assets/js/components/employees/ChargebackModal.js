import React, {useEffect, useState} from 'react'
import {Button, ButtonGroup, Col, Form, FormControl, InputGroup, Modal, Row} from 'react-bootstrap'
import Select from 'react-select'
import DatePicker from 'react-datepicker'

const initialState = {
    chargebackId: '',
    chargebackName: '',
    continuous: true,
    countRemaining: 1,
    amount: '',
    glCode: '',
    startDate: new Date(),
    description: '',
    selectedEmployees: [],
    cell: {}
}

export default function ChargebackModal(props) {
    const [amount, setAmount] = useState('')
    const [chargebackId, setChargebackId] = useState('')
    const [chargebackName, setChargebackName] = useState('')
    const [continuous, setContinuous] = useState(true)
    const [countRemaining, setCountRemaining] = useState(1)
    const [description, setDescription] = useState('')
    const [glCode, setGlCode] = useState('')
    const [selectedEmployees, setSelectedEmployees] = useState([])
    const [startDate, setStartDate] = useState(new Date())

    useEffect(() => {
        setAmount(props.chargeback?.amount ?? '')
        setChargebackId(props.chargeback?.chargeback_id ?? '')
        setChargebackName(props.chargeback?.chargeback_name ?? '')
        setContinuous(props.chargeback?.continuous == 0 ? false : true)
        setCountRemaining(props.chargeback?.count_remaining ?? 1)
        setDescription(props.chargeback.description ?? '')
        setGlCode(props.chargeback?.gl_code ?? '')
        setSelectedEmployees(props.chargeback?.employee_id ? [props.employees.find(employee => employee.value == props.chargeback.employee_id)] : [])
        setStartDate(props.chargeback?.chargeback_start_date ? new Date(props.chargeback.chargeback_start_date) : new Date())
    }, [props.chargeback])

    const storeChargeback = () => {
        const data = {
            chargeback_id: chargebackId,
            amount: amount,
            count_remaining: countRemaining,
            continuous: continuous,
            gl_code: glCode,
            start_date: startDate.toLocaleString('en-us'),
            description: description,
            employee_ids: selectedEmployees.map(employee => {return employee.value}),
            name: chargebackName
        }
        makeAjaxRequest('/chargebacks', 'POST', data, response => {
            props.fetchTableData()
            props.toggleModal()
        })
    }

    return (
        <Modal show={props.show} onHide={props.toggleModal} size='lg'>
            <Modal.Header closeButton><Modal.Title>{chargebackId ? 'Edit' : 'Create'} Chargeback {chargebackId ? chargebackId : ''}</Modal.Title></Modal.Header>
            <Modal.Body>
                <Row>
                    <Col md={continuous ? 12 : 6}>
                        <Form.Group>
                            <Form.Check
                                name='continuous'
                                label='Continuous'
                                onChange={() => setContinuous(!continuous)}
                                checked={continuous}
                                value={continuous}
                            />
                        </Form.Group>
                    </Col>
                    {!continuous &&
                        <Col md={6}>
                            <InputGroup>
                                <InputGroup.Text>Repeat</InputGroup.Text>
                                <FormControl
                                    type='number'
                                    min={1}
                                    name='countRemaining'
                                    value={countRemaining}
                                    onChange={event => setCountRemaining(event.target.value)}
                                />
                                <InputGroup.Text>Times</InputGroup.Text>
                            </InputGroup>
                        </Col>
                    }
                    <Col md={6}>
                        <InputGroup>
                            <InputGroup.Text>Name</InputGroup.Text>
                            <FormControl
                                name='chargebackName'
                                value={chargebackName}
                                onChange={event => setChargebackName(event.target.value)}
                            />
                        </InputGroup>
                    </Col>
                    <Col md={6}>
                        <InputGroup>
                            <InputGroup.Text>Amount</InputGroup.Text>
                            <FormControl
                                type='number'
                                min={0}
                                name='amount'
                                value={amount}
                                onChange={event => setAmount(event.target.value)}
                                step={0.01}
                            />
                        </InputGroup>
                    </Col>
                    <Col md={6}>
                        <InputGroup>
                            <InputGroup.Text>GL Code</InputGroup.Text>
                            <FormControl
                                name='glCode'
                                value={glCode}
                                onChange={event => setGlCode(event.target.value)}
                            />
                        </InputGroup>
                    </Col>
                    <Col md={6}>
                        <InputGroup>
                            <InputGroup.Text>Start Date</InputGroup.Text>
                            <DatePicker
                                dateFormat='MMMM d, yyyy'
                                onChange={value => setStartDate(value)}
                                showMonthDropdown
                                showYearDropdown
                                scrollableMonthYearDropdown
                                scrollableYearDropdown
                                className='form-control'
                                selected={startDate}
                                wrapperClassName='form-control'
                            />
                        </InputGroup>
                    </Col>
                    <Col md={12}>
                        Description:
                        <FormControl
                            name='description'
                            as='textarea'
                            rows={3}
                            onChange={event => setDescription(event.target.value)}
                            value={description}
                        />
                    </Col>
                    <Col md={12}>
                        <InputGroup>
                            <InputGroup.Text>{chargebackId ? 'Employee' : 'Employees'}</InputGroup.Text>
                            <Select
                                options={props.employees}
                                value={selectedEmployees}
                                isMulti={!chargebackId}
                                onChange={value => setSelectedEmployees(value)}
                            />
                        </InputGroup>
                    </Col>
                </Row>
            </Modal.Body>
            <Modal.Footer className='justify-content-md-center'>
                <ButtonGroup>
                    <Button variant='light' onClick={props.toggleModal}>Cancel</Button>
                    <Button variant='success' onClick={storeChargeback}>Submit</Button>
                </ButtonGroup>
            </Modal.Footer>
        </Modal>
    )
}
