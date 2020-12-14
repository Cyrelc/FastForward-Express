import React, {Component} from 'react'
import { connect } from 'react-redux'
import { push } from 'connected-react-router'

import * as actionTypes from '../../store/actions'
import { fetchEmployees } from '../../store/reducers/employees'
import ChangePasswordModal from '../partials/ChangePasswordModal'
import ReduxTable from '../partials/ReduxTable'

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

class Employees extends Component {
    constructor() {
        super()
        this.state = {
            showChangePasswordModal: false,
            changePasswordModalUserId: null
        }
        this.toggleChangePasswordModal = this.toggleChangePasswordModal.bind(this)
        this.toggleEmployeeActive = this.toggleEmployeeActive.bind(this)
    }

    toggleEmployeeActive(cell) {
        const {active, employee_id, employee_name, employee_number} = cell.getRow().getData()
        if(confirm('Are you sure you wish to ' + (active ? 'DEACTIVATE' : 'ACTIVATE') + ' employee' + employee_number + ' - ' + employee_name)) {
            const url = '/employees/toggleActive/' + employee_id
            makeAjaxRequest(url, 'GET', null, response => {
                this.props.fetchTableData()
            })
        }
    }

    toggleChangePasswordModal(cell = null) {
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
            }, width: 50, hozAlign: 'center', cellClick:(e, cell) => this.toggleEmployeeActive(cell), headerSort: false, print: false},
            {formatter: (cell) => {
                if(cell.getRow().getData().active)
                    return "<button class='btn btn-sm btn-warning' title='Reset Password'><i class='fas fa-key'></i></button>"
            }, width: 50, hozAlign: 'center', cellClick:(e, cell) => this.toggleChangePasswordModal(cell), headerSort: false, print: false},
            {title: 'Employee ID', field: 'employee_id', formatter: (cell, formatterParams) => fakeLinkFormatter(cell, formatterParams), formatterParams:{type: 'fakeLink', urlPrefix:'/app/employees/edit/'}, sorter: 'number'},
            {title: 'Employee Number', field: 'employee_number', formatter: (cell, formatterParams) => fakeLinkFormatter(cell, formatterParams), formatterParams:{type: 'fakeLink', urlPrefix: '/app/employees/edit/N'}},
            {title: 'Employee Name', field: 'employee_name'},
            {title: 'Primary Phone', field: 'primary_phone'},
            {title: 'Primary Email', field: 'primary_email'},
        ]

        return (
            <div>
                <ReduxTable
                    columns={this.props.columns.length ? this.props.columns : columns}
                    fetchTableData={this.props.fetchTableData}
                    filters={filters}
                    groupByOptions={groupByOptions}
                    indexName='employee_id'
                    initialSort={initialSort}
                    pageTitle='Employees'
                    reduxQueryString={this.props.reduxQueryString}
                    redirect={this.props.redirect}
                    setReduxQueryString={this.props.setQueryString}
                    setSortedList={this.props.setSortedList}
                    tableData={this.props.tableData}
                    toggleColumnVisibility={this.props.toggleColumnVisibility}
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

const matchDispatchToProps = dispatch => {
    return {
        fetchTableData: () => dispatch(fetchEmployees),
        redirect: url => dispatch(push(url)),
        setQueryString: queryString => dispatch({type: actionTypes.SET_EMPLOYEES_QUERY_STRING, payload: queryString}),
        setSortedList: sortedList => dispatch({type: actionTypes.SET_EMPLOYEES_SORTED_LIST, payload: sortedList}),
        toggleColumnVisibility: (columns, toggleColumn) => dispatch({type: actionTypes.TOGGLE_EMPLOYEES_COLUMN_VISIBILITY, payload: {columns: columns, toggleColumn: toggleColumn}})
    }
}

const mapStateToProps = store => {
    return {
        columns: store.employees.columns,
        tableData: store.employees.employeesTable,
        reduxQueryString: store.employees.reduxQueryString
    }
}

export default connect(mapStateToProps, matchDispatchToProps)(Employees)

