import {Redirect, Route, Switch} from 'react-router-dom'
import {Col, Row} from 'react-bootstrap'

import {useUser} from '../../contexts/UserContext'

import Account from '../accounts/Account'
import Accounts from '../accounts/Accounts'
import AccountsPayableReceivable from '../admin/AccountsPayableReceivable'
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
import Invoice from '../invoices/Invoice'
import Invoices from '../invoices/Invoices'
import Manifest from '../manifests/Manifest'
import Manifests from '../manifests/Manifests'
import PageNotFound from './PageNotFound'
import Ratesheet from '../ratesheets/Ratesheet'
import Search from '../search/Search'
import UserSettings from '../users/UserSettings'

import NewBill from '../bills_new/Bill'

export default function Router(props) {
    const {authenticatedUser} = useUser()

    const {front_end_permissions: frontEndPermissions} = authenticatedUser

    return (
        <main style={{maxHeight: '100vh', overflowY: 'auto', overflowX: 'hidden', width: '100%'}}>
            <Row className='justify-content-md-center'>
                <Col md={12}>
                    <Switch>
                        <Route exact path='/'>
                            <Redirect to={props.homePage} />
                        </Route>
                        <Route exact path='/error404' component={PageNotFound}></Route>
                        <Route path='/search' exact component={Search}></Route>
                        <Route path='/user_settings' exact component={UserSettings}></Route>
                        {frontEndPermissions.accounts.viewAny &&
                            <Route path='/accounts' exact component={Accounts}></Route>
                        }
                        {frontEndPermissions.accounts.create &&
                            <Route path='/accounts/create' exact component={Account}></Route>
                        }
                        {frontEndPermissions.accounts.viewAny &&
                            <Route path='/accounts/:accountId' exact component={Account}></Route>
                        }
                        {frontEndPermissions.appSettings.edit &&
                            <Route path='/accountsPayable' exact render={routeProps => <AccountsPayableReceivable version='Payable' {...routeProps} />}></Route>
                        }
                        {frontEndPermissions.appSettings.edit &&
                            <Route path='/accountsReceivable' exact render={routeProps => <AccountsPayableReceivable version='Receivable' {...routeProps} />}></Route>
                        }
                        {frontEndPermissions.appSettings.edit &&
                            <Route path='/appSettings' exact component={AppSettings}></Route>
                        }
                        {frontEndPermissions.bills.viewAny &&
                            <Route exact path='/bills' component={Bills}></Route>
                        }
                        {frontEndPermissions.appSettings.edit &&
                            <Route path='/bills/trend' component={Charts}></Route>
                        }
                        {frontEndPermissions.bills.create &&
                            <Route path='/bills/create' exact component={Bill}></Route>
                        }
                        {frontEndPermissions.bills.create &&
                            <Route path='/bills/new/create' exact component={NewBill}></Route>
                        }
                        {frontEndPermissions.bills.viewAny &&
                            <Route path='/bills/new/:billId' component={NewBill}></Route>
                        }
                        {frontEndPermissions.bills.viewAny &&
                            <Route path='/bills/:billId' component={Bill}></Route>
                        }
                        {frontEndPermissions.chargebacks.viewAny &&
                            <Route path='/chargebacks' exact component={Chargebacks}></Route>
                        }
                        {frontEndPermissions.bills.dispatch &&
                            <Route path='/dispatch' component={Dispatch}></Route>
                        }
                        {frontEndPermissions.appSettings.edit &&
                            <Route path='/adminDashboard' exact component={AdminDashboard}></Route>
                        }
                        {frontEndPermissions.invoices.viewAny &&
                            <Route path='/invoices' exact component={Invoices}></Route>
                        }
                        {frontEndPermissions.invoices.create &&
                            <Route path='/invoices/generate' exact component={GenerateInvoices}></Route>
                        }
                        {frontEndPermissions.invoices.viewAny &&
                            <Route path='/invoices/:invoiceId' component={Invoice}></Route>
                        }
                        {frontEndPermissions.employees.viewAll &&
                            <Route path='/employees' exact component={Employees}></Route>
                        }
                        {frontEndPermissions.employees.create &&
                            <Route path='/employees/create' component={Employee}></Route>
                        }
                        {frontEndPermissions.employees.viewAny &&
                            <Route path='/employees/:employeeId' component={Employee}></Route>
                        }
                        {frontEndPermissions.manifests.create &&
                            <Route path='/manifests/generate' exact component={GenerateManifests}></Route>
                        }
                        {frontEndPermissions.manifests.viewAny &&
                            <Route path='/manifests/:manifestId' exact component={Manifest}></Route>
                        }
                        {frontEndPermissions.manifests.viewAny &&
                            <Route path='/manifests' exact component={Manifests}></Route>
                        }
                        {frontEndPermissions.appSettings.edit &&
                            <Route path='/ratesheets/create' component={Ratesheet}></Route>
                        }
                        {frontEndPermissions.appSettings.edit &&
                            <Route path='/ratesheets/:ratesheetId' component={Ratesheet}></Route>
                        }
                    </Switch>
                </Col>
            </Row>
        </main>
    )
}

