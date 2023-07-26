import React from 'react'
import {FormControl, ButtonGroup, Button, Table} from 'react-bootstrap'
import Select from 'react-select'
import MaskedInput from 'react-text-mask'

export default function Phones(props) {
    const {
        phoneNumbers,
        phoneTypes,
        readOnly,
        setPhoneNumbers
    } = props

    function addPhone() {
        const phones = phoneNumbers.concat([
            {phone: '', extension: '', type: '', is_primary: phoneNumbers.length === 0}
        ])
        setPhoneNumbers(phones)
    }

    /**
     * If the phone exists in database (has phone_number_id) then mark it as to be deleted,
     * Otherwise if it is a new phone number that they have changed their mind about, simply filter it out
     */
    function deletePhone(phoneIndex) {
        if(phoneNumbers.length <= 1)
            return
        const phones = phoneNumbers.map((phone, index) => {
            // precautionary check to never delete the primary phone number 
            if(!phone.is_primary && index == phoneIndex && phone.phone_number_id)
                return {...phone, delete: true}
            return phone
        }).filter((phone, index) => {
            if(phone.is_primary || index != phoneIndex || phone.phone_number_id)
                return true
            return false
        })
        setPhoneNumbers(phones)
    }

    function handlePhoneChange(event) {
        const {name, value} = event.target
        const phoneIndex = event.target.dataset.phoneIndex
        const phones = phoneNumbers.map((phone, index) => {
            if(index == phoneIndex)
                return {...phone, [name]: value}
            return phone
        })
        setPhoneNumbers(phones)
    }

    function setPrimaryPhone(phoneIndex) {
        const phones = phoneNumbers.map((phone, index) => {
            if(index == phoneIndex)
                return {...phone, is_primary: 1}
            return {...phone, is_primary: 0}
        })
        setPhoneNumbers(phones)
    }

    return (
        <Table striped bordered size='sm'>
            <thead>
                <tr>
                    <td style={{minWidth: '90px', width: '90px'}}>
                        {!readOnly &&
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
                {phoneNumbers?.map((phone, index) => {
                    if(!phone.delete)
                        return (
                            <tr key={index}>
                                <td>
                                    <ButtonGroup size='sm'>
                                        <Button title='Set as primary' disabled={phone.is_primary || readOnly} onClick={() => setPrimaryPhone(index)}>
                                            <i className={phone.is_primary ? 'fas fa-star' : 'far fa-star'}></i>
                                        </Button>
                                        <Button title='Delete' variant='danger' disabled={phone.is_primary || readOnly} onClick={() => deletePhone(index)}>
                                            <i className='fas fa-trash'></i>
                                        </Button>
                                    </ButtonGroup>
                                </td>
                                <td>
                                    <MaskedInput
                                        className='form-control'
                                        data-phone-index={index}
                                        mask={['(', /[1-9]/, /\d/, /\d/, ')', ' ', /\d/, /\d/, /\d/, '-', /\d/, /\d/, /\d/, /\d/]}
                                        name='phone_number'
                                        onChange={handlePhoneChange}
                                        placeholder='(XXX) XXX-XXX'
                                        readOnly={readOnly}
                                        value={phone.phone_number}
                                    />
                                </td>
                                <td>
                                    <FormControl
                                        data-phone-index={index}
                                        name='extension_number'
                                        placeholder='Extension (optional)'
                                        value={phone.extension_number}
                                        onChange={handlePhoneChange}
                                        readOnly={readOnly}
                                    />
                                </td>
                                <td>
                                    <Select
                                        options={phoneTypes}
                                        value={phone.type ? phoneTypes.find(type => type.value == phone.type) : undefined}
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
