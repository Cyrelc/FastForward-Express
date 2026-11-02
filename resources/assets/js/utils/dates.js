import {DateTime} from 'luxon'

/**
 * Two kinds of date cross the wire, and they must never be mixed:
 *
 *  - Instants: a moment in time (scheduled pickup, delivered, updated_at...). The server sends ISO 8601 with its
 *    UTC offset; send them back with Date.toISOString(). Display converts to the browser's local time.
 *  - Calendar dates: a day with no time attached (birth date, expiry, report ranges). Always plain 'YYYY-MM-DD'.
 *    Never hand one to `new Date()`, which reads a date-only string as UTC midnight - the previous evening
 *    anywhere west of Greenwich.
 */

/** 'YYYY-MM-DD' (or a longer datetime string, whose date part is used) -> Date at local midnight on that day */
export const parseCalendarDate = value => {
    if(!value)
        return null
    const date = DateTime.fromISO(String(value).substring(0, 10))
    return date.isValid ? date.toJSDate() : null
}

/** Date -> 'YYYY-MM-DD' for the day the user sees */
export const formatCalendarDate = date => date ? DateTime.fromJSDate(date).toISODate() : ''

/** Server instant (ISO 8601 with offset) -> Luxon DateTime in the user's local time */
export const parseInstant = value => value ? DateTime.fromISO(value) : null

/** Server instant (ISO 8601 with offset) -> display string in the user's local time */
export const formatInstant = (value, format = 'yyyy-MM-dd HH:mm') => {
    const instant = parseInstant(value)
    return instant?.isValid ? instant.toFormat(format) : ''
}

/** The browser's own IANA timezone name, e.g. 'America/Edmonton' */
export const browserTimeZone = () => Intl.DateTimeFormat().resolvedOptions().timeZone

// A browser that picked up the computer's timezone properly reports a location ('America/Edmonton'). A bare offset
// ('Etc/GMT+7', shown as "GMT-07:00"), UTC or an unknown zone means it is running on a stale zone it cached earlier.
const LOCATION_ZONE = /^(Africa|America|Antarctica|Arctic|Asia|Atlantic|Australia|Canada|Europe|Indian|Pacific|US)\//

/**
 * True when this browser's timezone can't be trusted, so it will show times off and save times the user picks shifted:
 *  - its zone isn't a location (see LOCATION_ZONE), or
 *  - the offset Date objects actually use disagrees with the zone it reports, or
 *  - its rules disagree with the server's, which are the authority (see AuthenticatedUserResource::timeZoneReference)
 */
export const isBrowserTimeZoneDataStale = (reference, timeZone = browserTimeZone(), dateOffset = -new Date().getTimezoneOffset()) => {
    if(!timeZone || !LOCATION_ZONE.test(timeZone))
        return true

    const reportedZoneNow = DateTime.now().setZone(timeZone)
    if(reportedZoneNow.isValid && reportedZoneNow.offset !== dateOffset)
        return true

    return (reference ?? []).some(({time_zone, timestamp, offset}) => {
        const browserTime = DateTime.fromSeconds(timestamp, {zone: time_zone})
        return browserTime.isValid && browserTime.offset !== offset
    })
}
