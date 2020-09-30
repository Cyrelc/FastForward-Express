import React from 'react'
import {FormControl, ButtonGroup, Button, Table} from 'react-bootstrap'
import Select from 'react-select'

export default function Emails(props) {
    function addEmail() {
        const emails = props.emailAddresses
        emails[emails.length] = {email: '', is_primary: emails.length === 0}
        props.handleChanges({target: {name: 'emailAddresses', type: 'objects', value: emails}})
    }

    /**
     * If the email exists in database (has email_address_id) then mark it as to be deleted,
     * Otherwise if it is a new email_address that they have changed their mind about, simply filter it out
     */
    function deleteEmail(emailIndex) {
        if(props.emailAddresses.length <= 1)
            return
        const emails = props.emailAddresses.map((email, index) => {
            //precautionary check to never delete the primary email address
            if(!email.is_primary && index == emailIndex && email.email_address_id)
                return {...email, delete: true}
            return email
        }).filter((email, index) => {
            if(email.is_primary || index != emailIndex || email.email_address_id)
                return true
            return false
        })
        props.handleChanges({target: {name: 'emailAddresses', type: 'objects', value: emails}})
    }

    function handleEmailChange(event) {
        const {name, type, value} = event.target
        const emailIndex = event.target.dataset.emailIndex
        const emails = props.emailAddresses.map((email, index) => {
            if(index == emailIndex)
                return {...email, [name]: value}
            return email
        })
        props.handleChanges({target: {name: 'emailAddresses', type: 'objects', value: emails}})
    }

    function setPrimaryEmail(emailIndex) {
        const emails = props.emailAddresses.map((email, index) => {
            if(index == emailIndex)
                return {...email, is_primary: 1}
            return {...email, is_primary: 0}
        })
        props.handleChanges({target: {name: 'emailAddresses', type: 'objects', value: emails}})
    }

    return (
        <Table striped bordered>
            <thead>
                <tr>
                    <td style={{width:100}}>
                        {!props.readOnly &&
                            <Button variant='success' onClick={addEmail}>
                                <span><i className='fas fa-plus' style={{paddingRight: 5}}></i><i className='fas fa-at'></i></span>
                            </Button>
                        }
                    </td>
                    <td><label>Email address</label></td>
                    {props.emailTypes && 
                        <td><label>Type</label></td>
                    }
                </tr>
            </thead>
            <tbody>
                {props.emailAddresses && props.emailAddresses.map((email, index) => {
                    if(!email.delete)
                        return (
                            <tr key={index}>
                                <td>
                                    <ButtonGroup>
                                        <Button title='Set as primary' disabled={email.is_primary || props.readOnly} onClick={() => setPrimaryEmail(index)}><i className={email.is_primary ? 'fas fa-star' : 'far fa-star'}></i></Button>
                                        <Button title='Delete' variant='danger' disabled={email.is_primary || props.readOnly} onClick={() => deleteEmail(index)}><i className='fas fa-trash'></i></Button>
                                    </ButtonGroup>
                                </td>
                                <td>
                                    <FormControl
                                        data-email-index={index}
                                        name='email'
                                        onChange={handleEmailChange}
                                        placeholder='email@address.domain'
                                        readOnly={props.readOnly}
                                        value={email.email}
                                    />
                                </td>
                                {props.emailTypes &&
                                    <td>
                                        <Select
                                            options={props.emailTypes}
                                            getOptionLabel={type => type.name}
                                            getOptionValue={type => type.value}
                                            value={props.emailTypes.filter(type = type.value == email.type)}
                                            onChange={value => handleEmailChange({target: {name: 'type', type: 'string', value: value.value}})}
                                        />
                                    </td>
                                }
                            </tr>
                        )
                    }
                )}
            </tbody>
        </Table>
    )
}
