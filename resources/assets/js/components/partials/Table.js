import React, {Fragment, useEffect, useRef, useState} from 'react'
import {Button, ButtonGroup, Card, Col, Container, FormControl, InputGroup, Row, Modal} from 'react-bootstrap'
import Select from 'react-select'
import {useHistory, useLocation} from 'react-router-dom'
import queryString from 'query-string'
import {TabulatorFull as Tabulator} from 'tabulator-tables'
import Dropdown from 'react-multilevel-dropdown'

import TableFilters from './TableFilters'

const localFilterQueryGet = (pageTitle) => {
    const value = localStorage.getItem(`${pageTitle}.queryString`)
    return value ? '?' + value : null
}

const localFilterQuerySet = (pageTitle, filterQuery) => {
    localStorage.setItem(`${pageTitle}.queryString`, filterQuery)
}

export default function Table(props) {
    const history = useHistory()
    const location = useLocation()
    const tableRef = useRef(null)

    const [columns, setColumns] = useState([])
    const [filters, setFilters] = useState([])
    const [groupBy, setGroupBy] = useState(props.groupBy ?? null)
    const [isLoading, setIsLoading] = useState(true)
    const [queries, setQueries] = useState([])
    const [queryName, setQueryName] = useState('')
    const [table, setTable] = useState(null)
    const [tableData, setTableData] = useState([])
    const [tableBuilt, setTableBuilt] = useState(false)

    // Initial setup, set document title, get initial searchQuery
    useEffect(() => {
        document.title = `${props.pageTitle} - Fast Forward Express`
    }, [])

    useEffect(() => {
        const initialQuery = location.search || localFilterQueryGet(props.tableName) || props.defaultFilterQuery || ''
        fetchTableData(initialQuery)
        if(location.search != initialQuery) {
            history.replace(location.pathname + initialQuery)
        }

        const queryStrings = queryString.parse(initialQuery)
        const initialFilters = props.filters.map(filter => {
            const value = queryStrings[`filter[${filter.db_field}]`]
            return {
                ...filter,
                active: value != undefined,
                value: value,
                // value: value === false ? 'false' : value ?? null
            }
        })
        setFilters(initialFilters)
    }, [location.search])

    // Initialize datatable
    useEffect(() => {
        let localStorageColumnVisibility = localStorage.getItem(`${props.tableName}.columnVisibility`)
        let columnsWithVisibilityParsed = null
        if(localStorageColumnVisibility) {
            localStorageColumnVisibility = JSON.parse(localStorageColumnVisibility)
            columnsWithVisibilityParsed = columns.map(column => {
                const visible = localStorageColumnVisibility.find(columnVisibility => columnVisibility.field == column.field && column.title == column.title)
                if(visible)
                    return {...column, visible: visible.visible}
                return column
            })
        }
        if(columnsWithVisibilityParsed)
            setColumns(columnsWithVisibilityParsed)

        if(tableRef.current && !table && !isLoading) {
            const newTabulator = new Tabulator(tableRef.current, {
                columns: columnsWithVisibilityParsed ?? columns,
                data: tableData,
                groupBy: groupBy?.value ?? null,
                groupHeader: groupBy?.groupHeader ?? null,
                layout: 'fitColumns',
                pagination: 'local',
                paginationSize: 20,
                printAsHtml: true,
                printStyled: true,
                initialSort: props.initialSort,
                rowFormatter: props.rowFormatter ?? null,
                selectable: props.selectable ?? false,
                selectableCheck: () => {return props.selectable ? true : false}
            })

            newTabulator.on('dataSorted', (sorters, rows) => {
                if(rows.length > 0) {
                    setSortedList()
                }
            })

            newTabulator.on('tableBuilt', () => setTableBuilt(true))
            newTabulator.on('dataSorted', (column, data) => setSortedList(column, data, 'dataSorted'))

            setTable(newTabulator)
        }
    }, [tableRef.current, isLoading])

    // Handle table data changes
    useEffect(() => {
        if(table)
            table.setData(tableData)
    }, [tableData])

    useEffect(() => {
        const updatedColumns = props.columns.map(column => {
            const oldColumn = columns.find(oldColumn => oldColumn.field == column.field)
            if(oldColumn)
                return {...column, visible: oldColumn.visible}
            return column
        })
        setColumns(updatedColumns)
    }, [props.columns])

    // Handle column definition changes
    useEffect(() => {
        if(tableBuilt)
            table.setColumns(columns)
    }, [columns])

    // Handle groupBy changes
    useEffect(() => {
        if(groupBy?.value && table) {
            table?.setGroupBy(groupBy.value)
            if(groupBy.groupHeader)
                table?.setGroupHeader(groupBy.groupHeader)
            else
                table?.setGroupHeader()
        } else
            table?.setGroupBy()
    }, [groupBy])

    // Handle trigger call from parent to refresh table data
    useEffect(() => {
        if(props.triggerReload) {
            fetchTableData()
            props.setTriggerReload(false)
        }
    }, [props.triggerReload])

    const deleteQuery = query => {
        if(confirm(`Are you sure you wish to delete query "${query.name}?\nThis action can not be undone`))
            makeAjaxRequest(`/queries/${query.id}`, 'DELETE', null, response => {
                setQueries(response)
            })
    }

    const fetchTableData = (query = null) => {
        setIsLoading(true)
        if(query == null) {
            const activeFilters = {}
            filters.forEach(filter => {
                if(filter.active && filter.value)
                    activeFilters[`filter[${filter.db_field}]`] = filter.value
            })

            query = queryString.stringify(activeFilters)
            localFilterQuerySet(props.tableName, query)
            history.push({search: query})
        }

        makeAjaxRequest(`${props.baseRoute}${query[0] == '?' ? '' : '?'}${query}`, 'GET', null, response => {
            if(props.transformResponse)
                response = props.transformResponse(response)
            setTableData(response.data)
            setQueries(response.queries ?? [])
            setIsLoading(false)
        }, () => {setIsLoading(false)})
    }

    const handleActiveFiltersChange = activeFilters => {
        const newFilters = filters.map(filter => {
            if(activeFilters && activeFilters.find(activeFilter => activeFilter.db_field == filter.db_field))
                return {...filter, active: true}
            return {...filter, active: false}
        })
        setFilters(newFilters)
    }

    const saveCurrentQuery = () => {
        if(!queryName) {
            toastr.warn('Please enter a name for the query!')
            return
        }
        const data = {
            name: queryName,
            query_string: location.search,
            table: props.tableName.toLowerCase()
        }
        makeAjaxRequest('/queries', 'POST', data, response => {
            setQueries(response)
            setQueryName('')
        })
    }

    // TODO: move to table "On data change" event
    const setSortedList = (column, data, callingFunction) => {
        if(data && props.indexName)
            localStorage.setItem(`${props.tableName}.sortedList`, data.map(row => row.getData()[props.indexName]))
    } 

    /**
     * Note: We check both field and title here, because for navigation reasons, several columns may use the same "field" in the backend, and then
     * display different data due to the formatter. Checking both ensures that we only enable/disable one field instead of multiple
     */
    const toggleColumnVisibility = (toggleColumn) => {
        const newColumns = columns.map(column => {
            if(column.field == toggleColumn.field && column.title == toggleColumn.title) {
                if(column.visible === undefined)
                    return {...column, visible: false}
                else
                    return {...column, visible: !column.visible}
            }
            return column
        })
        const columnVisibility = newColumns.filter(column => !!column.field).map(column => {
            return {field: column.field, title: column.title, visible: column.visible != false}
        })
        localStorage.setItem(`${props.tableName}.columnVisibility`, JSON.stringify(columnVisibility))
        setColumns(newColumns)
    }

    const writeQueryToClipboard = async (queryString) => {
        await navigator.clipboard.writeText(location.pathname + queryString)
        toastr.success('Query copied to clipboard!')
    }

    return (
        <Row>
            <Col md={12}>
                <Modal show={isLoading}>
                    <h4>Requesting data, please wait... <i className='fas fa-spinner fa-spin'></i></h4>
                </Modal>
                <Card>
                    <Card.Header>
                        <Container fluid>
                            <Row>
                                <Col>
                                    <Card.Title>{props.pageTitle}</Card.Title>
                                    <h6>{table?.getDataCount()} results</h6>
                                </Col>
                                {props.createObjectFunction &&
                                    <Col md={2}>
                                        <Button variant='success' onClick={props.createObjectFunction}>
                                            <i className='fas fa-square-plus'></i>Create {props.pageTitle}
                                        </Button>
                                    </Col>
                                }
                                <Col md={props.createObjectFunction ? 6 : 8}>
                                    <InputGroup style={{display: 'flex', width: '100%'}}>
                                        <InputGroup.Text>Select Active Filters: </InputGroup.Text>
                                        <Select
                                            options={filters.filter(filter => filter.type != 'SelectFilter' || filter.creatable || (filter.selections && filter.selections.length > 1))}
                                            value={filters.filter(filter => filter.active) || ''}
                                            getOptionLabel={option => option.name}
                                            onChange={filters => handleActiveFiltersChange(filters)}
                                            isDisabled={filters.length === 0}
                                            isMulti
                                            styles={{
                                                container: (baseStyles, state) => ({
                                                    ...baseStyles,
                                                    flexGrow: 1,
                                                }),
                                                menuList: (baseStyles, state) => ({
                                                    ...baseStyles,
                                                    fontSize: 14,
                                                })
                                            }}
                                        />
                                        <Button variant='success' onClick={() => fetchTableData()}>Apply Filters</Button>
                                    </InputGroup>
                                </Col>
                                <Col style={{textAlign: 'right'}}>
                                    <ButtonGroup>
                                        <Dropdown
                                            className='btn btn-secondary'
                                            title={<div>View <i className='fas fa-caret-down' style={{paddingLeft: '5px'}} /></div>}
                                        >
                                            <Dropdown.Item>
                                                <h6><i className='fas fa-caret-left'></i> Columns</h6>
                                                <Dropdown.Submenu>
                                                    {columns.filter(column => column.field != undefined).map(column =>
                                                        <Dropdown.Item
                                                            key={column.field}
                                                            style={{color: column.visible === false  ? 'red' : 'black'}}
                                                            onClick={() => toggleColumnVisibility(column)}
                                                        >{column.title}</Dropdown.Item>
                                                    )}
                                                </Dropdown.Submenu>
                                            </Dropdown.Item>
                                            {props.groupByOptions.length > 0 &&
                                                <Dropdown.Item>
                                                    <h6><i className='fas fa-caret-left'></i> Group By</h6>
                                                    <Dropdown.Submenu>
                                                        {props.groupByOptions.map(option =>
                                                            <Dropdown.Item
                                                                key={option.value}
                                                                style={{
                                                                    background: option.value == groupBy?.value ? 'green' : 'none',
                                                                    color: option.value == groupBy?.value ? 'white' : 'black'
                                                                }}
                                                                onClick={() => setGroupBy(option)}
                                                            >{option.label}</Dropdown.Item>
                                                        )}
                                                    </Dropdown.Submenu>
                                                </Dropdown.Item>
                                            }
                                        </Dropdown>
                                        <Dropdown
                                            className='btn btn-secondary'
                                            title={<div>Actions <i className='fas fa-caret-down' style={{paddingLeft: '5px'}} /></div>}
                                        >
                                            <Dropdown.Item
                                                onClick={table?.print}
                                            ><h6><i className='fas fa-print' style={{paddingRight: '5px'}}></i>Print Table</h6>
                                            </Dropdown.Item>
                                            {props.withSelected &&
                                                <Dropdown.Item>
                                                    <h6><i className='fas fa-caret-left'></i> With Selected</h6>
                                                    <Dropdown.Submenu>
                                                        {props.withSelected &&
                                                            <ButtonGroup vertical style={{padding: 10, margin: 0}}>
                                                                {props.withSelected.map(menuItem =>
                                                                    <Button
                                                                        key={menuItem.label}
                                                                        onClick={() => menuItem.onClick(table?.getSelectedRows(), menuItem.options ?? null)}
                                                                        disabled={!tableBuilt}
                                                                        size='sm'
                                                                    >
                                                                        <h6>{menuItem.icon && <i className={menuItem.icon}></i>} {menuItem.label}</h6>
                                                                    </Button>
                                                                )}
                                                            </ButtonGroup>
                                                        }
                                                    </Dropdown.Submenu>
                                                </Dropdown.Item>
                                            }
                                            <Dropdown.Item>
                                                <h6><i className='fas fa-caret-left'></i> Queries</h6>
                                                <Dropdown.Submenu style={{width: '400px'}}>
                                                    <InputGroup>
                                                        <InputGroup.Text>Name: </InputGroup.Text>
                                                        <FormControl
                                                            value={queryName}
                                                            onChange={event => setQueryName(event.target.value)}
                                                        ></FormControl>
                                                        <Button
                                                            onClick={saveCurrentQuery}
                                                        >Save As</Button>
                                                    </InputGroup>
                                                    {queries &&
                                                        <Fragment>
                                                            <hr />
                                                            <ButtonGroup vertical style={{width: '100%'}}>
                                                                {queries.map(query =>
                                                                    <ButtonGroup>
                                                                        <Button
                                                                            key={query.id + '.delete'}
                                                                            onClick={() => deleteQuery(query)}
                                                                            size='sm'
                                                                            variant='danger'
                                                                            style={{flex: 0}}
                                                                        ><i className='fas fa-trash'></i></Button>
                                                                        <Button
                                                                            key={query.id + '.load'}
                                                                            onClick={() => history.push(location.pathname + query.query_string)}
                                                                            size='sm'
                                                                            style={{flex: 1}}
                                                                        >{query.name}</Button>
                                                                        <Button
                                                                            key={query.id + '.share'}
                                                                            onClick={() => writeQueryToClipboard(query.query_string)}
                                                                            size='sm'
                                                                            style={{flex: 0}}
                                                                            variant='success'
                                                                        ><i className='fas fa-share'></i></Button>
                                                                    </ButtonGroup>
                                                                )}
                                                            </ButtonGroup>
                                                        </Fragment>
                                                    }
                                                </Dropdown.Submenu>
                                            </Dropdown.Item>
                                        </Dropdown>
                                    </ButtonGroup>
                                </Col>
                            </Row>
                        </Container>
                    </Card.Header>
                    {filters.some(filter => filter.active) &&
                        <Card.Body>
                            <Row>
                                <Col md={12}>
                                    <TableFilters
                                        filters={filters}
                                        setFilters={setFilters}
                                    />
                                </Col>
                            </Row>
                        </Card.Body>
                    }
                    <Card.Footer>
                        <div ref={tableRef}></div>
                    </Card.Footer>
                </Card>
            </Col>
        </Row>
    )
}

