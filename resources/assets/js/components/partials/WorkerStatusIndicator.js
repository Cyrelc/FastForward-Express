import React, {useEffect, useState} from 'react'
import {OverlayTrigger, Tooltip} from 'react-bootstrap'
import {DateTime} from 'luxon'

import {useAPI} from '../../contexts/APIContext'

export default function WorkerStatusIndicator(props) {
    const {
        refreshInterval = 60000, // Default 60 seconds
        showText = true,
        size = 'small' // 'small' or 'large'
    } = props

    const [workerStatus, setWorkerStatus] = useState(null)
    const [lastFetchTime, setLastFetchTime] = useState(null)

    const api = useAPI()

    useEffect(() => {
        const fetchWorkerStatus = () => {
            api.get('/api/workers/status')
                .then(response => {
                    setWorkerStatus(response)
                    setLastFetchTime(DateTime.now())
                })
                .catch((error) => {
                    console.error('Failed to fetch worker status:', error)
                    setWorkerStatus({overall: {status: 'unknown'}})
                    setLastFetchTime(DateTime.now())
                })
        }

        fetchWorkerStatus()
        const interval = setInterval(fetchWorkerStatus, refreshInterval)
        return () => clearInterval(interval)
    }, [refreshInterval])

    const getWorkerStatusColor = () => {
        if (!workerStatus || !workerStatus.overall) return 'grey'
        switch(workerStatus.overall.status) {
            case 'healthy': return 'green'
            case 'recovered': return 'lightgreen'
            case 'degraded': return 'yellow'
            case 'unhealthy': return 'red'
            default: return 'grey'
        }
    }

    const getWorkerStatusText = () => {
        if (!workerStatus || !workerStatus.overall) return 'Unknown'
        switch(workerStatus.overall.status) {
            case 'healthy': return 'Healthy'
            case 'recovered': return 'Recovered'
            case 'degraded': return 'Degraded'
            case 'unhealthy': return 'Unhealthy'
            default: return 'Unknown'
        }
    }

    const getTooltipContent = () => {
        const lastCacheUpdate = workerStatus?.health?.checked_at_human || 'Unknown'
        const lastFetch = lastFetchTime ? lastFetchTime.toFormat('yyyy-MM-dd HH:mm:ss') : 'Not yet fetched'
        
        return (
            <div style={{textAlign: 'left'}}>
                <div><strong>Last Frontend Fetch:</strong></div>
                <div style={{marginBottom: '8px'}}>{lastFetch}</div>
                <div><strong>Last Cache Update:</strong></div>
                <div>{lastCacheUpdate}</div>
            </div>
        )
    }

    const indicatorSize = size === 'large' ? '16px' : '12px'
    const fontSize = size === 'large' ? '1em' : '0.9em'

    const renderTooltip = (props) => (
        <Tooltip id="worker-status-tooltip" {...props}>
            {getTooltipContent()}
        </Tooltip>
    )

    return (
        <OverlayTrigger
            placement="bottom"
            overlay={renderTooltip}
        >
            <div style={{display: 'flex', alignItems: 'center', gap: size === 'large' ? '8px' : '5px', cursor: 'pointer'}}>
                <div style={{
                    width: indicatorSize,
                    height: indicatorSize,
                    borderRadius: '50%',
                    backgroundColor: getWorkerStatusColor(),
                    boxShadow: `0 0 5px ${getWorkerStatusColor()}`
                }}></div>
                {showText && (
                    <span style={{fontSize: fontSize, fontWeight: 'bold'}}>{getWorkerStatusText()}</span>
                )}
            </div>
        </OverlayTrigger>
    )
}
