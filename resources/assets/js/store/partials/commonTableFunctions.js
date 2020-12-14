export const setSortedList = (tableRef, idColumn) => {
    console.log("SET_SORTED_LIST")
    const data = tableRef.current.table.getData()
    return data.map(row => row[idColumn])
} 

export const toggleColumnVisibility = (action) => {
    const newColumns = action.payload.columns.map(column => {
        if(column.field === action.payload.toggleColumn.field)
            if(column.visible === undefined)
                return {...column, visible: false}
            else
                return {...column, visible: !column.visible}
        return column
    })
    return newColumns
}
