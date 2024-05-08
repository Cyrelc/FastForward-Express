import React, {useState} from 'react'

export default function useImportFromRatesheet() {
    const [importRatesheet, setImportRatesheet] = useState(undefined)
    const [importType, setImportType] = useState(undefined)
    const [ratesheets, setRatesheets] = useState([])
    const [selectedImports, setSelectedImports] = useState([])
    const [showImportModal, setShowImportModal] = useState(false)
    const [showReplaceModal, setShowReplaceModal] = useState(false)

    const reset = () => {
        // setAddAll(false)
        // setReplaceAll(false)
        setSelectedImports([])
        setShowImportModal(false)
    }

    return {
        importRatesheet,
        importType,
        ratesheets,
        reset,
        selectedImports,
        setImportRatesheet,
        setImportType,
        setRatesheets,
        setSelectedImports,
        setShowImportModal,
        setShowReplaceModal,
        showImportModal,
        showReplaceModal,
    }
}
