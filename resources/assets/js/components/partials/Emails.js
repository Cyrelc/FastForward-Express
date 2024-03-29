import React, {useEffect} from 'react'
import {FormControl, ButtonGroup, Button, Table} from 'react-bootstrap'
import Select from 'react-select'

const emailTypesTitle = 'Email types are used to identify which users would like communications.\n\n' +
'Want to receive emails when your invoice is ready? Set yourself to "Billing".\n\n' +
'Are you the point of contact for when we have trouble making a delivery? Set yourself to "Support"'

export default function Emails(props) {
    const {
        emailAddresses,
        emailTypes,
        readOnly,
        setEmailAddresses
    } = props

    function addEmail() {
        const emails = emailAddresses.concat([{email: '', is_primary: props.emailAddresses.length === 0, type: ''}])
        setEmailAddresses(emails)
    }

    /**
     * If the email exists in database (has email_address_id) then mark it as to be deleted,
     * Otherwise if it is a new email_address that they have changed their mind about, simply filter it out
     */
    function deleteEmail(emailIndex) {
        if(props.emailAddresses.length <= 1)
            return
        const emails = emailAddresses.map((email, index) => {
            //precautionary check to never delete the primary email address
            if(!email.is_primary && index == emailIndex && email.email_address_id)
                return {...email, delete: true}
            return email
        }).filter((email, index) => {
            if(email.is_primary || index != emailIndex || email.email_address_id)
                return true
            return false
        })
        setEmailAddresses(emails)
    }

    function handleEmailChange(event) {
        const {name, type, value} = event.target
        const emailIndex = event.target.dataset.emailIndex
        const emails = emailAddresses.map((email, index) => {
            if(index == emailIndex)
                return {...email, [name]: value}
            return email
        })
        setEmailAddresses(emails)
    }

    function setPrimaryEmail(emailIndex) {
        const emails = emailAddresses.map((email, index) => {
            if(index == emailIndex)
                return {...email, is_primary: 1}
            return {...email, is_primary: 0}
        })
        setEmailAddresses(emails)
    }

    return (
        <Table striped bordered size='sm'>
            <thead>
                <tr>
                    <td style={{minWidth: '90px', width: '90px'}}>
                        {!props.readOnly &&
                            <Button variant='success' onClick={addEmail} size='sm'>
                                <span>
                                    <i className='fas fa-plus' style={{paddingRight: 5}}></i><i className='fas fa-at'></i>
                                </span>
                            </Button>
                        }
                    </td>
                    <td><label>Email address</label></td>
                    {emailTypes?.length > 0 && 
                        <td><label>Type <i className='fas fa-question-circle' title={emailTypesTitle}></i></label></td>
                    }
                </tr>
            </thead>
            <tbody>
                {emailAddresses?.map((email, index) => {
                    if(!email.delete)
                        return (
                            <tr key={index}>
                                <td>
                                    <ButtonGroup size='sm'>
                                        <Button
                                            title='Set as primary'
                                            disabled={email.is_primary || readOnly}
                                            onClick={() => setPrimaryEmail(index)}
                                        >
                                            <i className={email.is_primary ? 'fas fa-star' : 'far fa-star'}></i>
                                        </Button>
                                        <Button
                                            title='Delete'
                                            variant='danger'
                                            disabled={email.is_primary || readOnly}
                                            onClick={() => deleteEmail(index)}
                                        >
                                                <i className='fas fa-trash'></i>
                                        </Button>
                                    </ButtonGroup>
                                </td>
                                <td>
                                    <FormControl
                                        data-email-index={index}
                                        name='email'
                                        onChange={handleEmailChange}
                                        placeholder='email@address.domain'
                                        readOnly={readOnly}
                                        value={email.email}
                                    />
                                </td>
                                {emailTypes?.length > 0 &&
                                    <td width='40%'>
                                        <Select
                                            options={emailTypes}
                                            value={email.type}
                                            onChange={value => handleEmailChange({target: {name: 'type', type: 'string', value: value, dataset: {emailIndex: index}}})}
                                            isMulti
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
