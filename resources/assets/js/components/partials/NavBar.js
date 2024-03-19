import React, {Fragment, useEffect, useRef, useState} from 'react'
import {Menu, menuClasses, MenuItem, Sidebar, SubMenu} from 'react-pro-sidebar'
import {AsyncTypeahead, Highlighter, Menu as AsyncMenu, MenuItem as AsyncMenuItem} from 'react-bootstrap-typeahead'
import {Link, useHistory} from 'react-router-dom'
import fullLogo from '/images/fast_forward_full_logo_transparent.png'
import shortLogo from '/images/fast_forward_short_logo_transparent.png'

import {useUser} from '../../contexts/UserContext'

const renderMenuItemChildren = (option, text) => {
    return (
        <Fragment>
            <span>
                <strong>{option.result_type}: </strong><Highlighter search={text}>{option.name}</Highlighter>
            </span>
            <br/>
            {option.result_type == 'Account' &&
                <SmallHighlighter search={text} text={`Account #: ${option.account_number}`}/>
            }
            {option.result_type == 'Account User' &&
                <SmallHighlighter search={text} text={`Email: ${option.email}`}/>
            }
            {option.result_type === 'Bill' &&
                <SmallHighlighter search={text} text={`Bill# ${option.bill_number}`}/>
            }
            {option.result_type == 'Bill' && option.charge_reference_field_name && option.charge_reference_value &&
                <SmallHighlighter search={text} text={`${option.charge_reference_field_name}: ${option.charge_reference_value}`}/>
            }
            {option.result_type == 'Bill' && option.delivery_reference_field_name && option.delivery_reference_value &&
                <SmallHighlighter search={text} text={`${option.delivery_reference_field_name}: ${option.delivery_reference_value}`}/>
            }
            {option.result_type == 'Bill' && option.pickup_reference_field_name && option.pickup_reference_value &&
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
                return <AsyncMenu id='async-search-menu' {...props} maxHeight='90vh' >
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
    const {collapsed, toggleCollapsed, menuItemStyles} = props

    return (
        <Menu iconShape='circle' menuItemStyles={menuItemStyles} style={{textAlign: 'center'}}>
            <MenuItem component={<Link to='/' />} style={{textAlign: 'center'}}>
                {collapsed ? <img src={shortLogo} alt='FFE' width='40' /> : <img src={fullLogo} alt='Fast Forward Express' width='210' style={{paddingTop: 10}} />}
            </MenuItem>
            <MenuItem style={{textAlign: 'center'}} onClick={toggleCollapsed}>
                <i className={collapsed ? 'far fa-arrow-alt-circle-right fa-lg' : 'far fa-arrow-alt-circle-left fa-lg'} />
            </MenuItem>
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

export default function NavBar(props) {
    const [collapsed, setCollapsed] = useState(false)
    const [isLoadingSearch, setIsLoadingSearch] = useState(false)
    const [searchResults, setSearchResults] = useState([])
    const searchRef = useRef(null)
    const history = useHistory()
    const {authenticatedUser} = useUser()
    const {front_end_permissions: frontEndPermissions, is_impersonating, account_users, employee, contact} = authenticatedUser

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
            width: '250px',
            overflow: collapsed ? 'visible' : 'hidden',
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
        setCollapsed(JSON.parse(localStorage.getItem('isNavBarCollapsed')) === true)
    }, [])

    useEffect(() => {
        localStorage.setItem('isNavBarCollapsed', collapsed)
    }, [collapsed])

    const getUserIcon = () => {
        if(is_impersonating)
            return 'fas fa-people-arrows'
        if(account_users === null && employee === null)
            return 'fas fa-dragon'
        else if(employee)
            return 'fas fa-user-ninja'
        return 'fas fa-user-circle'
    }

    /** Selections is always passed as an array even when multiselect is not enabled */
    const handleSearchSelect = selections => {
        if(selections.length) {
            searchRef.current?.clear()
            history.push(selections[0].link)
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
        setCollapsed(!collapsed)
    }

    const unimpersonate = () => {
        makeAjaxRequest('/users/unimpersonate', 'GET', null, response => location.reload())
    }

    return (
        <Sidebar
            collapsed={collapsed}
            // toggled={collapsed}
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
                <SidebarHeader collapsed={collapsed} toggleCollapsed={toggleCollapsed} menuItemStyles={menuItemStyles}/>
                <div style={{display: 'flex', flexDirection: 'column', flex: 1}}>
                    <Menu iconShape='circle' menuItemStyles={menuItemStyles}>
                        {hasAnyPermission(frontEndPermissions.bills) &&
                            <SubMenu label={<h5>Bills</h5>} icon={<i className='fas fa-boxes fa-lg'/>}>
                                {frontEndPermissions.bills.viewAny &&
                                    <MenuItem component={<Link to='/bills' />} icon={<i className='fa fa-list'></i>}>List Bills</MenuItem>
                                }
                                {frontEndPermissions.bills.create &&
                                    <MenuItem component={<Link to='/bills/create' />} icon={<i className='fa fa-plus-square'></i>}>Create Bill</MenuItem>
                                }
                                {/* {frontEndPermissions.appSettings.edit && frontEndPermissions.bills.create &&
                                    <MenuItem component={<Link to='/bills/create/bulk' />} icon={<i className='fas fa-mail-bulk'></i>}>Create Bills in Bulk</MenuItem>
                                } */}
                                {frontEndPermissions.appSettings.edit &&
                                    <MenuItem component={<Link to='/bills/trend' />} icon={<i className='fas fa-chart-bar'></i>}>Trend</MenuItem>
                                }
                            </SubMenu>
                        }
                        {hasAnyPermission(frontEndPermissions.invoices) &&
                            <SubMenu label={<h5>Invoices</h5>} icon={<i className='fas fa-file-invoice-dollar fa-lg'/>}>
                                {frontEndPermissions.invoices.viewAny &&
                                    <MenuItem component={<Link to='/invoices' />} icon={<i className='fa fa-list'></i>}>List Invoices</MenuItem>
                                }
                                {frontEndPermissions.invoices.create &&
                                    <MenuItem component={<Link to='/invoices/generate' />} icon={<i className='fa fa-plus-square'></i>}>Generate Invoices</MenuItem>
                                }
                            </SubMenu>
                        }
                        {hasAnyPermission(frontEndPermissions.accounts) &&
                            <SubMenu label={<h5>Accounts</h5>} icon={<i className='fas fa-city fa-lg'/>}>
                                {(props.authenticatedAccountUsers && props.accounts.length == 1) &&
                                    <MenuItem component={<Link to={`/accounts/${props.authenticatedAccountUsers[0]?.account_id}`} />} icon={<i className='fas fa-building'></i>}>
                                        {props.accounts.find(account => account.value === props.authenticatedAccountUsers[0].account_id).label}
                                    </MenuItem>
                                }
                                {frontEndPermissions.accounts.viewAny &&
                                    <MenuItem component={<Link to='/accounts' />} icon={<i className='fa fa-list'></i>}>List Accounts</MenuItem>
                                }
                                {frontEndPermissions.accounts.create &&
                                    <MenuItem component={<Link to='/accounts/create' />} href='/accounts/create' icon={<i className='fa fa-plus-square'></i>}>Create Account</MenuItem>
                                }
                                {frontEndPermissions.appSettings.edit &&
                                    <MenuItem component={<Link to='/accountsReceivable' />} icon={<i className='fas fa-balance-scale'></i>}>Accounts Receivable</MenuItem>
                                }
                                {frontEndPermissions.appSettings.edit &&
                                    <MenuItem component={<Link to='/accountsPayable' />} icon={<i className='fas fa-funnel-dollar'></i>}>Accounts Payable</MenuItem>
                                }
                            </SubMenu>
                        }
                        {hasAnyPermission(frontEndPermissions.employees) &&
                            <SubMenu label={<h5>Employees</h5>} icon={<i className='fas fa-id-card-alt fa-lg'/>}>
                                {frontEndPermissions.employees.viewAll &&
                                    <MenuItem component={<Link to='/employees' />} icon={<i className='fa fa-list'></i>}>List Employees</MenuItem>
                                }
                                {frontEndPermissions.employees.create &&
                                    <MenuItem component={<Link to='/employees/create' />} icon={<i className='fa fa-plus-square'></i>}>Create Employee</MenuItem>
                                }
                                {frontEndPermissions.chargebacks.viewAny &&
                                    <MenuItem component={<Link to='/chargebacks' />} icon={<i className='fas fa-cash-register'></i>}> Chargebacks</MenuItem>
                                }
                                {frontEndPermissions.manifests.viewAny &&
                                    <MenuItem component={<Link to='/manifests' />} icon={<i className='fas fa-clipboard-list'></i>}>Manifests</MenuItem>
                                }
                                {frontEndPermissions.manifests.create &&
                                    <MenuItem component={<Link to='/manifests/generate' />} icon={<i className='fas fa-clipboard'></i>}>Generate Manifests</MenuItem>
                                }
                            </SubMenu>
                        }
                        {frontEndPermissions.bills.dispatch &&
                            <MenuItem component={<Link to='/dispatch' />} icon={<i className='fas fa-headset fa-lg'></i>}><h5>Dispatch</h5></MenuItem>
                        }
                        {frontEndPermissions.appSettings.edit &&
                            <SubMenu label={<h5>App Settings</h5>} icon={<i className='fas fa-toolbox fa-lg'></i>}>
                                <MenuItem component={<Link to='/appSettings#accounting' />} icon={<i className='fas fa-calculator'></i>}>Accounting</MenuItem>
                                <MenuItem component={<Link to='/appSettings#interliners' />} icon={<i className='fas fa-shipping-fast'></i>}>Interliners</MenuItem>
                                <MenuItem component={<Link to='/appSettings#ratesheets' />} icon={<i className='fas fa-tags'></i>}>Ratesheets</MenuItem>
                                <MenuItem component={<Link to='/appSettings#scheduling' />} icon={<i className='fas fa-calendar-alt'></i>}>Scheduling</MenuItem>
                                <MenuItem component={<Link to='/appSettings#selections' />} icon={<i className='fas fa-list-ul'></i>}>Selections</MenuItem>
                            </SubMenu>
                        }
                    </Menu>
                </div>
                <hr/>
                <div style={{display: 'flex', flexDirection: 'column'}}>
                    <Menu iconShape='circle' menuItemStyles={{...menuItemStyles, overflow: 'visible'}}>
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
                        <SubMenu label={contact ? contact.display_name : 'User'} icon={<i className={getUserIcon()}/>} defaultOpen={false}>
                            {authenticatedUser.employee?.employee_id &&
                                <MenuItem component={<Link to={`/employees/${authenticatedUser.employee.employee_id}`} />} icon={<i className='fas fa-user-ninja'></i>}>
                                    {contact.display_name}
                                </MenuItem>
                            }
                            <MenuItem icon={<i className='fas fa-user-shield'></i>} onClick={props.toggleChangePasswordModal}> Change Password</MenuItem>
                            <MenuItem component={<Link to='/user_settings' />} icon={<i className='fas fa-cog'></i>}>User Preferences</MenuItem>
                            {is_impersonating &&
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
