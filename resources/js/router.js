
import { createRouter, createWebHistory } from 'vue-router'
import store from './store' 
// import Board from './components/Board/Board.vue'

import Task from './components/Mobile/Task.vue'
import axios from 'axios'
const routes = [
    { 
        path: '/board', 
        name: 'board', 
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
                    { path: 'memo', name: 'memo',  component:  () => import('./components/Mobile/Memo.vue'), },
                ]
            }
        ],
    },
    { 
        path: '/members', 
        name: 'members',  
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
        children: [
            
            {
                path: 'personal-info-settings',
                component: () => import('./components/Profile/UserEditComps/UserInfoEdit.vue'),
                name: 'personal-info-settings',
                props: true,
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

        ],
        beforeEnter: (to, from, next) => {
            resolveBeforeEnter(to, next, from);
        },
    },
    { 
        path: '/help', 
        name: 'help',  
        component:  () => import('./components/Help/Topics.vue'),
        children: [
            { path: 'account-management', name:'AccountManagement', component: () => import('./components/Help/AccountManagement.vue')},
            { path: 'chat-guide', name:'ChatGuide', component: () => import('./components/Help/ChatGuide.vue')},
            { path: 'task-guide', name: 'TaskGuide', component: () => import('./components/Help/TaskGuide.vue' )},
            { path: 'member-guide', name:'MemberGuide', component: () => import('./components/Help/MemberGuide.vue')},
            { path: 'report-problem', name:'ReportProblem', component: () => import('./components/Help/Report.vue')},
            
        ]
    },
    {
        path: '/knowledge',
        name: 'knowledge',
        component: () => import('./components/Post/PostContainer.vue'),
        beforeEnter: (to, from, next) => {
            fetchPosts(to, next, from, 'knowledge');
        },
    },
    {
        path: '/nice',
        name: 'nice',
        component: () => import('./components/Post/PostContainer.vue'),
        beforeEnter: (to, from, next) => {
            fetchPosts(to, next, from, 'nice');
        },
    },
    {
        path: '/challenge',
        name: 'challenge',
        component: () => import('./components/Post/PostContainer.vue'),
        beforeEnter: (to, from, next) => {
            fetchPosts(to, next, from, 'challenge');
        },
    },
    {
        path: '/calendar',
        name: 'calendar',
        component: () => import('./components/Calendar/CalendarContainer.vue'),       
        
    },
    {
        path: '/work',
        name: 'work',
        props: true,
        component: () => import('./components/Work/WorkContainer.vue'),
        beforeEnter: (to, from, next) => {
            fetchTimeCard(to, next, from)
        }
        
    },
    {
        path: '/admin_control',
        name: 'admin_control',
        component: () => import('./components/AccountControl/AdminControlList.vue'),
        children: [
            { path: 'account',props: true, name: 'account', component: () => import('./components/AccountControl/AdminAccount.vue') },
            { path: 'workgroup',props: true, name: 'workgroup', component: () => import('./components/AccountControl/AdminWorkGroup.vue') },
            { path: 'workcontrol',props: true, name: 'workcontrol', component: () => import('./components/AccountControl/AdminWork.vue') },
            { path: 'clapcount',props: true, name: 'clapcount', component: () => import('./components/AccountControl/AdminClapCount.vue') },
            { path: 'learningcontrol',props: true, name: 'learningcontrol', component: () => import('./components/AccountControl/LearningControl/LearningControl.vue') },
        ],
        beforeEnter: (to, from, next) => {
            const rootElement = document.getElementById('app');
            const userId = parseInt(rootElement.getAttribute('data-user-id'));
            const viewTrayUsers = [608, 610]
            if(!viewTrayUsers.includes(userId)){
                next({name:'board'});
            }else{
                next();
            }
        }, 
    },
    {
        path: '/support',
        name: 'support',
        component: () => import('./components/Support/Support.vue'),
        children: [
            { path: 'faq',props: true, name: 'faq', component: () => import('./components/Support/Faq.vue') },
            { path: 'email_consult',props: true, name: 'email_consult', component: () => import('./components/Support/MailConsult.vue') },
            { path: 'phone_consult',props: true, name: 'phone_consult', component: () => import('./components/Support/PhoneConsult.vue') },
            { path: 'email_inbox',props: true, name: 'email_inbox', component: () => import('./components/Support/Inbox.vue'), 
                beforeEnter: (to, from, next) => {
                    const rootElement = document.getElementById('app');
                    const userId = parseInt(rootElement.getAttribute('data-user-id'));
                    const viewTrayUsers = [610, 516, 517, 519, 518, 526, 494, 604]
                    if(!viewTrayUsers.includes(userId)){
                        next({name:'email_consult'});
                    }else{
                        next();
                    }
                    
                }, 
            }
        ],
    },
    {
        path: '/notice',
        name: 'notice',
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
        props: true,
        
    },
    {
        path: '/learning',
        name: 'learning',
        component: () => import('./components/Learning/LearningRoot.vue'),
        children: [
            { 
                path: ':lessonThemeId',
                name: 'top',
                component: () => import('./components/Learning/LessonContainer.vue'),
                children: [
                    {
                        path: 'basic',
                        name: 'basic',
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
                            }
                            
                        ]
                    },
                    {
                        path: 'discussion',
                        name: 'discussion',
                        props: true,
                        component: () => import('./components/Learning/Discussion/GroupDiscussion.vue'),
                    },
                    {
                        path: 'portfoliodraft',
                        name: 'portfoliodraft',
                        props: true,
                        component: () => import('./components/Learning/BasicKnowledge/BasicDraftPortfolio.vue')
                    },
                    {
                        path: 'portfolio',
                        name: 'portfolio',
                        props: true,
                        component: () => import('./components/Learning/Portfolio/CompletePortfolio.vue')
                    },
                    
                    {
                        path: 'form',
                        name: 'form',
                        component: () => import('./components/Learning/Portfolio/LessonForm.vue')
                    },
                    {
                        path: 'finish',
                        name: 'finish',
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
    console.log(to)
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

function fetchTimeCard(to, next, from){
    const params = {
        current_date : null,
        work_group : null
    }
    axios.post('/get_work_data', {
        params
    })
    .then(response => {
        to.meta.data = response.data
        next()
    })
    .catch(error => {
        
    })
}

const router = createRouter({
    history: createWebHistory(),
    routes
})
router.beforeEach((to, from, next) => {
    if(store.state.mobile){
        store.commit('setSideMenuView', false);
    }    
    next();
  });
export default router