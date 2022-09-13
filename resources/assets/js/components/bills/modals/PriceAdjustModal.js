import React, {useEffect, useState} from 'react'
import {Button, Col, InputGroup, Modal, Row, Table} from 'react-bootstrap'
import Select from 'react-select'
import CurrencyInput from 'react-currency-input-field'

export default function PriceAdjustModal(props) {
    const [driverPriceAdjustment, setDriverPriceAdjustment] = useState(0)
    const [relevantInvoices, setRelevantInvoices] = useState([])
    const [relevantManifests, setRelevantManifests] = useState([])
    const [priceAdjustment, setPriceAdjustment] = useState(0)

    const {charge, delivery, hide, pickup, show, tableRef} = props

    const originalPrice = charge?.lineItems?.reduce((aggregate, lineItem) => {
        return aggregate += parseFloat(lineItem.price)
    }, 0)

    const originalDriverAmount = charge?.lineItems?.reduce((aggregate, lineItem) => {
        return aggregate += parseFloat(lineItem.driver_amount)
    }, 0)

    useEffect(() => {
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

    const submitAdjustment = () => {
        const adjustment = {
            driver_amount: driverPriceAdjustment,
            name: 'Adjustment',
            paid: false,
            price: priceAdjustment,
            type: 'miscellaneousRate',
        }
        tableRef.current.table.addData(adjustment)
    }

    if(!charge.lineItems)
        return (
            <Modal>
                <Modal.Header closeButton>
                    <Modal.Title>Unable to adjust price of a charge with no line items present.</Modal.Title>
                </Modal.Header>
            </Modal>
        )

    return (
        <Modal onHide={hide} show={show} size='lg'>
            <Modal.Header closeButton>
                <Modal.Title>Price Correction</Modal.Title>
            </Modal.Header>
            <Modal.Body>
                Optional: You may select an invoice, pickup manifest, and delivery manifest to assign the adjustment to.<br/>
                If you do not, it will be picked up the next time an invoice or manifest is run which matches the criteria.
                <hr/>
            </Modal.Body>
            <Modal.Body>
                <Table bordered>
                    <thead>
                        <tr>
                            <th></th>
                            <th>{charge.name}</th>
                            <th><i className='fas fa-arrow-up'></i> {pickup.driver.label}</th>
                            <th><i className='fas fa-arrow-down'></i> {delivery.driver.label}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <th>Original</th>
                            <td>{originalPrice.toLocaleString('en-CA', {style: 'currency', currency: 'CAD'})}</td>
                            <td colSpan={2}>{originalDriverAmount.toLocaleString('en-CA', {style: 'currency', currency: 'CAD'})}</td>
                        </tr>
                        <tr>
                            <th>Adjustment</th>
                            <td>
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
                            </td>
                            <td colSpan={2}>
                                <CurrencyInput
                                    decimalsLimit={2}
                                    decimalScale={2}
                                    min={0.01}
                                    name='driverPriceAdjust'
                                    onValueChange={setDriverPriceAdjustment}
                                    prefix='$'
                                    step={0.01}
                                    value={driverPriceAdjustment}
                                />
                            </td>
                        </tr>
                        <tr>
                            <th>Invoice/Manifest (Optional)</th>
                            <td>
                                <InputGroup>
                                    <Select
                                        options={relevantInvoices}
                                    />
                                    <InputGroup.Text>
                                        <i className='fas fa-question-circle' title='The invoice to assign the price adjustment to'></i>
                                    </InputGroup.Text>
                                </InputGroup>
                            </td>
                            <td>
                                <InputGroup>
                                    <Select
                                        options={relevantManifests}
                                    />
                                    <InputGroup.Text>
                                        <i className='fas fa-question-circle' title='The pickup manifest to assign this price adjustment to'></i>
                                    </InputGroup.Text>
                                </InputGroup>
                            </td>
                            <td>
                                <InputGroup>
                                    <Select
                                        options={relevantManifests}
                                    />
                                    <InputGroup.Text>
                                        <i className='fas fa-question-circle' title='The delivery manifest to assign this price adjustment to'></i>
                                    </InputGroup.Text>
                                </InputGroup>
                            </td>
                        </tr>
                        <tr>
                            <th>New Total</th>
                            <th>{(originalPrice + parseFloat(priceAdjustment)).toLocaleString('en-CA', {style: 'currency', currency: 'CAD'})}</th>
                            <th>{(originalDriverAmount * 0.5 + parseFloat(driverPriceAdjustment) * 0.5).toLocaleString('en-CA', {style: 'currency', currency: 'CAD'})}</th>
                            <th>{(originalDriverAmount * 0.5 + parseFloat(driverPriceAdjustment) * 0.5).toLocaleString('en-CA', {style: 'currency', currency: 'CAD'})}</th>
                        </tr>
                        <tr>
                            <th>Driver Take Home</th>
                            <td></td>
                            <td>{((originalDriverAmount + parseFloat(driverPriceAdjustment)) * parseFloat(pickup.driverCommission / 100)).toLocaleString('en-CA', {style: 'currency', currency: 'CAD'})}</td>
                            <td>{((originalDriverAmount + parseFloat(driverPriceAdjustment)) * parseFloat(delivery.driverCommission / 100)).toLocaleString('en-CA', {style: 'currency', currency: 'CAD'})}</td>
                        </tr>
                    </tbody>
                </Table>
            </Modal.Body>
            <Modal.Body>
                <Row className='justify-content-md-center'>
                    <Button variant='success' onClick={submitAdjustment}>Submit</Button>
                </Row>
            </Modal.Body>
        </Modal>
    )
}


