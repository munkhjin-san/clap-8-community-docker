import { inject, onBeforeUnmount, provide, type InjectionKey } from 'vue'

/**
 * A row that has been composed but not committed yet.
 *
 * The permission editors all work the same way: pick subjects, tick the flags, press ＋追加 — and
 * only that press puts the row into the definition. Pressing 保存 with a half-composed row used to
 * discard it without a word, which people hit repeatedly in real use because the row looks finished
 * on screen.
 */
export type FlowDraft = {
    /**
     * True when something is staged that a save would silently drop.
     *
     * It must mean "picks that would be lost", NOT "the ＋追加 button is enabled": for the subject
     * types with nothing to pick (作成者, 全員, プロジェクトメンバー…) that button is always enabled, so
     * keying off it would report a pending draft on every save and train people to ignore the warning.
     */
    pending: () => boolean
    /** how to name this section to the user */
    label: string
    /** the builder tab holding it */
    tab: string
    /** optional extra step to bring it on screen (a sub-tab, a scroll) */
    reveal?: () => void
}

type Registry = { register: (d: FlowDraft) => void; firstPending: () => FlowDraft | null }
const KEY: InjectionKey<Registry> = Symbol('flowDrafts')

/** Call from the screen that owns 保存 (the app builder). */
export function provideFlowDrafts(): Registry {
    const drafts = new Set<FlowDraft>()
    const registry: Registry = {
        register: (d) => {
            drafts.add(d)
            // runs in the registering child's setup, so the entry leaves with that child
            onBeforeUnmount(() => drafts.delete(d))
        },
        firstPending: () => [...drafts].find((d) => d.pending()) ?? null,
    }
    provide(KEY, registry)

    return registry
}

/** Call from any editor that stages a row before committing it. No-op outside a provider. */
export function useFlowDraft(draft: FlowDraft): void {
    inject(KEY, null)?.register(draft)
}
