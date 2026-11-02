import React, {useState} from 'react'
import {Alert, Button} from 'react-bootstrap'

// Official update instructions per browser. Chrome, Edge and Firefox ship their own timezone data, so updating
// (and restarting) the browser fixes it; Safari uses macOS's, so it takes a macOS update.
const updateGuides = [
    {name: 'Microsoft Edge', test: /Edg\//, url: 'https://support.microsoft.com/en-us/microsoft-edge/microsoft-edge-update-settings-af8aaca2-1b69-4870-94fe-18822dbb7ef1'},
    {name: 'Firefox', test: /Firefox\//, url: 'https://support.mozilla.org/en-US/kb/update-firefox-latest-release'},
    {name: 'Google Chrome', test: /Chrome\//, url: 'https://support.google.com/chrome/answer/95414'},
    {name: 'Safari (via macOS Software Update)', test: /Safari\//, url: 'https://support.apple.com/en-us/HT201541'},
]

export default function TimeZoneWarning() {
    const [dismissed, setDismissed] = useState(false)

    if(dismissed)
        return null

    const guide = updateGuides.find(guide => guide.test.test(navigator.userAgent))

    return (
        <Alert variant='danger' className='mb-0 rounded-0 text-center'>
            <Alert.Heading><i className='fas fa-exclamation-triangle'></i> Your browser's time zone information is out of date</Alert.Heading>
            <p>
                Times on this site may be shown off by an hour, and pickup or delivery times you choose may be saved incorrectly.
                Please update your browser, then fully close and reopen it (or restart your computer) and reload this page.
            </p>
            <p className='mb-0'>
                {guide ?
                    <a href={guide.url} target='_blank' rel='noopener noreferrer'>How to update {guide.name}</a> :
                    updateGuides.map((guide, index) => <span key={guide.name}>{index > 0 && ' · '}<a href={guide.url} target='_blank' rel='noopener noreferrer'>{guide.name}</a></span>)
                }
                <Button variant='outline-danger' size='sm' className='ms-3' onClick={() => setDismissed(true)}>Hide until next visit</Button>
            </p>
        </Alert>
    )
}
