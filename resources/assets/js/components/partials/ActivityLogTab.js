import React from 'react';
import {Card} from 'react-bootstrap';
import {ReactTabulator, reactFormatter} from 'react-tabulator'

function AttributeTable(props) {
    const data = props.cell.getValue()
    return (
        <table style={{border: '1px solid black'}}>
            <thead>
                <tr>
                    <td style={{border: '1px solid black'}}>Attribute</td>
                    <td style={{border: '1px solid black'}}>New Value</td>
                    <td style={{border: '1px solid black'}}>Old Value</td>
                </tr>
            </thead>
            <tbody>
                {Object.keys(data.attributes).map(key =>
                    <tr key={Math.random()}>
                        <td style={{border: '1px solid black', wordWrap: 'break-word'}}>{key}</td>
                        <td style={{border: '1px solid black', wordWrap: 'break-word'}}>{data.attributes[key]}</td>
                        <td style={{border: '1px solid black', wordWrap: 'break-word'}}>{data.old ? data.old[key] : ''}</td>
                    </tr>
                )}
            </tbody>
        </table>
    )
}

const activityLogColumns = [
    {title: 'Date Modified', field: 'updated_at', width:150},
    {title: 'Type', field: 'subject_type', formatter: cell => {return cell.getValue().split('\\')[1]}, headerFilter: true, width: 150},
    {title: 'Subject ID', field: 'subject_id', width: 100},
    {title: 'Action', field: 'description', headerFilter: true, width: 100},
    {title: 'Modified By', field: 'user_name', headerFilter: true, width: 200},
    {title: 'Attributes', field: 'properties', formatter: reactFormatter(<AttributeTable/>), headerSort: false}
]

export default function ActivityLogTab(props) {
    return (
        <Card border='dark'>
            <Card.Body>
                <ReactTabulator
                    columns={activityLogColumns}
                    data={props.activityLog}
                    layout='fitData'
                    responsiveLayout='collapse'
                    options={{
                        pagination:true,
                        paginationSize:5
                    }}
                />
            </Card.Body>
        </Card>
    )
}
