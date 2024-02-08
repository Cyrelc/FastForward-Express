import React from 'react'
import {Button, Card, Table} from 'react-bootstrap'
import { LinkContainer } from 'react-router-bootstrap'

export default function RatesheetsTab(props) {
    return (
        <Card border='dark'>
            <Card.Header>
                <h4 className='text-muted'>Ratesheets</h4>
            </Card.Header>
            <Card.Body>
                <Table>
                    <thead>
                        <tr>
                            <th>
                                <LinkContainer to='/ratesheets/create'>
                                    <Button variant='success'><i className='fas fa-plus'></i> Create Ratesheet</Button>
                                </LinkContainer>
                            </th>
                            <th>Ratesheet ID</th>
                            <th>Ratesheet Name</th>
                        </tr>
                    </thead>
                    <tbody>
                        {props.ratesheets.map(ratesheet =>
                            <tr key={ratesheet.ratesheet_id}>
                                <td>
                                    <LinkContainer to={`/ratesheets/${ratesheet.ratesheet_id}`}>
                                        <Button variant='warning'><i className='fas fa-edit'></i></Button>
                                    </LinkContainer>
                                </td>
                                <td>{ratesheet.ratesheet_id}</td>
                                <td>{ratesheet.name}</td>
                            </tr>
                        )}
                    </tbody>
                </Table>
            </Card.Body>
        </Card>
    )
}
