import React from 'react';

import {useHistory} from 'react-router-dom'

export const LinkCellRenderer = (props) => {
    const history = useHistory()

    const handleClick = () => {
        history.push(`${props.urlPrefix}${props.value}`)
    };

    return (
        <span
            style={{ cursor: 'pointer', color: 'inherit', textDecoration: 'underline' }}
            onClick={handleClick}
        >
            {props.labelField ? props.data[props.labelField] : props.value}
        </span>
    );
};

