import React, {useEffect, useState} from 'react'
import {Accordion, Badge, Button, Col, Nav, Row, Tab} from 'react-bootstrap'

import AccountingTab from './AccountingTab'
// import CompanyInfoTab from './CompanyInfoTab'
import InterlinersTab from './InterlinersTab'
import RatesheetsTab from './RatesheetsTab'
// import MiscTab from './MiscTab'
import SchedulingTab from './SchedulingTab'

export default function AppSettings(props) {
    const [activeKey, setActiveKey] = useState('accounting')
    const [gst, setGst] = useState('')
    const [interliners, setInterliners] = useState([])
    const [paymentTypes, setPaymentTypes] = useState([])
    const [ratesheets, setRatesheets] = useState([])
    const [blockedDates, setBlockedDates] = useState([])

    useEffect(() => {
        document.title = 'Application Settings - Fast Forward Express'
        setActiveKey(location.hash?.slice(1) ?? 'accounting')
        makeAjaxRequest(`/appsettings`, 'GET', null, response => {
            response = JSON.parse(response)
            setGst(response.gst)
            setInterliners(response.interliners)
            setPaymentTypes(response.payment_types)
            setRatesheets(response.ratesheets)
            const blockedDates = response.blocked_dates.map(blocked => {
                return {...blocked, date: Date.parse(blocked.value)}
            })
            setBlockedDates(blockedDates)
        })
    }, [])

    useEffect(() => {
        setActiveKey(location.hash?.slice(1) ?? 'accounting')
    }, [window.location.hash])

    const handleTabChange = (eventKey) => {
        window.location.hash = eventKey
        setActiveKey(eventKey)
    }

    return (
        <Row className='text-center justify-content-md-center'>
            <Col md={12}>
                <Badge bg='warning'>
                    <h5 className='text-muted'>
                        NOTE: Changes made on this page <u>will affect only <strong>new</strong> objects or calculations, or edits of old objects.</u> They will not affect anything previously created.
                    </h5>
                </Badge>
            </Col>
            <Col md={12}>
                <Tab.Container activeKey={activeKey} onChange={(event) => console.log(event)}>
                    <Row>
                        <Col md={2}>
                            <Accordion activeKey={activeKey}>
                                <Accordion.Item eventKey='accounting' onClick={() => handleTabChange('accounting')}>
                                    <Accordion.Header>Accounting</Accordion.Header>
                                    <Accordion.Body>
                                        <Nav.Link><a href="#">Taxes</a></Nav.Link>
                                        <Nav.Link><a href="#">Default Ratesheets</a></Nav.Link>
                                    </Accordion.Body>
                                </Accordion.Item>
                                <Accordion.Item eventKey='interliners' onClick={() => handleTabChange('interliners')}>
                                    <Accordion.Header>Interliners</Accordion.Header>
                                </Accordion.Item>
                                <Accordion.Item eventKey='ratesheets' onClick={() => handleTabChange('ratesheets')}>
                                    <Accordion.Header>Ratesheets</Accordion.Header>
                                </Accordion.Item>
                                <Accordion.Item eventKey='scheduling' onClick={() => handleTabChange('scheduling')}>
                                    <Accordion.Header>Scheduling</Accordion.Header>
                                </Accordion.Item>
                                {/* <Accordion.Item eventKey='company'>
                                    <Accordion.Header>Company Info</Accordion.Header>
                                </Accordion.Item> */}
                                {/* <Accordion.Item eventKey='misc'>
                                    <Accordion.Header>Miscellaneous</Accordion.Header>
                                </Accordion.Item> */}
                            </Accordion>
                        </Col>
                        <Col md={10}>
                            <Tab.Content>
                                <Tab.Pane eventKey='accounting'>
                                    <AccountingTab
                                        gst={gst}
                                        paymentTypes={paymentTypes}
                                        ratesheets={ratesheets}

                                        setGst={setGst}
                                        setPaymentTypes={setPaymentTypes}
                                    />
                                </Tab.Pane>
                            </Tab.Content>
                            {/* <Tab.Content>
                                <Tab.Pane eventKey='company-info'>
                                    <CompanyInfoTab
                                        handleChange={this.handleChange}
                                    />
                                </Tab.Pane>
                            </Tab.Content> */}
                            <Tab.Content>
                                <Tab.Pane eventKey='interliners'>
                                    <InterlinersTab
                                        interliners={interliners}
                                        setInterliners={setInterliners}
                                    />
                                </Tab.Pane>
                            </Tab.Content>
                            <Tab.Content>
                                <Tab.Pane eventKey='ratesheets'>
                                    <RatesheetsTab
                                        ratesheets={ratesheets}
                                    />
                                </Tab.Pane>
                            </Tab.Content>
                            <Tab.Content>
                                <Tab.Pane eventKey='scheduling'>
                                    <SchedulingTab
                                        blockedDates={blockedDates}
                                        setBlockedDates={setBlockedDates}
                                    />
                                </Tab.Pane>
                            </Tab.Content>
                            {/* <Tab.Content>
                                <Tab.Pane eventKey='misc'>
                                </Tab.Pane>
                            </Tab.Content> */}
                        </Col>
                    </Row>
                </Tab.Container>
            </Col>
        </Row>
    )
}
