import React, {useEffect, useMemo, useState} from 'react'
import {Button, Card, FormControl, InputGroup} from 'react-bootstrap'
import queryString from 'query-string'
import {useHistory, useLocation} from 'react-router-dom'
import {useAPI} from '../../contexts/APIContext'
import {useUser} from '../../contexts/UserContext'
import {MaterialReactTable, useMaterialReactTable} from 'material-react-table'

import {LinkCellRenderer} from '../../utils/table_cell_renderers'

const OtherFieldsRenderer = ({ row, searchTerm }) => {
    const rowData = row.original;

    const matches = Object.keys(rowData).map((key) => {
        if (key === 'link' || key === 'object_id') return null;

        if (rowData[key] && rowData[key].toString().includes(searchTerm)) {
            const parts = rowData[key].toString().split(new RegExp(`(${searchTerm})`, 'gi'));
            return (
                <div key={key}>
                    {parts.map((part, index) =>
                        part.toLowerCase() === searchTerm.toLowerCase() ? (
                            <span key={index} style={{ backgroundColor: 'lightgreen', color: 'black'}}>
                                {part}
                            </span>
                        ) : (
                                part
                            )
                    )}
                </div>
            );
        }

        return null;
    }).filter((element) => element !== null);

    return <div>{matches}</div>;
};

export default function Search(props) {
    const [searchTerm, setSearchTerm] = useState('')
    const [searchResults, setSearchResults] = useState([])

    const api = useAPI()
    const history = useHistory();
    const location = useLocation();
    const {authenticatedUser} = useUser()

    const columns = useMemo(() => [
        {header: 'Result Type', accessorKey: 'result_type', size: '10%'},
        ...authenticatedUser.employee ? [
            {
                header: 'Object ID',
                accessorKey: 'object_id',
                size: '10%',
                Cell: ({renderedCellValue, row}) => (
                    <LinkCellRenderer renderedCellValue={renderedCellValue} row={row} urlPrefix='' redirectField='link' />
                ),
            }
        ] : [],
        {
            header: 'Name',
            accessorKey: 'name',
            Cell: ({renderedCellValue, row}) => (
                <LinkCellRenderer renderedCellValue={renderedCellValue} row={row} urlPrefix='' redirectField='link' />
            ),
        },
        {
            header: 'Other',
            accessorKey: 'other',
            Cell: ({renderedCellValue, row}) => (
                <OtherFieldsRenderer renderedCellValue={renderedCellValue} row={row} searchTerm={searchTerm} />
            ),
            enableSorting: false
        }
    ], [searchTerm])

    const searchTable = useMaterialReactTable({
        columns,
        data: searchResults,
        initialState: {
            density: 'compact'
        }
    })

    const updateSearchQuery = () => {
        history.push({search: `query=${searchTerm}`})
    }

    useEffect(() => {
        if(searchTerm != location.search)
            setSearchTerm(queryString.parse(location.search)['query'])
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
                <MaterialReactTable table={searchTable} />
            </Card.Body>
        </Card>
    )
}
