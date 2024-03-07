import React, {Fragment, useState} from 'react'

import ChargebackModal from './ChargebackModal'
import Table from '../partials/Table'

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

export default function Chargebacks(props) {
    const [chargeback, setChargeback] = useState({})
    const [showChargebackModal, setShowChargebackModal] = useState(false)
    const [triggerReload, setTriggerReload] = useState(false)

    const cellContextMenu = [
        {label: '<i class="fas fa-edit"></i> Edit Chargeback', action: (event, cell) => {
            setChargeback(cell.getData())
            setShowChargebackModal(true)
        }},
        {label: '<i class="fas fa-copy"></i> Copy Chargeback', action: (event, cell) => {
            // TODO: remove chargeback_id from cell data for copy action
            setChargeback({...cell.getData(), chargeback_id: null})
            setShowChargebackModal(true)
        }},
        {label: '<i class="fas fa-trash"></i> Delete Chargeback', action: (event, cell) => deleteChargeback(cell)},
    ]

    const deleteChargeback = (event, cell) => {
        const data = cell.getData()
        if(confirm(`Are you sure you wish to delete chargeback ${data.chargeback_id}?\nThis action can not be undone`))
            makeAjaxRequest(`/chargebacks/${cell.getData().chargeback_id}`, 'DELETE', null, response => {
                cell.getRow().delete()
            })
    }

    const filters = [
        {
            name: 'Active',
            type: 'BooleanFilter',
            db_field: 'active',
            default: true
        },
        {
            isMulti: true,
            name: 'Employee',
            selections: props.employees,
            type: 'SelectFilter',
            db_field: 'employee_id'
        },
        {
            name: 'Manifest Start Date',
            type: 'DateBetweenFilter',
            db_field: 'start_date',
        },
        {
            name: 'Manifest End Date',
            type: 'DateBetweenFilter',
            db_field: 'end_date',
        },
    ]

    const toggleModal = () => {
        if(showChargebackModal)
            setChargeback({})
        setShowChargebackModal(!showChargebackModal)
    }

    const columns = [
        {
            clickMenu: cellContextMenu,
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
                baseRoute='/chargebacks'
                columns={columns}
                createObjectFunction={toggleModal}
                defaultQueryString='?filter[active]=true'
                filters={filters}
                groupBy={groupBy}
                groupByOptions={groupByOptions}
                indexName='chargeback_id'
                initalSort={initialSort}
                pageTitle='Chargebacks'
                setTriggerReload={setTriggerReload}
                tableName='chargebacks'
                triggerReload={triggerReload}
            />
            <ChargebackModal
                chargeback={chargeback}
                employees={props.employees}

                setTriggerReload={setTriggerReload}
                show={showChargebackModal}
                toggleModal={toggleModal}
            />
        </Fragment>
    )
}
