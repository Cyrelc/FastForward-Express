import React, {Component, createRef} from 'react'
import {Button, Card, Col, InputGroup, Row} from 'react-bootstrap'
import DatePicker from 'react-datepicker'
import { ReactTabulator } from 'react-tabulator'

export default class GenerateManifests extends Component {
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
            {Title: 'Selected', field: 'isSelected', formatter: 'tickCross', hozAlign: 'center', headerHozAlign: 'center', headerSort: false, print: false, width: 50},
            {title: 'Employee ID', field: 'employee_id'},
            {title: 'Employee Number', field: 'employee_number'},
            {title: 'Employee', field: 'label'},
            {title: 'Valid Bills', field: 'valid_bill_count'},
            {title: 'Legacy Bills', field: 'legacy_bill_count'},
            {title: 'Incomplete Bills', field: 'incomplete_bill_count'}
        ]

        return (
            <Card>
                <Card.Header>
                    <Row className='justify-content-md-center'>
                        <h3>Generate Manifests</h3>
                    </Row>
                </Card.Header>
                <Card.Body>
                    <Row className='justify-content-md-center'>
                        <Col md={3}>
                            <InputGroup>
                                <InputGroup.Text>Start Date: </InputGroup.Text>
                                <DatePicker
                                    className='form-control'
                                    dateFormat='MMMM d, yyyy'
                                    placeholderText='After'
                                    selected={this.state.startDate}
                                    onChange={value => this.handleChange({target: {name: 'startDate', type: 'date', value: value}})}
                                    wrapperClassName='form-control'
                                />
                            </InputGroup>
                        </Col>
                        <Col md={3}>
                            <InputGroup>
                                <InputGroup.Text>End Date: </InputGroup.Text>
                                <DatePicker
                                    className='form-control'
                                    dateFormat='MMMM d, yyyy'
                                    placeholderText='Before'
                                    selected={this.state.endDate}
                                    onChange={value => this.handleChange({target: {name: 'endDate', type: 'date', value: value}})}
                                    wrapperClassName='form-control'
                                />
                            </InputGroup>
                        </Col>
                        <Col md={2}>
                            <Button variant='primary' disabled={this.state.employees.length === 0} onClick={this.store}>Generate Manifests</Button>
                        </Col>
                    </Row>
                </Card.Body>
                <Card.Footer>
                    <ReactTabulator
                        ref={this.state.tableRef}
                        columns={columns}
                        data={this.state.employees}
                        dataLoaded={() => {
                            const table = this.state.tableRef.current.table
                            table.rowManager.rows.map(row => {
                                const data = row.getData()
                                if(data.valid_bill_count > 0 && data.incomplete_bill_count === 0 && data.legacy_bill_count === 0)
                                    table.selectRow(row)
                            })
                        }}
                        options={{
                            layout: 'fitColumns',
                            maxHeight: '80vh'
                        }}
                        placeholder='No employees fit the selected criteria for generating a manifest'
                        rowSelected={row => {row.update({isSelected: true})}}
                        rowDeselected={row => {row.update({isSelected: false})}}
                        selectable={true}
                        selectableCheck={row => {
                            const selectable = row.getData().valid_bill_count > 0
                            return selectable
                        }}
                    />
                </Card.Footer>
            </Card>
        )
    }
}

