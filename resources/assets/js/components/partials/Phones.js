import React from 'react'
import {FormControl, ButtonGroup, Button, Table} from 'react-bootstrap'
import Select from 'react-select'
import MaskedInput from 'react-maskedinput'

export default function Phones(props) {
    function addPhone() {
        const phones = props.phoneNumbers.concat([
            {phone: '', extension: '', type: '', is_primary: props.phoneNumbers.length === 0}
        ])
        props.handleChanges({target: {name: 'phoneNumbers', type: 'objects', value: phones}})
    }

    /**
     * If the phone exists in database (has phone_number_id) then mark it as to be deleted,
     * Otherwise if it is a new phone number that they have changed their mind about, simply filter it out
     */
    function deletePhone(phoneIndex) {
        if(props.phoneNumbers.length <= 1)
            return
        const phones = props.phoneNumbers.map((phone, index) => {
            // precautionary check to never delete the primary phone number 
            if(!phone.is_primary && index == phoneIndex && phone.phone_number_id)
                return {...phone, delete: true}
            return phone
        }).filter((phone, index) => {
            if(phone.is_primary || index != phoneIndex || phone.phone_number_id)
                return true
            return false
        })
        props.handleChanges([{target: {name: 'phoneNumbers', type: 'objects', value: phones}}])
    }

    function handlePhoneChange(event) {
        const {name, value} = event.target
        const phoneIndex = event.target.dataset.phoneIndex
        const phones = props.phoneNumbers.map((phone, index) => {
            if(index == phoneIndex)
                return {...phone, [name]: value}
            return phone
        })
        props.handleChanges({target: {name: 'phoneNumbers', type: 'objects', value: phones}})
    }

    function setPrimaryPhone(phoneIndex) {
        const phones = props.phoneNumbers.map((phone, index) => {
            if(index == phoneIndex)
                return {...phone, is_primary: 1}
            return {...phone, is_primary: 0}
        })
        props.handleChanges({target: {name: 'phoneNumbers', type: 'objects', value: phones}})
    }

    return (
        <Table striped bordered size='sm'>
            <thead>
                <tr>
                    <td style={{minWidth: '90px', width: '90px'}}>
                        {!props.readOnly &&
                            <Button variant='success' onClick={addPhone} size='sm'>
                                <span><i className='fas fa-plus' style={{paddingRight: 5}}></i><i className='fas fa-phone'></i></span>
                            </Button>
                        }
                    </td>
                    <td style={{minWidth: '170px'}}>Phone</td>
                    <td>Extension</td>
                    <td style={{minWidth: '150px'}}>Type</td>
                </tr>
            </thead>
            <tbody>
                {props.phoneNumbers && props.phoneNumbers.map((phone, index) => {
                    if(!phone.delete)
                        return (
                            <tr key={index}>
                                <td>
                                    <ButtonGroup size='sm'>
                                        <Button title='Set as primary' disabled={phone.is_primary || props.readOnly} onClick={() => setPrimaryPhone(index)}><i className={phone.is_primary ? 'fas fa-star' : 'far fa-star'}></i></Button>
                                        <Button title='Delete' variant='danger' disabled={phone.is_primary || props.readOnly} onClick={() => deletePhone(index)}><i className='fas fa-trash'></i></Button>
                                    </ButtonGroup>
                                </td>
                                <td>
                                    <FormControl
                                        as={MaskedInput}
                                        data-phone-index={index}
                                        name='phone_number'
                                        placeholder='(XXX) XXX-XXXX'
                                        value={phone.phone_number}
                                        onChange={handlePhoneChange}
                                        readOnly={props.readOnly}
                                        mask="(111) 111-1111"
                                    />
                                </td>
                                <td>
                                    <FormControl
                                        data-phone-index={index}
                                        name='extension_number'
                                        placeholder='Extension (optional)'
                                        value={phone.extension_number}
                                        onChange={handlePhoneChange}
                                        readOnly={props.readOnly}
                                    />
                                </td>
                                <td>
                                    <Select
                                        options={props.phoneTypes}
                                        value={phone.type ? props.phoneTypes.find(type => type.value == phone.type) : undefined}
                                        onChange={value => handlePhoneChange({target: {name: 'type', type: 'string', value: value.value, dataset: {phoneIndex: index}}})}
                                    />
                                </td>
                            </tr>
                        )
                    }
                )}
            </tbody>
        </Table>
    )
}
