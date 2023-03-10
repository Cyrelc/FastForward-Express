import React, {Fragment, useEffect, useRef, useState} from 'react'
import {Menu, menuClasses, MenuItem, Sidebar, SubMenu, useProSidebar} from 'react-pro-sidebar'
import {Card} from 'react-bootstrap'
import {connect} from 'react-redux'
import {push} from 'connected-react-router'
import {AsyncTypeahead, Highlighter, Menu as AsyncMenu, MenuItem as AsyncMenuItem} from 'react-bootstrap-typeahead'
import {Link} from 'react-router-dom'

const renderMenuItemChildren = (option, text) => {
    return (
        <Fragment>
            <span>
                <strong>{option.type}: </strong><Highlighter search={text}>{option.name}</Highlighter>
            </span>
            {option.type == 'Account' &&
                <SmallHighlighter search={text} text={`Account #: ${option.account_number}`}/>
            }
            {option.type == 'Account User' &&
                <SmallHighlighter search={text} text={`Email: ${option.email}`}/>
            }
            {option.type === 'Bill' &&
                <SmallHighlighter search={text} text={`Bill# ${option.bill_number}`}/>
            }
            {option.type == 'Bill' && option.charge_reference_field_name && option.charge_reference_value &&
                <SmallHighlighter search={text} text={`${option.charge_reference_field_name}: ${option.charge_reference_value}`}/>
            }
            {option.type == 'Bill' && option.delivery_reference_field_name && option.delivery_reference_value &&
                <SmallHighlighter search={text} text={`${option.delivery_reference_field_name}: ${option.delivery_reference_value}`}/>
            }
            {option.type == 'Bill' && option.pickup_reference_field_name && option.pickup_reference_value &&
                <SmallHighlighter search={text} text={`${option.pickup_reference_field_name}: ${option.pickup_reference_value}`} />
            }
        </Fragment>
    )
}

const FFETypeAhead = props => {
    const {searchRef, isLoadingSearch, handleSearchSelect, performSearch, searchResults} = props

    return (
        <AsyncTypeahead
            ref={searchRef}
            id='react-typeahead'
            align='left'
            debounce={2000}
            dropup
            filterBy={() => true}
            isLoading={isLoadingSearch}
            labelKey='name'
            minLength={3}
            onChange={handleSearchSelect}
            onSearch={performSearch}
            options={searchResults}
            placeholder="Type at least three characters to begin searching"
            positionFixed
            renderMenu={(results, props, search) => {
                return <AsyncMenu id='async-search-menu' {...props} maxHeight='90vh'>
                    {results.map((result, index) =>
                        <AsyncMenuItem key={index} option={result} position={index}>
                            {renderMenuItemChildren(result, search.text, index)}
                        </AsyncMenuItem>
                    )}
                </AsyncMenu>
            }}
            selectHint={(shouldSelectHint, event) => {
                if(event.key == 'Enter')
                    return true
                return false
            }}
            size='sm'
        />
    )
}

const SidebarHeader = props => {
    const {collapsed, collapseSidebar, menuItemStyles} = props

    return (
        <Menu iconShape='circle' menuItemStyles={menuItemStyles} style={{textAlign: 'center'}}>
            <MenuItem component={<Link to='/' />} style={{textAlign: 'center'}}>
                <h5>{collapsed ? 'FFE' : 'Fast Forward Express'}</h5>
            </MenuItem>
            <i className={collapsed ? 'far fa-arrow-alt-circle-right fa-lg' : 'far fa-arrow-alt-circle-left fa-lg'} onClick={() => collapseSidebar(!collapsed)}/>
            <hr/>
        </Menu>
    )
}

const SmallHighlighter = props => {
    return (
        <small>
            <Highlighter search={props.search}>
                {props.text}
            </Highlighter>
            <br/>
        </small>
    )
}

function NavBar(props) {
    const [isLoadingSearch, setIsLoadingSearch] = useState(false)
    const [searchResults, setSearchResults] = useState([])
    const searchRef = useRef(null)

    const {collapsed, collapseSidebar, toggleSidebar} = useProSidebar();

    const menuItemStyles = {
        root: {
            fontSize: '15px',
            fontWeight: 400,
            color: 'gainsboro'
        },
        icon: {
            color: 'gainsboro',
            [`&.${menuClasses.disabled}`]: {color: '#3e5e7e'}
        },
        SubMenuExpandIcon: {
            color: '#gainsboro',
        },
        subMenuContent: {
            backgroundColor: collapsed ? 'black' : 'transparent',
            width: '250px'
        },
        button: {
            [`&.${menuClasses.disabled}`]: {
                color: '#3e5e7e'
            },
            '&:hover': {
                backgroundColor: '#00458b',
                color: '#b6c8d9'
            }
        },
        label: ({open}) => {
            fontWeight: open ? 600 : undefined
        }
    }

    useEffect(() => {
        collapseSidebar(!!localStorage.getItem('isNavBarCollapsed'))
    }, [])

    useEffect(() => {
        toggleSidebar(false)
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

    /** Selections is always passed as an array even when multiselect is not enabled */
    const handleSearchSelect = selections => {
        if(selections.length) {
            searchRef.current?.clear()
            props.history.push(selections[0].link)
        }
    }

    const hasAnyPermission = permissionArray => {
        if(permissionArray === undefined)
            return false
        for(const [key, value] of Object.entries(permissionArray))
            if(value === true)
                return true
        return false
    }

    const performSearch = (query) => {
        if(isLoadingSearch)
            return
        setIsLoadingSearch(true)
        makeAjaxRequest(`/search?query=${query}`, 'GET', null, response => {
            setSearchResults(response)
            setIsLoadingSearch(false)
        }, error => {
            setIsLoadingSearch(false)
        })
    }

    const toggleCollapsed = () => {
        localStorage.setItem('isNavBarCollapsed', !isCollapsed)
        setIsCollapsed(!isCollapsed)
    }

    const unimpersonate = () => {
        makeAjaxRequest('/users/unimpersonate', 'GET', null, response => location.reload())
    }

    return (
        <Sidebar
            collapsed={collapsed}
            toggled={true}
        >
            <div
                style={{
                    backgroundImage: 'linear-gradient(to bottom, black, #0770b1, black)',
                    display: 'flex',
                    flexDirection: 'column',
                    minHeight: '100%',
                    color: '#808080'
                }}
            >
                <SidebarHeader collapsed={collapsed} collapseSidebar={collapseSidebar} menuItemStyles={menuItemStyles}/>
                <div style={{display: 'flex', flexDirection: 'column', flex: 1}}>
                    <Menu iconShape='circle' menuItemStyles={menuItemStyles}>
                        {hasAnyPermission(props.frontEndPermissions.bills) &&
                            <SubMenu label={<h5>Bills</h5>} icon={<i className='fas fa-boxes fa-lg'/>}>
                                {props.frontEndPermissions.bills.viewAny &&
                                    <MenuItem component={<Link to='/app/bills' />} icon={<i className='fa fa-list'></i>}>List Bills</MenuItem>
                                }
                                {props.frontEndPermissions.bills.create &&
                                    <MenuItem component={<Link to='/app/bills/create' />} icon={<i className='fa fa-plus-square'></i>}>Create Bill</MenuItem>
                                }
                                {props.frontEndPermissions.appSettings.edit &&
                                    <MenuItem component={<Link to='/app/bills/trend' />} icon={<i className='fas fa-chart-bar'></i>}>Trend</MenuItem>
                                }
                            </SubMenu>
                        }
                        {hasAnyPermission(props.frontEndPermissions.invoices) &&
                            <SubMenu label={<h5>Invoices</h5>} icon={<i className='fas fa-file-invoice-dollar fa-lg'/>}>
                                {props.frontEndPermissions.invoices.viewAny &&
                                    <MenuItem component={<Link to='/app/invoices' />} icon={<i className='fa fa-list'></i>}>List Invoices</MenuItem>
                                }
                                {props.frontEndPermissions.invoices.create &&
                                    <MenuItem component={<Link to='/app/invoices/generate' />} icon={<i className='fa fa-plus-square'></i>}>Generate Invoices</MenuItem>
                                }
                            </SubMenu>
                        }
                        {hasAnyPermission(props.frontEndPermissions.accounts) &&
                            <SubMenu label={<h5>Accounts</h5>} icon={<i className='fas fa-city fa-lg'/>}>
                                {(props.authenticatedAccountUsers && props.accounts.length == 1) &&
                                    <MenuItem component={<Link to={`/app/accounts/${props.authenticatedAccountUsers[0]?.account_id}`} />} icon={<i className='fas fa-building'></i>}>
                                        {props.accounts.find(account => account.value === props.authenticatedAccountUsers[0].account_id).label}
                                    </MenuItem>
                                }
                                {props.frontEndPermissions.accounts.viewAny &&
                                    <MenuItem component={<Link to='/app/accounts' />} icon={<i className='fa fa-list'></i>}>List Accounts</MenuItem>
                                }
                                {props.frontEndPermissions.accounts.create &&
                                    <MenuItem component={<Link to='/app/accounts/create' />} href='/app/accounts/create' icon={<i className='fa fa-plus-square'></i>}>Create Account</MenuItem>
                                }
                                {props.frontEndPermissions.appSettings.edit &&
                                    <MenuItem component={<Link to='/app/accountsReceivable' />} icon={<i className='fas fa-balance-scale'></i>}>Accounts Receivable</MenuItem>
                                }
                            </SubMenu>
                        }
                        {hasAnyPermission(props.frontEndPermissions.employees) &&
                            <SubMenu label={<h5>Employees</h5>} icon={<i className='fas fa-id-card-alt fa-lg'/>}>
                                {props.frontEndPermissions.employees.viewAll &&
                                    <MenuItem component={<Link to='/app/employees' />} icon={<i className='fa fa-list'></i>}>List Employees</MenuItem>
                                }
                                {props.frontEndPermissions.employees.create &&
                                    <MenuItem component={<Link to='/app/employees/create' />} icon={<i className='fa fa-plus-square'></i>}>Create Employee</MenuItem>
                                }
                                {props.frontEndPermissions.chargebacks.viewAny &&
                                    <MenuItem component={<Link to='/app/chargebacks' />} icon={<i className='fas fa-cash-register'></i>}> Chargebacks</MenuItem>
                                }
                                {props.frontEndPermissions.manifests.viewAny &&
                                    <MenuItem component={<Link to='/app/manifests' />} icon={<i className='fas fa-clipboard-list'></i>}>Manifests</MenuItem>
                                }
                                {props.frontEndPermissions.manifests.create &&
                                    <MenuItem component={<Link to='/app/manifests/generate' />} icon={<i className='fas fa-clipboard'></i>}>Generate Manifests</MenuItem>
                                }
                            </SubMenu>
                        }
                        {props.frontEndPermissions.bills.dispatch &&
                            <MenuItem component={<Link to='/app/dispatch' />} icon={<i className='fas fa-headset fa-lg'></i>}><h5>Dispatch</h5></MenuItem>
                        }
                        {props.frontEndPermissions.appSettings.edit &&
                            <SubMenu label={<h5>App Settings</h5>} icon={<i className='fas fa-toolbox fa-lg'></i>}>
                                <MenuItem component={<Link to='/app/appSettings#accounting' />} icon={<i className='fas fa-calculator'></i>}>Accounting</MenuItem>
                                <MenuItem component={<Link to='/app/appSettings#interliners' />} icon={<i className='fas fa-shipping-fast'></i>}>Interliners</MenuItem>
                                <MenuItem component={<Link to='/app/appSettings#ratesheets' />} icon={<i className='fas fa-tags'></i>}>Ratesheets</MenuItem>
                                <MenuItem component={<Link to='/app/appSettings#scheduling' />} icon={<i className='fas fa-calendar-alt'></i>}>Scheduling</MenuItem>
                            </SubMenu>
                        }
                    </Menu>
                </div>
                <hr/>
                <div style={{display: 'flex', flexDirection: 'column'}}>
                    <Menu iconShape='circle' menuItemStyles={{...menuItemStyles, subMenuContent: {...menuItemStyles.subMenuContent, overflow: 'visible'}}}>
                        {collapsed ?
                            <SubMenu
                                label='Search'
                                icon={<i className='fas fa-search'></i>}
                                onOpenChange={isOpen => {
                                    if(isOpen)
                                        searchRef.current?.focus()
                                }}
                            >
                                <MenuItem style={{padding: '20px'}}>
                                    <FFETypeAhead
                                        handleSearchSelect={handleSearchSelect}
                                        isLoadingSearch={isLoadingSearch}
                                        performSearch={performSearch}
                                        searchRef={searchRef}
                                        searchResults={searchResults}
                                    />
                                </MenuItem>
                            </SubMenu>
                            :
                            <MenuItem>
                                <FFETypeAhead
                                    handleSearchSelect={handleSearchSelect}
                                    isLoadingSearch={isLoadingSearch}
                                    performSearch={performSearch}
                                    searchRef={searchRef}
                                    searchResults={searchResults}
                                />
                            </MenuItem>
                        }
                        <SubMenu label={props.contact ? `${props.contact.first_name} ${props.contact.last_name}` : 'User'} icon={<i className={getUserIcon()}/>}>
                            {props.authenticatedEmployee?.employee_id &&
                                <MenuItem component={<Link to={`/app/employees/${props.authenticatedEmployee.employee_id}`} />} icon={<i className='fas fa-user-ninja'></i>}>
                                    {`${props.contact.first_name} ${props.contact.last_name}`}
                                </MenuItem>
                            }
                            <MenuItem icon={<i className='fas fa-user-shield'></i>} onClick={props.toggleChangePasswordModal}> Change Password</MenuItem>
                            <MenuItem component={<Link to='/app/user_settings' />} icon={<i className='fas fa-cog'></i>}>User Preferences</MenuItem>
                            {props.isImpersonating &&
                                <MenuItem onClick={unimpersonate}><i className='fas fa-people-arrows'></i> Unimpersonate</MenuItem>
                            }
                            <MenuItem onClick={() => window.location.href='/logout'} icon={<i className='fas fa-door-open' />}>Log Out</MenuItem>
                        </SubMenu>
                    </Menu>
                </div>
            </div>
        </Sidebar>
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

