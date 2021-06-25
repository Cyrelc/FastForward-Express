import React, { Component } from 'react'
import { connect } from 'react-redux'
import { push } from 'connected-react-router'

import ReduxTable from '../partials/ReduxTable'
import { fetchManifests } from '../../store/reducers/manifests'
import * as actionTypes from '../../store/actions'

function printManifests(selectedRows = null, withoutBills = false) {
    if(!selectedRows || selectedRows.length === 0) {
        toastr.warning('Please select at least one row to operate on')
        return
    }
    const data = selectedRows.map(selectedRow => {return selectedRow.getData().manifest_id})
    if(selectedRows.length === 1)
        window.open('/manifests/print/' + data[0] + (withoutBills ? '?without_bills' : ''))
    else
        window.open('/manifests/printMass/' + data + (withoutBills ? '?without_bills' : ''))
}

function printManifestsWithoutBills(selectedRows = null) {
    printManifests(selectedRows, true)
}

// const groupBy = 'end_date'

const groupByOptions = [
    {label: 'None', value: null},
    {label: 'Driver', value: 'employee_id', groupHeader: (value, count, data, group) => {return (value + ' - ' + data[0].employee_name)}},
    {label: 'Bill End Date', value: 'end_date'}
]

const withSelected = [
    {
        label: 'Print',
        onClick: printManifests
    },
    {
        label: 'Print Without Bill List',
        onClick: printManifestsWithoutBills
    }
]

class Manifests extends Component {
    constructor() {
        super()
        this.state = {
            columns: [],
            filters: []
        }
        this.deleteManifest = this.deleteManifest.bind(this)
    }

    componentDidMount() {
        const columns = [
            {formatter: cell => this.cellContextMenuFormatter(cell), width: 50, hozAlign: 'center', clickMenu: cell => this.cellContextMenu(cell), headerSort: false, print: false},
            {formatter: 'rowSelection', titleFormatter: 'rowSelection', hozAlign: 'center', headerHozAlign: 'center', headerSort: false, print: false, width: 50},
            {title: 'Manifest ID', field: 'manifest_id', formatter: (cell, formatterParams) => fakeLinkFormatter(cell, formatterParams), formatterParams: {type: 'fakeLink', urlPrefix:'/app/manifests/'}, sorter: 'number'},
            {title: 'Employee', field: 'employee_id', formatter: (cell, formatterParams) => fakeLinkFormatter(cell, formatterParams), formatterParams: {type: 'fakeLink', labelField: 'employee_name', urlPrefix: '/app/employees/'}},
            {title: 'Date Run', field: 'date_run', visible: false},
            {title: 'Bill Start Date', field: 'start_date'},
            {title: 'Bill End Date', field: 'end_date'},
            {title: 'Bill Count', field: 'bill_count'},
            {title: 'Driver Gross', field: 'driver_gross', formatter: 'money', formatterParams:{ thousand: ',', symbol: '$'}, topCalc: 'sum', topCalcParams:{precision: 2}, topCalcParams:{precision: 2}, topCalcFormatter: 'money', topCalcFormatterParams:{thousand: ',', symbol: '$'}},
            {title: 'Driver Chargebacks', field: 'driver_chargeback_amount', formatter: 'money', formatterParams:{ thousand: ',', symbol: '$'}, sorter: 'number', topCalc:'sum', topCalcParams:{precision: 2}, topCalcFormatter: 'money', topCalcFormatterParams:{thousand: ',', symbol: '$'}},
            {title: 'Driver Income', field: 'driver_income', formatter: 'money', formatterParams: { thousand: ',', symbol: '$'}, sorter: 'number', topCalc: 'sum', topCalcParams:{precision: 2}, topCalcFormatter: 'money', topCalcFormatterParams: {thousand: ',', symbol: '$'}}
        ]

        const filters = [
            ... (this.props.drivers && this.props.drivers.length > 1) ? [{
                selections: this.props.drivers,
                isMulti: true,
                name: 'Driver',
                type: 'SelectFilter',
                value: 'driver_id'
            }] : [],
            {
                name: 'Bill Start Date',
                value: 'start_date',
                type: 'DateBetweenFilter'
            },
            {
                name: 'Bill End Date',
                value: 'end_date',
                type: 'DateBetweenFilter'
            }
        ]

        this.setState({columns: columns, filters: filters})
    }

    cellContextMenu(cell) {
        const data = cell.getData()
        if(!data.manifest_id)
            return undefined
        var menuItems = [
            {label: 'Print Manifest', action: () => printManifests([cell.getRow()])},
            {label: 'Print Without Bill List', action: () => printManifests([cell.getRow()], true)}
        ]
        if(this.props.frontEndPermissions.manifests.delete)
            menuItems = menuItems.concat([{label: 'Delete Manifest', action: () => this.deleteManifest(cell)}])

        return menuItems
    }

    cellContextMenuFormatter(cell) {
        if(cell.getData().manifest_id)
            return '<button class="btn btn-sm btn-dark"><i class="fas fa-bars"</button>'
    }

    deleteManifest(cell) {
        const manifestId = cell.getRow().getData().manifest_id 
        if(confirm('Are you sure you want to delete manifest ' + manifestId + '?\nThis action can not be undone')) {
            makeAjaxRequest('/manifests/delete/' + manifestId, 'GET', null, response => {
                this.props.fetchTableData()
            })
        }
    }

    render() {
        return <ReduxTable
            columns={this.props.columns.length ? this.props.columns : this.state.columns}
            fetchTableData={this.props.fetchTableData}
            filters={this.state.filters}
            // groupBy={groupBy}
            groupByOptions={groupByOptions}
            indexName='manifest_id'
            initialSort={[{column:'manifest_id', dir:'desc'}]}
            pageTitle='Manifests'
            reduxQueryString={this.props.reduxQueryString}
            redirect={this.props.redirect}
            selectable='highlight'
            setReduxQueryString={this.props.setQueryString}
            setSortedList={this.props.setSortedList}
            tableData={this.props.manifestTable}
            toggleColumnVisibility={this.props.toggleColumnVisibility}
            withSelected={withSelected}
        />
    }
}

const matchDispatchToProps = dispatch => {
    return {
        fetchTableData: () => dispatch(fetchManifests),
        redirect: url => dispatch(push(url)),
        setQueryString: queryString => dispatch({type: actionTypes.SET_MANIFESTS_QUERY_STRING, payload: queryString}),
        setSortedList: sortedList => dispatch({type: actionTypes.SET_MANIFESTS_SORTED_LIST, payload: sortedList}),
        toggleColumnVisibility: (columns, toggleColumn) => dispatch({type: actionTypes.TOGGLE_MANIFESTS_COLUMN_VISIBILITY, payload: {columns: columns, toggleColumn: toggleColumn}})
    }
}

const mapStateToProps = store => {
    return {
        columns: store.manifests.columns,
        drivers: store.app.drivers,
        frontEndPermissions: store.app.frontEndPermissions,
        manifestTable: store.manifests.manifestTable,
        reduxQueryString: store.manifests.queryString
    }
}

export default connect(mapStateToProps, matchDispatchToProps)(Manifests)
