import React from 'react';

import {useHistory} from 'react-router-dom'

export const LinkCellRenderer = ({renderedCellValue, row, urlPrefix, labelField = null, redirectField = null}) => {
    const history = useHistory()

    const handleClick = () => {
        if(redirectField)
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

