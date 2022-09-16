import React, {useState} from 'react'
import {Button, ButtonGroup, Col, FormControl, InputGroup, Modal, Row} from 'react-bootstrap'

export default function ChangePasswordModal(props) {
    const [newPassword, setNewPassword] = useState('')
    const [confirmPassword, setConfirmPassword] = useState('')
    const [viewPassword, setViewPassword] = useState(false)

    const generatePassword = () => {
        makeFetchRequest('https://makemeapassword.ligos.net/api/v1/passphrase/json?wc=4&whenUp=StartOfWord&ups=2&minCh=20', data => {
            setNewPassword(data.pws[0])
            setConfirmPassword(data.pws[0])
            setViewPassword(true)
        })
    }

    const sendResetPasswordEmail = () => {
        makeAjaxRequest(`/users/sendPasswordReset/${props.userId}`, 'GET', null, response => {
            toastr.success(`Password reset email sent to ${response.email}`, 'Success', {
                'positionClass': 'toast-top-center'
            })
            props.toggleModal()
        })
    }

    const submitChangePassword = () => {
        const data = {
            password: newPassword,
            password_confirmation: confirmPassword
        }
        makeAjaxRequest(`/users/changePassword/${props.userId}`, 'POST', data, response => {
            toastr.clear()
            props.toggleModal()
            toastr.success('Password was successfully changed', 'Success')
        })
    }

    return(
        <Modal show={props.show} onHide={props.toggleModal} size='lg'>
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
                            <li>Be more than 20 characters long, <strong>or</strong> contain a number or special character</li>
                        </ol>
                        <hr/>
                        <p>For strong and secure password creation tips, please see <a href='https://xkcd.com/936' target='none'>xkcd.com/936</a></p>
                    </Col>
                    <Col md={11}>
                        <InputGroup>
                            <InputGroup.Text>New Password: </InputGroup.Text>
                            <FormControl
                                name='newPassword'
                                type={viewPassword ? '' : 'password'}
                                value={newPassword}
                                onChange={event => setNewPassword(event.target.value)}
                            />
                        </InputGroup>
                    </Col>
                    <Col md={11}>
                        <InputGroup>
                            <InputGroup.Text>Confirm Password: </InputGroup.Text>
                            <FormControl
                                name='confirmPassword'
                                type={viewPassword ? '' : 'password'}
                                value={confirmPassword}
                                onChange={event => setConfirmPassword(event.target.value)}
                            />
                        </InputGroup>
                    </Col>
                </Row>
            </Modal.Body>
            <Modal.Footer className='justify-content-md-center'>
                <ButtonGroup>
                    <Button variant='primary' onClick={sendResetPasswordEmail}><i className='fas fa-envelope'> Send Password Reset Email</i></Button>
                    <Button variant='dark' onClick={generatePassword}><i className='fas fa-dice-d20 fa-lg'></i> Generate Password</Button>
                    <Button variant='light' onClick={props.toggleModal}>Cancel</Button>
                    <Button variant='success' onClick={submitChangePassword}><i className='fas fa-save'></i> Submit</Button>
                </ButtonGroup>
            </Modal.Footer>
        </Modal>
    )
}
