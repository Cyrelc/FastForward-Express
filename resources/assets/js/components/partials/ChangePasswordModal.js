import React, {Component} from 'react'
import {Button, ButtonGroup, Col, FormControl, InputGroup, Modal, Row, ToastHeader} from 'react-bootstrap'

export default class ChangePasswordModal extends Component {
    constructor() {
        super()
        this.state = {
            confirmPassword: '',
            newPassword: '',
            viewPassword: false
        }
        this.generatePassword = this.generatePassword.bind(this)
        this.handleChange = this.handleChange.bind(this)
        this.submitChangePassword = this.submitChangePassword.bind(this)
    }

    generatePassword() {
        makeFetchRequest('https://makemeapassword.ligos.net/api/v1/passphrase/json?wc=4&whenUp=StartOfWord&ups=2', data => {
            this.setState({
                newPassword: data.pws,
                confirmPassword: data.pws,
                viewPassword: true
            })
        })
    }

    handleChange(event) {
        const {name, value} = event.target
        this.setState({[name]: value})
    }

    submitChangePassword() {
        const data = {
            password: this.state.newPassword,
            password_confirm: this.state.confirmPassword
        }
        makeAjaxRequest('/users/changePassword/' + this.props.userId, 'POST', data, response => {
            toastr.clear()
            this.props.toggleModal()
            toastr.success('Password was successfully changed', 'Success')
        })
    }

    render() {
        return(
            <Modal show={this.props.show} onHide={this.props.toggleModal} size='lg'>
                <Modal.Header closeButton>
                    <Modal.Title>Change Password</Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <Row>
                        <Col md={12}>
                            <p>Please enter a new password. This action <strong>can not</strong> be undone.</p>
                            <hr/>
                            <p>Passwords must:</p>
                            <ol>
                                <li>Be at least eight characters long</li>
                                <li>Contain one uppercase and one lowercase character</li>
                                <li>Be more than 20 characters long, or contain either a number or a special character</li>
                            </ol>
                            <hr/>
                            <p>For strong and secure password creation tips, please see <a href='https://xkcd.com/936' target='none'>xkcd.com/936</a></p>
                        </Col>
                        <Col md={11}>
                            <InputGroup>
                                <InputGroup.Prepend><InputGroup.Text>New Password: </InputGroup.Text></InputGroup.Prepend>
                                <FormControl
                                    name='newPassword'
                                    type={this.state.viewPassword ? '' : 'password'}
                                    value={this.state.newPassword}
                                    onChange={this.handleChange}
                                />
                            </InputGroup>
                        </Col>
                        <Col md={11}>
                            <InputGroup>
                                <InputGroup.Prepend><InputGroup.Text>Confirm Password: </InputGroup.Text></InputGroup.Prepend>
                                <FormControl
                                    name='confirmPassword'
                                    type={this.state.viewPassword ? '' : 'password'}
                                    value={this.state.confirmPassword}
                                    onChange={this.handleChange}
                                />
                            </InputGroup>
                        </Col>
                    </Row>
                </Modal.Body>
                <Modal.Footer className='justify-content-md-center'>
                    <ButtonGroup>
                        <Button variant='dark' onClick={this.generatePassword}><i className='fas fa-dice-d20 fa-lg'></i> Generate Password</Button>
                        <Button variant='light' onClick={this.props.toggleModal}>Cancel</Button>
                        <Button variant='success' onClick={this.submitChangePassword}><i className='fas fa-save'></i> Submit</Button>
                    </ButtonGroup>
                </Modal.Footer>
            </Modal>
        )
    }
}
