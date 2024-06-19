import React from 'react'
import {render, screen, fireEvent} from '@testing-library/react'
import BillingTab from './BillingTab'

test('renders BillingTab component and adds a new Charge', () => {
    render(<BillingTab />)

    const addButton = screen.getByText(/Add Charge/i)
    expect(addButton).toBeInTheDocument()

    fireEvent.click(addButton)

    const removeButton = screen.getByText(/Remove Charge/)
    expect(removeButton).toBeInTheDocument()
})
