import {useState} from 'react'

const emailAddressSeed = [{'email': '', 'type': '', 'email_address_id': null, 'is_primary': true}]
const phoneNumberSeed = [{'phone': '', 'extension': '', 'type':'', 'phone_number_id': null, 'is_primary': true}]

import {useLists} from '../../../contexts/ListsContext'

export default function useContact() {
    const [contactId, setContactId] = useState('')
    const [emailTypes, setEmailTypes] = useState([])
    const [emailAddresses, setEmailAddresses] = useState(emailAddressSeed)
    const [firstName, setFirstName] = useState('')
    const [lastName, setLastName] = useState('')
    const [preferredName, setPreferredName] = useState('')
    const [position, setPosition] = useState('')
    const [pronouns, setPronouns] = useState([])
    const [phoneTypes, setPhoneTypes] = useState([])
    const [phoneNumbers, setPhoneNumbers] = useState(phoneNumberSeed)

    const lists = useLists()

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

    const reset = (useEmailTypes = false) => {
        setContactId('')
        setEmailAddresses(emailAddressSeed)
        setEmailTypes(useEmailTypes ? lists.emailTypes : [])
        setFirstName('')
        setLastName('')
        setPhoneNumbers(phoneNumberSeed)
        setPhoneTypes(lists.phoneTypes)
        setPosition('')
        setPreferredName('')
        setPronouns([])
    }

    const setup = contact => {
        setContactId(contact.contact_id)
        setEmailAddresses(contact.emails ?? contact.email_addresses ?? emailAddresses)
        setEmailTypes(contact.email_types ?? lists.emailTypes)
        setFirstName(contact.first_name)
        setLastName(contact.last_name)
        setPhoneNumbers(contact.phone_numbers ?? phoneNumbers)
        setPhoneTypes(contact.phone_types ?? lists.phoneTypes)
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

