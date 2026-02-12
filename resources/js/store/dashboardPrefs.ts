import { defineStore } from 'pinia'
import { useDebounceFn, useLocalStorage } from '@vueuse/core'

const DASHBOARD_CARDS_ORDER_STORAGE_KEY = 'dashboardCardsOrder:v1'
const DASHBOARD_CARDS_LAYOUT_STORAGE_KEY = 'dashboardCardsLayout:v1'
const DASHBOARD_CARDS_HEIGHTS_STORAGE_KEY = 'dashboardCardsHeights:v1'

const isValidColSpan = (value: unknown): value is string =>
    typeof value === 'string' && /^col-span-[1-4]$/.test(value)

export const useDashboardPrefsStore = defineStore('dashboardPrefsStore', () => {
    const order = useLocalStorage<string[]>(DASHBOARD_CARDS_ORDER_STORAGE_KEY, [])
    const layout = useLocalStorage<Record<string, string>>(DASHBOARD_CARDS_LAYOUT_STORAGE_KEY, {})
    const heights = useLocalStorage<Record<string, number>>(DASHBOARD_CARDS_HEIGHTS_STORAGE_KEY, {})

    const setOrder = (types: string[]) => {
        order.value = types.filter((t) => typeof t === 'string' && t.length > 0)
    }

    const setColSpan = (type: string, col: string) => {
        if (typeof type !== 'string' || type.length === 0) return
        if (!isValidColSpan(col)) return
        layout.value = { ...layout.value, [type]: col }
    }

    const setHeightNow = (type: string, height: number) => {
        if (typeof type !== 'string' || type.length === 0) return
        const h = Math.round(Number(height))
        if (!Number.isFinite(h) || h <= 0) return
        if (heights.value[type] === h) return
        heights.value = { ...heights.value, [type]: h }
    }

    const setHeight = useDebounceFn(setHeightNow, 150)

    const applyLayoutToCards = <T extends { type: string; col: string }>(cards: T[]) => {
        for (const card of cards) {
            const stored = layout.value?.[card.type]
            if (isValidColSpan(stored)) card.col = stored
        }
    }

    const applyOrderToCards = <T extends { type: string }>(cards: T[]): T[] => {
        const storedOrder = order.value
        if (!storedOrder || storedOrder.length === 0) return cards

        const byType = new Map<string, T>()
        for (const card of cards) byType.set(card.type, card)

        const ordered: T[] = []
        for (const type of storedOrder) {
            const card = byType.get(type)
            if (card) ordered.push(card)
        }

        for (const card of cards) {
            if (!storedOrder.includes(card.type)) ordered.push(card)
        }

        return ordered
    }

    return {
        order,
        layout,
        heights,
        setOrder,
        setColSpan,
        setHeight,
        setHeightNow,
        applyLayoutToCards,
        applyOrderToCards,
    }
})
