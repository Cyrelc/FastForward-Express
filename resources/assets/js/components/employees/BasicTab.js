import React from 'react'
import {Card, Col, Row} from 'react-bootstrap'

import Contact from '../partials/Contact'
import EmergencyContacts from './EmergencyContacts'

export default function BasicTab(props) {
    return (
        <Card border='dark'>
            <Card.Header>
                <Contact
                    key={props.contact_id}
                    addressId={'employee'}
                    firstName={props.firstName}
                    lastName={props.lastName}
                    position={props.position}
                    address={props.address}
                    phoneNumbers={props.phoneNumbers}
                    phoneNumbersToDelete={props.phoneNumbersToDelete}
                    phoneTypes={props.phoneTypes}
                    emailAddresses={props.emailAddresses}
                    emailAddressesToDelete={props.emailAddressesToDelete}
                    handleChanges={props.handleChanges}
                    readOnly={props.readOnly}
                    showAddress
                />
            </Card.Header>
            {(props.action != 'create' && props.emergencyContacts) &&
                <Card.Body>
                    <EmergencyContacts
                        emergencyContacts={props.emergencyContacts}
                        employeeId={props.employeeId}
                        handleChanges={props.handleChanges}
                    />
                </Card.Body>
            }
        </Card>
    )
}


