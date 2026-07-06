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
        component: () => import('@/components/Settings/SettingsLayout.vue'),
        name: 'settings',
        meta: {
            title: '設定',
        },
        children: [
            {
                path: 'password',
                name: 'settings-password',
                meta: { title: 'パスワードの変更' },
                component: () => import('@/components/Settings/panels/PasswordPanel.vue'),
            },
            {
                path: 'two-factor',
                name: 'settings-two-factor',
                meta: { title: '二段階認証' },
                component: () => import('@/components/Settings/panels/TwoFactorPanel.vue'),
            },
            {
                path: 'email-otp',
                name: 'settings-email-otp',
                meta: { title: 'メール二段階認証' },
                component: () => import('@/components/Settings/panels/EmailOtpPanel.vue'),
            },
            {
                path: 'passkeys',
                name: 'settings-passkeys',
                meta: { title: 'パスキー' },
                component: () => import('@/components/Settings/panels/PasskeysPanel.vue'),
            },
            {
                path: 'color',
                name: 'settings-color',
                meta: { title: 'カラー設定' },
                component: () => import('@/components/Settings/panels/ColorPanel.vue'),
            },
            {
                path: 'theme',
                name: 'settings-theme',
                meta: { title: 'テーマ設定' },
                component: () => import('@/components/Settings/panels/ThemePanel.vue'),
            },
            {
                path: 'signature',
                name: 'settings-signature',
                meta: { title: 'マイサイン' },
                component: () => import('@/components/Settings/panels/SignaturePanel.vue'),
            },
            {
                path: 'schedule',
                name: 'settings-schedule',
                meta: { title: 'スケジュール設定' },
                component: () => import('@/components/Settings/panels/SchedulePanel.vue'),
            },
            {
                path: 'notification',
                name: 'settings-notification',
                meta: { title: '通知設定' },
                component: () => import('@/components/Settings/panels/NotificationPanel.vue'),
            },
            {
                path: 'notification-guide',
                name: 'settings-notification-guide',
                meta: { title: '通知設定案内' },
                component: () => import('@/components/Settings/panels/NotificationGuidePanel.vue'),
            },
            {
                path: 'footer-menu',
                name: 'settings-footer-menu',
                meta: { title: 'フッターメニュー表示' },
                component: () => import('@/components/Settings/panels/FooterMenuPanel.vue'),
            },
        ],
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