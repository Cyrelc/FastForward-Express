import React from 'react'
import {Button, Card, Col, Row} from 'react-bootstrap'
import {toast} from 'react-toastify'

import {useAPI} from '../../contexts/APIContext'

export default function ToolsTab(props) {
    const api = useAPI()

    const refreshReceipts = () => {
        api.get('/api/payments/getStripeReceipts')
            .then(response => {
                toast.success(`Successfully retrieved ${response.count} receipts`)
            })
    }

    return (
        <Card>
            <Card.Header>
                <Card.Title>Tools</Card.Title>
            </Card.Header>
            <Card.Body>
                <Row>
                    <Col md={2}>
                        <h2 className='text-muted'>Payments</h2>
                    </Col>
                    <Col md={4}>
                        <Button onClick={refreshReceipts}>
                            <i className='fas fa-receipt'></i> Retrieve Stripe Receipts
                        </Button>
                    </Col>
                </Row>
            </Card.Body>
        </Card>
    )
}

