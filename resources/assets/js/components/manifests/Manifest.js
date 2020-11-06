import React, {Component} from 'react'
import {Button, ButtonGroup, Card, Col, Row, Table} from 'react-bootstrap'
import {LinkContainer} from 'react-router-bootstrap'

const headerTDStyle = {width: '20%', textAlign: 'center', border: 'grey solid', whiteSpace: 'pre', paddingTop: '10px', paddingBottom: '10px'}

export default class Manifest extends Component {
    constructor() {
        super()
        this.state = {
            data: null,
            curBillDate: null
        }
        this.getManifest = this.getManifest.bind(this)
    }

    componentDidMount() {
        this.getManifest()
    }

    componentDidUpdate(prevProps) {
        if(prevProps.match.params.manifestId != this.props.match.params.manifestId)
            this.getManifest()
    }

    getManifest() {
        makeAjaxRequest('/manifests/getModel/' + this.props.match.params.manifestId, 'GET', null, response => {
            response = JSON.parse(response)
            document.title = 'View Manifest - ' + response.manifest.manifest_id
            this.setState({data: response})
        })
    }

    render() {
        return (
            (this.state.data &&
                <Row className='justify-content-md-center' style={{paddingTop: '20px'}}>
                    <Col md={2}>
                        <h3>Manifest {this.state.data.manifest.manifest_id}</h3>
                    </Col>
                    <Col md={9} style={{textAlign: 'right'}}>
                        <ButtonGroup>
                            <Button variant='primary' href={'/manifests/print/' + this.state.data.manifest.manifest_id} target='_blank'><i className='fas fa-print'></i> Print</Button>
                            <Button variant='success' href={'/manifests/print/' + this.state.data.manifest.manifest_id + '?without_bills'}><i className='fas fa-print'></i> Print Without Bills</Button>
                        </ButtonGroup>
                    </Col>
                    <Col md={11}>
                        <hr/>
                    </Col>
                    <Col md={11}>
                        <table style={{width: '100%'}}>
                            <tbody>
                                <tr>
                                    <th style={{...headerTDStyle, backgroundColor: '#ADD8E6'}}>{'Driver Gross\n$' + this.state.data.driver_total}</th>
                                    <th style={{...headerTDStyle, backgroundColor: 'orange'}}>{'Chargebacks\n$' + this.state.data.chargeback_total}</th>
                                    <th style={{...headerTDStyle, backgroundColor: '#ADD8E6'}}>{'Driver Income\n$' + this.state.data.driver_income}</th>
                                    <th style={{width: '40%', textAlign: 'center'}}>
                                        <LinkContainer to={'/app/employees/edit/' + this.state.data.employee.employee_id}>
                                            <h3><a href=''>{this.state.data.employee.employee_number + ' - ' + (this.state.data.employee.company_name === null ? this.state.data.employee.contact.first_name + ' ' + this.state.data.employee.contact.last_name : this.state.data.employee.company_name)}</a></h3>
                                        </LinkContainer>
                                    </th>
                                </tr>
                            </tbody>
                        </table>
                    </Col>
                    <Col md={11}>
                        <hr/>
                    </Col>
                    {
                        this.state.data.warnings &&
                        <Col md={11}>
                            <table style={{width: '100%'}}>
                                <thead>
                                    <tr>
                                        {this.state.data.warnings.map(warning =>
                                            <th style={{border: '1px solid black', textAlign: 'center', background: warning.type === 'error' ? 'tomato' : 'gold'}} width={(1 / this.state.data.warnings.length * 100) + '%'}>{warning.friendlyString}</th>
                                        )}
                                    </tr>
                                </thead>
                            </table>
                        </Col>
                    }
                    <Col md={11} style={{textAlign: 'center'}}>
                        <h4>{this.state.data.manifest.start_date + ' to ' + this.state.data.manifest.end_date}</h4>
                        <h4>Driver Statement</h4>
                        <Table striped size='sm'>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Pickups</th>
                                    <th>Deliveries</th>
                                    <th>Pickup Income</th>
                                    <th>Delivery Income</th>
                                    <th>Driver Income</th>
                                </tr>
                            </thead>
                            <tbody>
                                {this.state.data.overview.map(day =>
                                    <tr key={day.time_pickup_scheduled}>
                                        <td>{day.time_pickup_scheduled}</td>
                                        <td>{day.pickup_count}</td>
                                        <td>{day.delivery_count}</td>
                                        <td>{'$' + day.pickup_amount.toFixed(2)}</td>
                                        <td>{'$' + day.delivery_amount.toFixed(2)}</td>
                                        <td>{'$' + (day.pickup_amount + day.delivery_amount).toFixed(2)}</td>
                                    </tr>
                                )}
                            </tbody>
                        </Table>
                        <h4>Chargebacks</h4>
                        <Table striped size='sm'>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>GL Code</th>
                                    <th>Description</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                {this.state.data.chargebacks.map(chargeback =>
                                    <tr key={chargeback.chargeback_id}>
                                        <td>{chargeback.name}</td>
                                        <td>{chargeback.gl_code}</td>
                                        <td>{chargeback.description}</td>
                                        <td>{'$' + chargeback.amount}</td>
                                    </tr>
                                )}
                            </tbody>
                        </Table>
                        <h4>Detailed</h4>
                        <Table striped size='sm'>
                            <thead>
                                <tr>
                                    <th>Bill ID</th>
                                    <th>Date</th>
                                    <th>Delivery Type</th>
                                    <th>Direction</th>
                                    <th>Bill Gross</th>
                                    <th>Driver Income</th>
                                </tr>
                            </thead>
                            <tbody>
                                {
                                    this.state.data.bills.map(bill =>
                                        <tr>
                                            <LinkContainer to={'/app/bills/view/' + bill.bill_id}><td><a href=''>{bill.bill_id}</a></td></LinkContainer>
                                            <td>{bill.time_pickup_scheduled}</td>
                                            <td>{bill.delivery_type}</td>
                                            <td>{bill.type}</td>
                                            <td>{'$' + parseFloat(bill.amount).toFixed(2)}</td>
                                            <td>{'$' + bill.driver_income.toFixed(2)}</td>
                                        </tr>
                                    )
                                }
                            </tbody>
                        </Table>
                    </Col>
                </Row>
            )
        )
    }
}
