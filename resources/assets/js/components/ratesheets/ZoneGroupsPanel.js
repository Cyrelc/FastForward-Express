import React, {useState} from 'react'
import {Badge, Button, Card, Collapse, InputGroup} from 'react-bootstrap'
import Select from 'react-select'
import {menuPortalProps} from './selectMenuPortal'

/**
 * The map's zone-group manager: the full list of groups on this ratesheet, with create/edit, and the
 * group that newly drawn zones join.
 *
 * Deliberately compact - it lives in the map's control column, so it collapses to a header and caps
 * its own height rather than pushing the map controls down.
 */
export default function ZoneGroupsPanel(props) {
    const {
        defaultZoneGroupId,
        dirtyZoneGroupIds = [],
        mapZones = [],
        setDefaultZoneGroupId,
        zoneGroups = [],
    } = props

    const [open, setOpen] = useState(true)

    const zoneCount = (zoneGroupId) =>
        mapZones.filter(zone => (zone.groupIds ?? []).includes(zoneGroupId)).length

    const groupOptions = zoneGroups.map(zoneGroup => ({value: zoneGroup.zoneGroupId, label: zoneGroup.name}))

    return (
        <Card style={{marginTop: '8px'}}>
            <Card.Header
                style={{padding: '6px 10px', cursor: 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'space-between'}}
                onClick={() => setOpen(!open)}
            >
                <span>
                    <i className={`fas fa-chevron-${open ? 'down' : 'right'}`} style={{marginRight: '6px'}} />
                    <strong>Zone Groups</strong>{' '}
                    <Badge bg='secondary'>{zoneGroups.length}</Badge>
                </span>
                <Button
                    size='sm'
                    variant='outline-primary'
                    onClick={event => {
                        event.stopPropagation()
                        setOpen(true)
                        props.createZoneGroup()
                    }}
                >
                    <i className='fas fa-plus' /> New
                </Button>
            </Card.Header>
            <Collapse in={open}>
                <div>
                    <Card.Body style={{padding: '8px'}}>
                        {zoneGroups.length === 0 &&
                            <small className='text-muted'>
                                No groups yet. Zones in the same group can have their crossing counted
                                between them &mdash; a pricing or timing conditional decides whether that
                                count is used.
                            </small>
                        }

                        {zoneGroups.length > 0 &&
                            <div style={{maxHeight: '160px', overflowY: 'auto'}}>
                                {zoneGroups.map(zoneGroup =>
                                    <div
                                        key={zoneGroup.zoneGroupId}
                                        style={{display: 'flex', alignItems: 'center', gap: '6px', padding: '3px 0'}}
                                    >
                                        <span
                                            title={zoneGroup.colour}
                                            style={{
                                                backgroundColor: zoneGroup.colour ?? '#8491c9',
                                                border: '1px solid rgba(0,0,0,.2)',
                                                borderRadius: '3px',
                                                display: 'inline-block',
                                                flex: '0 0 auto',
                                                height: '14px',
                                                width: '14px',
                                            }}
                                        />
                                        <span style={{flex: 1, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap'}}>
                                            {zoneGroup.name}
                                            {dirtyZoneGroupIds.includes(zoneGroup.zoneGroupId) &&
                                                <span className='text-warning' title='Unsaved changes'> &bull;</span>
                                            }
                                        </span>
                                        <small className='text-muted' style={{flex: '0 0 auto'}}>
                                            {zoneCount(zoneGroup.zoneGroupId)}
                                        </small>
                                        <Button
                                            size='sm'
                                            variant='link'
                                            style={{padding: '0 4px'}}
                                            title={`Edit ${zoneGroup.name} and its crossing rates`}
                                            onClick={() => props.editZoneGroup(zoneGroup.zoneGroupId)}
                                        >
                                            <i className='fas fa-pen' />
                                        </Button>
                                    </div>
                                )}
                            </div>
                        }
                    </Card.Body>
                    {zoneGroups.length > 0 &&
                        <Card.Footer style={{padding: '6px 8px'}}>
                            <InputGroup size='sm'>
                                <InputGroup.Text>New zones join</InputGroup.Text>
                                <div style={{flex: 1}}>
                                    <Select
                                        options={groupOptions}
                                        value={groupOptions.find(option => option.value === defaultZoneGroupId) ?? null}
                                        onChange={option => setDefaultZoneGroupId(option?.value ?? null)}
                                        isClearable
                                        placeholder='No group'
                                        menuPlacement='auto'
                                        {...menuPortalProps}
                                    />
                                </div>
                            </InputGroup>
                        </Card.Footer>
                    }
                </div>
            </Collapse>
        </Card>
    )
}
