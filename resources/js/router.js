
import { createRouter, createWebHistory } from 'vue-router'
import { useMessageUsers } from '@/store/messageUsers'
import { useFilePreview } from "@/store/filePreview"
import { useResponsive } from '@/store/responsive'
import { useSideMenuView } from '@/store/sideMenuView'
import { useAuthUserStore } from './store/auth'
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
                ]
            }
        ],
    },
    { 
        path: '/members', 
        name: 'members',  
        meta: {
            title: 'CLAP - メンバー',
        }, 
        component: () => import('./components/Members/MembersRoot.vue'),
        beforeEnter: (to, from, next) => {
            if(window.innerWidth < 959){
                document.body.style.background = 'var(--background-color)'
            }
            next();
        }, 
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
            {
                path: 'salary-issue',
                component: () => import('./components/Profile/Issue/Salary.vue'),
                name: 'salary-issue',
                props: true,
                meta: {
                    title: 'CLAP - 昇給課題',
                },
                beforeEnter: (to, from, next) => {
                    const rootElement = document.getElementById('app');
                    const userId = rootElement.getAttribute('data-user-id');

                    if (to.params.userId !== userId) {
                        const currentUserIdRoute = `/user/${userId}/salary-issue`;
                        
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
            // {
            //     path: 'performance-goals',
            //     component: () => import('./components/Profile/Issue/Performance.vue'),
            //     name: 'performance-goals',
            //     props: true,
            //     meta: {
            //         title: 'CLAP - 成果目標',
            //     },
            //     beforeEnter: (to, from, next) => {
            //         const rootElement = document.getElementById('app');
            //         const userId = rootElement.getAttribute('data-user-id');

            //         if (to.params.userId !== userId) {
            //             const currentUserIdRoute = `/user/${userId}/salary-issue`;
                        
            //             if (to.path !== currentUserIdRoute) {
            //                 next(currentUserIdRoute);
            //             } else {
            //                 next();
            //             }
            //         } else {
            //             next();
            //         }
            //     },

            // }

        ],
        beforeEnter: (to, from, next) => {
            resolveBeforeEnter(to, next, from);
        },
    },
    {
        path: '/knowledge',
        name: 'knowledge',
        meta: {
            title: 'CLAP - ナレッジ',
        }, 
        component: () => import('./components/Post/PostContainer.vue'),
        beforeEnter: (to, from, next) => {
            fetchPosts(to, next, from, 'knowledge');
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
            fetchPosts(to, next, from, 'nice');
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
            fetchPosts(to, next, from, 'challenge');
        },
    },
    {
        path: '/calendar',
        name: 'calendar',
        meta: {
            title: 'CLAP - カレンダー',
        }, 
        component: () => import('./components/Calendar/CalendarContainer.vue'),       
        
    },
    {
        path: '/work',
        name: 'work',
        props: true,
        meta: {
            title: 'CLAP - ワーク',
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
            }
        ],
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
                beforeEnter: (to, from, next) => {
                    axios.get(`/get_lessons?lesson_theme_id=${to.params.lessonThemeId}`).then(
                        response => {
                            to.meta.data = response.data
                            next();
                        })
                }
            }
        ],
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