import React, {useEffect, useState} from 'react'

import {Button, ButtonGroup, Col, FormControl, InputGroup, Modal, Row, Table} from 'react-bootstrap'
import DatePicker from 'react-datepicker'

export default function ManageCreditCardsModal(props) {
    const [ccNumber, setCCNumber] =  useState('')
    const [creditCards, setCreditCards] = useState([])
    const [expiryDate, setExpiryDate] = useState(null)
    const [isLoading, setIsLoading] = useState(true)
    const [locked, setLocked] = useState(true)
    const [showCreateSection, setShowCreateSection] = useState(false)
    const [street, setStreet] = useState('')
    const [streetNumber, setStreetNumber] = useState('')
    const [zipPostal, setZipPostal] = useState('')

    const processCard = (card) => {
        card.expiry_date = new Date(card.expiry_date.date)
        const today = new Date()
        if(today.getTime() > card.expiry_date.getTime())
            card.expired = true

        switch(card.payment_type_name) {
            case 'American Express':
                return {...card, icon:<i className='fab fa-cc-amex fa-2x'></i>}
            case 'Visa':
                return {...card, icon:<i className='fab fa-cc-visa fa-2x'></i>}
            case 'Mastercard':
                return {...card, icon:<i className='fab fa-cc-mastercard fa-2x'></i>}
        }
    }

    const clearForm = () => {
        setCCNumber('')
        setExpiryDate(null)
        setStreetNumber('')
        setStreet('')
        setZipPostal('')
        setShowCreateSection(false)
    }

    const deleteCreditCard = (card) => {
        if(confirm(`Are you sure you would like to delete the credit card ending in ${card.masked_pan.slice(-4)}? This action can not be undone`)) {
            makeAjaxRequest(`/payments/deleteCreditCard/${card.credit_card_id}`, 'GET', null, response => {
                fetchCreditCards()
            })
        }
    }

    const fetchCreditCards = () => {
        setIsLoading(true)
        makeAjaxRequest(`/payments/getCreditCards/${props.accountId}`, 'GET', null, response => {
            response = JSON.parse(response)
            setCreditCards(response.map(card => {
                return processCard(card)
            }))
            setIsLoading(false)
        })
    }

    const hideForm = () => {
        clearForm()
        setLocked(true)
        props.hide()
    }

    useEffect(() => {
        if(props.show)
            fetchCreditCards()
        else
            setCreditCards([])
    }, [props.show])

    const storeCreditCard = () => {
        const data = {
            'account_id': props.accountId,
            'expiry_date': expiryDate.toLocaleString('en-US'),
            'pan': ccNumber,
            'street': street,
            'street_number': streetNumber,
            'zip_postal': zipPostal
        }

        makeAjaxRequest('/payments/storeCreditCard', 'POST', data, response => {
            toastr.success('Credit card saved')
            clearForm()
            fetchCreditCards()
        })
    }

    return (
        <Modal show={props.show} onHide={hideForm} size='lg'>
            <Modal.Header closeButton>
                <Modal.Title> Manage Credit Cards</Modal.Title>
            </Modal.Header>
            <Modal.Body>
                <Button variant='success' size='sm' onClick={() => setShowCreateSection(true)}><i className='fas fa-plus'></i> Add New</Button>
            </Modal.Body>
            {showCreateSection &&
                <Modal.Body>
                    <Row>
                        <Col md={8}>
                            <InputGroup>
                                <InputGroup.Text>CC Number: </InputGroup.Text>
                                <FormControl
                                    onChange={event => setCCNumber(event.target.value)}
                                    type='number'
                                    value={ccNumber}
                                />
                            </InputGroup>
                        </Col>
                        <Col md={4}>
                            <InputGroup>
                                <InputGroup.Text>Expiry: </InputGroup.Text>
                                <DatePicker
                                    className='form-control'
                                    dateFormat='MM/yyyy'
                                    onChange={setExpiryDate}
                                    selected={expiryDate}
                                    showMonthYearPicker
                                    wrapperClassName='form-control'
                                />
                            </InputGroup>
                        </Col>
                        <Col md={4} style={{paddingBottom: 12}}>
                            <InputGroup>
                                <InputGroup.Text>Street Number:</InputGroup.Text>
                                <FormControl
                                    onChange={event => setStreetNumber(event.target.value)}
                                    placeholder='(optional)'
                                    value={streetNumber}
                                />
                            </InputGroup>
                        </Col>
                        <Col md={4}>
                            <InputGroup>
                                <InputGroup.Text>Street:</InputGroup.Text>
                                <FormControl
                                    onChange={event => setStreet(event.target.value)}
                                    placeholder='(optional)'
                                    value={street}
                                />
                            </InputGroup>
                        </Col>
                        <Col md={4}>
                            <InputGroup>
                                <InputGroup.Text>Postal:</InputGroup.Text>
                                <FormControl
                                    onChange={event => setZipPostal(event.target.value)}
                                    placeholder='(optional)'
                                    value={zipPostal}
                                />
                            </InputGroup>
                        </Col>
                    </Row>
                    <Row>
                        <Col style={{textAlign: 'center'}}>
                            <ButtonGroup style={{marginBottom: 15}}>
                                <Button onClick={storeCreditCard} variant='success'><i className='fas fa-save'></i> Save</Button>
                                <Button onClick={clearForm} variant='light'>Cancel</Button>
                            </ButtonGroup>
                        </Col>
                    </Row>
                    <Row>
                        <hr/>
                    </Row>
                </Modal.Body>
            }
            <Modal.Body>
                {isLoading ?
                    <Row className='justify-content-md-center'>
                        <Col md={3}><h4><i className='fas fa-cog fa-spin'></i>  Loading...</h4></Col>
                    </Row> :
                    <Row>
                        <Col md={12}>
                            <Table striped bordered size='sm'>
                                <thead>
                                    <tr>
                                        <th width={50}>
                                            <Button variant='warning' size='sm' onClick={() => setLocked(!locked)}>
                                                <i className={locked ? 'fas fa-lock-open' : 'fas fa-lock'} size='lg'></i>
                                            </Button>
                                        </th>
                                        <th width={50}></th>
                                        <th>Card #</th>
                                        <th>Expiry</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {creditCards.map(card =>
                                        <tr key={card.masked_pan}>
                                            <td>
                                                <Button variant='danger' onClick={() => deleteCreditCard(card)} disabled={locked} size='sm'>
                                                    <i className='fas fa-trash'></i>
                                                </Button>
                                            </td>
                                            <td>{card.icon}</td>
                                            <td style={card.expired ? {color: 'red'} : {}}>{card.masked_pan}</td>
                                            <td style={card.expired ? {color: 'red'} : {}}>{card.expiry_date.toLocaleDateString('en-US', {month: '2-digit', year: 'numeric'})}</td>
                                        </tr>
                                    )}
                                </tbody>
                            </Table>
                        </Col>
                    </Row>
                }
            </Modal.Body>
            <Modal.Footer className='justify-content-md-center'>
                <Button variant='light' onClick={hideForm}>Cancel</Button>
            </Modal.Footer>
        </Modal>
    )
}
