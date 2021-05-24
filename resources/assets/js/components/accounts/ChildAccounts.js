import React from 'react'
import {Card, Col, Row} from 'react-bootstrap'
import { LinkContainer } from 'react-router-bootstrap'

export default function ChildAccounts(props) {
    return (
        <Card>
            <Card.Header>
                <Row>
                    <Col md={2}>
                        <h4 className='text-muted'>Child Accounts</h4>
                    </Col>
                </Row>
            </Card.Header>
            <Card.Body>
                <Row>
                    <Col md={11}>
                        <ul>
                            {props.childAccountList.map(account =>
                                <li>
                                    <LinkContainer to={'/app/accounts/' + account.account_id}>
                                        <a>{account.account_number + " - " + account.name}</a>
                                    </LinkContainer>
                                </li>
                                )
                            }
                        </ul>
                    </Col>
                </Row>
            </Card.Body>
        </Card>
    )
}
