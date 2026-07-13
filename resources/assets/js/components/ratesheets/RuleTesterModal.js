import React, {Fragment, useState} from 'react'
import {Badge, Button, Col, FormCheck, FormControl, InputGroup, Modal, Row, Spinner, Table} from 'react-bootstrap'
import {Autocomplete} from '@react-google-maps/api'
import {toast} from 'react-toastify'

import {useAPI} from '../../contexts/APIContext'

/**
 * Prices a made-up delivery against the ratesheet being edited, and shows why it came out that way.
 *
 * This is deliberately NOT a second bills page. It hits the same endpoint a real quote does
 * (/bills/estimateDeliveryTimes), and recreates none of the rest of a bill - no accounts, charges,
 * invoices, drivers, manifests or dispatch. What it adds over "make a bill and don't press save" is
 * narrow but real: it prices the sheet you are looking at rather than the one an account happens to
 * point at, it cannot create a bill by accident, and it shows every delivery type at once.
 *
 * The breakdown is the point. A line item tells you what was charged; it cannot tell you that the rule
 * you just wrote did not fire, or that it fired and was skipped because something else already set the
 * base. Those are the only two questions anyone actually has.
 */

const ROW_STYLES = {
    base: {label: 'Base', bg: 'secondary'},
    add: {label: 'Added', bg: 'success'},
    set: {label: 'Set base', bg: 'primary'},
    discount: {label: 'Discount', bg: 'info'},
    percent: {label: 'Percent', bg: 'success'},
    skipped: {label: 'Skipped', bg: 'warning'},
    not_matched: {label: 'Did not fire', bg: 'light'},
}

const BreakdownTable = ({rows, unit}) => {
    if(!rows?.length)
        return <p className='text-muted mb-0'>No rules on this ratesheet.</p>

    return (
        <Table size='sm' className='mb-0'>
            <tbody>
                {rows.map((row, index) => {
                    const style = ROW_STYLES[row.action] ?? {label: row.action, bg: 'light'}
                    const didNothing = row.action === 'not_matched' || row.action === 'skipped'

                    return (
                        <tr key={index} style={{opacity: didNothing ? 0.65 : 1}}>
                            <td style={{width: 110}}>
                                <Badge bg={style.bg} text={style.bg === 'light' || style.bg === 'warning' ? 'dark' : undefined}>
                                    {style.label}
                                </Badge>
                            </td>
                            <td>
                                {row.name}
                                {row.note &&
                                    <div className='text-muted' style={{fontSize: '0.75rem'}}>{row.note}</div>
                                }
                            </td>
                            <td className='text-end' style={{width: 90}}>
                                {row.value === null ? '—' : `${unit === '$' ? '$' : ''}${Number(row.value).toFixed(2)}${unit === 'h' ? ' h' : ''}`}
                            </td>
                            <td className='text-end text-muted' style={{width: 90}}>
                                {row.running_total === null ? '' : `${unit === '$' ? '$' : ''}${Number(row.running_total).toFixed(2)}${unit === 'h' ? ' h' : ''}`}
                            </td>
                        </tr>
                    )
                })}
            </tbody>
        </Table>
    )
}

const AddressPicker = ({label, address, onPick}) => {
    const [autocomplete, setAutocomplete] = useState(null)

    const pick = () => {
        const place = autocomplete?.getPlace()
        if(!place?.geometry)
            return

        onPick({
            lat: place.geometry.location.lat(),
            lng: place.geometry.location.lng(),
            formatted: place.formatted_address ?? place.name,
        })
    }

    return (
        <InputGroup className='mb-2'>
            <InputGroup.Text style={{width: 90}}>{label}</InputGroup.Text>
            <Autocomplete
                className='form-control autocomplete-wrapper'
                fields={['geometry', 'name', 'formatted_address']}
                onLoad={setAutocomplete}
                onPlaceChanged={pick}
            >
                <FormControl type='text' placeholder='Search an address...' defaultValue={address?.formatted ?? ''}/>
            </Autocomplete>
        </InputGroup>
    )
}

export default function RuleTesterModal(props) {
    const api = useAPI()

    const [pickup, setPickup] = useState(null)
    const [delivery, setDelivery] = useState(null)
    const [weight, setWeight] = useState('10')
    const [useImperial, setUseImperial] = useState(false)
    const [isPallet, setIsPallet] = useState(false)
    const [isMinimum, setIsMinimum] = useState(false)
    const [quotes, setQuotes] = useState(null)
    const [expanded, setExpanded] = useState(null)
    const [isTesting, setIsTesting] = useState(false)

    const runTest = () => {
        if(!pickup || !delivery) {
            toast.error('Pick a pickup and a delivery address first')
            return
        }

        setIsTesting(true)
        setQuotes(null)

        // The same payload a real quote sends - including the unit convention, which matters: the bill
        // page converts pounds to kilograms in the browser (usePackages), so totalWeight is ALWAYS
        // metric and the engine never converts. Sending raw pounds here would test something the real
        // client never does, and would quietly price this trip at 45% of the weight.
        const kilograms = useImperial
            ? Math.round((Number(weight) || 0) * 0.45359237 * 100) / 100
            : (Number(weight) || 0)

        api.post('/bills/estimateDeliveryTimes', {
            ratesheet_id: props.ratesheetId,
            pickup_address: {lat: pickup.lat, lng: pickup.lng, is_mall: false},
            delivery_address: {lat: delivery.lat, lng: delivery.lng, is_mall: false},
            packages: isMinimum ? [] : [{count: 1, totalWeight: kilograms, length: 10, width: 10, height: 10}],
            package_is_minimum: isMinimum,
            package_is_pallet: isPallet,
            use_imperial: useImperial,
            time_pickup_scheduled: new Date().toLocaleString('en-US'),
            time_delivery_scheduled: new Date().toLocaleString('en-US'),
        }).then(response => {
            setQuotes(response ?? [])
            setExpanded(null)
        }).catch(() => {
            // APIService already surfaces the server's message, which for a refusal is the author's own
            // wording - exactly what we want him to see.
            setQuotes([])
        }).finally(() => setIsTesting(false))
    }

    return (
        <Modal show={props.show} onHide={props.onHide} size='xl'>
            <Modal.Header closeButton>
                <Modal.Title>Test a Delivery</Modal.Title>
            </Modal.Header>
            <Modal.Body>
                <p className='text-muted'>
                    Prices a pretend delivery against <strong>this ratesheet</strong>. Nothing is saved and
                    no bill is created. Expand a delivery type to see which rules fired, which were skipped,
                    and which did not fire at all.
                </p>

                <Row>
                    <Col md={7}>
                        <AddressPicker label='Pickup' address={pickup} onPick={setPickup}/>
                        <AddressPicker label='Delivery' address={delivery} onPick={setDelivery}/>
                    </Col>
                    <Col md={5}>
                        <InputGroup className='mb-2'>
                            <InputGroup.Text>Weight</InputGroup.Text>
                            <FormControl
                                type='number'
                                value={weight}
                                disabled={isMinimum}
                                onChange={event => setWeight(event.target.value)}
                            />
                            <Button
                                variant={useImperial ? 'secondary' : 'outline-secondary'}
                                onClick={() => setUseImperial(!useImperial)}
                            >
                                {useImperial ? 'lbs' : 'kg'}
                            </Button>
                        </InputGroup>
                        <FormCheck
                            inline
                            label='Pallet'
                            checked={isPallet}
                            onChange={() => setIsPallet(!isPallet)}
                        />
                        <FormCheck
                            inline
                            label='Minimum (no weight charge)'
                            checked={isMinimum}
                            onChange={() => setIsMinimum(!isMinimum)}
                        />
                    </Col>
                </Row>

                <Button variant='primary' onClick={runTest} disabled={isTesting} className='mt-2'>
                    {isTesting ? <Fragment><Spinner animation='border' size='sm'/> Pricing...</Fragment> : 'Price it'}
                </Button>

                {quotes && quotes.length === 0 &&
                    <p className='text-danger mt-3 mb-0'>
                        This delivery could not be priced. The message above says why.
                    </p>
                }

                {quotes && quotes.length > 0 &&
                    <Table className='mt-3' bordered>
                        <thead>
                            <tr>
                                <th>Delivery Type</th>
                                <th className='text-end'>Price</th>
                                <th className='text-end'>Time</th>
                                <th style={{width: 120}}></th>
                            </tr>
                        </thead>
                        <tbody>
                            {quotes.map(quote => (
                                <React.Fragment key={quote.id}>
                                    <tr>
                                        <td><strong>{quote.name}</strong></td>
                                        <td className='text-end'>
                                            {quote.price === null
                                                ? <span className='text-danger'>{quote.price_unavailable_reason ?? 'Cannot price'}</span>
                                                : `$${Number(quote.price).toFixed(2)}`
                                            }
                                        </td>
                                        <td className='text-end'>{quote.time ?? '—'} h</td>
                                        <td>
                                            <Button
                                                size='sm'
                                                variant='link'
                                                onClick={() => setExpanded(expanded === quote.id ? null : quote.id)}
                                            >
                                                {expanded === quote.id ? 'Hide why' : 'Why?'}
                                            </Button>
                                        </td>
                                    </tr>
                                    {expanded === quote.id &&
                                        <tr>
                                            <td colSpan={4} style={{background: 'rgba(0,0,0,0.02)'}}>
                                                <h6 className='text-muted'>Price</h6>
                                                <BreakdownTable rows={quote.price_breakdown} unit='$'/>
                                                <h6 className='text-muted mt-3'>Time</h6>
                                                <BreakdownTable rows={quote.time_breakdown} unit='h'/>
                                            </td>
                                        </tr>
                                    }
                                </React.Fragment>
                            ))}
                        </tbody>
                    </Table>
                }
            </Modal.Body>
            <Modal.Footer>
                <Button variant='secondary' onClick={props.onHide}>Close</Button>
            </Modal.Footer>
        </Modal>
    )
}
