import React from 'react'
import ReactDOM from 'react-dom/client'
import {BrowserRouter} from 'react-router-dom'
import {APIProvider} from './contexts/APIContext'
import {ListsProvider} from './contexts/ListsContext'
import {UserProvider} from './contexts/UserContext'
import {ToastContainer} from 'react-toastify'

import App from './components/partials/App'
/**
 * First we will load all of this project's JavaScript dependencies which
 * includes React and other helpers. It's a great starting point while
 * building robust, powerful web applications using React + Laravel.
 */
require('datejs')
const root = ReactDOM.createRoot(document.getElementById('reactDiv'));
/**
 * Next, we will create a fresh React component instance and attach it to
 * the page. Then, you may begin adding components to this application
 * or customize the JavaScript scaffolding to fit your unique needs.
 */

root.render(
    <APIProvider history={history}>
        <UserProvider>
            <BrowserRouter basename='/app'>
                <ListsProvider>
                    <ToastContainer theme='dark'/>
                    <App history={history}/>
                </ListsProvider>
            </BrowserRouter>
        </UserProvider>
    </APIProvider>
)
