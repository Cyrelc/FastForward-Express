import React, {Fragment, useState} from 'react'
import { connect } from 'react-redux'
import {useHistory} from 'react-router-dom'

import ChangePasswordModal from '../partials/ChangePasswordModal'
import Table from '../partials/Table'

const defaultFilterQuery = '?filter[active]=true'

/**
 * Table constants
 */
const filters = [
    {
        name: 'Active',
        db_field: 'active',
        type: 'BooleanFilter'
    }
]

const groupByOptions = [

]

const initialSort = [{column: 'employee_id', dir: 'asc'}]

function Employees(props) {
    const [changePasswordModalUserId, setChangePasswordModalUserId] = useState(false)
    const [showChangePasswordModal, setShowChangePasswordModal] = useState(false)

    const history = useHistory()

    const columns = [
        ...props.frontEndPermissions.employees.edit ? [
            {
                formatter: cell => cellContextMenuFormatter(cell),
                width: 50,
                hozAlign:'center',
                clickMenu: (cell) => cellContextMenu(cell),
                headerSort: false,
                print: false
            },
        ] : [],
        {title: 'Employee ID', field: 'employee_id', ...configureFakeLink('/app/employees/', history.push), sorter: 'number'},
        {title: 'Employee Number', field: 'employee_number', ...configureFakeLink('/app/employees/', history.push, null, 'employee_id')},
        {title: 'Employee Name', field: 'employee_name'},
        {title: 'Primary Phone', field: 'primary_phone', headerSort: false, formatter: (cell) => {
            const cleaned = ('' + cell.getValue()).replace(/\D/g, '')
            const match = cleaned.match(/^(\d{3})(\d{3})(\d{4})$/)
            if (match)
                return `(${match[1]}) ${match[2]}-${match[3]}`
            return cell.getValue()
        }},
        {title: 'Primary Email', field: 'primary_email'},
    ]

    const cellContextMenu = (cell, canEdit = false) => {
        const data = cell.getData()
        if(!data.employee_id)
            return undefined
        var menuItems = [
            {label: data.active ? 'Disable' : 'Enable', action: () => toggleEmployeeActive(cell)},
            {label: 'Change Password', action: () => toggleChangePasswordModal(cell), disabled: !data.active},
            ...props.frontEndPermissions.employees.impersonate ? [{label: 'Impersonate', action: () => impersonateEmployee(cell)}] : []
        ]

        return menuItems
    }

    const cellContextMenuFormatter = cell => {
        if(cell.getData().employee_id)
            return '<button class="btn btn-sm btn-dark"><i class="fas fa-bars"</button>'
    }

    const impersonateEmployee = cell => {
        const employee_id = cell.getRow().getData().employee_id
        makeAjaxRequest(`/users/impersonate`, 'POST', {'employee_id': employee_id}, response => {
            location.reload()
        })
    }

    const toggleEmployeeActive = cell => {
        const {active, employee_id, employee_name, employee_number} = cell.getRow().getData()
        if(confirm(`Are you sure you wish to ${active ? 'DEACTIVATE' : 'ACTIVATE'} employee ${employee_number} - ${employee_name}`)) {
            const url = '/employees/toggleActive/' + employee_id
            makeAjaxRequest(url, 'GET', null, response => {
                cell.getRow().update({'active': !active})
            })
        }
    }

    const toggleChangePasswordModal = (cell = null) => {
        if(showChangePasswordModal) {
            setShowChangePasswordModal(false)
            setChangePasswordModalUserId(null)
        }
        else if(cell) {
            setShowChangePasswordModal(true)
            setChangePasswordModalUserId(cell.getRow().getData().user_id)
        }
    }

    return (
        <Fragment>
            <Table
                baseRoute='/employees'
                columns={columns}
                defaultFilterQuery={defaultFilterQuery}
                filters={filters}
                groupByOptions={groupByOptions}
                indexName='employee_id'
                initialSort={initialSort}
                pageTitle='Employees'
                tableName='employees'
            />
            {showChangePasswordModal &&
                <ChangePasswordModal
                    show={showChangePasswordModal}
                    userId={changePasswordModalUserId}
                    toggleModal={toggleChangePasswordModal}
                />
            }
        </Fragment>
    )
}

const matchDispatchToProps = dispatch => {
    return {
    }
}

const mapStateToProps = store => {
    return {
        frontEndPermissions: store.user.frontEndPermissions,
    }
}

export default connect(mapStateToProps, matchDispatchToProps)(Employees)

