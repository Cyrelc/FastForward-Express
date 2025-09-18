import React, {useEffect, useState} from 'react'
import {Card, InputGroup, ToggleButton, ToggleButtonGroup} from 'react-bootstrap'
import {ResponsiveBar} from '@nivo/bar'
import {DateTime} from 'luxon'
import {useAPI} from '../../contexts/APIContext'

export default function Charts(props) {
    const [chartData, setChartData] = useState([])
    const [keys, setKeys] = useState([])
    const [summationType, setSummationType] = useState('count')
    const [isLoading, setIsLoading] = useState(true)

    const {accountId} = props
    const api = useAPI()

    useEffect(() => {
        setIsLoading(true)
        if(!accountId)
            return
        const endDate = DateTime.now().set({hour: 0, minute: 0, second: 0, millisecond: 0}).startOf('month')
        const startDate = endDate.minus({months: 12})
        const data = {
            account_id: props.accountId,
            end_date: endDate.toFormat('yyyy-MM-dd'),
            start_date: startDate.toFormat('yyyy-MM-dd'),
            summationType
        }
        api.post(`/accounts/chart/${accountId}`, data)
            .then(response => {
                if(response.bills)
                    setChartData(Object.values(response.bills).map(value => {return value}))
                setKeys(response.keys)
                setIsLoading(false)
            })
    }, [accountId, summationType])

    return (isLoading
        ? <h4>Requesting data, please wait... <i className='fas fa-spinner fa-spin'></i></h4>
        :
        <Card>
            <Card.Header>
                <InputGroup>
                    <InputGroup.Text>View: </InputGroup.Text>
                    <ToggleButtonGroup type='radio' value={summationType} name='summationType' onChange={value => setSummationType(value)}>
                        <ToggleButton id='count' key='count' value='count' variant='outline-secondary'>Count</ToggleButton>
                        <ToggleButton id='amount' key='amount' value='amount' variant='outline-secondary'>Cost</ToggleButton>
                    </ToggleButtonGroup>
                </InputGroup>
            </Card.Header>
            <Card.Body>
                <div style={{height: '65vh', width: '90vw'}}>
                    <ResponsiveBar
                        data={chartData}
                        keys={keys}
                        indexBy='indexKey'
                        label={group => summationType === 'amount' ? group.value.toLocaleString('en-CA', {style: 'currency', currency: 'CAD'}) : group.value}
                        legends={[{
                            anchor: 'bottom-right',
                            dataFrom: 'keys',
                            direction: 'column',
                            justify: false,
                            translateX: 120,
                            translateY: 0,
                            itemsSpacing: 2,
                            itemWidth: 100,
                            itemHeight: 20,
                            itemDirection: 'left-to-right',
                            itemOpacity: 0.85,
                            symbolSize: 20,
                        }]}
                        margin={{top: 50, right: 160, bottom: 50, left: 60}}
                    />
                </div>
            </Card.Body>
        </Card>
    )
}
