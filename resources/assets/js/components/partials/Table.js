import React, {Component, createRef} from 'react'
import { ReactTabulator } from 'react-tabulator'
import {Button, ButtonGroup, Card, Col, Dropdown, Row, InputGroup} from 'react-bootstrap'
import Select from 'react-select'

import TableFilters from '../partials/TableFilters'

export default class Table extends Component {
    constructor() {
        super()
        this.state = {
            activeColumns: [],
            baseRoute: undefined,
            columns: [],
            data: [],
            filters: [],
            groupByOptions: [],
            pageTitle: '',
            tableRef: createRef()
        }
        this.handleActiveFiltersChange = this.handleActiveFiltersChange.bind(this)
        this.handleChange = this.handleChange.bind(this)
        this.refreshTable = this.refreshTable.bind(this)
    }

    // On mount we have to parse the query string in the URL to see if any values were set. 
    // Those filters that are matched, are set to active
    componentDidMount() {
        const search = window.location.search
        if(search.includes('%5B') || search.includes('%5D') || search.includes('%2C'))
            window.location.search = search.replace('%5B', '[').replace('%5D', ']').replace('%2C', ',')
        const filters = this.props.filters.map(filter => {
            if(search.includes('filter[' + filter.value + ']='))
                return {...filter, active: true}
            return {...filter, active: false}
        })
        this.setState({
            baseRoute: this.props.baseRoute,
            columns: this.props.columns,
            filters: filters,
            groupBy: this.props.groupBy ? this.props.groupBy : undefined,
            groupByOptions: this.props.groupByOptions ? this.props.groupByOptions : [],
            pageTitle: this.props.pageTitle
        }, this.refreshTable)
        document.title = this.props.pageTitle + ' - ' + document.title
    }

    handleActiveColumnsChange(columnField) {
        this.state.tableRef.current.table.toggleColumn(columnField)
        this.state.tableRef.current.table.redraw()
        const columns = this.state.columns.map(column => {
            if(column.field === columnField)
                if(column.visible === undefined)
                    return {...column, visible: false}
                else
                    return {...column, visible: !column.visible}
            return column
        })
        this.setState({columns: columns})
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
        if(name === 'activeFilters')
            this.handleActiveFiltersChange(event)
        else
            this.setState({[name]: value})
    }

    handleGroupByChange(event) {
        if(event.value) {
            this.state.tableRef.current.table.setGroupBy(event.value)
            if(event.groupHeader)
                this.state.tableRef.current.table.setGroupHeader(event.groupHeader)
            else
                this.state.tableRef.current.table.setGroupHeader()
        }
        else
            this.state.tableRef.current.table.setGroupBy()
    }

    refreshTable() {
        var query = ''
        const anyActiveFilters = this.state.filters.some(filter => filter.active);
        this.state.filters.forEach(filter => {
            if(filter.active && filter.queryString && filter.queryString.includes("="))
                if(query === '')
                    query += filter.queryString
                else
                    query += '&' + filter.queryString
        })
        // if the query is not blank, and the window location does not already match the query
        if(query && window.location.search != query)
            window.location.search = query
        // else if there are no active filters, and the window location search is not ALREADY blank (without the latter check, the page will reload indefinitely)
        else if(!anyActiveFilters && window.location.search)
            window.location.search = ''
        const route = this.state.baseRoute + window.location.search
        fetch(route)
        .then(response => {return response.json()})
        .then(data => {this.setState({data: data})})
    }

    render() {
        return (
            <Row>
                <Col md={12}>
                    <Card>
                        <Card.Header>
                            <Row>
                                <Col md={1}>
                                    <Card.Title>{this.state.pageTitle}</Card.Title>
                                </Col>
                                <Col md={1}>
                                    <Dropdown>
                                        <Dropdown.Toggle variant='primary' id='column_select'>View Columns</Dropdown.Toggle>
                                        <Dropdown.Menu>
                                            {
                                                this.state.columns.map(column =>
                                                    <Dropdown.Item key={column.field} style={{color: column.visible === false  ? 'red' : 'black'}} onClick={() => this.handleActiveColumnsChange(column.field)}>{column.title}</Dropdown.Item>
                                                )
                                            }
                                        </Dropdown.Menu>
                                    </Dropdown>
                                </Col>
                                <Col md={2}>
                                    <InputGroup>
                                        <InputGroup.Prepend><InputGroup.Text>Group By: </InputGroup.Text></InputGroup.Prepend>
                                        <Select
                                            options={this.state.groupByOptions}
                                            value={this.state.groupBy}
                                            onChange={value => this.handleGroupByChange(value)}
                                            isDisabled={this.state.groupByOptions.length === 0}
                                        />
                                    </InputGroup>
                                </Col>
                                <Col md={6}>
                                    <InputGroup>
                                        <InputGroup.Prepend>
                                            <InputGroup.Text>Select Active Filters: </InputGroup.Text>
                                        </InputGroup.Prepend>
                                        <Select
                                            options={this.state.filters}
                                            value={this.state.filters.filter(filter => filter.active)}
                                            getOptionLabel={option => option.name}
                                            onChange={filters => this.handleChange({target: {name: 'activeFilters', type: 'array', value: filters}})}
                                            isDisabled={this.state.filters.length === 0}
                                            isMulti
                                        />
                                        <InputGroup.Append>
                                            <Button variant='success' onClick={this.refreshTable} disabled={this.state.filters.length === 0}>Apply Filters</Button>
                                        </InputGroup.Append>
                                    </InputGroup>
                                </Col>
                                <Col md={2}>
                                    <ButtonGroup>
                                        <Button variant='primary' onClick={() => this.state.tableRef.current.table.print()} title='Print all items currently visible on the page'>Print <i className='fas fa-print'></i></Button>
                                    </ButtonGroup>
                                </Col>
                            </Row>
                        </Card.Header>
                        {this.state.filters.some(filter => filter.active) && 
                            <Card.Body>
                                <Row>
                                    <Col md={12}>
                                        <TableFilters
                                            filters={this.state.filters}
                                            handleChange={this.handleChange}
                                        />
                                    </Col>
                                </Row>
                            </Card.Body>
                        }
                        <Card.Footer>
                            <ReactTabulator
                                ref={this.state.tableRef}
                                columns={this.props.columns}
                                data={this.state.data}
                                id='dataTable'
                                initialSort={this.props.initialSort}
                                maxHeight='80vh'
                                options={{
                                    groupBy: this.state.groupBy,
                                    layout: 'fitColumns',
                                    pagination:'local',
                                    paginationSize:50,
                                }}
                                printAsHtml={true}
                                printStyled={true}
                            />
                        </Card.Footer>
                    </Card>
                </Col>
            </Row>
        )
    }
}
