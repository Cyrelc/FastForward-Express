import React, {Component, createRef} from 'react'
import { ReactTabulator } from 'react-tabulator'
import {Button, ButtonGroup, Card, Col, Dropdown, Row, InputGroup} from 'react-bootstrap'
import Select from 'react-select'

import TableFilters from './TableFilters'

export default class ReduxTable extends Component {
    constructor() {
        super()
        this.state = {
            filters: [],
            tableRef: createRef()
        }
        this.handleActiveFiltersChange = this.handleActiveFiltersChange.bind(this)
        this.handleChange = this.handleChange.bind(this)
        this.parseFilters = this.parseFilters.bind(this)
        this.refreshTable = this.refreshTable.bind(this)
        this.handleGroupByChange = this.handleGroupByChange.bind(this)
    }
    // On mount we have to parse the query string in the URL to see if any values were set.
    // Those filters that are matched, are set to active
    componentDidMount() {
        // console.log("COMPONENT DID MOUNT")
        if(window.location.search == '' && this.props.reduxQueryString)
            this.props.redirect(window.location.pathname + this.props.reduxQueryString)
        this.setState({
            filters: this.parseFilters()
        }, this.refreshTable)
        document.title = this.props.pageTitle + ' - Fast Forward Express'
    }

    componentDidUpdate(prevProps) {
        console.log("COMPONENT DID UPDATE")
        if(this.props.refreshTable === true) {
            this.refreshTable()
            this.props.toggleRefreshTable()
        } else if (prevProps.reduxQueryString != this.props.reduxQueryString)
            this.refreshTable()
    }

    handleActiveFiltersChange(activeFilters) {
        const filters = this.state.filters.map(filter => {
            if(activeFilters && activeFilters.find(activeFilter => activeFilter.value == filter.value))
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
        this.setState({groupBy: event})
    }

    parseFilters() {
        if(!window.location.search)
            return [];
        const queryStrings = window.location.search.replace('%5B', '[').replace('%5D', ']').replace('%2C', ',').split('?')[1].split('&')
        return this.props.filters.map(filter => {
            const queryString = queryStrings.find(testString => testString.startsWith('filter[' + filter.value + ']='))
            if(queryString)
                return {...filter, active: true, queryString: queryString}
            return {...filter, active: false, queryString: ''}
        })
    }

    refreshTable() {
        var query = ''
        const anyActiveFilters = this.state.filters.some(filter => filter.active);
        this.state.filters.forEach(filter => {
            if(filter.active && filter.queryString && filter.queryString.includes("="))
                if(query === '')
                    query += '?' + filter.queryString
                else
                    query += '&' + filter.queryString
        })
        // if the query is not blank, and the window location does not already match the query
        if(query && window.location.search != query) {
            // console.log("FIRST - Query exists, window location does not match requested query - REDIRECTING")
            this.props.setReduxQueryString(query)
            this.props.redirect(window.location.pathname + query)
        }
        // else if there are no active filters, and the window location search is not ALREADY blank (without the latter check, the page will reload indefinitely)
        else if(!anyActiveFilters && window.location.search) {
            // console.log("SECOND - No active filters - clearing query string and REDIRECTING")
            this.props.setReduxQueryString('')
            this.props.redirect(window.location.pathname)
        } else {
            // console.log("THIRD - ANY OTHER CASE")
            this.props.setReduxQueryString(query)
            this.props.fetchTableData()
        }
    }

    render() {
        return (
            <Row>
                <Col md={12}>
                    <Card>
                        <Card.Header>
                            <Row>
                                <Col md={1}>
                                    <Card.Title>{this.props.pageTitle}</Card.Title>
                                </Col>
                                <Col md={2}>
                                    <InputGroup>
                                        <InputGroup.Prepend><InputGroup.Text>Group By: </InputGroup.Text></InputGroup.Prepend>
                                        <Select
                                            options={this.props.groupByOptions}
                                            value={this.props.groupBy}
                                            onChange={value => this.handleGroupByChange(value)}
                                            isDisabled={this.props.groupByOptions.length === 0}
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
                                            onChange={filters => this.handleActiveFiltersChange(filters)}
                                            isDisabled={this.props.filters.length === 0}
                                            isMulti
                                        />
                                        <InputGroup.Append>
                                            <Button variant='success' onClick={this.refreshTable} disabled={!this.state.filters.some(filter => filter.active)}>Apply Filters</Button>
                                        </InputGroup.Append>
                                    </InputGroup>
                                </Col>
                                <Col md={3}>
                                    <ButtonGroup>
                                        <Dropdown>
                                            <Dropdown.Toggle variant='dark' id='column_select'>View Columns</Dropdown.Toggle>
                                            <Dropdown.Menu>
                                                {this.props.columns.filter(column => column.field != undefined).map(column =>
                                                    <Dropdown.Item
                                                        key={column.field}
                                                        style={{color: column.visible === false  ? 'red' : 'black'}}
                                                        onClick={() => this.props.toggleColumnVisibility(this.props.columns, column)}
                                                    >{column.title}</Dropdown.Item>
                                                )}
                                            </Dropdown.Menu>
                                        </Dropdown>
                                        <Button variant='primary' onClick={() => this.props.tableRef.current.table.print()}>Print Table <i className='fas fa-print'></i></Button>
                                        {this.props.withSelected &&
                                            <Dropdown>
                                                <Dropdown.Toggle variant='dark' id='withSelected'>With Selected</Dropdown.Toggle>
                                                <Dropdown.Menu>
                                                    {this.props.withSelected.map(menuItem =>
                                                        <Dropdown.Item
                                                            key={menuItem.label}
                                                            onClick={() => menuItem.onClick(this.props.tableRef.current.table.getSelectedRows())}
                                                        >{menuItem.label}</Dropdown.Item>
                                                    )}
                                                </Dropdown.Menu>
                                            </Dropdown>
                                        }
                                        {this.props.createObjectFunction &&
                                            <Button variant='success' onClick={this.props.createObjectFunction}><i className='fas fa-square-plus'></i>Create {this.props.pageTitle}</Button>
                                        }
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
                                columns={this.props.columns.map(column => {
                                    if(column.formatterParams && column.formatterParams.type === 'fakeLink' && column.formatterParams.urlPrefix != undefined)
                                        return {...column, cellClick: (event, cell) => {
                                            const value = cell.getValue()
                                            if(value)
                                                this.props.redirect(column.formatterParams.urlPrefix + value)}
                                        }
                                    return column
                                })}
                                data={this.props.tableData}
                                dataSorted={(sorters, rows) => {
                                    if(rows.length > 0) {
                                        const sortedList = rows.map(row => row.getData()[this.props.indexName])
                                        this.props.setSortedList(sortedList)
                                    }
                                }}
                                groupBy={this.props.groupBy}
                                initialSort={this.props.initialSort}
                                maxHeight='80vh'
                                options={{
                                    layout: 'fitColumns',
                                    pagination:'local',
                                    paginationSize:25,
                                }}
                                printAsHtml={true}
                                printStyled={true}
                                selectable={this.props.selectable ? this.props.selectable : false}
                                selectableCheck={() => {return this.props.selectable ? true : false}}
                            />
                        </Card.Footer>
                    </Card>
                </Col>
            </Row>
        )
    }
}
