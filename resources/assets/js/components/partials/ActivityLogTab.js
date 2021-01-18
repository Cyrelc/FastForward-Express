import React from 'react';
import {Card, Table} from 'react-bootstrap';

export default function ActivityLogTab(props) {
    return (
        <Card border='dark'>
            <Card.Body>
                <Table striped bordered size='sm' variant='dark' style={{tableLayout: 'fixed', width: '100%'}}>
                    <thead>
                        <tr>
                            <td>Date Modified</td>
                            <td>Object</td>
                            <td>Object ID</td>
                            <td>Modified By</td>
                            <td>Property</td>
                            <td>Old Value</td>
                            <td>New Value</td>
                        </tr>
                    </thead>
                    <tbody>
                        {props.activityLog.map((log, index) => {
                            return Object.keys(log.properties.attributes).map((key, attributesIndex) => {
                                if(attributesIndex)
                                    return (
                                        <tr>
                                            <td style={{wordWrap: 'break-word'}}>{key}</td>
                                            <td style={{wordWrap: 'break-word'}}>{log.properties.old ? log.properties.old[key] : ''}</td>
                                            <td style={{wordWrap: 'break-word'}}>{log.properties.attributes[key]}</td>
                                        </tr>
                                    )
                                else {
                                    const rowSpanLength = Object.keys(log.properties.attributes).length
                                    return (
                                        <tr key={index + '.' + key}>
                                            <td rowSpan={rowSpanLength}>{log.updated_at}</td>
                                            <td rowSpan={rowSpanLength}>{log.subject_type}</td>
                                            <td rowSpan={rowSpanLength}>{log.subject_id}</td>
                                            <td rowSpan={rowSpanLength}>{log.user_name}</td>
                                            <td style={{wordWrap: 'break-word'}}>{key}</td>
                                            <td style={{wordWrap: 'break-word'}}>{log.properties.old ? log.properties.old[key] : ''}</td>
                                            <td style={{wordWrap: 'break-word'}}>{log.properties.attributes[key]}</td>
                                        </tr>
                                    )
                                }
                                })
                            })
                        }
                    </tbody>
                </Table>
            </Card.Body>
        </Card>
    )
}
