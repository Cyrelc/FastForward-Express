export const fakeLinkFormatter = (cell, formatterParams) => {
    if(formatterParams && formatterParams.labelField) {
        const data = cell.getRow().getData()
        return '<span class="fakeLink">' + data[formatterParams.labelField] + '</span>'
    }
    return "<span class='fakeLink'>" + cell.getValue() + "</span>"
}

export const setSortedList = (tableRef, idColumn) => {
    console.log("SET_SORTED_LIST")
    const data = tableRef.current.table.getData()
    return data.map(row => row[idColumn])
} 

export const toggleActiveFilters = (filters, action) => {
    console.log('TOGGLING_ACTIVE_FILTERS')
    const activeFilters = action.payload
    const newFilters = filters.map(filter => {
        if(activeFilters && activeFilters.some(activeFilter => activeFilter.value == filter.value))
            return {...filter, active: true}
        return {...filter, active: false}
    })
    console.log(newFilters)
    return newFilters
}

export const toggleColumnVisibility = (columns, action) => {
    const newColumns = columns.map(column => {
        if(column.field === action.payload.field)
            if(column.visible === undefined)
                return {...column, visible: false}
            else
                return {...column, visible: !column.visible}
        return column
    })
    return newColumns
}

export const updateGroupBy = (tableRef, groupByOptions, action) => {
    console.log(action)
    const {value, groupHeader} = action.payload
    if(value) {
        tableRef.current.table.setGroupBy(value)
        if(groupHeader)
            tableRef.current.table.setGroupHeader(groupHeader)
        else
            tableRef.current.table.setGroupHeader()
    } else
        tableRef.current.table.setGroupBy()

    return groupByOptions.find(option => option.value === value)[0]
}
