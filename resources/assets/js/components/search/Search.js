import React, {useEffect, useState} from 'react'
import {Button, Card, FormControl, InputGroup} from 'react-bootstrap'
import queryString from 'query-string'
import {ReactTabulator} from 'react-tabulator'
import {useLocation} from 'react-router-dom'

const Search = (props) => {
    const [searchTerm, setSearchTerm] = useState('')
    const [searchResults, setSearchResults] = useState([])

    const location = useLocation();

    const tableColumns = [
        {title: 'Result Type', field: 'type'},
        {title: 'Name', field: 'link', formatter: 'link', formatterParams: {labelField: 'name'}},
        {title: 'Email', field: 'email'}
    ]

    const updateSearchQuery = () => {
        props.history.push({search: `term=${searchTerm}`})
    }

    useEffect(() => {
        if(searchTerm != location.search)
            setSearchTerm(queryString.parse(location.search)['term'])
        makeAjaxRequest(`/search${location.search}`, 'GET', null, response => {
            if(response.length == 1)
                props.history.push(response[0].link)
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
                <ReactTabulator
                    columns={tableColumns}
                    data={searchResults}
                    options={{
                        pagination:'local',
                        paginationSize:25
                    }}
                />
            </Card.Body>
        </Card>
    )
} 

export default Search;
