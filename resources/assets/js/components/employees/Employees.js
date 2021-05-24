import React, {Component} from 'react'
import { connect } from 'react-redux'
import { push } from 'connected-react-router'

import * as actionTypes from '../../store/actions'
import { fetchEmployees } from '../../store/reducers/employees'
import ChangePasswordModal from '../partials/ChangePasswordModal'
import ReduxTable from '../partials/ReduxTable'

/**
 * Table constants
 */
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
            columns: [],
            changePasswordModalUserId: null,
            showChangePasswordModal: false,
        }
        this.toggleChangePasswordModal = this.toggleChangePasswordModal.bind(this)
        this.toggleEmployeeActive = this.toggleEmployeeActive.bind(this)
    }

    cellContextMenu(cell, canEdit = false) {
        const data = cell.getData()
        if(!data.employee_id)
            return undefined
        var menuItems = [
            {label: data.active ? 'Disable' : 'Enable', action: () => this.toggleEmployeeActive(cell)},
            {label: 'Change Password', action: () => this.toggleChangePasswordModal(cell), disabled: !data.active}
        ]

        return menuItems
    }

    cellContextMenuFormatter(cell) {
        if(cell.getData().employee_id)
            return '<button class="btn btn-sm btn-dark"><i class="fas fa-bars"</button>'
    }

    componentDidMount() {
        const adminColumns = this.props.frontEndPermissions.employees.edit ? [
            {formatter: cell => this.cellContextMenuFormatter(cell), width: 50, hozAlign:'center', clickMenu: cell => this.cellContextMenu(cell), headerSort: false, print: false},
        ] : []
        const columns = [
            {title: 'Employee ID', field: 'employee_id', formatter: (cell, formatterParams) => fakeLinkFormatter(cell, formatterParams), formatterParams:{type: 'fakeLink', urlPrefix:'/app/employees/'}, sorter: 'number'},
            {title: 'Employee Number', field: 'employee_number', formatter: (cell, formatterParams) => fakeLinkFormatter(cell, formatterParams), formatterParams:{type: 'fakeLink', urlPrefix: '/app/employees/N'}},
            {title: 'Employee Name', field: 'employee_name'},
            {title: 'Primary Phone', field: 'primary_phone'},
            {title: 'Primary Email', field: 'primary_email'},
        ]

        this.setState({columns: Array.prototype.concat(adminColumns, columns)})
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
        return (
            <div>
                <ReduxTable
                    columns={this.props.columns.length ? this.props.columns : this.state.columns}
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
        frontEndPermissions: store.app.frontEndPermissions,
        reduxQueryString: store.employees.queryString,
        tableData: store.employees.employeesTable
    }
}

export default connect(mapStateToProps, matchDispatchToProps)(Employees)

