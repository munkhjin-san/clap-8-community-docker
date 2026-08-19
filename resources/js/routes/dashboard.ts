import type { RouteRecordRaw } from 'vue-router'

export const dashboardRoutes: RouteRecordRaw[] = [
    {
        path: '/dashboard/:type?/:itemId?',
        name: 'dashboard',
        component: () => import('@/components/Dashboard/DashboardContainer.vue'),
        meta: {
            title: 'ダッシュボード',
        },
        children: [
            {
                path: 'support',
                name: 'dashboard-support',
                component: () => import('@/components/Support/Support.vue'),
                meta: {
                    title: 'サポート',
                },
                children: [
                    {
                        path: 'faq',
                        name: 'faq',
                        component: () => import('@/components/Support/Faq.vue'),
                        meta: {
                            title: 'FAQ',
                        },
                        children: [
                            {
                                path: ':faqId',
                                name: 'faq_detail',
                                component: { render: () => null },
                            },
                        ],
                    },
                    {
                        path: 'regulations',
                        name: 'regulations',
                        meta: {
                            title: '規約',
                        },
                        component: () => import('@/components/Support/Regulations/RegulationsContainer.vue'),
                    },
                    {
                        path: 'email_consult',
                        name: 'email_consult',
                        meta: {
                            title: 'メール相談',
                        },
                        component: () => import('@/components/Support/MailConsult.vue'),
                    },
                    {
                        path: 'phone_consult',
                        name: 'phone_consult',
                        meta: {
                            title: '電話相談',
                        },
                        component: () => import('@/components/Support/PhoneConsult.vue'),
                    },
                    {
                        path: 'emergency_contact',
                        name: 'emergency_contact',
                        meta: {
                            title: '緊急連絡',
                        },
                        component: () => import('@/components/Support/EmergencyContact.vue'),
                        children: [
                            {
                                path: 'history',
                                name: 'emergency_contact_history',
                                component: () => import('@/components/Support/EmergencyContactHistory.vue'),
                            },
                        ],
                    },
                    {
                        path: 'email_inbox',
                        name: 'email_inbox',
                        meta: {
                            title: 'メール受信箱',
                        },
                        component: () => import('@/components/Support/Inbox.vue'),
                    },
                    {
                        path: 'chat',
                        name: 'ai_chat',
                        meta: {
                            title: 'AIチャット',
                        },
                        component: () => import('@/components/Support/Chat/AiChat.vue'),
                    },
                    {
                        path: 'chat-test',
                        redirect: { name: 'ai_chat' },
                    },
                    {
                        path: 'system_updates',
                        name: 'system_updates',
                        meta: {
                            title: 'システム更新',
                        },
                        component: () => import('@/components/Support/SystemUpdates.vue'),
                    },
                ],
            },
        ],
    },
]
