
import { createRouter, createWebHistory } from 'vue-router'
import { useMessageUsers } from '@/store/messageUsers'
import { useFilePreview } from "@/store/filePreview"
import { useResponsive } from '@/store/responsive'
import { useSideMenuView } from '@/store/sideMenuView'
import { useAuthUserStore } from './store/auth'
import { useProjectUsers } from '@/store/projectUsers'
import { useKeyboardStore } from '@/store/keyboardStore'
import axios from 'axios'
const routes = [
    { 
        path: '/board', 
        name: 'board', 
        meta: {
            title: 'CLAP - ボード',
        },        
        component: () => import('./components/Board/Board.vue'),
        children: [
            {
                path: ':chatId',
                component: () => import('./components/Board/Message/MessageContainer.vue'),
                name: 'room',
                props: true,
                children: [
                    {
                        path: 'task',
                        component: () => import('./components/Mobile/Task.vue'),
                        name: 'task',
                        props: true
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
        props: true,
        meta: {
            title: 'CLAP - プロフィール',
        }, 
        children: [
            
            {
                path: 'personal-info-settings',
                component: () => import('./components/Profile/UserEditComps/UserInfoEdit.vue'),
                name: 'personal-info-settings',
                props: true,
                meta: {
                    title: 'CLAP - プロフィール編集',
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
        beforeEnter: (to, from, next) => {
            resolveBeforeEnter(to, next, from);
        },
    },
    {
        path: '/post',
        name: 'post',
        meta: {
            title: 'CLAP - ポスト',
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
            title: 'CLAP - ナレッジ',
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
            title: 'CLAP - ナイス',
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
            title: 'CLAP - チャレンジ',
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
            title: 'CLAP - プロジェクト'
        },
        component: () => import('./components/Project/ProjectContainer.vue'),
        children: [
            {
                path: ':projectId',
                name: 'projectdetail',
                props: true,
                component: () => import('./components/Project/ProjectDetail.vue'),
                children: [
                    {
                        path:'overview',
                        name:'overview',
                        props: true,
                        component: () => import('./components/Project/ProjectTabs/ProjectOverview.vue'),
                        
                    },
                    {
                        path: 'project-members',
                        name: 'project-members',
                        props: true,
                        component: () => import('./components/Project/ProjectTabs/Members.vue'),
                        children:[
                            {
                                path: 'outcomegoal/:memberId',
                                name: 'outcomegoal',
                                props: true,
                                meta: {
                                    nameJp: '成果目標・昇給課題',
                                    pushTo: 'goal-span'
                                },
                                
                                component: () => import('./components/Project/PersonnelEvaluation/EvaluationSpan.vue'),
                                children: [
                                    {
                                        path: ':span',
                                        name: 'goal-span',
                                        props: true,
                                        component: () => import('./components/Project/ProjectGoalDetail.vue'),
                                        children:[
                                            {
                                                path: ':goalId',
                                                name: 'goal-more',
                                                props: true,
                                                component: () => import('./components/Project/ProjectGoalMore.vue'),
                                            }
                                        ]
        
                                    }
                                ]
                            },
                            {
                                path: 'evaluation/:memberId',
                                name: 'evaluation',
                                props: true,
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
                        ]
                    },
                    {
                        path: 'operation',
                        name: 'operation',
                        props: true,
                        component: () => import('./components/Project/ProjectTabs/Operation.vue'),
                    },
                    {
                        path: 'contracts',
                        name: 'contracts',
                        props: true,
                        component: () => import('./components/Project/ProjectTabs/Contracts.vue'),
                    },
                    {
                        path: 'finance',
                        name: 'finance',
                        props: true,
                        component: () => import('./components/Project/ProjectTabs/Finance.vue'),
                    },
                    {
                        path: 'dispatch',
                        name: 'dispatch',
                        props: true,
                        component: () => import('./components/Project/ProjectTabs/Dispatch.vue'),
                    },
                    {
                        path: 'assets',
                        name: 'assets',
                        meta: {
                            title: 'CLAP - 物品'
                        },
                        props: true,
                        component: () => import('./components/Asset/AssetContainer.vue'),
                    },
                    {
                        path: 'task-calendar',
                        name: 'task-calendar',
                        props: true,
                        component: () => import('./components/Project/ProjectTabs/TaskCalendar.vue'),
                    }
                    
                ]
            },
            // {
            //     path: 'gantt-chart',
            //     name: 'gantt-chart',
            //     meta: {
            //         title: 'CLAP - ガントチャート'
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
            //         title: 'CLAP - 物品'
            //     },
            //     props: true,
            //     component: () => import('./components/Asset/AssetContainer.vue'),
            // }
        ]
    },
    {
        path: '/schedule',
        name: 'schedule',
        meta: {
            title: 'CLAP - スケジュール',
        }, 
        component: () => import('./components/Calendar/CalendarContainer.vue'),       
        
    },
    {
        path: '/timesheet',
        name: 'timesheet',
        props: true,
        meta: {
            title: 'CLAP - タイムシート',
        }, 
        component: () => import('./components/Work/WorkContainer.vue'),
    },
    {
        path: '/admin_control',
        name: 'admin_control',
        meta: {
            title: 'CLAP - 管理画面',
        }, 
        component: () => import('./components/AccountControl/AdminControlList.vue'),
        children: [
            { path: 'account',props: true, name: 'account', component: () => import('./components/AccountControl/AdminAccount.vue') },
            { path: 'clapcount',props: true, name: 'clapcount', component: () => import('./components/AccountControl/AdminClapCount.vue') },
            { path: 'glowdnine', props:true, name: 'glowdnine', component: () => import('./components/AccountControl/GlowdNine.vue')},
            { 
                path: 'learningcontrol',
                props: true, name: 'learningcontrol', 
                component: () => import('./components/AccountControl/LearningControl/LearningControl.vue'), 
                children: [
                    {
                        path: ':themeId',
                        name: 'themeContainer',
                        component: () => import('./components/AccountControl/LearningControl/ThemeContainer.vue'),
                        props: true,
                        children: [
                            {
                                props: true,
                                path: 'content',
                                name: 'content',
                                component: () => import('./components/AccountControl/LearningControl/ContentControl.vue')
                            },
                            {
                                props: true,
                                path: 'trainee',
                                name: 'trainee',
                                component: () => import('./components/AccountControl/LearningControl/TraineeControl.vue')
                            },
                            {
                                props: true,
                                path: 'assistant',
                                name: 'assistant',
                                component: () => import('./components/AccountControl/LearningControl/AssistantControl.vue')
                            },
                            {
                                props: true,
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
                props: true, 
                name: 'workcontrol', 
                component: () => import('./components/AccountControl/WorkControl/AdminWorkControl.vue'),
                children: [
                    {
                        path: 'workgroup',
                        name: 'workgroup',
                        props: true,
                        component: () => import('./components/AccountControl/WorkControl/AdminWorkGroup.vue')
                    },
                    {
                        path: 'attendance',
                        name: 'attendance',
                        props: true,
                        component: () => import('./components/AccountControl/WorkControl/AdminWork.vue')
                    },
                    {
                        path: 'paidholdiay',
                        name: 'paidholiday',
                        props: true,
                        component: () => import('./components/AccountControl/WorkControl/WorkPlannedPaid.vue')
                    }
                ]
            },
            {
                path: 'projectcontrol',
                props: true,
                name: 'projectcontrol',
                component: () => import('./components/AccountControl/ProjectControl/ProjectControl.vue'),
                children: [
                    {
                        path: 'projectlist',
                        name: 'projectlist',
                        props: true,
                        component: () => import('./components/AccountControl/ProjectControl/ProjectList.vue')
                    },
                    {
                        path: 'mentorcontrol',
                        name: 'mentorcontrol',
                        props: true,
                        component: () => import('./components/AccountControl/ProjectControl/EvaluationMentor.vue')
                    },

                ]
            },
            {
                path: 'custom-form-control',
                name: 'custom-form-control',
                meta: { head: 'アンケート' },
                props: true,
                component: () => import('@/components/AccountControl/CustomForm/CustomFormControl.vue'),
                children: [
                    {
                        path: ':formId',
                        name: 'formDetail',
                        props: true,
                        component: () => import('@/components/AccountControl/CustomForm/CustomFormDetail.vue'),
                    }
                ]
            },
            {
                path: 'asset-control',
                name: 'asset-control',
                meta: { head: '物品' },
                props: true,
                component: () => import('@/components/AccountControl/AssetControl/AssetControl.vue'),
            }
        ],
    },
    {
        path: '/survey',
        name: 'survey',
        component: () => import('@/components/Survey/Survey.vue'),
        meta: {
            title: 'CLAP - アンケート',
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
            title: 'CLAP - サポート',
        }, 
        component: () => import('./components/Support/Support.vue'),
        children: [
            { path: 'faq',props: true, name: 'faq', component: () => import('./components/Support/Faq.vue') },
            { path: 'email_consult',props: true, name: 'email_consult', component: () => import('./components/Support/MailConsult.vue') },
            { path: 'phone_consult',props: true, name: 'phone_consult', component: () => import('./components/Support/PhoneConsult.vue') },
            { path: 'email_inbox',props: true, name: 'email_inbox', component: () => import('./components/Support/Inbox.vue')}
        ],
    },
    {
        path: '/notice',
        name: 'notice',
        meta: {
            title: 'CLAP - お知らせ',
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
            title: 'CLAP - 設定',
        }, 
        props: true,
        
    },
    {
        path: '/learning',
        name: 'learning',
        meta: {
            title: 'CLAP - ラーニング',
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
                        props: true,
                        component: () => import('./components/Learning/BasicKnowledge/BasicContainer.vue'),
                        children: [
                            {
                                path: ':materialId',
                                name: 'material',
                                props: true,
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
                                        props: true,
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
                                props: true,
                                meta: {
                                    nameJp: 'エピソード'
                                },
                                component: () => import('./components/Learning/BasicKnowledge/Draft/Episode.vue')
                            },
                            {
                                path:'story',
                                name:'story',
                                props: true,
                                meta: {
                                    nameJp: 'ポートフォリオ内容'
                                },
                                component: () => import('./components/Learning/BasicKnowledge/Draft/Story.vue')
                            },
                            {
                                path:'title',
                                name:'title',
                                props: true,
                                meta: {
                                    nameJp: 'ポートフォリオタイトル'
                                },
                                component: () => import('./components/Learning/BasicKnowledge/Draft/Title.vue')
                            },
                            {
                                path:'review',
                                name:'review',
                                props: true,
                                meta: {
                                    nameJp: 'AI分析'
                                },
                                component: () => import('./components/Learning/BasicKnowledge/Draft/Review.vue')
                            },
                            {
                                path:'summary',
                                name:'summary',
                                props: true,
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
                        props: true,
                        component: () => import('./components/Learning/Discussion/GroupDiscussion.vue'),
                    },
                    // {
                    //     path: 'portfoliodraft',
                    //     name: 'portfoliodraft',
                    //     meta: {
                    //         nameJp: 'ポートフォリオ作成'
                    //     },
                    //     props: true,
                    //     component: () => import('./components/Learning/BasicKnowledge/BasicDraftPortfolio.vue'),
                    //     children: [
                            
                    //     ]
                    // },
                    
                    {
                        path:'portfolioview',
                        name:'portfolioview',
                        meta: {
                            nameJp: 'ポートフォリオ一覧'
                        },
                        props: true,
                        component: () => import('./components/Learning/BasicKnowledge/PortfolioView.vue')
                    },
                    {
                        path: 'portfolio',
                        name: 'portfolio',
                        meta: {
                            nameJp: 'ポートフォリオ完成'
                        },
                        props: true,
                        component: () => import('./components/Learning/Portfolio/CompletePortfolio.vue')
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
            title: 'CLAP - コンタクト',
        },
        redirect: {name: 'tab1'}, 
        component: () => import('@/components/Contact/MainContainer.vue'),
        children: [
            {
                path: 'tab1',
                name: 'tab1',
                component: () => import('@/components/Contact/Tab1/MemberContainer.vue')
            },
            // {
            //     path: 'tab2',
            //     name: 'tab2',
            //     component: () => import('@/components/Contact/Tab2/ContactContainer.vue'),
            //     children: [
            //         {
            //             path: ':contactId',
            //             name: 'contactDetail',
            //             component: () => import('@/components/Contact/Tab2/ContactDetail.vue')
            //         }
            //     ]
            // }
        ]

    },
    {
        path: '/remind',
        name: 'remind',
        meta: {
            title: 'CLAP - リマインド',
            titleJp: 'リマインド'
        }, 
        component: () => import('./components/Remind/RemindContainer.vue'),
        children: [
            {
                path: 'project-approval/:userId',
                name: 'project-approval',
                component: () => import('./components/Global/CheckGoal.vue'),
                children: [
                    {
                        path: ':goalId',
                        name: 'goal-approval',
                        component: () => import('./components/Project/ProjectGoalMore.vue')
                    }
                ]
            },
            {
                path: 'evaluation-approval/:memberId/:span',
                name: 'evaluation-approval',
                component: () => import('./components/Global/CheckEvaluation.vue'),
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
    }

    


]
function resolveBeforeEnter(to, next, from) {
    axios.post('/profile_get_update_user', {id: to.params.userId})
    .then(response => {
        to.meta.data = response.data;
        next();
    })
    .catch(error => {
        next();
        to.meta.data = null
    });
}
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
router.afterEach(() => {
    const responsive = useResponsive()
    const sideMenuView = useSideMenuView()
    if(responsive.mobile){
        sideMenuView.setSideMenuView(false)
    }    
    cleanUp()
  });
  function cleanUp(){
    const messageUsers = useMessageUsers()
    const filePreview = useFilePreview()
    const projectUsers = useProjectUsers()
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
  }
  router.onError((error, to) => {
    if (error.message.includes('Failed to fetch dynamically imported module') || error.message.includes("Importing a module script failed")) {
      window.location = to.fullPath
    }
  })
export default router