
import { createRouter, createWebHistory } from 'vue-router'

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
        beforeEnter: (to, from, next) => {
            document.body.style.height = '100%';
            document.body.style.position = 'fixed';
            document.body.style.overflow = 'hidden';
            if(window.innerWidth < 959){
                document.body.style.background = 'var(--background-color)'
            }
            next();
        },
    },
    { 
        path: '/members', 
        name: 'members',  
        component: () => import('./components/Members/MembersRoot.vue'),
        beforeEnter: (to, from, next) => {
            document.body.style.height = '100%';
            document.body.style.position = 'fixed';
            document.body.style.overflow = 'hidden';
            if(window.innerWidth < 959){
                document.body.style.background = 'var(--background-color)'
            }
            window.document.title = 'メンバー'; 
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
                path: 'account-settings',
                component: () => import('./components/Profile/UserEditComps/UserSettingEdit.vue'),
                name: 'account-settings',
                props: true,
                beforeEnter: (to, from, next) => {
                    const rootElement = document.getElementById('app');
                    const userId = rootElement.getAttribute('data-user-id');

                    if (to.params.userId !== userId) {
                        const currentUserIdRoute = `/user/${userId}/account-settings`;
                        
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
        beforeEnter: (to, from, next) => {
            document.body.style.height = '100%';
            document.body.style.position = 'fixed';
            document.body.style.overflow = 'hidden';
            window.document.title = 'カレンダー'; 
            next();
        }, 
        
    },
    {
        path: '/work',
        name: 'work',
        component: () => import('./components/Work/WorkContainer.vue'),
        beforeEnter: (to, from, next) => {
            fetchTimeCard(to, next, from)
            document.body.style.height = '100%';
            document.body.style.position = 'fixed';
            document.body.style.overflow = 'hidden';
            window.document.title = 'ワーク';
        }
        
    },
    {
        path: '/admin_control',
        name: 'admin_control',
        component: () => import('./components/AccountControl/AdminControlList.vue'),
        beforeEnter: (to, from, next) => {
            document.body.style.height = '100%';
            document.body.style.position = 'fixed';
            document.body.style.overflow = 'hidden';
            window.document.title = '管理者'
            next();
        }, 
    },
    


]
function resolveBeforeEnter(to, next, from) {
    document.body.style.height = '100%';
    document.body.style.position = 'fixed';
    document.body.style.overflow = 'hidden';
    axios.post('/profile_get_update_user', {id: to.params.userId})
    .then(response => {
        to.meta.data = response.data;
        
        window.document.title = `プロフィール - ${response.data.name}`; 
        next();
    })
    .catch(error => {
        next();
        to.meta.data = null
        // console.error('API request failed:', from);
        // alert('メンバーが見つかりませんでした。');
        // // location.replace('/')
        // next(false);

    });
}
function fetchPosts(to, next, from, path) {
    document.body.style.height = '100%';
    document.body.style.position = 'fixed';
    document.body.style.overflow = 'hidden';
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
        const title = path == 'knowledge' ? 'ナレッジ' : path == 'nice' ? 'ナイス' : path =='challenge' ? 'チャレンジ' : 'CLAP'
        window.document.title = title; 
        next();
    })
    .catch(error => {
        // console.error('API request failed:', from);
        // alert('User Not Found');
        // location.replace('/')
        // next(false);
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

export default router