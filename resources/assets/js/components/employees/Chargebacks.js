import React, {Fragment, useEffect, useState} from 'react'
import { connect } from 'react-redux'
import {push} from 'connected-react-router'

import ChargebackModal from './ChargebackModal'
// import Table from '../partials/Table'
import Table from '../partials/ReduxTable'
import { SET_BILLS_TABLE_LOADING } from '../../store/actions'

const groupBy = {
    label: 'Employee ID',
    value: 'employee_id',
    groupHeader: (value, count, data, group) => {
        return `${data[0].employee_number} - ${data[0].employee_name} <span style="color: navy">${count}</span>`;
    }
}
const groupByOptions = [
    {
        label: 'Employee ID',
        value: 'employee_id',
        groupHeader: (value, count, data, group) => {
            return `${data[0].employee_number} - ${data[0].employee_name} <span style="color: navy">${count}</span>`;
    }}
]

const initialSort = [{column: 'chargeback_id', dir: 'desc'}]

function Chargebacks(props) {
    const [showChargebackModal, setShowChargebackModal] = useState(false)
    const [refreshTable, setRefreshTable] = useState(true)
    const [chargeback, setChargeback] = useState({})
    const [chargebacks, setChargebacks] = useState([])
    const [queryString, setQueryString] = useState('')
    // We don't actually use this sorted list, but it is required for the <Table> component
    const [sortedList, setSortedList] = useState([])
    const [tableLoading, setTableLoading] = useState(false)

    const cellContextMenu = cell => {
        const stuff = [
            {label: '<i class="fas fa-edit"></i> Edit Chargeback', action: () => {
                setChargeback(cell.getData())
                setShowChargebackModal(true)
            }},
            {label: '<i class="fas fa-copy"></i> Copy Chargeback', action: () => {
                // TODO: remove chargeback_id from cell data for copy action
                setChargeback({...cell.getData(), chargeback_id: null})
                setShowChargebackModal(true)
            }},
            {label: '<i class="fas fa-trash"></i> Delete Chargeback', action: () => deleteChargeback(cell)},
        ]
        return stuff;
    }

    const deleteChargeback = cell => {
        const data = cell.getData()
        if(confirm(`Are you sure you wish to delete chargeback ${data.chargeback_id}?\nThis action can not be undone`))
            makeAjaxRequest(`/chargebacks/${cell.getData().chargeback_id}`, 'DELETE', null, response => {
                setRefreshTable(true)
            })
    }

    const fetchTableData = () => {
        setTableLoading(true)
        makeAjaxRequest(`/chargebacks${queryString}`, 'GET', null, response => {
            response = JSON.parse(response)
            setChargebacks(response)
            setTableLoading(false)
        }, error => {
            setTableLoading(false)
            handleErrorResponse(error)
        })
    }

    const filters = [
        {
            name: 'Active',
            type: 'BooleanFilter',
            value: 'active',
            default: true
        },
        {
            isMulti: true,
            name: 'Employee',
            selections: props.employees,
            type: 'SelectFilter',
            value: 'employee_id'
        },
        {
            name: 'Manifest Start Date',
            type: 'DateBetweenFilter',
            value: 'start_date',
        },
        {
            name: 'Manifest End Date',
            type: 'DateBetweenFilter',
            value: 'end_date',
        },
    ]

    const toggleModal = () => {
        setShowChargebackModal(!showChargebackModal)
    }

    const columns = [
        {
            clickMenu: cell => cellContextMenu(cell),
            formatter: cell => {return '<button class="btn btn-sm btn-dark"><i class="fas fa-bars"></i></button>'},
            headerSort: false,
            hozAlign: 'center',
            print: false,
            width: 50,
        },
        {title: 'Chargeback ID', field: 'chargeback_id'},
        {title: 'Employee', field: 'employee_name', visible: false, editor: 'select'},
        {title: 'Employee ID', field: 'employee_id', visible: false},
        {title: 'Chargeback Name', field: 'chargeback_name'},
        {title: 'Start Date', field: 'chargeback_start_date'},
        {title: 'Amount', field: 'amount', formatter: 'money', formatterParams: {symbol: '$'}},
        {title: 'GL Code', field: 'gl_code'},
        {title: 'Count Remaining', field: 'count_remaining'},
        {title: 'Continuous?', field: 'continuous', formatter: 'tickCross'},
        {title: 'Manifest ID', field: 'manifest_id'},
        {title: 'Manifest Dates', field: 'manifest_dates'}
    ]

    return (
        <Fragment>
            <Table
                columns={columns}
                dataUrl='/chargebacks'
                defaultQueryString='?filter[active]=true'
                fetchTableData={fetchTableData}
                filters={filters}
                groupBy={groupBy}
                groupByOptions={groupByOptions}
                indexName='chargeback_id'
                initalSort={initialSort}
                pageTitle='Chargebacks'
                reduxQueryString={queryString}
                redirect={props.redirect}
                setReduxQueryString={setQueryString}
                setSortedList={setSortedList}
                tableData={chargebacks}
                tableLoading={tableLoading}
            />
            <ChargebackModal
                chargeback={chargeback}
                employees={props.employees}
                fetchTableData={fetchTableData}

                show={showChargebackModal}
                toggleModal={toggleModal}
            />
        </Fragment>
    )
}

const matchDispatchToProps = dispatch => {
    return {
        redirect: url=>dispatch(push(url)),
    }
}

const mapStateToProps = store => {
    return {
        employees: store.app.employees
    }
}

export default connect(mapStateToProps, matchDispatchToProps)(Chargebacks)
