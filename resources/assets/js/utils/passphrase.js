import WORDS from './passphrase_words'

/**
 * A uniformly random integer in [0, max), from the browser's cryptographic RNG. Values that would bias the modulo
 * are drawn again.
 */
function randomInt(max) {
    const limit = Math.floor(0x100000000 / max) * max
    const value = new Uint32Array(1)
    do {
        crypto.getRandomValues(value)
    } while(value[0] >= limit)
    return value[0] % max
}

/**
 * A passphrase of random words, each capitalised on its own coin flip, e.g. "Crusher velvet Unpack Gloomy".
 * Generated in the browser: the password never leaves it until the user saves it.
 *
 * Drawn again until it is long enough and has at least one capital, which App\Rules\Password requires. That costs
 * under 0.1 bit; 4 words from 7268 carry about 51.3 bits, plus about 4 bits of capitalisation.
 */
export function generatePassphrase({wordCount = 4, minLength = 20, separator = ' '} = {}) {
    while(true) {
        const words = Array.from({length: wordCount}, () => {
            const word = WORDS[randomInt(WORDS.length)]
            return randomInt(2) ? word[0].toUpperCase() + word.slice(1) : word
        })
        const passphrase = words.join(separator)

        if(passphrase.length >= minLength && /[A-Z]/.test(passphrase))
            return passphrase
    }
}
