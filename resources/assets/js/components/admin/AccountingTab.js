import React from 'react'
import {Button, Card, Row, Col, InputGroup, FormControl} from 'react-bootstrap'
import Select from 'react-select'
import {toast} from 'react-toastify'

export default function AccountingTab(props) {
    const handleDefaultRatesheetChange = (paymentTypeId, ratesheetId) => {
        const paymentTypes = props.paymentTypes.map(paymentType => {
            if(paymentType.payment_type_id === paymentTypeId)
                return {...paymentType, default_ratesheet_id: ratesheetId}
            return paymentType
        })
        props.setPaymentTypes(paymentTypes)
    }

    const store = () => {
        const data = {
            gst: props.gst,
            paymentTypes: props.paymentTypes
        }
        makeAjaxRequest('/appsettings', 'POST', data, response => {
            toast.success('Settings successfully applied')
        })
    }

    return (
        <Card border='dark'>
            <Card.Header><Card.Title>Accounting</Card.Title></Card.Header>
            <Card.Body>
                <Row>
                    <Col md={2}><h5 className='text-muted'>Taxes</h5></Col>
                    <Col md={10}>
                        <InputGroup>
                            <InputGroup.Text>GST: </InputGroup.Text>
                            <FormControl
                                disabled
                                type='number'
                                min={0}
                                max={100}
                                value={props.gst}
                                name='gst'
                                onChange={event => props.setGst(event.target.value)}
                            />
                            <InputGroup.Text> %</InputGroup.Text>
                        </InputGroup>
                    </Col>
                </Row>
                <hr/>
                <Row>
                    <Col md={2}>
                        <h5 className='text-muted'>Default Ratesheets</h5>
                    </Col>
                    <Col md={10}>
                        {props.paymentTypes && Object.keys(props.paymentTypes).map(index =>
                            <InputGroup key={'paymentType' + index}>
                                <InputGroup.Text style={{width: '20%'}}>{props.paymentTypes[index].name}</InputGroup.Text>
                                <Select
                                    options={props.ratesheets}
                                    getOptionLabel={ratesheet => `${ratesheet.ratesheet_id} - ${ratesheet.name}`}
                                    getOptionValue={ratesheet => ratesheet.ratesheet_id}
                                    value={props.ratesheets.filter(ratesheet => ratesheet.ratesheet_id === props.paymentTypes[index].default_ratesheet_id)}
                                    onChange={ratesheet => handleDefaultRatesheetChange(props.paymentTypes[index].payment_type_id, ratesheet.ratesheet_id)}
                                    input={{size: 5}}
                                />
                            </InputGroup>
                        )}
                    </Col>
                </Row>
            </Card.Body>
            <Card.Footer>
                <Col md={12} className='text-center'>
                    <Button variant='primary' onClick={store}>Submit</Button>
                </Col>
            </Card.Footer>
        </Card>
    )
}
