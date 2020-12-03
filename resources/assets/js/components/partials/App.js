import React, { Component } from 'react'
import { Redirect, Route, Switch } from 'react-router-dom'
import { LinkContainer } from 'react-router-bootstrap'
import { FormControl, InputGroup, Navbar, Nav, NavDropdown, NavLink } from 'react-bootstrap'
import { connect } from 'react-redux'
import { fetchAccountsSelectionList, fetchEmployeesSelectionList } from '../../store/reducers/app'
import Select from 'react-select'
import { ConnectedRouter, push } from 'connected-react-router'

import Account from '../accounts/Account'
import Accounts from '../accounts/Accounts'
import AccountsReceivable from '../admin/AccountsReceivable'
import AdminDashboard from '../dashboards/AdminDashboard'
import AppSettings from '../admin/AppSettings'
import Bill from '../bills/Bill'
import Bills from '../bills/Bills'
import Charts from '../bills/Charts'
import Chargebacks from '../employees/Chargebacks'
import Dispatch from '../dispatch/Dispatch'
import Employee from '../employees/Employee'
import Employees from '../employees/Employees'
import GenerateInvoices from '../invoices/GenerateInvoices'
import GenerateManifests from '../manifests/GenerateManifests'
import Interliners from '../interliners/Interliners'
import Invoice from '../invoices/Invoice'
import Invoices from '../invoices/Invoices'
import Manifest from '../manifests/Manifest'
import Manifests from '../manifests/Manifests'
import Ratesheet from '../ratesheets/Ratesheet'
import Ratesheets from '../ratesheets/Ratesheets'

class App extends Component {
    constructor() {
        super()
        this.state =  {
            billId: '',
            invoiceId: '',
            manifestId: ''
        }
        this.handleChange = this.handleChange.bind(this)
    }

    componentDidMount() {
        this.props.fetchAccountsSelectionList()
        this.props.fetchEmployeesSelectionList()
    }

    handleChange(event) {
        const {name, checked, value, type} = event.target
        this.setState({[name]: type === 'checkbox' ? checked : value})
    }

    render() {
        return (
            <ConnectedRouter history={this.props.history}>
                <Navbar variant='dark' bg='dark' className={'navbar-expand-lg', 'navbar'}>
                    <LinkContainer to='/'>
                        <Navbar.Brand>Fast Forward Express v2.0</Navbar.Brand>
                    </LinkContainer>
                    <Navbar.Toggle aria-controls='responsive-navbar-nav' />
                    <Navbar.Collapse id='responsive-navbar-nav'>
                        <Nav className='ml-auto'>
                            <NavDropdown title='Bills' id='navbar-bills'>
                                <LinkContainer to='/app/bills?filter[percentage_complete]=,100'><NavDropdown.Item><i className='fa fa-list'></i> List Bills - Incomplete</NavDropdown.Item></LinkContainer>
                                <LinkContainer to={'/app/bills?filter[time_pickup_scheduled]=' + new Date().addDays(-45).toISOString().split('T')[0]}><NavDropdown.Item><i className='fa fa-list'></i> List Bills - Last 45 days</NavDropdown.Item></LinkContainer>
                                <LinkContainer to='/app/bills/create'><NavDropdown.Item><i className='fa fa-plus-square'></i> New Bill</NavDropdown.Item></LinkContainer>
                                <LinkContainer to='/app/bills/trend'><NavDropdown.Item><i className='fas fa-chart-bar'></i> Trend</NavDropdown.Item></LinkContainer>
                                <InputGroup style={{paddingLeft: '10px', paddingRight: '10px', width: '300px'}}>
                                    <InputGroup.Prepend><InputGroup.Text>Bill ID: </InputGroup.Text></InputGroup.Prepend>
                                    <FormControl
                                        name={'billId'}
                                        onChange={this.handleChange}
                                        type='number'
                                        min='1'
                                        value={this.state.billId}
                                        onKeyPress={event => {
                                            if(event.key === 'Enter' && this.state.billId) {
                                                const billId = this.state.billId
                                                this.setState({billId: ''}, () => this.props.redirect('/app/bills/edit/' + billId))
                                            }
                                        }}
                                    />
                                </InputGroup>
                            </NavDropdown>
                            <NavDropdown title='Invoices' id='navbar-invoices'>
                                <LinkContainer to='/app/invoices'><NavDropdown.Item><i className='fa fa-list'></i> List Invoices</NavDropdown.Item></LinkContainer>
                                <LinkContainer to='/app/invoices/generate'><NavDropdown.Item><i className='fa fa-plus-square'></i> Generate Invoices</NavDropdown.Item></LinkContainer>
                                <InputGroup style={{paddingLeft: '10px', paddingRight: '10px', width: '300px'}}>
                                    <InputGroup.Prepend><InputGroup.Text>Invoice ID: </InputGroup.Text></InputGroup.Prepend>
                                    <FormControl
                                        name={'invoiceId'}
                                        onChange={this.handleChange}
                                        type='number'
                                        min='1'
                                        value={this.state.invoiceId}
                                        onKeyPress={event => {
                                            if(event.key === 'Enter' && this.state.invoiceId) {
                                                const invoiceId = this.state.invoiceId
                                                this.setState({invoiceId: ''}, this.props.redirect('/app/invoices/view/' + invoiceId))
                                            }
                                        }}
                                    />
                                </InputGroup>
                            </NavDropdown>
                            <NavDropdown title='Accounts' id='navbar-accounts' alignRight>
                                <LinkContainer to='/app/accounts'><NavDropdown.Item><i className='fa fa-list'></i> List Accounts</NavDropdown.Item></LinkContainer>
                                <NavDropdown.Item href='/app/accounts/create'><i className='fa fa-plus-square'></i> New Account</NavDropdown.Item>
                                <InputGroup style={{paddingLeft: '10px', paddingRight: '10px', width: '500px'}}>
                                    <InputGroup.Prepend><InputGroup.Text>Account ID: </InputGroup.Text></InputGroup.Prepend>
                                    <Select
                                        options={this.props.accounts}
                                        onChange={value => this.props.redirect('/app/accounts/edit/' + value.value)}
                                    />
                                </InputGroup>
                            </NavDropdown>
                            <NavDropdown title='Employees' id='navbar-employees' alignRight>
                                <LinkContainer to='/app/employees'><NavDropdown.Item><i className='fa fa-list'></i> List Employees</NavDropdown.Item></LinkContainer>
                                <LinkContainer to='/app/employees/create'><NavDropdown.Item><i className='fa fa-plus-square'></i> New Employee</NavDropdown.Item></LinkContainer>
                                <NavDropdown.Item href='/app/chargebacks'><i className='fas fa-cash-register'></i> Chargebacks</NavDropdown.Item>
                                <LinkContainer to='/app/manifests'><NavDropdown.Item><i className='fas fa-clipboard-list'></i> Manifests</NavDropdown.Item></LinkContainer>
                                <LinkContainer to='/app/manifests/generate'><NavDropdown.Item><i className='fas fa-clipboard'></i> Generate Manifests</NavDropdown.Item></LinkContainer>
                                <InputGroup style={{paddingLeft: '10px', paddingRight: '10px', width: '350px'}}>
                                    <InputGroup.Prepend><InputGroup.Text>Manifest ID: </InputGroup.Text></InputGroup.Prepend>
                                    <FormControl
                                        name={'manifestId'}
                                        onChange={this.handleChange}
                                        value={this.state.manifestId}
                                        onKeyPress={event => {
                                            if(event.key === 'Enter' && this.state.manifestId) {
                                                const manifestId = this.state.manifestId
                                                this.setState({manifestId: ''}, () => this.props.redirect('/app/manifests/view/' + this.state.manifestId))
                                            }
                                        }}
                                    />
                                </InputGroup>
                                <InputGroup style={{paddingLeft: '10px', paddingRight: '10px', width: '350px'}}>
                                    <InputGroup.Prepend><InputGroup.Text>Employee ID: </InputGroup.Text></InputGroup.Prepend>
                                    <Select
                                        options={this.props.employees}
                                        onChange={value => this.props.redirect('/app/employees/edit/' + value.value)}
                                    />
                                </InputGroup>
                            </NavDropdown>
                            <LinkContainer to='/app/dispatch'><NavLink>Dispatch</NavLink></LinkContainer>
                            <NavDropdown title='Administration' id='navbar-admin' alignRight>
                                <LinkContainer to='/app/accountsReceivable'><NavDropdown.Item><i className=''></i> Accounts Receivable</NavDropdown.Item></LinkContainer>
                                <LinkContainer to='/app/appSettings'><NavDropdown.Item><i className='fas fa-cogs'></i> App Settings</NavDropdown.Item></LinkContainer>
                                <LinkContainer to='/app/ratesheets'><NavDropdown.Item><i className='fas fa-dollar-sign'></i> Ratesheets</NavDropdown.Item></LinkContainer>
                                <LinkContainer to='/app/ratesheets/create'><NavDropdown.Item>Create Ratesheet</NavDropdown.Item></LinkContainer>
                                <LinkContainer to='/app/interliners'><NavDropdown.Item><i className='fa fa-list'></i> List Interliners</NavDropdown.Item></LinkContainer>
                                <NavDropdown.Item href='/interliners/create'><i className='fa fa-plus-square'></i> New Interliner</NavDropdown.Item>
                                <NavDropdown.Item href='/logout'><i className='fas fa-door-open'></i> Log Out</NavDropdown.Item>
                            </NavDropdown>
                        </Nav>
                    </Navbar.Collapse>
                </Navbar>
                <Switch>
                    <Route path='/app/accounts/:action/:accountId?' component={Account}></Route>
                    <Route path='/app/accounts' exact component={Accounts}></Route>
                    <Route path='/app/accountsReceivable' exact component={AccountsReceivable}></Route>
                    <Route path='/app/appSettings' exact component={AppSettings}></Route>
                    <Route path='/app/bills/trend' component={Charts}></Route>
                    <Route exact path='/app/bills' component={Bills}></Route>
                    <Route path='/app/bills/:action/:billId?' component={Bill}></Route>
                    <Route path='/app/chargebacks' exact component={Chargebacks}></Route>
                    <Route path='/app/dispatch' component={Dispatch}></Route>
                    <Route path='/' exact component={AdminDashboard}></Route>
                    <Route path='/app/interliners' component={Interliners}></Route>
                    <Route path='/app/invoices' exact component={Invoices}></Route>
                    <Route path='/app/invoices/generate' exact component={GenerateInvoices}></Route>
                    <Route path='/app/invoices/view/:invoiceId' component={Invoice}></Route>
                    <Route path='/app/employees' exact component={Employees}></Route>
                    <Route path='/app/employees/:action/:employeeId?' component={Employee}></Route>
                    <Route path='/app/manifests/view/:manifestId' exact component={Manifest}></Route>
                    <Route path='/app/manifests' exact component={Manifests}></Route>
                    <Route path='/app/manifests/generate' exact component={GenerateManifests}></Route>
                    <Route path='/app/ratesheets' exact component={Ratesheets}></Route>
                    <Route path='/app/ratesheets/:action/:ratesheetId?' component={Ratesheet}></Route>
                </Switch>
            </ConnectedRouter>
        )
    }
}

const matchDispatchToProps = dispatch => {
    return {
        fetchAccountsSelectionList: () => dispatch(fetchAccountsSelectionList),
        fetchEmployeesSelectionList: () => dispatch(fetchEmployeesSelectionList),
        redirect: url => dispatch(push(url))
    }
}

const mapStateToProps = store => {
    return {
        accounts: store.app.accounts,
        employees: store.app.employees
    }
}

export default connect(mapStateToProps, matchDispatchToProps)(App)
