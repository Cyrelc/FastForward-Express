import React, {useCallback, useEffect, useReducer, useState} from 'react'
import {Badge, Button, ButtonGroup, Col, Dropdown, FormCheck, Modal, Navbar, NavDropdown, OverlayTrigger, Row, Tab, Tabs, Tooltip} from 'react-bootstrap'
import {LinkContainer} from 'react-router-bootstrap'
import {connect} from 'react-redux'

import BillReducer, {initialState as initialBillState} from './reducers/billReducer'
import ChargeReducer, {initialState as initialChargeState} from './reducers/chargeReducer'
import PackageReducer, {initialState as initialPackageState} from './reducers/packageReducer'
import ActivityLogTab from '../partials/ActivityLogTab'
import BasicTab from './BasicTab'
import BillingTab from './BillingTab'
import DispatchTab from './DispatchTab'

const Bill = (props) => {
    const [billState, billDispatch] = useReducer(BillReducer, initialBillState)
    const [chargeState, chargeDispatch] = useReducer(ChargeReducer, initialChargeState)
    const [packageState, packageDispatch] = useReducer(PackageReducer, initialPackageState)

    const [viewTermsAndConditions, setViewTermsAndConditions] = useState(false)
    const [awaitingCharges, setAwaitingCharges] = useState(false)

    const {accounts, billId, deliveryType, isTemplate, nextBillId, permissions, prevBillId, readOnly} = billState
    const {account: deliveryAccount, addressLat: deliveryAddressLat, addressLng: deliveryAddressLng, timeScheduled: deliveryTimeScheduled} = billState.delivery
    const {account: pickupAccount, addressLat: pickupAddressLat, addressLng: pickupAddressLng, timeScheduled: pickupTimeScheduled} = billState.pickup
    const {account: chargeAccount, activeRatesheet, charges, invoiceIds, manifestIds} = chargeState
    const {packageIsMinimum, packageIsPallet, packages, useImperial} = packageState

    const {match: {params}} = props

    const configureBill = () => {
        const fetchUrl = `/bills/${params.billId ? params.billId : 'create'}`

        document.title = params.billId ? `Manage Bill: ${params.billId}` : 'Create Bill - Fast Forward Express'

        makeAjaxRequest(fetchUrl, 'GET', null, data => {
            data = JSON.parse(data)
            data.drivers = props.drivers
            data.employees = props.employees

            billDispatch({type: 'CONFIGURE_BILL', payload: data})
            billDispatch({type: 'SET_ACTIVE_RATESHEET', payload: data.ratesheets[0]})

            chargeDispatch({
                type: 'CONFIGURE_CHARGES',
                payload: {
                    accounts: data.accounts,
                    activeRatesheet: data.ratesheets[0],
                    charges: data?.charges,
                    chargeTypes: data.charge_types,
                    interliners: data.interliners,
                    ratesheets: data.ratesheets
                }
            })
            packageDispatch({type: 'CONFIGURE_PACKAGES'})

            if(data.bill?.bill_id) {
                billDispatch({type: 'CONFIGURE_EXISTING', payload: data})
                chargeDispatch({type: 'CONFIGURE_EXISTING', payload: data})
                packageDispatch({type: 'CONFIGURE_EXISTING', payload: data})
                const currentBillIndex = props.sortedBills.findIndex(bill_id => bill_id === data.bill.bill_id)
                if(currentBillIndex != -1) {
                    const prevBillId = currentBillIndex == 0 ? null : props.sortedBills[currentBillIndex - 1]
                    const nextBillId = currentBillIndex <= props.sortedBills.length ? props.sortedBills[currentBillIndex + 1] : null
                    billDispatch({type: 'SET_NEXT_BILL_ID', payload: nextBillId})
                    billDispatch({type: 'SET_PREV_BILL_ID', payload: prevBillId})
                }

                if(data.charges?.length === 1 && data.charges[0].charge_account_id) {
                    const chargeAccount = data.accounts.find(account => account.account_id === data.charges[0].charge_account_id)
                    const ratesheet = data.ratesheets.find(ratesheet => ratesheet.ratesheet_id === chargeAccount.ratesheet_id)
                    if(ratesheet)
                        billDispatch({type: 'SET_ACTIVE_RATESHEET', payload: ratesheet})
                }
            } else {
                if(data.permissions.createFull && !data.permissions.packages)
                    packageDispatch({type: 'TOGGLE_PACKAGE_IS_MINIMUM'})
                billDispatch({type: 'SET_PICKUP_TIME_EXPECTED', payload: new Date()})
            }
        })
    }

    const copyBill = () => {
        if(!billId)
            return
        if(confirm(`Are you certain you wish to make a copy of bill ${billId}?\nThe pickup and delivery date will be changed to today, but all other fields including times, will remain the same`)) {
            makeAjaxRequest(`/bills/copy/${billId}`, 'GET', null, response => {
                toastr.success(`Successfully copied bill ${billId} to new bill ${response.bill_id}`)
            })
        }
    }

    const generateCharges = useCallback((overwrite = false) => {
        if(awaitingCharges)
            return
        if(charges?.length !== 1) {
            toastr.error('Unable to generate charges where there is not exactly one charge recipient present')
            return
        }

        const data = {
            charge_account_id: chargeAccount ? chargeAccount.account_id : null,
            delivery_address: {lat: deliveryAddressLat, lng: deliveryAddressLng},
            delivery_type_id: deliveryType.id,
            package_is_minimum: packageIsMinimum,
            package_is_pallet: packageIsPallet,
            packages: packageState.packageIsMinimum ? [] : packageState.tableRef.current.table.getData(),
            pickup_address: {lat: pickupAddressLat, lng: pickupAddressLng},
            ratesheet_id: activeRatesheet ? activeRatesheet.ratesheet_id : null,
            time_pickup_scheduled: pickupTimeScheduled,
            time_delivery_scheduled: deliveryTimeScheduled,
            use_imperial: useImperial
        }
        setAwaitingCharges(true)

        makeAjaxRequest('/bills/generateCharges', 'POST', data, response => {
            response = JSON.parse(response)
            if(overwrite)
                charges[0].tableRef.current.table.replaceData(response)
            else
                response.forEach(charge => {
                    charges[0].tableRef.current.table.addRow(charge);
                })
            setAwaitingCharges(false)
            toastr.warning('Automatic Pricing is currently experimental. Please review the charges generated carefully for any inconsistencies')
        },
        error => {setAwaitingCharges(false)}
        )
    }, [
        activeRatesheet,
        chargeAccount,
        charges,
        deliveryAddressLat,
        deliveryAddressLng,
        deliveryTimeScheduled,
        deliveryType,
        packageIsMinimum,
        packageIsPallet,
        packages,
        pickupAddressLat,
        pickupAddressLng,
        pickupTimeScheduled,
        useImperial,
    ])

    const getStoreButton = () => {
        if(billId) {
            if(permissions.editBasic || permissions.editDispatch || permissions.editBilling)
                return <Button variant='primary' onClick={storeBill} disabled={billState.readOnly}>Update</Button>
        } else {
            if(permissions.createBasic || permissions.createFull)
                return <Button variant='primary' onClick={storeBill} disabled={billState.readOnly}>Create</Button>
            else
                return <Button variant='primary' disabled>{billId ? 'Update' : 'Create'}</Button>
        }
    }

    const toggleTemplate = () => {
        makeAjaxRequest(`/bills/template/${billId}`, 'GET', null, response => {
            billDispatch({type: 'SET_IS_TEMPLATE', payload: response.is_template})
        })
    }

    const toggleTermsAndConditions = event => {
        if(event)
            event.preventDefault()
        setViewTermsAndConditions(!viewTermsAndConditions)
    }

    const storeBill = () => {
        if(readOnly)
            return
        try {
            billDispatch({type: 'TOGGLE_READ_ONLY', payload: true})
            var data = {bill_id: billId}
            if(billId ? permissions.editBasic : permissions.createBasic)
                data = {...data,
                    delivery_account_id: billState.delivery.account?.account_id,
                    delivery_address_formatted: billState.delivery.addressFormatted,
                    delivery_address_lat: billState.delivery.addressLat,
                    delivery_address_lng: billState.delivery.addressLng,
                    delivery_address_name: billState.delivery.addressName,
                    delivery_address_place_id: billState.delivery.addressPlaceId,
                    delivery_address_type: billState.delivery.addressType,
                    delivery_type: billState.deliveryType,
                    delivery_reference_value: billState.delivery.referenceValue,
                    description: billState.description,
                    is_min_weight_size: packageState.packageIsMinimum ? true : false,
                    is_pallet: packageState.packageIsPallet,
                    packages: packageState.packageIsMinimum ? [] : packageState.tableRef.current.table.getData(),
                    pickup_account_id: billState.pickup.account?.account_id,
                    pickup_address_formatted: billState.pickup.addressFormatted,
                    pickup_address_lat: billState.pickup.addressLat,
                    pickup_address_lng: billState.pickup.addressLng,
                    pickup_address_name: billState.pickup.addressName,
                    pickup_address_place_id: billState.pickup.addressPlaceId,
                    pickup_address_type: billState.pickup.addressType,
                    pickup_reference_value: billState.pickup.referenceValue,
                    proof_of_delivery_required: packageState.proofOfDeliveryRequired,
                    time_delivery_scheduled: billState.delivery.timeScheduled.toLocaleString("en-US"),
                    time_pickup_scheduled: billState.pickup.timeScheduled.toLocaleString("en-US"),
                    updated_at: billState.updatedAt.toLocaleString("en-US"),
                    use_imperial: packageState.useImperial
                }

            if(!billId && permissions.createBasic && !permissions.createFull)
                data = {...data,
                    accept_terms_and_conditions: billState.acceptTermsAndConditions,
                    charge_type: chargeState.chargeType,
                    charge_account_id: chargeState.chargeAccount?.account_id,
                    charge_reference_value: chargeState.chargeReferenceValue
                }

            if(billId ? permissions.editDispatch : permissions.createFull)
                data = {...data,
                    bill_number: billState.billNumber,
                    delivery_driver_commission: billState.delivery.driverCommission,
                    delivery_driver_id: billState.delivery.driver?.employee_id,
                    internal_comments: billState.internalNotes,
                    pickup_driver_id: billState.pickup.driver?.employee_id,
                    pickup_driver_commission: billState.pickup.driverCommission,
                    time_call_received: billState.timeCallReceived ? billState.timeCallReceived.toLocaleString("en-US") : new Date().toLocaleString("en-US"),
                    time_dispatched: billState.timeDispatched ? billState.timeDispatched.toLocaleString("en-US") : null,
                }

            if(billId ? permissions.editBilling : permissions.createFull) {
                data = {...data,
                    charges: chargeState.charges,
                    interliner_cost: chargeState.interlinerActualCost,
                    interliner_id: chargeState.interliner?.value,
                    interliner_reference_value: chargeState.interlinerReferenceValue,
                    repeat_interval: billState.repeatInterval?.selection_id,
                    skip_invoicing: billState.skipInvoicing
                }
                data.charges.forEach(charge => delete charge.tableRef)

                const chargesPresent = data.charges ? data.charges.filter(charge => !charge.toBeDeleted).length > 0 : false
                if(!chargesPresent && !confirm("This bill is being saved without any charges present.\n\nPress okay if this is intentional, or cancel to return and review the bill."))
                    throw 'No charges present'

                // Confirmation modal if bill is charged to an account other than the pickup or delivery account
                // Only performing on create
                if(!billId && chargesPresent) {
                    // If there is an account set, see whether it is in the set of charges. If it is not set, we consider this "true" as it's not a mismatch
                    const pickupAccountMatch = pickupAccount ? data.charges.find(charge => charge.charge_account_id == pickupAccount.account_id) : true
                    const deliveryAccountMatch = deliveryAccount ? data.charges.find(charge => charge.charge_account_id == deliveryAccount.account_id) : true
                    // If both are false, then neither match and we should check with the user before submitting that this was intentional
                    if(!pickupAccountMatch && !deliveryAccountMatch) {
                        if(!confirm("This bill is being charged to an account which is different from the pickup and/or delivery accounts.\n\nPress okay if this is intentional, or cancel to return and review the bill."))
                            throw "Mismatched charge account"
                    }
                }
            }

            makeAjaxRequest('/bills/store', 'POST', data, response => {
                toastr.clear()
                if(billId) {
                    toastr.success(`Bill ${billId} was successfully updated!`, 'Success')
                    configureBill()
                } else {
                    toastr.success(`Bill ${response.id} was successfully created`, 'Success', {
                        'progressBar': true,
                        'positionClass': 'toast-top-full-width',
                        'showDuration': 300,
                        'onHidden': () => {
                            billDispatch({type: 'SET_TAB_KEY', payload: 'basic'})
                            billDispatch({type: 'TOGGLE_READ_ONLY', payload: false})
                            configureBill()
                        }
                    })
                }
            }, error => {
                billDispatch({type: 'TOGGLE_READ_ONLY', payload: false})
            })
        }
        catch(error) {
            console.log(error)
            billDispatch({type: 'TOGGLE_READ_ONLY', payload: false})
        }
    }

    // On load => load the persist fields from local storage and make sure to check them off in the reducer
    useEffect(() => {
        let stored = localStorage.getItem("persistFields")
        if(stored) {
            stored = JSON.parse(stored)
            stored.forEach(field => billDispatch({type: 'TOGGLE_PERSIST_FIELD', name: field.name, checked: field.checked}))
        }
    }, [])

    // If the bill ID changes, reload all fields with new data (for SPA navigation)
    useEffect(() => {
        configureBill()
        if(params.billId === 'create')
            billDispatch({type: 'TOGGLE_READ_ONLY', payload: false})
    }, [params.billId])

    // In the event a new pickup or delivery account has been set and there is no charge account, automatically populate the charge account
    useEffect(() => {
        if(!billId && !chargeState.charges?.length && pickupAccount?.account_id) {
            chargeDispatch({type: 'SET_CHARGE_ACCOUNT', payload: pickupAccount})
            chargeDispatch({type: 'SET_CHARGE_TYPE', payload: chargeState.chargeTypes.find(chargeType => chargeType.name === 'Account')})
            chargeDispatch({type: 'ADD_CHARGE_TABLE'})
        }
    }, [pickupAccount])

    // If the delivery account changes, as a convenience we create a charge assigned to this account
    useEffect(() => {
        if(!billId && !chargeState.charges?.length && deliveryAccount?.account_id) {
            chargeDispatch({type: 'SET_CHARGE_ACCOUNT', payload: deliveryAccount})
            chargeDispatch({type: 'SET_CHARGE_TYPE', payload: chargeState.chargeTypes.find(chargeType => chargeType.name === 'Account')})
            chargeDispatch({type: 'ADD_CHARGE_TABLE'})
        }
    }, [deliveryAccount])

    // Set the ratesheet (for purposes of delivery type time primarily) - based on the currently selected charge Account on the basic page
    useEffect(() => {
        if(permissions.createBasic && billID && chargeAccount?.ratesheet_id != null && chargeAccount?.ratesheet_id != activeRatesheet?.ratesheet_id) {
            const ratesheet = billState.ratesheets.find(ratesheet => ratesheet.ratesheet_id === chargeAccount.ratesheet_id)
            if(ratesheet)
                billDispatch({type: 'SET_ACTIVE_RATESHEET', payload: ratesheet})
        }
    }, [chargeAccount])

    // If the charge account changes, and it is the only valid option as a chargeType for this user (i.e. they only have one account so it is automatically locked currently)
    // then create a charge assigned to this account so that the submission will be successful
    useEffect(() => {
        if(charges?.length === 0 && permissions.createBasic && !permissions.createFull && !billId && accounts.length === 1 && chargeState.chargeType)
            chargeDispatch({type: 'ADD_CHARGE_TABLE'})
    }, [chargeAccount, permissions, accounts, chargeState.chargeType])

    useEffect(() => {
        let conditionsMet = false
        if(!!activeRatesheet.ratesheet_id && !billId && !!deliveryAddressLat && !!deliveryAddressLng && !!pickupAddressLat && !!pickupAddressLng && !!deliveryType) {
            if(packageIsMinimum) {
                conditionsMet = true
            } else {
                if(packages.length > 0) {
                    conditionsMet = packages.reduce(currentPackage => {
                        return !!currentPackage.count && !!currentPackage.weight && !!currentPackage.length && !!currentPackage.width && !!currentPackage.height
                    })
                }
            }
        }
        if(permissions.createFull && conditionsMet)
            generateCharges(true)
    }, [
        activeRatesheet,
        billId,
        charges,
        deliveryAddressLat,
        deliveryAddressLng,
        deliveryType,
        packageIsMinimum,
        packageIsPallet,
        packages,
        pickupAddressLat,
        pickupAddressLng,
        useImperial,
    ])

    return (
        <Row className='justify-content-md-center'>
            <Col md={12}>
                <Navbar expand='md' variant='dark' bg='dark'>
                    <Navbar.Brand style={{paddingLeft: '15px'}}>
                        <h4>{billId ? `Bill ID: ${billId}` : 'Create Bill'}</h4>
                    </Navbar.Brand>
                    {/* {(billId && billState.charges) &&
                        <ListGroup.Item variant='warning'></ListGroup.Item>
                    } */}
                    {billId &&
                        <Badge bg='success' style={{margin: '0px 5px'}}>
                            <h5>{billState.percentComplete}% Complete
                                {billState.incompleteFields?.length ?
                                    <OverlayTrigger
                                        placement={"right"}
                                        overlay={<Tooltip><ul>{billState.incompleteFields.map(field => <li key={field}>{field}</li>)}</ul></Tooltip>}
                                    >
                                        <i className='fas fa-question-circle' style={{paddingLeft: '10px'}}/>
                                    </OverlayTrigger> : null
                                }
                            </h5>
                        </Badge>
                    }
                    {invoiceIds?.length &&
                        <Badge bg='info' text='dark' style={{margin: '0px 5px'}}>
                            <h5>Invoices: {invoiceIds.map((invoiceId, index, arr) => {
                                if(props.frontEndPermissions.invoices.viewAny)
                                    return (
                                        <LinkContainer to={`/app/invoices/${invoiceId}`} style={{marginRight: '7px'}} key={invoiceId}>
                                            <a>{invoiceId}</a>
                                        </LinkContainer>
                                    )
                                return `${invoiceId}${index === arr.length - 1 ? '' : ', '}`
                            })}</h5>
                        </Badge>
                    }
                    {manifestIds?.length &&
                        <Badge bg='light' text='dark' style={{margin: '0px 5px'}}>
                            <h5>Manifests: {manifestIds.map((manifestId, index, arr) => {
                                if(props.frontEndPermissions.invoices.viewAny)
                                    return (
                                        <LinkContainer to={`/app/manifests/${manifestId}`} style={{marginRight: '7px'}} key={manifestId}>
                                            <a>{manifestId}</a>
                                        </LinkContainer>
                                    )
                                return `${manifestId}${index === arr.length - 1 ? '' : ', '}`
                            })}</h5>
                        </Badge>
                    }
                    <Navbar.Collapse className='justify-content-end' style={{paddingRight: '15px'}}>
                        {(!billId && permissions.createFull) &&
                            <NavDropdown title='Persist Fields'>
                                <ul style={{listStyleType: 'none', padding: '4px 10px'}}>
                                    {billState.persistFields.sort((a, b) => a.label > b.label ? 1 : -1).map(persistField =>
                                        <li key={persistField.name}>
                                            <FormCheck
                                                name={persistField.name}
                                                label={persistField.label}
                                                checked={persistField.checked}
                                                onChange={event => billDispatch({type: 'TOGGLE_PERSIST_FIELD', name: event.target.name, checked: event.target.checked})}
                                                style={{whiteSpace: 'nowrap'}}
                                            />
                                        </li>
                                    )}
                                </ul>
                            </NavDropdown>
                        }
                        {(permissions.createFull || permissions.editDispatch) &&
                            <Button
                                variant={billState.applyRestrictions ? 'dark' : 'danger'}
                                onClick={() => billDispatch({type: 'TOGGLE_RESTRICTIONS'})}
                                style={{backgroundColor: billState.applyRestrictions ? 'tomato' : 'black', color: billState.applyRestrictions ? 'black' : 'white'}}
                                title='Toggle restrictions'
                            >
                                <i className={billState.applyRestrictions ? 'fas fa-lock' : 'fas fa-unlock'}></i> {billState.applyRestrictions ? 'Remove Time Restrictions' : 'Restore Time Restrictions'}
                            </Button>
                        }
                        {(billId && (permissions.createBasic || permissions.createFull)) &&
                            <Button
                                onClick={toggleTemplate}
                                variant='warning'
                            >
                                <i className={`${isTemplate ? 'fas' : 'far'} fa-star`}></i>Template
                            </Button>
                        }
                        {(billId && (permissions.createBasic || permissions.createFull)) &&
                            <Button
                                variant='success'
                                onClick={copyBill}
                                title='Copy Bill'
                            ><i className='fas fa-copy'></i> Copy Bill</Button>
                        }
                        {billId &&
                            <Dropdown
                                align='end'
                                as={ButtonGroup}
                            >
                                <Button
                                    href={billId ? `/bills/print/${billId}` : null}
                                    target='_blank'
                                    title='Print Bill'
                                    variant='success'
                                ><i className='fas fa-print'></i> Print</Button>
                                <Dropdown.Toggle
                                    id='print-button-split'
                                    split
                                    variant='success'
                                >
                                    <Dropdown.Menu>
                                        <Dropdown.Item
                                            href={billId ? `/bills/print/${billId}?showCharges` : null}
                                            target='_blank'
                                            title='Print Bill with Charges'
                                            variant='success'
                                        >Print with Charges</Dropdown.Item>
                                    </Dropdown.Menu>
                                </Dropdown.Toggle>
                            </Dropdown>
                        }
                    </Navbar.Collapse>
                </Navbar>
            </Col>
            <Col md={12}>
                <Tabs id='bill-tabs' className='nav-justified' activeKey={billState.key} onSelect={key =>billDispatch({type: 'SET_TAB_KEY', payload: key})}>
                    <Tab eventKey='basic' title={<h4>Pickup/Delivery Info <i className='fas fa-map-pin'></i></h4>}>
                        <BasicTab
                            billDispatch={billDispatch}
                            billState={billState}
                            chargeDispatch={chargeDispatch}
                            chargeState={chargeState}
                            packageDispatch={packageDispatch}
                            packageState={packageState}
                        />
                    </Tab>
                    {(billId ? permissions.viewDispatch : permissions.createFull) &&
                        <Tab eventKey='dispatch' title={<h4>Dispatch <i className='fas fa-truck'></i></h4>}>
                            <DispatchTab
                                billState={billState}
                                billDispatch={billDispatch}
                                charges={chargeState.charges}
                                drivers={props.drivers}
                                // isDeliveryManifested={this.state.charges.some(charge => charge.lineItems.some(lineItem => lineItem.delivery_manifest_id))}
                                // isPickupManifested={this.state.charges.some(charge => charge.lineItems.some(lineItem => lineItem.pickup_manifest_id))}
                                isPickupManifested={false}
                                isDeliveryManifested={false}
                            />
                        </Tab>
                    }
                    {(billId ? permissions.viewBilling : permissions.createFull) &&
                        <Tab eventKey='billing' title={<h4>Billing  <i className='fas fa-credit-card'></i></h4>}>
                            <BillingTab
                                billDispatch={billDispatch}
                                billState={billState}
                                chargeDispatch={chargeDispatch}
                                chargeState={chargeState}
                                generateCharges={() => generateCharges()}
                            />
                        </Tab>
                    }
                    {(permissions.viewActivityLog && billState.activityLog) &&
                        <Tab eventKey='activity_log' title={<h4>Activity Log  <i className='fas fa-book-open'></i></h4>}>
                            <ActivityLogTab
                                activityLog={billState.activityLog}
                            />
                        </Tab>
                    }
                </Tabs>
            </Col>
            {(!billId && permissions.createBasic && !permissions.createFull) &&
                <Col md='auto'>
                    <FormCheck
                        name={'acceptTermsAndConditions'}
                        label={<p>I have read and agree to the <a href='' onClick={event => toggleTermsAndConditions(event)}>terms and conditions</a></p>}
                        checked={billState.acceptTermsAndConditions}
                        disabled={readOnly}
                        onChange={event => billDispatch({type: 'TOGGLE_ACCEPT_TERMS_AND_CONDITIONS'})}
                        type='switch'
                        style={{whiteSpace: 'nowrap'}}
                    />
                </Col>
            }
            <Col md={12} className='text-center'>
                <ButtonGroup>
                    {billId &&
                        <LinkContainer to={`/app/bills/${prevBillId}#${window.location.hash?.substr(1)}`}>
                            <Button variant='secondary' disabled={!prevBillId}>
                                <i className='fas fa-arrow-circle-left'></i> Back - {prevBillId}
                            </Button>
                        </LinkContainer>
                    }
                    {getStoreButton()}
                    {billId &&
                        <LinkContainer to={`/app/bills/${nextBillId}#${window.location.hash?.substr(1)}`}>
                            <Button variant='secondary' disabled={!nextBillId}>
                                Next - {nextBillId} <i className='fas fa-arrow-circle-right'></i>
                            </Button>
                        </LinkContainer>
                    }
                </ButtonGroup>
            </Col>
            <Modal show={viewTermsAndConditions} onHide={toggleTermsAndConditions} size='lg'>
                <Modal.Header closeButton>
                    <Modal.Title>Terms and Conditions</Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <h4>DAMAGE OR LOSS</h4>
                    ANY DAMAGE MUST BE NOTED ON THE BILL OF LADING AT THE TIME OF DELIVERY OTHERWISE CONSIGNEE'S SIGNATURE WILL CONSTITUTE CONCLUSIVE PROOF OF GOODS HAVING BEEN RECEIVED IN GOOD ORDER AND CONDITION. CARRIER WILL NOT BE LIABLE FOR ANY DAMAGE OR LOSS UNLESS WRITTEN NOTICE THEREOF IS GIVEN TO CARRIER AT ITS REGION OR HEAD OFFICE WITHIN TEN (10) DAYS AFTER THE SHIPMENT WAS RECEIVED BY THE CARRIER FOR CARRIAGE
                    <h4>CHARGES AND RATES</h4>
                    FREIGHT CHARGES ARE PREPAID UNLESS OTHERWISE STATED. THIS BILL OF LADING SHALL BE DEEMED TO INCORPORATE SUCH TERMS AND CONDITIONS AS MAY BE REQUIRED TO BE INCORPORATED BY THE LEGISLATION OF ANY JURISDICTION TO WHICH IT IS SUBJECT ON COLLECT SHIPMENTS. IF CONSIGNEE DOES NOT PAY FULL CHARGES, SHIPPER AGREES TO PAY ALL CHARGES <br/>
                    ADDITIONALLY, UNTIL COMPLETION OF DELIVERY ANY PRICES ARE ESTIMATIONS BASED UPON PROVIDED INFORMATION AND ARE SUBJECT TO CHANGE UPON ARRIVAL AND INSPECTION OF THE GOODS TO BE DELIVERED
                    <h4>DELAY AND LIMITATION OF LIABILITY</h4>
                    UNLESS SPECIFICALLY AGREED TO IN WRITING PRIOR TO SHIPMENT CARRIER WILL NOT:
                    <ol>
                        <li>BE LIABLE IN EXCESS OF THE DECLARED VALUE OR $500.00 CAD WHICHEVER IS LESS FOR ANY AND ALL DAMAGES WHATSOEVER ARISING FROM THE FAILURE OR DELAY IN DELIEVERY OF ANY SHIPMENT OR FOR ANY OTHER REASON INCLUDING THE NEGLIGENCE OF THE CARRIER ITS SERVANTS OR AGENTS</li>
                        <li>TRANSPORT ANY DOCUMENTS OR GOODS DECLARED TO HAVE A VALUE IN EXCESS OF $500.00 CAD</li>
                        <li>TRANSFORT ANY SPECIES</li>
                    </ol>
                    IF NO VALUE IS DECLARED ON THE FACE HEREOF, OR IF A SHIPMENT HAS A DECLARED VALUE IN EXCESS OF $500 CAD AND NO PRIOR SPECIAL AGREEMENT IN WRITING HAS BEEN OBTAINED THIS SHALL BE DEEMED TO BE AN AGREEMENT THAT THE VALUE OF THE GOODS SHIPPED IS $2.00 / LB ($4.41 / KG) AND CARRIER SHALL NOT BE LIABLE FOR ANY DAMAGES IN EXCESS THEREOF
                    UNDER NO CIRCUMSTANCES WILL THE CARRIER BE LIABLE FOR ANY INCIDENTAL OR CONSEQUENTIAL DAMAGES
                    <h4>DANGEROUS GOODS</h4>
                    CARRIER WILL NOT BE LIABLE FOR ANY LOSS, DAMAGE, FAILURE TO PERFORM OR DELAY FOR GOODS THAT ARE PROHIBITED, RESTRICTED, OR REQUIRED TO BE CARRIED IN SPECIAL CONTAINERS BY C.T.C., I.A.T.A, OR OTHERWISE, UNLESS SHIPPER FULLY DISCLOSES NATURE OF DANGEROUS GOODS AND SAME HAVE BEEN PROPERLY CONTAINED. SHIPPER AGREES TO INDEMNIFY CARRIER FOR ALL COSTS AND DAMAGES CAUSED BY ITS FAILURE TO DISCLOSE AND/OR PROPERLY CONTAIN DANGEROUS GOODS
                    <h4>NOTE: CARRIER DOES NOT GUARANTEE DELIVERY TIMES</h4>
                </Modal.Body>
            </Modal>
        </Row>
    )
}

const mapStateToProps = store => {
    return {
        drivers: store.app.drivers,
        employees: store.app.employees,
        frontEndPermissions: store.app.frontEndPermissions,
        sortedBills: store.bills.sortedList
    }
}

export default connect(mapStateToProps)(Bill)
