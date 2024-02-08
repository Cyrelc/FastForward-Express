import {toast} from 'react-toastify'

const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content')
let mostRecentToastId = null

const displayErrorMessages = props => {
    return (
        <ul>
            {Object.keys(props).map(key => 
                <li key={key}>
                    {props[key][0]}
                </li>
            )}
        </ul>
    )
}

export default class ApiService {
    constructor(history) {
        this.history = history
    }

    async get(endpoint, errorCallback = null) {
        try {
            const response = await fetch(`${endpoint}`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                }
            })

            return await this._handleResponse(response, errorCallback)
        } catch (error) {
            console.error('GET Request Error:', error)
            throw error
        }
    }

    async post(endpoint, data, errorCallback = null) {
        try {
            const response = await fetch(`${endpoint}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify(data)
            })
            return await this._handleResponse(response, errorCallback)
        } catch (error) {
            console.error('POST Request Error:', error)
            throw error
        }
    }

    async put(endpoint, data, errorCallback = null) {
        try {
            const response = await fetch(`${endpoint}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify(data)
            })
            return await this._handleResponse(response, errorCallback)
        } catch (error) {
            console.error('PUT Request Error:', error)
            throw error
        }
    }

    async _handleResponse(response, errorCallback = null) {
        if(!response.ok) {
            const errorData = await response.json()

            if(response.status === 401 || response.message === 'CSRF token mismatch.')
                location.reload()
            switch(response.status) {
                case 404:
                    history.push('/error404')
                    break
                case 403:
                    responseText = JSON.parse(response.responseText)
                    if(responseText.message)
                        toast.error(responseText.message, {autoClose: false})
                    else
                        toast.error('Authenticated User does not have permission to perform the requested action', {autoClose: false})
                    break
                default:
                    if(mostRecentToastId)
                        toast.dismiss(mostRecentToastId)
                    mostRecentToastId = toast.error(displayErrorMessages(errorData.errors), {autoClose: false});
                    break
            }

            throw new Error('Server responded with an error', {cause: errorData})
        }

        return response.json()
    }
}
