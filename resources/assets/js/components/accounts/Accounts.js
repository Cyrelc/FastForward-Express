import React, { Component } from 'react'
import { connect } from 'react-redux'
import { push } from 'connected-react-router'
import ReduxTable from '../partials/ReduxTable'

import { fetchAccounts } from '../../store/reducers/accounts'
import * as actionTypes from '../../store/actions'

/**
 * Table constants including definitions
 */

const groupByOptions = [
    {label: 'None', value: null},
    {label: 'Parent Account', value: 'parent_id', groupHeader: (value, count, data, group) => {return value + ' - ' + data[0].parent_name}},
]

const initialSort = [{column: 'account_id', dir: 'asc'}]

class Accounts extends Component {
    constructor() {
        super()
        this.state = {
            columns: [],
            filters: []
        }
        this.toggleAccountActive = this.toggleAccountActive.bind(this)
    }

    toggleAccountActive(cell) {
        if(!this.props.frontEndPermissions.accounts.toggleEnabled)
            return

        const active = cell.getRow().getData().active
        if(confirm('Are you sure you wish to ' + (active ? 'DEACTIVATE' : 'ACTIVATE') + ' account ' + cell.getRow().getData().name + '?')) {
            makeAjaxRequest('/accounts/toggleActive/' + cell.getRow().getData().account_id, 'GET', null, response => {
                fetchAccounts()
            })
        }
    }

    componentDidMount() {
        const adminColumns = this.props.frontEndPermissions.accounts.toggleEnabled ? [
            {formatter: (cell) => {
                if(!this.props.frontEndPermissions.accounts.toggleEnabled)
                    return

                if(cell.getValue() == 1)
                    return "<button class='btn btn-sm btn-danger' title='Deactivate'><i class='far fa-times-circle'></i></button>"
                else
                    return "<button class='btn btn-sm btn-success' title='Activate'><i class='far fa-check-circle'></i></button>"
            }, field: 'active', width: 50, hozAlign: 'center', cellClick:(e, cell) => this.toggleAccountActive(cell), headerSort: false, print: false},
            {title: 'Created On', field: 'created_at', visible: false}
        ] : [];
        const basicColumns = [
            {title: 'Account ID', field: 'account_id', formatter: (cell, formatterParams) => fakeLinkFormatter(cell, formatterParams), formatterParams:{type: 'fakeLink', urlPrefix:'/app/accounts/'}, sorter: 'number'},
            {title: 'Account Number', field: 'account_number'},
            {title: 'Parent Account', field: 'parent_id', formatter: (cell, formatterParams) => fakeLinkFormatter(cell, formatterParams), formatterParams:{type: 'fakeLink', labelField: 'parent_name', urlPrefix:'/app/accounts/'}},
            {title: 'Account Name', field: 'account_id', formatter: (cell, formatterParams) => fakeLinkFormatter(cell, formatterParams), formatterParams:{type: 'fakeLink', labelField: 'name', urlPrefix:'/app/accounts/'}, sorter: 'number'},
            {title: 'Start Date', field: 'start_date', visible: false},
            {title: 'Invoice Interval', field: 'invoice_interval'},
            {title: 'Primary Contact', field: 'primary_contact_name'},
            {title: 'Primary Contact Phone', field: 'primary_contact_phone', headerSort: false},
            {title: 'Shipping Address Name', field: 'shipping_address_name', visible: false},
            {title: 'Shipping Address', field: 'shipping_address', visible: false},
            {title: 'Billing Address Name', field: 'billing_address_name'},
            {title: 'Billing Address', field: 'billing_address', visible: false}
        ]

        this.setState({
            columns: Array.prototype.concat(adminColumns, basicColumns),
            filters: [
                {name: 'Account', value: 'account_id', selections: this.props.accounts, type: 'SelectFilter', isMulti: true},
                {name: 'Active', value: 'active', type: 'BooleanFilter'},
                {name: 'Has Parent', value: 'has_parent', type: 'BooleanFilter'},
                {isMulti: true, name: 'Invoice Interval', selections: this.props.invoice_intervals, type: 'SelectFilter', value: 'invoice_interval'},
                {isMulti: true, name: 'Parent Account', selections: this.props.parent_accounts, type: 'SelectFilter', value: 'parent_id'}
            ]
        })
    }

    render() {
        return <ReduxTable
            columns={this.props.columns.length ? this.props.columns : this.state.columns}
            fetchTableData={this.props.fetchTableData}
            filters={this.state.filters}
            groupByOptions={groupByOptions}
            indexName='account_id'
            initialSort={initialSort}
            pageTitle='Accounts'
            reduxQueryString={this.props.reduxQueryString}
            redirect={this.props.redirect}
            selectable={false}
            setReduxQueryString={this.props.setQueryString}
            setSortedList={this.props.setSortedList}
            tableData={this.props.accountsTable}
            toggleColumnVisibility={this.props.toggleColumnVisibility}
        />
    }
}

const matchDispatchToprops = dispatch => {
    return {
        fetchTableData: () => dispatch(fetchAccounts),
        redirect: url => dispatch(push(url)),
        setQueryString: queryString => dispatch({type: actionTypes.SET_ACCOUNTS_QUERY_STRING, payload: queryString}),
        setSortedList: sortedList => dispatch({type: actionTypes.SET_ACCOUNTS_SORTED_LIST, payload: sortedList}),
        toggleColumnVisibility: (columns, toggleColumn) => dispatch({type: actionTypes.TOGGLE_ACCOUNTS_COLUMN_VISIBILITY, payload: {columns: columns, toggleColumn: toggleColumn}}),
    }
}

const mapStateToprops = store => {
    return {
        accounts: store.app.accounts,
        accountsTable: store.accounts.accountsTable,
        columns: store.accounts.columns,
        frontEndPermissions: store.app.frontEndPermissions,
        invoice_intervals: store.app.invoiceIntervals,
        parent_accounts: store.app.parentAccounts,
        reduxQueryString: store.accounts.queryString,
    }
}

export default connect(mapStateToprops, matchDispatchToprops)(Accounts)
