import React, { Component } from 'react'
import ReactDom from 'react-dom'
import { BrowserRouter, Switch, Route, Redirect } from 'react-router-dom'
import { LinkContainer } from 'react-router-bootstrap'
import { FormControl, InputGroup, Navbar, Nav, NavDropdown, NavLink } from 'react-bootstrap'
import Select from 'react-select'

import Accounts from '../accounts/Accounts'
import AdminDashboard from '../dashboards/AdminDashboard'
import AppSettings from '../admin/AppSettings'
import Bill from '../bills/Bill'
import Bills from '../bills/Bills'
import Charts from '../bills/Charts'
import Dispatch from '../dispatch/Dispatch'
import Employee from '../employees/Employee'
import Employees from '../employees/Employees'
import Interliners from '../interliners/Interliners'
import Invoice from '../invoices/Invoice'
import Invoices from '../invoices/Invoices'
import InvoiceGenerate from '../invoices/InvoicesGenerate'
import Manifests from '../manifests/Manifests'
import ManifestsGenerate from '../manifests/ManifestsGenerate'
import Ratesheet from '../ratesheets/Ratesheet'
import Ratesheets from '../ratesheets/Ratesheets'

export default class App extends Component {
    constructor() {
        super()
        this.state =  {
            account: {},
            accounts: [],
            billId: '',
            employeeId: '',
            invoiceId: '',
            manifestId: ''
        }
        this.handleChange = this.handleChange.bind(this)
    }

    componentDidMount() {
        var accounts = []
        var employees = []
        makeFetchRequest('/getList/accounts', response => {
            this.setState({accounts: response})
        })
        makeFetchRequest('/getList/employees', response => {
            this.setState({employees: response})
        })
    }

    handleChange(event) {
        const {name, checked, value, type} = event.target
        this.setState({[name]: type === 'checkbox' ? checked : value})
    }

    render() {
        return (
            <BrowserRouter>
                <Navbar variant='dark' bg='dark' className={'navbar-expand-lg', 'navbar'}>
                    <LinkContainer to='/'>
                        <Navbar.Brand>Fast Forward Express v2.0</Navbar.Brand>
                    </LinkContainer>
                    <Navbar.Toggle aria-controls='responsive-navbar-nav' />
                    <Navbar.Collapse id='responsive-navbar-nav'>
                        <Nav className='ml-auto'>
                            <NavDropdown title='Bills' id='navbar-bills'>
                                <LinkContainer to='/app/bills?filter[percentage_complete]=,1'><NavDropdown.Item><i className='fa fa-list'></i> List Bills</NavDropdown.Item></LinkContainer>
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
                                            if(event.key === 'Enter' && this.state.billId)
                                                this.setState({redirect: '/app/bills/edit/' + this.state.billId, billId: ''})
                                        }}
                                    />
                                </InputGroup>
                            </NavDropdown>
                            <NavDropdown title='Invoices' id='navbar-invoices'>
                                <LinkContainer to='/app/invoices?filter[finalized]=false'><NavDropdown.Item><i className='fa fa-list'></i> List Invoices</NavDropdown.Item></LinkContainer>
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
                                            if(event.key === 'Enter' && this.state.invoiceId)
                                                this.setState({redirect: '/app/invoices/view/' + this.state.invoiceId, invoiceId: ''})
                                        }}
                                    />
                                </InputGroup>
                            </NavDropdown>
                            <NavDropdown title='Accounts' id='navbar-accounts' alignRight>
                                <LinkContainer to='/app/accounts'><NavDropdown.Item><i className='fa fa-list'></i> List Accounts</NavDropdown.Item></LinkContainer>
                                <NavDropdown.Item href='/accounts/create'><i className='fa fa-plus-square'></i> New Account</NavDropdown.Item>
                                <InputGroup style={{paddingLeft: '10px', paddingRight: '10px', width: '500px'}}>
                                    <InputGroup.Prepend><InputGroup.Text>Account ID: </InputGroup.Text></InputGroup.Prepend>
                                    <Select
                                        options={this.state.accounts}
                                        onChange={value => window.location.href = '/accounts/edit/' + value.value}
                                    />
                                </InputGroup>
                            </NavDropdown>
                            <NavDropdown title='Employees' id='navbar-employees' alignRight>
                                <LinkContainer to='/app/employees'><NavDropdown.Item><i className='fa fa-list'></i> List Employees</NavDropdown.Item></LinkContainer>
                                <LinkContainer to='/app/employees/create'><NavDropdown.Item><i className='fa fa-plus-square'></i> New Employee</NavDropdown.Item></LinkContainer>
                                <NavDropdown.Item href='/chargebacks'>Chargebacks</NavDropdown.Item>
                                <LinkContainer to='/app/manifests'><NavDropdown.Item><i className='fas fa-clipboard-list'></i> Manifests</NavDropdown.Item></LinkContainer>
                                <LinkContainer to='/app/manifests/generate'><NavDropdown.Item><i className='fas fa-clipboard'></i> Generate Manifests</NavDropdown.Item></LinkContainer>
                                <InputGroup style={{paddingLeft: '10px', paddingRight: '10px', width: '350px'}}>
                                    <InputGroup.Prepend><InputGroup.Text>Manifest ID: </InputGroup.Text></InputGroup.Prepend>
                                    <FormControl
                                        name={'manifestId'}
                                        onChange={this.handleChange}
                                        value={this.state.manifestId}
                                        onKeyPress={event => {
                                            if(event.key === 'Enter' && this.state.manifestId)
                                                window.location.href = '/manifests/view/' + this.state.manifestId
                                                // this.setState({redirect: '/manifests/view/' + this.state.manifestId, manifestId: ''})
                                        }}
                                    />
                                </InputGroup>
                                <InputGroup style={{paddingLeft: '10px', paddingRight: '10px', width: '350px'}}>
                                    <InputGroup.Prepend><InputGroup.Text>Employee ID: </InputGroup.Text></InputGroup.Prepend>
                                    <Select
                                        options={this.state.employees}
                                        onChange={value => this.setState({redirect: '/app/employees/edit/' + value.value})}
                                    />
                                </InputGroup>
                            </NavDropdown>
                            <LinkContainer to='/app/dispatch'><NavLink>Dispatch</NavLink></LinkContainer>
                            <NavDropdown title='Administration' id='navbar-admin' alignRight>
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
                    <Route path='/app/accounts' exact component={Accounts}></Route>
                    <Route path='/app/appSettings' exact component={AppSettings}></Route>
                    <Route path='/app/bills/trend' component={Charts}></Route>
                    <Route exact path='/app/bills' component={Bills}></Route>
                    <Route path='/app/bills/:action/:billId?' component={Bill}></Route>
                    <Route path='/app/dispatch' component={Dispatch}></Route>
                    <Route path='/' exact component={AdminDashboard}></Route>
                    <Route path='/app/interliners' component={Interliners}></Route>
                    <Route path='/app/invoices' exact component={Invoices}></Route>
                    <Route path='/app/invoices/generate' exact component={InvoiceGenerate}></Route>
                    <Route path='/app/invoices/view/:invoiceId' component={Invoice}></Route>
                    <Route path='/app/employees' exact component={Employees}></Route>
                    <Route path='/app/employees/:action/:employeeId?' component={Employee}></Route>
                    <Route path='/app/manifests' exact component={Manifests}></Route>
                    <Route path='/app/manifests/generate' exact component={ManifestsGenerate}></Route>
                    <Route path='/app/ratesheets' exact component={Ratesheets}></Route>
                    <Route path='/app/ratesheets/:action/:ratesheetId?' component={Ratesheet}></Route>
                </Switch>
                {this.state.redirect &&
                    <Redirect to={this.state.redirect}></Redirect>
                }
            </BrowserRouter>
        )
    }
}

ReactDom.render(<App />, document.getElementById('reactDiv'))
