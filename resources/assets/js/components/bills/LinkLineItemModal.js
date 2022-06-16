import React, {createRef, useState} from 'react'
import {Alert, Button, Col, FormControl, InputGroup, Modal, Row} from 'react-bootstrap' 

export default function LinkLineItemModal(props) {
    const [targetObject, setTargetObject] = useState(undefined)
    const [searchValue, setSearchValue] = useState(undefined)
    const [targetId, setTargetId] = useState(undefined)
    const searchTextFieldRef = createRef()

    const {linkLineItemCell, linkLineItemToType, show} = props

    const searchLinkTo = () => {
        if (linkLineItemToType === 'Invoice') {
            makeAjaxRequest(`/invoices/getModel/${searchValue}`, 'GET', null, response => {
                response = JSON.parse(response)
                setTargetObject(response)
                setTargetId(response.invoice.invoice_id)
            })
        } else if (linkLineItemToType === 'Pickup Manifest' || linkLineItemToType == 'Delivery Manifest') {
            makeAjaxRequest(`/manifests/${searchValue}`, 'GET', null, response => {
                response = JSON.parse(response)
                setTargetObject(response)
                setTargetId(response.manifest.manifest_id)
            })
        }
    }

    const submitLinkTo = () => {
        const data = {
            action: 'create_link',
            line_item_id: linkLineItemCell.getRow().getData().line_item_id,
            link_type: linkLineItemToType,
            link_to_target_id: targetId
        }

        makeAjaxRequest('/bills/manageLineItemLinks', 'POST', data, response => {
            linkLineItemCell.getRow().update(JSON.parse(response))

            setSearchValue(undefined)
            setTargetObject(undefined)
            setTargetId(undefined)
            props.hide()
        })
    }

    return (
        <Modal
            autoFocus={false}
            onHide={props.hide}
            onShow={() => searchTextFieldRef.current.focus()}
            show={show}
        >
            <Modal.Header closeButton><Modal.Title>Link Line Item to {linkLineItemToType}</Modal.Title></Modal.Header>
            <Modal.Body>
                <Row>
                    <Col md={12}>
                        <InputGroup>
                            <InputGroup.Text>{linkLineItemToType} ID: </InputGroup.Text>
                            <FormControl
                                autoFocus={show}
                                name='searchValue'
                                value={searchValue}
                                onKeyPress={(event) => {
                                    if(event.key === 'Enter')
                                        searchLinkTo()
                                }}
                                onChange={event => setSearchValue(event.target.value)}
                                ref={searchTextFieldRef}
                            />
                        </InputGroup>
                    </Col>
                </Row>
                {targetObject &&
                    <Row>
                        <Col md={12}>
                            {targetObject?.manifest ?
                                'Manifest ID: ' + targetObject.manifest.manifest_id :
                                'Invoice ID: ' + targetObject.invoice.invoice_id
                            }
                        </Col>
                        <Col md={12}>
                            {targetObject?.manifest ?
                                'Driver: ' + targetObject.employee.contact.first_name + " " + targetObject.employee.contact.last_name :
                                'Account: ' + targetObject.parent.name
                            }
                        </Col>
                        {(targetObject?.invoice && targetObject?.invoice.finalized) &&
                            <Col md={12}>
                                <Alert variant='warning'>
                                    WARNING - Selected invoice has been finalized, this line item will be attached as an amendment
                                </Alert>
                            </Col>
                        }
                    </Row>
                }
            </Modal.Body>
            <Modal.Footer className='justify-content-md-center'>
                <Button variant='light' onClick={props.hide}>Cancel</Button>
                <Button variant='warning' onClick={searchLinkTo} disabled={!searchValue?.length}><i className='fas fa-search'></i> Search</Button>
                <Button variant='success' onClick={submitLinkTo} disabled={!targetObject}><i className='fas fa-save'></i> Submit</Button>
            </Modal.Footer>
        </Modal>
    )
}

