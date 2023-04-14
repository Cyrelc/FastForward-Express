import React, {useEffect, useState} from 'react'
import { connect } from 'react-redux'

import ChargebackModal from './ChargebackModal'
import Table from '../partials/Table'

const groupBy = 'employee_id'

const groupByOptions = [
    {
        label: 'Employee ID',
        value: 'employee_id',
        groupHeader: (value, count, data, group) => {
            return `${data[0].employee_number} - ${data[0].employee_name} <span style="color: navy">${count}</span>`;
    }}
]

const initialSort = [{column: 'chargeback_id', dir: 'desc'}]

const Chargebacks = props => {
    const [showChargebackModal, setShowChargebackModal] = useState(false)
    const [refreshTable, setRefreshTable] = useState(true)
    const [chargeback, setChargeback] = useState({})

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

    const toggleRefreshTable = () => {
        setRefreshTable(!refreshTable)
    }

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
        {title: 'Continuous?', field: 'continuous', formatter: 'tickCross'}
    ]

    return (
        <div>
            <Table
                baseRoute='/chargebacks'
                columns={columns}
                createObjectFunction={() => {
                    setChargeback({})
                    setShowChargebackModal(true)
                }}
                filters={[
                    {
                        isMulti: true,
                        name: 'Employee',
                        selections: props.employees,
                        type: 'SelectFilter',
                        value: 'employee_id'
                    }
                ]}
                groupBy={groupBy}
                groupByOptions={groupByOptions}
                initalSort={initialSort}
                pageTitle='Chargebacks'
                refreshTable={refreshTable}
                location={props.location}
                history={props.history}
                toggleRefreshTable={toggleRefreshTable}
            />
            <ChargebackModal
                chargeback={chargeback}
                employees={props.employees}
                toggleRefreshTable={toggleRefreshTable}

                show={showChargebackModal}
                toggleModal={toggleModal}
            />
        </div>
    )
}

const matchDispatchToProps = store => {
    return {}
}

const mapStateToProps = store => {
    return {
        employees: store.app.employees
    }
}

export default connect(mapStateToProps, matchDispatchToProps)(Chargebacks)
