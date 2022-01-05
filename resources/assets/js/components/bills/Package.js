import React from 'react'
import {InputGroup, FormControl, Button} from 'react-bootstrap'

export default function Package(props) {
    return (
        <tr key={props.package.packageId}>
            <td key={props.package.packageId + 'delete'}>
                {(props.packageCount > 1 && !props.readOnly) && 
                    <Button variant='danger' onClick={() => props.deletePackage(props.package.packageId)}><i className='fas fa-trash'></i></Button>
                }
            </td>
            <td key={props.package.packageId + 'count'}>
                <InputGroup>
                    <FormControl 
                        type='number'
                        min={1}
                        name='packageCount'
                        data-packageid={props.package.packageId}
                        value={props.package.packageCount}
                        onChange={props.handleChanges}
                        readOnly={props.readOnly}
                    />
                    <InputGroup.Text>{props.package.packageCount > 1 ? ' Pieces' : ' Piece'}</InputGroup.Text>
                </InputGroup>
            </td>
            <td key={props.package.packageId + 'weight'}>
                <InputGroup>
                    <FormControl 
                        type='number'
                        min={1}
                        name='packageWeight'
                        data-packageid={props.package.packageId}
                        value={props.package.packageWeight}
                        onChange={props.handleChanges}
                        readOnly={props.readOnly}
                    />
                    <InputGroup.Text>{props.useImperial ? 'lbs' : 'kgs'}</InputGroup.Text>
                </InputGroup>
            </td>
            <td key={props.package.packageId + 'length'}>
                <InputGroup>
                    <FormControl 
                        type='number'
                        min={1}
                        name='packageLength'
                        value={props.package.packageLength}
                        data-packageid={props.package.packageId}
                        onChange={props.handleChanges}
                        readOnly={props.readOnly}
                    />
                    <InputGroup.Text>{props.useImperial ? 'in' : 'cm'}</InputGroup.Text>
                </InputGroup>
            </td>
            <td key={props.package.packageId + 'width'}>
                <InputGroup>
                    <FormControl 
                        type='number'
                        min={1}
                        name='packageWidth'
                        data-packageid={props.package.packageId}
                        value={props.package.packageWidth}
                        onChange={props.handleChanges}
                        readOnly={props.readOnly}
                    />
                    <InputGroup.Text>{props.useImperial ? 'in' : 'cm'}</InputGroup.Text>
                </InputGroup>
            </td>
            <td key={props.package.packageId + 'height'}>
                <InputGroup>
                    <FormControl 
                        type='number'
                        min={1}
                        name='packageHeight'
                        data-packageid={props.package.packageId}
                        value={props.package.packageHeight}
                        onChange={props.handleChanges}
                        readOnly={props.readOnly}
                    />
                    <InputGroup.Text>{props.useImperial ? 'in' : 'cm'}</InputGroup.Text>
                </InputGroup>
            </td>
        </tr>
    )
}
