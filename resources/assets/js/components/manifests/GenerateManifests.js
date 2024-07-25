import React, {useEffect, useRef, useState} from 'react'
import {Button, Card, Col, InputGroup, Row} from 'react-bootstrap'
import DatePicker from 'react-datepicker'
import {useHistory} from 'react-router-dom'
import {toast} from 'react-toastify'
import {DateTime} from 'luxon'
import {MaterialReactTable, useMaterialReactTable} from 'material-react-table'

import {useAPI} from '../../contexts/APIContext'

const columns = [
    {header: 'Employee ID', accessorKey: 'employee_id'},
    {header: 'Employee Number', accessorKey: 'employee_number'},
    {header: 'Employee', accessorKey: 'label'},
    {header: 'Valid Bills', accessorKey: 'valid_bill_count'},
    {header: 'Legacy Bills', accessorKey: 'legacy_bill_count'},
    {header: 'Incomplete Bills', accessorKey: 'incomplete_bill_count'}
]

export default function GenerateManifests(props) {
    const [employees, setEmployees] = useState([])
    const [startDate, setStartDate] = useState(DateTime.now().minus({months: 1}).startOf('month').toJSDate())
    const [endDate, setEndDate] = useState(DateTime.now().minus({months: 1}).endOf('month').toJSDate())
    const [isLoading, setIsLoading] = useState(true)
    const [isStoring, setIsStoring] = useState(false)
    const [rowSelection, setRowSelection] = useState({})

    const api = useAPI();
    const history = useHistory()

    const table = useMaterialReactTable({
        columns,
        data: employees,
        initialState: {
            denisity: 'compact'
        },
        enableRowSelection: row => row.original.valid_bill_count > 0,
        enableStickyHeader: true,
        getRowId: row => row.employee_id,
        enablePagination: false,
        onRowSelectionChange: setRowSelection,
        state: {rowSelection}
    })

    useEffect(() => {
        const selectedRows = employees.filter(row => {
            return row.valid_bill_count > 0 && row.incomplete_bill_count === 0 && row.legacy_bill_count === 0
        }).reduce((acc, row) => {
            acc[row.employee_id.toString()] = true
            return acc
        }, {})

        setRowSelection(selectedRows)
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
        else if(Object.keys(rowSelection).length < 1) {
            toast.error('Please select at least one driver to manifest')
            return
        }

        setIsStoring(true)

        const data = {
            employees: Object.keys(rowSelection),
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
                <MaterialReactTable table={table} />
            </Card.Footer>
        </Card>
    )
}

