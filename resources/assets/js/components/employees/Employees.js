import React, {Fragment, useState} from 'react'
import {useHistory} from 'react-router-dom'

import ChangePasswordModal from '../partials/ChangePasswordModal'
import Table from '../partials/Table'
import {useAPI} from '../../contexts/APIContext'
import {useUser} from '../../contexts/UserContext'

const defaultFilterQuery = '?filter[is_enabled]=true'

/**
 * Table constants
 */
const filters = [
    {
        name: 'Enabled',
        db_field: 'is_enabled',
        type: 'BooleanFilter'
    }
]

const groupByOptions = [

]

const initialSort = [{column: 'employee_id', dir: 'asc'}]

export default function Employees(props) {
    const [changePasswordModalUserId, setChangePasswordModalUserId] = useState(false)
    const [showChangePasswordModal, setShowChangePasswordModal] = useState(false)

    const api = useAPI()
    const history = useHistory()
    const {frontEndPermissions} = useUser()

    const cellContextMenu = (cell) => {
        const data = cell.getData()
        if(!data.employee_id)
            return undefined
        var menuItems = [
            {label: data.is_enabled ? 'Disable' : 'Enable', action: () => toggleEmployeeEnabled(cell)},
            {label: 'Change Password', action: () => toggleChangePasswordModal(cell), disabled: !data.is_enabled},
            ...frontEndPermissions.employees.impersonate ? [{label: 'Impersonate', action: () => impersonateEmployee(cell)}] : []
        ]

        return menuItems
    }

    const columns = [
        ...frontEndPermissions.employees.edit ? [
            {
                clickMenu: (event, cell) => cellContextMenu(cell),
                formatter: () => {return '<button class="btn btn-sm btn-dark"><i class="fas fa-bars"</button>'},
                hozAlign:'center',
                headerSort: false,
                print: false,
                width: 50,
            },
        ] : [],
        {title: 'Employee ID', field: 'employee_id', ...configureFakeLink('/employees/', history.push), sorter: 'number'},
        {title: 'Employee Number', field: 'employee_number', ...configureFakeLink('/employees/', history.push, null, 'employee_id')},
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

    const impersonateEmployee = cell => {
        const userId = cell.getRow().getData().user_id
        api.get(`/users/impersonate/${userId}`).then(response => {
            location.reload()
        })
    }

    const toggleEmployeeEnabled = cell => {
        const {is_enabled, employee_id, employee_name, employee_number} = cell.getRow().getData()
        if(confirm(`Are you sure you wish to ${is_enabled ? 'DEACTIVATE' : 'ACTIVATE'} employee ${employee_number} - ${employee_name}`)) {
            const url = '/employees/toggleEnabled/' + employee_id
            api.get(url).then(response => {
                cell.getRow().update({'is_enabled': !is_enabled})
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
