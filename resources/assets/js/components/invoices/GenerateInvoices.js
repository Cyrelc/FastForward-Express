import React, {useEffect, useMemo, useState} from 'react'
import {Button, Card, Col, InputGroup, Row} from 'react-bootstrap'
import Select from 'react-select'
import DatePicker from 'react-datepicker'
import {useHistory} from 'react-router-dom'
import {toast} from 'react-toastify'
import {DateTime} from 'luxon'
import {MaterialReactTable, useMaterialReactTable} from 'material-react-table'

import {useAPI} from '../../contexts/APIContext'
import {useLists} from '../../contexts/ListsContext'
import { LinkCellRenderer } from '../../utils/table_cell_renderers'

const ValidForInvoicingRenderer = ({row}) => {
    const data = row.original

    if(data.valid_bill_count == 0) {
        return (
            <div style={{backgroundColor: 'lightCoral', textAlign: 'center'}}>
                <i className='fas fa-times-circle'></i> No valid bills
            </div>
        )
    } else if(data.incomplete_bill_count > 0 || data.skipped_bill_count > 0 || data.legacy_bill_count > 0) {
        const incompleteBills = data.incomplete_bill_count > 0 ? `Incomplete Bills (${data.incomplete_bill_count})` : ''
        const skippedBills = data.skipped_bill_count > 0 ? ` - Skipped Bills (${data.skipped_bill_count})` : ''
        const legacyBills = data.legacy_bill_count > 0 ? ` - Legacy Bills (${data.legacy_bill_count})` : ''
        return (
            <div style={{backgroundColor: 'gold'}}>
                Warning
                {incompleteBills ?? null}
                {skippedBills ?? null}
                {legacyBills ?? null}
            </div>
        )
    }

    return <div style={{backgroundColor: 'mediumseagreen'}}>True</div>
}

export default function GenerateInvoices(props) {
    const [invoiceIntervals, setInvoiceIntervals] = useState([])
    const [startDate, setStartDate] = useState()
    const [endDate, setEndDate] = useState()
    const [pendingCreation, setPendingCreation] = useState([])
    const [rowSelection, setRowSelection] = useState({})
    const [selectedInvoiceIntervals, setSelectedInvoiceIntervals] = useState([])

    const api = useAPI()
    const lists = useLists()
    const history = useHistory()

    useEffect(() => {
        document.title = 'Generate Invoices - Fast Forward Express'
        const startDate = DateTime.now().minus({months: 1})
        setStartDate(startDate.startOf('month').toJSDate())
        setEndDate(startDate.endOf('month').toJSDate())
        let options = [
            {label: 'Invoice Intervals', options: lists.invoiceIntervals.map(interval => {return {...interval, type: 'invoice_interval'}})},
            {label: 'Prepaid', options: lists.paymentTypes.filter(paymentType => paymentType.type == 'prepaid').map(paymentType => {return {...paymentType, type: 'prepaid_type'}})}
        ]
        setInvoiceIntervals(options)
    }, [])

    const columns = useMemo(() => [
        {header: 'Valid', Cell: ValidForInvoicingRenderer},
        {header: 'ID', accessorKey: 'id', sorter: 'number'},
        {header: 'Number', accessorKey: 'number'},
        {header: 'Account Name', accessorKey: 'name'},
        {header: 'Bill Pickup Date', accessorKey: 'time_pickup_scheduled'},
        {
            header: 'Complete',
            accessorKey: 'valid_bill_count',
            size: 110,
        },
        {
            header: 'Incomplete',
            accessorKey: 'incomplete_bill_count',
            size: 110,
            Cell: ({renderedCellValue, row}) => {
                const data = row.original
                if(data.incomplete_bill_count == 0)
                    return renderedCellValue
                const url = data.type == 'account' ? `/bills?filter[charge_account_id]=${data.id}&filter[percentage_complete]=,100` : `/bills/${data.id}`
                return <LinkCellRenderer renderedCellValue={renderedCellValue} row={row} url={url} />
            }
        },
        {
            header: 'Skipped',
            accessorKey: 'skipped_bill_count',
            size: 110,
            Cell: ({renderedCellValue, row}) => {
                const data = row.original
                if(data.skipped_bill_count == 0)
                    return renderedCellValue
                const url = data.type == 'account' ? `/bills?filter[charge_account_id]=${data.id}&filter[skip_invoicing]=1` : `/bills/${data.id}`
                return <LinkCellRenderer renderedCellValue={renderedCellValue} row={row} url={url} />
            }
        },
        {
            header: `Legacy`,
            accessorKey: 'legacy_bill_count',
            size: 110,
            Cell: ({renderedCellValue, row}) => {
                const data = row.original
                if(data.legacy_bill_count == 0)
                    return renderedCellValue
                const url = data.type == 'account' ?
                    `/bills?filter[charge_account_id]=${data.id}&filter[time_pickup_scheduled]=,${startDate.toISOString().split('T')[0]}&filter[is_invoiced]=false`
                    :
                    `/bills/${data.id}`
                return <LinkCellRenderer renderedCellValue={renderedCellValue} row={row} url={url} />
            }
        },
        // {header: 'Parent Account', accessorKey: 'parent_account', visible: false},
        // {header: 'group', accessorKey: 'group', visible: false}
    ], [])

    const table = useMaterialReactTable({
        columns,
        data: pendingCreation,
        initialState: {
            density: 'compact'
        },
        enableRowSelection: row => row.original.valid_bill_count > 0,
        enableStickyHeader: true,
        getRowId: row => row.id,
        enablePagination: false,
        onRowSelectionChange: setRowSelection,
        state: {rowSelection},
    })

    useEffect(() => {
        if(!selectedInvoiceIntervals || selectedInvoiceIntervals.length === 0 || !startDate || !endDate) {
            setPendingCreation([])
            return
        }

        const data = {
            invoice_intervals: selectedInvoiceIntervals.filter(interval => interval.type == 'invoice_interval').map(interval => {return interval.value}),
            prepaid_types: selectedInvoiceIntervals.filter(interval => interval.type == 'prepaid_type').map(interval => {return interval.value}),
            start_date: startDate.toLocaleString('en-US'),
            end_date: endDate.toLocaleString('en-US')
        }

        api.post('/invoices/getUninvoiced', data).then(response => {
            if(response.pending_creation) {
                setPendingCreation(Object.values(response.pending_creation))
            } else {
                setPendingCreation([])
            }
        })
    }, [startDate, endDate, selectedInvoiceIntervals])

    useEffect(() => {
        const selectedRows = pendingCreation.filter(row => {
            return row.valid_bill_count > 0 && row.incomplete_bill_count === 0 && row.skipped_bill_count === 0 && row.legacy_bill_count === 0
        })
        .reduce((acc, row) => {
            acc[row.id.toString()] = true
            return acc
        }, {})

        setRowSelection(selectedRows)
    }, [pendingCreation])

    const selectAll = () => {
        let selected = []
        invoiceIntervals.forEach(group => {
            group.options.forEach(option => selected.push(option))
        })
        setSelectedInvoiceIntervals(selected)
    }

    const store = () => {
        if(Object.keys(rowSelection).length < 1) {
            toast.error('Please select at least one invoice to be created')
            return
        }

        const selected = pendingCreation.filter(pendingInvoice => {
            return pendingInvoice.id in rowSelection
        })

        const data = {
            accounts: selected.filter(row => row.type == 'account').map(account => account.id),
            prepaid: selected.filter(row => row.type == 'prepaid').map(bill => bill.charge_id),
            start_date: startDate.toLocaleString('en-US'),
            end_date: endDate.toLocaleString('en-US')
        }

        api.post('/invoices', data).then(response => {
            toast.success('Successfully generated invoices', 'Success', {
                'progressBar' : true,
                'showDuration': 500,
                'onHidden': history.push('/invoices'),
                'positionClass': 'toast-top-center'
            })
        })
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
                                classNamePrefix='react-select'
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
                <MaterialReactTable table={table} />
            </Card.Footer>
        </Card>
    )
}

