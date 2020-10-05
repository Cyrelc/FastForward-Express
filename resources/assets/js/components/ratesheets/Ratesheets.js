import React from 'react'

import Table from '../partials/Table'

const columns = [
    {title: 'Ratesheet ID', field: 'ratesheet_id', formatter:'link', formatterParams:{labelField:'ratesheet_id', urlPrefix:'/app/ratesheets/edit/'}, width: 150},
    {title: 'Name', field: 'name', width: 150}
]

const filters = []

const groupByOptions = []

const initialSort =[{column: 'ratesheet_id', dir: 'desc'}]

export default function Ratesheets(props) {
    return (
        <Table
            baseRoute='/ratesheets/buildTable'
            columns={columns}
            filters={filters}
            groupByOptions={groupByOptions}
            initialSort={initialSort}
            pageTitle='Ratesheets'
        />
    )
}
