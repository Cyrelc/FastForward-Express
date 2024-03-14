import React, {useEffect, useState} from 'react'
import {Button, ButtonGroup, Col, Row, Table} from 'react-bootstrap'
import { LinkContainer } from 'react-router-bootstrap'
import {useUser} from '../../contexts/UserContext'

const headerTDStyle = {width: '20%', textAlign: 'center', border: 'grey solid', whiteSpace: 'pre', paddingTop: '10px', paddingBottom: '10px'}

export default function Manifest(props) {
    const [data, setData] = useState({})
    const [nextManifestId, setNextManifestId] = useState(null)
    const [prevManifestId, setPrevManifestId] = useState(null)
    const [isLoading, setIsLoading] = useState(true)

    const {manifestId} = props.match.params
    const {bills, chargebacks, manifest, overview, employee} = data
    const {contact, warnings} = data?.employee || {}
    const {frontEndPermissions} = useUser()

    useEffect(() => {
        getManifest(manifestId)
    }, [manifestId])

    const getManifest = () => {
        setIsLoading(true)
        makeAjaxRequest(`/manifests/${manifestId}`, 'GET', null, response => {
            response = JSON.parse(response)
            document.title = `View Manifest - ${response.manifest.manifest_id}`
            let sortedManifests = localStorage.getItem('manifests.sortedList')
            if(sortedManifests) {
                sortedManifests = sortedManifests.split(',').map(index => parseInt(index))
                const thisManifestIndex = sortedManifests.findIndex(manifest_id => response.manifest.manifest_id === manifest_id)
                setPrevManifestId(thisManifestIndex <= 0 ? null : sortedManifests[thisManifestIndex - 1])
                setNextManifestId((thisManifestIndex < 0 || thisManifestIndex === sortedManifests.length - 1) ? null : sortedManifests[thisManifestIndex + 1])
            }

            setData(response)
            setIsLoading(false)
        }, () => setIsLoading(false))
    }

    const regather = () => {
        setIsLoading(true)
        makeAjaxRequest(`/manifests/regather/${manifestId}`, 'GET', null, response => {
            setData(JSON.parse(response))
            setIsLoading(false)
        }, error => setIsLoading(false))
    }

    if(isLoading)
        return (
            <Row className='justify-content-md-center'>
                <Col md={3}><h4><i className='fas fa-cog fa-spin'></i>  Loading...</h4></Col>
            </Row>
        )

    return (
        <Row className='justify-content-md-center' style={{paddingTop: '20px'}}>
            <Col md={2}>
                <h3>Manifest {manifest?.manifest_id}</h3>
            </Col>
            <Col md={2}>
                <ButtonGroup>
                    <LinkContainer to={`/manifests/${prevManifestId}`}>
                        <Button variant='info' disabled={!prevManifestId}>
                            <i className='fas fa-arrow-circle-left'></i> Back - {prevManifestId}
                        </Button>
                    </LinkContainer>
                    <LinkContainer to={`/manifests/${nextManifestId}`}>
                        <Button variant='info' disabled={!nextManifestId}>
                            Next - {nextManifestId} <i className='fas fa-arrow-circle-right'></i>
                        </Button>
                    </LinkContainer>
                </ButtonGroup>
            </Col>
            <Col md={7} style={{textAlign: 'right'}}>
                <ButtonGroup>
                    <Button variant='primary' href={`/manifests/print/${manifest?.manifest_id}`} target='_blank'>
                        <i className='fas fa-print'></i> Print
                    </Button>
                    <Button variant='success' href={`/manifests/print/${manifest?.manifest_id}?without_bills`}>
                        <i className='fas fa-print'></i> Print Without Bills
                    </Button>
                    {frontEndPermissions.manifests.create &&
                        <Button variant='warning' onClick={regather}><i className='fas fa-sync'></i> Regather</Button>
                    }
                </ButtonGroup>
            </Col>
            <Col md={11}>
                <hr/>
            </Col>
            <Col md={11}>
                <table style={{width: '100%'}}>
                    <tbody>
                        <tr>
                            <th style={{...headerTDStyle, backgroundColor: '#ADD8E6'}} key='driver-gross'>
                                {`Driver Gross\n$${data?.driver_total}`}
                            </th>
                            <th style={{...headerTDStyle, backgroundColor: 'orange'}} key='chargeback-total'>
                                {`Chargebacks\n$${data?.chargeback_total}`}
                            </th>
                            <th style={{...headerTDStyle, backgroundColor: '#ADD8E6'}} key='driver-income'>
                                {`Driver Income\n$${data?.driver_income}`}
                            </th>
                            <th style={{width: '40%', textAlign: 'center'}} key='employee'>
                                <LinkContainer to={'/employees/' + employee?.employee_id}>
                                    <h3>
                                        <a href=''>
                                            {`${employee?.employee_number} - ${employee?.company_name ?? contact.display_name}`}
                                        </a>
                                    </h3>
                                </LinkContainer>
                            </th>
                        </tr>
                    </tbody>
                </table>
            </Col>
            <Col md={11}>
                <hr/>
            </Col>
            {warnings &&
                <Col md={11}>
                    <table style={{width: '100%'}}>
                        <thead>
                            <tr>
                                {warnings.map(warning =>
                                    <th
                                        style={{
                                            border: '1px solid black',
                                            textAlign: 'center',
                                            background: warning.type === 'error' ? 'tomato' : 'gold'
                                        }}
                                        width={(1 / warnings.length * 100) + '%'}
                                        key={warning.friendlyString}
                                    >{warning.friendlyString}</th>
                                )}
                            </tr>
                        </thead>
                    </table>
                </Col>
            }
            <Col md={11} style={{textAlign: 'center'}}>
                <h4>{`${manifest?.start_date} to ${manifest?.end_date}`}</h4>
                <h4>Driver Statement</h4>
                <Table striped size='sm'>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Pickups</th>
                            <th>Deliveries</th>
                            <th>Pickup Income</th>
                            <th>Delivery Income</th>
                            <th>Driver Income</th>
                        </tr>
                    </thead>
                    <tbody>
                        {overview?.map(day =>
                            <tr key={day.time_pickup_scheduled}>
                                <td>{day.time_pickup_scheduled}</td>
                                <td>{day.pickup_count}</td>
                                <td>{day.delivery_count}</td>
                                <td>{day.pickup_amount.toLocaleString('en-US', {style: 'currency', currency: 'USD'})}</td>
                                <td>{day.delivery_amount.toLocaleString('en-US', {style: 'currency', currency: 'USD'})}</td>
                                <td>{(day.pickup_amount + day.delivery_amount).toLocaleString('en-US', {style: 'currency', currency: 'USD'})}</td>
                            </tr>
                        )}
                    </tbody>
                </Table>
                <h4>Chargebacks</h4>
                <Table striped size='sm'>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>GL Code</th>
                            <th>Description</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        {chargebacks?.map(chargeback =>
                            <tr key={chargeback.chargeback_id}>
                                <td>{chargeback.name}</td>
                                <td>{chargeback.gl_code}</td>
                                <td>{chargeback.description}</td>
                                <td>{chargeback.amount.toLocaleString('en-US', {style: 'currency', currency: 'USD'})}</td>
                            </tr>
                        )}
                    </tbody>
                </Table>
                <h4>Detailed</h4>
                <Table striped size='sm'>
                    <thead>
                        <tr>
                            <th>Bill ID</th>
                            <th>Date</th>
                            <th>Delivery Type</th>
                            <th>Direction</th>
                            <th>Bill Gross</th>
                            <th>Driver Income</th>
                        </tr>
                    </thead>
                    <tbody>
                        {bills?.map(bill =>
                            <tr key={bill.bill_id}>
                                <LinkContainer to={`/bills/${bill.bill_id}`}><td><a href=''>{bill.bill_id}</a></td></LinkContainer>
                                <td>{bill.time_pickup_scheduled}</td>
                                <td>{bill.delivery_type}</td>
                                <td>{bill.type}</td>
                                <td>{Number(bill.amount).toLocaleString('en-US', {style: 'currency', currency: 'USD'})}</td>
                                <td>{bill.driver_income.toLocaleString('en-US', {style: 'currency', currency: 'USD'})}</td>
                            </tr>
                        )}
                    </tbody>
                </Table>
            </Col>
        </Row>
    )
}
