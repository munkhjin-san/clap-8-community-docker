import type { RouteRecordRaw } from 'vue-router'

export const adminRoutes: RouteRecordRaw[] = [
    {
        path: '/admin_control',
        name: 'admin_control',
        meta: {
            title: '管理画面',
        },
        component: () => import('@/components/AccountControl/AdminControlList.vue'),
        children: [
            {
                path: 'account',
                name: 'account',
                component: () => import('@/components/AccountControl/AdminAccount.vue'),
            },
            {
                path: 'clapcount',
                name: 'clapcount',
                component: () => import('@/components/AccountControl/AdminClapCount.vue'),
            },
            {
                path: 'glowdnine',
                props: true,
                name: 'glowdnine',
                component: () => import('@/components/AccountControl/GlowdNine.vue'),
            },
            {
                path: 'learningcontrol',
                name: 'learningcontrol',
                component: () => import('@/components/AccountControl/LearningControl/LearningControl.vue'),
                children: [
                    {
                        path: 'categories',
                        name: 'learning-categories',
                        component: () => import('@/components/AccountControl/LearningControl/LearningCategoryControl.vue'),
                    },
                    {
                        path: ':themeId',
                        name: 'themeContainer',
                        component: () => import('@/components/AccountControl/LearningControl/ThemeContainer.vue'),
                        children: [
                            {
                                path: 'content',
                                name: 'content',
                                component: () => import('@/components/AccountControl/LearningControl/ContentControl.vue'),
                            },
                            {
                                path: 'trainee',
                                name: 'trainee',
                                component: () => import('@/components/AccountControl/LearningControl/TraineeControl.vue'),
                            },
                            {
                                path: 'assistant',
                                name: 'assistant',
                                component: () => import('@/components/AccountControl/LearningControl/AssistantControl.vue'),
                            },
                            {
                                path: 'case-study',
                                name: 'case-study',
                                component: () => import('@/components/AccountControl/LearningControl/CaseStudyControl.vue'),
                            },
                            {
                                path: 'non-trainee',
                                name: 'non-trainee',
                                component: () => import('@/components/AccountControl/LearningControl/NonTraineeControl.vue'),
                            },
                        ],
                    },
                ],
            },
            {
                path: 'workcontrol',
                name: 'workcontrol',
                component: () => import('@/components/AccountControl/WorkControl/AdminWorkControl.vue'),
                children: [
                    {
                        path: 'workgroup',
                        name: 'workgroup',
                        component: () => import('@/components/AccountControl/WorkControl/AdminWorkGroup.vue'),
                    },
                    {
                        path: 'attendance',
                        name: 'attendance',
                        component: () => import('@/components/AccountControl/WorkControl/AdminWork.vue'),
                    },
                    {
                        path: 'receipt-audit',
                        name: 'receipt-audit',
                        component: () => import('@/components/AccountControl/WorkControl/AdminReceiptAudit.vue'),
                    },
                    {
                        path: 'paidholiday',
                        name: 'paidholiday',
                        component: () => import('@/components/AccountControl/WorkControl/WorkPlannedPaid.vue'),
                    },
                    {
                        path: 'paid-leave-rules',
                        name: 'paid-leave-rules',
                        component: () => import('@/components/AccountControl/WorkControl/PaidLeavePolicy.vue'),
                    },
                    {
                        path: 'paid-leave-ledger',
                        name: 'paid-leave-ledger',
                        component: () => import('@/components/AccountControl/WorkControl/PaidLeaveLedger.vue'),
                    },
                ],
            },
            {
                path: 'projectcontrol',
                name: 'projectcontrol',
                component: () => import('@/components/AccountControl/ProjectControl/ProjectControl.vue'),
                children: [
                    {
                        path: 'projectlist',
                        name: 'projectlist',
                        component: () => import('@/components/AccountControl/ProjectControl/ProjectList.vue'),
                    },
                    {
                        path: 'projecttypes',
                        name: 'projecttypes',
                        component: () => import('@/components/AccountControl/ProjectControl/ProjectTypes.vue'),
                    },
                    {
                        path: 'checkitem-categories',
                        name: 'checkitem-categories',
                        component: () => import('@/components/AccountControl/ProjectControl/CheckItemCategories.vue'),
                    },
                    {
                        path: 'mentorcontrol',
                        name: 'mentorcontrol',
                        component: () => import('@/components/AccountControl/ProjectControl/EvaluationMentor.vue'),
                    },
                    {
                        path: 'accounts',
                        name: 'accountcontrol',
                        component: () => import('@/components/AccountControl/ProjectControl/AccountManager.vue'),
                    },
                    {
                        path: 'checkitems',
                        name: 'checkitems',
                        component: () => import('@/components/AccountControl/ProjectControl/CheckItems.vue'),
                    },
                ],
            },
            {
                path: 'custom-form-control',
                name: 'custom-form-control',
                meta: { head: 'アンケート' },
                component: () => import('@/components/AccountControl/CustomForm/CustomFormControl.vue'),
                children: [
                    {
                        path: ':formId',
                        name: 'formDetail',
                        component: () => import('@/components/AccountControl/CustomForm/CustomFormDetail.vue'),
                    },
                ],
            },
            {
                path: 'refresh-control',
                name: 'refresh-control',
                meta: { head: 'リフレッシュ' },
                redirect: { name: 'applications' },
                component: () => import('@/components/AccountControl/RefreshControl/RefreshControl.vue'),
                children: [
                    {
                        path: 'management',
                        name: 'management',
                        component: () => import('@/components/AccountControl/RefreshControl/RefreshManagement.vue'),
                    },
                    {
                        path: 'applications',
                        name: 'applications',
                        component: () => import('@/components/AccountControl/RefreshControl/RefreshApplications.vue'),
                    },
                    {
                        path: 'rakuaward',
                        name: 'rakuaward',
                        component: () => import('@/components/AccountControl/RefreshControl/RakuawardControl.vue'),
                    },
                ],
            },
            {
                path: 'offices',
                name: 'admin-offices',
                meta: { head: '営業所管理' },
                component: () => import('@/components/AccountControl/Office/AdminOffice.vue'),
            },
            {
                path: 'facilities',
                name: 'facilities-control',
                meta: { head: '施設' },
                redirect: { name: 'facility-rooms' },
                component: () => import('@/components/AccountControl/ScheduleControl/ScheduleControl.vue'),
                children: [
                    {
                        path: 'rooms',
                        name: 'facility-rooms',
                        component: () => import('@/components/AccountControl/ScheduleControl/CalendarFacilities.vue'),
                        props: { type: 'room' },
                    },
                    {
                        path: 'cars',
                        name: 'facility-cars',
                        component: () => import('@/components/AccountControl/ScheduleControl/CalendarFacilities.vue'),
                        props: { type: 'car' },
                    },
                    {
                        path: 'web-meetings',
                        name: 'web-meetings',
                        component: () => import('@/components/AccountControl/ScheduleControl/WebMeetings.vue'),
                    },
                ],
            },
            {
                path: 'schedule/:pathMatch(.*)*',
                redirect: { name: 'facility-rooms' },
            },
            {
                path: 'employee-change-applications',
                name: 'employee-change-applications',
                meta: { head: '各種届出' },
                component: () => import('@/components/AccountControl/EmployeeChangeApplications.vue'),
            },
            {
                path: 'employee-change-applications/:applicationId',
                name: 'employee-change-application-detail',
                meta: { head: '各種届出' },
                component: () => import('@/components/AccountControl/EmployeeChangeApplications.vue'),
            },
            // {
            //     path: 'ai-control',
            //     name: 'admin-ai',
            //     meta: { head: 'AI管理' },
            //     component: () => import('@/components/AccountControl/AIControl/AIControl.vue'),           
            
            // },
            // {
            //     path: 'cost-master',
            //     name: 'cost-master',
            //     meta: { head: '原価マスタ管理' },
            //     component: () => import('@/components/AccountControl/CostMaster/CostMasterWorkspace.vue'),
            // },
            {
                path: 'actual-results',
                name: 'actual-results',
                meta: { head: '実績計算' },
                component: () => import('@/components/AccountControl/ActualResult/ActualResultCalculator.vue'),
            },
        ],
    },
]
