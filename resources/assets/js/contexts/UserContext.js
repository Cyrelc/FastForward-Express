import React, {createContext, useContext, useEffect, useState} from 'react'
import {useAPI} from '../contexts/APIContext'
import {browserTimeZone, isBrowserTimeZoneDataStale} from '../utils/dates'

const UserContext = createContext()

export function UserProvider ({children}) {
    const [authenticatedUser, setAuthenticatedUser] = useState(null)
    const [contact, setContact] = useState({})
    const [frontEndPermissions, setFrontEndPermissions] = useState({})
    const [homePage, setHomePage] = useState('')
    const [settings, setSettings] = useState([])
    const [timeZoneDataIsStale, setTimeZoneDataIsStale] = useState(false)

    const api = useAPI()

    useEffect(() => {
        async function fetchUser() {
            await api.get(`/users/getConfiguration?${new URLSearchParams({time_zone: browserTimeZone()})}`)
            .then(data => {
                data = data.data
                setAuthenticatedUser(data)
                setContact(data.contact)
                setFrontEndPermissions(data.front_end_permissions)
                setSettings(data.user_settings)
                setTimeZoneDataIsStale(isBrowserTimeZoneDataStale(data.time_zone_reference))
                if(data.front_end_permissions.appSettings.edit)
                    setHomePage('/adminDashboard')
                else if(data.employee)
                    setHomePage(`/employees/${data.employee.employee_id}`)
                else if(data.account_users?.length > 1)
                    setHomePage(`/accounts`)
                else
                    setHomePage(`/accounts/${data.account_users[0].account_id}`)
            })
        }
        fetchUser()
    }, [])

    return (
        <UserContext.Provider value={{
            authenticatedUser,
            contact,
            frontEndPermissions,
            homePage,
            settings,
            timeZoneDataIsStale,
        }}>
            {children}
        </UserContext.Provider>
    )
}

export const useUser = () => useContext(UserContext)
