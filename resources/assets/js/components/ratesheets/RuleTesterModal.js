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

const AddressPicker = ({label, address, onPick, time, onTimeChange, isMall, onMallChange}) => {
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
        <div className='mb-3'>
            <InputGroup className='mb-1'>
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
            <InputGroup size='sm'>
                {/* Time drives after-hours / weekend / day-of-week rules. It defaults to now, so a
                    rule that only fires at certain times shows nothing until the time is set to match. */}
                <InputGroup.Text style={{width: 90}}>{label} time</InputGroup.Text>
                <FormControl
                    type='datetime-local'
                    value={time}
                    onChange={event => onTimeChange(event.target.value)}
                />
                <InputGroup.Text>
                    <FormCheck
                        label='Mall'
                        checked={isMall}
                        onChange={() => onMallChange(!isMall)}
                    />
                </InputGroup.Text>
            </InputGroup>
        </div>
    )
}

/** datetime-local wants "YYYY-MM-DDTHH:mm" in LOCAL time - toISOString would shift it to UTC. */
const nowForInput = () => {
    const now = new Date()
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset())
    return now.toISOString().slice(0, 16)
}

export default function RuleTesterModal(props) {
    const api = useAPI()

    const [pickup, setPickup] = useState(null)
    const [delivery, setDelivery] = useState(null)
    const [pickupTime, setPickupTime] = useState(nowForInput)
    const [deliveryTime, setDeliveryTime] = useState(nowForInput)
    const [pickupIsMall, setPickupIsMall] = useState(false)
    const [deliveryIsMall, setDeliveryIsMall] = useState(false)
    const [weight, setWeight] = useState('10')
    const [length, setLength] = useState('10')
    const [width, setWidth] = useState('10')
    const [height, setHeight] = useState('10')
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
            pickup_address: {lat: pickup.lat, lng: pickup.lng, is_mall: pickupIsMall},
            delivery_address: {lat: delivery.lat, lng: delivery.lng, is_mall: deliveryIsMall},
            // Dimensions go in the ENTERED unit (unlike weight, which is pre-converted to kg above):
            // use_imperial tells the engine whether these are inches or centimetres, and it converts.
            packages: isMinimum ? [] : [{
                count: 1,
                totalWeight: kilograms,
                length: Number(length) || 0,
                width: Number(width) || 0,
                height: Number(height) || 0,
            }],
            package_is_minimum: isMinimum,
            package_is_pallet: isPallet,
            use_imperial: useImperial,
            // datetime-local yields "YYYY-MM-DDTHH:mm", which PHP's DateTime parses directly.
            time_pickup_scheduled: pickupTime,
            time_delivery_scheduled: deliveryTime,
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
                        <AddressPicker
                            label='Pickup'
                            address={pickup} onPick={setPickup}
                            time={pickupTime} onTimeChange={setPickupTime}
                            isMall={pickupIsMall} onMallChange={setPickupIsMall}
                        />
                        <AddressPicker
                            label='Delivery'
                            address={delivery} onPick={setDelivery}
                            time={deliveryTime} onTimeChange={setDeliveryTime}
                            isMall={deliveryIsMall} onMallChange={setDeliveryIsMall}
                        />
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
                        {/* Dimensions drive the longest-side and cubed-weight rules. Their unit follows
                            the same kg/lbs toggle: metric is cm, imperial is inches. */}
                        <InputGroup className='mb-2' size='sm'>
                            <InputGroup.Text>{useImperial ? 'L×W×H (in)' : 'L×W×H (cm)'}</InputGroup.Text>
                            <FormControl type='number' value={length} disabled={isMinimum} onChange={event => setLength(event.target.value)}/>
                            <FormControl type='number' value={width} disabled={isMinimum} onChange={event => setWidth(event.target.value)}/>
                            <FormControl type='number' value={height} disabled={isMinimum} onChange={event => setHeight(event.target.value)}/>
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
                                        {/* The ratesheet's delivery types carry friendlyName, not name -
                                            the quote is the raw type object with the price merged onto it. */}
                                        <td><strong>{quote.friendlyName ?? quote.name ?? quote.id}</strong></td>
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
