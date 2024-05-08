import React from 'react'
import {Card, Col, FormControl, InputGroup, Row} from 'react-bootstrap'

export default function ZoneDistanceRatesTab(props) {
    return (
        <Card>
            <Card.Header>
                <Row>
                    <Col md={12}>
                        <h4 className='text-muted'>Distance Rates</h4>
                    </Col>
                </Row>
            </Card.Header>
            <Card.Body>
                <InputGroup>
                    <InputGroup.Text></InputGroup.Text>
                    <FormControl
                        type='number'
                        step='0.1'
                        min='0'
                        max='100'
                        name='outlyingToOutlyingDiscount'
                        value={props.outlyingToOutlyingDiscount}
                        onChange={event => props.handleChange(event, 'outlyingToOutlyingDiscount', type.id)}
                    />
                </InputGroup>
            </Card.Body>
        </Card>
    )
}

