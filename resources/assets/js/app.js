import React from 'react'
import ReactDom from 'react-dom'
import { Provider } from 'react-redux'
import { ConnectedRouter } from 'connected-react-router'
import { createBrowserHistory } from 'history'

import configureStore from './store/configureStore'

import App from './components/partials/App'
/**
 * First we will load all of this project's JavaScript dependencies which
 * includes React and other helpers. It's a great starting point while
 * building robust, powerful web applications using React + Laravel.
 */
require('datejs')
const history = createBrowserHistory()
const store = configureStore(history)
/**
 * Next, we will create a fresh React component instance and attach it to
 * the page. Then, you may begin adding components to this application
 * or customize the JavaScript scaffolding to fit your unique needs.
 */

ReactDom.render(
    <Provider store={store}>
        <ConnectedRouter history={history}>
            <App history={history}/>
        </ConnectedRouter>
    </Provider>,
    document.getElementById('reactDiv')
)
