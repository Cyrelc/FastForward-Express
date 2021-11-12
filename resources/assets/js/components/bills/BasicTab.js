import React from 'react'
import {Card, Row, Col, InputGroup, FormControl, FormCheck, Table, Button} from 'react-bootstrap'
import Select from 'react-select'

import Pickup_Delivery from './Pickup-Delivery'
import Package from './Package'

export default function BasicTab(props) {
    const packageCountInfo = 'Package count is for multiples of similar or uniform packages. Please enter weight, length, width, and height per package. It will be multiplied by the package count'

    return (
        <Card border='dark'>
            <Card.Header>
                <Row>
                    <Col md={2}><h4 className='text-muted'>Package Info</h4></Col>
                    <Col md={10}>
                        <Row>
                            <Col md={4}>
                                <FormCheck
                                    name='packageIsMinimum'
                                    label='Package is smaller than 30 cm&#179; (1 foot&#179;)'
                                    value={props.packageIsMinimum}
                                    checked={props.packageIsMinimum}
                                    disabled={props.readOnly || props.packageIsPallet || props.invoiceId}
                                    onChange={props.handleChanges}
                                />
                            </Col>
                            {!props.packageIsMinimum &&
                                <Col md={4}>
                                    <FormCheck
                                        name='packageIsPallet'
                                        label='Is a pallet'
                                        value={props.packageIsPallet}
                                        checked={props.packageIsPallet}
                                        disabled={props.readOnly || props.invoiceId}
                                        onChange={props.handleChanges}
                                    />
                                </Col>
                            }
                            {!props.packageIsMinimum &&
                                <Col md={4}>
                                    <FormCheck
                                        name='useImperial'
                                        label='Use Imperial Measurements'
                                        value={props.useImperial}
                                        checked={props.useImperial}
                                        disabled={props.readOnly || props.invoiceId}
                                        onChange={props.handleChanges}
                                    />
                                </Col>
                            }
                            {!props.packageIsMinimum &&
                                <Col md={12}>
                                    <Table size='sm'>
                                        <thead>
                                            <tr key='Titles'>
                                                <th style={{width:100}}>
                                                    {(!props.readOnly && !props.invoiceId) &&
                                                        <Button variant='success' onClick={props.addPackage} size='sm'>
                                                            <span><i className='fas fa-plus' style={{paddingRight: 5}}></i><i className='fas fa-box'></i></span>
                                                        </Button>
                                                    }
                                                </th>
                                                <th>Count <i className='fas fa-question-circle' title={packageCountInfo}></i></th>
                                                <th>Weight</th>
                                                <th>Length</th>
                                                <th>Width</th>
                                                <th>Height</th>
                                            </tr>
                                            <tr key='Warning'>
                                                <td></td>
                                                <td colSpan={5}><strong><i className='fas fa-exclamation'></i> Warning: </strong> Failure to accurately represent the weight or dimensions of your delivery may result in additional charges</td>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {props.packages && props.packages.map(parcel =>
                                                <Package
                                                    useImperial={props.useImperial}
                                                    package={parcel}
                                                    packageCount={props.packages.length}
                                                    readOnly={props.readOnly || props.invoiceId}
                                                    
                                                    deletePackage={props.deletePackage}
                                                    handleChanges={props.handleChanges}
                                                    key={parcel}
                                                />
                                            )}
                                        </tbody>
                                    </Table>
                                </Col>
                            }
                        </Row>
                    </Col>
                </Row>
                <hr/>
                <Row className='pad-top'>
                    <Col md={2}><h4 className='text-muted'>Billing</h4></Col>
                    <Col md={3}>
                        <InputGroup>
                            <InputGroup.Prepend>
                                <InputGroup.Text>Delivery Type:</InputGroup.Text>
                            </InputGroup.Prepend>
                            <Select
                                options={props.ratesheet.deliveryTypes}
                                getOptionLabel={type => type.friendlyName + ' (Est. ~' + type.time + ' hours)'}
                                getOptionValue={type => type.id}
                                value={props.deliveryType}
                                onChange={item => props.handleChanges({target: {name: 'deliveryType', type: 'text', value: item}})}
                                isDisabled={props.readOnly || props.invoiceId}
                                isOptionDisabled={option => props.applyRestrictions ? option.isDisabled : false}
                            />
                        </InputGroup>
                    </Col>
                    {/* <Col md={3}>
                        <InputGroup>
                            <InputGroup.Prepend>
                                <InputGroup.Text>Payment Type: </InputGroup.Text>
                            </InputGroup.Prepend>
                            <Select
                                options={props.paymentTypes}
                                getOptionLabel={type => type.name}
                                value={props.paymentType}
                                onChange={paymentType => props.handleChanges({target: {name: 'paymentType', type: 'text', value: paymentType}})}
                                isDisabled={props.readOnly || props.invoiceId || props.paymentTypes.length === 1}
                            />
                        </InputGroup>
                    </Col> */}
                    {/* {props.paymentType.name === 'Account' &&
                        <Col md={4}>
                            <Row>
                                <Col md={12}>
                                    <InputGroup>
                                        <InputGroup.Prepend>
                                            <InputGroup.Text>Account: </InputGroup.Text>
                                        </InputGroup.Prepend>
                                        <Select
                                            options={props.accounts}
                                            isSearchable
                                            onChange={account => props.handleChanges({target: {name: 'chargeAccount', type: 'object', value: account}})}
                                            value={props.chargeAccount}
                                            isDisabled={props.readOnly || props.invoiceId || props.accounts.length === 1}
                                            menuPortalTarget={document.body}
                                            menuPosition='fixed'
                                        />
                                    </InputGroup>
                                </Col>
                                {((props.chargeAccount && props.chargeAccount.custom_field !== null)
                                    || (props.paymentType !== '' && props.paymentType.required_field !== null)) &&
                                    <Col md={12}>
                                        <InputGroup>
                                            <InputGroup.Prepend>
                                                <InputGroup.Text>{props.paymentType.name === 'Account' ? props.chargeAccount.custom_field : props.paymentType.required_field}: </InputGroup.Text>
                                            </InputGroup.Prepend>
                                            <FormControl
                                                name='chargeReferenceValue'
                                                value={props.chargeReferenceValue}
                                                onChange={props.handleChanges}
                                                readOnly={props.readOnly || props.invoiceId}
                                            />
                                        </InputGroup>
                                    </Col>
                                }
                            </Row>
                        </Col>
                    } */}
                </Row>
                <hr/>
                <Row className='pad-top'>
                    <Col md={2}><h4 className='text-muted'>Notes</h4></Col>
                    <Col md={10}>
                        <FormControl 
                            as='textarea'
                            placeholder='Oversized or unusually shaped delivery? Special delivery instructions? Contact information on delivery? Let us know here!'
                            name='description'
                            value={props.description}
                            onChange={props.handleChanges}
                            readOnly={props.readOnly || props.invoiceId}
                        />
                    </Col>
                </Row>
            </Card.Header>
            <Card.Body>
                <Row>
                    <Col md={6}>
                        <Pickup_Delivery
                            id='pickup'
                            friendlyName='Pickup'
                            applyRestrictions={props.applyRestrictions}
                            data={props.pickup}
                            addressTypes={props.addressTypes}
                            minTimestamp={props.minTimestamp}
                            accounts={props.accounts}
                            dateTimeReadOnly={false}
                            readOnly={props.readOnly || props.invoiceId}
                            admin={props.admin}
                            timeTooltip={"The earliest time the driver will pick up the package. Please have the package ready by the time indicated."}

                            handleChanges={props.handleChanges}
                        />
                    </Col>
                    <Col md={6}>
                        <Pickup_Delivery
                            id='delivery'
                            friendlyName='Delivery'
                            applyRestrictions={props.applyRestrictions}
                            data={props.delivery}
                            addressTypes={props.addressTypes}
                            minTimestamp={props.minTimestamp}
                            accounts={props.accounts}
                            dateTimeReadOnly={props.applyRestrictions}
                            admin={props.admin}
                            readOnly={props.readOnly || props.invoiceId}
                            timeTooltip={"The estimated time of delivery based on your selections. The time shown is not a guarantee"}

                            handleChanges={props.handleChanges}
                        />
                    </Col>
                </Row>
            </Card.Body>
        </Card>
    )
}
