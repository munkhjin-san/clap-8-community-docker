import type { RouteRecordRaw } from 'vue-router'

export const userRoutes: RouteRecordRaw[] = [
    {
        path: '/user/:userId',
        name: 'user',
        component: () => import('@/components/Profile/ProfileContainer.vue'),
        meta: {
            title: 'プロフィール',
        },
        children: [
            {
                path: 'personal-info-settings',
                component: () => import('@/components/Profile/UserEditComps/UserInfoEdit.vue'),
                name: 'personal-info-settings',
                meta: {
                    title: 'プロフィール編集',
                },
                beforeEnter: (to, from, next) => {
                    const rootElement = document.getElementById('app')
                    const userId = rootElement?.getAttribute('data-user-id')

                    if (userId && to.params.userId !== userId) {
                        const currentUserIdRoute = `/user/${userId}/personal-info-settings`

                        if (to.path !== currentUserIdRoute) {
                            next(currentUserIdRoute)
                            return
                        }
                    }

                    next()
                },
            },
        ],
    },
]