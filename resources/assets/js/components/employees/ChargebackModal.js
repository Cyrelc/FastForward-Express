import React, {Component} from 'react'
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

export default class ChargebackModal extends Component {
    constructor() {
        super()
        this.state = {
            ...initialState,
            employees: [],
        }
        this.handleChange = this.handleChange.bind(this)
        this.storeChargeback = this.storeChargeback.bind(this)
    }

    componentDidUpdate(prevProps) {
        if(this.props.show === true && prevProps.show === false) {
            this.setState({...initialState})
            if(this.props.modalAction === 'edit' || this.props.modalAction === 'copy') {
                const cellData = this.props.cell.getData()
                const setup = {
                    cell: this.props.cell,
                    chargebackId: this.props.modalAction === 'edit' ? cellData.chargeback_id : '',
                    chargebackName: cellData.chargeback_name,
                    continuous: cellData.continuous,
                    countRemaining: cellData.count_remaining,
                    amount: cellData.amount,
                    glCode: cellData.gl_code,
                    startDate: Date.parse(cellData.chargeback_start_date),
                    description: cellData.description,
                    selectedEmployees: this.props.modalAction === 'edit' ? this.props.employees.filter(employee => employee.value === cellData.employee_id) : []
                }
                this.setState(setup)
            }
            else
                this.setState({...initialState})
        }
    }

    handleChange(event) {
        const {checked, name, type, value} = event.target
        this.setState({[name]: type === 'checkbox' ? checked : value})
    }

    storeChargeback() {
        const data = {
            chargeback_id: this.state.chargebackId,
            amount: this.state.amount,
            count_remaining: this.state.countRemaining,
            continuous: this.state.continuous,
            gl_code: this.state.glCode,
            start_date: this.state.startDate.toLocaleString('en-us'),
            description: this.state.description,
            employee_ids: this.state.selectedEmployees.map(employee => {return employee.value}),
            name: this.state.chargebackName
        }
        makeAjaxRequest('/chargebacks', 'POST', data, response => {
            this.props.toggleRefreshTable()
            this.props.toggleModal()
        })
    }

    render() {
        return (
            <Modal show={this.props.show} onHide={this.props.toggleModal} size='lg'>
                <Modal.Header closeButton><Modal.Title>{this.state.chargebackId ? 'Edit' : 'Create'} Chargeback</Modal.Title></Modal.Header>
                <Modal.Body>
                    <Row>
                        <Col md={this.state.continuous ? 12 : 6}>
                            <Form.Group>
                                <Form.Check
                                    name='continuous'
                                    label='Continuous'
                                    onChange={this.handleChange}
                                    checked={this.state.continuous}
                                    value={this.state.continuous}
                                />
                            </Form.Group>
                        </Col>
                        {!this.state.continuous &&
                            <Col md={6}>
                                <InputGroup>
                                    <InputGroup.Text>Repeat</InputGroup.Text>
                                    <FormControl
                                        type='number'
                                        min={1}
                                        name='countRemaining'
                                        value={this.state.countRemaining}
                                        onChange={this.handleChange}
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
                                    value={this.state.chargebackName}
                                    onChange={this.handleChange}
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
                                    value={this.state.amount}
                                    onChange={this.handleChange}
                                    step={0.01}
                                />
                            </InputGroup>
                        </Col>
                        <Col md={6}>
                            <InputGroup>
                                <InputGroup.Text>GL Code</InputGroup.Text>
                                <FormControl
                                    name='glCode'
                                    value={this.state.glCode}
                                    onChange={this.handleChange}
                                />
                            </InputGroup>
                        </Col>
                        <Col md={6}>
                            <InputGroup>
                                <InputGroup.Text>Start Date</InputGroup.Text>
                                <DatePicker
                                    dateFormat='MMMM d, yyyy'
                                    onChange={value => this.handleChange({target: {name: 'startDate', type: 'date', value: value}})}
                                    showMonthDropdown
                                    showYearDropdown
                                    scrollableMonthYearDropdown
                                    scrollableYearDropdown
                                    className='form-control'
                                    selected={this.state.startDate}
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
                                onChange={this.handleChange}
                                value={this.state.description}
                            />
                        </Col>
                        <Col md={12}>
                            <InputGroup>
                                <InputGroup.Text>{this.state.chargebackId ? 'Employee' : 'Employees'}</InputGroup.Text>
                                <Select
                                    options={this.props.employees}
                                    value={this.state.selectedEmployees}
                                    isMulti={this.state.chargebackId ? false : true}
                                    onChange={value => this.handleChange({target: {name: 'selectedEmployees', type: 'object', value: value}})}
                                />
                            </InputGroup>
                        </Col>
                    </Row>
                </Modal.Body>
                <Modal.Footer className='justify-content-md-center'>
                    <ButtonGroup>
                        <Button variant='light' onClick={this.props.toggleModal}>Cancel</Button>
                        <Button variant='success' onClick={this.storeChargeback}>Submit</Button>
                    </ButtonGroup>
                </Modal.Footer>
            </Modal>
        )
    }
}

