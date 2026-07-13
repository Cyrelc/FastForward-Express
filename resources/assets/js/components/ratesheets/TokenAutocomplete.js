import React, {useCallback, useRef, useState} from 'react'

/**
 * Type-ahead for the two free-text fields a rule has: the equation, and the {{...}} placeholders in a
 * rule's name.
 *
 * The safety property that makes this usable on an equation: accepting a suggestion only ever splices
 * the identifier sitting under the caret. The rest of the string is never re-parsed, re-formatted or
 * rewritten, so autocomplete cannot turn a working equation into a broken one - the worst it can do is
 * insert a word. If the author ignores the dropdown entirely and keeps typing, nothing happens at all.
 *
 * Keys are only intercepted while the list is open: Enter would otherwise submit the modal, and
 * swallowing it when there is nothing to accept would leave the author unable to save.
 */
export function useTokenAutocomplete({value, onChange, matchToken, buildSuggestions, applyToken}) {
    const inputRef = useRef(null)
    const [open, setOpen] = useState(false)
    const [items, setItems] = useState([])
    const [activeIndex, setActiveIndex] = useState(0)
    const [token, setToken] = useState(null)

    const refresh = useCallback((text, caret) => {
        const found = matchToken(`${text ?? ''}`.slice(0, caret))

        if(!found) {
            setOpen(false)
            return
        }

        const matches = buildSuggestions(found.query)

        setItems(matches)
        setToken({...found, caret})
        setActiveIndex(0)
        setOpen(matches.length > 0)
    }, [matchToken, buildSuggestions])

    const accept = (item) => {
        if(!item || !token)
            return

        const {text, caret} = applyToken(value ?? '', token, item)

        onChange(text)
        setOpen(false)

        // The value lands via React state, so the caret has to be restored after that render or the
        // browser drops it to the end of the input - mid-equation, that is maddening.
        window.requestAnimationFrame(() => {
            const input = inputRef.current
            if(!input)
                return
            input.focus()
            input.setSelectionRange(caret, caret)
        })
    }

    return {
        inputRef,
        open,
        items,
        activeIndex,
        setActiveIndex,
        accept,
        close: () => setOpen(false),

        handleChange: (event) => {
            onChange(event.target.value)
            refresh(event.target.value, event.target.selectionStart)
        },

        // Moving the caret with the arrows/mouse changes which token we are on.
        handleCaretMove: (event) => refresh(event.target.value, event.target.selectionStart),

        handleKeyDown: (event) => {
            if(!open || !items.length)
                return

            if(event.key === 'ArrowDown') {
                event.preventDefault()
                setActiveIndex(index => (index + 1) % items.length)
            } else if(event.key === 'ArrowUp') {
                event.preventDefault()
                setActiveIndex(index => (index - 1 + items.length) % items.length)
            } else if(event.key === 'Enter' || event.key === 'Tab') {
                event.preventDefault()
                accept(items[activeIndex])
            } else if(event.key === 'Escape') {
                event.preventDefault()
                setOpen(false)
            }
        },
    }
}

export const SuggestionList = ({open, items, activeIndex, onPick, onHover}) => {
    if(!open || !items.length)
        return null

    return (
        <div style={{
            position: 'absolute',
            top: '100%',
            left: 0,
            right: 0,
            zIndex: 1080, // above the modal's own stacking context
            background: '#fff',
            border: '1px solid #ced4da',
            borderRadius: 4,
            boxShadow: '0 4px 12px rgba(0,0,0,0.15)',
            maxHeight: 220,
            overflowY: 'auto',
        }}>
            {items.map((item, index) => (
                <div
                    key={item.insert}
                    onMouseDown={event => {
                        // mousedown, not click: click fires after blur, by which point the caret is gone.
                        event.preventDefault()
                        onPick(item)
                    }}
                    onMouseEnter={() => onHover(index)}
                    style={{
                        padding: '4px 10px',
                        cursor: 'pointer',
                        background: index === activeIndex ? '#e6f7ff' : 'transparent',
                    }}
                >
                    <code style={{color: item.kind === 'function' ? '#6f42c1' : '#22863a', fontWeight: 600}}>
                        {item.label}
                    </code>
                    {item.description &&
                        <div className='text-muted' style={{fontSize: '0.75rem', lineHeight: 1.2}}>
                            {item.description}
                        </div>
                    }
                </div>
            ))}
        </div>
    )
}

/** The identifier being typed immediately before the caret, e.g. "tota" in "max(0, tota". */
export const matchIdentifier = (textBeforeCaret) => {
    const match = /([A-Za-z_][A-Za-z0-9_]*)$/.exec(textBeforeCaret)

    if(!match)
        return null

    return {start: textBeforeCaret.length - match[1].length, query: match[1]}
}

/** An unclosed {{ placeholder, e.g. "Fuel ({{dist" -> "dist". */
export const matchPlaceholder = (textBeforeCaret) => {
    const match = /\{\{\s*([A-Za-z0-9_.]*)$/.exec(textBeforeCaret)

    if(!match)
        return null

    return {start: match.index, query: match[1]}
}

export const spliceIdentifier = (value, token, item) => {
    const text = value.slice(0, token.start) + item.insert + value.slice(token.caret)

    return {text, caret: token.start + (item.caretOffset ?? item.insert.length)}
}

export const splicePlaceholder = (value, token, item) => {
    // Swallow a "}}" the author already typed, rather than producing "{{x}}}}".
    const after = value.slice(token.caret)
    const rest = after.startsWith('}}') ? after.slice(2) : after
    const inserted = `{{${item.insert}}}`

    return {text: value.slice(0, token.start) + inserted + rest, caret: token.start + inserted.length}
}
