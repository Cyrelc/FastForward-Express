import React from 'react'
import {Button, Col, InputGroup, FormControl, Row, Table} from 'react-bootstrap'

const bracketsInfo = 'The "Price" column can be applied only once for the entire range, or in segments of X amount.\n' +
    'For example, if your range goes from 25 Kg to 525 Kg, with a price of $1.50 per 100 Kg, then it would result in the following charges:\n\n' +
    '25 Kg to 125 Kg = $1.50\n' +
    '125 Kg to 225 Kg = $3.00\n' +
    '225 Kg to 325 Kg = $4.50\n' +
    'etc...\n\n' +
    'To have no separation, and instead to price for the entire range, simply leave the field blank, or enter 0'

export default function WeightRate(props) {

    function deleteWeightBracket(index) {
        props.handleWeightRateChange({...props.weightRate, brackets: props.weightRate.brackets.filter((bracket, i) => i != index)}, props.index)
    }

    function handleChange(event) {
        const {name, value} = event.target
        switch(name) {
            case 'name':
                props.handleWeightRateChange({...props.weightRate, name: value}, props.index)
                break
            case 'basePrice':
            case 'incrementalPrice':
                props.handleWeightRateChange({...props.weightRate, brackets: props.weightRate.brackets.map((bracket, index) => {
                    if(index == event.target.dataset.weightbracketindex) {
                        return {...bracket, [name]: value}
                    }
                    return bracket
                })}, props.index)
                break
            case 'lbmax':
            case 'kgmax':
                props.handleWeightRateChange({...props.weightRate, brackets: props.weightRate.brackets.map((bracket, index) => {
                    if(index == event.target.dataset.weightbracketindex)
                        return {
                            ...bracket,
                            lbmax: name === 'lbmax' ? value : kilogramsToPounds(value),
                            kgmax: name === 'kgmax' ? value : poundsToKilograms(value)
                        }
                    return bracket
                })}, props.index)
                break
            case 'additionalXKgs':
            case 'additionalXLbs':
                props.handleWeightRateChange({...props.weightRate, brackets: props.weightRate.brackets.map((bracket, index) => {
                    if(index == event.target.dataset.weightbracketindex)
                        return {
                            ...bracket,
                            additionalXKgs: name === 'additionalXKgs' ? value : poundsToKilograms(value),
                            additionalXLbs: name === 'additionalXLbs' ? value : kilogramsToPounds(value)
                        }
                    return bracket
                })}, props.index)
                break
        }
    }

    function isEmpty(value) {
        if(value === '' || value === undefined || value === 0)
            return true
        return false
    }

    function newWeightBracket() {
        const brackets = props.weightRate.brackets.concat([{
            price: 0.00, kgmax: 0.00, lbmax: 0.00, additionalXKgs: 0.00, additionalXLbs: 0.00
        }])
        console.log(brackets)
        props.handleWeightRateChange({...props.weightRate, brackets: brackets}, props.index)
    }

    return(
        <Row>
            <Col md={2}>
                <InputGroup>
                    {props.weightRate.name != 'Basic Weight Rate' && props.weightRate.name != 'Pallet Weight Rate' &&
                        <Button variant='danger' onClick={() => props.deleteWeightRate(props.index)}><i className='fas fa-trash'></i></Button>
                    }
                    <InputGroup.Text>Name: </InputGroup.Text>
                    <FormControl
                        name='name'
                        value={props.weightRate.name}
                        onChange={handleChange}
                        disabled={props.weightRate.name === 'Basic Weight Rate' || props.weightRate.name === 'Pallet Weight Rate'}
                    />
                </InputGroup>
            </Col>
            <Col md={10}>
                <Table bordered size='sm'>
                    <thead>
                        <tr>
                            <th><Button variant='success' size='sm' onClick={newWeightBracket}><i className='fas fa-plus'></i></Button></th>
                            <th colSpan={2}>Kilograms</th>
                            <th colSpan={2} style={{backgroundColor: 'lightGrey'}}>Pounds</th>
                            <th style={{backgroundColor: 'darkGrey'}}>Price</th>
                        </tr>
                        <tr>
                            <th></th>
                            <th>Range</th>
                            <th>Price in Increments of <i className='fas fa-question-circle' title={bracketsInfo}></i></th>
                            <th style={{backgroundColor: 'lightGrey'}}>Range</th>
                            <th style={{backgroundColor: 'lightGrey'}}>Price in Increments of <i className='fas fa-question-circle' title={bracketsInfo}></i></th>
                            <th style={{backgroundColor: 'darkGrey'}}></th>
                        </tr>
                    </thead>
                    <tbody>
                        {props.weightRate && props.weightRate.brackets.map((bracket, index, bracketArray) => 
                            <tr key={props.index + '.' + index}>
                                <td><Button variant='danger' size='sm' onClick={() => deleteWeightBracket(index)}><i className='fas fa-trash'></i></Button></td>
                                <td>
                                    <InputGroup size='sm'>
                                        <InputGroup.Text>{(index === 0 ? 0 : bracketArray[index - 1].kgmax) + ' '} <i className='fas fa-arrow-right fa-fw'></i></InputGroup.Text>
                                        <FormControl
                                            type='number'
                                            key={index + '.kgmax'}
                                            min={bracket.kgmax}
                                            name='kgmax'
                                            step={0.1}
                                            value={bracket.kgmax}
                                            data-weightbracketindex={index}
                                            onChange={handleChange}
                                        />
                                        <InputGroup.Text> Kg</InputGroup.Text>
                                    </InputGroup>
                                </td>
                                <td>
                                    <InputGroup size='sm'>
                                        <InputGroup.Text>Per </InputGroup.Text>
                                        <FormControl
                                            type='number'
                                            key={index + '.additionalKgs'}
                                            name='additionalXKgs'
                                            data-weightbracketindex={index}
                                            step={0.01}
                                            value={bracket.additionalXKgs}
                                            onChange={handleChange}
                                        />
                                        <InputGroup.Text> Kgs</InputGroup.Text>
                                    </InputGroup>
                                </td>
                                <td style={{backgroundColor: 'lightGrey'}}>
                                    <InputGroup size='sm'>
                                        <InputGroup.Text>{index === 0 ? 0 : bracketArray[index - 1].lbmax} <i className='fas fa-arrow-right fa-fw'></i></InputGroup.Text>
                                        <FormControl
                                            type='number'
                                            key={index + '.lbmax'}
                                            min={bracket.lbmax}
                                            name='lbmax'
                                            step={0.1}
                                            value={bracket.lbmax}
                                            onChange={handleChange}
                                            data-weightbracketindex={index}
                                        />
                                        <InputGroup.Text> Lb</InputGroup.Text>
                                    </InputGroup>
                                </td>
                                <td style={{backgroundColor: 'lightGrey'}}>
                                    <InputGroup size='sm'>
                                        <InputGroup.Text>Per </InputGroup.Text>
                                        <FormControl
                                            type='number'
                                            name='additionalXLbs'
                                            data-weightbracketindex={index}
                                            step={0.01}
                                            value={bracket.additionalXLbs}
                                            key={index + '.additionalLbs'}
                                            onChange={handleChange}
                                        />
                                        <InputGroup.Text> Lbs</InputGroup.Text>
                                    </InputGroup>
                                </td>
                                <td style={{backgroundColor: 'darkGrey'}}>
                                    <InputGroup size='sm'>
                                        <InputGroup.Text>Base: $</InputGroup.Text>
                                        <FormControl
                                            type='number'
                                            step={0.01}
                                            value={bracket.basePrice}
                                            name='basePrice'
                                            onChange={handleChange}
                                            data-weightbracketindex={index}
                                        />
                                        <InputGroup.Text>Incremental: $</InputGroup.Text>
                                        <FormControl
                                            type='number'
                                            step={0.01}
                                            value={bracket.incrementalPrice}
                                            name='incrementalPrice'
                                            onChange={handleChange}
                                            data-weightbracketindex={index}
                                        />
                                    </InputGroup>
                                </td>
                            </tr>
                        )}
                    </tbody>
                </Table>
            </Col>
            <Col md={12}>
                <hr/>
            </Col>
        </Row>
    )
}
