import React from 'react';
import {Card, Table} from 'react-bootstrap';

export default function ActivityLogTab(props) {
    return (
        <Card border='dark'>
            <Card.Body>
                <Table striped bordered>
                    <thead>
                        <tr>
                            <td>Date Modified</td>
                            <td>Object</td>
                            <td>Object ID</td>
                            <td>Modified By</td>
                            <td>Properties</td>
                        </tr>
                    </thead>
                    <tbody>
                        {props.activityLog.map((log, index) => {
                            return(
                                <tr key={index}>
                                    <td key={index + '.updated_at'}>{log.updated_at}</td>
                                    <td key={index + '.subject_type'}>{log.subject_type}</td>
                                    <td key={index + '.subject_id'}>{log.subject_id}</td>
                                    <td key={index + '.user_name'}>{log.user_name}</td>
                                    <td key={index + '.properties'}>
                                        <table>
                                            <thead>
                                                <tr>
                                                    <td>Attribute</td>
                                                    {log.properties.old && <td>Old</td>}
                                                    <td>Value</td>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            {
                                                Object.keys(log.properties.attributes).map(key => {
                                                    return (
                                                        <tr key={index + '.' + key}>
                                                            <td><strong>{key}</strong></td>
                                                            {log.properties.old && <td>{log.properties.old[key]}</td>}
                                                            <td>{log.properties.attributes[key]}</td>
                                                        </tr>
                                                    )
                                                })
                                            }
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>
                            )
                        })
                    }
                    </tbody>
                </Table>
            </Card.Body>
        </Card>
    )
}
