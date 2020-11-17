import React, {Component} from 'react'
import {Button, Card, Col, InputGroup, Row, Table} from 'react-bootstrap'
import DatePicker from 'react-datepicker'

export default class AccountsReceivable extends Component {
    constructor() {
        super()
        this.state = {
            accountsReceivable: [],
            prepaidAccountsReceivable: [],
            startDate: new Date(),
            endDate: new Date()
        }
        this.getAccountsReceivable = this.getAccountsReceivable.bind(this)
        this.handleChange = this.handleChange.bind(this)
    }

    componentDidMount() {
        this.getAccountsReceivable()
    }

    handleChange(event) {
        const {name, type, value, checked} = event.target
        this.setState({[name]: type === 'checkbox' ? checked : value})
    }

    getAccountsReceivable() {
        makeAjaxRequest('/admin/getAccountsReceivable/' + this.state.startDate.toISOString() + '/' + this.state.endDate.toISOString(), 'GET', null, response => {
            response = JSON.parse(response)
            this.setState({
                accountsReceivable: response.accounts_receivable,
                prepaidAccountsReceivable: response.prepaid_accounts_receivable
            })
        })
    }

    render() {
        return (
            <Row className='justify-content-md-center'>
                <Col md={11} className='justify-content-center'>
                    <Card border='dark'>
                        <Card.Header>
                            <Row>
                                <Col md={2}>
                                    <Card.Title>Accounts Receivable</Card.Title>
                                </Col>
                                <Col md={3}>
                                    <InputGroup>
                                        <InputGroup.Prepend>
                                            <InputGroup.Text>Start Month</InputGroup.Text>
                                        </InputGroup.Prepend>
                                        <DatePicker
                                            className='form-control'
                                            dateFormat='MMMM, yyyy'
                                            selected={this.state.startDate}
                                            showMonthYearPicker
                                            onChange={value => this.handleChange({target: {name: 'startDate', type: 'date', value: value}})}
                                        />
                                    </InputGroup>
                                </Col>
                                <Col md={3}>
                                    <InputGroup>
                                        <InputGroup.Prepend>
                                            <InputGroup.Text>End Month</InputGroup.Text>
                                        </InputGroup.Prepend>
                                        <DatePicker
                                            className='form-control'
                                            dateFormat='MMMM, yyyy'
                                            selected={this.state.endDate}
                                            showMonthYearPicker
                                            onChange={value => this.handleChange({target: {name: 'endDate', type: 'date', value: value}})}
                                        />
                                    </InputGroup>
                                </Col>
                                <Col md={2}>
                                    <Button variant='success' onClick={this.getAccountsReceivable}>Refresh</Button>
                                </Col>
                            </Row>
                        </Card.Header>
                        <Card.Body>
                            <Table bordered striped size='sm'>
                                <thead>
                                    <tr>
                                        <th>Account</th>
                                        <th>Account Number</th>
                                        <th>Total</th>
                                        <th>Balance Owing</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {this.state.accountsReceivable.map(account =>
                                        <tr key={account.account_number}>
                                            <td>{account.name}</td>
                                            <td>{account.account_number}</td>
                                            <td style={{textAlign: 'right'}}>{account.total_cost.toLocaleString('en-US', {style: 'currency', currency: 'USD'})}</td>
                                            <td style={{textAlign: 'right'}}>{account.balance_owing.toLocaleString('en-US', {style: 'currency', currency: 'USD'})}</td>
                                        </tr>
                                    )}
                                </tbody>
                                <tbody>
                                    {this.state.prepaidAccountsReceivable.map(paymentType =>
                                        <tr key={paymentType.payment_type_name}>
                                            <td>{paymentType.payment_type_name}</td>
                                            <td></td>
                                            <td style={{textAlign: 'right'}}>{paymentType.amount.toLocaleString('en-US', {style: 'currency', currency: 'USD'})}</td>
                                            <td></td>
                                        </tr>
                                    )}
                                </tbody>
                            </Table>
                        </Card.Body>
                    </Card>
                </Col>
            </Row>
        )
    }
}
