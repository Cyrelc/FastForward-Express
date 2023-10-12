import React from 'react';
import {Card, Table} from 'react-bootstrap';
import {ReactTabulator, reactFormatter} from 'react-tabulator'

function AttributeTable({cell}) {
    const data = cell.getValue()
    const activityLogType = cell.getRow().getData().description

    return (
        <Table striped bordered size='sm' width='100%' responsive>
            <thead>
                <tr>
                    <th style={{textAlign: 'center'}}>Attribute</th>
                    <th style={{textAlign: 'center'}}>New Value</th>
                    {activityLogType != 'created' && 
                        <th style={{textAlign: 'center'}}>Old Value</th>
                    }
                </tr>
            </thead>
            <tbody>
                {Object.keys(data.attributes).map(key => {
                    let value = data?.attributes[key]
                    let oldValue = data?.old ? data.old[key] : ''

                    try {
                        const parsedJSON = JSON.parse(data?.attributes[key])
                        if (parsedJSON)
                            value = parsedJSON
                    } catch (e) {}

                    try {
                        const parsedJSON = JSON.parse(data?.old[key])
                        if(parsedJSON)
                            oldValue = parsedJSON
                    } catch (e) {}

                    return (
                        <tr key={Math.random()}>
                            <th>{key}</th>
                            <td>
                                <pre>{JSON.stringify(value, null, '\t')}</pre>
                            </td>
                            {activityLogType != 'created' &&
                                <td>
                                    <pre>{JSON.stringify(oldValue, null, '\t')}</pre>
                                </td>
                            }
                        </tr>
                    )
                })}
            </tbody>
        </Table>
    )
}

const activityLogColumns = [
    {title: 'Date Modified', field: 'updated_at', width:150},
    {title: 'Type', field: 'subject_type', formatter: cell => {return cell.getValue().split('\\').slice(-1)[0]}, headerFilter: true, width: 150},
    {title: 'Subject ID', field: 'subject_id', width: 100},
    {title: 'Action', field: 'description', headerFilter: true, width: 100},
    {title: 'Modified By', field: 'user_name', headerFilter: true, width: 200},
    {title: 'Attributes', field: 'properties', formatter: reactFormatter(<AttributeTable/>), headerSort: false}
]

export default function ActivityLogTab(props) {
    const {activityLog} = props
    return (
        <Card border='dark'>
            <Card.Body>
                <ReactTabulator
                    columns={activityLogColumns}
                    data={activityLog}
                    height='70vh'
                    layout='fitDataStretch'
                    options={{
                        pagination: 'local',
                        paginationSize: 10
                    }}
                    responsiveLayout='collapse'
                />
            </Card.Body>
        </Card>
    )
}
