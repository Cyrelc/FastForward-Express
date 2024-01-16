import React from 'react'
import { connect } from 'react-redux'
import Table from '../partials/Table'
import {useHistory} from 'react-router-dom'

const defaultFilterQuery = '?filter[active]=true'
/**
 * Table constants including definitions
 */
const groupByOptions = [
    {label: 'None', value: null},
    {label: 'Parent Account', value: 'parent_id', groupHeader: (value, count, data, group) => {return value + ' - ' + data[0].parent_name}},
]

const initialSort = [{column: 'account_id', dir: 'asc'}]

function Accounts(props) {
    const history = useHistory()

    const basicColumns = [
        {title: 'Account ID', field: 'account_id', ...configureFakeLink('/app/accounts/', history.push), sorter: 'number'},
        {title: 'Account Number', field: 'account_number'},
        {title: 'Parent Account', field: 'parent_name', ...configureFakeLink('/app/accounts/', history.push, null, 'parent_id')},
        {title: 'Account Name', field: 'name', ...configureFakeLink('/app/accounts/', history.push, null, 'account_id')},
        {title: 'Start Date', field: 'start_date', visible: false},
        {title: 'Invoice Interval', field: 'invoice_interval'},
        {title: 'Primary Contact', field: 'primary_contact_name'},
        {title: 'Primary Contact Phone', field: 'primary_contact_phone', headerSort: false, formatter: (cell) => {
            const cleaned = ('' + cell.getValue()).replace(/\D/g, '')
            const match = cleaned.match(/^(\d{3})(\d{3})(\d{4})$/)
            if (match)
                return `(${match[1]}) ${match[2]}-${match[3]}`
            return cell.getValue()
        }},
        {title: 'Shipping Address Name', field: 'shipping_address_name', visible: false},
        {title: 'Shipping Address', field: 'shipping_address', visible: false},
        {title: 'Billing Address Name', field: 'billing_address_name'},
        {title: 'Billing Address', field: 'billing_address', visible: false}
    ]

    const adminColumns = props.frontEndPermissions.accounts.toggleEnabled ? [
        {formatter: (cell) => {
            if(!props.frontEndPermissions.accounts.toggleEnabled)
                return

            if(cell.getValue() == 1)
                return "<button class='btn btn-sm btn-danger' title='Deactivate'><i class='far fa-times-circle'></i></button>"
            else
                return "<button class='btn btn-sm btn-success' title='Activate'><i class='far fa-check-circle'></i></button>"
        }, field: 'active', width: 50, hozAlign: 'center', cellClick:(e, cell) => toggleAccountActive(cell), headerSort: false, print: false},
        {title: 'Created On', field: 'created_at', visible: false}
    ] : [];

    const columns = Array.prototype.concat(adminColumns, basicColumns)
    const filters = [
        {name: 'Account', db_field: 'account_id', selections: props.accounts, type: 'SelectFilter', isMulti: true},
        {name: 'Active', db_field: 'active', type: 'BooleanFilter'},
        {name: 'Has Parent', db_field: 'has_parent', type: 'BooleanFilter'},
        {isMulti: true, name: 'Invoice Interval', selections: props.invoice_intervals, type: 'SelectFilter', db_field: 'invoice_interval'},
        {isMulti: true, name: 'Parent Account', selections: props.parent_accounts, type: 'SelectFilter', db_field: 'parent_id'}
    ]

    const toggleAccountActive = cell => {
        if(!props.frontEndPermissions.accounts.toggleEnabled)
            return

        const active = cell.getRow().getData().active
        if(confirm(`Are you sure you wish to ${active ? 'DEACTIVATE' : 'ACTIVATE'} account ${cell.getRow().getData().name}?`)) {
            makeAjaxRequest(`/accounts/toggleActive/${cell.getRow().getData().account_id}`, 'GET', null, response => {
                fetchAccounts()
            })
        }
    }

    return <Table
        baseRoute='/accounts'
        columns={columns}
        defaultFilterQuery={defaultFilterQuery}
        filters={filters}
        groupByOptions={groupByOptions}
        indexName='account_id'
        initialSort={initialSort}
        pageTitle='Accounts'
        selectable={false}
        tableName='accounts'
    />
}

const matchDispatchToprops = dispatch => {
    return {
    }
}

const mapStateToprops = store => {
    return {
        accounts: store.app.accounts,
        frontEndPermissions: store.user.frontEndPermissions,
        invoice_intervals: store.app.invoiceIntervals,
        parent_accounts: store.app.parentAccounts,
    }
}

export default connect(mapStateToprops, matchDispatchToprops)(Accounts)
