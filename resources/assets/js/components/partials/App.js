import React, {useEffect, useState} from 'react'
import {Redirect, Route, Switch} from 'react-router-dom'
import {connect} from 'react-redux'
import {fetchAppConfiguration} from '../../store/reducers/app'
import {fetchUserConfiguration} from '../../store/reducers/user'
import {ConnectedRouter, push} from 'connected-react-router'
import {Col, Row} from 'react-bootstrap'
import {ProSidebarProvider} from 'react-pro-sidebar'

import NavBar from './NavBar'
import {APIProvider} from '../../contexts/APIContext'

import Account from '../accounts/Account'
import Accounts from '../accounts/Accounts'
import AccountsPayable from '../admin/AccountsPayable'
import AccountsReceivable from '../admin/AccountsReceivable'
import AdminDashboard from '../dashboards/AdminDashboard'
import AppSettings from '../admin/AppSettings'
import Bill from '../bills/Bill'
import Bills from '../bills/Bills'
import ChangePasswordModal from './ChangePasswordModal'
import Charts from '../bills/Charts'
import Chargebacks from '../employees/Chargebacks'
import Dispatch from '../dispatch/Dispatch'
import Employee from '../employees/Employee'
import Employees from '../employees/Employees'
import GenerateInvoices from '../invoices/GenerateInvoices'
import GenerateManifests from '../manifests/GenerateManifests'
import Invoice from '../invoices/Invoice'
import Invoices from '../invoices/Invoices'
import Manifest from '../manifests/Manifest'
import Manifests from '../manifests/Manifests'
import PageNotFound from './PageNotFound'
import Ratesheet from '../ratesheets/Ratesheet'
import Search from '../search/Search'
import UserSettings from '../users/UserSettings'

function App(props) {
    const [showChangePasswordModal, setShowPasswordChangeModal] = useState(false)

    useEffect(() => {
        props.fetchAppConfiguration()
        props.fetchUserConfiguration()
    }, [])

    const toggleChangePasswordModal = () => {
        setShowPasswordChangeModal(!showPasswordChangeModal)
    }

    return (
        <ConnectedRouter history={props.history}>
            <APIProvider history={props.history}>
                <ProSidebarProvider>
                    <div style={{display: 'flex', height: '100vh', maxHeight: '100vh', direction: 'ltr'}}>
                        <NavBar
                            // history={props.history}
                            toggleChangePasswordModal={toggleChangePasswordModal}
                        />
                        <main style={{maxHeight: '100vh', overflowY: 'auto', overflowX: 'hidden', width: '100%'}}>
                            <Row className='justify-content-md-center' style={{paddingLeft: '40px'}}>
                                <Col md={12}>
                                <Switch>
                                    <Route exact path='/' render={props => {
                                        if(props.authenticatedEmployee && props.authenticatedEmployee.employee_id)
                                            return props.frontEndPermissions.appSettings.edit
                                                ? <Redirect to='/app/adminDashboard' />
                                                : <Redirect to={`/app/employees/${props.authenticatedEmployee.employee_id}`} />
                                        else if(props.frontEndPermissions.appSettings.edit)
                                            return <Redirect to='/app/adminDashboard' />
                                        else if(props.authenticatedAccountUsers && props.authenticatedAccountUsers.length == 1)
                                            return <Redirect to={`/app/accounts/${props.authenticatedAccountUsers[0].account_id}`}></Redirect>
                                        else if(props.authenticatedAccountUsers && props.accounts.length > 1)
                                            return <Redirect to='/app/accounts'></Redirect>
                                    }}></Route>
                                    <Route exact path='/app/error404' component={PageNotFound}></Route>
                                    <Route path='/app/search' exact component={Search}></Route>
                                    <Route path='/app/user_settings' exact component={UserSettings}></Route>
                                    {props.frontEndPermissions.accounts.viewAny &&
                                        <Route path='/app/accounts' exact component={Accounts}></Route>
                                    }
                                    {props.frontEndPermissions.accounts.create &&
                                        <Route path='/app/accounts/create' exact component={Account}></Route>
                                    }
                                    {props.frontEndPermissions.accounts.viewAny &&
                                        <Route path='/app/accounts/:accountId' exact component={Account}></Route>
                                    }
                                    {props.frontEndPermissions.appSettings.edit &&
                                        <Route path='/app/accountsPayable' exact component={AccountsPayable}></Route>
                                    }
                                    {props.frontEndPermissions.appSettings.edit &&
                                        <Route path='/app/accountsReceivable' exact component={AccountsReceivable}></Route>
                                    }
                                    {props.frontEndPermissions.appSettings.edit &&
                                        <Route path='/app/appSettings' exact component={AppSettings}></Route>
                                    }
                                    {props.frontEndPermissions.bills.viewAny &&
                                        <Route exact path='/app/bills' component={Bills}></Route>
                                    }
                                    {props.frontEndPermissions.appSettings.edit &&
                                        <Route path='/app/bills/trend' component={Charts}></Route>
                                    }
                                    {props.frontEndPermissions.bills.create &&
                                        <Route path='/app/bills/create' exact component={Bill}></Route>
                                    }
                                    {props.frontEndPermissions.bills.viewAny &&
                                        <Route path='/app/bills/:billId' component={Bill}></Route>
                                    }
                                    {props.frontEndPermissions.chargebacks.viewAny &&
                                        <Route path='/app/chargebacks' exact component={Chargebacks}></Route>
                                    }
                                    {props.frontEndPermissions.bills.dispatch &&
                                        <Route path='/app/dispatch' component={Dispatch}></Route>
                                    }
                                    {props.frontEndPermissions.appSettings.edit &&
                                        <Route path='/app/adminDashboard' exact component={AdminDashboard}></Route>
                                    }
                                    {props.frontEndPermissions.invoices.viewAny &&
                                        <Route path='/app/invoices' exact component={Invoices}></Route>
                                    }
                                    {props.frontEndPermissions.invoices.create &&
                                        <Route path='/app/invoices/generate' exact component={GenerateInvoices}></Route>
                                    }
                                    {props.frontEndPermissions.invoices.viewAny &&
                                        <Route path='/app/invoices/:invoiceId' component={Invoice}></Route>
                                    }
                                    {props.frontEndPermissions.employees.viewAll &&
                                        <Route path='/app/employees' exact component={Employees}></Route>
                                    }
                                    {props.frontEndPermissions.employees.create &&
                                        <Route path='/app/employees/create' component={Employee}></Route>
                                    }
                                    {props.frontEndPermissions.employees.viewAny &&
                                        <Route path='/app/employees/:employeeId' component={Employee}></Route>
                                    }
                                    {props.frontEndPermissions.manifests.create &&
                                        <Route path='/app/manifests/generate' exact component={GenerateManifests}></Route>
                                    }
                                    {props.frontEndPermissions.manifests.viewAny &&
                                        <Route path='/app/manifests/:manifestId' exact component={Manifest}></Route>
                                    }
                                    {props.frontEndPermissions.manifests.viewAny &&
                                        <Route path='/app/manifests' exact component={Manifests}></Route>
                                    }
                                    {props.frontEndPermissions.appSettings.edit &&
                                        <Route path='/app/ratesheets/create' component={Ratesheet}></Route>
                                    }
                                    {props.frontEndPermissions.appSettings.edit &&
                                        <Route path='/app/ratesheets/:ratesheetId' component={Ratesheet}></Route>
                                    }
                                </Switch></Col>
                            </Row>
                        </main>
                        {showChangePasswordModal &&
                            <ChangePasswordModal
                                show={showChangePasswordModal}
                                userId={props.authenticatedUserId}
                                toggleModal={toggleChangePasswordModal}
                            />
                        }
                    </div>
                </ProSidebarProvider>
            </APIProvider>
        </ConnectedRouter>
    )
}

const matchDispatchToProps = dispatch => {
    return {
        fetchAppConfiguration: () => dispatch(fetchAppConfiguration),
        fetchUserConfiguration: () => dispatch(fetchUserConfiguration),
        redirect: url => dispatch(push(url))
    }
}

const mapStateToProps = store => {
    return {
        accounts: store.app.accounts,
        authenticatedAccountUsers: store.user.authenticatedAccountUsers,
        authenticatedEmployee: store.user.authenticatedEmployee,
        authenticatedUserId: store.user.authenticatedUserId,
        contact: store.user.authenticatedUserContact,
        employees: store.app.employees,
        frontEndPermissions: store.user.frontEndPermissions,
        isImpersonating: store.user.isImpersonating
    }
}

export default connect(mapStateToProps, matchDispatchToProps)(App)
