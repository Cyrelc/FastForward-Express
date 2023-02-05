import React, {useEffect, useState} from 'react'
import {Button, Card, Col, Form, Row} from 'react-bootstrap'
import {connect} from 'react-redux'

const UserSettings = (props) => {
    const [defaultImperial, setDefaultImperial] = useState(false)

    useEffect(() => {
        console.log(props.userSettings)
        setDefaultImperial(props.userSettings.use_imperial_default == 1)
    }, [props.userSettings])

    const storeUserSettings = () => {
        const data = {
            use_imperial_default: defaultImperial
        }

        makeAjaxRequest('/users/settings', 'POST', data, response => {
            console.log(response)
        })
    }

    return (
        <Card>
            <Card.Header>
                <Card.Title className='text-muted'>User Settings</Card.Title>
            </Card.Header>
            <Card.Body>
                <Row>
                    <Col md={2}>
                        <h5 className='text-muted'>Units</h5>
                    </Col>
                    <Col md={2}>
                        <Form.Check
                            reverse
                            checked={defaultImperial}
                            type='switch'
                            label='Use Imperial By Default'
                            onClick={() => setDefaultImperial(!defaultImperial)}
                        ></Form.Check>
                    </Col>
                    <Col md={8}>
                        <p className='text-muted'>
                            By default, all calculations are in metric (kilograms, meters, kilometers, etc.)<br/>
                            Enabling this will allow you to input all measurements in imperial, and have them converted for you behind the scenes
                        </p>
                    </Col>
                </Row>
                <hr/>
            </Card.Body>
            <Card.Footer style={{textAlign: 'center'}}>
                <Button variant='primary' onClick={storeUserSettings}>Submit</Button>
            </Card.Footer>
        </Card>
    )
}


const mapStateToProps = store => {
    return {
        userSettings: store.user.userSettings,
    }
}

export default connect(mapStateToProps)(UserSettings)

