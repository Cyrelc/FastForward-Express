import React, {useCallback, useEffect, useState} from 'react'
import {Badge, Button, ButtonGroup, Col, Dropdown, FormCheck, Modal, Nav, Navbar, NavDropdown, OverlayTrigger, ProgressBar, Row, Tab, Tabs, Tooltip} from 'react-bootstrap'
import {useHistory, useLocation, useParams} from 'react-router-dom'
import {LinkContainer} from 'react-router-bootstrap'
import {toast} from 'react-toastify'

import ActivityLogTab from '../partials/ActivityLogTab'
import BasicTab from './BasicTab'
import BillingTab from './BillingTab'
import DispatchTab from './DispatchTab'
import {useAPI} from '../../contexts/APIContext'
// import {useLists} from '../../contexts/ListsContext'
import {useUser} from '../../contexts/UserContext'
import useBill from './hooks/useBill'

const unmanifestedDriverMismatchMessage = (
`------------------PLEASE READ CAREFULLY------------------

At least one unmanifested line item is assigned to a driver which no longer matches the pickup or delivery driver.

Click "Okay" to reassign, or "Cancel" to leave them.

Please remember you can view the driver who is assigned each line item by clicking the "Toggle details" button on the Billing tab`
)

export default function Bill(props) {
    const [isLoading, setIsLoading] = useState(true)
    const [nextBillId, setNextBillId] = useState(null)
    const [prevBillId, setPrevBillId] = useState(null)

    const api = useAPI()
    const {authenticatedUser: {front_end_permissions: frontEndPermissions}} = useUser()
    const {bill, charges, delivery, packages, pickup} = useBill()
    const history = useHistory()
    // const lists = useLists()
    const location = useLocation()
    const params = useParams()

    const {
        acceptTermsAndConditions,
        applyRestrictions,
        billId,
        incompleteFields,
        isTemplate,
        percentComplete,
        permissions,
        persistFields,
        readOnly,
        toggleIsTemplate,
        togglePersistField,
        toggleRestrictions,
        toggleAcceptTermsAndConditions,
        toggleViewTermsAndConditions,
        viewTermsAndConditions,
    } = bill

    // const [awaitingCharges, setAwaitingCharges] = useState(false)

    // const queryParams = new URLSearchParams(location.search)

    const configureBill = () => {
        setIsLoading(true)
        const fetchUrl = `/bills/${params.billId ? params.billId : 'create'}${location.search}`

        document.title = params.billId ? `Manage Bill: ${params.billId}` : 'Create Bill - Fast Forward Express'

        api.get(fetchUrl)
            .then(data => {
                bill.setup(data)

                if(data.bill?.bill_id) {
                    let sortedBills = localStorage.getItem('bills.sortedList')
                    if(sortedBills) {
                        sortedBills = sortedBills.split(',').map(index => parseInt(index))
                        const currentBillIndex = sortedBills.findIndex(bill_id => bill_id === data.bill.bill_id)
                        if(currentBillIndex != -1) {
                            const prevBillId = currentBillIndex == 0 ? null : sortedBills[currentBillIndex - 1]
                            const nextBillId = currentBillIndex <= sortedBills.length ? sortedBills[currentBillIndex + 1] : null
                            setPrevBillId(prevBillId)
                            setNextBillId(nextBillId)
                        }
                    }
                }

                if(!location.hash)
                    history.push('#basic')
    //             billDispatch({type: 'SET_ACTIVE_RATESHEET', payload: data.ratesheets[0]})


    //             if(data.bill?.bill_id) {

    //                 if(data.charges?.length === 1 && data.charges[0].charge_account_id) {
    //                     // const chargeAccount = data.accounts.find(account => account.account_id === data.charges[0].charge_account_id)
    //                     // const ratesheet = data.ratesheets.find(ratesheet => ratesheet.ratesheet_id === chargeAccount.ratesheet_id)
    //                     if(ratesheet) {
    //                         billDispatch({type: 'SET_ACTIVE_RATESHEET', payload: ratesheet})
    //                         // chargeDispatch({type: 'SET_ACTIVE_RATESHEET', payload: ratesheet})
    //                     }
    //                 }
    //             } else {
    //                 if(data.permissions.createFull && !data.permissions.packages)
    //                     packages.setPackageIsMinimum(true)
    //                 if(queryParams.get('copy_from')) {
    //                     billDispatch({type: 'CONFIGURE_COPY', payload: data})
    //                     // chargeDispatch({type: 'CONFIGURE_EXISTING', payload: data})
    //                 }
    //                 billDispatch({type: 'SET_PICKUP_TIME_EXPECTED', payload: new Date()})
    //             }

    //             billDispatch({type: 'SET_IS_LOADING', payload: false})
                setIsLoading(false)
            }
        )
    }

    const copyBill = () => {
        if(!billId) {
            toast.error("Can't copy a bill that doesn't exist... how did you even get this menu!?!?")
            return
        }
        history.push(`/bills/new/create?copy_from=${billId}`)
    }

    // const debounceSetPickupTimeExpected = useCallback(debounce((value) => {
    //     billDispatch({type: 'SET_PICKUP_TIME_EXPECTED', payload: value})
    // }, 300), [])

    // const generateCharges = (chargeIndex, overwrite = false) => {
    //     if(awaitingCharges)
    //         return

    //     setAwaitingCharges(true)

    //     debouncedGenerateCharges(chargeIndex, overwrite)
    // }

    // const getStoreButton = () => {
    //     if(billId) {
    //         if(permissions.editBasic || permissions.editDispatch || permissions.editBilling)
    //             return <Button variant='primary' onClick={storeBill} disabled={billState.readOnly}>Update</Button>
    //     } else {
    //         if(permissions.createBasic || permissions.createFull)
    //             return <Button variant='primary' onClick={storeBill} disabled={billState.readOnly}>Create</Button>
    //         else
    //             return <Button variant='primary' disabled>{billId ? 'Update' : 'Create'}</Button>
    //     }
    // }

    // const toggleTemplate = () => {
    //     api.get(`/bills/template/${billId}`)
    //         .then(response => {
    //             billDispatch({type: 'SET_IS_TEMPLATE', payload: response.is_template})
    //         })
    // }

    // const storeBill = () => {
    //     if(readOnly)
    //         return
    //     try {
    //         billDispatch({type: 'TOGGLE_READ_ONLY', payload: true})
    //         var data = {bill_id: billId}
    //         if(billId ? permissions.editBasic : permissions.createBasic)
    //             data = {...data,
    //                 ...packages.collect(),
    //                 delivery_account_id: billState.delivery.account?.account_id,
    //                 delivery_address_formatted: billState.delivery.addressFormatted,
    //                 delivery_address_is_mall: billState.delivery.isMall,
    //                 delivery_address_lat: billState.delivery.addressLat,
    //                 delivery_address_lng: billState.delivery.addressLng,
    //                 delivery_address_name: billState.delivery.addressName,
    //                 delivery_address_place_id: billState.delivery.addressPlaceId,
    //                 delivery_address_type: billState.delivery.addressType,
    //                 delivery_type: billState.deliveryType,
    //                 delivery_reference_value: billState.delivery.referenceValue,
    //                 description: billState.description,
    //                 pickup_account_id: billState.pickup.account?.account_id,
    //                 pickup_address_formatted: billState.pickup.addressFormatted,
    //                 pickup_address_is_mall: billState.pickup.isMall,
    //                 pickup_address_lat: billState.pickup.addressLat,
    //                 pickup_address_lng: billState.pickup.addressLng,
    //                 pickup_address_name: billState.pickup.addressName,
    //                 pickup_address_place_id: billState.pickup.addressPlaceId,
    //                 pickup_address_type: billState.pickup.addressType,
    //                 pickup_reference_value: billState.pickup.referenceValue,
    //                 time_delivery_scheduled: billState.delivery.timeScheduled.toLocaleString("en-US"),
    //                 time_pickup_scheduled: billState.pickup.timeScheduled.toLocaleString("en-US"),
    //                 updated_at: billState.updatedAt.toLocaleString("en-US"),
    //             }

    //         if(!billId && permissions.createBasic && !permissions.createFull)
    //             data = {...data,
    //                 accept_terms_and_conditions: billState.acceptTermsAndConditions,
    //                 charge_type: chargeState.chargeType,
    //                 charge_account_id: chargeState.chargeAccount?.account_id,
    //                 charge_reference_value: chargeState.chargeReferenceValue
    //             }

    //         if(billId ? permissions.editDispatch : permissions.createFull)
    //             data = {...data,
    //                 bill_number: billState.billNumber,
    //                 delivery_driver_commission: billState.delivery.driverCommission,
    //                 delivery_driver_id: billState.delivery.driver?.value,
    //                 delivery_person_name: billState.delivery.personName,
    //                 internal_comments: billState.internalComments,
    //                 pickup_driver_id: billState.pickup.driver?.value,
    //                 pickup_driver_commission: billState.pickup.driverCommission,
    //                 pickup_person_name: billState.pickup.personName,
    //                 time_call_received: billState.timeCallReceived ? billState.timeCallReceived.toLocaleString("en-US") : new Date().toLocaleString("en-US"),
    //                 time_delivered: billState.delivery.timeActual ? billState.delivery.timeActual.toLocaleString("en-US") : null,
    //                 time_dispatched: billState.timeDispatched ? billState.timeDispatched.toLocaleString("en-US") : null,
    //                 time_picked_up: billState.pickup.timeActual ? billState.pickup.timeActual.toLocaleString("en-US") : null,
    //             }

    //         if(billId ? permissions.editBilling : permissions.createFull) {
    //             data = {...data,
    //                 charges: chargeState.charges,
    //                 interliner_cost: chargeState.interlinerActualCost,
    //                 interliner_id: chargeState.interliner?.value,
    //                 interliner_reference_value: chargeState.interlinerReferenceValue,
    //                 repeat_interval: billState.repeatInterval?.selection_id,
    //                 skip_invoicing: billState.skipInvoicing
    //             }
    //             data.charges.forEach(charge => delete charge.tableRef)

    //             const chargesPresent = data.charges ? data.charges.filter(charge => !charge.toBeDeleted).length > 0 : false
    //             if(!chargesPresent && !confirm("This bill is being saved without any charges present.\n\nPress okay if this is intentional, or cancel to return and review the bill."))
    //                 throw 'No charges present'

    //             // Confirmation modal if bill is charged to an account other than the pickup or delivery account
    //             // Only performing on create
    //             if(!billId && chargesPresent) {
    //                 // If there is an account set, see whether it is in the set of charges. If it is not set, we consider this "true" as it's not a mismatch
    //                 const pickupAccountMatch = pickupAccount ? data.charges.find(charge => charge.charge_account_id == pickupAccount.account_id) : true
    //                 const deliveryAccountMatch = deliveryAccount ? data.charges.find(charge => charge.charge_account_id == deliveryAccount.account_id) : true
    //                 // If both are false, then neither match and we should check with the user before submitting that this was intentional
    //                 if(!pickupAccountMatch && !deliveryAccountMatch) {
    //                     if(!confirm("This bill is being charged to an account which is different from the pickup and/or delivery accounts.\n\nPress okay if this is intentional, or cancel to return and review the bill."))
    //                         throw "Mismatched charge account"
    //                 }
    //             }
    //         }

    //         api.post('/bills/store', data)
    //             .then(response => {
    //                 if(billId) {
    //                     toast.success(`Bill ${billId} was successfully updated!`)
    //                     configureBill()
    //                 } else {
    //                     toast.success(`Bill ${response.id} was successfully created`, {
    //                         position: 'top-center',
    //                         onClose: () => {
    //                             billDispatch({type: 'SET_TAB_KEY', payload: 'basic'})
    //                             billDispatch({type: 'TOGGLE_READ_ONLY', payload: false})
    //                             configureBill()
    //                         }
    //                     })
    //                 }
    //         }, error => {
    //             billDispatch({type: 'TOGGLE_READ_ONLY', payload: false})
    //         })
    //     }
    //     catch(error) {
    //         console.log(error)
    //         billDispatch({type: 'TOGGLE_READ_ONLY', payload: false})
    //     }
    // }

    // If the pickup or delivery driver is changed, offer the user the option to update unmanifested line items
    // This doesn't happen by default, as it would otherwise throw away customized line items
    // useEffect(() => {
    //     if(!pickupDriver || !deliveryDriver)
    //         return

    //     const mismatchedCharges = charges.filter(charge =>
    //         charge.lineItems.some(lineItem => {
    //             if(lineItem.delivery_manifest_id == null && lineItem.delivery_driver_id && lineItem.delivery_driver_id != deliveryDriver.employee_id)
    //                 return true
    //             if(lineItem.pickup_manifest_id == null && lineItem.pickup_driver_id && lineItem.pickup_driver_id != pickupDriver.employee_id)
    //                 return true
    //             return false
    //         }
    //     ))

    //     if(mismatchedCharges.length && confirm(unmanifestedDriverMismatchMessage)) {
    //         charges.forEach((charge, index) => {
    //             const updatedLineItems = charge.lineItems.map(lineItem => {
    //                 return {
    //                     ...lineItem,
    //                     delivery_driver_id: lineItem.delivery_manifest_id ? lineItem.delivery_driver_id : deliveryDriver.employee_id,
    //                     pickup_driver_id: lineItem.pickup_manifest_id ? lineItem.pickup_driver_id : pickupDriver.employee_id
    //                 }
    //             })
    //             // chargeDispatch({type: 'UPDATE_LINE_ITEMS', payload: {data: updatedLineItems, index: index}})
    //         })
    //     }
    // }, [pickupDriver, deliveryDriver])

    // On load => load the persist fields from local storage and make sure to check them off in the reducer
    // useEffect(() => {
    //     let stored = localStorage.getItem("persistFields")
    //     if(stored) {
    //         stored = JSON.parse(stored)
    //         stored.forEach(field => billDispatch({type: 'TOGGLE_PERSIST_FIELD', name: field.name, checked: field.checked}))
    //     }
    // }, [])

    // If the bill ID changes, reload all fields with new data (for SPA navigation)
    useEffect(() => {
        configureBill()
        // if(params.billId === 'create')
        //     billDispatch({type: 'TOGGLE_READ_ONLY', payload: false})
    }, [params.billId])

    // If the charge account changes, and it is the only valid option as a chargeType for this user (i.e. they only have one account so it is automatically locked currently)
    // then create a charge assigned to this account so that the submission will be successful
    // useEffect(() => {
    //     if(charges?.length === 0 && permissions.createBasic && !permissions.createFull && !billId && accounts.length === 1 && chargeState.chargeType)
    //         chargeDispatch({type: 'ADD_CHARGE_TABLE'})
    // }, [chargeAccount, permissions, accounts, chargeState.chargeType])

    // useEffect(() => {
    //     let conditionsMet = false
    //     if(!!activeRatesheet.ratesheet_id && !billId && !!deliveryAddressLat && !!deliveryAddressLng && !!pickupAddressLat && !!pickupAddressLng && !!deliveryType && charges[0]) {
    //         if(packageIsMinimum) {
    //             conditionsMet = true
    //         } else {
    //             if(packageArray.length > 0) {
    //                 conditionsMet = packageArray.reduce(currentPackage => {
    //                     return !!currentPackage.count && !!currentPackage.weight && !!currentPackage.length && !!currentPackage.width && !!currentPackage.height
    //                 })
    //             }
    //         }
    //     }

    //     if(permissions.createFull && conditionsMet)
    //         generateCharges(0, true)
    // }, [
    //     activeRatesheet,
    //     billId,
    //     charges[0]?.chargeType,
    //     charges[0]?.charge_account_id,
    //     deliveryAddressIsMall,
    //     deliveryAddressLat,
    //     deliveryAddressLng,
    //     deliveryType,
    //     packageIsMinimum,
    //     packageIsPallet,
    //     packageArray,
    //     pickupAddressIsMall,
    //     pickupAddressLat,
    //     pickupAddressLng,
    //     useImperial,
    // ])

    if(isLoading)
        return <h4>Requesting data, please wait... <i className='fas fa-spinner fa-spin'></i></h4>

    return (
        <Row className='justify-content-md-center'>
            <Col md={12}>
                <Navbar expand='md' variant='dark' bg='dark'>
                    <Navbar.Brand style={{paddingLeft: '15px'}}>
                        <h4>{billId ? `Bill ID: B${billId}` : 'Create Bill'}</h4>
                    </Navbar.Brand>
                    {/* {(billId && billState.charges) &&
                        <ListGroup.Item variant='warning'></ListGroup.Item>
                    } */}
                    {billId &&
                        <OverlayTrigger
                            placement={"right"}
                            overlay={<Tooltip><ul>{incompleteFields?.map(field => <li key={field}>{field}</li>)}</ul></Tooltip>}
                        >
                            <ProgressBar
                                now={percentComplete}
                                label={`${percentComplete}%`}
                                style={{width: '200px'}}
                                variant={percentComplete <= 33 ? 'danger' : percentComplete <= 66 ? 'warning' : percentComplete <= 99 ? 'primary' : 'success'}
                            >
                            </ProgressBar>
                        </OverlayTrigger>
                    }
                    {/* {invoiceIds?.length &&
                        <Badge bg='info' text='dark' style={{margin: '0px 5px'}}>
                            <h5>Invoices: {invoiceIds.map((invoiceId, index, arr) => {
                                if(frontEndPermissions.invoices.viewAny)
                                    return (
                                        <LinkContainer to={`/invoices/${invoiceId}`} style={{marginRight: '7px'}} key={invoiceId}>
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
                                if(frontEndPermissions.invoices.viewAny)
                                    return (
                                        <LinkContainer to={`/manifests/${manifestId}`} style={{marginRight: '7px'}} key={manifestId}>
                                            <a>{manifestId}</a>
                                        </LinkContainer>
                                    )
                                return `${manifestId}${index === arr.length - 1 ? '' : ', '}`
                            })}</h5>
                        </Badge>
                    } */}
                    <Navbar.Collapse className='justify-content-end' style={{paddingRight: '15px'}}>
                        {(!billId && permissions.createFull && persistFields) &&
                            <Nav>
                                <NavDropdown title='Persist Fields' menuVariant='dark' drop='start'>
                                    <ul style={{listStyleType: 'none', padding: '4px 10px'}}>
                                        {persistFields.sort((a, b) => a.label > b.label ? 1 : -1).map(persistField =>
                                            <li key={persistField.name}>
                                                <FormCheck
                                                    id={persistField.name}
                                                    name={persistField.name}
                                                    label={persistField.label}
                                                    checked={persistField.checked}
                                                    onChange={event => togglePersistField({name: event.target.name, checked: event.target.checked})}
                                                    style={{whiteSpace: 'nowrap'}}
                                                />
                                            </li>
                                        )}
                                    </ul>
                                </NavDropdown>
                            </Nav>
                        }
                        <ButtonGroup>
                            {(permissions.createFull || permissions.editDispatch) &&
                                <Button
                                    variant={applyRestrictions ? 'dark' : 'danger'}
                                    onClick={toggleRestrictions}
                                    style={{backgroundColor: applyRestrictions ? 'tomato' : 'black', color: applyRestrictions ? 'black' : 'white'}}
                                    title='Toggle restrictions'
                                >
                                    <i className={applyRestrictions ? 'fas fa-lock' : 'fas fa-unlock'}></i> {applyRestrictions ? 'Remove Time Restrictions' : 'Restore Time Restrictions'}
                                </Button>
                            }
                            {(billId && (permissions.createBasic || permissions.createFull)) &&
                                <Button
                                    variant='dark'
                                    onClick={toggleIsTemplate}
                                >
                                    <i className={`${isTemplate ? 'fas' : 'far'} fa-star`}></i>Template
                                </Button>
                            }
                            {(billId && (permissions.createBasic || permissions.createFull)) &&
                                <Button
                                    onClick={copyBill}
                                    title='Copy Bill'
                                    variant='dark'
                                ><i className='fas fa-copy'></i> Copy Bill</Button>
                            }
                        {billId &&
                            <Dropdown
                                align='end'
                            >
                                <Button
                                    href={billId ? `/bills/print/${billId}` : null}
                                    target='_blank'
                                    title='Print Bill'
                                    variant='dark'
                                ><i className='fas fa-print'></i> Print</Button>
                                {frontEndPermissions.invoices?.viewAny &&
                                    <Dropdown.Toggle
                                        id='print-button-split'
                                        split
                                        variant='dark'
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
                                }
                            </Dropdown>
                        }
                        </ButtonGroup>
                    </Navbar.Collapse>
                </Navbar>
            </Col>
            <Col md={12}>
                <Tabs id='bill-tabs' className='nav-justified' activeKey={location.hash} onSelect={key => {history.push(key)}}>
                    <Tab eventKey='#basic' title={<h4>Pickup/Delivery Info <i className='fas fa-map-pin'></i></h4>}>
                        <BasicTab
                            bill={bill}
                            charges={charges}
                            delivery={delivery}
                            packages={packages}
                            pickup={pickup}
                        />
                    </Tab>
                    {(billId ? permissions.viewDispatch : permissions.createFull) &&
                        <Tab eventKey='#dispatch' title={<h4>Dispatch <i className='fas fa-truck'></i></h4>}>
                            <DispatchTab
                                bill={bill}
                                delivery={delivery}
                                pickup={pickup}
                                // isDeliveryManifested={this.state.charges.some(charge => charge.lineItems.some(lineItem => lineItem.delivery_manifest_id))}
                                // isPickupManifested={this.state.charges.some(charge => charge.lineItems.some(lineItem => lineItem.pickup_manifest_id))}
                                isPickupManifested={false}
                                isDeliveryManifested={false}
                            />
                        </Tab>
                    }
                    {/* {(billId ? permissions.viewBilling : permissions.createFull) &&
                        <Tab eventKey='#billing' title={<h4>Billing  <i className='fas fa-credit-card'></i></h4>}>
                            <BillingTab
                                billDispatch={billDispatch}
                                billState={billState}
                                chargeState={chargeState}
                                generateCharges={generateCharges}
                            />
                        </Tab>
                    } */}
                    {/* {(permissions.viewActivityLog && billState.activityLog) &&
                        <Tab eventKey='#activity_log' title={<h4>Activity Log  <i className='fas fa-book-open'></i></h4>}>
                            <ActivityLogTab
                                activityLog={billState.activityLog}
                            />
                        </Tab>
                    } */}
                </Tabs>
            </Col>
            {(!billId && permissions.createBasic && !permissions.createFull) &&
                <Col md='auto'>
                    <FormCheck
                        name={'acceptTermsAndConditions'}
                        label={<p>I have read and agree to the <a href='' onClick={toggleAcceptTermsAndConditions}>terms and conditions</a></p>}
                        checked={acceptTermsAndConditions}
                        disabled={readOnly}
                        onChange={toggleAcceptTermsAndConditions}
                        type='switch'
                        style={{whiteSpace: 'nowrap'}}
                    />
                </Col>
            }
            <Col md={12} className='text-center'>
                <ButtonGroup>
                    {billId &&
                        <LinkContainer to={`/bills/${prevBillId}#${location.hash?.substr(1)}`}>
                            <Button variant='secondary' disabled={!prevBillId}>
                                <i className='fas fa-arrow-circle-left'></i> Back - {prevBillId}
                            </Button>
                        </LinkContainer>
                    }
                    {/* {getStoreButton()} */}
                    {billId &&
                        <LinkContainer to={`/bills/${nextBillId}#${location.hash?.substr(1)}`}>
                            <Button variant='secondary' disabled={!nextBillId}>
                                Next - {nextBillId} <i className='fas fa-arrow-circle-right'></i>
                            </Button>
                        </LinkContainer>
                    }
                </ButtonGroup>
            </Col>
            <Modal show={viewTermsAndConditions} onHide={toggleViewTermsAndConditions} size='lg'>
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
