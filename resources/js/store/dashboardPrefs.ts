import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { useDebounceFn, useLocalStorage } from '@vueuse/core'

const DASHBOARD_CARDS_ORDER_STORAGE_KEY = 'dashboardCardsOrder:v2'
const DASHBOARD_CARDS_LAYOUT_STORAGE_KEY = 'dashboardCardsLayout:v2'
const DASHBOARD_CARDS_HEIGHTS_STORAGE_KEY = 'dashboardCardsHeights:v2'
const DASHBOARD_CARDS_GRID_LAYOUT_STORAGE_KEY = 'dashboardCardsGridLayout:v2'

export type DashboardGridLayout = {
    x?: number
    y?: number
    w?: number
    h?: number
}

type UserId = string | number | null | undefined

const isValidColSpan = (value: unknown): value is string =>
    typeof value === 'string' && /^col-span-[1-4]$/.test(value)

export const useDashboardPrefsStore = defineStore('dashboardPrefsStore', () => {
    const orderByUser = useLocalStorage<Record<string, string[]>>(DASHBOARD_CARDS_ORDER_STORAGE_KEY, {})
    const layoutByUser = useLocalStorage<Record<string, Record<string, string>>>(DASHBOARD_CARDS_LAYOUT_STORAGE_KEY, {})
    const heightsByUser = useLocalStorage<Record<string, Record<string, number>>>(DASHBOARD_CARDS_HEIGHTS_STORAGE_KEY, {})
    const gridLayoutByUser = useLocalStorage<Record<string, Record<string, DashboardGridLayout>>>(DASHBOARD_CARDS_GRID_LAYOUT_STORAGE_KEY, {})
    const currentUserKey = ref('guest')

    const normalizeUserKey = (userId: UserId) => userId === null || userId === undefined || userId === '' ? 'guest' : String(userId)

    const setActiveUser = (userId: UserId) => {
        currentUserKey.value = normalizeUserKey(userId)
    }

    const getUserRecord = <T extends object>(source: { value: Record<string, T> }, fallback: T): T => {
        return source.value[currentUserKey.value] ?? fallback
    }

    const setUserRecord = <T extends object>(source: { value: Record<string, T> }, value: T) => {
        source.value = {
            ...source.value,
            [currentUserKey.value]: value,
        }
    }

    const order = computed(() => orderByUser.value[currentUserKey.value] ?? [])
    const layout = computed(() => getUserRecord(layoutByUser, {} as Record<string, string>))
    const heights = computed(() => getUserRecord(heightsByUser, {} as Record<string, number>))
    const gridLayout = computed(() => getUserRecord(gridLayoutByUser, {} as Record<string, DashboardGridLayout>))

    const setOrder = (types: string[]) => {
        setUserRecord(orderByUser, types.filter((t) => typeof t === 'string' && t.length > 0))
    }

    const setColSpan = (type: string, col: string) => {
        if (typeof type !== 'string' || type.length === 0) return
        if (!isValidColSpan(col)) return
        setUserRecord(layoutByUser, { ...layout.value, [type]: col })
    }

    const setHeightNow = (type: string, height: number) => {
        if (typeof type !== 'string' || type.length === 0) return
        const h = Math.round(Number(height))
        if (!Number.isFinite(h) || h <= 0) return
        if (heights.value[type] === h) return
        setUserRecord(heightsByUser, { ...heights.value, [type]: h })
    }

    const setHeight = useDebounceFn(setHeightNow, 150)

    const isValidGridLayout = (value: DashboardGridLayout) => {
        const entries = [value.x, value.y, value.w, value.h].filter((v) => v !== undefined)
        return entries.every((v) => Number.isInteger(v) && Number(v) >= 0)
    }

    const setGridLayoutNow = (type: string, value: DashboardGridLayout) => {
        if (typeof type !== 'string' || type.length === 0) return
        if (!isValidGridLayout(value)) return
        setUserRecord(gridLayoutByUser, {
            ...gridLayout.value,
            [type]: {
                x: value.x,
                y: value.y,
                w: value.w,
                h: value.h,
            },
        })
    }

    const setGridLayout = useDebounceFn(setGridLayoutNow, 150)

    const setGridLayoutsNow = (values: Record<string, DashboardGridLayout>) => {
        const next = { ...gridLayout.value }
        for (const [type, value] of Object.entries(values)) {
            if (typeof type !== 'string' || type.length === 0) continue
            if (!isValidGridLayout(value)) continue
            next[type] = {
                x: value.x,
                y: value.y,
                w: value.w,
                h: value.h,
            }
        }
        setUserRecord(gridLayoutByUser, next)
    }

    const setGridLayouts = useDebounceFn(setGridLayoutsNow, 150)

    const getGridLayout = (type: string): DashboardGridLayout | undefined => {
        const stored = gridLayout.value?.[type]
        if (!stored || !isValidGridLayout(stored)) return undefined
        return stored
    }

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
        gridLayout,
        setActiveUser,
        setOrder,
        setColSpan,
        setHeight,
        setHeightNow,
        setGridLayout,
        setGridLayoutNow,
        setGridLayouts,
        setGridLayoutsNow,
        getGridLayout,
        applyLayoutToCards,
        applyOrderToCards,
    }
})
