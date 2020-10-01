import React, {Component} from 'react'
import {Tabs, Tab, Nav, Row, Col, ListGroup, Button} from 'react-bootstrap'
import AccountingTab from './AccountingTab'
import MiscTab from './MiscTab'

export default class AppSettings extends Component {
    constructor() {
        super()
        this.state = {
            //todo: Business hours open/closed, but will require application restart like GST does for editing
            gst: '',
            paymentTypes: [],
            ratesheets: []
        }
        this.handleChange = this.handleChange.bind(this)
        this.store = this.store.bind(this)
    }

    componentDidMount() {
        document.title = 'Application Settings - ' + document.title
        fetch('/appsettings/get')
        .then(response => {return response.json()})
        .then(data => this.setState({gst: data.gst,
            paymentTypes: data.paymentTypes,
            ratesheets: data.ratesheets}))
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
                <Row className='text-center'><h4 className='text-muted'>NOTE: Changes made on this page <u>will affect only <strong>new</strong> objects or calculations.</u> They will not affect anything previously created.</h4></Row>
                <Row>
                    <Col sm={2}>
                        <Nav variant='pills' className='flex-column'>
                            <Nav.Item>
                                <Nav.Link eventKey='accounting'>Accounting</Nav.Link>
                            </Nav.Item>
                            <Nav.Item>
                                <Nav.Link eventKey='misc'>Miscellaneous</Nav.Link>
                            </Nav.Item>
                        </Nav>
                    </Col>
                    <Col sm={10}>
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
                            <Tab.Pane eventKey='misc'>
                            </Tab.Pane>
                        </Tab.Content>
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
            paymentTypes: this.state.paymentTypes
        }
        $.ajax({
            'url': '/appsettings/store',
            'type': 'POST',
            'data': data,
            'success': response => {
                toastr.clear()
                toastr.success('Settings successfully applied')
            },
            'error': response => handleErrorResponse(response)
        })
    }
}
