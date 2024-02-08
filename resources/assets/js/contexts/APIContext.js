import React, { createContext, useContext } from 'react'
import APIService from '../services/APIService'

const APIContext = createContext()

export const APIProvider = ({children, history}) => {
    const apiService = new APIService(history);

    return (
        <APIContext.Provider value={apiService}>
            {children}
        </APIContext.Provider>
    )
}

export const useAPI = () => useContext(APIContext)
