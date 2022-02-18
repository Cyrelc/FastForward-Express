import React, { Component } from 'react'
import { Redirect, Route, Switch } from 'react-router-dom'
import { LinkContainer } from 'react-router-bootstrap'
import { Container, FormControl, InputGroup, Navbar, Nav, NavDropdown, NavLink } from 'react-bootstrap'
import { connect } from 'react-redux'
import { fetchAppConfiguration } from '../../store/reducers/app'
import Select from 'react-select'
import { ConnectedRouter, push } from 'connected-react-router'

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

    getUserIcon() {
        if(this.props.authenticatedAccountUsers === null && this.props.authenticatedEmployee === null)
            return 'fas fa-dragon'
        else if(this.props.authenticatedEmployee)
            return 'fas fa-user-ninja'
        return 'fas fa-user-circle'
    }

    handleChange(event) {
        const {name, checked, value, type} = event.target
        this.setState({[name]: type === 'checkbox' ? checked : value})
    }

    hasAnyPermission(permissionArray) {
        if(permissionArray === undefined)
            return false
        for(const [key, value] of Object.entries(permissionArray))
            if(value === true)
                return true
        return false
    }

    toggleChangePasswordModal() {
        this.setState({showChangePasswordModal: !this.state.showChangePasswordModal})
    }

    render() {
        return (
            <ConnectedRouter history={this.props.history}>
                <Navbar variant='dark' className={'navbar-expand-lg', 'navbar', 'justify-content-md-end'} style={{backgroundImage:'linear-gradient(to right, black, #0770b1, black)'}}>
                    <Container fluid>
                        <LinkContainer to='/'>
                            <Navbar.Brand align='start'>Fast Forward Express v4.0</Navbar.Brand>
                        </LinkContainer>
                        <Navbar.Toggle aria-controls='responsive-navbar-nav' />
                        <Navbar.Collapse id='responsive-navbar-nav' className='justify-content-end'>
                            <Nav className='ml-auto'>
                                {this.hasAnyPermission(this.props.frontEndPermissions.bills) &&
                                    <NavDropdown title='Bills' id='navbar-bills'>
                                        {this.props.frontEndPermissions.bills.viewAny &&
                                            <LinkContainer to='/app/bills'><NavDropdown.Item><i className='fa fa-list'></i> List Bills</NavDropdown.Item></LinkContainer>
                                        }
                                        {this.props.frontEndPermissions.bills.create &&
                                            <LinkContainer to='/app/bills/create'><NavDropdown.Item><i className='fa fa-plus-square'></i> New Bill</NavDropdown.Item></LinkContainer>
                                        }
                                        {this.props.frontEndPermissions.appSettings.edit &&
                                            <LinkContainer to='/app/bills/trend'><NavDropdown.Item><i className='fas fa-chart-bar'></i> Trend</NavDropdown.Item></LinkContainer>
                                        }
                                        {this.props.frontEndPermissions.bills.viewAny &&
                                            <InputGroup style={{paddingLeft: '10px', paddingRight: '10px', width: '300px'}}>
                                                <InputGroup.Text>Bill ID: </InputGroup.Text>
                                                <FormControl
                                                    name={'billId'}
                                                    onChange={this.handleChange}
                                                    type='number'
                                                    min='1'
                                                    value={this.state.billId}
                                                    onKeyPress={event => {
                                                        if(event.key === 'Enter' && this.state.billId) {
                                                            const billId = this.state.billId
                                                            this.setState({billId: ''}, () => this.props.redirect('/app/bills/' + billId))
                                                        }
                                                    }}
                                                />
                                            </InputGroup>
                                        }
                                    </NavDropdown>
                                }
                                {this.hasAnyPermission(this.props.frontEndPermissions.invoices) &&
                                    <NavDropdown title='Invoices' id='navbar-invoices'>
                                        {this.props.frontEndPermissions.invoices.viewAny &&
                                            <LinkContainer to='/app/invoices'><NavDropdown.Item><i className='fa fa-list'></i> List Invoices</NavDropdown.Item></LinkContainer>
                                        }
                                        {this.props.frontEndPermissions.invoices.create &&
                                            <LinkContainer to='/app/invoices/generate'><NavDropdown.Item><i className='fa fa-plus-square'></i> Generate Invoices</NavDropdown.Item></LinkContainer>
                                        }
                                        {this.props.frontEndPermissions.invoices.viewAny &&
                                            <InputGroup style={{paddingLeft: '10px', paddingRight: '10px', width: '300px'}}>
                                                <InputGroup.Text>Invoice ID: </InputGroup.Text>
                                                <FormControl
                                                    name={'invoiceId'}
                                                    onChange={this.handleChange}
                                                    type='number'
                                                    min='1'
                                                    value={this.state.invoiceId}
                                                    onKeyPress={event => {
                                                        if(event.key === 'Enter' && this.state.invoiceId) {
                                                            const invoiceId = this.state.invoiceId
                                                            this.setState({invoiceId: ''}, this.props.redirect('/app/invoices/' + invoiceId))
                                                        }
                                                    }}
                                                />
                                            </InputGroup>
                                        }
                                    </NavDropdown>
                                }
                                {this.hasAnyPermission(this.props.frontEndPermissions.accounts) &&
                                    <NavDropdown title='Accounts' id='navbar-accounts' align='end'>
                                        {(this.props.authenticatedAccountUsers && this.props.accounts.length == 1) &&
                                            <LinkContainer to={'/app/accounts/' + this.props.authenticatedAccountUsers[0].account_id}><NavDropdown.Item>{this.props.accounts.find(account => account.value === this.props.authenticatedAccountUsers[0].account_id).label}</NavDropdown.Item></LinkContainer>
                                        }
                                        {this.props.frontEndPermissions.accounts.viewAny &&
                                            <LinkContainer to='/app/accounts'><NavDropdown.Item><i className='fa fa-list'></i> List Accounts</NavDropdown.Item></LinkContainer>
                                        }
                                        {this.props.frontEndPermissions.accounts.create &&
                                            <NavDropdown.Item href='/app/accounts/create'><i className='fa fa-plus-square'></i> New Account</NavDropdown.Item>
                                        }
                                        {this.props.frontEndPermissions.appSettings.edit &&
                                            <LinkContainer to='/app/accountsReceivable'><NavDropdown.Item><i className=''></i> Accounts Receivable</NavDropdown.Item></LinkContainer>
                                        }
                                        {(this.props.frontEndPermissions.accounts.viewAny && this.props.accounts.length > 1) &&
                                            <InputGroup style={{paddingLeft: '10px', paddingRight: '10px', width: '500px'}}>
                                                <InputGroup.Text>Account ID: </InputGroup.Text>
                                                <Select
                                                    options={this.props.accounts}
                                                    onChange={value => this.props.redirect('/app/accounts/' + value.value)}
                                                />
                                            </InputGroup>
                                        }
                                    </NavDropdown>
                                }
                                {this.hasAnyPermission(this.props.frontEndPermissions.employees) &&
                                    <NavDropdown title='Employees' id='navbar-employees' align='end'>
                                        {this.props.frontEndPermissions.employees.viewAll &&
                                            <LinkContainer to='/app/employees'><NavDropdown.Item><i className='fa fa-list'></i> List Employees</NavDropdown.Item></LinkContainer>
                                        }
                                        {this.props.frontEndPermissions.employees.create &&
                                            <LinkContainer to='/app/employees/create'><NavDropdown.Item><i className='fa fa-plus-square'></i> New Employee</NavDropdown.Item></LinkContainer>
                                        }
                                        {this.props.frontEndPermissions.chargebacks.viewAny &&
                                            <NavDropdown.Item href='/app/chargebacks'><i className='fas fa-cash-register'></i> Chargebacks</NavDropdown.Item>
                                        }
                                        {this.props.frontEndPermissions.manifests.viewAny &&
                                            <LinkContainer to='/app/manifests'><NavDropdown.Item><i className='fas fa-clipboard-list'></i> Manifests</NavDropdown.Item></LinkContainer>
                                        }
                                        {this.props.frontEndPermissions.manifests.create &&
                                            <LinkContainer to='/app/manifests/generate'><NavDropdown.Item><i className='fas fa-clipboard'></i> Generate Manifests</NavDropdown.Item></LinkContainer>
                                        }
                                        {this.props.frontEndPermissions.manifests.viewAny &&
                                            <InputGroup style={{paddingLeft: '10px', paddingRight: '10px', width: '350px'}}>
                                                <InputGroup.Text>Manifest ID: </InputGroup.Text>
                                                <FormControl
                                                    name={'manifestId'}
                                                    onChange={this.handleChange}
                                                    value={this.state.manifestId}
                                                    onKeyPress={event => {
                                                        if(event.key === 'Enter' && this.state.manifestId) {
                                                            const manifestId = this.state.manifestId
                                                            this.setState({manifestId: ''}, () => this.props.redirect('/app/manifests/' + this.state.manifestId))
                                                        }
                                                    }}
                                                />
                                            </InputGroup>
                                        }
                                        {this.props.frontEndPermissions.employees.viewAll &&
                                            <InputGroup style={{paddingLeft: '10px', paddingRight: '10px', width: '350px'}}>
                                                <InputGroup.Text>Employee ID: </InputGroup.Text>
                                                <Select
                                                    options={this.props.employees}
                                                    onChange={value => this.props.redirect('/app/employees/' + value.value)}
                                                />
                                            </InputGroup>
                                        }
                                    </NavDropdown>
                                }
                                {this.props.frontEndPermissions.bills.dispatch &&
                                    <LinkContainer to='/app/dispatch'><NavLink>Dispatch</NavLink></LinkContainer>
                                }
                                {this.props.frontEndPermissions.appSettings.edit &&
                                    <LinkContainer to='/app/appSettings'><NavLink>App Settings</NavLink></LinkContainer>
                                }
                                <NavDropdown title={<span><i className={this.getUserIcon()}></i> {this.props.contact ? this.props.contact.first_name + " " + this.props.contact.last_name : 'User'} </span>} align='end'>
                                    {this.props.authenticatedEmployee && this.props.authenticatedEmployee.employee_id &&
                                        <LinkContainer to={'/app/employees/' + this.props.authenticatedEmployee.employee_id}><NavDropdown.Item><i className='fas fa-user-ninja'></i> {this.props.contact.first_name + " " + this.props.contact.last_name}</NavDropdown.Item></LinkContainer>
                                    }
                                    <NavDropdown.Item onClick={this.toggleChangePasswordModal}><i className='fas fa-user-shield'></i> Change Password</NavDropdown.Item>
                                    <NavDropdown.Item href='/logout'><i className='fas fa-door-open'></i> Log Out</NavDropdown.Item>
                                </NavDropdown>
                            </Nav>
                        </Navbar.Collapse>
                    </Container>
                </Navbar>
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
                <ChangePasswordModal
                    show={this.state.showChangePasswordModal}
                    userId={this.props.authenticatedUserId}
                    toggleModal={this.toggleChangePasswordModal}
                />
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
        frontEndPermissions: store.app.frontEndPermissions
    }
}

export default connect(mapStateToProps, matchDispatchToProps)(App)
