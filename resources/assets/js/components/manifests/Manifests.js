import React from 'react'
import Table from '../partials/Table'

function deleteManifest(cell) {
    const manifestId = cell.getRow().getData().manifest_id 
    if(confirm('Are you sure you want to delete manifest ' + manifestId + '?\nThis action can not be undone')) {
        fetch('/manifests/delete/' + manifestId)
        .then(response => {return response.json()})
        .then(data => {
            if(data.success)
                location.reload()
            else
                handleErrorResponse(JSON.stringify(data))
        })
    }
}

const columns = [
    {formatter: (cell) => {return "<button class='btn btn-sm btn-danger'><i class='fas fa-trash'></i></button>"}, width: 50, align: 'center', cellClick:(e, cell) => deleteManifest(cell), headerSort: false, print: false},
    {title: 'Manifest ID', field: 'manifest_id', formatter: 'link', formatterParams: {urlPrefix:'/manifests/view/'}, sorter: 'number'},
    {title: 'Employee', field: 'employee_id', formatter: 'link', formatterParams: {labelField: 'employee_name', urlPrefix: '/app/employees/edit'}},
    {title: 'Date Run', field: 'date_run', visible: false},
    {title: 'Bill Start Date', field: 'start_date'},
    {title: 'Bill End Date', field: 'end_date'},
    {title: 'Bill Count', field: 'bill_count'},
    {title: 'Driver Income', field: 'driver_income', formatter: 'money', formatterParams:{ thousand: ',', symbol: '$'}, topCalc: 'sum', topCalcParams:{precision: 2}},
    {title: 'Driver Chargebacks', field: 'driver_chargeback_amount', formatter: 'money', formatterParams:{ thousand: ',', symbol: '$'}, sorter: 'number', topCalc:'sum', topCalcParams:{precision: 2}, visible: false}
]

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

const groupByOptions = [
    {label: 'None', value: null},
    {label: 'Driver', value: 'employee_id', groupHeader: (value, count, data, group) => {return (value + ' - ' + data[0].employee_name)}},
    {label: 'Bill End Date', value: 'end_date'}
]

const initialSort = [{column: 'manifest_id', dir: 'desc'}]

export default function Manifests(props) {
    return (
        <Table
            baseRoute='/manifests/buildTable'
            columns={columns}
            filters={filters}
            groupByOptions={groupByOptions}
            initialSort={initialSort}
            pageTitle='Manifests'
        />
    )
}
