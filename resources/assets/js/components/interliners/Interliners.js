import React from 'react'

import Table from '../partials/Table'

const columns = [
    {title: 'Interliner ID', field: 'interliner_id', formatter: 'link', formatterParams:{url: (cell) => {return '/interliners/edit/' + cell.getRow().getData().interliner_id}}},
    {title: 'Interliner Name', field: 'interliner_name', formatter: 'link', formatterParams: {url: (cell) => {return '/interliners/edit/' + cell.getRow().getData().interliner_id}}},
    {title: 'Address', field: 'formatted'},
    {title: 'Address Name', field: 'address_name'}
]

const filters = [

]

const groupByOptions = [

]

const initialSort = [{column: 'interliner_id', dir: 'asc'}]

export default function Interliners(props) {
    return (
        <Table
            baseRoute='/interliners/buildTable'
            columns={columns}
            filters={filters}
            initialSort={initialSort}
            pageTitle='Interliners'
        />
    )
}
