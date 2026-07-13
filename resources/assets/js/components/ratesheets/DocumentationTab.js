import React from 'react'
import {Accordion, Alert, Badge, Card, Col, Row, Table} from 'react-bootstrap'

/**
 * Reference documentation for ratesheets, shown as its own tab.
 *
 * Deliberately describes behaviour that is not visible from the UI - evaluation order, first-set-wins,
 * why CONTAINS is the right tag operator, the snap-radius clamp - rather than restating the labels on
 * the form. Anything a person can read off the screen is left out; anything that has already caused a
 * mispriced bill is in here.
 */

const Code = ({children}) => (
    <code style={{
        background: 'rgba(0,0,0,0.06)',
        padding: '0.1rem 0.3rem',
        borderRadius: 3,
        whiteSpace: 'nowrap',
    }}>{children}</code>
)

const Block = ({children}) => (
    <pre style={{
        background: 'rgba(0,0,0,0.06)',
        padding: '0.6rem 0.8rem',
        borderRadius: 4,
        overflowX: 'auto',
        marginBottom: '0.75rem',
    }}><code>{children}</code></pre>
)

const Rule = ({name, when, then, note}) => (
    <Card className='mb-2'>
        <Card.Body className='py-2'>
            <div><strong>{name}</strong></div>
            <div className='text-muted' style={{fontSize: '0.9rem'}}>
                <div><Badge bg='secondary'>IF</Badge> {when}</div>
                <div><Badge bg='primary'>THEN</Badge> {then}</div>
                {note && <div className='mt-1 fst-italic'>{note}</div>}
            </div>
        </Card.Body>
    </Card>
)

export default function DocumentationTab() {
    return (
        <Card>
            <Card.Body>
                <Row>
                    <Col md={12}>
                        <h4>Ratesheet Documentation</h4>
                        <p className='text-muted'>
                            How a delivery gets priced and timed, and how the map, tags, groups and rules
                            fit together. If you only read one section, read <em>How a price is built</em>.
                        </p>
                    </Col>
                </Row>

                <Accordion defaultActiveKey='0' alwaysOpen>

                    {/* ---------------------------------------------------------------- */}
                    <Accordion.Item eventKey='0'>
                        <Accordion.Header><strong>1. How a price is built</strong></Accordion.Header>
                        <Accordion.Body>
                            <p>Every quote runs through the same four passes, in this order:</p>
                            <ol>
                                <li>
                                    <strong>Refusals.</strong> Every <Code>Refuse to Price</Code> rule is
                                    checked first, <em>regardless of priority</em>. If any matches, there is
                                    no price at all &mdash; the rule&apos;s message is shown and the bill must
                                    be priced by hand.
                                </li>
                                <li>
                                    <strong>Base price.</strong> The ratesheet&apos;s own flat/distance rate
                                    from the Basic tab.
                                </li>
                                <li>
                                    <strong>Amount rules</strong> (Amount, Field, Equation), in{' '}
                                    <strong>priority order, lowest first</strong>. These add to, replace, or
                                    discount the running total.
                                </li>
                                <li>
                                    <strong>Percent rules</strong>, last, applied to whatever the total came
                                    to. This is what makes &ldquo;10% fuel surcharge&rdquo; mean 10% of
                                    everything else.
                                </li>
                            </ol>
                            <Alert variant='info' className='mb-0'>
                                <strong>Refusals run first on purpose.</strong> A rule that says &ldquo;we
                                cannot carry this&rdquo; is not competing for a slot in the pricing order, so
                                giving it a priority would be meaningless. That is why refusals default to
                                priority 0 and why moving one does nothing.
                            </Alert>
                        </Accordion.Body>
                    </Accordion.Item>

                    {/* ---------------------------------------------------------------- */}
                    <Accordion.Item eventKey='1'>
                        <Accordion.Header><strong>2. Priority, and why the first &ldquo;Set Base Price&rdquo; wins</strong></Accordion.Header>
                        <Accordion.Body>
                            <p>
                                Priority is simply the order rules run in: <strong>lower runs first</strong>.
                                New rules are given the lowest unused number, so a rule you deliberately
                                parked at 100 to mean &ldquo;run last&rdquo; stays last.
                            </p>
                            <p>
                                The important part is what happens when two rules both want to{' '}
                                <Code>Set Base Price</Code>. <strong>The first one to fire wins, and every
                                later one is skipped</strong> &mdash; it is not added on top. So priority on
                                a set of &ldquo;Set Base Price&rdquo; rules is really a <em>ranking</em> of
                                candidates for the same slot:
                            </p>
                            <Block>{`priority 10   If pickup zone is St. Albert   -> Set Base Price = St. Albert's rate
priority 20   If delivery zone is St. Albert -> Set Base Price = St. Albert's rate
priority 50   If they share a zone group     -> Set Base Price = zone crossing cost`}</Block>
                            <p className='mb-0'>
                                A St. Albert &rarr; Edmonton trip matches rule 10 and stops looking for a
                                base. An Edmonton &rarr; Edmonton trip falls through to rule 50. Surcharges
                                (<Code>Add Charge</Code>) still run afterwards either way &mdash; only the{' '}
                                <em>base</em> is exclusive.
                            </p>
                        </Accordion.Body>
                    </Accordion.Item>

                    {/* ---------------------------------------------------------------- */}
                    <Accordion.Item eventKey='2'>
                        <Accordion.Header><strong>3. Anatomy of a rule</strong></Accordion.Header>
                        <Accordion.Body>
                            <p><strong>Action</strong> &mdash; what the rule does to the price:</p>
                            <Table size='sm' bordered responsive>
                                <tbody>
                                    <tr>
                                        <td style={{width: '30%'}}><Code>Add Charge</Code></td>
                                        <td>Adds a line item. The workhorse: surcharges, weight, after hours.</td>
                                    </tr>
                                    <tr>
                                        <td><Code>Set Base Price</Code></td>
                                        <td>Replaces the base price. First one to fire wins (see above).</td>
                                    </tr>
                                    <tr>
                                        <td><Code>Discount</Code></td>
                                        <td>Subtracts from the running total, never below $0.</td>
                                    </tr>
                                    <tr>
                                        <td><Code>Refuse to Price</Code></td>
                                        <td>No price at all. Shows your message. Runs before everything.</td>
                                    </tr>
                                </tbody>
                            </Table>

                            <p><strong>Value type</strong> &mdash; where the number comes from:</p>
                            <Table size='sm' bordered responsive>
                                <tbody>
                                    <tr>
                                        <td style={{width: '30%'}}><Code>Amount</Code></td>
                                        <td>A fixed dollar figure you type in.</td>
                                    </tr>
                                    <tr>
                                        <td><Code>Percent</Code></td>
                                        <td>A percentage of the total. Always applied last.</td>
                                    </tr>
                                    <tr>
                                        <td><Code>Field</Code></td>
                                        <td>
                                            A figure looked up from the delivery itself &mdash; a zone&apos;s
                                            rate for the selected delivery type, or the shared group&apos;s
                                            zone-crossing cost. Use this instead of retyping numbers that
                                            already live on the map.
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><Code>Equation</Code></td>
                                        <td>A formula. See the Equations section.</td>
                                    </tr>
                                    <tr>
                                        <td><Code>Message</Code></td>
                                        <td>Only for Refuse to Price: the text staff and customers are shown.</td>
                                    </tr>
                                </tbody>
                            </Table>

                            <p className='mb-0'>
                                <strong>Continue on match</strong> &mdash; leave this on. Turning it off stops
                                <em> all</em> further amount rules the moment this one fires, which will
                                silently drop surcharges that were supposed to stack.
                            </p>
                        </Accordion.Body>
                    </Accordion.Item>

                    {/* ---------------------------------------------------------------- */}
                    <Accordion.Item eventKey='3'>
                        <Accordion.Header><strong>4. Worked examples</strong></Accordion.Header>
                        <Accordion.Body>
                            <Rule
                                name='Outlying Zone Surcharge - Delivery'
                                when={<>Delivery Zone Tags <strong>CONTAINS</strong> <Code>outlying</Code></>}
                                then={<>Add Charge &rarr; Field: <Code>Delivery Zone Cost (Selected Type)</Code></>}
                                note='The dollar figure lives on the zone, per delivery type. Change the zone, and every rule that reads it follows.'
                            />
                            <Rule
                                name='{{zones_crossed}} zones crossed'
                                when={<>Pickup and Delivery Share a Zone Group is <Code>true</Code></>}
                                then={<>Set Base Price &rarr; Field: <Code>Zone Crossing Cost (Selected Type)</Code></>}
                                note='Reads the shared group&rsquo;s crossing table. The name interpolates, so the invoice reads "4 zones crossed".'
                            />
                            <Rule
                                name='Basic Weight 100-999 lb'
                                when={<>Is Pallet is <Code>false</Code> AND Total Weight (lbs) is between 100 and 999</>}
                                then={<>Add Charge &rarr; Equation: <Code>floor(max(0, total_weight_lbs - 100) / 100) * 2 + 16</Code></>}
                                note='$16 at 100 lb, plus $2 for every whole 100 lb beyond it.'
                            />
                            <Rule
                                name='Too heavy to carry'
                                when={<>Total Weight (lbs) &ge; 10000</>}
                                then={<>Refuse to Price &rarr; Message: <em>&ldquo;We can&rsquo;t handle this weight &mdash; please call us.&rdquo;</em></>}
                                note='Blocks a customer from submitting. Staff can still price by hand.'
                            />
                        </Accordion.Body>
                    </Accordion.Item>

                    {/* ---------------------------------------------------------------- */}
                    <Accordion.Item eventKey='4'>
                        <Accordion.Header><strong>5. Tags</strong></Accordion.Header>
                        <Accordion.Body>
                            <p>
                                A tag is a free-text label on a zone &mdash; <Code>internal</Code>,{' '}
                                <Code>outlying</Code>, <Code>peripheral</Code>, or anything you invent. A zone
                                can carry several. Tags replaced the old fixed <em>zone type</em>, which only
                                allowed one value from a list nobody could extend.
                            </p>
                            <Alert variant='warning'>
                                <strong>Always match tags with CONTAINS.</strong> A zone has a <em>list</em> of
                                tags, so <Code>CONTAINS</Code> is the operator that asks &ldquo;is this tag
                                among them?&rdquo; Using <Code>=</Code> asks whether the zone&apos;s tags are{' '}
                                <em>exactly and only</em> that one tag, which quietly stops matching the moment
                                anyone adds a second tag to the zone.
                            </Alert>
                            <p className='mb-0'>
                                Tags are the right tool for <em>&ldquo;what kind of place is this?&rdquo;</em>{' '}
                                (charge more to reach it). Groups are the right tool for{' '}
                                <em>&ldquo;how do these zones relate to each other?&rdquo;</em> (count the
                                crossing between them).
                            </p>
                        </Accordion.Body>
                    </Accordion.Item>

                    {/* ---------------------------------------------------------------- */}
                    <Accordion.Item eventKey='5'>
                        <Accordion.Header><strong>6. Zone groups &amp; zone crossing</strong></Accordion.Header>
                        <Accordion.Body>
                            <p>
                                A <strong>zone group</strong> is a set of zones that can be crossed, plus the
                                table of what crossing them costs. The table is per delivery type:
                            </p>
                            <Block>{`Zones crossed    Regular        Rush          Direct
     1           $6.80         $10.30        $15.50
     2           $8.00         $12.40        $18.60
     3           $9.10         $14.30        $20.90`}</Block>
                            <p>
                                &ldquo;Zones crossed&rdquo; is counted by walking neighbouring zones from
                                pickup to delivery, <strong>staying inside that group</strong>. A zone is 1
                                zone from itself. A zone may belong to several groups, and a walk through
                                group A will never route through a zone that only belongs to group B.
                            </p>
                            <p>
                                <strong>A group existing does not charge anybody.</strong> It only makes the
                                numbers available. A conditional decides when crossing applies:
                            </p>
                            <Block>{`IF   Pickup and Delivery Share a Zone Group = true
THEN Set Base Price = Field: Zone Crossing Cost (Selected Type)`}</Block>
                            <p>
                                If the two zones happen to share <em>more than one</em> group, the{' '}
                                <strong>cheapest</strong> crossing is used (and for timing, the fastest).
                            </p>
                            <p className='mb-0'>
                                Groups also own a <strong>colour</strong>, and a group&apos;s colour beats the
                                zone&apos;s own colour on the map &mdash; so a group is visible at a glance.
                            </p>
                        </Accordion.Body>
                    </Accordion.Item>

                    {/* ---------------------------------------------------------------- */}
                    <Accordion.Item eventKey='6'>
                        <Accordion.Header><strong>7. Equations</strong></Accordion.Header>
                        <Accordion.Body>
                            <p><strong>Variables.</strong> The unit is part of the name &mdash; there are no unit suffixes:</p>
                            <Table size='sm' bordered responsive>
                                <tbody>
                                    <tr><td style={{width: '35%'}}><Code>total_weight_kgs</Code></td><td>Total weight in <strong>kilograms</strong></td></tr>
                                    <tr><td><Code>total_weight_lbs</Code></td><td>The same weight in <strong>pounds</strong></td></tr>
                                    <tr><td><Code>distance_km</Code></td><td>Road distance of the trip (see below)</td></tr>
                                    <tr><td><Code>drive_time_minutes</Code></td><td>Driving time for that route</td></tr>
                                </tbody>
                            </Table>
                            <p>
                                Write <Code>total_weight_lbs - 100</Code>, never{' '}
                                <Code>total_weight_kgs - 100 lbs</Code>. Pick the variable whose unit you want
                                to think in; the conversion is exact. A unit written as a{' '}
                                <em>suffix</em> is read as part of the variable name and the rule will refuse
                                to price.
                            </p>
                            <p><strong>Functions.</strong></p>
                            <Block>{`max  min  round  floor  ceil  abs  sqrt  pow`}</Block>
                            <Alert variant='warning' className='mb-0'>
                                <strong>Two traps worth knowing.</strong> Charging &ldquo;per 100 lb&rdquo;
                                needs <Code>floor(...)</Code>, or a 150 lb parcel is billed for half a
                                bracket. And subtraction can go negative: at 99.5 lb,{' '}
                                <Code>floor((99.5 - 100) / 100)</Code> is <strong>&minus;1</strong>, which
                                <em> refunds</em> money. Wrap it: <Code>floor(max(0, total_weight_lbs - 100) / 100)</Code>.
                            </Alert>
                        </Accordion.Body>
                    </Accordion.Item>

                    {/* ---------------------------------------------------------------- */}
                    <Accordion.Item eventKey='7'>
                        <Accordion.Header><strong>8. Naming line items</strong></Accordion.Header>
                        <Accordion.Body>
                            <p>
                                A line item is named after the rule that produced it, which describes the rule
                                rather than the delivery. Put <Code>{'{{...}}'}</Code> placeholders in the
                                name and they are filled in from the trip:
                            </p>
                            <Block>{`{{zones_crossed}} zones crossed ({{crossing_group}})
   ->  "4 zones crossed (City of Edmonton)"`}</Block>
                            <p>
                                The Name field offers the usual ones as click-to-insert chips. Any context
                                value works, including <Code>{'{{delivery_address.zone.name}}'}</Code> and{' '}
                                <Code>{'{{package.total_weight_lbs}}'}</Code>. Refusal messages interpolate
                                too.
                            </p>
                            <p className='mb-0'>
                                A placeholder that has no value on a given trip renders as{' '}
                                <strong>nothing</strong> rather than printing braces at a customer, so the
                                label just gets shorter. Naming never changes a price.
                            </p>
                        </Accordion.Body>
                    </Accordion.Item>

                    {/* ---------------------------------------------------------------- */}
                    <Accordion.Item eventKey='8'>
                        <Accordion.Header><strong>9. Timing conditionals</strong></Accordion.Header>
                        <Accordion.Body>
                            <p>
                                Timing works exactly like pricing, in hours instead of dollars. The delivery
                                type&apos;s own time is the starting point, then rules run in priority order:
                            </p>
                            <Table size='sm' bordered responsive>
                                <tbody>
                                    <tr><td style={{width: '30%'}}><Code>Set Base Time</Code></td><td>Replaces the running time. <strong>First one to fire wins</strong>, same as pricing.</td></tr>
                                    <tr><td><Code>Add Time</Code></td><td>Adds hours to the running time.</td></tr>
                                </tbody>
                            </Table>
                            <p className='mb-0'>
                                Values are either <Code>Hours</Code> you type, or a <Code>Field</Code> &mdash; a
                                zone&apos;s time modifier, or the shared group&apos;s{' '}
                                <Code>Zone Crossing Time</Code>. On a bill, hover the delivery type to see the
                                full breakdown of which rules fired and what each did to the clock.
                            </p>
                        </Accordion.Body>
                    </Accordion.Item>

                    {/* ---------------------------------------------------------------- */}
                    <Accordion.Item eventKey='9'>
                        <Accordion.Header><strong>10. The map: drawing, snapping &amp; cleanup</strong></Accordion.Header>
                        <Accordion.Body>
                            <p>
                                Zones are polygons. Two zones are <strong>neighbours</strong> when they
                                actually touch &mdash; adjacency is read off the geometry, not typed in. That
                                is what zone crossing counts, so <strong>gaps between zones break
                                crossing</strong>: if two polygons do not truly share their border, no route
                                exists between them and a crossing rule will not fire.
                            </p>

                            <h6 className='mt-3'>Snapping</h6>
                            <p>
                                Snapping is what makes borders truly shared instead of nearly shared. While
                                you draw or edit, a vertex dropped near an existing zone&apos;s vertex is
                                pulled exactly onto it.
                            </p>
                            <p>
                                <strong>Snap tolerance</strong> (in <em>Map Tools</em>) is a distance{' '}
                                <em>on the ground</em>, in metres &mdash; default <Code>2</Code>. Because it
                                is a real-world distance, it is converted to a pixel radius at your current
                                zoom, and then clamped between <strong>6 and 20 pixels</strong>. That clamp is
                                deliberate: at the zoom the map opens at, 2&nbsp;m is a fraction of a pixel and
                                no cursor could ever land inside it, while zoomed right in it would let you
                                grab a vertex halfway across the screen.
                            </p>
                            <p className='text-muted' style={{fontSize: '0.9rem'}}>
                                Practical effect: raise the tolerance if vertices are not catching; lower it if
                                they are catching the wrong ones. The same value also drives Simplify.
                            </p>

                            <h6 className='mt-3'>Per-zone tools</h6>
                            <Table size='sm' bordered responsive>
                                <tbody>
                                    <tr>
                                        <td style={{width: '25%'}}><Code>Match</Code></td>
                                        <td>
                                            Pulls this zone&apos;s border onto its neighbours&apos;, closing
                                            gaps. <strong>This is the one tool that edits other zones too</strong> —
                                            they will be marked unsaved along with this one. That is expected.
                                        </td>
                                    </tr>
                                    <tr><td><Code>Smooth</Code></td><td>Rounds off jagged edges on this zone only.</td></tr>
                                    <tr><td><Code>Remove Duplicates</Code></td><td>Strips stacked/repeated vertices.</td></tr>
                                    <tr><td><Code>Simplify</Code></td><td>Thins out vertices across the map. <em>Topo</em> mode preserves shared borders; <em>Quick</em> is faster but can tear them.</td></tr>
                                </tbody>
                            </Table>

                            <h6 className='mt-3'>Saving</h6>
                            <p className='mb-0'>
                                Zones and groups save <strong>individually</strong>, not as one big map save.
                                Anything you have changed is marked unsaved, and you will be warned before
                                leaving the page with outstanding edits.
                            </p>
                        </Accordion.Body>
                    </Accordion.Item>

                    {/* ---------------------------------------------------------------- */}
                    <Accordion.Item eventKey='11'>
                        <Accordion.Header><strong>11. Distance travelled</strong></Accordion.Header>
                        <Accordion.Body>
                            <p>
                                <Code>Distance Travelled (km)</Code> is the <strong>road distance</strong>{' '}
                                between pickup and delivery, along the route Google recommends for driving.
                                It is not straight-line distance, and it has nothing to do with zones
                                crossed &mdash; it works even when an address falls outside every zone.
                            </p>
                            <p>
                                <Code>Drive Time (minutes)</Code> comes from the same lookup, for free.
                            </p>
                            <p>Both are available as a condition, in an equation, and in a rule&apos;s name:</p>
                            <Rule
                                name='Fuel Surcharge ({{distance_km}} km)'
                                when={<>Distance Travelled (km) is <Code>&gt; 20</Code></>}
                                then={<>Add Charge &rarr; Equation: <Code>distance_km * 0.15</Code></>}
                            />
                            <Alert variant='warning' className='mb-0'>
                                <strong>If the distance cannot be worked out, the trip is not priced.</strong>{' '}
                                Google being unreachable, or two points with no road between them, leaves the
                                distance empty &mdash; and any rule that uses it then refuses to price, exactly
                                like an address outside every zone. It does <em>not</em> fall back to a
                                straight-line guess, and it does <em>not</em> quietly treat the trip as 0&nbsp;km:
                                a $0.15/km surcharge on a silent 0&nbsp;km is a surcharge that disappears
                                without anyone noticing. Sheets that never mention distance are unaffected.
                            </Alert>
                        </Accordion.Body>
                    </Accordion.Item>

                    {/* ---------------------------------------------------------------- */}
                    <Accordion.Item eventKey='10'>
                        <Accordion.Header><strong>12. When a rule doesn&apos;t fire</strong></Accordion.Header>
                        <Accordion.Body>
                            <p>Worth checking, roughly in the order these actually go wrong:</p>
                            <ul className='mb-0'>
                                <li>
                                    <strong>Tag rule using <Code>=</Code> instead of <Code>CONTAINS</Code>.</strong>{' '}
                                    The most common cause of a surcharge silently not applying.
                                </li>
                                <li>
                                    <strong>An earlier <Code>Set Base Price</Code> already won.</strong> Check
                                    the priorities &mdash; a lower-numbered rule may be claiming the base.
                                </li>
                                <li>
                                    <strong>The zones don&apos;t actually touch.</strong> A crossing rule needs
                                    a route through the group. Run <Code>Match</Code> and confirm the borders
                                    are shared.
                                </li>
                                <li>
                                    <strong>The zones aren&apos;t in the same group</strong>, so{' '}
                                    <Code>Share a Zone Group</Code> is false.
                                </li>
                                <li>
                                    <strong>The group&apos;s rate table has no row</strong> for that many zones
                                    crossed, or no figure for that delivery type. The rule will refuse to price
                                    rather than quietly charge $0.
                                </li>
                                <li>
                                    <strong><Code>Continue on match</Code> is off</strong> on an earlier rule,
                                    which stopped the run.
                                </li>
                            </ul>
                        </Accordion.Body>
                    </Accordion.Item>

                </Accordion>
            </Card.Body>
        </Card>
    )
}
