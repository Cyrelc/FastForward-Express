import React, {Component, createRef} from 'react'
import {Button, Card, Col, InputGroup, Row} from 'react-bootstrap'
import DatePicker from 'react-datepicker'
import { ReactTabulator } from 'react-tabulator'

export default class ManifestsGenerate extends Component {
    constructor() {
        super()
        this.state = {
            employees: [],
            startDate: new Date(),
            endDate: new Date(),
            tableRef: createRef()
        }
        this.handleChange = this.handleChange.bind(this)
        this.refreshEmployees = this.refreshEmployees.bind(this)
        this.store = this.store.bind(this)
    }

    componentDidMount() {
        const currentDate = new Date()
        const firstDayOfPreviousMonth = new Date(currentDate.getFullYear(), currentDate.getMonth() - 1, 1)
        //note the following line MODIFIES currentDate - so if you use it subsequently, beware!!
        const lastDayOfPreviousMonth = new Date(currentDate.moveToFirstDayOfMonth().setHours(-1))
        this.setState({startDate: firstDayOfPreviousMonth, endDate: lastDayOfPreviousMonth}, this.refreshEmployees)
    }

    handleChange(event) {
        const {name, type, checked, value} = event.target
        this.setState({[name]: type === 'checkbox' ? checked : value}, this.refreshEmployees)
    }

    refreshEmployees() {
        const data = {
            start_date: this.state.startDate.toLocaleString('en-US'),
            end_date: this.state.endDate.toLocaleString('en-US')
        }
        makeAjaxRequest('/manifests/getDriversToManifest', 'GET', data, result => {
            this.setState({
                employees: JSON.parse(result)
            })
        })
    }

    store() {
        if(this.state.tableRef.current === undefined || this.state.tableRef.current.table.getSelectedData().length === 0) {
            toastr.error('Please select at least one driver to manifest')
            return
        }

        const data = {
            employees: this.state.tableRef.current.table.getSelectedData().map(employee => {return employee.employee_id}),
            start_date: this.state.startDate.toLocaleString('en-US'),
            end_date: this.state.endDate.toLocaleString('en-US')
        }
        makeAjaxRequest('/manifests/store', 'POST', data, response => {
            toastr.clear()
            toastr.success('Successfully generated manifests', 'Success', {
                'progressBar': true,
                'showDuration': 500,
                'onHidden': window.location = '/app/manifests',
                'positionClass': 'toast-top-center'
            })
        })
    }

    render() {
        const columns = [
            {formatter: 'rowSelection', titleFormatter: 'rowSelection', hozAlign: 'center', headerHozAlign: 'center', headerSort: false, print: false, width: 50},
            {title: 'Employee ID', field: 'employee_id'},
            {title: 'Employee Number', field: 'employee_number'},
            {title: 'Employee Name', formatter: (cell) => {return cell.getRow().getData().contact.first_name + ' ' + cell.getRow().getData().contact.last_name}},
            {title: 'Company Name', field: 'company_name'},
            {title: 'Bills Matched', field: 'bill_count'}
        ]

        return (
            <Card>
                <Card.Header>
                    <Row className='justify-content-md-center'>
                        <h3>Generate Invoices</h3>
                    </Row>
                </Card.Header>
                <Card.Body>
                    <Row className='justify-content-md-center'>
                        <Col md={3}>
                            <InputGroup>
                                <InputGroup.Prepend><InputGroup.Text>Start Date: </InputGroup.Text></InputGroup.Prepend>
                                <DatePicker
                                    className='form-control'
                                    dateFormat='MMMM d, yyyy'
                                    isClearable
                                    placeholderText='After'
                                    selected={this.state.startDate}
                                    onChange={value => this.handleChange({target: {name: 'startDate', type: 'date', value: value}})}
                                />
                            </InputGroup>
                        </Col>
                        <Col md={3}>
                            <InputGroup>
                                <InputGroup.Prepend><InputGroup.Text>End Date: </InputGroup.Text></InputGroup.Prepend>
                                <DatePicker
                                    className='form-control'
                                    dateFormat='MMMM d, yyyy'
                                    isClearable
                                    placeholderText='Before'
                                    selected={this.state.endDate}
                                    onChange={value => this.handleChange({target: {name: 'endDate', type: 'date', value: value}})}
                                />
                            </InputGroup>
                        </Col>
                        <Col md={2}>
                            <Button variant='primary' disabled={this.state.employees.length === 0} onClick={this.store}>Generate Manifests</Button>
                        </Col>
                    </Row>
                </Card.Body>
                <Card.Footer>
                    {
                        this.state.employees.length === 0 ?
                        <p style={{color: 'red'}}>Currently no employees match the selected criteria</p> :
                        <ReactTabulator
                            ref={this.state.tableRef}
                            columns={columns}
                            data={this.state.employees}
                            dataLoaded={() => {
                                this.state.tableRef.current.table.selectRow();
                            }}
                            options={{
                                layout: 'fitColumns',
                                maxHeight: '80vh'
                            }}
                            selectable='highlight'
                            selectableCheck={() => {return true}}
                        />
                    }
                </Card.Footer>
            </Card>
        )
    }
}

