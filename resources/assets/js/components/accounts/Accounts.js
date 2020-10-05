import React from 'react'
import Table from '../partials/Table'

function toggleAccountActive(cell) {
    const active = cell.getRow().getData().active
    if(confirm('Are you sure you wish to ' + (active ? 'DEACTIVATE' : 'ACTIVATE') + ' account ' + cell.getRow().getData().name + '?')) {
        const url = '/accounts/toggleActive/' + cell.getRow().getData().account_id
        makeFetchRequest(url, data => {
            location.reload()
        })
    }
}

const columns = [
    {formatter: (cell) => {
        const active = cell.getRow().getData().active;
        if(active)
            return "<button class='btn btn-sm btn-danger' title='Deactivate'><i class='far fa-times-circle'></i></button>"
        else
            return "<button class='btn btn-sm btn-success'  title='Activate'><i class='far fa-check-circle'></i></button>"
    }, width: 50, align: 'center', cellClick:(e, cell) => toggleAccountActive(cell), headerSort: false, print: false},
    {title: 'Account ID', field: 'account_id', formatter: 'link', formatterParams:{labelField:'account_id', urlPrefix:'/accounts/edit/'}, sorter: 'number'},
    {title: 'Account Number', field: 'account_number'},
    {title: 'Parent Account', field: 'parent_id', formatter: 'link', formatterParams:{labelField: 'parent_name', urlPrefix:'/accounts/edit/'}},
    {title: 'Account Name', field: 'name', formatter: 'link', formatterParams:{url: (cell) => {return '/accounts/edit/' + cell.getRow().getData().account_id}}},
    {title: 'Invoice Interval', field: 'invoice_interval'},
    {title: 'Primary Contact', field: 'primary_contact_name'},
    {title: 'Shipping Address Name', field: 'shipping_address_name', visible: false},
    {title: 'Shipping Address', field: 'shipping_address', visible: false},
    {title: 'Billing Address Name', field: 'billing_address_name'},
    {title: 'Billing Address', field: 'billing_address', visible: false}
]

const filters = [
    {
        name: 'Active',
        value: 'active',
        type: 'BooleanFilter'
    },
    {
        name: 'Has Parent',
        value: 'has_parent',
        type: 'BooleanFilter'
    },
    {
        fetchUrl: '/getList/selections/invoice_interval',
        isMulti: true,
        name: 'Invoice Interval',
        type: 'SelectFilter',
        value: 'invoice_interval'
    },
    {
        fetchUrl: '/getList/parent_accounts',
        isMulti: true,
        name: 'Parent Account',
        type: 'SelectFilter', 
        value: 'parent_id'
    }
]

const groupByOptions = [
    {label: 'Parent Account', value: 'parent_id', groupHeader: (value, count, data, group) => {return value + ' - ' + data[0].parent_name}},
]

const initialSort = [{column: 'account_id', dir: 'asc'}]

export default function Accounts(props) {
    return (
        <Table
            baseRoute='/accounts/buildTable'
            columns={columns}
            filters={filters}
            groupByOptions={groupByOptions}
            initialSort={initialSort}
            pageTitle='Accounts'
        />
    )
}
