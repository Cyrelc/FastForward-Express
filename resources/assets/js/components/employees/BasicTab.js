import React from 'react'
import {Card} from 'react-bootstrap'

import Contact from '../partials/Contact'
import EmergencyContacts from './EmergencyContacts'

export default function BasicTab(props) {
    return (
        <Card border='dark'>
            <Card.Header>
                <Contact
                    key={props.contact.contactId}
                    contact={props.contact}
                    address={props.address}
                    emailAddressesToDelete={props.emailAddressesToDelete}
                    handleContactChange={props.handleContactChange}
                    readOnly={props.readOnly}
                    showAddress
                />
            </Card.Header>
            {(props.action != 'create' && props.emergencyContacts) &&
                <Card.Body>
                    <EmergencyContacts
                        emergencyContacts={props.emergencyContacts}
                        employeeId={props.employeeId}
                        setEmergencyContacts={props.setEmergencyContacts}
                    />
                </Card.Body>
            }
        </Card>
    )
}


