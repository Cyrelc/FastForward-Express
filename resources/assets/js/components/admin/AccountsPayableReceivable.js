import React, {useEffect, useState, useMemo} from 'react'
import {Card, Col, InputGroup, Row} from 'react-bootstrap'
import DatePicker from 'react-datepicker'
import {Box, Button} from '@mui/material'
import {MaterialReactTable, useMaterialReactTable} from 'material-react-table'
import FileDownloadIcon from '@mui/icons-material/FileDownload'
import {jsPDF} from 'jspdf'
import autoTable from 'jspdf-autotable'

import {CurrencyCellRenderer} from '../../utils/table_cell_renderers'
import {useAPI} from '../../contexts/APIContext'


export default function AccountsPayableReceivable(props) {
    const {version} = props
    const [accounts, setAccounts] = useState([])
    const [startDate, setStartDate] = useState(new Date())
    const [endDate, setEndDate] = useState(new Date())
    const [sumTotalCost, setSumTotalCost] = useState(0)
    const [sumBalanceOwing, setSumBalanceOwing] = useState(0)

    useEffect(() => {
        let sumTotalCost = 0
        let sumBalanceOwing = 0

        accounts.forEach(row => {
            sumTotalCost += parseFloat(row.total_cost)
            if(!isNaN(row.balance_owing))
                sumBalanceOwing += parseFloat(row.balance_owing)
        })

        setSumTotalCost(sumTotalCost.toLocaleString('en-CA', {style: 'currency', currency: 'CAD'}))
        setSumBalanceOwing(sumBalanceOwing.toLocaleString('en-CA', {style: 'currency', currency: 'CAD'}))
    }, [accounts])

    const api = useAPI()

    useEffect(() => {
        getAccounts()
    }, [])

    useEffect(() => {
        getAccounts()
    }, [startDate, endDate])

    const columns = useMemo(() => [
        {header: 'Account', accessorKey: 'name'},
        {header: 'Account #', accessorKey: 'account_number'},
        {
            header: 'Total',
            accessorKey: 'total_cost',
            Cell: CurrencyCellRenderer,
            Header: () => <div>Total Cost: {sumTotalCost}</div>,
            muiTableHeadCellProps: {
                align: 'right'
            },
            muiTableBodyCellProps: {
                align: 'right'
            }
        },
        ...version === 'Receivable' ? [
            {
                header: 'Balance Owing',
                accessorKey: 'balance_owing',
                Cell: CurrencyCellRenderer,
                Header: () => <div>Balance Owing: {sumBalanceOwing}</div>,
                muiTableHeadCellProps: { align: 'right' },
                muiTableBodyCellProps: { align: 'right' }
            }
        ] : [],
    ], [version, sumTotalCost, sumBalanceOwing])

    const handleExportRows = (rows) => {
        const doc = new jsPDF('p', 'pt');
        const tableColumns = columns.map((c) => ({header: c.header, dataKey: c.accessorKey}));
        const tableRows = rows.map((row) => ({
            ...row.original,
            total_cost: new Intl.NumberFormat('en-US', {style: 'currency', currency: 'USD'}).format(row.original.total_cost),
            ...version === 'Receivable' ? {
                balance_owing: new Intl.NumberFormat('en-US', {style: 'currency', currency: 'USD'}).format(row.original.balance_owing)
            } : {}
        }))

        const header = `Accounts ${version}: ${startDate.toLocaleString('default', {month: 'long', year: 'numeric'})} - ${endDate.toLocaleString('default', {month: 'long', year: 'numeric'})}`;

        doc.autoTable({
            columns: tableColumns,
            body: tableRows,
            columnStyles: {
                total_cost: {
                    halign: 'right'
                },
                balance_owing: {
                    halign: 'right'
                }
            },
            didDrawPage: (data) => {
                // Header
                doc.setFontSize(12);
                doc.text(header, data.settings.margin.left, 40);
            },
            margin: { top: 60 }, // Adjust the top margin to accommodate the header
        });

        const name = `Accounts_${version}_${startDate.toLocaleDateString().replace(/\//g, '-')}_${endDate.toLocaleDateString().replace(/\//g, '-')}.pdf`;

        // Generate Blob from PDF
        const pdfBlob = doc.output('blob');
        const pdfUrl = URL.createObjectURL(pdfBlob);

        // Open the Blob URL in a new tab
        const newWindow = window.open(pdfUrl);
    };

    const table = useMaterialReactTable({
        columns,
        data: accounts,
        enableBottomToolbar: false,
        enablePagination: false,
        initialState: {density: 'compact',},
        renderTopToolbarCustomActions: ({ table }) => (
            <Box
                sx={{
                    display: 'flex',
                    gap: '16px',
                    padding: '8px',
                    flexWrap: 'wrap',
                }}
            >
                <Button
                    disabled={table.getPrePaginationRowModel().rows.length === 0}
                    //export all rows, including from the next page, (still respects filtering and sorting)
                    onClick={() =>
                        handleExportRows(table.getPrePaginationRowModel().rows)
                    }
                    startIcon={<FileDownloadIcon />}
                >
                    Export All Rows
              </Button>
            </Box>
        ),
    })

    const getAccounts = () => {
        api.get(`/admin/getAccounts${version}/${startDate.toISOString()}/${endDate.toISOString()}`)
            .then(response => {
                setAccounts(response?.accounts_receivable ?? response.accounts_payable)
            })
    }

    return (
        <Row className='justify-content-md-center'>
            <Col md={12} className='justify-content-center'>
                <Card border='dark'>
                    <Card.Header>
                        <Row>
                            <Col md={2}>
                                <Card.Title>Accounts {version}</Card.Title>
                            </Col>
                            <Col md={3}>
                                <InputGroup>
                                    <InputGroup.Text>Start Month</InputGroup.Text>
                                    <DatePicker
                                        className='form-control'
                                        dateFormat='MMMM, yyyy'
                                        selected={startDate}
                                        showMonthYearPicker
                                        onChange={setStartDate}
                                        wrapperClassName='form-control'
                                    />
                                </InputGroup>
                            </Col>
                            <Col md={3}>
                                <InputGroup>
                                    <InputGroup.Text>End Month</InputGroup.Text>
                                    <DatePicker
                                        className='form-control'
                                        dateFormat='MMMM, yyyy'
                                        selected={endDate}
                                        showMonthYearPicker
                                        onChange={setEndDate}
                                        wrapperClassName='form-control'
                                    />
                                </InputGroup>
                            </Col>
                        </Row>
                    </Card.Header>
                    <Card.Body>
                        <MaterialReactTable table={table} />
                    </Card.Body>
                </Card>
            </Col>
        </Row>
    )
}
