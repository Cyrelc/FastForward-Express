import React from 'react'
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

const filters = [
    {
        fetchUrl: '/getList/drivers',
        isMulti: true,
        name: 'Driver',
        type: 'SelectFilter',
        value: 'driver_id'
    },
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

const groupBy = 'end_date'

const groupByOptions = [
    {label: 'None', value: null},
    {label: 'Driver', value: 'employee_id', groupHeader: (value, count, data, group) => {return (value + ' - ' + data[0].employee_name)}},
    {label: 'Bill End Date', value: 'end_date'}
]

const initialSort = [{column: 'manifest_id', dir: 'desc'}]

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

function Manifests(props) {
    function cellContextMenu(cell) {
        const data = cell.getData()
        if(!data.manifest_id)
            return undefined
        var menuItems = [
            {label: 'Print Manifest', action: () => printManifests([cell.getRow()])},
            {label: 'Print Without Bill List', action: () => printManifests(cell.getRow(), true)},
            {label: 'Delete Manifest', action: () => deleteManifest(cell)}
        ]

        return menuItems
    }

    function cellContextMenuFormatter(cell) {
        if(cell.getData().manifest_id)
            return '<button class="btn btn-sm btn-dark"><i class="fas fa-bars"</button>'
    }

    function deleteManifest(cell) {
        const manifestId = cell.getRow().getData().manifest_id 
        if(confirm('Are you sure you want to delete manifest ' + manifestId + '?\nThis action can not be undone')) {
            makeAjaxRequest('/manifests/delete/' + manifestId, 'GET', null, response => {
                props.fetchTableData()
            })
        }
    }

    const columns = [
        {formatter: cell => cellContextMenuFormatter(cell), width: 50, hozAlign: 'center', clickMenu: cell => cellContextMenu(cell), headerSort: false, print: false},
        {formatter: 'rowSelection', titleFormatter: 'rowSelection', hozAlign: 'center', headerHozAlign: 'center', headerSort: false, print: false, width: 50},
        {title: 'Manifest ID', field: 'manifest_id', formatter: (cell, formatterParams) => fakeLinkFormatter(cell, formatterParams), formatterParams: {type: 'fakeLink', urlPrefix:'/app/manifests/view/'}, sorter: 'number'},
        {title: 'Employee', field: 'employee_id', formatter: (cell, formatterParams) => fakeLinkFormatter(cell, formatterParams), formatterParams: {type: 'fakeLink', labelField: 'employee_name', urlPrefix: '/app/employees/edit'}},
        {title: 'Date Run', field: 'date_run', visible: false},
        {title: 'Bill Start Date', field: 'start_date'},
        {title: 'Bill End Date', field: 'end_date'},
        {title: 'Bill Count', field: 'bill_count'},
        {title: 'Driver Gross', field: 'driver_gross', formatter: 'money', formatterParams:{ thousand: ',', symbol: '$'}, topCalc: 'sum', topCalcParams:{precision: 2}, topCalcParams:{precision: 2}, topCalcFormatter: 'money', topCalcFormatterParams:{thousand: ',', symbol: '$'}},
        {title: 'Driver Chargebacks', field: 'driver_chargeback_amount', formatter: 'money', formatterParams:{ thousand: ',', symbol: '$'}, sorter: 'number', topCalc:'sum', topCalcParams:{precision: 2}, topCalcFormatter: 'money', topCalcFormatterParams:{thousand: ',', symbol: '$'}},
        {title: 'Driver Income', field: 'driver_income', formatter: 'money', formatterParams: { thousand: ',', symbol: '$'}, sorter: 'number', topCalc: 'sum', topCalcParams:{precision: 2}, topCalcFormatter: 'money', topCalcFormatterParams: {thousand: ',', symbol: '$'}}
    ]

    return (
        <ReduxTable
            columns={props.columns.length ? props.columns : columns}
            fetchTableData={props.fetchTableData}
            filters={filters}
            groupBy={groupBy}
            groupByOptions={groupByOptions}
            indexName='manifest_id'
            initialSort={initialSort}
            pageTitle='Manifests'
            reduxQueryString={props.reduxQueryString}
            redirect={props.redirect}
            selectable='highlight'
            setReduxQueryString={props.setQueryString}
            setSortedList={props.setSortedList}
            tableData={props.manifestTable}
            toggleColumnVisibility={props.toggleColumnVisibility}
            withSelected={withSelected}
        />
    )
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
        manifestTable: store.manifests.manifestTable,
        reduxQueryString: store.manifests.queryString
    }
}

export default connect(mapStateToProps, matchDispatchToProps)(Manifests)
