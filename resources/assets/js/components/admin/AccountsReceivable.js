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
    {title: 'Account', field: 'name'},
    {title: 'Account #', field: 'account_number'},
    {title: 'Total', field: 'total_cost', ...moneyColumnStandardParams},
    {title: 'Balance Owing', field: 'balance_owing', ...moneyColumnStandardParams},
    {title: 'Type', field: 'type', visible: false}
]

export default function AccountsReceivable(props) {
    const [accountsReceivable, setAccountsReceivable] = useState([])
    const [startDate, setStartDate] = useState(new Date())
    const [endDate, setEndDate] = useState(new Date())

    useEffect(() => {
        getAccountsReceivable()
    }, [])

    useEffect(() => {
        getAccountsReceivable()
    }, [startDate, endDate])

    const getAccountsReceivable = () => {
        makeAjaxRequest(`/admin/getAccountsReceivable/${startDate.toISOString()}/${endDate.toISOString()}`, 'GET', null, response => {
            response = JSON.parse(response)
            setAccountsReceivable(response.accounts_receivable)
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
                                <Card.Title>Accounts Receivable</Card.Title>
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
                            {accountsReceivable?.length ?
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
                            data={accountsReceivable}
                            options={{
                                columnCalcs: 'both',
                                groupBy: 'type',
                                printStyled: true
                            }}
                        />
                    </Card.Body>
                </Card>
            </Col>
        </Row>
    )
}
