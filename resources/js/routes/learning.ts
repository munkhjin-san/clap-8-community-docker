import type { RouteRecordRaw } from 'vue-router'
import { useAuthUserStore } from '@/store/auth'

export const learningRoutes: RouteRecordRaw[] = [
    {
        path: '/learning',
        name: 'learning',
        meta: {
            title: 'ラーニング',
            titleJp: 'ラーニング',
        },
        component: () => import('@/components/Learning/LearningRoot.vue'),
        children: [
            {
                path: ':lessonThemeId',
                name: 'top',
                component: () => import('@/components/Learning/LessonContainer.vue'),
                children: [
                    {
                        path: 'evaluate',
                        name: 'evaluate',
                        meta: {
                            nameJp: 'ポートフォリオ',
                        },
                        component: () => import('@/components/Learning/Evaluation.vue'),
                        beforeEnter: (to, from, next) => {
                            void from
                            const auth = useAuthUserStore()
                            const permitted = [608, 610, 799, 800, 829]
                            const userId = auth?.activeUser?.id

                            if (typeof userId === 'number' && permitted.includes(userId)) return next()

                            next({ name: 'learning' })
                        },
                    },
                    {
                        path: 'basic',
                        name: 'basic',
                        meta: {
                            nameJp: '基礎知識',
                        },
                        component: () => import('@/components/Learning/BasicKnowledge/BasicContainer.vue'),
                        children: [
                            {
                                path: 'personal-material/more',
                                name: 'personal_material_more',
                                component: () => import('@/components/Learning/BasicKnowledge/SectionMoreDetailed.vue'),
                            },
                            {
                                path: ':materialId',
                                name: 'material',
                                component: () => import('@/components/Learning/BasicKnowledge/Section.vue'),
                                children: [
                                    {
                                        path: 'more',
                                        name: 'more',
                                        component: () => import('@/components/Learning/BasicKnowledge/SectionMoreDetailed.vue'),
                                    },
                                ],
                            },
                            {
                                path: 'episode',
                                name: 'episode',
                                meta: {
                                    nameJp: 'エピソード',
                                },
                                component: () => import('@/components/Learning/BasicKnowledge/Draft/Episode.vue'),
                            },
                            {
                                path: 'story',
                                name: 'story',
                                meta: {
                                    nameJp: 'ポートフォリオ内容',
                                },
                                component: () => import('@/components/Learning/BasicKnowledge/Draft/Story.vue'),
                            },
                            {
                                path: 'title',
                                name: 'title',
                                meta: {
                                    nameJp: 'ポートフォリオタイトル',
                                },
                                component: () => import('@/components/Learning/BasicKnowledge/Draft/Title.vue'),
                            },
                            {
                                path: 'review',
                                name: 'review',
                                meta: {
                                    nameJp: 'AI分析',
                                },
                                component: () => import('@/components/Learning/BasicKnowledge/Draft/Review.vue'),
                            },
                            {
                                path: 'summary',
                                name: 'summary',
                                meta: {
                                    nameJp: 'サマリー',
                                },
                                component: () => import('@/components/Learning/BasicKnowledge/Draft/Summary.vue'),
                            },
                        ],
                    },
                    {
                        path: 'discussion',
                        name: 'discussion',
                        meta: {
                            nameJp: 'グループディスカッション',
                        },
                        component: () => import('@/components/Learning/Discussion/GroupDiscussion.vue'),
                    },
                    {
                        path: 'portfolioview',
                        name: 'portfolioview',
                        meta: {
                            nameJp: 'ポートフォリオ一覧',
                        },
                        component: () => import('@/components/Learning/BasicKnowledge/PortfolioView.vue'),
                    },
                    {
                        path: 'portfolio',
                        name: 'portfolio',
                        meta: {
                            nameJp: 'ポートフォリオ完成',
                        },
                        component: () => import('@/components/Learning/Portfolio/CompletePortfolio.vue'),
                    },
                    {
                        path: 'exam',
                        name: 'exam',
                        meta: {
                            nameJp: '試験',
                        },
                        component: () => import('@/components/Learning/Exam/ExamContainer.vue'),
                    },
                    {
                        path: 'form',
                        name: 'form',
                        meta: {
                            nameJp: 'アンケート',
                        },
                        component: () => import('@/components/Learning/Portfolio/LessonForm.vue'),
                    },
                    {
                        path: 'finish',
                        name: 'finish',
                        meta: {
                            nameJp: '完了',
                        },
                        component: () => import('@/components/Learning/Portfolio/LessonFinish.vue'),
                    },
                ],
            },
        ],
    },
]
