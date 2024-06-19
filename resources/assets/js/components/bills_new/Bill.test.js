import React from 'react'
import {screen, waitFor, fireEvent, render} from '@testing-library/react'
import {useParams} from 'react-router-dom'
import Bill from './Bill'
import '@testing-library/jest-dom'
import {initialize} from '@googlemaps/jest-mocks'

// Mock the google object
beforeEach(() => {
    initialize()
});

jest.mock('../../contexts/APIContext', () => ({
    useAPI: jest.fn(() => ({
        get: jest.fn().mockResolvedValue({
            bill: {
                bill_id: null
            },
            permissions: {
                createFull: true
            }
        })
    }))
}))

test('renders Bill loading page', async () => {
    useParams.mockReturnValue({billId: null})
    render(<Bill />)

    await waitFor(() => {
        expect(screen.getByText(/Requesting data, please wait.../)).toBeInTheDocument()
    })
})

test('test Persist fields', async () => {
    useParams.mockReturnValue({billId: 'create'})
    render(<Bill />)

    await waitFor(() => {expect(screen.getByText(/Create Bill/)).toBeInTheDocument()})

    const persistFields = screen.getByText('Persist Fields')
    expect(persistFields).toBeInTheDocument()

    fireEvent.click(persistFields)
    const deliveryTypeCheckbox = screen.getByLabelText('Delivery Type')
    expect(deliveryTypeCheckbox).toBeInTheDocument()
    expect(deliveryTypeCheckbox).not.toBeChecked()
    fireEvent.click(deliveryTypeCheckbox)
    expect(deliveryTypeCheckbox).toBeChecked()

    const pickupDriverCheckbox = screen.getByLabelText('Pickup Driver')
    expect(pickupDriverCheckbox).toBeInTheDocument()
    expect(pickupDriverCheckbox).not.toBeChecked()
    fireEvent.click(pickupDriverCheckbox)
    expect(pickupDriverCheckbox).toBeChecked()

    const localStoragePersistFields = JSON.parse(localStorage.getItem('bill.persistFields'))
    localStoragePersistFields.forEach(persistField => {
        if(persistField.label == 'Delivery Type' || persistField.label == 'Pickup Driver')
            expect(persistField.checked).toBeTruthy()
        else
            expect(persistField.checked).not.toBeTruthy()
    })
})
