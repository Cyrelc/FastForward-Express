import React, {Component} from 'react'
import {Alert, Button, Col, FormControl, InputGroup, Modal, Row} from 'react-bootstrap' 

export default class LinkLineItemModal extends Component {
    constructor(props) {
        super(props)
        this.state={
            targetObject: undefined,
            searchValue: undefined,
            targetId: undefined,
        }
        this.handleChange = this.handleChange.bind(this)
        this.searchLinkTo = this.searchLinkTo.bind(this)
        this.submitLinkTo = this.submitLinkTo.bind(this)
    }

    handleChange(event) {
        const {name, value, checked, type} = event.target
        this.setState({[name] : type === 'checkbox' ? checked : value})
    }

    searchLinkTo() {
        if (this.props.linkLineItemToType === 'Invoice') {
            makeAjaxRequest('/invoices/getModel/' + this.state.searchValue, 'GET', null, response => {
                response = JSON.parse(response)
                this.handleChange({target: {name: 'targetObject', type: 'object', value: response}})
                this.handleChange({target: {name: 'targetId', type: 'number', value: response.invoice.invoice_id}})
            })
        } else if (this.props.linkLineItemToType === 'Pickup Manifest' || this.props.linkLineItemToType == 'Delivery Manifest') {
            makeAjaxRequest('/manifests/getModel/' + this.state.searchValue, 'GET', null, response => {
                response = JSON.parse(response)
                this.handleChange({target: {name: 'targetObject', type: 'object', value: response}})
                this.handleChange({target: {name: 'targetId', type: 'number', value: response.manifest.manifest_id}})
            })
        }
    }

    submitLinkTo() {
        const data = {
            action: 'create_link',
            line_item_id: this.props.linkLineItemCell.getRow().getData().line_item_id,
            link_type: this.props.linkLineItemToType,
            link_to_target_id: this.state.targetId
        }

        makeAjaxRequest('/bills/manageLineItemLinks', 'POST', data, response => {
            this.props.linkLineItemCell.getRow().update(JSON.parse(response))

            this.props.handleChanges([
                {target: {name: 'showLinkLineItemModal', type: 'boolean', value: false}},
                {target: {name: 'linkLineItemCell', type: 'object', value: null}}
            ])
            this.handleChange({target: {name: 'searchValue', type: 'number', value: undefined}})
            this.handleChange({target: {name: 'targetObject', type: 'object', value: undefined}})
            this.handleChange({target: {name: 'targetId', type: 'number', value: undefined}})
        })
    }

    render() {
        return (
            <Modal show={this.props.show} onHide={() => this.props.handleChanges({target: {name: 'showLinkLineItemModal', type: 'boolean', value: false}})}>
                <Modal.Header closeButton><Modal.Title>Link Line Item to {this.props.linkLineItemToType}</Modal.Title></Modal.Header>
                <Modal.Body>
                    <Row>
                        <Col md={12}>
                            <InputGroup>
                                <InputGroup.Text>{this.props.linkLineItemToType} ID: </InputGroup.Text>
                                <FormControl
                                    name='searchValue'
                                    value={this.state.searchValue}
                                    onChange={this.handleChange}
                                />
                            </InputGroup>
                        </Col>
                    </Row>
                    {this.state.targetObject &&
                        <Row>
                            <Col md={12}>
                                {this.state.targetObject.manifest ?
                                    'Manifest ID: ' + this.state.targetObject.manifest.manifest_id :
                                    'Invoice ID: ' + this.state.targetObject.invoice.invoice_id
                                }
                            </Col>
                            <Col md={12}>
                                {this.state.targetObject.manifest ?
                                    'Driver: ' + this.state.targetObject.employee.contact.first_name + " " + this.state.targetObject.employee.contact.last_name :
                                    'Account: ' + this.state.targetObject.parent.name
                                }
                            </Col>
                            {(this.state.targetObject.invoice && this.state.targetObject.invoice.finalized) &&
                                <Col md={12}>
                                    <Alert variant='warning'>
                                        WARNING - Selected invoice has been finalized, this line item will be attached as an amendment
                                    </Alert>
                                </Col>
                            }
                        </Row>
                    }
                </Modal.Body>
                <Modal.Footer className='justify-content-md-center'>
                    <Button variant='light' onClick={() => props.handleChanges({target: {name: 'showLinkLineItemModal', type: 'boolean', value: false}})}>Cancel</Button>
                    <Button variant='warning' onClick={this.searchLinkTo} disabled={!this.state.searchValue || !this.state.searchValue.length}><i className='fas fa-search'></i> Search</Button>
                    <Button variant='success' onClick={this.submitLinkTo} disabled={!this.state.targetObject}><i className='fas fa-save'></i> Submit</Button>
                </Modal.Footer>
            </Modal>
        )
    }
}

