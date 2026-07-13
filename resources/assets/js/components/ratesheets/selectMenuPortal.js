/**
 * react-select renders its menu inline by default, which is fine until the select sits inside a
 * scrolling box - the map's control column is `maxHeight: 85vh; overflow-y: auto`, so an inline menu
 * is clipped at the column's edge and its height pushes the column into scrolling.
 *
 * Spreading this onto a select renders the menu into document.body instead, positioned against the
 * control, so it can overhang the column freely. Every select inside that column needs it.
 */
export const menuPortalProps = {
    menuPortalTarget: typeof document !== 'undefined' ? document.body : null,
    menuPosition: 'fixed',
    // The map's own controls and the Bootstrap modals both stack above the page; the menu has to win.
    styles: {menuPortal: base => ({...base, zIndex: 9999})},
}
