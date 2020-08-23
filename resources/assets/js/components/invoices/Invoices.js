import React, {Component, createRef} from 'react'
import ReactDom from 'react-dom'
import {ReactTabulator} from 'react-tabulator'
import {Button, ButtonGroup, Card, Col, Row, Table, InputGroup} from 'react-bootstrap'
import Select from 'react-select'

import TableFilters from '../partials/TableFilters'

const filters = [
    {
        name: 'Bill End Date',
        value: 'bill_end_date',
        type: 'DateBetweenFilter',
        active: false,
        queryString: ''
    },
    {
        name: 'Date Run',
        value: 'date',
        type: 'DateBetweenFilter',
        active: false,
        queryString: ''
    },
    {
        name: 'Account',
        value: 'account_id',
        type: 'SelectFilter',
        filterOptions: undefined,
        optionName: 'name',
        optionValue: 'account_id',
        active: false,
        queryString: '',
        isMulti: true
    },
    {
        active: false,
        name: 'Balance Owing',
        value: 'balance_owing',
        type: 'NumberBetweenFilter',
        queryString: '',
        step: 0.01,
    },
    {
        active: false,
        creatable: true,
        filterOptions: undefined,
        isMulti: true,
        name: 'Invoice ID',
        optionName: 'invoice_id',
        optionValue: 'invoice_id',
        queryString: '',
        type: 'SelectFilter',
        value: 'invoice_id'
    }
]

export default class Invoices extends Component {
    constructor() {
        super()
        this.tableRef = React.createRef()
        this.state = {
            invoices: [],
            accounts: [],
            filters: filters,
            groupByAccountId: false,
        }
        this.handleActiveFiltersChange = this.handleActiveFiltersChange.bind(this)
        this.handleChange = this.handleChange.bind(this)
        this.refreshInvoices = this.refreshInvoices.bind(this)
    }

    componentDidMount() {
        document.title = 'Invoices - ' + document.title
        fetch('/accounts/buildTable')
        .then(response => {return response.json()})
        .then(data => {
            const accounts = data.map(account => {return {value: account.account_id, label: account.account_number + ' - ' + account.name}})
            const filters = this.state.filters.map(filter => {
                if(filter.value === 'account_id')
                    return {...filter, filterOptions: accounts}
                return filter
            })
            this.setState({filters: filters})
            this.refreshInvoices()
        })
    }

    deleteInvoice(e, cell) {
        if(confirm('Are you sure you wish to delete invoice ' + cell.getRow().getData().invoice_id + '?\nThis action can not be undone')) {
            fetch('/invoices/delete/' + cell.getRow().getData().invoice_id)
            .then(response => {return response.json()})
            .then(data => {
                if(data.success)
                    location.reload()
                else
                    handleErrorResponse(JSON.stringify(data))
            })
        }
    }

    handleActiveFiltersChange(event) {
        const activeFilters = event.target.value
        const filters = this.state.filters.map(filter => {
            if(activeFilters && activeFilters.some(activeFilter => activeFilter.value == filter.value))
                return {...filter, active: true}
            return {...filter, active: false}
        })
        this.setState({filters: filters})
    }

    handleChange(event) {
        const {name, type, value, checked} = event.target
        switch(name) {
            case 'activeFilters':
                this.handleActiveFiltersChange(event)
                break;
            default:
                this.setState({[name]: value})
        }
    }

    refreshInvoices() {
        var query = '?'
        this.state.filters.forEach(filter => {
            if(query.length === 1 && filter.active && filter.queryString) {
                query += filter.queryString
            }
            else if(filter.active && filter.queryString) {
                query += '&' + filter.queryString
            }
        })
        if(query != '?' && window.location.search != query)
            window.location.search = query
        const route = '/invoices/buildTable/' + query
        fetch(route)
        .then(response => {return response.json()})
        .then(data => {this.setState({invoices: data})})
    }

    render() {
        const columns = [
            {formatter: (cell) => {if(cell.getRow().getData().payment_count == 0) return "<button class='btn btn-sm btn-danger'><i class='fas fa-trash'></i></button>"}, width:50, align:'center', cellClick:(e, cell) => this.deleteInvoice(e, cell)},
            {title: 'Invoice ID', field: 'invoice_id', formatter: 'link', formatterParams:{labelField:'invoice_id', urlPrefix:'/invoices/view/'}, sorter:'number'},
            {title: 'Account', field: 'account_id', formatter: 'link', formatterParams:{labelField:'account_name', urlPrefix:'accounts/edit/'}},
            {title: 'Date Run', field: 'date', sorter:'date'},
            {title: 'Bill Start Date', field: 'bill_start_date', sorter:'date'},
            {title: 'Bill End Date', field: 'bill_end_date', sorter:'date'},
            {title: 'Balance Owing', field: 'balance_owing', formatter: 'money', bottomCalc:"sum", bottomCalcParams:{precision:2}, sorter:'number'},
            {title: 'Bill Cost', field: 'bill_cost', formatter: 'money', sorter:'number'},
            {title: 'Total Cost', field: 'total_cost', formatter: 'money', bottomCalc:"sum", bottomCalcParams:{precision:2}, sorter:'number'},
            {title: 'Bill Count', field: 'bill_count', sorter: 'number'}
        ]
        return (
            <Row>
                <Col md={12}>
                    <Card>
                        <Card.Header>
                            <Row>
                                <Col md={2}>
                                    <Card.Title>Invoices</Card.Title>
                                </Col>
                                <Col md={8}>
                                    <InputGroup>
                                        <InputGroup.Prepend>
                                            <InputGroup.Text>Select Active Filters: </InputGroup.Text>
                                        </InputGroup.Prepend>
                                        <Select
                                            options={this.state.filters}
                                            value={this.state.filters.filter(filter => filter.active)}
                                            getOptionLabel={option => option.name}
                                            onChange={filters => this.handleChange({target: {name: 'activeFilters', type: 'array', value: filters}})}
                                            isMulti
                                        />
                                    </InputGroup>
                                </Col>
                                <Col md={2}>
                                    <ButtonGroup>
                                        <Button variant='success' onClick={this.refreshInvoices}>Apply Filters</Button>
                                        <Button variant='primary' onClick={() => this.tableRef.current.table.print()}>Print <i className='fas fa-print'></i></Button>
                                    </ButtonGroup>
                                </Col>
                            </Row>
                        </Card.Header>
                        <Card.Body>
                            <Row>
                                <Col md={12}>
                                    <TableFilters
                                        accounts={this.state.accounts}
                                        filters={this.state.filters}
                                        handleChange={this.handleChange}
                                    />
                                </Col>
                            </Row>
                        </Card.Body>
                        <Card.Footer>
                            <ReactTabulator
                                ref={this.tableRef}
                                id={'invoicesTable'}
                                columns={columns}
                                data={this.state.invoices}
                                initialSort={[{column:'invoice_id', dir:'desc'}]}
                                options={{
                                    groupBy: this.state.groupByAccountId ? 'account_id' : null,
                                    groupHeader: (value, count, data, group) => {
                                        return data[0].account_name
                                    },
                                    layout: 'fitColumns',
                                    print: this.state.print,
                                }}
                            />
                        </Card.Footer>
                    </Card>
                </Col>
            </Row>
        )
    }
}


ReactDom.render(<Invoices />, document.getElementById('invoices'))
