import React, {useState} from 'react'
import {Button, Card, Col, FormControl, InputGroup, Row, Table} from 'react-bootstrap'
import DatePicker from 'react-datepicker'

import {useAPI} from '../../contexts/APIContext'

export default function SchedulingTab(props) {
    const [blockedDate, setBlockedDate] = useState(new Date())
    const [blockedDateName, setBlockedDateName] = useState('')

    const api = useAPI()

    const addBlockedDate = () => {
        const data = {date: blockedDate.toLocaleString('en-CA'), name: blockedDateName}
        api.post(`/appsettings/scheduling/blockedDates`, data)
            .then(response => {
                props.setBlockedDates(response.blocked_dates.map(date => {
                    return {
                        ...date,
                        date: new Date(date.value)
                    }
                }))
                setBlockedDate(new Date())
                setBlockedDateName('')
            })
    }

    const deleteBlockedDate = blockedDate => {
        if(confirm(`You are about to delete date ${blockedDate.name}. This action can not be undone`)) {
            api.delete(`/appsettings/scheduling/blockedDates/${blockedDate.id}`, null)
                .then(response => {

                    const blockedDates = response.blocked_dates.map(blocked => {
                        return {...blocked, date: new Date(blocked.value)}
                    })
                    props.setBlockedDates(blockedDates)
                })
            }
    }

    return (
        <Card>
            <Card.Header><Card.Title>Scheduling</Card.Title></Card.Header>
            <Card.Body>
                <Row>
                    <Col md={2}>
                        <h5 className='text-muted'>
                            {`Holidays  `}
                            <i
                                className='fas fa-question-circle'
                                title={`Specifies the dates which qualify as holidays.\n\t- Recurring functions will NOT be run (i.e. recurring bills)\n\t- Bill requests on these dates will count as "holidays" for pricing purposes`}
                            />
                        </h5>
                    </Col>
                    <Col md={10}>
                        <Table>
                            <thead>
                                <tr>
                                    <td></td>
                                    <td>Date</td>
                                    <td>Name</td>
                                </tr>
                            </thead>
                            <thead>
                                <tr>
                                    <td><Button variant='primary' onClick={addBlockedDate}>Add</Button></td>
                                    <td>
                                        <InputGroup>
                                            <InputGroup.Text>Date: </InputGroup.Text>
                                            <DatePicker
                                                className='form-control'
                                                selected={blockedDate}
                                                onChange={value => setBlockedDate(value)}
                                                wrapperClassName='form-control'
                                            />
                                        </InputGroup>
                                    </td>
                                    <td>
                                        <InputGroup>
                                            <InputGroup.Text>Name</InputGroup.Text>
                                            <FormControl
                                                name='blockedDateName'
                                                value={blockedDateName}
                                                placeholder='Holiday name / Reason'
                                                onChange={event => setBlockedDateName(event.target.value)}
                                            />
                                        </InputGroup>
                                    </td>
                                </tr>
                            </thead>
                            <tbody>
                                {props.blockedDates && props.blockedDates.map(date =>
                                    <tr key={date.date}>
                                        <td>
                                            <Button variant='danger' size='sm' onClick={() => deleteBlockedDate(date)}>
                                                <i className='fas fa-trash'></i>
                                            </Button>
                                        </td>
                                        <td>{date.date.toLocaleDateString('en-CA')}</td>
                                        <td>{date.name}</td>
                                    </tr>
                                )}
                            </tbody>
                        </Table>
                    </Col>
                </Row>
            </Card.Body>
        </Card>
    )
}
