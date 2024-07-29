import React, { useState, useEffect } from 'react';
import CurrencyInput from 'react-currency-input-field'

export default function CurrencyCellEditor({ cell, exitEditingMode, data, setData }) {
    const [value, setValue] = useState(cell.getValue());

    const onBlur = () => {
        // Format the value to two decimal places
        const formattedValue = parseFloat(value).toFixed(2);
        console.log(cell)
        // table.setEditingCell(null)
        // table.setEditingRow(null)
        return
        exitEditingMode();
    };

    // const handleKeyDown = (event) => {
    //     if (event.key === 'Enter') {
    //         handleBlur();
    //     }
    // };

    // const onBlur = (event) => {
    //     row._valuesCache[column.id] = event.target.value;
    //     if (isCreating) {
    //         setCreatingRow(row);
    //     } else if (isEditing) {
    //         setEditingRow(row);
    //     }
    // };

    useEffect(() => {
        setValue(cell.getValue());
    }, [cell]);

    return (
        <CurrencyInput
            decimalsLimit={2}
            decimalScale={2}
            min={0.01}
            onValueChange={setValue}
            prefix='$'
            step={0.01}
            value={value}
            onBlur={onBlur}
            onKeyDown={event => {
                if(event.key === 'Enter')
                    onBlur()
            }}
        />
    )

    return (
        <TextField
            value={value}
            onChange={handleChange}
            onBlur={handleBlur}
            onKeyDown={handleKeyDown}
            inputProps={{ inputMode: 'decimal', pattern: '^\d*(\.\d{0,2})?$' }}
            autoFocus
        />
    );
};
