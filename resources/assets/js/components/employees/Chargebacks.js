import React, {Component} from 'react'
import { connect } from 'react-redux'

import ChargebackModal from './ChargebackModal'
import Table from '../partials/Table'

const groupBy = 'employee_id'

const groupByOptions = [
    {label: 'Employee ID', value: 'employee_id', groupHeader: (value, count, data, group) => {return (data[0].employee_number + ' - ' + data[0].employee_name + '<span style="color: navy">\t(' + count + ')</span>')}}
]

const initialSort = [{column: 'chargeback_id', dir: 'desc'}]

class Chargebacks extends Component {
    constructor() {
        super()
        this.state = {
            showChargebackModal: false,
            refreshTable: false,
            cell: {},
            modalAction: 'create'
        }
        this.cellContextMenu = this.cellContextMenu.bind(this)
        this.createChargeback = this.createChargeback.bind(this)
        this.toggleRefreshTable = this.toggleRefreshTable.bind(this)
        this.toggleModal = this.toggleModal.bind(this)
    }

    cellContextMenu(cell) {
        var menuItems = [
            {label: '<i class="fas fa-edit"></i> Edit Chargeback', action: () => this.setState({cell: cell, modalAction: 'edit', showChargebackModal: true})},
            {label: '<i class="fas fa-copy"></i> Copy Chargeback', action: () => this.setState({cell: cell, modalAction: 'copy', showChargebackModal: true})},
            {label: '<i class="fas fa-trash"></i> Delete Chargeback', action: () => this.deleteChargeback(cell)},
        ]
    
        return menuItems
    }

    createChargeback() {
        this.setState({modalAction: 'create', showChargebackModal: true})
    }

    deleteChargeback(cell) {
        const data = cell.getData()
        if(confirm('Are you sure you wish to delete chargeback ' + data.chargeback_id + '?\nThis action can not be undone'))
            makeAjaxRequest('/chargebacks/delete/' + cell.getData().chargeback_id, 'GET', null, response => {
                this.toggleRefreshTable()
            })
    }

    toggleRefreshTable() {
        this.setState({refreshTable: !this.state.refreshTable})
    }

    toggleModal() {
        this.setState({showChargebackModal: !this.state.showChargebackModal})
    }

    render() {
        const columns = [
            {formatter: cell => {return '<button class="btn btn-sm btn-dark"><i class="fas fa-bars"></i></button>'}, width: 50, hozAlign: 'center', clickMenu: cell => this.cellContextMenu(cell), headerSort: false, print: false},
            {title: 'Chargeback ID', field: 'chargeback_id'},
            {title: 'Employee', field: 'employee_name', visible: false, editor: 'select'},
            {title: 'Employee ID', field: 'employee_id', visible: false},
            {title: 'Chargeback Name', field: 'chargeback_name'},
            {title: 'Start Date', field: 'chargeback_start_date'},
            {title: 'Amount', field: 'amount'},
            {title: 'GL Code', field: 'gl_code'},
            {title: 'Count Remaining', field: 'count_remaining'},
            {title: 'Continuous?', field: 'continuous', formatter: 'tickCross'}
        ]
        return (
            <div>
                <Table
                    baseRoute='/chargebacks/buildTable'
                    columns={columns}
                    createObjectFunction={this.createChargeback}
                    filters={[
                        {
                            isMulti: true,
                            name: 'Employee',
                            selections: this.props.employees,
                            type: 'SelectFilter',
                            value: 'employee_id'
                        }
                    ]}
                    groupBy={groupBy}
                    groupByOptions={groupByOptions}
                    initalSort={initialSort}
                    pageTitle='Chargebacks'
                    refreshTable={this.state.refreshTable}
                    location={this.props.location}
                    history={this.props.history}
                    toggleRefreshTable={this.toggleRefreshTable}
                />
                <ChargebackModal
                    cell={this.state.cell}
                    employees={this.props.employees}
                    modalAction={this.state.modalAction}
                    toggleRefreshTable={this.toggleRefreshTable}

                    show={this.state.showChargebackModal}
                    toggleModal={this.toggleModal}
                />
            </div>
        )
    }
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
