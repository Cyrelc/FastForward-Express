import React, {Component} from 'react'
import {Tabs, Tab, Nav, Row, Col, ListGroup, Button} from 'react-bootstrap'

import AccountingTab from './AccountingTab'
import InterlinersTab from './InterlinersTab'
import RatesheetsTab from './RatesheetsTab'
// import MiscTab from './MiscTab'
// import SchedulingTab from './SchedulingTab'

export default class AppSettings extends Component {
    constructor() {
        super()
        this.state = {
            //todo: Business hours open/closed, but will require application restart like GST does for editing
            gst: '',
            interliners: [],
            paymentTypes: [],
            ratesheets: [],
            blockedDate: new Date(),
            blockedDates: [],
            blockedDateReason: ''
        }
        this.handleChange = this.handleChange.bind(this)
        this.store = this.store.bind(this)
    }

    componentDidMount() {
        document.title = 'Application Settings - ' + document.title
        makeAjaxRequest('/appsettings/get', 'GET', null, response => {
            response = JSON.parse(response)
            this.setState({
                gst: response.gst,
                interliners: response.interliners,
                paymentTypes: response.payment_types,
                ratesheets: response.ratesheets,
            })
        })
    }

    handleChange(event) {
        const {name, value, type, checked} = event.target
        var temp = {}
        switch(name) {
            case 'default_ratesheet_id':
                temp = this.handleDefaultRatesheetChange(event)
                break;
            default:
                temp[name] = type === 'checkbox' ? checked : value
        }
        this.setState(temp)
    }

    handleDefaultRatesheetChange(event) {
        const {name, value, paymentTypeId} = event.target
        const paymentTypes = this.state.paymentTypes.map(paymentType => {
            if(paymentType.payment_type_id === paymentTypeId)
                return {...paymentType, [name] : value}
            else
                return paymentType
        })

        return {paymentTypes: paymentTypes}
    }

    render() {
        return (
            <Tab.Container defaultActiveKey='accounting'>
                <Row className='text-center justify-content-md-center'><h4 className='text-muted'>NOTE: Changes made on this page <u>will affect only <strong>new</strong> objects or calculations.</u> They will not affect anything previously created.</h4></Row>
                <Row>
                    <Col md={2}>
                        <Nav variant='pills' className='flex-column'>
                            <Nav.Item>
                                <Nav.Link eventKey='accounting'>Accounting</Nav.Link>
                            </Nav.Item>
                            <Nav.Item>
                                <Nav.Link eventKey='interliners'>Interliners</Nav.Link>
                            </Nav.Item>
                            <Nav.Item>
                                <Nav.Link eventKey='ratesheets'>Ratesheets</Nav.Link>
                            </Nav.Item>
                            {/* <Nav.Item>
                                <Nav.Link eventKey='scheduling'>Scheduling</Nav.Link>
                            </Nav.Item> */}
                            {/* <Nav.Item>
                                <Nav.Link eventKey='misc'>Miscellaneous</Nav.Link>
                            </Nav.Item> */}
                        </Nav>
                    </Col>
                    <Col md={10}>
                        <Tab.Content>
                            <Tab.Pane eventKey='accounting'>
                                <AccountingTab
                                    gst={this.state.gst}
                                    paymentTypes={this.state.paymentTypes}
                                    ratesheets={this.state.ratesheets}

                                    handleChange={this.handleChange}
                                />
                            </Tab.Pane>
                        </Tab.Content>
                        <Tab.Content>
                            <Tab.Pane eventKey='interliners'>
                                <InterlinersTab
                                    interliners={this.state.interliners}

                                    handleChange={this.handleChange}
                                />
                            </Tab.Pane>
                        </Tab.Content>
                        <Tab.Content>
                            <Tab.Pane eventKey='ratesheets'>
                                <RatesheetsTab
                                    ratesheets={this.state.ratesheets}
                                />
                            </Tab.Pane>
                        </Tab.Content>
                        {/* <Tab.Content>
                            <Tab.Pane eventKey='scheduling'>
                                <SchedulingTab
                                    blockedDate={this.state.blockedDate}
                                    blockedDateReason={this.state.blockedDateReason}
                                    handleChange={this.handleChange}
                                />
                            </Tab.Pane>
                        </Tab.Content> */}
                        {/* <Tab.Content>
                            <Tab.Pane eventKey='misc'>
                            </Tab.Pane>
                        </Tab.Content> */}
                    </Col>
                </Row>
                <Row>
                    <Col md={12} className='text-center'>
                        <Button variant='primary' onClick={this.store}>Submit</Button>
                    </Col>
                </Row>
            </Tab.Container>
        )
    }

    store() {
        const data = {
            gst: this.state.gst,
            paymentTypes: this.state.paymentTypes,
            blockedDates: this.state.blockedDates
        }
        makeAjaxRequest('/appsettings/store', 'POST', data, response => {
            toastr.clear()
            toastr.success('Settings successfully applied')
        })
    }
}
