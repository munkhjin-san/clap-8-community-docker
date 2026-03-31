
import { createRouter, createWebHistory } from 'vue-router'
import { useMessageUsers } from '@/store/messageUsers'
import { useFilePreview } from "@/store/filePreview"
import { useResponsive } from '@/store/responsive'
import { useSideMenuView } from '@/store/sideMenuView'
import { useAuthUserStore } from './store/auth'
import { useProjectUsers } from '@/store/projectUsers'
import { useKeyboardStore } from '@/store/keyboardStore'
import { useBoardList } from '@/composables/board'
import { useModal } from '@/composables/modal';

import axios from 'axios'
const routes = [
    {
        name: 'community',
        path: '/community',
        component: () => import('./components/Community/CommunityContainer.vue'),
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
                    }
                ]
            },
            {
                path: 'offices',
                name: 'offices',
                component: () => import('@/components/Community/OfficeContainer.vue')
            }
        ]
            
    },
    { 
        path: '/board', 
        name: 'board', 
        meta: {
            title: 'チャット',
        },        
        component: () => import('./components/Board/Board.vue'),
        children: [
            {
                path: ':chatId',
                component: () => import('./components/Board/Message/MessageContainer.vue'),
                name: 'room',
                children: [
                    {
                        path: 'task',
                        component: () => import('./components/Mobile/Task.vue'),
                        name: 'task',
                    },
                    { path: 'file', name: 'file',  component: () => import('./components/Mobile/File.vue'), },
                    { path: 'board-form', name: 'board-form', component: () => import('./components/Mobile/Form.vue'),}
                ]
            }
        ],
    },
    {   
        path: '/user/:userId', 
        name: 'user',  
        component: () => import('./components/Profile/UserComponent.vue'),
        meta: {
            title: 'プロフィール',
        }, 
        children: [
            
            {
                path: 'personal-info-settings',
                component: () => import('./components/Profile/UserEditComps/UserInfoEdit.vue'),
                name: 'personal-info-settings',
                meta: {
                    title: 'プロフィール編集',
                },
                beforeEnter: (to, from, next) => {
                    const rootElement = document.getElementById('app');
                    const userId = rootElement.getAttribute('data-user-id');

                    if (to.params.userId !== userId) {
                        const currentUserIdRoute = `/user/${userId}/personal-info-settings`;
                        
                        if (to.path !== currentUserIdRoute) {
                            next(currentUserIdRoute);
                        } else {
                            next();
                        }
                    } else {
                        next();
                    }
                },
            },

        ],
    },
    {
        path: '/post',
        name: 'post',
        meta: {
            title: 'ポスト',
        },
        component: () => import('./components/Post/PostContainer.vue'),
        beforeEnter: (to, from, next) => {
            fetchPosts(to, next, from, 'post');
        }
    },
    {
        path: '/knowledge',
        name: 'knowledge',
        meta: {
            title: 'ナレッジ',
        }, 
        component: () => import('./components/Post/PostContainer.vue'),
        beforeEnter: (to, from, next) => {
            next({ path: '/post', query: { app_type: 1 } });
        },
    },
    {
        path: '/nice',
        name: 'nice',
        meta: {
            title: 'ナイス',
        }, 
        component: () => import('./components/Post/PostContainer.vue'),
        beforeEnter: (to, from, next) => {
            next({ path: '/post', query: { app_type: 0 } });
        },
    },
    {
        path: '/challenge',
        name: 'challenge',
        meta: {
            title: 'チャレンジ',
        }, 
        component: () => import('./components/Post/PostContainer.vue'),
        beforeEnter: (to, from, next) => {
            next({ path: '/post', query: { app_type: 2 } });
        },
    },
    {
        path: '/project',
        name: 'project',
        meta: {
            title: 'プロジェクト'
        },
        component: () => import('./components/Project/ProjectContainer.vue'),
        children: [
            {
                path: ':projectId',
                name: 'projectdetail',
                component: () => import('./components/Project/ProjectDetail.vue'),
                children: [
                    {
                        path:'overview',
                        name:'overview',
                        component: () => import('./components/Project/ProjectTabs/OverviewRoot.vue'),
                        redirect: { name: 'project-overview-detail' },
                        children: [
                            {
                                path: 'detail',
                                name: 'project-overview-detail',
                                component: () => import('./components/Project/ProjectTabs/Overview/Detail.vue')
                            },
                            {
                                path: 'checkitems',
                                name: 'project-overview-checkitems',
                                component: () => import('./components/Project/ProjectTabs/Overview/CheckList.vue')
                            },
                            {
                                path: 'apply',
                                name: 'project-overview-apply',
                                component: () => import('./components/Project/ProjectTabs/Overview/ProjectCreationForm.vue')
                            }
                        ]
                    },
                    {
                        path: 'project-members',
                        name: 'project-members',
                        component: () => import('./components/Project/ProjectTabs/MemberRoot.vue'),
                        redirect: { name: 'project-member-list' },
                        children:[
                            {
                                path: 'list',
                                name: 'project-member-list',
                                component: () => import('./components/Project/ProjectTabs/MembersList.vue'),
                            },
                            {
                                path: 'role',
                                name: 'project-member-role',
                                component: () => import('./components/Project/ProjectTabs/Members/MemberRole.vue'),
                            },
                            {
                                path: 'assign',
                                name: 'project-member-assign',
                                component: () => import('./components/Project/ProjectTabs/Members/Assign.vue'),
                            },
                            {
                                path: 'outcomegoal/:memberId',
                                name: 'outcomegoal',
                                meta: {
                                    nameJp: '成果目標・昇給課題',
                                    pushTo: 'goal-span'
                                },
                                
                                component: () => import('./components/Project/PersonnelEvaluation/EvaluationSpan.vue'),
                                children: [
                                    {
                                        path: ':span/:goalId?',
                                        name: 'goal-span',
                                        component: () => import('./components/Project/MonthlyGoal/MonthlyGoalContainer.vue'),
        
                                    }
                                ]
                            },
                            {
                                path: 'evaluation/:memberId',
                                name: 'evaluation',
                                meta: {
                                    nameJp: '人事考課',
                                    pushTo: 'evalutation-span'
                                },
                                component: () => import('./components/Project/PersonnelEvaluation/EvaluationSpan.vue'),
                                children: [
                                    {
                                        name: 'evalutation-span',
                                        path: ':span',
                                        component: () => import('./components/Project/PersonnelEvaluation/EvaluationDetail.vue'),                                         
                                    }
                                ]
                            },
                            {
                                path: 'asignment/:memberId',
                                name: 'asignment',
                                component: () => import('./components/Project/ProjectMemberAsignment.vue'),
                            }
                        ]
                    },
                    {
                        path: 'operation',
                        name: 'operation',
                        component: () => import('./components/Project/ProjectTabs/Operation.vue'),
                    },
                    {
                        path: 'contracts',
                        name: 'contracts',
                        component: () => import('./components/Project/ProjectTabs/Contracts.vue'),
                    },
                    {
                        path: 'legal',
                        name: 'legal',
                        component: () => import('./components/Project/ProjectTabs/Legal.vue'),
                    },
                    {
                        path: 'finance',
                        name: 'finance',
                        component: () => import('./components/Project/ProjectTabs/Finance.vue'),
                        children: [
                            {
                                name: 'plan',
                                path: 'plan',
                                component: () => import('./components/Project/ProjectTabs/Finance/YearlyBudget.vue')
                            },
                            {
                                name: 'result',
                                path: 'result',
                                component: () => import('./components/Project/ProjectTabs/Finance/CaseConfirm.vue')
                            }
                        ]
                    },
                    {
                        path: 'dispatch',
                        name: 'dispatch',
                        component: () => import('./components/Project/ProjectTabs/Dispatch.vue'),
                    },
                    {
                        path: 'task-calendar',
                        name: 'task-calendar',
                        component: () => import('./components/Project/ProjectTabs/TaskCalendar.vue'),
                    },
                    {
                        path: 'file-storage/:parentId?',
                        name: 'file-storage',
                        component: () => import ('./components/Project/ProjectTabs/FileStorage.vue'),
                    }
                    
                ]
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
            }
            // {
            //     path: 'gantt-chart',
            //     name: 'gantt-chart',
            //     meta: {
            //         title: 'ガントチャート'
            //     },
            //     component: () => import('./components/Task/TaskComponent.vue'),
            //     children: [
            //         {
            //             path: `:projectId`,
            //             component: () => import('./components/Task/Gantt/GanttTaskPopup.vue'),
            //             name: 'projectGanttDetail',
            //             props: true
            //         }
            //     ],
            // },
            // {
            //     path: 'assets',
            //     name: 'assets',
            //     meta: {
            //         title: '物品'
            //     },
            //     
            //     component: () => import('./components/Asset/AssetContainer.vue'),
            // }
        ]
    },
    {
        path: '/schedule',
        name: 'schedule',
        meta: {
            title: 'スケジュール',
        }, 
        component: () => import('./components/Calendar/CalendarContainer.vue'),       
        
    },
    {
        path: '/timesheet',
        name: 'timesheet',
        
        meta: {
            title: 'タイムシート',
        }, 
        component: () => import('./components/Work/WorkContainer.vue'),
    },
    {
        path: '/admin_control',
        name: 'admin_control',
        meta: {
            title: '管理画面',
        }, 
        component: () => import('./components/AccountControl/AdminControlList.vue'),
        children: [
            { path: 'account', name: 'account', component: () => import('./components/AccountControl/AdminAccount.vue') },
            { path: 'clapcount', name: 'clapcount', component: () => import('./components/AccountControl/AdminClapCount.vue') },
            { path: 'glowdnine', props:true, name: 'glowdnine', component: () => import('./components/AccountControl/GlowdNine.vue')},
            { 
                path: 'learningcontrol',
                 name: 'learningcontrol', 
                component: () => import('./components/AccountControl/LearningControl/LearningControl.vue'), 
                children: [
                    {
                        path: ':themeId',
                        name: 'themeContainer',
                        component: () => import('./components/AccountControl/LearningControl/ThemeContainer.vue'),
                        
                        children: [
                            {
                                
                                path: 'content',
                                name: 'content',
                                component: () => import('./components/AccountControl/LearningControl/ContentControl.vue')
                            },
                            {
                                
                                path: 'trainee',
                                name: 'trainee',
                                component: () => import('./components/AccountControl/LearningControl/TraineeControl.vue')
                            },
                            {
                                
                                path: 'assistant',
                                name: 'assistant',
                                component: () => import('./components/AccountControl/LearningControl/AssistantControl.vue')
                            },
                            {
                                
                                path: 'case-study',
                                name: 'case-study',
                                component: () => import('./components/AccountControl/LearningControl/CaseStudyControl.vue')
                            }
                            
                        ]
                    },
                    
                ]
            },
            { 
                path: 'workcontrol', 
                 
                name: 'workcontrol', 
                component: () => import('./components/AccountControl/WorkControl/AdminWorkControl.vue'),
                children: [
                    {
                        path: 'workgroup',
                        name: 'workgroup',
                        
                        component: () => import('./components/AccountControl/WorkControl/AdminWorkGroup.vue')
                    },
                    {
                        path: 'attendance',
                        name: 'attendance',
                        
                        component: () => import('./components/AccountControl/WorkControl/AdminWork.vue')
                    },
                    {
                        path: 'paidholiday',
                        name: 'paidholiday',
                        
                        component: () => import('./components/AccountControl/WorkControl/WorkPlannedPaid.vue')
                    }
                ]
            },
            {
                path: 'projectcontrol',
                
                name: 'projectcontrol',
                component: () => import('./components/AccountControl/ProjectControl/ProjectControl.vue'),
                children: [
                    {
                        path: 'projectlist',
                        name: 'projectlist',
                        
                        component: () => import('./components/AccountControl/ProjectControl/ProjectList.vue')
                    },
                    {
                        path: 'projecttypes',
                        name: 'projecttypes',
                        component: () => import('./components/AccountControl/ProjectControl/ProjectTypes.vue')
                    },
                    {
                        path: 'checkitem-categories',
                        name: 'checkitem-categories',
                        component: () => import('./components/AccountControl/ProjectControl/CheckItemCategories.vue')
                    },
                    {
                        path: 'mentorcontrol',
                        name: 'mentorcontrol',
                        
                        component: () => import('./components/AccountControl/ProjectControl/EvaluationMentor.vue')
                    },
                    {
                        path: 'accounts',
                        name: 'accountcontrol',
                        
                        component: () => import('./components/AccountControl/ProjectControl/AccountManager.vue')
                    },
                    {
                        path: 'checkitems',
                        name: 'checkitems',
                        component: () => import('./components/AccountControl/ProjectControl/CheckItems.vue')
                    }
                ]
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
                    }
                ]
            },
            // {
            //     path: 'asset-control',
            //     name: 'asset-control',
            //     meta: { head: '物品' },
                
            //     component: () => import('@/components/AccountControl/AssetControl/AssetControl.vue'),
            // },
            // {
            //     path: 'refresh-control',
            //     name: 'refresh-control',
            //     meta: { head: 'リフレッシュ' },
            //     redirect: { name: 'applications' },
            //     component: () => import('@/components/AccountControl/RefreshControl/RefreshControl.vue'),
            //     children: [
            //         {
            //             path: 'management',
            //             name: 'management',
            //             component: () => import('@/components/AccountControl/RefreshControl/RefreshManagement.vue'),
            //         },
            //         {
            //             path: 'applications',
            //             name: 'applications',
            //             component: () => import('@/components/AccountControl/RefreshControl/RefreshApplications.vue'),
            //         }
            //     ]
            // },
            {
                path: 'offices',
                name: 'admin-offices',
                meta: { head: '営業所管理' },
                
                component: () => import('@/components/AccountControl/Office/AdminOffice.vue'),
            }
        ],
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
                component: () => import('@/components/Survey/SurveyForm.vue')
            },
            {
                path: 'completed',
                name: 'completed-survey',
                component: () => import('./components/Survey/SurveyComplete.vue'),
            }
        ]
    },
    {
        path: '/support',
        name: 'support',
        meta: {
            title: 'サポート',
        }, 
        component: () => import('./components/Support/Support.vue'),
        children: [
            { path: 'faq', name: 'faq', component: () => import('./components/Support/Faq.vue') },
            { path: 'regulations', name: 'regulations', component: () => import('./components/Support/Regulations/RegulationsContainer.vue') },
            { path: 'email_consult', name: 'email_consult', component: () => import('./components/Support/MailConsult.vue') },
            { path: 'phone_consult', name: 'phone_consult', component: () => import('./components/Support/PhoneConsult.vue') },
            { path: 'email_inbox', name: 'email_inbox', component: () => import('./components/Support/Inbox.vue')}
        ],
    },
    {
        path: '/notice',
        name: 'notice',
        meta: {
            title: 'お知らせ',
        }, 
        component: () => import('./components/Notice/Notice.vue'),
        children: [
            { 
                path: ':noticeId',
                name: 'notice_detail',
                component: () => import('./components/Notice/NoticeDetail.vue'),
                beforeEnter: (to, from, next) => {
                    axios.get(`/get_notice?id=${to.params.noticeId}`).then(
                    response => {
                        to.meta.data = response.data
                        next();
                    })
                    
                }, 
            }
        ],
    },
    {
        path: '/settings',
        component: () => import('./components/Settings/Settings.vue'),
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
                ]
            }
        ]
    },
    {
        path: '/learning',
        name: 'learning',
        meta: {
            title: 'ラーニング',
            titleJp: 'ラーニング'
        }, 
        component: () => import('./components/Learning/LearningRoot.vue'),
        children: [
            { 
                path: ':lessonThemeId',
                name: 'top',
                component: () => import('./components/Learning/LessonContainer.vue'),
                children: [
                    {
                        path: 'evaluate',
                        name: 'evaluate',
                        meta: {
                            nameJp: 'ポートフォリオ'
                        },
                        component: () => import('./components/Learning/Evaluation.vue'),
                        beforeEnter: (to, from, next) => {
                            const auth = useAuthUserStore()
                            const permitted = [608, 610, 799, 800, 829]
                            const userId = auth?.activeUser?.id
                            if(permitted.includes(userId)){
                                axios.get(`/get_portfolios_list?theme_id=${to.params.lessonThemeId}`).then(
                                response => {
                                    to.meta.list = response.data
                                    next();
                                })
                            }else{
                                next({name:'learning'});
                            }
                            
                        }
                    },
                    {
                        path: 'basic',
                        name: 'basic',
                        meta: {
                            nameJp: '基礎知識'
                        },
                        
                        component: () => import('./components/Learning/BasicKnowledge/BasicContainer.vue'),
                        children: [
                            {
                                path: ':materialId',
                                name: 'material',
                                
                                component: () => import('./components/Learning/BasicKnowledge/Section.vue'),
                                beforeEnter: (to, from, next) => {
                                    axios.get(`/get_material`, {params: {id: to.params.materialId}}).then(
                                        response => {
                                            to.meta.material = response.data
                                            next();
                                        })
                                },
                                children: [
                                    {
                                        path: 'more',
                                        name: 'more',
                                        
                                        component: () => import('./components/Learning/BasicKnowledge/SectionMoreDetailed.vue'),
                                        beforeEnter: (to, from, next) => {
                                            axios.get(`/get_support_account`).then(
                                                response => {
                                                    to.meta.support_user_id = response.data
                                                    next();
                                                })
                                        }
                                    },
                                ]
                            },
                            {
                                path:'episode',
                                name:'episode',
                                
                                meta: {
                                    nameJp: 'エピソード'
                                },
                                component: () => import('./components/Learning/BasicKnowledge/Draft/Episode.vue')
                            },
                            {
                                path:'story',
                                name:'story',
                                
                                meta: {
                                    nameJp: 'ポートフォリオ内容'
                                },
                                component: () => import('./components/Learning/BasicKnowledge/Draft/Story.vue')
                            },
                            {
                                path:'title',
                                name:'title',
                                
                                meta: {
                                    nameJp: 'ポートフォリオタイトル'
                                },
                                component: () => import('./components/Learning/BasicKnowledge/Draft/Title.vue')
                            },
                            {
                                path:'review',
                                name:'review',
                                
                                meta: {
                                    nameJp: 'AI分析'
                                },
                                component: () => import('./components/Learning/BasicKnowledge/Draft/Review.vue')
                            },
                            {
                                path:'summary',
                                name:'summary',
                                
                                meta: {
                                    nameJp: 'サマリー'
                                },
                                component: () => import('./components/Learning/BasicKnowledge/Draft/Summary.vue')
                            },
                            
                        ]
                    },
                    {
                        path: 'discussion',
                        name: 'discussion',
                        meta: {
                            nameJp: 'グループディスカッション'
                        },
                        
                        component: () => import('./components/Learning/Discussion/GroupDiscussion.vue'),
                    },
                    
                    {
                        path:'portfolioview',
                        name:'portfolioview',
                        meta: {
                            nameJp: 'ポートフォリオ一覧'
                        },
                        
                        component: () => import('./components/Learning/BasicKnowledge/PortfolioView.vue')
                    },
                    {
                        path: 'portfolio',
                        name: 'portfolio',
                        meta: {
                            nameJp: 'ポートフォリオ完成'
                        },
                        
                        component: () => import('./components/Learning/Portfolio/CompletePortfolio.vue')
                    },
                    {
                        path: 'exam',
                        name: 'exam',
                        meta: {
                            nameJp: '試験'
                        },
                        
                        component: () => import('./components/Learning/Exam/ExamContainer.vue')
                    },
                    
                    {
                        path: 'form',
                        name: 'form',
                        meta: {
                            nameJp: 'アンケート'
                        },
                        component: () => import('./components/Learning/Portfolio/LessonForm.vue')
                    },
                    {
                        path: 'finish',
                        name: 'finish',
                        meta: {
                            nameJp: '完了'
                        },
                        component: () => import('./components/Learning/Portfolio/LessonFinish.vue')
                    },
                ],
                // beforeEnter: (to, from, next) => {
                //     axios.get(`/get_lessons?lesson_theme_id=${to.params.lessonThemeId}`).then(
                //         response => {
                //             to.meta.data = response.data
                //             next();
                //         })
                // }
            }
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
                component: () => import('@/components/Contact/Tab2/ContactDetail.vue')
            }
        ]
            
        

    },
    {
        path: '/asset-partner',
        name: 'asset-partner',
        component: () => import('./components/Asset/Partner/PartnerAssetContainer.vue'),
    },
    {
        path: '/survey-answers',
        name: 'survey-answers',
        component: () => import('./components/Survey/MySurveyAnswers.vue'),
        children: [
            {
                path: 'survey-answers-detail',
                name: 'survey-answers-detail',
                component: () => import('./components/Survey/MySurveyDetail.vue'),
            },
        ]
    },
    {
        path: '/file-preview/:fileId',
        name: 'file-preview',
        component: () => import('./components/Global/FilePreviewDeeplink.vue'),
        props: true,
    },
    {
        path: '/dashboard/:type?/:itemId?',
        name: 'dashboard',
        component: () => import('./components/Dashboard/DashboardContainer.vue'),
        meta: {
            title: 'ダッシュボード',
        }, 
    }

    


]
function fetchPosts(to, next, from, path) {
    if(window.innerWidth < 959){
        document.body.style.background = 'var(--background-color)'
    }
    axios.post('/get_posts', {
        path: path,
        query: to.query
        
    })
    .then(response => {
        to.meta.data = response.data;
        next();
    })
    .catch(error => {
        next()
    });
}
const router = createRouter({
    history: createWebHistory(),
    routes
})
router.beforeEach((to, from) => {
    const keyboardStore = useKeyboardStore()
    keyboardStore.setKeyboardHeight(0)
    if ("virtualKeyboard" in navigator) {                  
        navigator.virtualKeyboard.overlaysContent = false;                  
    }
})
router.afterEach((to, from) => {
    const responsive = useResponsive()
    const sideMenuView = useSideMenuView()
    if(responsive.mobile){
        sideMenuView.setSideMenuView(false)
    }    
    cleanUp(to, from)
  });
  function cleanUp(to, from){
    const { setEmoteUsers } = useModal()
    const messageUsers = useMessageUsers()
    const filePreview = useFilePreview()
    const projectUsers = useProjectUsers()
    const { setNextCursor, nextCursor } = useBoardList() 
    setEmoteUsers([])
    projectUsers.setProjectUsers({
        active: false,
        userList: [],
        title: ''
    })
    messageUsers.setMessageUsers({
        active: false,
        userList: [],
        title: ''
    })
    filePreview.setFilePreview({
        active: false,
        files: [],
        source: null,
        source_board_id: null,
        index: 0,
        message: null
    })
    if(!['board', 'room'].includes(from.name)){
        setNextCursor(null)  
    }    
  }
  router.onError((error, to) => {
    if (error.message.includes('Failed to fetch dynamically imported module') || error.message.includes("Importing a module script failed")) {
      window.location = to.fullPath
    }
  })
export default router
