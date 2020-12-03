import { applyMiddleware, combineReducers, compose, createStore } from 'redux'
import thunkMiddleware from 'redux-thunk'
import { connectRouter, routerMiddleware } from 'connected-react-router'

import AppReducer from './reducers/app'
import InvoicesReducer from './reducers/invoices'

const createRootReducer = history => combineReducers({
        router: connectRouter(history),
        app: AppReducer,
        invoices: InvoicesReducer
    })

const composeEnhancers = window.__REDUX_DEVTOOLS_EXTENSION_COMPOSE__ || compose;

export default function configureStore(history) {
    const middleware = [routerMiddleware(history), thunkMiddleware]

    const rootReducer = createRootReducer(history)

    return createStore(rootReducer, composeEnhancers(applyMiddleware(...middleware)))
}
