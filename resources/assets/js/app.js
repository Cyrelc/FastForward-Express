import React from 'react'
import ReactDom from 'react-dom'
import {ConnectedRouter} from 'connected-react-router'
import {createBrowserHistory} from 'history'
import {APIProvider} from './contexts/APIContext'
import {ListsProvider} from './contexts/ListsContext'
import {UserProvider} from './contexts/UserContext'
import {ProSidebarProvider} from 'react-pro-sidebar'
import {ToastContainer} from 'react-toastify'

import App from './components/partials/App'
/**
 * First we will load all of this project's JavaScript dependencies which
 * includes React and other helpers. It's a great starting point while
 * building robust, powerful web applications using React + Laravel.
 */
require('datejs')
const history = createBrowserHistory({ basename: '/app'})
/**
 * Next, we will create a fresh React component instance and attach it to
 * the page. Then, you may begin adding components to this application
 * or customize the JavaScript scaffolding to fit your unique needs.
 */

ReactDom.render(
    <Provider store={store}>
        <APIProvider history={history}>
            <UserProvider>
                <ConnectedRouter history={history}>
                    <ListsProvider>
                        <ProSidebarProvider>
                            <ToastContainer />
                            <App history={history}/>
                        </ProSidebarProvider>
                    </ListsProvider>
                </ConnectedRouter>
            </UserProvider>
        </APIProvider>
    </Provider>,
    document.getElementById('reactDiv')
)
