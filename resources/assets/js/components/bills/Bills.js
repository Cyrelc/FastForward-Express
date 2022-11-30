import React from 'react'
import {connect} from 'react-redux'
import {push, routerMiddleware} from 'connected-react-router'
import {DateTime} from 'luxon'

import ReduxTable from '../partials/ReduxTable'
import {fetchBills} from '../../store/reducers/bills'
import * as actionTypes from '../../store/actions'

const baseGroupByOptions = [
    {
        label: 'None',
        value: null
    },
    {
        label: 'Account ID',
        value: 'charge_account_number',
        groupHeader: (value, count, data, group) => {
            return `${data[0].charge_account_number} - ${data[0].charge_account_name} <span style="color: red">\t(${count})</span>`
        }
    },
    {
        label: 'Delivery Address',
        value: 'delivery_address_formatted',
        groupHeader: (value, count, data, group) => {
            return `${data[0].delivery_address_name ? data[0].delivery_address_name : value} <span style="color: red">\t(${count})</span>`}
    },
    {
        label: 'Parent Account',
        value: 'parent_account'
    },
    {
        label: 'Payment Type',
        value: 'payment_type_id'
    },
    {
        label: 'Pickup Address',
        value: 'pickup_address_formatted',
        groupHeader: (value, count, data, group) => {
            return `${data[0].pickup_address_name ? data[0].pickup_address_name : value} <span style="color: red">\t(${count})</span>`
        }
    }
]

const initialSort = [{column:'bill_id', dir: 'desc'}]

function Bills(props) {
    /**
     * There is some fancy array spreading here to combine to create a single array of "columns" based on permissions level
     * This is necessary just to keep columns in a strict order (for example to ensure Amount and Percentage done remain at the end of the list)
     * So essentially it spreads an empty array if you don't have permission, and adds the correct columns if you do!
     */
    const columns = [
            ... props.frontEndPermissions.bills.delete ? [{
                formatter: (cell) => {if(cell.getRow().getData().deletable) return "<button class='btn btn-sm btn-danger'><i class='fas fa-trash'></i></button>"},
                titleFormatter: () => {return "<i class='fas fa-trash'></i>"},
                width:50,
                hozAlign:'center',
                headerHozAlign: 'center',
                cellClick:(e, cell) => deleteBill(cell),
                headerSort: false,
                print: false
            }] : [],
            ... props.frontEndPermissions.bills.create ? [{
                formatter: cell => "<button class='btn btn-sm btn-success'><i class='fas fa-copy'></i></button>",
                titleFormatter: () => "<i class='fas fa-copy'></i>",
                width: 50,
                hozAlign: 'center',
                headerHozAlign: 'center',
                cellClick: (e, cell) => copyBill(cell),
                headerSort: false,
                print: false
            }] : [],
            {
                formatter: cell => {if(cell.getRow().getData()) return '<i class="fa fa-plus-circle"></i>'; else return '<i class="fas fa-minus-circle"'},
                title: 'Charges',
                width: 70,
                hozAlign:'center',
                headerHozAlign:'center',
                cellClick:(e, cell) => {$('.charges_' + cell.getRow().getData().bill_id).toggle(); cell.getRow().getTable().redraw()},
                headerSort: false,
                print: false
            },
            {title: 'Bill ID', field: 'bill_id', ...configureFakeLink('/app/bills/', props.redirect), sorter:'number'},
            {title: 'Waybill #', field: 'bill_number'},
            {title: props.customFieldName, field: 'custom_field_value', formatter: cell => {
                const value = cell.getValue()
                if(value)
                    return value.slice(0, -1)
                return null;
            }},
            {title: 'Delivery Address', field: 'delivery_address_formatted', visible: false},
            {title: 'Delivery Address Name', field: 'delivery_address_name', visible: false},
            ... (props.frontEndPermissions.bills.dispatch || props.authenticatedEmployee) ? [
                {title: 'Delivery Driver', field: 'delivery_employee_name', ...configureFakeLink('/app/employees/', props.redirect, null, 'delivery_driver_id'), visible: false},
                {title: 'Pickup Driver', field: 'pickup_employee_name', ...configureFakeLink('/app/employees/', props.redirect, null, 'pickup_driver_id')},
            ] : [],
            ... props.frontEndPermissions.bills.billing ? [
                {title: 'Interliner', field: 'interliner_name', visible: false},
                {title: 'Payment Type', field: 'payment_type', visible: false},
                {title: 'Repeat Interval', field: 'repeat_interval_name', visible: false}
            ] : [],
            {title: 'Invoice ID', field: 'invoice_id', ...configureFakeLink('/app/invoices/', props.redirect), visible: false},
            {title: 'Pickup Address', field: 'pickup_address_formatted', visible: false},
            {title: 'Pickup Address Name', field: 'pickup_address_name', visible: false},
            {title: 'Scheduled Pickup', field: 'time_pickup_scheduled'},
            {title: 'Scheduled Delivery', field: 'time_delivery_scheduled', visible: false},
            {title: 'Type', field: 'type'},
            {title: 'Price', field: 'price', formatter: 'money', formatterParams: {thousand:',', symbol: '$'}, sorter: 'number', topCalc: 'sum', topCalcParams:{precision: 2}, topCalcFormatter: 'money', topCalcFormatterParams: {thousand: ',', symbol: '$'}},
            {title: 'Percent Complete', field: 'percentage_complete', formatter: 'progress', formatterParams:{min:0, max:100, legend: value => {return value + ' %'}, color: value => {
                if(value <= 33)
                    return 'red'
                else if (value <= 66)
                    return 'gold'
                else if (value == 100)
                    return 'mediumseagreen'
                else
                    return 'mediumturquoise'
            }}},
            {title: 'Paid', field: 'paid', formatter: 'tickCross', hozAlign: 'center', width: 65},
            {title: 'Charges', field: 'charges', visible: false}
    ]

    const filters = [
        ...props.frontEndPermissions.bills.billing ? [
            {
                name: 'Skip Invoicing',
                type: 'BooleanFilter',
                value: 'skip_invoicing'
            },
            {
                isMulti: true,
                name: 'Repeat Interval',
                selections: props.repeatIntervals,
                type: 'SelectFilter',
                value: 'repeat_interval'
            },
        ] : [],
        {
            name: props.customFieldName ?? 'Custom Field',
            type: 'StringFilter',
            value: 'custom_field_value'
        },
        {
            isMulti: true,
            name: 'Account',
            selections: props.accounts,
            type: 'SelectFilter',
            value: 'charge_account_id'
        },
        ...props.frontEndPermissions.bills.create ? [
            {
                default: true,
                name: 'Is Template',
                type: 'BooleanFilter',
                value: 'is_template',
            }
        ] : [],
        {
            isMulti: true,
            name: 'Parent Account',
            selections: props.parentAccounts,
            type: 'SelectFilter',
            value: 'parent_account_id'
        },
        {
            name: 'Price',
            value: 'price',
            type: 'NumberBetweenFilter',
            step: 0.01
        },
        {
            name: 'Paid',
            type: 'BooleanFilter',
            value: 'paid'
        },
        {
            creatable: true,
            isMulti: true,
            name: 'Invoice ID',
            type: 'SelectFilter',
            value: 'invoice_id'
        },
        {
            isMulti: true,
            name: 'Charge Type',
            selections: props.chargeTypes,
            type: 'SelectFilter',
            value: 'charge_type_id',
        },
        {
            name: 'Percent Complete',
            type: 'NumberBetweenFilter',
            value: 'percentage_complete',
            step: 0.01,
            min: 0,
            max: 100,
            defaultUpperBound: 100,
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
        // {
        //     fetchUrl: '/getList/selections/delivery_type',
        //     isMulti: true,
        //     name: 'Delivery Type',
        //     type: 'SelectFilter',
        //     value: 'delivery_type'
        // },
        {
            name: 'Waybill Number',
            type: 'StringFilter',
            value: 'bill_number'
        },
        ...props.frontEndPermissions.bills.edit ? [
            // {
            //     fetchUrl: '/getList/interliners',
            //     name: 'Interliner',
            //     value: 'interliner_id',
            //     type: 'SelectFilter',
            //     isMulti: true
            // },
        ] : [],
        ...props.frontEndPermissions.employees.viewAll ? [
            {
                isMulti: true,
                name: 'Pickup Employee',
                selections: props.drivers,
                type: 'SelectFilter',
                value: 'pickup_driver_id',
            },
        ] : [],
    ]

    const groupBy = ''

    const groupByOptions = baseGroupByOptions.concat(...props.frontEndPermissions.bills.edit ? [
            {label: 'Pickup Employee', value: 'pickup_driver_id', groupHeader: (value, count, data, group) => {return value + ' - ' + data[0].pickup_employee_name + '<span style="color: red">\t(' + count + ')</span>'}}
        ] : [])

        const rowFormatter = row => {
            const rowData = row._row.getData ? row.getData() : undefined
            if(!rowData?.bill_id)
                return

            const holderEl = document.createElement('div')

            holderEl.style.boxSizing = "border-box";
            holderEl.style.padding = "10px 30px 10px 10px";
            holderEl.style.borderTop = "1px solid #333";
            holderEl.style.borderBotom = "1px solid #333";
            holderEl.style.background = "#ddd";
            holderEl.setAttribute('class', 'charges_' + rowData.bill_id)
            holderEl.style.display = 'none'

            const chargeTable = document.createElement('table')
            chargeTable.style.border = '2px solid black'
            chargeTable.style.borderCollapse = 'collapse'
            chargeTable.style.width = '100%'

            const chargeColumns = [
                // {'name': 'Charge ID', 'field': 'charge_id'},
                {'name': 'Type', 'field': 'type'},
                {'name': 'Price', 'field': 'price'},
                ... props.frontEndPermissions.bills.createFull ? [
                    {'name': 'Driver Amount', 'field': 'driver_amount'}
                ] : [],
                {'name': 'Charge Account', 'field': 'charge_account_name'},
                ... props.frontEndPermissions.bills.createFull ? [
                    {'name': 'Charge Employee', 'field': 'charge_employee_name'}
                ] : [],
                {'name': 'Charge Reference Value', 'field': 'charge_reference_value'},
            ]

            if(rowData.charges) {
                const thead = chargeTable.createTHead()
                const theadRow = thead.insertRow(0)
                chargeColumns.forEach((column, index) => {
                    theadRow.insertCell(index).outerHTML = `<th>${column.name}</th>`
                })
                const tbody = chargeTable.createTBody()
                rowData.charges.forEach(charge => {
                    const row = tbody.insertRow(0)
                    chargeColumns.forEach((column, index) => {
                        row.insertCell(index).innerHTML = charge[column.field]
                    })
                })
            }

            holderEl.appendChild(chargeTable)
            row.getElement().appendChild(holderEl)
        }

    const withSelected = []

    const copyBill = cell => {
        const billId = cell.getRow().getData().bill_id
        if(confirm(`Are you certain you wish to make a copy of bill ${billId}?\nThe pickup and delivery date will be changed to today, but all other fields including times, will remain the same`)) {
            makeAjaxRequest(`/bills/copy/${billId}`, 'GET', null, response => {
                toastr.success(`Successfully copied bill ${billId} to new bill ${response.bill_id}`)
                props.fetchTableData()
            })
        }
    }

    const defaultQueryString = () => {
        const billsPermissions = props.frontEndPermissions.bills
        if(billsPermissions.delete || billsPermissions.billing || billsPermissions.dispatch)
            return '?filter[percentage_complete]=,100'
        const billsSinceDate = DateTime.now().minus({months: 4})
        return `?filter[time_pickup_scheduled]=${billsSinceDate.toFormat('yyyy-MM-dd')}`
    }

    const deleteBill = cell => {
        if(!props.frontEndPermissions.bills.delete) {
            console.log('User has no delete bills permission')
            return
        }
        const data = cell.getRow().getData()
        if(!data.deletable) {
            console.log('Bill can not be deleted!')
            return
        }

        if(confirm(`Are you sure you wish to delete bill ${data.bill_id}?\n\nThis action can not be undone`)) {
            makeAjaxRequest(`/bills/${data.bill_id}`, 'DELETE', null, response => {
                props.fetchTableData()
            })
        }
    }

    return (
        <ReduxTable
            columns={props.columns.length ? props.columns : columns}
            dataUrl='/bills'
            defaultQueryString={defaultQueryString()}
            fetchTableData={props.fetchTableData}
            filters={filters}
            groupByOptions={groupByOptions}
            indexName='bill_id'
            initialSort={initialSort}
            pageTitle='Bills'
            reduxQueryString={props.reduxQueryString}
            redirect={props.redirect}
            rowFormatter={rowFormatter}
            setReduxQueryString={props.setQueryString}
            setSortedList={props.setSortedList}
            tableData={props.billsTable}
            tableLoading={props.tableLoading}
            toggleColumnVisibility={props.toggleColumnVisibility}
        />
    )
}

const matchDispatchToProps = dispatch => {
    return {
        fetchTableData: () => dispatch(fetchBills),
        redirect: url=>dispatch(push(url)),
        setQueryString: queryString => dispatch({type: actionTypes.SET_BILLS_QUERY_STRING, payload: queryString}),
        setSortedList: sortedList => dispatch({type: actionTypes.SET_BILLS_SORTED_LIST, payload: sortedList}),
        toggleColumnVisibility: (columns, toggleColumn) => dispatch({type: actionTypes.TOGGLE_BILLS_COLUMN_VISIBILITY, payload: {columns: columns, toggleColumn: toggleColumn}})
    }
}

const mapStateToProps = store => {
    return {
        accounts: store.app.accounts,
        authenticatedEmployee: store.app.authenticatedEmployee,
        billsTable: store.bills.billsTable,
        chargeTypes: store.app.paymentTypes,
        columns: store.bills.columns,
        customFieldName: store.bills.customFieldName,
        drivers: store.app.drivers,
        frontEndPermissions: store.app.frontEndPermissions,
        parentAccounts: store.app.parentAccounts,
        reduxQueryString: store.bills.queryString,
        repeatIntervals: store.app.repeatIntervals,
        tableLoading: store.bills.tableLoading
    }
}

export default connect(mapStateToProps, matchDispatchToProps)(Bills)
