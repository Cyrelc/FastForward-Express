import React, {useEffect, useState, useRef} from 'react'
import {Button, Card, Col, InputGroup, Row, Table} from 'react-bootstrap'
import DatePicker from 'react-datepicker'
import {ReactTabulator} from 'react-tabulator'

const moneyColumnStandardParams = {
    formatter: 'money',
    formatterParams: {thousand: ',', symbol: '$', selectContents: true},
    hozAlign: 'right',
    topCalc: 'sum',
    topCalcParams: {precision: 2},
    topCalcFormatter: 'money',
    topCalcFormatterParams: {thousand: ',', symbol: '$'},
    sorter: 'number',
}

const columns = [
    {title: 'Bill ID', field: 'bill_id'},
    {title: 'Date', field: 'time_pickup_scheduled'},
    {title: 'Amount', field: 'amount', ...moneyColumnStandardParams},
]

export default function AccountsPayable(props) {
    const [accountsPayable, setAccountsPayable] = useState([])
    const [startDate, setStartDate] = useState(new Date())
    const [endDate, setEndDate] = useState(new Date())

    useEffect(() => {
        getAccountsPayable()
    }, [])

    useEffect(() => {
        getAccountsPayable()
    }, [startDate, endDate])

    const getAccountsPayable = () => {
        makeAjaxRequest(`/admin/getAccountsPayable?start_date=${encodeURIComponent(startDate.toISOString())}&end_date=${encodeURIComponent(endDate.toISOString())}`, 'GET', null, response => {
            response = JSON.parse(response)
            setAccountsPayable(response.accounts_payable)
        })
    }

    const tableRef = useRef(null)

    return (
        <Row className='justify-content-md-center'>
            <Col md={12} className='justify-content-center'>
                <Card border='dark'>
                    <Card.Header>
                        <Row>
                            <Col md={2}>
                                <Card.Title>Accounts Payable</Card.Title>
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
                            {accountsPayable?.length ?
                                <Col md={2}>
                                    <Button variant='success' onClick={() => tableRef.current.table.print()}>
                                        <i className='fas fa-print'></i> Print
                                    </Button>
                                </Col> : null
                            }
                        </Row>
                    </Card.Header>
                    <Card.Body>
                        <ReactTabulator
                            ref={tableRef}
                            columns={columns}
                            data={accountsPayable}
                            options={{
                                columnCalcs: 'both',
                                groupBy: 'type',
                                printStyled: true,
                                printHeader: `<h3>${startDate.toLocaleDateString(undefined, {month: 'short', year: 'numeric'})} to ${endDate.toLocaleDateString(undefined, {month: 'short', year: 'numeric'})}</h3>`
                            }}
                        />
                    </Card.Body>
                </Card>
            </Col>
        </Row>
    )
}
