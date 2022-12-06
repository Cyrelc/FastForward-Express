import React, {useEffect, useState} from 'react'
import {Button, Card, FormControl, InputGroup} from 'react-bootstrap'
import {connect} from 'react-redux'
import queryString from 'query-string'
import {ReactTabulator} from 'react-tabulator'
import {useHistory, useLocation} from 'react-router-dom'

const Search = (props) => {
    const [searchTerm, setSearchTerm] = useState('')
    const [searchResults, setSearchResults] = useState([])

    const history = useHistory();
    const location = useLocation();

    const otherFieldsFormatter = (cell) => {
        const rowData = cell.getRow().getData()

        const matches = Object.keys(rowData).map(key => {
            if(key == 'link')
                return null
            if(rowData[key] && rowData[key].toString().includes(searchTerm)) {
                const reg = new RegExp(searchTerm, 'gi')
                return `<b>${key}: </b>` + rowData[key].toString().replace(reg, str => {return `<span style='background-color: yellow'>${str}</span>`})
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
        ...props.authenticatedEmployee ? [{title: 'Object ID', field: 'object_id', width: '10%', ...configureFakeLink('', history.push, null, 'link')}] : [],
        {title: 'Name', field: 'name', ...configureFakeLink('', history.push, null, 'link')},
        {title: 'Other', field: 'other', formatter: otherFieldsFormatter, headerSort: false}
    ]

    const updateSearchQuery = () => {
        history.push({search: `term=${searchTerm}`})
    }

    useEffect(() => {
        if(searchTerm != location.search)
            setSearchTerm(queryString.parse(location.search)['term'])
        makeAjaxRequest(`/search${location.search}`, 'GET', null, response => {
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
                <ReactTabulator
                    columns={tableColumns}
                    data={searchResults}
                    height='85vh'
                    layout='fitDataStretch'
                    options={{
                        pagination:'local',
                        paginationSize:25,
                    }}
                    // responsiveLayout='collapse'
                />
            </Card.Body>
        </Card>
    )
}

const mapStateToProps = store => {
    return {
        authenticatedEmployee: store.app.authenticatedEmployee
    }
}

export default connect(mapStateToProps)(Search);
