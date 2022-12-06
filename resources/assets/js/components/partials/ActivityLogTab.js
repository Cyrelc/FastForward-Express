import React from 'react';
import {Card, Table} from 'react-bootstrap';
import {ReactTabulator, reactFormatter} from 'react-tabulator'

function AttributeTable({cell}) {
    const data = cell.getValue()
    return (
        <Table striped size='sm' width='100%' responsive>
            <thead>
                <tr>
                    <th style={{textAlign: 'center'}}>Attribute</th>
                    <th style={{textAlign: 'center'}}>New Value</th>
                    <th style={{textAlign: 'center'}}>Old Value</th>
                </tr>
            </thead>
            <tbody>
                {Object.keys(data.attributes).map(key =>
                    <tr key={Math.random()}>
                        <th>{key}</th>
                        <td style={{wordWrap: 'break-word'}}>{data.attributes[key]}</td>
                        <td style={{wordWrap: 'break-work'}}>{data.old ? data.old[key] : ''}</td>
                    </tr>
                )}
            </tbody>
        </Table>
    )
}

const activityLogColumns = [
    {title: 'Date Modified', field: 'updated_at', width:150},
    {title: 'Type', field: 'subject_type', formatter: cell => {return cell.getValue().split('\\')[-1]}, headerFilter: true, width: 150},
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
