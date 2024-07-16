import React, {useEffect, useRef, useState} from 'react';
import {Card, Table} from 'react-bootstrap';
import {TabulatorFull as Tabulator} from 'tabulator-tables'

const transformAttributes = data => {
    if(!data.attributes)
        return data
    return Object.keys(data.attributes).map(key => {
        if(data.old)
            return {key: key, new: data.attributes[key], old: data.old[key]}
        return {key: key, new: data.attributes[key], old: null}
    })
}

const activityLogColumns = [
    {title: 'Date Modified', field: 'updated_at'},
    // subject type is stored as the absolute location of the class in PHP, so generally App\Models\ClassName - additionally another \ is added when storing in the database to escape the backslash in the path name
    {
        title: 'Type',
        field: 'subject_type',
        formatter: cell => {
            let value = cell.getValue().split('\\').slice(-1)[0]

            value = value.split('').map((char, index) => {
                return (char === char.toUpperCase() && index !== 0) ? ' ' + char : char
            }).join('')
            return value
        },
        headerFilter: true,
    },
    {title: 'Subject ID', field: 'subject_id'},
    {title: 'Action', field: 'description', headerFilter: true},
    {title: 'Modified By', field: 'user_name', headerFilter: true},
]

export default function ActivityLogTab(props) {
    const [isLoading, setIsLoading] = useState(true)
    const [table, setTable] = useState()

    const {activityLog} = props
    const tableRef = useRef(null)

    useEffect(() => {
        if(tableRef.current && isLoading) {
            const table = new Tabulator(tableRef.current, {
                columns: activityLogColumns,
                data: activityLog,
                height: '70vh',
                initialSort: 'updated_at',
                layout: 'fitColumns',
                pagination: 'local',
                paginationSize: 5,
                rowFormatter: row => {
                    let data = row.getData().properties
                    try {
                        data = JSON.parse(row.getData().properties)
                    } catch (error) {}

                    data = transformAttributes(data)
                    if(!data.length)
                        return
                    //create and style holder elements
                    var holderEl = document.createElement("div");
                    var tableEl = document.createElement("div");

                    holderEl.style.boxSizing = "border-box";
                    holderEl.style.padding = "10px 30px 10px 10px";
                    holderEl.style.borderTop = "1px solid #333";
                    holderEl.style.borderBotom = "1px solid #333";

                    tableEl.style.border = "1px solid #333";

                    holderEl.appendChild(tableEl);

                    row.getElement().appendChild(holderEl);

                    new Tabulator(tableEl, {
                        layout:'fitColumns',
                        data: data,
                        columns:[
                            {title:"Attribute", field:"key"},
                            {title:"New Value", field:"new"},
                            ...data[0].old ? [{title:"Old Value", field:"old"}] : [],
                        ],
                        rowFormatter: row => {
                            row.getElement().classList.add('table-dark')
                            row.getElement().classList.add('bg-dark')
                        }
                    })
                },
            })
            setIsLoading(false)
            setTable(table)
        }
    }, [tableRef])

    useEffect(() => {
        if(table && !isLoading) {
            table.redraw()
        }
    }, [table, isLoading])

    return (
        <Card border='dark'>
            <Card.Body>
                <div ref={tableRef}/>
            </Card.Body>
        </Card>
    )
}
