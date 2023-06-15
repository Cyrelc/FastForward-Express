import React, {Component, createRef} from 'react'
import {ReactTabulator} from 'react-tabulator'
import {Button, ButtonGroup, Card, Col, Dropdown, Modal, Row, InputGroup} from 'react-bootstrap'
import Select from 'react-select'

import TableFilters from './TableFilters'

export default class ReduxTable extends Component {
    constructor(props) {
        super(props)
        if(!window.location.search) {
            if(this.props.reduxQueryString)
                this.props.redirect(window.location.pathname + this.props.reduxQueryString)
            else if(this.props.defaultQueryString)
                this.props.redirect(`${window.location.pathname}${this.props.defaultQueryString}`)
        }
        this.state = {
            filters: this.parseFilters(),
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
        document.title = this.props.pageTitle + ' - Fast Forward Express'
        if(this.props.groupBy)
            this.handleGroupByChange(this.props.groupBy)
        this.refreshTable()
    }

    componentDidUpdate(prevProps) {
        if(this.props.refreshTable === true) {
            this.setState({loading: true}, this.refreshTable())
            this.props.toggleRefreshTable()
        } else if (prevProps.reduxQueryString != this.props.reduxQueryString)
            this.setState({loading: true}, this.refreshTable())

        if(this.props.tableData != prevProps.tableData && this.state.groupBy)
            this.handleGroupByChange(this.state.groupBy)
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
            this.state.tableRef.current?.table?.setGroupBy(event.value)
            if(event.groupHeader)
                this.state.tableRef.current?.table?.setGroupHeader(event.groupHeader)
            else
                this.state.tableRef.current?.table?.setGroupHeader()
        } else
            this.state.tableRef?.table?.setGroupBy()
        this.handleChange({target: {name: 'groupBy', type: 'object', value: event}})
    }

    parseFilters() {
        const queryStrings = window.location.search ? window.location.search.replace('%5B', '[').replace('%5D', ']').replace('%2C', ',').split('?')[1].split('&') : []
        const filters = this.props.filters.map(filter => {
            const queryString = queryStrings.find(testString => testString.startsWith('filter[' + filter.value + ']='))
            return {...filter, active: queryString ? true : false, queryString: queryString}
        })
        return filters
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
        this.state.tableRef.current?.table?.redraw()
        this.setState({loading: false})
    }

    render() {
        return (
            <Row>
                <Col md={12}>
                    <Modal show={this.props.tableLoading}>
                        <h4>Requesting data, please wait... <i className='fas fa-spinner fa-spin'></i></h4>
                    </Modal>
                    <Card>
                        <Card.Header>
                            <Row>
                                <Col md={1}>
                                    <Card.Title>{this.props.pageTitle}</Card.Title>
                                    <h6>{this.props.tableData.length?.toLocaleString('en-US')} results</h6>
                                </Col>
                                <Col md={2}>
                                    <InputGroup>
                                        <InputGroup.Text>Group By: </InputGroup.Text>
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
                                        <InputGroup.Text>Select Active Filters: </InputGroup.Text>
                                        <Select
                                            options={this.props.filters.filter(filter => filter.type != 'SelectFilter' || filter.creatable || (filter.selections && filter.selections.length > 1))}
                                            value={this.state.filters.filter(filter => filter.active) || ''}
                                            getOptionLabel={option => option.name}
                                            onChange={filters => this.handleActiveFiltersChange(filters)}
                                            isDisabled={this.props.filters.length === 0}
                                            isMulti
                                        />
                                        <Button variant='success' onClick={this.refreshTable}>Apply Filters</Button>
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
                                        <Button variant='primary' onClick={() => this.state.tableRef.current?.table?.print()}>Print Table <i className='fas fa-print'></i></Button>
                                        {this.props.withSelected &&
                                            <Dropdown>
                                                <Dropdown.Toggle variant='dark' id='withSelected'>With Selected</Dropdown.Toggle>
                                                <Dropdown.Menu>
                                                    {this.props.withSelected.map(menuItem =>
                                                        <Dropdown.Item
                                                            key={menuItem.label}
                                                            onClick={() => menuItem.onClick(this.state.tableRef.current?.table?.getSelectedRows(), menuItem.options ?? null)}
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
                            {/* The table loads only when there is valid data - this means that the "intialSort" property is appropriately applied
                            Otherwise it is applied to the empty table */}
                            {this.props.tableData.length > 0 &&
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
                                    options={{
                                        debugInvalidOptions: false,
                                        layout: 'fitColumns',
                                        pagination: 'local',
                                        paginationSize: 20
                                    }}
                                    printAsHtml={true}
                                    printStyled={true}
                                    rowFormatter={this.props.rowFormatter ? this.props.rowFormatter : null}
                                    selectable={this.props.selectable ? this.props.selectable : false}
                                    selectableCheck={() => {return this.props.selectable ? true : false}}
                                />
                            }
                        </Card.Footer>
                    </Card>
                </Col>
            </Row>
        )
    }
}
