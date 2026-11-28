/**
 * Browser storage (localStorage) namespaced to the logged in user, so people sharing a browser each keep their own
 * table views (filters, columns, grouping, sorting). This separates users, it does not secure anything: anyone using
 * the browser can read localStorage, so never store anything sensitive here.
 *
 * Storage can be unavailable (private windows, blocked site data), so failures fall back to "nothing stored".
 */
const userStorageKey = (userId, key) => `user.${userId}.${key}`

export const getUserStorageItem = (userId, key) => {
    try {
        return localStorage.getItem(userStorageKey(userId, key))
    } catch(error) {
        return null
    }
}

export const setUserStorageItem = (userId, key, value) => {
    try {
        localStorage.setItem(userStorageKey(userId, key), value)
    } catch(error) {}
}

export const removeUserStorageItem = (userId, key) => {
    try {
        localStorage.removeItem(userStorageKey(userId, key))
    } catch(error) {}
}
