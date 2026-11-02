import React, {useEffect, useState} from 'react'

import ChangePasswordModal from '../partials/ChangePasswordModal'
import LoadingSpinner from '../partials/LoadingSpinner'
import Router from './Router'
import NavBar from './NavBar'
import TimeZoneWarning from './TimeZoneWarning'
import {useUser} from '../../contexts/UserContext'

export default function App(props) {
    const [showChangePasswordModal, setShowChangePasswordModal] = useState(false)
    const [loading, setLoading] = useState(true)

    const {authenticatedUser, homePage, timeZoneDataIsStale} = useUser()

    useEffect(() => {
        if(loading && homePage) {
            setLoading(false)
        }
    }, [homePage])

    const toggleChangePasswordModal = () => {
        setShowChangePasswordModal(!showChangePasswordModal)
    }

    if(loading)
        return <LoadingSpinner />

    return (
        <div style={{display: 'flex', flexDirection: 'column', height: '100vh', maxHeight: '100vh'}}>
            {timeZoneDataIsStale && <TimeZoneWarning />}
            <div style={{display: 'flex', flex: 1, minHeight: 0, direction: 'ltr'}}>
                <NavBar
                    toggleChangePasswordModal={toggleChangePasswordModal}
                />
                <Router
                    homePage={homePage}
                />
                <ChangePasswordModal
                    show={showChangePasswordModal}
                    userId={authenticatedUser.user_id}
                    toggleModal={toggleChangePasswordModal}
                />
            </div>
        </div>
    )
}
