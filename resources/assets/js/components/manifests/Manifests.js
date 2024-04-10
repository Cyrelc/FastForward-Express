import React from 'react'
import {useHistory} from 'react-router-dom'
import {toast} from 'react-toastify'

import {useUser} from '../../contexts/UserContext'

import Table from '../partials/Table'

function printManifests(selectedRows, options = null) {
    if(!selectedRows || selectedRows.length === 0) {
        toast.warn('Please select at least one row to operate on')
        return
    }

    const data = selectedRows.map(selectedRow => {return selectedRow.getData().manifest_id})
    const download = options?.download ? options.download : false;
    const withoutBills = options?.withoutBills ? options.withoutBills : false;

    window.open(`/manifests/${download ? 'download' : 'print'}/${data}${withoutBills ? '?without_bills=true' : ''}`)
}

// const groupBy = 'end_date'

const groupByOptions = [
    {label: 'None', value: null},
    {label: 'Driver', value: 'employee_id', groupHeader: (value, count, data, group) => {return (value + ' - ' + data[0].employee_name)}},
    {label: 'Bill End Date', value: 'end_date'},
    {label: 'Year', value: 'year'}
]

const withSelected = [
    {
        label: 'Download',
        onClick: printManifests,
        options: {
            download: true
        }
    },
    {
        label: 'Print',
        onClick: printManifests
    },
    {
        label: 'Print Without Bill List',
        onClick: printManifests,
        options: {
            withoutBills: true
        }
    }
]

export default function Manifests(props) {
    const history = useHistory()
    const {frontEndPermissions} = useUser()

    const columns= [
        {
            formatter: cell => cellContextMenuFormatter(cell),
            width: 50,
            hozAlign: 'center',
            clickMenu: (cell) => cellContextMenu(cell),
            headerSort: false,
            print: false
        },
        {formatter: 'rowSelection', titleFormatter: 'rowSelection', hozAlign: 'center', headerHozAlign: 'center', headerSort: false, print: false, width: 50},
        {title: 'Manifest ID', field: 'manifest_id', ...configureFakeLink('/manifests/', history.push), sorter: 'number'},
        {title: 'Employee', field: 'employee_id', ...configureFakeLink('/employees/', history.push, 'employee_name')},
        {title: 'Date Run', field: 'date_run', visible: false},
        {title: 'Bill Start Date', field: 'start_date'},
        {title: 'Bill End Date', field: 'end_date'},
        {title: 'Bill Count', field: 'bill_count'},
        {title: 'Driver Gross', field: 'driver_gross', formatter: 'money', formatterParams:{ thousand: ',', symbol: '$'}, topCalc: 'sum', topCalcParams:{precision: 2}, topCalcParams:{precision: 2}, topCalcFormatter: 'money', topCalcFormatterParams:{thousand: ',', symbol: '$'}},
        {title: 'Driver Chargebacks', field: 'driver_chargeback_amount', formatter: 'money', formatterParams:{ thousand: ',', symbol: '$'}, sorter: 'number', topCalc:'sum', topCalcParams:{precision: 2}, topCalcFormatter: 'money', topCalcFormatterParams:{thousand: ',', symbol: '$'}},
        {title: 'Driver Income', field: 'driver_income', formatter: 'money', formatterParams: { thousand: ',', symbol: '$'}, sorter: 'number', topCalc: 'sum', topCalcParams:{precision: 2}, topCalcFormatter: 'money', topCalcFormatterParams: {thousand: ',', symbol: '$'}}
    ]

    const filters= [
        ...props.drivers?.length > 0 ? [{
            selections: props.drivers,
            isMulti: true,
            name: 'Driver',
            type: 'SelectFilter',
            db_field: 'driver_id'
        }] : [],
        {
            name: 'Bill Start Date',
            db_field: 'start_date',
            type: 'DateBetweenFilter'
        },
        {
            name: 'Bill End Date',
            db_field: 'end_date',
            type: 'DateBetweenFilter'
        }
    ]

    const cellContextMenu = cell => {
        const data = cell.getData()
        if(!data.manifest_id)
            return undefined
        var menuItems = [
            {label: 'Download Manifest', action: () => printManifests([cell.getRow()], {download: true})},
            {label: 'Print Manifest', action: () => printManifests([cell.getRow()])},
            {label: 'Print Without Bill List', action: () => printManifests([cell.getRow()], {withoutBills: true})}
        ]
        if(frontEndPermissions.manifests.delete)
            menuItems = menuItems.concat([{label: 'Delete Manifest', action: () => deleteManifest(cell)}])

        return menuItems
    }

    const cellContextMenuFormatter = cell => {
        if(cell.getData().manifest_id)
            return '<button class="btn btn-sm btn-dark"><i class="fas fa-bars fa-sm"></i></button>'
    }

    const deleteManifest = cell => {
        const manifestId = cell.getRow().getData().manifest_id 
        if(confirm(`Are you sure you want to delete manifest ${manifestId}?\nThis action can not be undone`)) {
            makeAjaxRequest(`/manifests/${manifestId}`, 'DELETE', null, response => {
                cell.getRow().delete()
            })
        }
    }

    return <Table
        baseRoute='/manifests'
        columns={columns}
        filters={filters}
        // groupBy={groupBy}
        groupByOptions={groupByOptions}
        indexName='manifest_id'
        initialSort={[{column:'manifest_id', dir:'desc'}]}
        pageTitle='Manifests'
        selectable='highlight'
        tableName='manifests'
        withSelected={withSelected}
    />
}
