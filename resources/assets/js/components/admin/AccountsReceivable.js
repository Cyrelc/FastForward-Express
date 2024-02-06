import React, {useEffect, useState, useRef} from 'react'
import {Button, Card, Col, InputGroup, Row} from 'react-bootstrap'
import DatePicker from 'react-datepicker'
import {TabulatorFull as Tabulator} from 'tabulator-tables'

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
    const [table, setTable] = useState(null)

    const tabulatorRef = useRef(null)

    useEffect(() => {
        getAccountsReceivable()
    }, [])

    useEffect(() => {
        getAccountsReceivable()
    }, [startDate, endDate])

    useEffect(() => {
        if(tabulatorRef.current && !table) {
            const newTabulator = new Tabulator(tabulatorRef.current, {
                columns: columns,
                data: accountsReceivable,
                columnCalcs: 'both',
                groupBy: 'type',
                layout: 'fitColumns',
                printStyled: true,
                printHeader: `<h3>${startDate.toLocaleDateString(undefined, {month: 'short', year: 'numeric'})} to ${endDate.toLocaleDateString(undefined, {month: 'short', year: 'numeric'})}</h3>`
            })

            setTable(newTabulator)
        }
    }, [tabulatorRef.current])

    useEffect(() => {
        if(table)
            table.setData(accountsReceivable)
    }, [accountsReceivable])

    const getAccountsReceivable = () => {
        makeAjaxRequest(`/admin/getAccountsReceivable/${startDate.toISOString()}/${endDate.toISOString()}`, 'GET', null, response => {
            response = JSON.parse(response)
            setAccountsReceivable(response.accounts_receivable)
        })
    }

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
                                    <Button variant='success' onClick={() => table.print()}>
                                        <i className='fas fa-print'></i> Print
                                    </Button>
                                </Col> : null
                            }
                        </Row>
                    </Card.Header>
                    <Card.Body>
                        <div ref={tabulatorRef}></div>
                    </Card.Body>
                </Card>
            </Col>
        </Row>
    )
}
