import React from 'react'
import {Button, Col, FormControl, InputGroup, Modal, Row} from 'react-bootstrap'

import Address from '../partials/Address'

export default function InterlinerModal(props) {
    return (
        <Modal show={props.show} onHide={props.toggleModal} size='lg'>
            <Modal.Header closeButton><Modal.Title>{props.interlinerId ? 'Edit Interliner' : 'Create Interliner'}</Modal.Title></Modal.Header>
            <Modal.Body>
                <Row>
                    <Col md={11}>
                        <InputGroup>
                            <InputGroup.Text>Name </InputGroup.Text>
                            <FormControl name='interlinerName' value={props.interlinerName} onChange={props.handleChanges}/>
                        </InputGroup>
                    </Col>
                    <Col md={11}>
                        <Address
                            id='interliner'
                            address={props.interlinerAddress}
                            showAddressSearch={true}

                            handleChanges={props.handleChanges}
                        />
                    </Col>
                </Row>
                <Row className='justify-content-md-center'>
                    <Button onClick={props.storeInterliner} variant='primary'>Submit</Button>
                </Row>
            </Modal.Body>
        </Modal>
    )
}
