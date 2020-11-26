import React, {Component, createRef} from 'react'
import {Button, Card, Col, InputGroup, Row} from 'react-bootstrap'
import Select from 'react-select'
import DatePicker from 'react-datepicker'
import {ReactTabulator} from 'react-tabulator'

export default class GenerateInvoices extends Component {
    constructor() {
        super()
        this.state = {
            accounts: [],
            invoiceIntervals: [],
            startDate: new Date(),
            endDate: new Date(),
            selectedInvoiceIntervals: [],
            tableRef: createRef()
        }
        this.handleChange = this.handleChange.bind(this)
        this.refreshAccounts = this.refreshAccounts.bind(this)
        this.store = this.store.bind(this)
    }

    componentDidMount() {
        const currentDate = new Date()
        const firstDayOfPreviousMonth = new Date(currentDate.getFullYear(), currentDate.getMonth() - 1, 1)
        //note the following line MODIFIES currentDate - so if you use it subsequently, beware!!
        const lastDayOfPreviousMonth = new Date(currentDate.moveToFirstDayOfMonth().setHours(-1))
        makeAjaxRequest('/getList/selections/invoice_interval', 'GET', null, response => {
            response = JSON.parse(response)
            this.setState({
                invoiceIntervals: response,
                startDate: firstDayOfPreviousMonth,
                endDate: lastDayOfPreviousMonth,
            })
        })
    }

    handleChange(event) {
        const {name, type, checked, value} = event.target
        this.setState({[name]: type === 'checkbox' ? checked : value}, this.refreshAccounts)
    }

    refreshAccounts() {
        if(this.state.selectedInvoiceIntervals === null || this.state.selectedInvoiceIntervals.length === 0 || !this.state.startDate || !this.state.endDate) {
            this.setState({accounts: []})
            return
        }

        const data = {
            invoice_intervals: this.state.selectedInvoiceIntervals.map(interval => {return interval.value}),
            start_date: this.state.startDate.toLocaleString('en-US'),
            end_date: this.state.endDate.toLocaleString('en-US')
        }
        makeAjaxRequest('/invoices/getAccountsToInvoice', 'POST', data, response => {
            response = JSON.parse(response)
            toastr.clear()
            this.setState({accounts: response})
        })
    }

    store() {
        if(this.state.tableRef.current === undefined || this.state.tableRef.current.table.getSelectedData().length === 0) {
            toastr.error('Please select at least one account to invoice')
            return
        }
        const data = {
            accounts: this.state.tableRef.current.table.getSelectedData().map(account => {return account.account_id}),
            start_date: this.state.startDate.toLocaleString('en-US'),
            end_date: this.state.endDate.toLocaleString('en-US')
        }
        makeAjaxRequest('/invoices/store', 'POST', data, response => {
            toastr.clear()
            toastr.success('Successfully generated invoices', 'Success', {
                'progressBar' : true,
                'showDuration': 500,
                'onHidden': window.location = '/app/invoices',
                'positionClass': 'toast-top-center'
            })
        })
    }

    render() {
        function isAccountValidForInvoicing(cell) {
            const data = cell.getData()
            if(data.bill_count === 0) {
                cell.getElement().style.backgroundColor = 'salmon'
                return 'False - No valid bills'
            } else if(data.incomplete_bill_count > 0 || data.skipped_bill_count > 0 || data.legacy_bill_count > 0) {
                cell.getElement().style.backgroundColor = 'gold'
                const incompleteBills = data.incomplete_bill_count > 0 ? ' - Incomplete Bills (' + data.incomplete_bill_count + ')' : ''
                const skippedBills = data.skipped_bill_count > 0 ? ' - Skipped Bills (' + data.skipped_bill_count + ')' : ''
                const legacyBills = data.legacy_bill_count > 0 ? ' - Legacy Bills (' + data.legacy_bill_count + ')' : ''
                return 'Warning' + incompleteBills + skippedBills + legacyBills
            } else {
                cell.getElement().style.backgroundColor = 'mediumseagreen'
                return 'True'
            }
        }

        const columns = [
            {title: 'Valid', formatter: isAccountValidForInvoicing},
            {title: 'Account Id', field: 'account_id', sorter: 'number'},
            {title: 'Account Number', field: 'account_number'},
            {title: 'Account Name', field: 'name'},
            {title: 'Invoice Interval', field: 'invoice_interval'},
            {title: 'Completed Bills', field: 'bill_count', formatter: (cell) => {
                if(cell.getValue() === 0)
                    cell.getElement().style.backgroundColor = 'salmon'
                return cell.getValue()
            }, sorter: 'number'},
            {title: 'Incomplete Bills', field: 'incomplete_bill_count', formatter: 'link', formatterParams: {url: cell => {return '/app/bills?filter[charge_account_id]=' + cell.getRow().getData().account_id + '&filter[percentage_complete]=,1'}}, sorter: 'number'},
            {title: 'Skipped Bills', field: 'skipped_bill_count', formatter: 'link', formatterParams: {url: cell => {return '/app/bills?filter[charge_account_id]=' + cell.getRow().getData().account_id + '&filter[skip_invoicing]=1'}}, sorter: 'number'},
            {title: 'Legacy Bills', field: 'legacy_bill_count', formatter: 'link', formatterParams: {url: cell => {return '/app/bills?filter[charge_account_id]=' + cell.getRow().getData().account_id + '&filter[time_pickup_scheduled]=,' + this.state.startDate.toISOString().split('T')[0] + '&filter[invoiced]=0'}}, sorter: 'number'}
        ]

        return (
            <Card>
                <Card.Header>
                    <Row className='justify-content-md-center'>
                        <h3>Generate Invoices</h3>
                    </Row>
                </Card.Header>
                <Card.Body>
                    <Row>
                        <Col md={5}>
                            <InputGroup>
                                <InputGroup.Prepend><InputGroup.Text>Invoice Interval</InputGroup.Text></InputGroup.Prepend>
                                <Select
                                    options={this.state.invoiceIntervals}
                                    value={this.state.selectedInvoiceIntervals}
                                    onChange={value => this.handleChange({target: {name: 'selectedInvoiceIntervals', type: 'object', value: value}})}
                                    isMulti
                                />
                            </InputGroup>
                        </Col>
                        <Col md={5}>
                            <InputGroup>
                                <InputGroup.Prepend><InputGroup.Text>Start Date: </InputGroup.Text></InputGroup.Prepend>
                                <DatePicker
                                    className='form-control'
                                    dateFormat='MMMM d, yyyy'
                                    isClearable
                                    placeholderText='After'
                                    selected={this.state.startDate}
                                    onChange={value => this.handleChange({target: {name: 'startDate', type: 'date', value: value}})}
                                    selectsStart
                                    endDate={this.state.endDate}
                                />
                                <InputGroup.Append><InputGroup.Text> End Date: </InputGroup.Text></InputGroup.Append>
                                <DatePicker
                                    className='form-control'
                                    dateFormat='MMMM d, yyyy'
                                    isClearable
                                    placeholderText='Before'
                                    selected={this.state.endDate}
                                    onChange={value => this.handleChange({target: {name: 'endDate', type: 'date', value: value}})}
                                    selectsEnd
                                    startDate={this.state.startDate}
                                    minDate={this.state.startDate}
                                />
                            </InputGroup>
                        </Col>
                        <Col md={2}>
                            <Button variant='primary' disabled={this.state.accounts.length === 0} onClick={this.store}>Generate Invoices</Button>
                        </Col>
                    </Row>
                </Card.Body>
                <Card.Footer>
                    <p>The following accounts fit the selected criteria and have bills that are yet to be invoiced:</p>
                    {
                        this.state.accounts.length === 0 ?
                        <p style={{color: 'red'}}>Currently no accounts are selected to invoice</p> :
                        <ReactTabulator
                            ref={this.state.tableRef}
                            columns={columns}
                            data={this.state.accounts}
                            dataLoaded={() => {
                                const table = this.state.tableRef.current.table
                                table.rowManager.rows.map(row => {
                                    const data = row.getData()
                                    if(data.bill_count > 0 && data.incomplete_bill_count === 0 && data.skipped_bill_count === 0 && data.legacy_bill_count === 0)
                                        table.selectRow(row)
                                    })
                            }}
                            initialSort='account_number'
                            options={{
                                layout: 'fitColumns',
                                maxHeight: '80vh'
                            }}
                            selectable={true}
                            selectableCheck={row => {
                                return row.getData().bill_count > 0
                            }}
                        />
                    }
                </Card.Footer>
            </Card>
        )
    }
}

