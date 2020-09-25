import React from 'react'
import { ReactTabulator } from 'react-tabulator'
import { Row, Col, Button } from 'react-bootstrap'

export default class Ratesheets extends React.Component {
    constructor() {
        super()
        this.state = {
            ratesheets: []
        }
    }

    componentDidMount() {
        document.title = 'Ratesheets - ' + document.title
        fetch('/ratesheets/REST/index')
        .then(response => {return response.json()})
        .then(data => this.setState({ratesheets: data}));
    }

    render() {
        const columns = [
            {title: 'Ratesheet ID', field: 'ratesheet_id', formatter:'link', formatterParams:{labelField:'ratesheet_id', urlPrefix:'/app/ratesheets/edit/'}, width: 150},
            {title: 'Name', field: 'name', width: 150}
        ]
        return(
            <Row md={11} className='justify-content-md-center'>
                <Col md={3}>
                    <Button href='/ratesheets/create'>Create New Ratesheet</Button>
                </Col>
                <Col md={12} className='d-flex justify-content-center'>
                    <ReactTabulator 
                        columns={columns}
                        data={this.state.ratesheets}
                        options={{layout: 'fitColumns'}}
                    />
                </Col>
            </Row>
        )
    }
}

