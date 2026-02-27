import type { InjectionKey, Ref } from 'vue'

export type ExpansionPanelValue = string | number

export type ExpansionGridModelValue = ExpansionPanelValue | null

export interface ExpansionGridContext {
    cols: Ref<number>
    activeValue: Ref<ExpansionGridModelValue>
    isActive: (value: ExpansionPanelValue) => boolean
    setActive: (value: ExpansionGridModelValue) => void
    toggle: (value: ExpansionPanelValue) => void

    register: (value: ExpansionPanelValue) => void
    unregister: (value: ExpansionPanelValue) => void
    isLastRow: (value: ExpansionPanelValue) => boolean
}

export const EXPANSION_GRID_KEY: InjectionKey<ExpansionGridContext> = Symbol('ExpansionGrid')
