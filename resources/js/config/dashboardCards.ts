import { DashboardCard } from '@/interface/dashboard'
import { Message, Task } from '@/interface/globalInterface'
import { CustomForm } from '@/interface/customFormInterface'
import { Post } from '@/interface/postInterface'
import { Asset } from '@/interface/assetInterface'
import { CalendarRecord } from '@/interface/calendarInterface'
import type { Component } from 'vue'
import { markRaw } from 'vue'

// Component imports
import DashboardMessageLayout from '@/components/Dashboard/Layout/DashboardMessageLayout.vue'
import DashboardTaskLayout from '@/components/Dashboard/Layout/DashboardTaskLayout.vue'
import DashboardSurvey from '@/components/Dashboard/Layout/DashboardSurvey.vue'
import DashboardGoal from '@/components/Dashboard/Layout/DashboardGoal.vue'
import DashboardChallenge from '@/components/Dashboard/Layout/DashboardChallenge.vue'
import DashboardAsset from '@/components/Dashboard/Layout/DashboardAsset.vue'
import DashboardSchedule from '@/components/Dashboard/Layout/DashboardSchedule.vue'
import DashboardPersonnelEvaluation from '@/components/Dashboard/Layout/Admin/DashboardPersonnelEvaluation.vue'
import DashboardTimesheet from '@/components/Dashboard/Layout/DashboardTimesheet.vue'
import DashboardNotice from '@/components/Dashboard/Layout/DashboardNotice.vue'
import { useAuthUserStore } from '@/store/auth'

/**
 * Layout type constants
 */

const auth = useAuthUserStore()
export const CARD_LAYOUTS = {
    MESSAGE: 'message',
    TASK: 'task',
    SURVEY: 'survey',
    MONTHLY_GOALS: 'monthly_goals',
    CHALLENGE: 'challenge',
    ASSETS: 'assets',
    SCHEDULES: 'schedules',
    PERSONNEL_EVALUATION: 'personnelEvaluation',
    TIMESHEET: 'timesheet',
    NOTICE: 'notice',
} as const

/**
 * Component map for dynamic component rendering
 * Maps layout type to Vue component - O(1) lookup
 * Using markRaw to prevent Vue from making components reactive (fixes HMR issues)
 */
export const DASHBOARD_COMPONENTS: Record<string, Component> = {
    [CARD_LAYOUTS.MESSAGE]: markRaw(DashboardMessageLayout),
    [CARD_LAYOUTS.TASK]: markRaw(DashboardTaskLayout),
    [CARD_LAYOUTS.SURVEY]: markRaw(DashboardSurvey),
    [CARD_LAYOUTS.MONTHLY_GOALS]: markRaw(DashboardGoal),
    [CARD_LAYOUTS.CHALLENGE]: markRaw(DashboardChallenge),
    [CARD_LAYOUTS.ASSETS]: markRaw(DashboardAsset),
    [CARD_LAYOUTS.SCHEDULES]: markRaw(DashboardSchedule),
    [CARD_LAYOUTS.PERSONNEL_EVALUATION]: markRaw(DashboardPersonnelEvaluation),
    [CARD_LAYOUTS.TIMESHEET]: markRaw(DashboardTimesheet),
    [CARD_LAYOUTS.NOTICE]: markRaw(DashboardNotice),
}

/**
 * Type to store collection key mapping
 */
type DashboardStoreKey = 
    | 'remindedMessages'
    | 'mustCheckMessages'
    | 'mustSignMessages'
    | 'unfinishedTasks'
    | 'untouchedTasks'
    | 'pendingApprovalTasks'
    | 'forms'
    | 'overdueGoals'
    | 'challenges'
    | 'assets'
    | 'schedules'
    | 'timesheet'
    | 'personnelEvaluation'
    | 'notices'

/**
 * Maps card type to dashboard store collection key
 */
export const CARD_DATA_KEY_BY_TYPE: Record<string, DashboardStoreKey> = {
    remindedMessages: 'remindedMessages',
    mustCheckMessages: 'mustCheckMessages',
    mustSignMessages: 'mustSignMessages',
    unfinishedTasks: 'unfinishedTasks',
    untouchedTasks: 'untouchedTasks',
    pendingApprovalTasks: 'pendingApprovalTasks',
    forms: 'forms',
    overdueGoals: 'overdueGoals',
    challenges: 'challenges',
    assets: 'assets',
    schedules: 'schedules',
    timesheet: 'timesheet',
    notice: 'notices',
}

/**
 * Maps card type to store keys that need to be refreshed
 */
export const CARD_REFRESH_KEYS_BY_TYPE: Record<string, DashboardStoreKey[]> = {
    remindedMessages: ['remindedMessages'],
    mustCheckMessages: ['mustCheckMessages'],
    mustSignMessages: ['mustSignMessages'],
    unfinishedTasks: ['unfinishedTasks', 'untouchedTasks'],
    untouchedTasks: ['untouchedTasks'],
    pendingApprovalTasks: ['pendingApprovalTasks'],
    forms: ['forms'],
    overdueGoals: ['overdueGoals'],
    challenges: ['challenges'],
    assets: ['assets'],
    schedules: ['schedules'],
    timesheet: ['timesheet'],
    notice: ['notices'],
}

/**
 * Admin-only card data keys
 */
export const CARD_ADMIN_DATA_KEY_BY_TYPE: Record<string, DashboardStoreKey> = {
    personnelEvaluation: 'personnelEvaluation',
}

/**
 * Admin-only card refresh keys
 */
export const CARD_ADMIN_REFRESH_KEYS_BY_TYPE: Record<string, DashboardStoreKey[]> = {
    personnelEvaluation: ['personnelEvaluation'],
}

/**
 * Default dashboard card definitions
 */
export const DEFAULT_DASHBOARD_CARDS: DashboardCard[] = [
    {
        title: 'リマインドメッセージ',
        type: 'remindedMessages',
        layout: 'message',
        col: 'col-span-2',
        order: undefined,
        data: [] as Message[],
        canFullscreen: true,
        canResize: true,
    },
    {
        title: '確認依頼',
        type: 'mustCheckMessages',
        layout: 'message',
        col: 'col-span-1',
        order: undefined,
        data: [] as Message[],
        canFullscreen: true,
        canResize: true,
    },
    {
        title: 'サイン依頼',
        type: 'mustSignMessages',
        layout: 'message',
        col: 'col-span-1',
        order: undefined,
        data: [] as Message[],
        canFullscreen: true,
        canResize: true,
    },
    {
        title: '未対応タスク',
        type: 'unfinishedTasks',
        layout: 'task',
        col: 'col-span-1',
        order: undefined,
        data: [] as Task[],
        canFullscreen: true,
        canResize: true,
    },
    {
        title: '未完了タスク',
        type: 'untouchedTasks',
        layout: 'task',
        col: 'col-span-1',
        order: undefined,
        data: [] as Task[],
        canFullscreen: true,
        canResize: true,
    },
    {
        title: '承認待ちタスク',
        type: 'pendingApprovalTasks',
        layout: 'task',
        col: 'col-span-1',
        order: undefined,
        data: [] as Task[],
        canFullscreen: true,
        canResize: true,
    },
    {
        title: 'フォーム',
        type: 'forms',
        layout: 'survey',
        col: 'col-span-1',
        order: undefined,
        data: [] as CustomForm[],
        canFullscreen: true,
        canResize: true,
    },    
    {
        title: '',
        type: 'overdueGoals',
        layout: 'monthly_goals',
        col: 'col-span-2',
        order: undefined,
        data: [],
        canFullscreen: true,
        canResize: true,
    },
    {
        title: 'ポスト',
        type: 'challenges',
        layout: 'challenge',
        col: 'col-span-1',
        order: undefined,
        data: [] as Post[],
        canFullscreen: false,
        canResize: true,
    },
    {
        title: '物品',
        type: 'assets',
        layout: 'assets',
        col: 'col-span-1',
        order: undefined,
        data: {
            in_use: [] as Asset[]
        },
        canFullscreen: true,
        canResize: true,
    },
    {
        title: 'スケジュール',
        type: 'schedules',
        layout: 'schedules',
        col: 'col-span-1',
        order: undefined,
        data: {
            temp_schedules: [] as CalendarRecord[]
        },
        canFullscreen: false,
        canResize: true,
    },
    {
        title: 'タイムシート',
        type: 'timesheet',
        layout: 'timesheet',
        col: 'col-span-1',
        order: undefined,
        data: {
            pendingTimesheets: [] as any[],
            departuresReportUsers: [],
            pendingPlannedLeaves: []
        },
        canFullscreen: false,
        canResize: true,
    },
    {
        title: 'お知らせ',
        type: 'notice',
        layout: 'notice',
        col: 'col-span-1',
        order: undefined,
        data: [],
        canFullscreen: true,
        canResize: true,
    },
]

/**
 * Admin-only card definition
 */
export const ADMIN_PERSONNEL_EVALUATION_CARD: DashboardCard = {
    title: '人事評価',
    type: 'personnelEvaluation',
    layout: 'personnelEvaluation',
    col: 'col-span-1',
    order: undefined,
    data: {
        pendingEvaluations: [] as any[]
    },
    canFullscreen: false,
    canResize: true,
}

/**
 * Get default dashboard cards filtered by user permissions
 */
export function getDefaultDashboardCards(): DashboardCard[] {
    return DEFAULT_DASHBOARD_CARDS.filter(card => {
        // Overdue goals card - only include for non-partner and non-registered users
        if (card.type === 'overdueGoals') {
            return !auth.isPartner && !auth.isRegistered
        }
        return true
    })
}

/**
 * Helper to determine if a card should be shown
 */
export function shouldShowCard(card: DashboardCard): boolean {
    const { layout, data } = card

    // Cards with v-show based on data length
    if (layout === 'message' || layout === 'task' || layout === 'challenge') {
        return Array.isArray(data) && data.length > 0
    }

    // Schedules card
    if (layout === 'schedules') {
        return (data as any)?.temp_schedules?.length > 0
    }

    // All other cards show by default
    return true
}
