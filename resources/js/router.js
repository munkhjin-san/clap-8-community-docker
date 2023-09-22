
import { createRouter, createWebHistory } from 'vue-router'

import Board from './components/Board/Board.vue'

import Task from './components/Mobile/Task.vue'
import Memo from './components/Mobile/Memo.vue'
import File from './components/Mobile/File.vue'
import MessageContainer from './components/Board/Message/MessageContainer.vue'
import Members from './components/Members/MembersRoot.vue'
import UserComponent from './components/Profile/UserComponent.vue'
import UserInfoEdit from './components/Profile/UserEditComps/UserInfoEdit.vue'
import UserSettings from './components/Profile/UserEditComps/UserSettingEdit.vue'
import { defineAsyncComponent } from 'vue'
const routes = [
    { path: '/chat', name: 'board',  component: Board,
        children: [
            {
                path: ':chatId',
                component: MessageContainer,
                name: 'room',
                props: true,
                children: [
                    {
                        path: 'task',
                        component: Task,
                        name: 'task',
                        props: true
                    },
                    { path: 'file', name: 'file',  component: File },
                    { path: 'memo', name: 'memo',  component: Memo },
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
    { path: '/members', name: 'members',  component: Members,
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
        path: '/profile/:userId', 
        name: 'user',  
        component: UserComponent,
        props: true,
        children: [
            {
                path: 'account-settings',
                component: UserSettings,
                name: 'account-settings',
                props: true,
                beforeEnter: (to, from, next) => {
                    const rootElement = document.getElementById('app');
                    const userId = rootElement.getAttribute('data-user-id');

                    if (to.params.userId !== userId) {
                        const currentUserIdRoute = `/profile/${userId}/account-settings`;
                        
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
                component: UserInfoEdit,
                name: 'personal-info-settings',
                props: true,
                beforeEnter: (to, from, next) => {
                    const rootElement = document.getElementById('app');
                    const userId = rootElement.getAttribute('data-user-id');

                    if (to.params.userId !== userId) {
                        const currentUserIdRoute = `/profile/${userId}/personal-info-settings`;
                        
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
    { path: '/auth/:lang/terms-of-service', name: 'terms-of-service',  component:  defineAsyncComponent(() => import('./components/Terms/Terms.vue')) },
    { path: '/auth/:lang/privacy-policy', name: 'privacy-policy',  component:  defineAsyncComponent(() => import('./components/Privacy/Privacy.vue')) },
    { 
        path: '/help', 
        name: 'help',  
        component:  () => import('./components/Help/Topics.vue'),
        children: [
            { path: 'account-management', name:'AccountManagement', component: () => import('./components/Help/AccountManagement.vue')},
            { path: 'chat-guide', name:'ChatGuide', component: () => import('./components/Help/ChatGuide.vue')},
            { path: 'task-guide', name: 'TaskGuide', component: () => import('./components/Help/TaskGuide.vue' )},
            { path: 'member-guide', name:'MemberGuide', component: () => import('./components/Help/MemberGuide.vue')},
            // { path: 'other', name:'Other', component: () => import('./components/Help/Other.vue')},
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
        path: '/work',
        name: 'work',
        component: () => import('./components/Work/WorkContainer.vue'),
        beforeEnter: (to, from, next) => {
            fetchPosts(to, next, from, 'work');
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
            next();
        }, 
        
    }


]
function resolveBeforeEnter(to, next, from) {
    axios.post('/profile_get_update_user', {id: to.params.userId})
    .then(response => {
        to.meta.data = response.data;
        
        window.document.title = `GLOWD - ${response.data.name}`; 
        next();
    })
    .catch(error => {
        console.error('API request failed:', from);
        alert('User Not Found');
        location.replace('/')
        next(false);

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
        
        window.document.title = `GLOWD - ${response.data.name}`; 
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

const router = createRouter({
    history: createWebHistory(),
    routes
})

export default router