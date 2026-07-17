import { nextTick, onBeforeUnmount, ref, watch, type Ref } from 'vue'

/**
 * Decide whether a dropdown should open below its anchor (default) or flip above when there isn't
 * room below. The menu itself is positioned with plain `position: absolute` inside the field, so it
 * follows the input on any layout/width change (no teleport, no stale fixed coordinates). Returns a
 * `placement` ref ('bottom' | 'top') to bind as a class on the menu.
 */
export function useFloatingMenu(open: Ref<boolean>, anchor: Ref<HTMLElement | null>) {
    const placement = ref<'bottom' | 'top'>('bottom')
    const MAX_H = 260 // menu's max height; the reference for the flip decision (stable across async loads)

    const decide = () => {
        const a = anchor.value
        if (!a) return
        const r = a.getBoundingClientRect()
        const spaceBelow = window.innerHeight - r.bottom
        const spaceAbove = r.top
        placement.value = spaceBelow < MAX_H && spaceAbove > spaceBelow ? 'top' : 'bottom'
    }

    const onResize = () => { if (open.value) decide() }

    watch(open, (v) => {
        if (v) {
            nextTick(decide)
            window.addEventListener('resize', onResize)
        } else {
            window.removeEventListener('resize', onResize)
        }
    })

    onBeforeUnmount(() => window.removeEventListener('resize', onResize))

    return { placement }
}
