import React, {useEffect, useRef, useState} from 'react'
import {Button, Card, Col, InputGroup, Row} from 'react-bootstrap'
import DatePicker from 'react-datepicker'
import { ReactTabulator } from 'react-tabulator'
import {useHistory} from 'react-router-dom'

export default function GenerateManifests(props) {
    const [employees, setEmployees] = useState([])
    const [startDate, setStartDate] = useState(new Date((new Date()).getFullYear(), (new Date()).getMonth() - 1, 1))
    const [endDate, setEndDate] = useState(new Date((new Date()).moveToFirstDayOfMonth().setHours(-1)))
    const [isLoading, setIsLoading] = useState(true)
    const [isStoring, setIsStoring] = useState(false)

    const history = useHistory()
    const tableRef = useRef()

    useEffect(() => {
        refreshEmployees()
    }, [startDate, endDate])

    const refreshEmployees = () => {
        setIsLoading(true)
        const data = {
            start_date: startDate.toLocaleString('en-US'),
            end_date: endDate.toLocaleString('en-US')
        }

        makeAjaxRequest('/manifests/getDriversToManifest', 'GET', data, result => {
            setEmployees(JSON.parse(result))
            setIsLoading(false)
        })
    }

    const store = () => {
        if(isStoring)
            return
        else
            setIsStoring(true)

        if(tableRef.current === undefined || tableRef.current.table.getSelectedData().length === 0) {
            toastr.error('Please select at least one driver to manifest')
            return
        }

        const data = {
            employees: tableRef.current.table.getSelectedData().map(employee => {return employee.employee_id}),
            start_date: startDate.toLocaleString('en-US'),
            end_date: endDate.toLocaleString('en-US')
        }
        makeAjaxRequest('/manifests/store', 'POST', data, response => {
            toastr.clear()
            toastr.success('Successfully generated manifests', 'Success', {
                'progressBar': true,
                'showDuration': 500,
                'onHidden': () => history.push('/manifests'),
                'positionClass': 'toast-top-center'
            })
        }, error => {
            setIsLoading(false)
        })
    }

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
                                selected={startDate}
                                onChange={setStartDate}
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
                                selected={endDate}
                                onChange={setEndDate}
                                wrapperClassName='form-control'
                            />
                        </InputGroup>
                    </Col>
                    <Col md={2}>
                        <Button variant='primary' disabled={!employees.length || isStoring} onClick={store}>Generate Manifests</Button>
                    </Col>
                </Row>
            </Card.Body>
            <Card.Footer>
                {isLoading ? 
                    <Row className='justify-content-md-center'>
                        <Col md={3}><h4><i className='fas fa-cog fa-spin'></i>  Loading...</h4></Col>
                    </Row> :
                    <ReactTabulator
                        ref={tableRef}
                        columns={columns}
                        data={employees}
                        dataLoaded={() => {
                            const table = tableRef.current.table
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
                }
            </Card.Footer>
        </Card>
    )
}

