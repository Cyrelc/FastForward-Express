import React, {useEffect, useRef, useState} from 'react'
import {Button, Card, Col, InputGroup, Row} from 'react-bootstrap'
import DatePicker from 'react-datepicker'
import {TabulatorFull as Tabulator} from 'tabulator-tables'
import {useHistory} from 'react-router-dom'
import {toast} from 'react-toastify'
import {DateTime} from 'luxon'

import {useAPI} from '../../contexts/APIContext'

const columns = [
    {title: 'Selected', field: 'isSelected', formatter: 'tickCross', hozAlign: 'center', headerHozAlign: 'center', headerSort: false, print: false, width: 50},
    {title: 'Employee ID', field: 'employee_id'},
    {title: 'Employee Number', field: 'employee_number'},
    {title: 'Employee', field: 'label'},
    {title: 'Valid Bills', field: 'valid_bill_count'},
    {title: 'Legacy Bills', field: 'legacy_bill_count'},
    {title: 'Incomplete Bills', field: 'incomplete_bill_count'}
]

export default function GenerateManifests(props) {
    const [employees, setEmployees] = useState([])
    const [startDate, setStartDate] = useState(DateTime.now().minus({months: 1}).startOf('month').toJSDate())
    const [endDate, setEndDate] = useState(DateTime.now().minus({months: 1}).endOf('month').toJSDate())
    const [isLoading, setIsLoading] = useState(true)
    const [isStoring, setIsStoring] = useState(false)
    const [table, setTable] = useState(null)

    const api = useAPI();
    const history = useHistory()
    const tableRef = useRef()

    useEffect(() => {
        if(tableRef.current && !table) {
            const newTabulator = new Tabulator(tableRef.current, {
                columns: columns,
                data: employees,
                layout: 'fitColumns',
                maxHeight: '80vh',
                placeholder: 'No employees fit the selected criteria for generating a manifest',
                selectable: true,
                selectableCheck: row => {
                const selectable = row.getData().valid_bill_count > 0
                    return selectable
                }
            })

            newTabulator.on('rowDeselected', row => {
                row.update({isSelected: false})
            })

            newTabulator.on('rowSelected', row => {
                row.update({isSelected: true})
            })

            setTable(newTabulator)
        }
    })

    useEffect(() => {
        if(table) {
            table.setData(employees).then(() => {
                table.getRows().map(row => {
                    const data = row.getData()
                    if(data.valid_bill_count > 0 && data.incomplete_bill_count === 0 && data.legacy_bill_count === 0) {
                        row.select()
                        row.update({isSelected: true})
                    }
                })
            })
        }
    }, [employees])

    useEffect(() => {
        refreshEmployees()
    }, [startDate, endDate])

    const refreshEmployees = () => {
        setIsLoading(true)
        const queryString = new URLSearchParams({
            start_date: startDate.toLocaleDateString(),
            end_date: endDate.toLocaleDateString()
        }).toString()

        api.get(`/manifests/getDriversToManifest?${queryString}`)
            .then(result => {
                setEmployees(result)
                setIsLoading(false)
            })
    }

    const store = () => {
        if(isStoring)
            return
        else
            setIsStoring(true)

            if(!table || table.getSelectedData().length === 0) {
            toast.error('Please select at least one driver to manifest')
            return
        }

        const data = {
            employees: table.getSelectedData().map(employee => {return employee.employee_id}),
            start_date: startDate.toLocaleDateString(),
            end_date: endDate.toLocaleDateString()
        }
        api.post('/manifests/store', data)
            .then(response => {
                toast.success('Successfully generated manifests', {
                    position: 'top-center',
                    onClose: () => history.push('/manifests'),
                })
            }).catch(error => {
                setIsLoading(false)
            })
    }

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
                <div ref={tableRef}></div>
            </Card.Footer>
        </Card>
    )
}

