import React, {Component} from 'react'
import {Redirect, Route, Switch} from 'react-router-dom'
import {connect} from 'react-redux'
import {fetchAppConfiguration} from '../../store/reducers/app'
import {ConnectedRouter, push} from 'connected-react-router'
import {Col, Row} from 'react-bootstrap'

import NavBar from './NavBar'

import Account from '../accounts/Account'
import Accounts from '../accounts/Accounts'
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

class App extends Component {
    constructor() {
        super()
        this.state = {
            billId: '',
            invoiceId: '',
            manifestId: '',
            showChangePasswordModal: false
        }
        this.getLandingPage = this.getLandingPage.bind(this)
        this.handleChange = this.handleChange.bind(this)
        this.toggleChangePasswordModal = this.toggleChangePasswordModal.bind(this)
    }

    componentDidMount() {
        this.props.fetchAppConfiguration()
    }

    getLandingPage() {
        if(this.props.frontEndPermissions.appSettings.edit)
            return <Redirect from='/' to='/app/adminDashboard'></Redirect>
        else if(this.props.authenticatedEmployee)
            return <Redirect from='/' to={'/app/employees/' + this.props.authenticatedEmployee.employee_id}></Redirect>
        else if(this.props.authenticatedAccountUsers)
            return <Redirect from='/' to={'/app/accounts/' + this.props.authenticatedAccountUsers[0].account_id}></Redirect>
    }

    handleChange(event) {
        const {name, checked, value, type} = event.target
        this.setState({[name]: type === 'checkbox' ? checked : value})
    }

    toggleChangePasswordModal() {
        this.setState({showChangePasswordModal: !this.state.showChangePasswordModal})
    }

    render() {
        return (
            <ConnectedRouter history={this.props.history}>
                <Row>
                    <Col md='auto' style={{height: '100vh'}}>
                        <NavBar
                            toggleChangePasswordModal={this.toggleChangePasswordModal}
                        />
                    </Col>
                    <Col style={{height: '100vh', overflowY: 'scroll'}}>
                        <Switch>
                            <Route exact path='/' render={props => {
                                if(this.props.authenticatedEmployee && this.props.authenticatedEmployee.employee_id)
                                    return this.props.frontEndPermissions.appSettings.edit ? <Redirect to='/app/adminDashboard'></Redirect> : <Redirect to={'/app/employees/' + this.props.authenticatedEmployee.employee_id}></Redirect>
                                else if(this.props.frontEndPermissions.appSettings.edit)
                                    return <Redirect to='/app/adminDashboard'></Redirect>
                                else if(this.props.authenticatedAccountUsers && this.props.authenticatedAccountUsers.length == 1)
                                    return <Redirect to={'/app/accounts/' + this.props.authenticatedAccountUsers[0].account_id}></Redirect>
                                else if(this.props.authenticatedAccountUsers && this.props.accounts.length > 1)
                                    return <Redirect to='/app/accounts'></Redirect>
                            }}></Route>
                            <Route exact path='/app/error404' component={PageNotFound}></Route>
                            {this.props.frontEndPermissions.accounts.viewAny &&
                                <Route path='/app/accounts' exact component={Accounts}></Route>
                            }
                            {this.props.frontEndPermissions.accounts.create &&
                                <Route path='/app/accounts/create' exact component={Account}></Route>
                            }
                            {this.props.frontEndPermissions.accounts.viewAny &&
                                <Route path='/app/accounts/:accountId' exact component={Account}></Route>
                            }
                            {this.props.frontEndPermissions.appSettings.edit &&
                                <Route path='/app/accountsReceivable' exact component={AccountsReceivable}></Route>
                            }
                            {this.props.frontEndPermissions.appSettings.edit &&
                                <Route path='/app/appSettings' exact component={AppSettings}></Route>
                            }
                            {this.props.frontEndPermissions.bills.viewAny &&
                                <Route exact path='/app/bills' component={Bills}></Route>
                            }
                            {this.props.frontEndPermissions.appSettings.edit &&
                                <Route path='/app/bills/trend' component={Charts}></Route>
                            }
                            {this.props.frontEndPermissions.bills.create &&
                                <Route path='/app/bills/create' exact component={Bill}></Route>
                            }
                            {this.props.frontEndPermissions.bills.viewAny &&
                                <Route path='/app/bills/:billId' component={Bill}></Route>
                            }
                            {this.props.frontEndPermissions.chargebacks.viewAny &&
                                <Route path='/app/chargebacks' exact component={Chargebacks}></Route>
                            }
                            {this.props.frontEndPermissions.bills.dispatch &&
                                <Route path='/app/dispatch' component={Dispatch}></Route>
                            }
                            {this.props.frontEndPermissions.appSettings.edit &&
                                <Route path='/app/adminDashboard' exact component={AdminDashboard}></Route>
                            }
                            {this.props.frontEndPermissions.invoices.viewAny &&
                                <Route path='/app/invoices' exact component={Invoices}></Route>
                            }
                            {this.props.frontEndPermissions.invoices.create &&
                                <Route path='/app/invoices/generate' exact component={GenerateInvoices}></Route>
                            }
                            {this.props.frontEndPermissions.invoices.viewAny &&
                                <Route path='/app/invoices/:invoiceId' component={Invoice}></Route>
                            }
                            {this.props.frontEndPermissions.employees.viewAll &&
                                <Route path='/app/employees' exact component={Employees}></Route>
                            }
                            {this.props.frontEndPermissions.employees.create &&
                                <Route path='/app/employees/create' component={Employee}></Route>
                            }
                            {this.props.frontEndPermissions.employees.viewAny &&
                                <Route path='/app/employees/:employeeId' component={Employee}></Route>
                            }
                            {this.props.frontEndPermissions.manifests.create &&
                                <Route path='/app/manifests/generate' exact component={GenerateManifests}></Route>
                            }
                            {this.props.frontEndPermissions.manifests.viewAny &&
                                <Route path='/app/manifests/:manifestId' exact component={Manifest}></Route>
                            }
                            {this.props.frontEndPermissions.manifests.viewAny &&
                                <Route path='/app/manifests' exact component={Manifests}></Route>
                            }
                            {this.props.frontEndPermissions.appSettings.edit &&
                                <Route path='/app/ratesheets/create' component={Ratesheet}></Route>
                            }
                            {this.props.frontEndPermissions.appSettings.edit &&
                                <Route path='/app/ratesheets/:ratesheetId' component={Ratesheet}></Route>
                            }
                        </Switch>
                    </Col>
                </Row>
                {this.state.showChangePasswordModal &&
                    <ChangePasswordModal
                        show={this.state.showChangePasswordModal}
                        userId={this.props.authenticatedUserId}
                        toggleModal={this.toggleChangePasswordModal}
                    />
                }
            </ConnectedRouter>
        )
    }
}

const matchDispatchToProps = dispatch => {
    return {
        fetchAppConfiguration: () => dispatch(fetchAppConfiguration),
        redirect: url => dispatch(push(url))
    }
}

const mapStateToProps = store => {
    return {
        accounts: store.app.accounts,
        authenticatedAccountUsers: store.app.authenticatedAccountUsers,
        authenticatedEmployee: store.app.authenticatedEmployee,
        authenticatedUserId: store.app.authenticatedUserId,
        contact: store.app.authenticatedUserContact,
        employees: store.app.employees,
        frontEndPermissions: store.app.frontEndPermissions,
        isImpersonating: store.app.isImpersonating
    }
}

export default connect(mapStateToProps, matchDispatchToProps)(App)
