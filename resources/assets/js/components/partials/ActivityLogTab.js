import React, {useEffect, useMemo, useRef, useState} from 'react';
import {Card, Table} from 'react-bootstrap';
import {MaterialReactTable, useMaterialReactTable} from 'material-react-table'

const ExpandableTable = ({ properties }) => {
    const columns = useMemo(() => [
        { accessorKey: 'key', header: 'Key' },
        { accessorKey: 'oldValue', header: 'Old Value', enableHiding: !!properties.old },
        { accessorKey: 'newValue', header: properties.old ? 'New Value' : 'Value' },
    ].filter(col => col.accessorKey !== 'oldValue' || properties.old), [properties]);

    const data = useMemo(() =>
        Object.keys(properties.attributes).map(key => ({
            key,
            newValue: properties.attributes[key],
            oldValue: properties?.old ? properties.old[key] : null
        })), [properties]);

    const table = useMaterialReactTable({
        columns,
        data,
        enableSorting: true,
        enableGlobalFilter: true,
        initialState: {density: 'compact'},
    });

    return <MaterialReactTable table={table} />;
};

export default function ActivityLogTab(props) {
    const {activityLog} = props

    const columns = useMemo(() => [
        { accessorKey: 'updated_at', header: 'Updated At' },
        {
            accessorKey: 'subject_type',
            header: 'Subject Type',
            Cell: ({row}) => {
                let value = row.original.subject_type.split('\\').slice(-1)[0]

                value = value.split('').map((char, index) => {
                    return (char === char.toUpperCase() && index !== 0) ? ' ' + char : char
                }).join('')
                return value
            }
        },
        { accessorKey: 'subject_id', header: 'Subject ID' },
        { accessorKey: 'description', header: 'Description' },
        { accessorKey: 'user_name', header: 'User Name' },
    ], []);

    const table = useMaterialReactTable({
        columns,
        data: activityLog,
        enableExpandAll: false,
        filterFns: {
            custom: (row, id, filterValue) => {
                const rowString = Object.keys(row.original)
                    .map(key => {
                        const cellValue = row.original[key];
                        if (typeof cellValue === 'object') {
                            return JSON.stringify(cellValue);
                        }
                        return String(cellValue);
                    })
                    .join(' ');
                return rowString.toLowerCase().includes(filterValue.toLowerCase());
            }
        },
        enableGlobalFilter: true,
        globalFilterFn: 'custom',
        initialState: {
            density: 'compact',
            sorting: [{id: 'updated_at', desc: true}],
        },
        muiDetailPanelProps: () => ({
            sx: theme => ('rgba(255,210,244,0.1)'),
        }),
        muiExpandButtonProps: ({ row }) => ({
            onClick: () => table.setExpanded({ [row.id]: !row.getIsExpanded() }),
            sx: {
                transform: row.getIsExpanded() ? 'rotate(180deg)' : 'rotate(-90deg)',
                transition: 'transform 0.2s',
            },
        }),
        renderDetailPanel: ({ row }) => (
            <ExpandableTable properties={row.original.properties} />
        ),
        muiTableBodyProps: {
            sx: {
                '& tr:nth-of-type(odd) > td': {
                    backgroundColor: 'dimgray',
                },
            },
        },
    });

    return (
        <Card border='dark'>
            <Card.Body>
                <MaterialReactTable table={table} />
            </Card.Body>
        </Card>
    )
}
