import React, {Fragment, useEffect, useRef, useState} from 'react'
import {Menu, MenuItem, ProSidebar, SidebarContent, SidebarFooter, SidebarHeader, SubMenu} from 'react-pro-sidebar'
import {connect} from 'react-redux'
import {LinkContainer} from 'react-router-bootstrap'
import {push} from 'connected-react-router'
import {FormControl} from 'react-bootstrap'

const objectTypes = [
    {label: 'All', value: ''},
    {label: 'Accounts', value: 'accounts'},
    {label: 'Bills', value: 'bills'},
    {label: 'Employees', value: 'employees'},
    {label: 'Invoices', value: 'invoices'},
    {label: 'Manifests', value: 'manifests'},
    {label: 'User', value: 'user'}
]

function NavBar(props) {
    const [isCollapsed, setIsCollapsed] = useState(localStorage.getItem('isNavBarCollapsed') ? true : false)
    const [searchObjectType, setSearchObjectState] = useState({label: 'All', value: ''})
    const [searchTerm, setSearchTerm] = useState('')
    const [searchSubmenuOpen, setSearchSubmenuOpen] = useState(false)
    const searchPopoverRef = useRef(null)

    useEffect(() => {
        const storedSearchObjectType = localStorage.getItem('searchObjectType')
        console.log(storedSearchObjectType)
        if(storedSearchObjectType)
            setSearchObjectState(JSON.parse(storedSearchObjectType))
    }, [])

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

    const performSearch = () => {
        if(!searchTerm)
            return
        setSearchTerm('')
        props.history.push(`/app/search?term=${searchTerm}&objectType=${searchObjectType.value}`)
    }

    const setSearchObjectType = type => {
        setSearchObjectState(type)
        searchPopoverRef.current.focus()
        localStorage.setItem('searchObjectType', JSON.stringify(type))
        setSearchSubmenuOpen(false)
    }

    const toggleCollapsed = () => {
        localStorage.setItem('isNavBarCollapsed', !isCollapsed)
        setIsCollapsed(!isCollapsed)
    }

    const unimpersonate = () => {
        makeAjaxRequest('/users/unimpersonate', 'GET', null, response => location.reload())
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
                                    <MenuItem icon={<i className='fa fa-list'></i>}>List Bills</MenuItem>
                                </LinkContainer>
                            }
                            {props.frontEndPermissions.bills.create &&
                                <LinkContainer to='/app/bills/create'>
                                    <MenuItem icon={<i className='fa fa-plus-square'></i>}>Create Bill</MenuItem>
                                </LinkContainer>
                            }
                            {props.frontEndPermissions.appSettings.edit &&
                                <LinkContainer to='/app/bills/trend'>
                                    <MenuItem icon={<i className='fas fa-chart-bar'></i>}>Trend</MenuItem>
                                </LinkContainer>
                            }
                        </SubMenu>
                    }
                    {hasAnyPermission(props.frontEndPermissions.invoices) &&
                        <SubMenu title={<h5> Invoices</h5>} icon={<i className='fas fa-file-invoice-dollar fa-lg'/>}>
                            {props.frontEndPermissions.invoices.viewAny &&
                                <LinkContainer to='/app/invoices'>
                                    <MenuItem icon={<i className='fa fa-list'></i>}>List Invoices</MenuItem>
                                </LinkContainer>
                            }
                            {props.frontEndPermissions.invoices.create &&
                                <LinkContainer to='/app/invoices/generate'>
                                    <MenuItem icon={<i className='fa fa-plus-square'></i>}>Generate Invoices</MenuItem>
                                </LinkContainer>
                            }
                        </SubMenu>
                    }
                    {hasAnyPermission(props.frontEndPermissions.accounts) &&
                        <SubMenu title={<h5> Accounts</h5>} icon={<i className='fas fa-city fa-lg'/>}>
                            {(props.authenticatedAccountUsers && props.accounts.length == 1) &&
                                <LinkContainer to={`/app/accounts/${props.authenticatedAccountUsers[0]?.account_id}`}>
                                    <MenuItem icon={<i className='fas fa-building'></i>}>
                                        {props.accounts.find(account => account.value === props.authenticatedAccountUsers[0].account_id).label}
                                    </MenuItem>
                                </LinkContainer>
                            }
                            {props.frontEndPermissions.accounts.viewAny &&
                                <LinkContainer to='/app/accounts'>
                                    <MenuItem icon={<i className='fa fa-list'></i>}>List Accounts</MenuItem>
                                </LinkContainer>
                            }
                            {props.frontEndPermissions.accounts.create &&
                                <LinkContainer to='/app/accounts/create'>
                                    <MenuItem href='/app/accounts/create' icon={<i className='fa fa-plus-square'></i>}>Create Account</MenuItem>
                                </LinkContainer>
                            }
                            {props.frontEndPermissions.appSettings.edit &&
                                <LinkContainer to='/app/accountsReceivable'>
                                    <MenuItem icon={<i className='fas fa-balance-scale'></i>}>Accounts Receivable</MenuItem>
                                </LinkContainer>
                            }
                        </SubMenu>
                    }
                    {hasAnyPermission(props.frontEndPermissions.employees) &&
                        <SubMenu title={<h5> Employees</h5>} icon={<i className='fas fa-id-card-alt fa-lg'/>}>
                            {props.frontEndPermissions.employees.viewAll &&
                                <LinkContainer to='/app/employees'>
                                    <MenuItem icon={<i className='fa fa-list'></i>}>List Employees</MenuItem>
                                </LinkContainer>
                            }
                            {props.frontEndPermissions.employees.create &&
                                <LinkContainer to='/app/employees/create'>
                                    <MenuItem icon={<i className='fa fa-plus-square'></i>}>Create Employee</MenuItem>
                                </LinkContainer>
                            }
                            {props.frontEndPermissions.chargebacks.viewAny &&
                                <LinkContainer to='/app/chargebacks'>
                                    <MenuItem icon={<i className='fas fa-cash-register'></i>}> Chargebacks</MenuItem>
                                </LinkContainer>
                            }
                            {props.frontEndPermissions.manifests.viewAny &&
                                <LinkContainer to='/app/manifests'>
                                    <MenuItem icon={<i className='fas fa-clipboard-list'></i>}>Manifests</MenuItem>
                                </LinkContainer>
                            }
                            {props.frontEndPermissions.manifests.create &&
                                <LinkContainer to='/app/manifests/generate'>
                                    <MenuItem icon={<i className='fas fa-clipboard'></i>}>Generate Manifests</MenuItem>
                                </LinkContainer>
                            }
                        </SubMenu>
                    }
                    {props.frontEndPermissions.bills.dispatch &&
                        <LinkContainer to='/app/dispatch'>
                            <MenuItem icon={<i className='fas fa-headset fa-lg'></i>}><h5>Dispatch</h5></MenuItem>
                        </LinkContainer>
                    }
                    {props.frontEndPermissions.appSettings.edit &&
                        <SubMenu title={<h5>App Settings</h5>} icon={<i className='fas fa-toolbox fa-lg'></i>}>
                            <LinkContainer to='/app/appSettings#accounting'>
                                <MenuItem icon={<i className='fas fa-calculator'></i>}>Accounting</MenuItem>
                            </LinkContainer>
                            <LinkContainer to='/app/appSettings#interliners'>
                                <MenuItem icon={<i className='fas fa-shipping-fast'></i>}>Interliners</MenuItem>
                            </LinkContainer>
                            <LinkContainer to='/app/appSettings#ratesheets'>
                                <MenuItem icon={<i className='fas fa-tags'></i>}>Ratesheets</MenuItem>
                            </LinkContainer>
                            <LinkContainer to='/app/appSettings#scheduling'>
                                <MenuItem icon={<i className='fas fa-calendar-alt'></i>}>Scheduling</MenuItem>
                            </LinkContainer>
                        </SubMenu>
                    }
                </Menu>
            </SidebarContent>
            <SidebarFooter>
                <Menu iconShape='circle'>
                {isCollapsed ?
                    <SubMenu
                        title='Search'
                        icon={<i className='fas fa-search'></i>}
                        onClick={() => searchPopoverRef.current.focus()}
                    >
                        <SubMenu
                            title={`Search: ${searchObjectType.label}`}
                            onClick={() => setSearchSubmenuOpen(!searchSubmenuOpen)}
                        >
                            {
                                objectTypes.map(objectType =>
                                    <MenuItem key={objectType.value} onClick={() => setSearchObjectType(objectType)}>
                                        {objectType.label}
                                    </MenuItem>
                                )
                            }
                        </SubMenu>
                        <MenuItem>
                            <FormControl
                                name={'searchTerm'}
                                onChange={event => setSearchTerm(event.target.value)}
                                value={searchTerm}
                                ref={searchPopoverRef}
                                onKeyPress={event => {
                                    if(event.key === 'Enter' && searchTerm)
                                        performSearch()
                                }}
                            />
                        </MenuItem>
                    </SubMenu>
                    :
                    <Fragment>
                        <SubMenu
                            open={searchSubmenuOpen}
                            title={`Search: ${searchObjectType.label}`}
                            onClick={() => setSearchSubmenuOpen(!searchSubmenuOpen)}
                        >
                            {
                                objectTypes.map(objectType =>
                                    <MenuItem key={objectType.value} onClick={() => setSearchObjectType(objectType)}>
                                        {objectType.label}
                                    </MenuItem>
                                )
                            }
                        </SubMenu>
                        <MenuItem icon={<i className='fas fa-search'></i>}>
                            <FormControl
                                ref={searchPopoverRef}
                                name={'searchTerm'}
                                onChange={event => setSearchTerm(event.target.value)}
                                onKeyPress={event => {
                                    if(event.key === 'Enter' && searchTerm)
                                        performSearch()
                                }}
                                value={searchTerm}
                            />
                        </MenuItem>
                    </Fragment>
                }
                    <SubMenu title={props.contact ? `${props.contact.first_name} ${props.contact.last_name}` : 'User'} icon={<i className={getUserIcon()}/>}>
                        {props.authenticatedEmployee?.employee_id &&
                            <LinkContainer to={`/app/employees/${props.authenticatedEmployee.employee_id}`}>
                                <MenuItem icon={<i className='fas fa-user-ninja'></i>}>{`${props.contact.first_name} ${props.contact.last_name}`}</MenuItem>
                            </LinkContainer>
                        }
                        <MenuItem icon={<i className='fas fa-user-shield'></i>} onClick={props.toggleChangePasswordModal}> Change Password</MenuItem>
                        <LinkContainer to='/app/user_settings'>
                            <MenuItem icon={<i className='fas fa-cog'></i>}>User Preferences</MenuItem>
                        </LinkContainer>
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
        authenticatedAccountUsers: store.user.authenticatedAccountUsers,
        authenticatedEmployee: store.user.authenticatedEmployee,
        authenticatedUserId: store.user.authenticatedUserId,
        contact: store.user.authenticatedUserContact,
        employees: store.app.employees,
        frontEndPermissions: store.user.frontEndPermissions,
        isImpersonating: store.user.isImpersonating
    }
}

export default connect(mapStateToProps, matchDispatchToProps)(NavBar)

