
import React from 'react'
import ReactDom from 'react-dom'
import App from './components/partials/App'
import { createStore } from 'redux'
/**
 * First we will load all of this project's JavaScript dependencies which
 * includes React and other helpers. It's a great starting point while
 * building robust, powerful web applications using React + Laravel.
 */
require('bootstrap')
require('react-bootstrap')
require('datejs')
// require('moment')
/**
 * Next, we will create a fresh React component instance and attach it to
 * the page. Then, you may begin adding components to this application
 * or customize the JavaScript scaffolding to fit your unique needs.
 */

ReactDom.render(<App />, document.getElementById('reactDiv'))
