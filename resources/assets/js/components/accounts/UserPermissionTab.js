import React from 'react'
import {Col, Row} from 'react-bootstrap'

export default function UserPermissionTab(props) {
    return (
        <Row>
            <Col md={6}>
                <p>User permissions apply to the following accounts:</p>
                <ul>
                    {props.belongsTo.map(account =>
                        <li>{account.account_number + ' - ' + account.name}</li>
                    )}
                </ul>
            </Col>
        </Row>
    )
}
