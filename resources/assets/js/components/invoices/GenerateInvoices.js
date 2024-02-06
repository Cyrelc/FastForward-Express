import React, {useEffect, useRef, useState} from 'react'
import {Button, Card, Col, InputGroup, Row} from 'react-bootstrap'
import Select from 'react-select'
import DatePicker from 'react-datepicker'
import {TabulatorFull as Tabulator} from 'tabulator-tables'

export default function GenerateInvoices(props) {
    const [invoiceIntervals, setInvoiceIntervals] = useState([])
    const [startDate, setStartDate] = useState()
    const [endDate, setEndDate] = useState()
    const [pendingCreation, setPendingCreation] = useState([])
    const [selectedInvoiceIntervals, setSelectedInvoiceIntervals] = useState([])
    const [table, setTable] = useState(null)

    const tableRef = useRef(null)

    const columns = [
        {
            title: 'Selected',
            field: 'isSelected',
            formatter: 'tickCross',
            hozAlign: 'center',
            headerHozAlign: 'center',
            headerSort: false,
            print: false,
            width: 100,
            headerClick: (event, col) => {
                const table = col.getTable()
                if(table.getSelectedData().length > 0) {
                    const rows = table.getSelectedRows()
                    rows.forEach(row => {
                        row.deselect()
                    })
                } else {
                    const rows = table.getRows()
                    rows.forEach(row => {
                        if(table.options.selectableCheck(row)) {
                            row.select()
                        }
                    })
                }
            }
        },
        {title: 'Valid', formatter: isValidForInvoicing},
        {title: 'ID', field: 'id', sorter: 'number'},
        {title: 'Number', field: 'number'},
        {title: 'Account Name', field: 'name'},
        {title: 'Bill Pickup Date', field: 'time_pickup_scheduled'},
        {
            title: 'Completed Bills',
            field: 'valid_bill_count',
            formatter: (cell) => {
                if(cell.getValue() === 0)
                    cell.getElement().style.backgroundColor = 'lightCoral'
                return cell.getValue()
            },
            sorter: 'number'
        },
        {
            title: 'Incomplete Bills',
            field: 'incomplete_bill_count',
            formatter: 'link',
            formatterParams: {url: cell => {
                const {id, type} = cell.getRow().getData()
                if(type == 'account')
                    return `/app/bills?filter[charge_account_id]=${id}&filter[percentage_complete]=,100`
                return `/app/bills/${id}`
            }},
            sorter: 'number'
        },
        {
            title: 'Skipped Bills',
            field: 'skipped_bill_count',
            formatter: 'link',
            formatterParams: {url: cell => {
                const {id, type} = cell.getRow().getData()
                if(type == 'account')
                    return `/app/bills?filter[charge_account_id]=${id}&filter[skip_invoicing]=1`
                return `/app/bills/${id}`
            }},
            sorter: 'number'
        },
        {
            title: 'Legacy Bills',
            field: 'legacy_bill_count',
            formatter: 'link',
            formatterParams: {url: cell => {
                const {id, type} = cell.getRow().getData()
                if(type == 'account')
                    return `/app/bills?filter[charge_account_id]=${id}&filter[time_pickup_scheduled]=,${startDate.toISOString().split('T')[0]}&filter[is_invoiced]=false`
                return `/app/bills/${id}`
            }},
            sorter: 'number'
        },
        {title: 'Parent Account', field: 'parent_account', visible: false},
        {title: 'group', field: 'group', visible: false}
    ]

    useEffect(() => {
        document.title = 'Generate Invoices - Fast Forward Express'
        makeAjaxRequest('/invoices/getModel', 'GET', null, response => {
            response = JSON.parse(response)
            setInvoiceIntervals(response.invoice_intervals)
            setStartDate(Date.parse(response.start_date))
            setEndDate(Date.parse(response.end_date))
        })
    }, [])

    useEffect(() => {
        if(tableRef.current && !table) {
            const newTabulator = new Tabulator(tableRef.current, {
                placeholder: 'No accounts or charges fit the selected criteria for invoicing',
                columns: columns,
                data: pendingCreation,
                initialSort: [{column: 'number', dir: 'asc'}],
                groupBy: 'parent_account',
                layout: 'fitColumns',
                maxHeight: '65vh',
                selectable: true,
                selectableCheck: row => {
                    const selectable = row.getData().valid_bill_count > 0
                    return selectable
                },
            })

            newTabulator.on('rowDeselected', row => {
                row.update({isSelected: false})
            })

            newTabulator.on('rowSelected', row => {
                row.update({isSelected: true})
            })

            setTable(newTabulator)
        }
    }, [tableRef.current])

    // Handle table data changes
    useEffect(() => {
        if(table)
            table.setData(pendingCreation).then(() => {
                table.getRows().map(row => {
                    const data = row.getData()
                    if(data.valid_bill_count > 0 && data.incomplete_bill_count === 0 && data.skipped_bill_count === 0 && data.legacy_bill_count === 0) {
                        row.select()
                        row.update({isSelected: true})
                    }
                })
            })
    }, [pendingCreation])

    useEffect(() => {
        if(selectedInvoiceIntervals === null || selectedInvoiceIntervals.length === 0 || !startDate || !endDate) {
            setPendingCreation([])
            return
        }

        const data = {
            invoice_intervals: selectedInvoiceIntervals.filter(interval => interval.type == 'invoice_interval').map(interval => {return interval.value}),
            prepaid_types: selectedInvoiceIntervals.filter(interval => interval.type == 'prepaid_type').map(interval => {return interval.value}),
            start_date: startDate.toLocaleString('en-US'),
            end_date: endDate.toLocaleString('en-US')
        }

        makeAjaxRequest('/invoices/getUninvoiced', 'POST', data, response => {
            response = JSON.parse(response)
            toastr.clear()
            if(response.pending_creation)
                setPendingCreation(Object.values(response.pending_creation))
        })
    }, [startDate, endDate, selectedInvoiceIntervals])

    const selectAll = () => {
        let selected = []
        invoiceIntervals.forEach(group => {
            group.options.forEach(option => selected.push(option))
        })
        setSelectedInvoiceIntervals(selected)
    }

    const store = () => {
        if(table?.getSelectedData().length === 0) {
            toastr.error('Please select at least one invoice to be created')
            return
        }

        const selected = table.getSelectedData()

        const data = {
            accounts: selected.filter(row => row.type == 'account').map(account => account.id),
            prepaid: selected.filter(row => row.type == 'prepaid').map(bill => bill.charge_id),
            start_date: startDate.toLocaleString('en-US'),
            end_date: endDate.toLocaleString('en-US')
        }

        makeAjaxRequest('/invoices', 'POST', data, response => {
            toastr.clear()
            toastr.success('Successfully generated invoices', 'Success', {
                'progressBar' : true,
                'showDuration': 500,
                'onHidden': window.location = '/app/invoices',
                'positionClass': 'toast-top-center'
            })
        })
    }

    function isValidForInvoicing(cell) {
        const data = cell.getData()
        if(data.valid_bill_count === 0) {
            cell.getElement().style.backgroundColor = 'lightCoral'
            return 'False - No valid bills'
        } else if(data.incomplete_bill_count > 0 || data.skipped_bill_count > 0 || data.legacy_bill_count > 0) {
            cell.getElement().style.backgroundColor = 'gold'
            const incompleteBills = data.incomplete_bill_count > 0 ? ` - Incomplete Bills (${data.incomplete_bill_count})` : ''
            const skippedBills = data.skipped_bill_count > 0 ? ` - Skipped Bills (${data.skipped_bill_count})` : ''
            const legacyBills = data.legacy_bill_count > 0 ? ` - Legacy Bills (${data.legacy_bill_count})` : ''
            return 'Warning' + incompleteBills + skippedBills + legacyBills
        } else {
            cell.getElement().style.backgroundColor = 'mediumseagreen'
            return 'True'
        }
    }

    return (
        <Card>
            <Card.Header>
                <Row className='justify-content-md-center'>
                    <h3>Generate Invoices</h3>
                </Row>
            </Card.Header>
            <Card.Body>
                <Row>
                    <Col md={5}>
                        <InputGroup>
                            <InputGroup.Text>Invoice Interval</InputGroup.Text>
                            <Select
                                options={invoiceIntervals}
                                value={selectedInvoiceIntervals}
                                onChange={setSelectedInvoiceIntervals}
                                isMulti
                            />
                            <Button variant='primary' onClick={selectAll}>Select All</Button>
                        </InputGroup>
                    </Col>
                    <Col md={5}>
                        <InputGroup>
                            <InputGroup.Text>Start Date: </InputGroup.Text>
                            <DatePicker
                                className='form-control'
                                dateFormat='MMMM d, yyyy'
                                placeholderText='After'
                                selected={startDate}
                                onChange={setStartDate}
                                selectsStart
                                endDate={endDate}
                                wrapperClassName='form-control'
                            />
                            <InputGroup.Text> End Date: </InputGroup.Text>
                            <DatePicker
                                className='form-control'
                                dateFormat='MMMM d, yyyy'
                                placeholderText='Before'
                                selected={endDate}
                                onChange={setEndDate}
                                selectsEnd
                                startDate={startDate}
                                minDate={startDate}
                                wrapperClassName='form-control'
                            />
                        </InputGroup>
                    </Col>
                    <Col md={2}>
                        <Button variant='primary' disabled={pendingCreation.length === 0} onClick={store}>Generate Invoices</Button>
                    </Col>
                </Row>
            </Card.Body>
            <Card.Footer>
                <p>The following accounts and/or charges fit the selected criteria and have yet to be invoiced:</p>
                <div ref={tableRef}></div>
            </Card.Footer>
        </Card>
    )
}

