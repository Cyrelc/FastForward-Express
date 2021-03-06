import React from 'react'
import {Card, Row, Col, Table, InputGroup, FormControl, Form} from 'react-bootstrap'
import TimeRate from './TimeRate'
import WeightRates from './WeightRates'
import ZoneRate from './ZoneRate'
import RateOption from './RateOption'

export default function SettingsTab(props) {
    return (
        <Card>
            <Card.Header>
                <Row>
                    <Col md={2}>
                        <h4 className='text-muted'>Basic Options</h4>
                    </Col>
                    <Col md={4}>
                        <InputGroup>
                            <InputGroup.Prepend>
                                <InputGroup.Text>Ratesheet Name</InputGroup.Text>
                            </InputGroup.Prepend>
                            <FormControl type='text' placeholder='Ratesheet Name' name='name' value={props.name} onChange={props.handleChange}/>
                        </InputGroup>
                    </Col>
                    <Col md={4}>
                    <strong><Form.Check type='checkbox' name='useInternalZonesCalc' label='Use Internal Zones Crossed to Calculate Pricing' checked={props.useInternalZonesCalc} onChange={props.handleChange} /></strong>
                    </Col>
                </Row>
                <hr/>
                <Row>
                    <Col md={2}>
                        <h4 className='text-muted'>Pallet/Skid Pricing</h4>
                    </Col>
                    <Col md={10}>
                        <InputGroup>
                            <InputGroup.Prepend>
                                <InputGroup.Text>Base Cost: $</InputGroup.Text>
                            </InputGroup.Prepend>
                            <FormControl
                                type='number'
                                name='palletBasePrice'
                                min={1}
                                step={0.01}
                                value={props.palletRate.palletBasePrice}
                                onChange={props.handlePalletRateChange}
                            />
                            <InputGroup.Append>
                                <InputGroup.Text>Up to: </InputGroup.Text>
                            </InputGroup.Append>
                            <FormControl
                                type='number'
                                name='palletBaseWeightKgs'
                                min={1}
                                value={props.palletRate.palletBaseWeightKgs}
                                onChange={props.handlePalletRateChange}
                            />
                            <InputGroup.Append>
                                <InputGroup.Text> kgs </InputGroup.Text>
                            </InputGroup.Append>
                            <FormControl
                                type='number'
                                name='palletBaseWeightLbs'
                                min={1}
                                value={props.palletRate.palletBaseWeightLbs}
                                onChange={props.handlePalletRateChange}
                            />
                            <InputGroup.Append>
                                <InputGroup.Text> lbs</InputGroup.Text>
                            </InputGroup.Append>
                            <InputGroup.Prepend>
                                <InputGroup.Text>Additional Cost: $</InputGroup.Text>
                            </InputGroup.Prepend>
                            <FormControl
                                type='number'
                                name='palletAdditionalCharge'
                                min={1}
                                value={props.palletRate.palletAdditionalCharge}
                                onChange={props.handlePalletRateChange}
                            />
                            <InputGroup.Append>
                                <InputGroup.Text> for every </InputGroup.Text>
                            </InputGroup.Append>
                            <FormControl
                                type='number'
                                name='palletAdditionalWeightKgs'
                                min={1}
                                value={props.palletRate.palletAdditionalWeightKgs}
                                onChange={props.handlePalletRateChange}
                            />
                            <InputGroup.Append>
                                <InputGroup.Text> kgs </InputGroup.Text>
                            </InputGroup.Append>
                            <FormControl
                                type='number'
                                name='palletAdditionalWeightLbs'
                                min={1}
                                value={props.palletRate.palletAdditionalWeightLbs}
                                onChange={props.handlePalletRateChange}
                            />
                            <InputGroup.Append>
                                <InputGroup.Text> lbs</InputGroup.Text>
                            </InputGroup.Append>
                        </InputGroup>
                    </Col>
                </Row>
                <hr/>
                <Row>
                    <Col md={2}>
                        <h4 className='text-muted'>Weekends & Holidays</h4>
                    </Col>
                    <Col md={4}>
                        <InputGroup>
                            <InputGroup.Prepend>
                                <InputGroup.Text>Weekend Price: </InputGroup.Text>
                            </InputGroup.Prepend>
                            <FormControl
                                type='number'
                                step='0.01'
                                placeholder='Weekend Pricing'
                                name='weekendRate'
                                value={props.weekendRate}
                                onChange={props.handleChange}
                            />
                        </InputGroup>
                    </Col>
                    <Col md={4}>
                        <InputGroup>
                            <InputGroup.Prepend>
                                <InputGroup.Text>Holiday Price: </InputGroup.Text>
                            </InputGroup.Prepend>
                            <FormControl
                                type='number'
                                step='0.01'
                                placeholder='Holiday Pricing'
                                name='holidayRate'
                                value={props.holidayRate}
                                onChange={props.handleChange}
                            />
                        </InputGroup>
                    </Col>
                </Row>
                <hr/>
                {props.timeRates &&
                <Row>
                    <Col md={2}>
                        <h4 className='text-muted'>After Hours</h4>
                    </Col>
                    <Col md={10}>
                        {props.timeRates.map(rate =>
                            <TimeRate
                                key = {rate.id}
                                id = {rate.id}
                                startTime = {rate.startTime}
                                endTime = {rate.endTime}
                                cost = {rate.cost}
                                handleTimeRateChange = {props.handleTimeRateChange}
                            />
                        )}
                    </Col>
                </Row>}
            </Card.Header>
            <Card.Body>
                <Row>
                    {props.useInternalZonesCalc ? 
                    <Col md={6}>
                        <Card body>
                            <Row className='justify-content-md-center'>
                                <h4 className='text-muted'>Zone Distance Costs</h4>
                            </Row>
                            <Table size='sm'>
                                <thead>
                                    <tr>
                                        {props.deliveryTypes.map(type => 
                                            <td>{type.friendlyName}</td>
                                        )}
                                    </tr>
                                    <tr>
                                        {props.deliveryTypes.map(type =>
                                            <td>
                                                <InputGroup size='sm'>
                                                    <InputGroup.Prepend>
                                                        <InputGroup.Text>Time Est. </InputGroup.Text>
                                                    </InputGroup.Prepend>
                                                    <FormControl
                                                        type='number'
                                                        min='0.1'
                                                        step='0.1'
                                                        name='time'
                                                        value={type.time}
                                                        onChange={event => props.handleChange(event, 'deliveryTypes', type.id)}
                                                    />
                                                </InputGroup>
                                            </td>
                                        )}
                                    </tr>
                                </thead>
                                <tbody>
                                    {props.zoneRates.map(rate => 
                                        <ZoneRate 
                                            key={rate.id}
                                            id={rate.id}
                                            zones={rate.zones}
                                            regularCost={rate.regularCost}
                                            rushCost={rate.rushCost}
                                            directCost={rate.directCost}
                                            directRushCost={rate.directRushCost}
                                            handleZoneRateChange={props.handleZoneRateChange}/>
                                        )
                                    }
                                </tbody>
                            </Table>
                        </Card>
                    </Col>
                    :
                    <Col md={6}>
                        <Card body>
                            <Row className='justify-content-md-center'>
                                <h4 className='text-muted'>Delivery Types</h4>
                            </Row>
                            <Table>
                                <thead>
                                    <tr>
                                        <th>Type</th>
                                        <th>Additional Cost</th>
                                        <th>Additional Time (hours)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {props.deliveryTypes.map(type => 
                                        <RateOption 
                                            key={type.id}
                                            friendlyName={type.friendlyName}
                                            time={type.time}
                                            cost={type.cost}
                                            id={type.id}
                                            handleChange={props.handleChange}
                                        />
                                    )}
                                </tbody>
                            </Table>
                        </Card>
                    </Col>
                    }
                    <Col md={6}>
                        <Card body>
                            <Row className='justify-content-md-center'>
                                <h4 className='text-muted'>Weight Rates</h4>
                            </Row>
                            <WeightRates weightRates={props.weightRates} handleChange={props.handleChange} handleWeightRateChange={props.handleWeightRateChange}/>
                        </Card>
                    </Col>
                </Row>
            </Card.Body>
        </Card>
    )
}
