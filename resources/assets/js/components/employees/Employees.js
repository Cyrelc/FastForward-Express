import React, {Component} from 'react'

import ChangePasswordModal from '../partials/ChangePasswordModal'
import Table from '../partials/Table'

function toggleEmployeeActive(cell) {
    const {active, employee_id, employee_name, employee_number} = cell.getRow().getData()
    if(confirm('Are you sure you wish to ' + (active ? 'DEACTIVATE' : 'ACTIVATE') + ' employee' + employee_number + ' - ' + employee_name)) {
        const url = '/employees/toggleActive/' + employee_id
        fetch(url)
        .then(response => {return response.json()})
        .then(data => {
            if(data.success)
                location.reload()
            else
                handleErrorResponse(JSON.stringify(data))
        })
    }
}

const filters = [
    {
        name: 'Active',
        value: 'active',
        type: 'BooleanFilter'
    }
]

const groupByOptions = [

]

const initialSort = [{column: 'employee_id', dir: 'asc'}]

export default class Employees extends Component {
    constructor() {
        super()
        this.state = {
            showChangePasswordModal: false,
            changePasswordModalUserId: null
        }
        this.toggleChangePasswordModal = this.toggleChangePasswordModal.bind(this)
    }


    toggleChangePasswordModal(cell = null) {
        console.log('toggleChangePasswordModal')
        if(this.state.showChangePasswordModal)
            this.setState({showChangePasswordModal: false})
        else if(cell)
            this.setState({showChangePasswordModal: true, changePasswordModalUserId: cell.getRow().getData().user_id})
    }

    render() {
        const columns = [
            {formatter: (cell) => {
                const active = cell.getRow().getData().active
                if(active)
                    return "<button class='btn btn-sm btn-danger' title='Deactivate'><i class='far fa-times-circle'></i></button>"
                else
                    return "<button class='btn btn-sm btn-success' title='Activate'><i class='far fa-check-circle'></i></button>"
            }, width: 50, align: 'center', cellClick:(e, cell) => toggleEmployeeActive(cell), headerSort: false, print: false},
            {formatter: (cell) => {
                if(cell.getRow().getData().active)
                    return "<button class='btn btn-sm btn-warning' title='Reset Password'><i class='fas fa-key'></i></button>"
            }, width: 50, align: 'center', cellClick:(e, cell) => this.toggleChangePasswordModal(cell), headerSort: false, print: false},
            {title: 'Employee ID', field: 'employee_id', formatter: 'link', formatterParams:{urlPrefix:'/app/employees/edit/'}, sorter: 'number'},
            {title: 'Employee Number', field: 'employee_number'},
            {title: 'Employee Name', field: 'employee_name', formatter: 'link', formatterParams:{url: (cell) => {return '/app/employees/edit/' + cell.getRow().getData().employee_id}}},
            {title: 'Primary Phone', field: 'primary_phone'},
            {title: 'Primary Email', field: 'primary_email'},
        ]

        return (
            <div>
                <Table
                    baseRoute='/employees/buildTable'
                    columns={columns}
                    filters={filters}
                    groupByOptions={groupByOptions}
                    initialSort={initialSort}
                    pageTitle='Employees'
                />
                <ChangePasswordModal
                    show={this.state.showChangePasswordModal}
                    userId={this.state.changePasswordModalUserId}
                    toggleModal={this.toggleChangePasswordModal}
                />
            </div>
        )
    }
}

