import { applyMiddleware, combineReducers, compose, createStore } from 'redux'
import thunkMiddleware from 'redux-thunk'
import { connectRouter, routerMiddleware } from 'connected-react-router'

import AccountsReducer from './reducers/accounts'
import AppReducer from './reducers/app'
import EmployeesReducer from './reducers/employees'
import InvoicesReducer from './reducers/invoices'
import ManifestReducer from './reducers/manifests'

const createRootReducer = history => combineReducers({
        router: connectRouter(history),
        accounts: AccountsReducer,
        app: AppReducer,
        employees: EmployeesReducer,
        invoices: InvoicesReducer,
        manifests: ManifestReducer
    })

const composeEnhancers = window.__REDUX_DEVTOOLS_EXTENSION_COMPOSE__ || compose;

export default function configureStore(history) {
    const middleware = [routerMiddleware(history), thunkMiddleware]

    const rootReducer = createRootReducer(history)

    return createStore(rootReducer, composeEnhancers(applyMiddleware(...middleware)))
}
