import React, {useState} from 'react'
import {Button, Col, FormControl, InputGroup, Modal, Row} from 'react-bootstrap'

import Address from '../partials/AddressFunctional'

export default function InterlinerModal(props) {
    const [addressType, setAddressType] = useState('Search')
    // here because the address is used across components, we convert the old event driven handleChange to the individual useState pieces
    const handleChange = (event) => {
        const {name, value} = event.target
        switch(name) {
            case 'addressLat':
                props.setAddressLat(value)
                break;
            case 'addressLng':
                props.setAddressLng(value)
                break;
            case 'placeId':
                props.setPlaceId(value)
                break
            case 'addressName':
                props.setAddressName(value)
                break
            case 'addressFormatted':
                props.setAddressFormatted(value)
                break
            case 'addressType':
                setAddressType(value)
                break
        }
    }

    return (
        <Modal show={props.show} onHide={props.toggleModal} size='lg'>
            <Modal.Header closeButton><Modal.Title>{props.interlinerId ? 'Edit Interliner' : 'Create Interliner'}</Modal.Title></Modal.Header>
            <Modal.Body>
                <Row>
                    <Col md={11}>
                        <InputGroup>
                            <InputGroup.Text>Name </InputGroup.Text>
                            <FormControl name='interlinerName' value={props.interlinerName} onChange={props.setInterlinerName}/>
                        </InputGroup>
                    </Col>
                    <Col md={11}>
                        <Address
                            id='interliner'
                            data={{...props.interlinerAddress, type: addressType}}
                            showAddressSearch={true}
                            readOnly={false}

                            handleChange={handleChange}
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
