
import React from 'react'
import {screen, waitFor, fireEvent, render} from '@testing-library/react'
import {useParams} from 'react-router-dom'
import BasicTab from './BasicTab'
import '@testing-library/jest-dom'
import {initialize} from '@googlemaps/jest-mocks'

beforeEach(() => {
    initialize()
})

test('renders Bill page for create', async () => {
    useParams.mockReturnValue({billId: 'create'})
    render(<BasicTab
        bill={{
            accounts: [{'name': 'Charles Account', 'account_id': 12345, 'label': 'XKCD - Charles Account'}]
        }}
        charges={{
            chargeAcccount: {},
            invoiceIds: []
        }}
        delivery={{
            account: {}
        }}
        packages={{
            packageArray: []
        }}
        pickup={{
            account: {}
        }}
    />)

    const labelsFound = [/Proof of Delivery Required/, /Package is smaller than 30 cm/, /Is a pallet/, /Use Imperial Measurements/]
    const textFound = [/Count/, /Length/, /Width/, /Height/, /Total Weight/, /Cubed Weight/]
    await waitFor(() => {expect(screen.getByText('Pickup', {selector: 'h4'})).toBeInTheDocument()})
    expect(screen.getByText('Delivery', {selector: 'h4'})).toBeInTheDocument()
    expect(screen.getAllByLabelText(/Location In Mall/)).toHaveLength(2)
    expect(screen.getAllByText('Search', {selector: 'label'})).toHaveLength(2)
    //Account selector only shows up when accounts array has length greater than 0
    expect(screen.getAllByText('Account', {selector: 'label'})).toHaveLength(2)
    expect(screen.getAllByText('Manual', {selector: 'label'})).toHaveLength(2)
    labelsFound.forEach(label => {
        expect(screen.getByLabelText(label)).toBeInTheDocument()
    })
    textFound.forEach(text => {
        expect(screen.getByText(text)).toBeInTheDocument()
    })
})

test('packages are hidden when packageIsMinimum is clicked', async() => {
    useParams.mockReturnValue({billId: 'create'})
    render(<BasicTab
        bill={{
            accounts: [{'name': 'Charles Account', 'account_id': 12345, 'label': 'XKCD - Charles Account'}]
        }}
        charges={{
            chargeAcccount: {},
            invoiceIds: []
        }}
        delivery={{
            account: {}
        }}
        packages={{
            packageArray: [],
            packageIsMinimum: true,
        }}
        pickup={{
            account: {}
        }}
    />)

    const labelsNotFound = [/Count/, /Length/, /Width/, /Height/, /Total Weight/, /Cubed Weight/]

    await waitFor(() => {expect(screen.getByText('Pickup', {selector: 'h4'})).toBeInTheDocument()})
    labelsNotFound.forEach(label => {
        expect(screen.queryByText(label)).not.toBeInTheDocument()
    })
    expect(screen.getByLabelText(/Package is smaller than/)).toBeChecked()
})
