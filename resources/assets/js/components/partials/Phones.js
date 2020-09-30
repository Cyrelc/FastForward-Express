import React from 'react'
import {FormControl, ButtonGroup, Button, Table} from 'react-bootstrap'
import Select from 'react-select'

export default function Phones(props) {
    function addPhone() {
        const phones = props.phoneNumbers
        phones[phones.length] = {phone: '', extension: '', type: '', is_primary: phones.length === 0}
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
        <Table striped bordered>
            <thead>
                <tr>
                    <td style={{width:'10%'}}>
                        {!props.readOnly &&
                            <Button variant='success' onClick={addPhone}>
                                <span><i className='fas fa-plus' style={{paddingRight: 5}}></i><i className='fas fa-phone'></i></span>
                            </Button>
                        }
                    </td>
                    <td style={{width: '35%'}}>Phone</td>
                    <td style={{width: '25%'}}>Extension</td>
                    <td style={{width: '30%'}}>Type</td>
                </tr>
            </thead>
            <tbody>
                {props.phoneNumbers && props.phoneNumbers.map((phone, index) => {
                    if(!phone.delete)
                        return (
                            <tr key={index}>
                                <td>
                                    <ButtonGroup>
                                        <Button title='Set as primary' disabled={phone.is_primary || props.readOnly} onClick={() => setPrimaryPhone(index)}><i className={phone.is_primary ? 'fas fa-star' : 'far fa-star'}></i></Button>
                                        <Button title='Delete' variant='danger' disabled={phone.is_primary || props.readOnly} onClick={() => deletePhone(index)}><i className='fas fa-trash'></i></Button>
                                    </ButtonGroup>
                                </td>
                                <td>
                                    <FormControl
                                        data-phone-index={index}
                                        name='phone_number'
                                        placeholder='(XXX) XXX-XXXX'
                                        value={phone.phone_number}
                                        onChange={handlePhoneChange}
                                        readOnly={props.readOnly}
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
                                        getOptionLabel={type => type.name}
                                        getOptionValue={type => type.value}
                                        value={phone.type ? props.phoneTypes.filter(type => type.value == phone.type) : undefined}
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
