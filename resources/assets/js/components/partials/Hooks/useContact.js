import {useState} from 'react'

export default function useContact() {
    const [contactId, setContactId] = useState('')
    const [emailTypes, setEmailTypes] = useState([])
    const [emailAddresses, setEmailAddresses] = useState([])
    const [firstName, setFirstName] = useState('')
    const [lastName, setLastName] = useState('')
    const [preferredName, setPreferredName] = useState('')
    const [position, setPosition] = useState('')
    const [pronouns, setPronouns] = useState([])
    const [phoneTypes, setPhoneTypes] = useState([])
    const [phoneNumbers, setPhoneNumbers] = useState([])

    const collect = () => {
        return {
            'contact_id': contactId ?? null,
            'first_name': firstName,
            'last_name': lastName,
            'email_addresses': emailAddresses,
            'phone_numbers': phoneNumbers,
            'position': position,
            'preferred_name': preferredName,
            'pronouns': pronouns,
        }
    }

    const reset = () => {
        setContactId('')
        setEmailAddresses([])
        setEmailTypes([])
        setFirstName('')
        setLastName('')
        setPhoneNumbers([])
        setPhoneTypes([])
        setPosition('')
        setPreferredName('')
        setPronouns([])
    }

    const setup = contact => {
        setContactId(contact.contact_id)
        setEmailAddresses(contact.emails)
        setEmailTypes(contact.email_types)
        setFirstName(contact.first_name)
        setLastName(contact.last_name)
        setPhoneNumbers(contact.phone_numbers)
        setPhoneTypes(contact.phone_types)
        setPosition(contact.position)
        setPreferredName(contact.preferred_name || '')
        setPronouns(contact.pronouns ? JSON.parse(contact.pronouns) : [])
    }

    return {
        collect,
        contactId,
        emailAddresses,
        emailTypes,
        firstName,
        lastName,
        phoneNumbers,
        phoneTypes,
        position,
        preferredName,
        pronouns,
        reset,
        setContactId,
        setEmailAddresses,
        setFirstName,
        setLastName,
        setPhoneNumbers,
        setPosition,
        setPreferredName,
        setPronouns,
        setup
    }
}

