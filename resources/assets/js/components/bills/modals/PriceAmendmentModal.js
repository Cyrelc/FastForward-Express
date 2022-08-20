import React, {useEffect, useState} from 'react'
import {Col, InputGroup, Modal, Row, Table} from 'react-bootstrap'
import Select from 'react-select'
import CurrencyInput from 'react-currency-input-field'

export default function PriceAdjustModal(props) {
    const [relevantInvoices, setRelevantInvoices] = useState([])
    const [relevantManifests, setRelevantManifests] = useState([])
    const [priceAdjustment, setPriceAdjustment] = useState(0)

    const {charge, show} = props

    const originalPrice = charge?.lineItems?.reduce((aggregate, lineItem) => {
        return aggregate += parseFloat(lineItem.price)
    }, 0)

    const originalDriverAmount = charge?.lineItems?.reduce((aggregate, lineItem) => {
        return aggregate += parseFloat(lineItem.driver_amount)
    }, 0)

    useEffect(() => {
        console.log(charge.lineItems)
        let invoiceIds = new Set()
        let manifestIds = new Set()

        charge?.lineItems?.map(lineItem => {
            invoiceIds.add(lineItem.invoice_id)
        })

        charge?.lineItems?.map(lineItem => {
            manifestIds.add(lineItem.pickup_manifest_id)
            manifestIds.add(lineItem.delivery_manifest_id)
        })

        setRelevantInvoices(Array.from(invoiceIds).map(invoiceId => {return {label: invoiceId, value: invoiceId}}))
        setRelevantManifests(Array.from(manifestIds).map(manifestId => {return {label: manifestId, value: manifestId}}))
    }, [show])

    if(!charge.lineItems)
        return (
            <Modal>
                <Modal.Header closeButton>
                    <Modal.Title>Unable to adjust price of charge with no line items present.</Modal.Title>
                </Modal.Header>
            </Modal>
        )

    return (
        <Modal onHide={props.hide} show={show} size='lg'>
            <Modal.Header closeButton>
                <Modal.Title>Price Correction</Modal.Title>
            </Modal.Header>
            <Modal.Body>
                Optional: You may select an invoice, pickup manifest, and delivery manifest to assign the adjustment to.<br/>
                If you do not, it will be picked up the next time an invoice or manifest is run which matches the criteria.
                <hr/>
            </Modal.Body>
            <Modal.Body>
                <Row>
                    <Col md={6}>
                        <InputGroup>
                            <InputGroup.Text>Invoice ID:</InputGroup.Text>
                            <Select
                                options={relevantInvoices}
                            />
                            <InputGroup.Text>
                                <i className='fas fa-question-circle' title='The invoice to assign the price adjustment to'></i>
                            </InputGroup.Text>
                        </InputGroup>
                    </Col>
                    <Col md={6}>
                        <InputGroup>
                            <InputGroup.Text>Pickup Manifest ID:</InputGroup.Text>
                            <Select
                                options={relevantManifests}
                            />
                            <InputGroup.Text>
                                <i className='fas fa-question-circle' title='The pickup manifest to assign this price adjustment to'></i>
                            </InputGroup.Text>
                        </InputGroup>
                        <InputGroup>
                            <InputGroup.Text>Pickup Manifest ID:</InputGroup.Text>
                            <Select
                                options={relevantManifests}
                            />
                            <InputGroup.Text>
                                <i className='fas fa-question-circle' title='The delivery manifest to assign this price adjustment to'></i>
                            </InputGroup.Text>
                        </InputGroup>
                    </Col>
                </Row>
            </Modal.Body>
            <Modal.Body>
                <InputGroup>
                    <InputGroup.Text>Adjust price by:</InputGroup.Text>
                    <CurrencyInput
                        decimalsLimit={2}
                        decimalScale={2}
                        min={0.01}
                        name='priceAdjust'
                        onValueChange={setPriceAdjustment}
                        prefix='$'
                        step={0.01}
                        value={priceAdjustment}
                    />
                </InputGroup>
            </Modal.Body>
            <Modal.Body>
                <Table bordered>
                    <thead>
                        <tr>
                            <th></th>
                            <th></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Original</td>
                            <td>{originalPrice.toLocaleString('en-CA', {style: 'currency', currency: 'CAD'})}</td>
                            <td>{originalDriverAmount.toLocaleString('en-CA', {style: 'currency', currency: 'CAD'})}</td>
                        </tr>
                        <tr>
                            <td>Adjustment</td>
                            <td>{parseFloat(priceAdjustment).toLocaleString('en-CA', {style: 'currency', currency: 'CAD'})}</td>
                            <td>{parseFloat(priceAdjustment).toLocaleString('en-CA', {style: 'currency', currency: 'CAD'})}</td>
                        </tr>
                        <tr>
                            <td>New Total</td>
                            <td>{(originalPrice + parseFloat(priceAdjustment)).toLocaleString('en-CA', {style: 'currency', currency: 'CAD'})}</td>
                            <td>{(originalDriverAmount + parseFloat(priceAdjustment)).toLocaleString('en-CA', {style: 'currency', currency: 'CAD'})}</td>
                        </tr>
                    </tbody>
                </Table>
            </Modal.Body>
        </Modal>
    )
}


