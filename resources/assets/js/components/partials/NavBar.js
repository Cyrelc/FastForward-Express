import React, {useState} from 'react'
import {Menu, MenuItem, ProSidebar, SidebarContent, SidebarFooter, SidebarHeader, SubMenu} from 'react-pro-sidebar'
import {connect} from 'react-redux'
import {LinkContainer} from 'react-router-bootstrap'
import {FormControl, InputGroup} from 'react-bootstrap'
import {push} from 'connected-react-router'
import Select from 'react-select'

function NavBar(props) {
    const [billId, setBillId] = useState('')
    const [invoiceId, setInvoiceId] = useState('')
    const [isCollapsed, setIsCollapsed] = useState(localStorage.getItem('isNavBarCollapsed') ? true : false)
    const [manifestId, setManifestId] = useState('')

    const getUserIcon = () => {
        if(props.isImpersonating)
            return 'fas fa-people-arrows'
        if(props.authenticatedAccountUsers === null && props.authenticatedEmployee === null)
            return 'fas fa-dragon'
        else if(props.authenticatedEmployee)
            return 'fas fa-user-ninja'
        return 'fas fa-user-circle'
    }

    const hasAnyPermission = permissionArray => {
        if(permissionArray === undefined)
            return false
        for(const [key, value] of Object.entries(permissionArray))
            if(value === true)
                return true
        return false
    }

    const unimpersonate = () => {
        makeAjaxRequest('/users/unimpersonate', 'GET', null, response => location.reload())
    }

    const toggleCollapsed = () => {
        localStorage.setItem('isNavBarCollapsed', !isCollapsed)
        setIsCollapsed(!isCollapsed)
    }

    return (
        <ProSidebar
            collapsed={isCollapsed}
            style={{backgroundImage: 'linear-gradient(to bottom, black, #0770b1, black)'}}
        >
            <SidebarHeader style={{textAlign: 'center', listStyleType: 'none'}}>
                <Menu iconShape='circle'>
                    <LinkContainer to='/'>
                        <MenuItem>
                            <h5>{isCollapsed ? 'FFE' : 'Fast Forward Express'}</h5>
                        </MenuItem>
                    </LinkContainer>
                    <i className={isCollapsed ? 'far fa-arrow-alt-circle-right fa-lg' : 'far fa-arrow-alt-circle-left fa-lg'} onClick={toggleCollapsed}/>
                </Menu>
            </SidebarHeader>
            <SidebarContent>
                <Menu iconShape='circle'>
                    {hasAnyPermission(props.frontEndPermissions.bills) &&
                        <SubMenu title={<h5>Bills</h5>} icon={<i className='fas fa-boxes fa-lg'/>}>
                            {props.frontEndPermissions.bills.viewAny &&
                                <LinkContainer to='/app/bills'>
                                    <MenuItem><i className='fa fa-list'></i> List Bills</MenuItem>
                                </LinkContainer>
                            }
                            {props.frontEndPermissions.bills.create &&
                                <LinkContainer to='/app/bills/create'>
                                    <MenuItem><i className='fa fa-plus-square'></i> Create Bill</MenuItem>
                                </LinkContainer>
                            }
                            {props.frontEndPermissions.appSettings.edit &&
                                <LinkContainer to='/app/bills/trend'>
                                    <MenuItem><i className='fas fa-chart-bar'></i> Trend</MenuItem>
                                </LinkContainer>
                            }
                            {props.frontEndPermissions.bills.viewAny &&
                                <InputGroup style={{paddingLeft: '10px', paddingRight: '10px'}}>
                                    <InputGroup.Text>ID: </InputGroup.Text>
                                    <FormControl
                                        name={'billId'}
                                        onChange={event => setBillId(event.target.value)}
                                        type='number'
                                        min='1'
                                        value={billId}
                                        onKeyPress={event => {
                                            if(event.key === 'Enter' && billId) {
                                                props.redirect(`/app/bills/${billId}`)
                                                setBillId('')
                                                setShowBillDropdown(false)
                                            }
                                        }}
                                    />
                                </InputGroup>
                            }
                        </SubMenu>
                    }
                    {hasAnyPermission(props.frontEndPermissions.invoices) &&
                        <SubMenu title={<h5> Invoices</h5>} icon={<i className='fas fa-file-invoice-dollar fa-lg'/>}>
                            {props.frontEndPermissions.invoices.viewAny &&
                                <LinkContainer to='/app/invoices'>
                                    <MenuItem><i className='fa fa-list'></i> List Invoices</MenuItem>
                                </LinkContainer>
                            }
                            {props.frontEndPermissions.invoices.create &&
                                <LinkContainer to='/app/invoices/generate'>
                                    <MenuItem><i className='fa fa-plus-square'></i> Generate Invoices</MenuItem>
                                </LinkContainer>
                            }
                            {props.frontEndPermissions.invoices.viewAny &&
                                <InputGroup style={{paddingLeft: '10px', paddingRight: '10px'}}>
                                    <InputGroup.Text>ID: </InputGroup.Text>
                                    <FormControl
                                        name={'invoiceId'}
                                        onChange={event => setInvoiceId(event.target.value)}
                                        type='number'
                                        min='1'
                                        value={invoiceId}
                                        onKeyPress={event => {
                                            if(event.key === 'Enter' && invoiceId) {
                                                props.redirect(`/app/invoices/${invoiceId}`)
                                                setInvoiceId('')
                                            }
                                        }}
                                    />
                                </InputGroup>
                            }
                        </SubMenu>
                    }
                    {hasAnyPermission(props.frontEndPermissions.accounts) &&
                        <SubMenu title={<h5> Accounts</h5>} icon={<i className='fas fa-building fa-lg'/>}>
                            {(props.authenticatedAccountUsers && props.accounts.length == 1) &&
                                <LinkContainer to={`/app/accounts/${props.authenticatedAccountUsers[0].account_id}`}>
                                    <MenuItem>
                                        {props.accounts.find(account => account.value === props.authenticatedAccountUsers[0].account_id).label}
                                    </MenuItem>
                                </LinkContainer>
                            }
                            {props.frontEndPermissions.accounts.viewAny &&
                                <LinkContainer to='/app/accounts'>
                                    <MenuItem><i className='fa fa-list'></i> List Accounts</MenuItem>
                                </LinkContainer>
                            }
                            {props.frontEndPermissions.accounts.create &&
                                <LinkContainer to='/app/accounts/create'>
                                    <MenuItem href='/app/accounts/create'><i className='fa fa-plus-square'></i> Create Account</MenuItem>
                                </LinkContainer>
                            }
                            {props.frontEndPermissions.appSettings.edit &&
                                <LinkContainer to='/app/accountsReceivable'>
                                    <MenuItem><i className='fas fa-balance-scale'></i> Accounts Receivable</MenuItem>
                                </LinkContainer>
                            }
                            {(props.frontEndPermissions.accounts.viewAny && props.accounts.length > 1) &&
                                <InputGroup>
                                    <Select
                                        className='form-control'
                                        options={props.accounts}
                                        onChange={value => props.redirect(`/app/accounts/${value.value}`)}
                                        wrapperClassName='form-control'
                                    />
                                </InputGroup>
                            }
                        </SubMenu>
                    }
                    {hasAnyPermission(props.frontEndPermissions.employees) &&
                        <SubMenu title={<h5> Employees</h5>} icon={<i className='fas fa-id-card-alt fa-lg'/>}>
                            {props.frontEndPermissions.employees.viewAll &&
                                <LinkContainer to='/app/employees'>
                                    <MenuItem><i className='fa fa-list'></i> List Employees</MenuItem>
                                </LinkContainer>
                            }
                            {props.frontEndPermissions.employees.create &&
                                <LinkContainer to='/app/employees/create'>
                                    <MenuItem><i className='fa fa-plus-square'></i> Create Employee</MenuItem>
                                </LinkContainer>
                            }
                            {props.frontEndPermissions.chargebacks.viewAny &&
                                <LinkContainer to='/app/chargebacks'>
                                    <MenuItem><i className='fas fa-cash-register'></i> Chargebacks</MenuItem>
                                </LinkContainer>
                            }
                            {props.frontEndPermissions.manifests.viewAny &&
                                <LinkContainer to='/app/manifests'>
                                    <MenuItem><i className='fas fa-clipboard-list'></i> Manifests</MenuItem>
                                </LinkContainer>
                            }
                            {props.frontEndPermissions.manifests.create &&
                                <LinkContainer to='/app/manifests/generate'>
                                    <MenuItem><i className='fas fa-clipboard'></i> Generate Manifests</MenuItem>
                                </LinkContainer>
                            }
                            {props.frontEndPermissions.manifests.viewAny &&
                                <InputGroup>
                                    <InputGroup.Text>Manifest ID: </InputGroup.Text>
                                    <FormControl
                                        name={'manifestId'}
                                        onChange={event => setManifestId(event.target.value)}
                                        value={manifestId}
                                        onKeyPress={event => {
                                            if(event.key === 'Enter' && manifestId) {
                                                props.redirect(`/app/manifests/${manifestId}`)
                                                setManifestId('')
                                            }
                                        }}
                                    />
                                </InputGroup>
                            }
                            {props.frontEndPermissions.employees.viewAll &&
                                <Select
                                    options={props.employees}
                                    onChange={value => props.redirect(`/app/employees/${value.value}`)}
                                />
                            }
                        </SubMenu>
                    }
                    {props.frontEndPermissions.bills.dispatch &&
                        <LinkContainer to='/app/dispatch'>
                            <MenuItem icon={<i className='fas fa-headset fa-lg'></i>}><h5>Dispatch</h5></MenuItem>
                        </LinkContainer>
                    }
                    {props.frontEndPermissions.appSettings.edit &&
                        <LinkContainer to='/app/appSettings'>
                            <MenuItem icon={<i className='fas fa-toolbox fa-lg'></i>}> <h5>App Settings</h5></MenuItem>
                        </LinkContainer>
                    }
                </Menu>
            </SidebarContent>
            <SidebarFooter>
                <Menu iconShape='circle'>
                    <SubMenu title={props.contact ? `${props.contact.first_name} ${props.contact.last_name}` : 'User'} icon={<i className={getUserIcon()}/>}>
                        {props.authenticatedEmployee?.employee_id &&
                            <LinkContainer to={`/app/employees/${props.authenticatedEmployee.employee_id}`}>
                                <MenuItem icon={<i className='fas fa-user-ninja'></i>}>{`${props.contact.first_name} ${props.contact.last_name}`}</MenuItem>
                            </LinkContainer>
                        }
                        <MenuItem icon={<i className='fas fa-user-shield'></i>} onClick={props.toggleChangePasswordModal}> Change Password</MenuItem>
                        {props.isImpersonating &&
                            <MenuItem onClick={unimpersonate}><i className='fas fa-people-arrows'></i> Unimpersonate</MenuItem>
                        }
                        <MenuItem icon={<i className='fas fa-door-open'></i>}><a href='/logout'>Log Out</a></MenuItem>
                    </SubMenu>
                </Menu>
            </SidebarFooter>
        </ProSidebar>
    )
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

export default connect(mapStateToProps, matchDispatchToProps)(NavBar)

