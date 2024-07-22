import React, { createContext, useContext } from 'react'
import APIService from '../services/APIService'
import {useHistory} from 'react-router-dom'

const APIContext = createContext()

export const APIProvider = ({children}) => {
    const history = useHistory()
    const apiService = new APIService(history);

    return (
        <APIContext.Provider value={apiService}>
            {children}
        </APIContext.Provider>
    )
}

export const useAPI = () => useContext(APIContext)
