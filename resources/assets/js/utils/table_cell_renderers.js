import React from 'react';

import {useHistory} from 'react-router-dom'

/**
 * 
 * @param {string} renderedCellValue the cell value as given by the material-table
 * @param {row} row the row object, as given by the material-table
 * @param {labelField} string a field name if you wish to label with a different value than the cell content
 * @param {redirectField} string a field name if you wish to redirect to a different value than the cell content
 * @param {url} string string representation of a string if you want to statically set the redirect
 */
export const LinkCellRenderer = ({renderedCellValue, row, urlPrefix, labelField = null, redirectField = null, url = null}) => {
    const history = useHistory()

    const handleClick = () => {
        if(url)
            history.push(url)
        else if(redirectField)
            history.push(`${urlPrefix}${row.original[redirectField]}`)
        else
            history.push(`${urlPrefix}${renderedCellValue}`)
    }

    return (
        <span
            style={{ cursor: 'pointer', color: 'inherit', textDecoration: 'underline' }}
            onClick={handleClick}
        >
            {labelField ? row.original[labelField] : renderedCellValue}
        </span>
    );
};

export const CurrencyCellRenderer = ({cell}) => (
    <div>
        {new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
        }).format(cell.getValue())}
    </div>
)

