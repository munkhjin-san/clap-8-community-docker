import type { RouteRecordRaw } from 'vue-router'

// アプリ — top-level, accessible to all authenticated users.
// Per-app permissions (not the admin gate) govern what each user can do.
export const customAppRoutes: RouteRecordRaw[] = [
    {
        path: '/apps',
        name: 'flow-control',
        meta: { title: 'アプリ' },
        component: () => import('@/components/AccountControl/FlowControl/FlowControl.vue'),
    },
    {
        path: '/apps/builder/:flowId?',
        name: 'flow-builder',
        meta: { title: 'アプリ' },
        component: () => import('@/components/AccountControl/FlowControl/FlowBuilder.vue'),
    },
    {
        path: '/apps/dashboard',
        name: 'flow-dashboard',
        meta: { title: 'アプリ' },
        component: () => import('@/components/AccountControl/FlowControl/FlowDashboard.vue'),
    },
    {
        path: '/apps/records/:flowId',
        name: 'flow-records',
        meta: { title: 'アプリ' },
        component: () => import('@/components/AccountControl/FlowControl/FlowRecordsView.vue'),
    },
    {
        path: '/apps/records/:flowId/new',
        name: 'flow-record-new',
        meta: { title: 'アプリ' },
        component: () => import('@/components/AccountControl/FlowControl/FlowRecordDetail.vue'),
    },
    {
        path: '/apps/records/:flowId/edit/:recordId',
        name: 'flow-record-detail',
        meta: { title: 'アプリ' },
        component: () => import('@/components/AccountControl/FlowControl/FlowRecordDetail.vue'),
    },
]
