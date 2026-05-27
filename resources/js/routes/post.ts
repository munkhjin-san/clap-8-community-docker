import type { NavigationGuardNext, RouteLocationNormalized, RouteRecordRaw } from 'vue-router'
import axios from 'axios'

function fetchPosts(
    to: RouteLocationNormalized,
    next: NavigationGuardNext,
    path: string,
) {
    if (window.innerWidth < 959) {
        document.body.style.background = 'var(--background-color)'
    }

    axios.post('/get_posts', {
        path,
        query: to.query,
    })
        .then((response) => {
            to.meta.data = response.data
            next()
        })
        .catch(() => {
            next()
        })
}

export const postRoutes: RouteRecordRaw[] = [
    {
        path: '/post',
        name: 'post',
        meta: {
            title: 'ポスト',
        },
        component: () => import('@/components/Post/PostContainer.vue'),
        beforeEnter: (to, from, next) => {
            void from
            fetchPosts(to, next, 'post')
        },
    },
    {
        path: '/knowledge',
        name: 'knowledge',
        meta: {
            title: 'ナレッジ',
        },
        component: () => import('@/components/Post/PostContainer.vue'),
        beforeEnter: (to, from, next) => {
            void to
            void from
            next({ path: '/post', query: { app_type: 1 } })
        },
    },
    {
        path: '/nice',
        name: 'nice',
        meta: {
            title: 'ナイス',
        },
        component: () => import('@/components/Post/PostContainer.vue'),
        beforeEnter: (to, from, next) => {
            void to
            void from
            next({ path: '/post', query: { app_type: 0 } })
        },
    },
    {
        path: '/challenge',
        name: 'challenge',
        meta: {
            title: 'チャレンジ',
        },
        component: () => import('@/components/Post/PostContainer.vue'),
        beforeEnter: (to, from, next) => {
            void to
            void from
            next({ path: '/post', query: { app_type: 2 } })
        },
    },
]