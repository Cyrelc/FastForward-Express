jest.mock('react-router-dom', () => ({
    ...jest.requireActual('react-router-dom'),
    useLocation: jest.fn(() => ({
        hash: jest.fn()
    })),
    useHistory: jest.fn(() => ({
        push: jest.fn()
    })),
    useParams: jest.fn()
}))

jest.mock('./contexts/UserContext', () => ({
    useUser: jest.fn(() => ({
        authenticatedUser: {
            user_id: 2,
            account_users: [],
            contact: {
                address: {},
                contact_id: 368,
                display_name: 'Anthony Stark',
                email_addresses: [],
                first_name: 'Tony',
                last_name: 'Stark',
                phone_numbers: [],
                position: 'CEO',
                preferred_name: 'Tony Stark',
                pronouns: ["he/him/his"],
            },
            employee: {employee_id: 1},
            front_end_permissions: {
                bills: {
                    create: true
                }
            },
            is_impersonating: false
        },
        settings: {
            use_imperial_default: false
        }
    }))
}))

// jest.mock('./contexts/APIContext', () => ({
//     useAPI: jest.fn(() => ({
//         get: jest.fn().mockResolvedValue({
//             data: 'mock-data'
//         })
//     }))
// }))

document.querySelector = jest.fn().mockImplementation((selector) => {
    if (selector == 'meta[name="csrf-token"]') {
        return {
            getAttribute: jest.fn().mockReturnValue('mock-csrf-token')
        }
    }
    return null;
})
