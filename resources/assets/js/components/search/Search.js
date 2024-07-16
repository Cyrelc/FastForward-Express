import React, {useEffect, useRef, useState} from 'react'
import {Button, Card, FormControl, InputGroup} from 'react-bootstrap'
import queryString from 'query-string'
import {TabulatorFull as Tabulator} from 'tabulator-tables'
import {useHistory, useLocation} from 'react-router-dom'
import {useAPI} from '../../contexts/APIContext'
import {useUser} from '../../contexts/UserContext'

export default function Search(props) {
    const [searchTerm, setSearchTerm] = useState('')
    const [searchResults, setSearchResults] = useState([])
    const [table, setTable] = useState()

    const api = useAPI()
    const history = useHistory();
    const location = useLocation();
    const tableRef = useRef()
    const {authenticatedUser} = useUser()

    useEffect(() => {
        if(!table && tableRef.current) {
            const newTabulator = new Tabulator(tableRef.current, {
                columns: tableColumns,
                data: searchResults,
                height: '85vh',
                layout: 'fitDataStretch',
                pagination: 'local',
                paginationSize: 25,
                placeholder: 'No results found matching your request. Please try a different query'
            })

            setTable(newTabulator)
        }
    }, [tableRef, table])

    useEffect(() => {
        if(table)
            table.setData(searchResults)
    }, [searchResults])

    const otherFieldsFormatter = (cell) => {
        const rowData = cell.getRow().getData()

        const matches = Object.keys(rowData).map(key => {
            if(key == 'link' || key == 'object_id')
                return null
            if(rowData[key] && rowData[key].toString().includes(searchTerm)) {
                const reg = new RegExp(searchTerm, 'gi')
                return rowData[key].toString().replace(reg, str => {return `<span style='background-color: yellow'>${str}</span>`})
            }
            return null
        }).filter(element => element != null)
        return '<div>' +
            matches.map(match => {
                return match
            }) +
        '</div>';
    }

    const tableColumns = [
        {title: 'Result Type', field: 'type', width: '10%'},
        ...authenticatedUser.employee ? [
            {title: 'Object ID', field: 'object_id', width: '10%', ...configureFakeLink('', history.push, null, 'link')}
        ] : [],
        {title: 'Name', field: 'name', ...configureFakeLink('', history.push, null, 'link')},
        {title: 'Other', field: 'other', formatter: otherFieldsFormatter, headerSort: false}
    ]

    const updateSearchQuery = () => {
        history.push({search: `term=${searchTerm}`})
    }

    useEffect(() => {
        if(searchTerm != location.search)
            setSearchTerm(queryString.parse(location.search)['term'])
        api.get(`/search${location.search}`).then(response => {
            if(response.length == 1)
                history.push(response[0].link)
            setSearchResults(response)
        })
    }, [location.search])

    return (
        <Card>
            <Card.Header>
                <InputGroup>
                    <InputGroup.Text>Search</InputGroup.Text>
                    <FormControl
                        onChange={event => setSearchTerm(event.target.value)}
                        value={searchTerm}
                        onKeyPress={event => {
                            if(event.key === 'Enter' && searchTerm)
                                updateSearchQuery()
                        }}
                    />
                    <Button variant='success' onClick={updateSearchQuery}>Submit</Button>
                </InputGroup>
            </Card.Header>
            <Card.Body>
                <div ref={tableRef}></div>
            </Card.Body>
        </Card>
    )
}
