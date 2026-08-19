import { onBeforeUnmount } from 'vue'
import { onBeforeRouteLeave } from 'vue-router'
import { useDialog } from './dialog'

/**
 * Ask before abandoning unsaved work.
 *
 * Three ways out of a screen, and they need two different mechanisms:
 *
 *  - the in-app back button and the browser's back button are both SPA navigations, so one
 *    onBeforeRouteLeave guard covers them, and it can use the app's own confirm dialog
 *  - closing the tab, reloading, or following a link off the app only reaches `beforeunload`,
 *    where browsers show their OWN generic wording. Custom text has been ignored there for years,
 *    so there is nothing to pass; the callback's job is only to say "yes, ask"
 *
 * `isDirty` is a getter, not a ref, so callers can compare whatever they consider saveable state
 * (a serialized payload, a field-by-field fingerprint) instead of being pushed toward one shape.
 * It must return false once the work is saved — otherwise the guard fires on the way out of the
 * save that just succeeded.
 */
export function useUnsavedGuard(isDirty: () => boolean, message = '保存していない変更があります。破棄して移動しますか？') {
    const dialog = useDialog()

    onBeforeRouteLeave(async () => {
        if (!isDirty()) return true
        const answer = await dialog.ask(message)

        return !!answer?.value
    })

    const onBeforeUnload = (e: BeforeUnloadEvent) => {
        if (!isDirty()) return
        // both are needed: preventDefault() is the modern signal, returnValue the legacy one that
        // some browsers still read
        e.preventDefault()
        e.returnValue = ''
    }
    window.addEventListener('beforeunload', onBeforeUnload)
    onBeforeUnmount(() => window.removeEventListener('beforeunload', onBeforeUnload))
}
