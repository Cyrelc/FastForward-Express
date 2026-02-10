import React, {useState} from 'react'
import {Button, Card, Col, Row, Spinner} from 'react-bootstrap'
import {toast} from 'react-toastify'

import {useAPI} from '../../contexts/APIContext'
import WorkerStatusIndicator from '../partials/WorkerStatusIndicator'

export default function ToolsTab(props) {
    const [restarting, setRestarting] = useState(false)

    const api = useAPI()

    const refreshReceipts = () => {
        api.get('/api/tools/getStripeReceipts')
            .then(response => {
                if(response.count == 0)
                    toast.warning(`Found no receipts to refresh`)
                else
                    toast.success(`Successfully retrieved ${response.count} receipts`)
            })
    }

    const restartWorkers = () => {
        if(!confirm('Are you sure you want to restart all Laravel workers?')) {
            return
        }

        setRestarting(true)
        api.post('/api/workers/restart')
            .then(response => {
                toast.success('Workers restarted successfully')
            })
            .catch(error => {
                toast.error('Failed to restart workers')
            })
            .finally(() => {
                setRestarting(false)
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
                <hr/>
                <Row>
                    <Col md={2}>
                        <h2 className='text-muted'>Workers</h2>
                    </Col>
                    <Col md={4} style={{display: 'flex', alignItems: 'center', gap: '15px'}}>
                        <WorkerStatusIndicator refreshInterval={30000} size="large" />
                        <Button 
                            variant='warning' 
                            onClick={restartWorkers}
                            disabled={restarting}
                        >
                            {restarting ? 
                                <><Spinner animation='border' size='sm' /> Restarting...</> :
                                <><i className='fas fa-sync'></i> Restart Workers</>
                            }
                        </Button>
                    </Col>
                </Row>
            </Card.Body>
        </Card>
    )
}

