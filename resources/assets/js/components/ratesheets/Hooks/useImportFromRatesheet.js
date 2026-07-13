import React, {useState} from 'react'

export default function useImportFromRatesheet() {
    const [importRatesheet, setImportRatesheet] = useState(undefined)
    const [importType, setImportType] = useState(undefined)
    const [ratesheets, setRatesheets] = useState([])
    const [selectedImports, setSelectedImports] = useState([])
    const [showImportModal, setShowImportModal] = useState(false)
    const [showReplaceModal, setShowReplaceModal] = useState(false)

    // importType is what opens the import modal (it says which kind of thing is being imported), so
    // clearing it here is what closes it. Back when import was its own tab, the type was a dropdown
    // that stayed put between imports, and reset deliberately left it alone.
    const reset = () => {
        setImportType(undefined)
        setImportRatesheet(undefined)
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
