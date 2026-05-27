import type { RouteRecordRaw } from 'vue-router'

export const communityRoutes: RouteRecordRaw[] = [
    {
        name: 'community',
        path: '/community',
        component: () => import('@/components/Community/CommunityContainer.vue'),
        meta: {
            title: 'コミュニティ',
        },
        redirect: { name: 'members' },
        children: [
            {
                path: 'members',
                name: 'members',
                component: () => import('@/components/Community/MemberContainer.vue'),
                children: [
                    {
                        path: 'today-comments',
                        name: 'today-comments',
                        component: () => import('@/components/Community/MemberDailyCommentContainer.vue'),
                    },
                ],
            },
            {
                path: 'offices',
                name: 'offices',
                component: () => import('@/components/Community/OfficeContainer.vue'),
            },
        ],
    },
]