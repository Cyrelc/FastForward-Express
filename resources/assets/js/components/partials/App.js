import React, {useEffect, useState} from 'react'

import ChangePasswordModal from '../partials/ChangePasswordModal'
import LoadingSpinner from '../partials/LoadingSpinner'
import Router from './Router'
import NavBar from './NavBar'
import {useUser} from '../../contexts/UserContext'

export default function App(props) {
    const [showChangePasswordModal, setShowChangePasswordModal] = useState(false)
    const [loading, setLoading] = useState(true)

    const {authenticatedUser, homePage} = useUser()

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
        <div style={{display: 'flex', height: '100vh', maxHeight: '100vh', direction: 'ltr'}}>
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
    )
}
