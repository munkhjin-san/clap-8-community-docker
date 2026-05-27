import type { RouteRecordRaw } from 'vue-router'

export const boardRoutes: RouteRecordRaw[] = [
    {
        path: '/board',
        name: 'board',
        meta: {
            title: 'チャット',
        },
        component: () => import('@/components/Board/Board.vue'),
        children: [
            {
                path: ':chatId',
                component: () => import('@/components/Board/Message/MessageContainer.vue'),
                name: 'room',
                children: [
                    {
                        path: 'task',
                        component: () => import('@/components/Mobile/Task.vue'),
                        name: 'task',
                    },
                    {
                        path: 'file',
                        name: 'file',
                        component: () => import('@/components/Mobile/File.vue'),
                    },
                    {
                        path: 'board-form',
                        name: 'board-form',
                        component: () => import('@/components/Mobile/Form.vue'),
                    },
                ],
            },
        ],
    },
]