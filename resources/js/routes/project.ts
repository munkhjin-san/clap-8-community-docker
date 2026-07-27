import type { RouteRecordRaw } from 'vue-router'

export const projectRoutes: RouteRecordRaw[] = [
    {
        path: '/project',
        name: 'project',
        meta: {
            title: 'プロジェクト',
        },
        component: () => import('@/components/Project/ProjectContainer.vue'),
        children: [
            {
                path: ':projectId',
                name: 'projectdetail',
                component: () => import('@/components/Project/ProjectDetail.vue'),
                children: [
                    {
                        path: 'overview',
                        name: 'overview',
                        component: () => import('@/components/Project/ProjectTabs/OverviewRoot.vue'),
                        redirect: { name: 'project-overview-detail' },
                        children: [
                            {
                                path: 'detail',
                                name: 'project-overview-detail',
                                component: () => import('@/components/Project/ProjectTabs/Overview/Detail.vue'),
                            },
                            {
                                path: 'checkitems',
                                name: 'project-overview-checkitems',
                                component: () => import('@/components/Project/ProjectTabs/Overview/CheckList.vue'),
                            },
                            {
                                path: 'apply',
                                name: 'project-overview-apply',
                                component: () => import('@/components/Project/ProjectTabs/Overview/ProjectCreationForm.vue'),
                            },
                        ],
                    },
                    {
                        path: 'project-members',
                        name: 'project-members',
                        component: () => import('@/components/Project/ProjectTabs/MemberRoot.vue'),
                        redirect: { name: 'project-member-list' },
                        children: [
                            {
                                path: 'list',
                                name: 'project-member-list',
                                component: () => import('@/components/Project/ProjectTabs/MembersList.vue'),
                            },
                            {
                                path: 'role',
                                name: 'project-member-role',
                                component: () => import('@/components/Project/ProjectTabs/Members/MemberRole.vue'),
                            },
                            {
                                path: 'assign',
                                name: 'project-member-assign',
                                component: () => import('@/components/Project/ProjectTabs/Members/Assign.vue'),
                                children: [
                                    {
                                        path: ':memberId',
                                        name: 'assign-member',
                                        component: () => import('@/components/Project/ProjectTabs/Members/Assign/AssignMember.vue'),
                                    },
                                ],
                            },
                            {
                                path: 'outcomegoal/:memberId',
                                name: 'outcomegoal',
                                meta: {
                                    nameJp: '成果目標・昇給課題',
                                    pushTo: 'goal-span',
                                },
                                component: () => import('@/components/Project/PersonnelEvaluation/EvaluationSpan.vue'),
                                children: [
                                    {
                                        path: ':span/:goalId?',
                                        name: 'goal-span',
                                        component: () => import('@/components/Project/MonthlyGoal/MonthlyGoalContainer.vue'),
                                    },
                                ],
                            },
                            {
                                path: 'evaluation/:memberId',
                                name: 'evaluation',
                                meta: {
                                    nameJp: '人事考課',
                                    pushTo: 'evalutation-span',
                                },
                                component: () => import('@/components/Project/PersonnelEvaluation/EvaluationSpan.vue'),
                                children: [
                                    {
                                        name: 'evalutation-span',
                                        path: ':span',
                                        component: () => import('@/components/Project/PersonnelEvaluation/EvaluationDetail.vue'),
                                    },
                                ],
                            },
                            {
                                path: 'asignment/:memberId',
                                name: 'asignment',
                                component: () => import('@/components/Project/ProjectMemberAsignment.vue'),
                            },
                        ],
                    },
                    {
                        path: 'operation',
                        name: 'operation',
                        component: () => import('@/components/Project/ProjectTabs/Operation.vue'),
                    },
                    {
                        path: 'contracts',
                        name: 'contracts',
                        component: () => import('@/components/Project/ProjectTabs/Contracts.vue'),
                    },
                    {
                        path: 'legal',
                        name: 'legal',
                        component: () => import('@/components/Project/ProjectTabs/Legal.vue'),
                    },
                    // {
                    //     path: 'apps',
                    //     name: 'custom-apps',
                    //     component: () => import('@/components/Project/ProjectTabs/CustomApps.vue'),
                    // },
                    {
                        path: 'finance',
                        name: 'finance',
                        component: () => import('@/components/Project/ProjectTabs/FinanceRoot.vue'),
                        redirect: { name: 'income-expense' },
                        children: [
                            {
                                name: 'income-expense',
                                path: 'income-expense',
                                component: () => import('@/components/Project/ProjectTabs/Finance/Finance.vue'),
                            },
                            {
                                name: 'plan',
                                path: 'plan',
                                component: () => import('@/components/Project/ProjectTabs/Finance/YearlyBudget.vue'),
                            },
                            {
                                name: 'result',
                                path: 'result',
                                component: () => import('@/components/Project/ProjectTabs/Finance/CaseConfirm.vue'),
                            },
                        ],
                    },
                    {
                        path: 'dispatch',
                        name: 'dispatch',
                        component: () => import('@/components/Project/ProjectTabs/Dispatch.vue'),
                    },
                    {
                        path: 'task-calendar',
                        name: 'task-calendar',
                        component: () => import('@/components/Project/ProjectTabs/TaskCalendar.vue'),
                    },
                    {
                        path: 'file-storage/:parentId?',
                        name: 'file-storage',
                        component: () => import('@/components/Project/ProjectTabs/FileStorage.vue'),
                    },
                ],
            },
            {
                path: 'total-finance',
                name: 'total-finance',
                component: () => import('@/components/Project/ProjectTotalFinance.vue'),
            },
            {
                path: 'resource',
                name: 'resource',
                component: () => import('@/components/Project/ProjectResource.vue'),
            },
        ],
    },
]