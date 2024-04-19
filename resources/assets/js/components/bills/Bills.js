import React, {useState} from 'react'
import {useHistory} from 'react-router-dom'
import {DateTime} from 'luxon'

import Table from '../partials/Table'
import {useAPI} from '../../contexts/APIContext'
import {useLists} from '../../contexts/ListsContext'
import {useUser} from '../../contexts/UserContext'

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
        value: 'parent_account',
        groupHeader: (value, count, data, group) =>{
            return `${data[0]}`
        }
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

export default function Bills(props) {
    //Begin state
    const [customFieldName, setCustomFieldName] = useState('')
    const [triggerReload, setTriggerReload] = useState(false)

    // Begin declarations
    const api = useAPI()
    const history = useHistory()
    const lists = useLists()
    const {authenticatedUser} = useUser()
    const {front_end_permissions: frontEndPermissions} = authenticatedUser
    /**
     * There is some fancy array spreading here to combine to create a single array of "columns" based on permissions level
     * This is necessary just to keep columns in a strict order (for example to ensure "Amount" and "Percent Complete" remain at the end of the list)
     * So essentially it spreads an empty array if you don't have permission, and adds the correct columns if you do!
     */
    const columns = [
            ... frontEndPermissions.bills.delete ? [{
                formatter: (cell) => {if(cell.getRow().getData().deletable) return "<button class='btn btn-sm btn-danger'><i class='fas fa-trash'></i></button>"},
                titleFormatter: () => {return "<i class='fas fa-trash'></i>"},
                width:50,
                hozAlign:'center',
                headerHozAlign: 'center',
                cellClick:(e, cell) => deleteBill(cell),
                headerSort: false,
                print: false
            }] : [],
            ... frontEndPermissions.bills.create ? [{
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
            {title: 'Bill ID', field: 'bill_id', ...configureFakeLink('/bills/', history.push), sorter:'number'},
            {title: 'Waybill #', field: 'bill_number'},
            {title: customFieldName, field: 'custom_field_value'},
            {title: 'Delivery Address', field: 'delivery_address_formatted', visible: false},
            {title: 'Delivery Address Name', field: 'delivery_address_name', visible: false},
            ... (frontEndPermissions.bills.dispatch || authenticatedUser.employee) ? [
                {title: 'Delivery Driver', field: 'delivery_employee_name', ...configureFakeLink('/employees/', history.push, null, 'delivery_driver_id'), visible: false},
                {title: 'Pickup Driver', field: 'pickup_employee_name', ...configureFakeLink('/employees/', history.push, null, 'pickup_driver_id')},
            ] : [],
            ... frontEndPermissions.bills.billing ? [
                {title: 'Interliner', field: 'interliner_name', visible: false},
                {title: 'Payment Type', field: 'payment_type', visible: false},
                {title: 'Repeat Interval', field: 'repeat_interval_name', visible: false}
            ] : [],
            {title: 'Invoice ID', field: 'invoice_id', ...configureFakeLink('/invoices/', history.push), visible: false},
            {title: 'Charge Type', field: 'charge_type_name', visible: false},
            {title: 'Pickup Address', field: 'pickup_address_formatted', visible: false},
            {title: 'Pickup Address Name', field: 'pickup_address_name', visible: false},
            {title: 'Scheduled Pickup', field: 'time_pickup_scheduled'},
            {title: 'Scheduled Delivery', field: 'time_delivery_scheduled', visible: false},
            {title: 'Delivery Type', field: 'type'},
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
            {title: 'Charges', field: 'charges', visible: false}
    ]

    const filters = [
        {
            isMulti: true,
            name: 'Account',
            selections: lists.accounts,
            type: 'SelectFilter',
            db_field: 'charge_account_id'
        },
        {
            isMulti: true,
            name: 'Charge Type',
            selections: lists.paymentTypes,
            type: 'SelectFilter',
            db_field: 'charge_type_id',
        },
        {
            name: props.customFieldName ?? 'Custom Field',
            type: 'StringFilter',
            db_field: 'custom_field_value'
        },
        {
            creatable: true,
            isMulti: true,
            name: 'Invoice ID',
            type: 'SelectFilter',
            db_field: 'invoice_id'
        },
        ...frontEndPermissions.bills.create ? [
            {
                default: true,
                name: 'Is Template',
                type: 'BooleanFilter',
                db_field: 'is_template',
            }
        ] : [],
        {
            name: 'Is Invoiced',
            type: 'BooleanFilter',
            db_field: 'is_invoiced',
            default: false
        },
        ...frontEndPermissions.bills.billing ? [
            {
                isMulti: true,
                name: 'Repeat Interval',
                selections: lists.repeatIntervals,
                type: 'SelectFilter',
                db_field: 'repeat_interval'
            },
            {
                name: 'Skip Invoicing',
                type: 'BooleanFilter',
                db_field: 'skip_invoicing'
            },
        ] : [],
        {
            isMulti: true,
            name: 'Parent Account',
            selections: lists.accounts.filter(account => account.can_be_parent),
            type: 'SelectFilter',
            db_field: 'parent_account_id'
        },
        {
            name: 'Percent Complete',
            type: 'NumberBetweenFilter',
            db_field: 'percentage_complete',
            step: 0.01,
            min: 0,
            max: 100,
            defaultUpperBound: 100,
        },
        ...frontEndPermissions.employees.viewAll ? [
            {
                isMulti: true,
                name: 'Pickup Employee',
                selections: lists.employees.filter(employee => employee.is_driver),
                type: 'SelectFilter',
                db_field: 'pickup_driver_id',
            },
        ] : [],
        {
            name: 'Price',
            db_field: 'price',
            type: 'NumberBetweenFilter',
            step: 0.01
        },
        {
            name: 'Scheduled Delivery',
            type: 'DateBetweenFilter',
            db_field: 'time_delivery_scheduled',
        },
        {
            name: 'Scheduled Pickup',
            type: 'DateBetweenFilter',
            db_field: 'time_pickup_scheduled',
        },
        // {
        //     fetchUrl: '/getList/selections/delivery_type',
        //     isMulti: true,
        //     name: 'Delivery Type',
        //     type: 'SelectFilter',
        //     db_field: 'delivery_type'
        // },
        {
            name: 'Waybill Number',
            type: 'StringFilter',
            db_field: 'bill_number'
        },
        ...frontEndPermissions.bills.edit ? [
            // {
            //     fetchUrl: '/getList/interliners',
            //     name: 'Interliner',
            //     db_field: 'interliner_id',
            //     type: 'SelectFilter',
            //     isMulti: true
            // },
        ] : [],
    ].sort((a, b) => a.name < b.name)

    const groupBy = ''

    const groupByOptions = baseGroupByOptions.concat(...frontEndPermissions.employees.viewAll ? [
            {
                label: 'Pickup Employee',
                value: 'pickup_driver_id',
                groupHeader: (value, count, data, group) => `${value} - ${data[0].pickup_employee_name}<span style="color: red">\t(${count})</span>`
            }
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
            ... frontEndPermissions.bills.createFull ? [
                {'name': 'Driver Amount', 'field': 'driver_amount'}
            ] : [],
            {'name': 'Charge Account', 'field': 'charge_account_name'},
            ... frontEndPermissions.bills.createFull ? [
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

    // End declarations
    // Begin functions

    const copyBill = cell => {
        const billId = cell.getRow().getData().bill_id
        history.push(`/bills/create?copy_from=${billId}`)
    }

    const defaultFilterQuery = () => {
        const billsPermissions = frontEndPermissions.bills
        if(billsPermissions.delete || billsPermissions.billing || billsPermissions.dispatch)
            return '?filter[percentage_complete]=,100'
        const billsSinceDate = DateTime.now().minus({months: 4})
        return `?filter[time_pickup_scheduled]=${billsSinceDate.toFormat('yyyy-MM-dd')}`
    }

    const deleteBill = cell => {
        if(!frontEndPermissions.bills.delete) {
            console.log('User has no delete bills permission')
            return
        }
        const data = cell.getRow().getData()
        if(!data.deletable) {
            console.log('Bill can not be deleted!')
            return
        }

        if(confirm(`Are you sure you wish to delete bill ${data.bill_id}?\n\nThis action can not be undone`)) {
            api.delete(`/bills/${data.bill_id}`, null)
                .then(response => {
                    setTriggerReload(true)
                })
        }
    }

    const transformResponse = response => {
        setCustomFieldName(response.custom_field_name)
        return {'data': response.data, 'queries': response.queries}
    }

    return (
        <Table
            baseRoute='/bills'
            columns={columns}
            defaultFilterQuery={defaultFilterQuery()}
            filters={filters}
            groupBy={groupBy}
            groupByOptions={groupByOptions}
            indexName='bill_id'
            initialSort={initialSort}
            pageTitle='Bills'
            rowFormatter={rowFormatter}
            setTriggerReload={setTriggerReload}
            tableName='bills'
            transformResponse={transformResponse}
            triggerReload={triggerReload}
        />
    )
}
