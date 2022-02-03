import React from 'react'
import {Card} from 'react-bootstrap'

const dragonArray = [
    '/images/page404/dragon-head-evil-legend-myth-svgrepo-com.svg',
    '/images/page404/dragon-with-wings-monster-legend-myth-svgrepo-com.svg',
    '/images/page404/japanese-dragon-svgrepo-com.svg',
    '/images/page404/mode-standard-dragon-svgrepo-com.svg',
    '/images/page404/walking-dragon-legend-myth-folklore-svgrepo-com.svg'
];

const dragon = dragonArray[Math.floor(Math.random() * dragonArray.length)]

export default function PageNotFound(props) {
    return (
        <Card style={{textAlign: 'center', verticalAlign: 'middle'}}>
            <Card.Header>
                <h4>404 - Whoops! We can't find that page. Please try a different request</h4>
            </Card.Header>
            <Card.Body>
                <img src={dragon} alt='A dragon ate your page...' width="400px" height="500px" />
            </Card.Body>
            <Card.Footer>
                A dragon ate your page... (or it never existed in the first place). Please try again
            </Card.Footer>
        </Card>
    );
}

