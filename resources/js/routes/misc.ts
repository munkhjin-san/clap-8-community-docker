import type { RouteRecordRaw } from 'vue-router'

export const miscRoutes: RouteRecordRaw[] = [
    {
        path: '/schedule',
        name: 'schedule',
        meta: {
            title: 'スケジュール',
        },
        component: () => import('@/components/Calendar/CalendarContainer.vue'),
    },
    {
        path: '/timesheet',
        name: 'timesheet',
        meta: {
            title: 'タイムシート',
        },
        component: () => import('@/components/Work/WorkContainer.vue'),
    },
    {
        path: '/survey',
        name: 'survey',
        component: () => import('@/components/Survey/Survey.vue'),
        meta: {
            title: 'フォーム',
        },
        children: [
            {
                path: ':surveyId',
                name: 'survey-form',
                component: () => import('@/components/Survey/SurveyForm.vue'),
            },
            {
                path: 'completed',
                name: 'completed-survey',
                component: () => import('@/components/Survey/SurveyComplete.vue'),
            },
        ],
    },
    {
        path: '/settings',
        component: () => import('@/components/Settings/Settings.vue'),
        name: 'settings',
        meta: {
            title: '設定',
        },
    },
    {
        path: '/help',
        name: 'help',
        meta: {
            title: 'ヘルプ',
        },
        component: () => import('@/components/Help/HelpContainer.vue'),
        redirect: { name: 'tutorial' },
        children: [
            {
                path: 'tutorial',
                name: 'tutorial',
                component: () => import('@/components/Help/Tutorial.vue'),
                children: [
                    {
                        path: 'about-project',
                        name: 'about-project',
                        component: () => import('@/components/Help/Project/ProjectHelp.vue'),
                    },
                ],
            },
        ],
    },
    {
        path: '/contact',
        name: 'contact',
        meta: {
            title: 'コンタクト',
        },
        component: () => import('@/components/Contact/MainContainer.vue'),
        children: [
            {
                path: ':contactId',
                name: 'contactDetail',
                component: () => import('@/components/Contact/Tab2/ContactDetail.vue'),
            },
        ],
    },
    {
        path: '/asset-partner',
        name: 'asset-partner',
        component: () => import('@/components/Asset/Partner/PartnerAssetContainer.vue'),
    },
    {
        path: '/survey-answers',
        name: 'survey-answers',
        component: () => import('@/components/Survey/MySurveyAnswers.vue'),
        children: [
            {
                path: 'survey-answers-detail',
                name: 'survey-answers-detail',
                component: () => import('@/components/Survey/MySurveyDetail.vue'),
            },
        ],
    },
    {
        path: '/file-preview/:fileId',
        name: 'file-preview',
        component: () => import('@/components/Global/FilePreviewDeeplink.vue'),
        props: true,
    },
]