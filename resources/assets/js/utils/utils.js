import React from 'react';

import {useHistory} from 'react-router-dom'

export const LinkCellRenderer = ({renderedCellValue, row, urlPrefix, labelField = null}) => {
    const history = useHistory()

    const handleClick = () => {
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

