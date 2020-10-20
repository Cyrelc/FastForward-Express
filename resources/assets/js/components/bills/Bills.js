import React from 'react'
import Table from '../partials/Table'

function deleteBill(cell) {
    if(confirm('Are you sure you wish to delete bill ' + cell.getRow().getData().bill_id + '?\nThis action can not be undone')) {
        makeFetchRequest('/bills/delete/' + cell.getRow().getData().bill_id, data => location.reload())
    }
}

const columns = [
    {formatter: (cell) => {if(cell.getRow().getData().editable) return "<button class='btn btn-sm btn-danger'><i class='fas fa-trash'></i></button>"}, width:50, align:'center', cellClick:(e, cell) => deleteBill(cell), headerSort: false, print: false},
    {title: 'Bill ID', field: 'bill_id', formatter: 'link', formatterParams:{labelField:'bill_id', urlPrefix:'/app/bills/edit/'}, sorter:'number'},
    {title: 'Waybill #', field: 'bill_number'},
    {title: 'Account', field: 'charge_account_id', formatter: 'link', formatterParams:{labelField:'charge_account_name', urlPrefix:'/accounts/edit/'}},
    {title: 'Delivery Address', field: 'delivery_address_formatted', visible: false},
    {title: 'Delivery Driver', field: 'delivery_employee_id', formatter: 'link', formatterParams:{labelField:'delivery_employee_name', urlPrefix:'/app/employees/edit/'}, visible: false},
    {title: 'Delivery Manifest ID', field: 'delivery_manifest_id', formatter: 'link', formatterParams: {urlPrefix:'/manifests/view/'}, visible: false},
    {title: 'Editable', field: 'editable', visible: false},
    {title: 'Interliner', field: 'interliner_id', formatter: 'link', formatterParams:{labelField:'interliner_name', urlPrefix:'/interliners/edit/'}, visible: false},
    {title: 'Interliner Cost', field: 'interliner_cost', formatter: 'money', formatterParams:{thousand:',', symbol: '$'}, sorter: 'number', topCalc:'sum', topCalcParams:{precision: 2}, visible: false},
    {title: 'Interliner Cost to Customer', field: 'interliner_cost_to_customer', formatter: 'money', formatterParams:{thousand:',', symbol: '$'}, sorter: 'number', topCalc:'sum', topCalcParams:{precision: 2}, visible: false},
    {title: 'Invoice ID', field: 'invoice_id', formatter: 'link', formatterParams: {urlPrefix: '/invoices/view/'}, visible: false},
    {title: 'Parent Account', field: 'parent_account', visible: false},
    {title: 'Pickup Address', field: 'pickup_address_formatted', visible: false},
    {title: 'Pickup Driver', field: 'pickup_employee_id', formatter: 'link', formatterParams:{labelField:'pickup_employee_name', urlPrefix:'/app/employees/edit/'}},
    {title: 'Pickup Manifest ID', field: 'pickup_manifest_id', formatter: 'link', formatterParams: {urlPrefix: '/manifests/view/'}, visible: false},
    {title: 'Payment Type', field: 'payment_type', visible: false},
    {title: 'Scheduled Pickup', field: 'time_pickup_scheduled'},
    {title: 'Scheduled Delivery', field: 'time_delivery_scheduled', visible: false},
    {title: 'Repeat Interval', field: 'repeat_interval_name', visible: false},
    {title: 'Type', field: 'delivery_type'},
    {title: 'Amount', field: 'amount', formatter: 'money', formatterParams: {thousand:',', symbol: '$'}, sorter: 'number', topCalc: 'sum', topCalcParams:{precision: 2}},
    {title: 'Complete', field: 'percentage_complete', formatter: 'progress', formatterParams:{min:0, max:100, legend: value => {return value + ' %'}, color: value => {
        if(value <= 33)
            return 'red'
        else if (value <= 66)
            return 'gold'
        else if (value == 100)
            return 'mediumseagreen'
        else
            return 'mediumturquoise'
    }}},
]

const filters = [
    {
        fetchUrl: '/getList/accounts',
        isMulti: true,
        name: 'Account',
        type: 'SelectFilter',
        value: 'charge_account_id'
    },
    {
        name: 'Amount',
        value: 'amount',
        type: 'NumberBetweenFilter',
        step: 0.01
    },
    {
        fetchUrl: '/getList/interliners',
        name: 'Interliner',
        value: 'interliner_id',
        type: 'SelectFilter',
        isMulti: true
    },
    {
        name: 'Invoiced',
        type: 'BooleanFilter',
        value: 'invoiced'
    },
    {
        fetchUrl: '/getList/payment_types',
        isMulti: true,
        name: 'Payment Type',
        type: 'SelectFilter',
        value: 'payment_type_id',
    },
    {
        name: 'Percent Complete',
        type: 'NumberBetweenFilter',
        value: 'percentage_complete',
        step: 0.01,
        min: 0,
        max: 100
    },
    {
        name: 'Scheduled Pickup',
        type: 'DateBetweenFilter',
        value: 'time_pickup_scheduled',
    },
    {
        name: 'Scheduled Delivery',
        type: 'DateBetweenFilter',
        value: 'time_delivery_scheduled',
    },
    {
        name: 'Skip Invoicing',
        type: 'BooleanFilter',
        value: 'skip_invoicing'
    },
    {
        fetchUrl: '/getList/drivers',
        isMulti: true,
        name: 'Pickup Employee',
        type: 'SelectFilter',
        value: 'pickup_driver_id',
    },
    {
        fetchUrl: '/getList/selections/delivery_type',
        isMulti: true,
        name: 'Delivery Type',
        type: 'SelectFilter',
        value: 'delivery_type'
    },
    {
        fetchUrl: '/getList/selections/repeat_interval',
        isMulti: true,
        name: 'Repeat Interval',
        type: 'SelectFilter',
        value: 'repeat_interval'
    }
]

const groupByOptions = [
    {label: 'None', value: null},
    {label: 'Account ID', value: 'charge_account_number', groupHeader: (value, count, data, group) => {return data[0].charge_account_number + ' - ' + data[0].charge_account_name + '<span style="color: red">\t(' + count + ')</span>'}},
    {label: 'Delivery Address', value: 'delivery_address_formatted', groupHeader: (value, count, data, group) => {return (data[0].delivery_address_name ? data[0].delivery_address_name : value) + '<span style="color: red">\t(' + count + ')</span>'}},
    {label: 'Parent Account', value: 'parent_account'},
    {label: 'Payment Type', value: 'payment_type_id'},
    {label: 'Pickup Address', value: 'pickup_address_formatted', groupHeader: (value, count, data, group) => {return (data[0].pickup_address_name ? data[0].pickup_address_name : value) + '<span style="color: red">\t(' + count + ')</span>'}},
    {label: 'Pickup Employee', value: 'pickup_driver_id', groupHeader: (value, count, data, group) => {return value + ' - ' + data[0].pickup_employee_name + '<span style="color: red">\t(' + count + ')</span>'}}
]

const initialSort = [{column:'bill_id', dir: 'desc'}]

export default function Bills(props) {
    return (
        <Table
            baseRoute='/bills/buildTable'
            columns={columns}
            filters={filters}
            groupByOptions={groupByOptions}
            initialSort={initialSort}
            location={props.location}
            history={props.history}
            // groupBy={groupBy}
            pageTitle='Bills'
        />
    )
}
