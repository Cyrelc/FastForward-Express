import React, { Component } from 'react'
import { connect } from 'react-redux'
import { push } from 'connected-react-router'

import ReduxTable from '../partials/ReduxTable'
import { fetchBills } from '../../store/reducers/bills'
import * as actionTypes from '../../store/actions'

const baseGroupByOptions = [
    {label: 'None', value: null},
    {label: 'Account ID', value: 'charge_account_number', groupHeader: (value, count, data, group) => {return data[0].charge_account_number + ' - ' + data[0].charge_account_name + '<span style="color: red">\t(' + count + ')</span>'}},
    {label: 'Delivery Address', value: 'delivery_address_formatted', groupHeader: (value, count, data, group) => {return (data[0].delivery_address_name ? data[0].delivery_address_name : value) + '<span style="color: red">\t(' + count + ')</span>'}},
    {label: 'Parent Account', value: 'parent_account'},
    {label: 'Payment Type', value: 'payment_type_id'},
    {label: 'Pickup Address', value: 'pickup_address_formatted', groupHeader: (value, count, data, group) => {return (data[0].pickup_address_name ? data[0].pickup_address_name : value) + '<span style="color: red">\t(' + count + ')</span>'}}
]

const initialSort = [{column:'bill_id', dir: 'desc'}]

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

    if(rowData.charges) {
        const thead = chargeTable.createTHead()
        const theadRow = thead.insertRow(0)
        theadRow.insertCell(0).outerHTML = '<th>Charge ID</th>'
        theadRow.insertCell(1).outerHTML = '<th>Price</th>'
        theadRow.insertCell(2).outerHTML = '<th>Driver Amount</th>'
        theadRow.insertCell(3).outerHTML = '<th>Type</th>'
        theadRow.insertCell(4).outerHTML = '<th>Charge Account</th>'
        theadRow.insertCell(5).outerHTML = '<th>Charge Employee</th>'
        theadRow.insertCell(6).outerHTML = '<th>Charge Reference Value</th>'
        const tbody = chargeTable.createTBody()
        rowData.charges.forEach(charge => {
            const row = tbody.insertRow(0)
            row.insertCell(0).innerHTML = charge.charge_id
            row.insertCell(1).innerHTML = charge.price
            row.insertCell(2).innerHTML = charge.driver_amount
            row.insertCell(3).innerHTML = charge.type
            row.insertCell(4).innerHTML = charge.charge_account_name
            row.insertCell(5).innerHTML = charge.charge_employee_name
            row.insertCell(6).innerHTML = charge.charge_reference_value
        })
    }

    holderEl.appendChild(chargeTable)
    row.getElement().appendChild(holderEl)
}

class Bills extends Component {
    constructor(props) {
        super(props)
        /**
         * There is some fancy array spreading here to combine to create a single array of "columns" based on permissions level
         * This is necessary just to keep columns in a strict order (for example to ensure Amount and Percentage done remain at the end of the list)
         * So essentially it spreads an empty array if you don't have permission, and adds the correct columns if you do!
         */
        this.state = {
            columns: [
                ... this.props.frontEndPermissions.bills.delete ? [{
                    formatter: (cell) => {if(cell.getRow().getData().deletable) return "<button class='btn btn-sm btn-danger'><i class='fas fa-trash'></i></button>"},
                    titleFormatter: () => {return "<i class='fas fa-trash'></i>"},
                    width:50,
                    hozAlign:'center',
                    headerHozAlign: 'center',
                    cellClick:(e, cell) => this.deleteBill(cell),
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
                {title: 'Bill ID', field: 'bill_id', ...configureFakeLink('/app/bills/', this.props.redirect), sorter:'number'},
                {title: 'Waybill #', field: 'bill_number'},
                {title: 'Delivery Address', field: 'delivery_address_formatted', visible: false},
                ... (this.props.frontEndPermissions.bills.dispatch || this.props.authenticatedEmployee) ? [
                    {title: 'Delivery Driver', field: 'delivery_employee_name', ...configureFakeLink('/app/employees/', this.props.redirect, null, 'delivery_driver_id'), visible: false},
                    {title: 'Pickup Driver', field: 'pickup_employee_name', ...configureFakeLink('/app/employees/', this.props.redirect, null, 'pickup_driver_id')},
                ] : [],
                ... this.props.frontEndPermissions.bills.billing ? [
                    {title: 'Interliner', field: 'interliner_name', visible: false},
                    {title: 'Payment Type', field: 'payment_type', visible: false},
                    {title: 'Repeat Interval', field: 'repeat_interval_name', visible: false}
                ] : [],
                {title: 'Invoice ID', field: 'invoice_id', ...configureFakeLink('/app/invoices/', this.props.redirect), visible: false},
                {title: 'Pickup Address', field: 'pickup_address_formatted', visible: false},
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
            ],
            filters: [
                ...this.props.frontEndPermissions.bills.edit ? [
                    {
                        name: 'Skip Invoicing',
                        type: 'BooleanFilter',
                        value: 'skip_invoicing'
                    },
                    // {
                    //     fetchUrl: '/getList/selections/repeat_interval',
                    //     isMulti: true,
                    //     name: 'Repeat Interval',
                    //     type: 'SelectFilter',
                    //     value: 'repeat_interval'
                    // },
                ] : [],
                {
                    isMulti: true,
                    name: 'Account',
                    selections: this.props.accounts,
                    type: 'SelectFilter',
                    value: 'charge_account_id'
                },
                {
                    isMulti: true,
                    name: 'Parent Account',
                    selections: this.props.parentAccounts,
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
                    selections: this.props.chargeTypes,
                    type: 'SelectFilter',
                    value: 'charge_type_id',
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
                ...this.props.frontEndPermissions.bills.edit ? [
                    // {
                    //     fetchUrl: '/getList/interliners',
                    //     name: 'Interliner',
                    //     value: 'interliner_id',
                    //     type: 'SelectFilter',
                    //     isMulti: true
                    // },
                ] : [],
                ...this.props.frontEndPermissions.employees.viewAll ? [
                    {
                        isMulti: true,
                        name: 'Pickup Employee',
                        selections: this.props.drivers,
                        type: 'SelectFilter',
                        value: 'pickup_driver_id',
                    },
                ] : []
            ],
            groupBy: '',
            groupByOptions: baseGroupByOptions.concat(...this.props.frontEndPermissions.bills.edit ? [
                {label: 'Pickup Employee', value: 'pickup_driver_id', groupHeader: (value, count, data, group) => {return value + ' - ' + data[0].pickup_employee_name + '<span style="color: red">\t(' + count + ')</span>'}}
            ] : []),
            withSelected: []
        }
        this.deleteBill = this.deleteBill.bind(this)
        this.handleChange = this.handleChange.bind(this)
    }

    deleteBill(cell) {
        if(!this.props.frontEndPermissions.bills.delete) {
            console.log('User has no delete bills permission')
            return
        }
        const data = cell.getRow().getData()
        if(!data.deletable) {
            console.log('Bill cannot be deleted!')
            return
        }

        if(confirm(`Are you sure you wish to delete bill ${data.bill_id}?\nThis action can not be undone`)) {
            makeAjaxRequest(`/bills/${data.bill_id}`, 'DELETE', null, response => {
                this.props.fetchTableData()
            })
        }
    }

    handleChange(event) {
        const {name, type, value, checked} = event.target
        this.setState({[name]: type === 'checkbox' ? checked : value})
    }

    render() {
        return (
            <ReduxTable
                columns={this.props.columns.length ? this.props.columns : this.state.columns}
                dataUrl='/bills/buildTable'
                fetchTableData={this.props.fetchTableData}
                filters={this.state.filters}
                groupByOptions={this.state.groupByOptions}
                handleChange={this.handleChange}
                indexName='bill_id'
                initialSort={initialSort}
                pageTitle='Bills'
                reduxQueryString={this.props.reduxQueryString}
                redirect={this.props.redirect}
                rowFormatter={rowFormatter}
                setReduxQueryString={this.props.setQueryString}
                setSortedList={this.props.setSortedList}
                tableData={this.props.billsTable}
                tableLoading={this.props.tableLoading}
                toggleColumnVisibility={this.props.toggleColumnVisibility}
            />
        )
    }
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
        drivers: store.app.drivers,
        frontEndPermissions: store.app.frontEndPermissions,
        parentAccounts: store.app.parentAccounts,
        reduxQueryString: store.bills.queryString,
        tableLoading: store.bills.tableLoading
    }
}

export default connect(mapStateToProps, matchDispatchToProps)(Bills)
