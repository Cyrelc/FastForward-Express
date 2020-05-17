import React from 'react'
import {Card, Row, Col, InputGroup, FormControl, FormCheck, Table, Button} from 'react-bootstrap'
import Select from 'react-select'
import makeAnimated from 'react-select/animated'

import Pickup_Delivery from './Pickup-Delivery'
import Package from './Package'

export default function BasicTab(props) {
    return (
        <Card border='dark'>
            <Card.Header>
                <Row>
                    <Col md={2}><h4 className='text-muted'>Package</h4></Col>
                    <Col md={10}>
                        <Row>
                            <Col md={6}>
                                <InputGroup>
                                    <InputGroup.Prepend>
                                        <InputGroup.Text>Delivery Type:</InputGroup.Text>
                                    </InputGroup.Prepend>
                                    <Select
                                        options={props.ratesheet.deliveryTypes}
                                        getOptionLabel={type => type.friendlyName + ' (Estimated ' + type.time + ' hours)'}
                                        getOptionValue={type => type.id}
                                        value={props.deliveryType}
                                        onChange={item => props.handleChanges({target: {name: 'deliveryType', type: 'text', value: item}})}
                                        isDisabled={props.readOnly}
                                    />
                                </InputGroup>
                            </Col>
                        </Row>
                        <Row>
                            <Col md={4}>
                                <FormCheck
                                    name='packageIsMinimum'
                                    label='Package is smaller than 30 cm&#179; (1 foot&#179;)'
                                    value={props.packageIsMinimum}
                                    checked={props.packageIsMinimum}
                                    disabled={props.readOnly || props.packageIsPallet}
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
                                        disabled={props.readOnly}
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
                                        disabled={props.readOnly}
                                        onChange={props.handleChanges}
                                    />
                                </Col>
                            }
                            {!props.packageIsMinimum &&
                                <Col md={12}>
                                    <Table>
                                        <thead>
                                            <tr>
                                                <td style={{width:100}}>
                                                    {!props.readOnly &&
                                                        <Button variant='success' onClick={props.addPackage}>
                                                            <span><i className='fas fa-plus' style={{paddingRight: 5}}></i><i className='fas fa-box'></i></span>
                                                        </Button>
                                                    }
                                                </td>
                                                <td><label>Count</label></td>
                                                <td><label>Weight</label></td>
                                                <td><label>Length</label></td>
                                                <td><label>Width</label></td>
                                                <td><label>Height</label></td>
                                            </tr>
                                            <tr>
                                                <td></td>
                                                <td colSpan={5}>Package count is for multiples of similar or uniform packages. Please enter weight, length, width, and height <strong>per package.</strong> It will be multiplied by the package count</td>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {props.packages && props.packages.map(parcel =>
                                                <Package
                                                    useImperial={props.useImperial}
                                                    package={parcel}
                                                    packageCount={props.packages.length}
                                                    readOnly={props.readOnly}
                                                    
                                                    deletePackage={props.deletePackage}
                                                    handleChanges={props.handleChanges}
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
                    <Col md={2}><h4 className='text-muted'>Notes:</h4></Col>
                    <Col md={10}>
                        <FormControl 
                            as='textarea'
                            placeholder='Oversized or unusually shaped delivery? Special delivery instructions? Let us know here!'
                            name='description'
                            value={props.description}
                            onChange={props.handleChanges}
                            readOnly={props.readOnly}
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
                            data={props.pickup}
                            addressTypes={props.addressTypes}
                            minTimestamp={props.minTimestamp}
                            accounts={props.accounts}
                            dateTimeReadOnly={false}
                            readOnly={props.readOnly}
                            admin={props.admin}
                            timeTooltip={"The earliest time the driver will pick up the package. Please have the package ready by the time indicated."}

                            handleChanges={props.handleChanges}
                            />
                    </Col>
                    <Col md={6}>
                        <Pickup_Delivery
                            id='delivery'
                            friendlyName='Delivery'
                            data={props.delivery}
                            addressTypes={props.addressTypes}
                            minTimestamp={props.minTimestamp}
                            accounts={props.accounts}
                            dateTimeReadOnly={!props.admin}
                            admin={props.admin}
                            readOnly={props.readOnly}
                            timeTooltip={"The estimated time of delivery based on your selections. The time shown is not a guarantee"}

                            handleChanges={props.handleChanges}
                        />
                    </Col>
                </Row>
            </Card.Body>
        </Card>
    )
}
