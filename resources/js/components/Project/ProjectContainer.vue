<template>
    <div class="post-root">
        
        <div class="post-header" v-if="route.name === 'project'">
            <HamBurger v-if="responsive.mobile"/>
            <div class="project-search-wrap">
                <PostSearchBar 
                    className="newChatMemberSearch" 
                    :customPlaceHolder="`プロジェクト検索`" 
                    @search-start="(word) => {keywords = word}"
                />                
            </div>
            <div class="flex gap-[10px] ml-auto mr-[20px]">
                <router-link :to="{name: 'assets'}" class="c-bar-button whitespace-nowrap">物品</router-link>
                <router-link :to="{name: 'gantt-chart'}" class="c-bar-button whitespace-nowrap">ガントチャート</router-link>
            </div>
            
        </div>
        <Transition name="modalFade">
            <div class="cal-month-loader" style="height: calc(100% - 60px); top: 60px;" v-if="initialLoader && route.name === 'project'">
                <div id="loaderMini">
                    <div class="spinner-mini" style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
                </div>
            </div>
        </Transition>
        <div class="post-container scrollable" v-if="route.name === 'project'">
            
            <div class="project-table">
                <div class="project-header-row">
                    <div class="project-cell">プロジェクト名</div>
                    <div class="project-cell">概要</div>
                    <div class="project-cell">戦略</div>
                    <div class="project-cell">期間</div>
                    <!-- <div class="project-cell cursor-pointer" @click="sortType = 2">取締役<span v-if="sortType === 2">▲</span></div> -->
                    <div class="project-cell cursor-pointer relative">
                        <div @click.stop="menu.setMenu({parent: 'projectManagerSelect'})">管理者</div>
                        <div class="flex flex-wrap">
                            <UserPanel v-for="member in activeManagers" disable-instant :user="member" size="15"/>
                        </div>
                        <Transition name="slidePop">
                            <ProjectMemberSort 
                                v-if="menu.parent == 'projectManagerSelect'" 
                                id="projectManagerSelect" 
                                :members="sortableUsers('manager')" 
                                v-model:selected-users="selectedManagers"
                                custom-place-holder="管理者検索"
                            />
                        </Transition>
                    </div>
                    <div class="project-cell cursor-pointer relative">
                        <div @click.stop="menu.setMenu({parent: 'projectMemberSelect'})">メンバー</div>
                        <div class="flex flex-wrap">
                            <UserPanel v-for="member in activeMembers" disable-instant :user="member" size="15"/>
                        </div>
                        <Transition name="slidePop">
                            <ProjectMemberSort 
                                v-if="menu.parent == 'projectMemberSelect'" 
                                id="projectMemberSelect" 
                                :members="sortableUsers('members')" 
                                v-model:selected-users="selectedMembers"
                                custom-place-holder="メンバー検索"
                            />
                        </Transition>                        
                    </div>
                    
                </div>
                <div class="project-cell-row" v-for="project in sortedProjects">
                    <div class="project-cell" >
                        
                        <div class="flex justify-between w-full">
                            <div class="user-link flex gap-2 items-center" style="position: relative" @click="router.push({name: 'projectdetail', params: { projectId: project?.id}})">
                                <p>{{ project.name }}</p>
                                <WeatherIcon v-if="project.project_conditions.length" :which="project.project_conditions[0].value" size="15" style="margin-top: 3px;"/>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="side-notification" style="position: static;" v-if="projectBadge && projectBadge[project.id]">{{ projectBadge[project.id] }}</span>

                                <div v-if="project.board_id && (project?.members.some(ob => ob.id === auth.id || project?.manager.some(ob => ob.id === auth.id)))" class="flex">
                                    <svg @click="router.push(`/board/${project.board_id}`)" class="side-app-icon" version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 39 32" style="width: 20px !important; height: 17px; min-width: 20px;">
                                        <path d="M39.365 27.314v-2.971l-0.013-3.975-0.076-15.873v-0.14l-0.013-0.165c-0.013-0.114-0.013-0.229-0.025-0.343s-0.038-0.229-0.051-0.343l-0.089-0.33c-0.14-0.432-0.33-0.851-0.597-1.219-0.254-0.368-0.584-0.698-0.94-0.978s-0.762-0.495-1.194-0.635c-0.432-0.14-0.889-0.229-1.333-0.229l-10.933-0.013-19.619-0.089c-0.038-0.013-0.114-0.013-0.165-0.013l-0.178 0.013c-0.457 0.038-0.914 0.127-1.346 0.305s-0.825 0.419-1.181 0.724c-0.356 0.292-0.66 0.66-0.902 1.054-0.254 0.394-0.444 0.825-0.546 1.283-0.038 0.114-0.051 0.229-0.064 0.343s-0.038 0.229-0.038 0.343l-0.013 0.178v3.378l-0.025 3.987-0.025 7.949v8.076l0.013 0.178c0.013 0.114 0.013 0.241 0.025 0.356s0.038 0.229 0.051 0.356l0.089 0.343c0.14 0.457 0.343 0.876 0.61 1.27s0.597 0.724 0.965 1.016c0.368 0.279 0.787 0.521 1.244 0.66 0.444 0.152 0.914 0.241 1.384 0.241l30.87-0.038c0.47-0.038 0.927-0.14 1.359-0.317s0.838-0.432 1.194-0.724c0.356-0.305 0.66-0.66 0.902-1.054s0.432-0.838 0.533-1.295c0.038-0.114 0.051-0.229 0.063-0.343s0.038-0.229 0.038-0.343l0.013-0.178v-0.165l0.013-0.279zM36.914 4.419v0.064l-0.076 15.873-0.013 3.975v3.39c0 0.051-0.013 0.102-0.013 0.14 0 0.051-0.013 0.102-0.025 0.14-0.038 0.19-0.127 0.368-0.229 0.533s-0.229 0.317-0.381 0.444-0.317 0.229-0.495 0.305c-0.178 0.076-0.368 0.114-0.559 0.127l-30.667-0.025c-0.19 0-0.381-0.038-0.559-0.102-0.178-0.051-0.356-0.152-0.508-0.267s-0.292-0.254-0.406-0.406c-0.102-0.152-0.19-0.33-0.241-0.508-0.013-0.051-0.025-0.089-0.038-0.14l-0.025-0.14c-0.013-0.051-0.013-0.089-0.013-0.14v-8.127l-0.013-7.936-0.013-3.975v-3.378c0-0.051 0.013-0.102 0.013-0.14s0.013-0.102 0.025-0.14c0.038-0.19 0.114-0.368 0.216-0.546 0.102-0.165 0.229-0.33 0.381-0.457s0.317-0.229 0.495-0.305c0.178-0.076 0.368-0.114 0.571-0.14h1.905l1.981-0.013 15.873-0.076 10.908-0.013c0.203 0 0.419 0.051 0.61 0.114 0.203 0.063 0.381 0.165 0.546 0.292s0.317 0.279 0.432 0.457c0.114 0.178 0.203 0.356 0.267 0.559l0.038 0.152 0.025 0.152c0.013 0.051 0.013 0.102 0.013 0.152v0.076c-0.025-0.013-0.025 0-0.025 0.025z"></path><path d="M32.14 8.203c-2.070-0.076-4.152-0.127-6.222-0.152-1.041-0.013-2.070-0.025-3.111-0.038l-3.111-0.013-3.111 0.013-3.111 0.038c-1.041 0.013-2.070 0.038-3.111 0.051l-1.537 0.025-1.562 0.038c-0.495 0.013-0.902 0.419-0.927 0.927-0.013 0.521 0.406 0.952 0.927 0.965l1.562 0.038 1.549 0.025c1.041 0.013 2.070 0.038 3.111 0.051l3.111 0.038 3.111 0.013 3.111-0.013c1.041 0 2.070-0.025 3.111-0.038 2.070-0.025 4.152-0.063 6.222-0.152 0.47-0.025 0.851-0.394 0.876-0.876 0.013-0.495-0.381-0.927-0.889-0.94zM25.6 15.073c-1.524-0.076-3.060-0.114-4.584-0.152-0.762-0.013-1.524-0.038-2.286-0.038l-2.298-0.013-2.286 0.013-2.286 0.038c-1.524 0.025-3.060 0.051-4.584 0.114-0.483 0.025-0.889 0.406-0.902 0.902-0.025 0.521 0.381 0.965 0.902 0.99 1.524 0.064 3.060 0.089 4.584 0.114l2.286 0.025 2.286 0.013 2.298-0.013c0.762 0 1.524-0.025 2.286-0.038 1.524-0.025 3.060-0.064 4.584-0.152 0.457-0.025 0.838-0.394 0.863-0.863 0.025-0.483-0.356-0.914-0.863-0.94zM19.060 21.956c-0.978-0.089-1.968-0.114-2.946-0.152s-1.968-0.038-2.946-0.038c-0.495 0-0.978 0-1.473 0.013l-1.473 0.038c-0.978 0.025-1.968 0.051-2.946 0.114-0.47 0.025-0.851 0.406-0.889 0.889-0.038 0.521 0.368 0.965 0.889 1.003 0.978 0.064 1.968 0.089 2.946 0.114l1.473 0.038c0.495 0.013 0.978 0.013 1.473 0.013 0.978-0.013 1.968-0.013 2.946-0.038s1.968-0.064 2.946-0.152c0.432-0.038 0.8-0.381 0.838-0.838 0.025-0.521-0.343-0.965-0.838-1.003z"></path>
                                    </svg>
                                </div>
                            </div>
                            
                        </div>
                    </div>
                    <div class="project-cell pc">
                        <div style="position: relative;">
                            <div class="text-wrap" @click.stop="menu.setMenu({name: 'overviewBox', id: project?.id})">
                                {{ project.overview }}
                            </div>
                            <div @click="menu.close()" style="width: 100%" class="comment-box" id="overviewBox" v-if="menu.name == 'overviewBox' && menu.id == project?.id">
                                <div style="word-break: break-word;">{{ project.overview }}</div>                              
                            </div>
                        </div>
                        
                    </div>
                    <div class="project-cell pc">
                        <div style="position: relative">
                            <div class="text-wrap" @click.stop="menu.setMenu({name: 'strategyBox', id: project?.id})">
                                {{ project.strategy }}
                            </div>
                            <div @click="menu.close()" style="width: 100%" class="comment-box" id="strategyBox" v-if="menu.name == 'strategyBox' && menu.id == project?.id">
                                <div style="word-break: break-word;">{{ project.strategy }}</div>                              
                            </div>
                        </div>
                        
                    </div>
                    <div class="project-cell pc">
                        <div v-if="project?.date_start">{{ project.date_start }} ～ {{ project.date_end }}</div>
                    </div>
                    <!-- <div class="project-cell pc">
                        <div>
                            <UserPanel v-if="project?.director" imgClass="u_icon_20" :user="project?.director" size="20"/>
                        </div>
                    </div> -->
                    <div class="project-cell pc">
                        <div>
                            <UserPanel v-for="member in project.manager" imgClass="u_icon_20" :user="member" size="20"/>
                        </div>
                    </div>
                    <div class="project-cell pc" style="overflow: hidden">
                        <div style="display: flex;">
                            <div style="display: flex;" @click="viewUsers(project.members)">
                                <UserPanel v-for="member in project.members.slice(0, 10)" :disable-instant="true" imgClass="u_icon_20" :user="member" size="20"/>
                            </div>
                            <span style="margin: auto 0; cursor: pointer; font-size: 12px;" v-if="project.members.length > 10">...({{project.members.length}})</span>
                        </div>
                        
                    </div>
                    
                </div>
                
            </div>
        </div>
        <FloatButton type="plus" @action="createWindow = true" v-if="auth.activeUser.position_id <= 6 && (route.name == 'gantt-chart' || route.name == 'project')"/>
        <Transition name="modalFade">
            <ProjectCreate 
                v-if="createWindow"
                @close="createWindow = false, editData = null"
                @getProjects="getProjects"
                :userList="userList"
                :edit-data="editData"
            />
        </Transition>
        <div class="z-24 absolute w-full h-full top-[0] left-[0] bg-[var(--bg2)]" v-if="route.name !== 'project'">
            <router-view v-slot="{ Component }">
                <!-- <transition name="lessonShift"> -->
                    <component
                        @getProjects="getProjects"
                        @edit="(rec) => {editData = rec; createWindow = true}"
                        :is="Component"
                        :selectedProject="selectedProject"
                        :userList="userList"
                        :maxInterval="totalSpan"
                        ref="taskComponent"
                    />
                <!-- </transition> -->
            </router-view>
        </div>
    </div>
    
</template>
<script lang="ts" setup>
import { Project } from '@/interface/projectInterface';
import axios from 'axios';
import { nextTick, onMounted, ref, computed, provide, watch, inject, useTemplateRef, reactive } from 'vue';
import UserPanel from '../Global/UserPanel.vue';
import PostSearchBar from '../Post/PostSearchBar.vue';
import { useMenuStore } from '@/store/menu';
import { useRoute, useRouter } from 'vue-router';
import { useAuthUserStore } from '@/store/auth';
import HamBurger from '../Global/HamBurger.vue';
import { useResponsive } from '@/store/responsive';
import moment from 'moment';
import { Dialog, User } from '@/interface/globalInterface';
import { detailedDateOptions } from '@/utils/tools';
import { useProjectUsers } from '@/store/projectUsers';
import { useBadgeStore } from '@/store/badge';
import { DateTime, Interval } from 'luxon';
import TaskComponent from '../Task/TaskComponent.vue';
import { ComponentExposed } from 'vue-component-type-helpers'
import ProjectCreate from '../AccountControl/ProjectControl/ProjectCreate.vue';
import FloatButton from '../Global/FloatButton.vue';
import WeatherIcon from '../Global/WeatherIcon.vue';
import ProjectMemberSort from './ProjectMemberSort.vue';
const projects = ref<Project[]>([])
const keywords = ref('')
const initialLoader = ref(true)
const menu = useMenuStore()
const route = useRoute()
const router = useRouter()
const auth = useAuthUserStore()
const responsive = useResponsive()
const evaluationDate = reactive({year: '', which_half: ''})
const { notify } = inject<Dialog>('dialog')!
const options = detailedDateOptions()
const sortType = ref(0)
const projectUsers = useProjectUsers()
const userList = ref([])
const editData = ref(null)
const createWindow = ref(false)
const selectedManagers = ref<number[]>([])
const selectedMembers = ref<number[]>([])
const badge = useBadgeStore()
const taskComponent = useTemplateRef<ComponentExposed<typeof TaskComponent>>('taskComponent')
onMounted(async() => {
    await getProjects();
    getSelectableUsers()
})
const projectBadge = computed(() => {
    return badge.project.project_counts
})

const totalSpan = computed(() => {
    let startPoint: DateTime = DateTime.now().startOf('year');
    let endPoint: DateTime = DateTime.now().plus({ year: 1 }).endOf('year');
    projects.value.forEach((project: { date_start?: string; date_end?: string }) => {
        const startDate = project.date_start ? DateTime.fromISO(project.date_start) : null;
        const endDate = project.date_end ? DateTime.fromISO(project.date_end) : null;
        if (startDate?.isValid) {
            startPoint = DateTime.min(startPoint, startDate);
        }
        if (endDate?.isValid) {
            endPoint = DateTime.max(endPoint, endDate);
        }
    });
    return Interval.fromDateTimes(startPoint, endPoint)    
})
const sortByPosition = computed(() => {
    if (auth.user?.position_id === 13) {
        return projects.value.filter(
                project => project?.members.some(member => member.id === auth.id))
    }
    return projects.value
})

const searchResults = computed(() => {
    if(keywords.value){
        const lowSearch = keywords.value.toLowerCase()

        const deepSearch = (obj) => {
            if (typeof obj === 'string' || typeof obj === 'number') {
                return String(obj).toLowerCase().includes(lowSearch);
            } else if (Array.isArray(obj)) {
                return obj.some(item => deepSearch(item));
            } else if (typeof obj === 'object' && obj !== null) {
                return Object.values(obj).some(val => deepSearch(val));
            }
            return false;
        }
        return sortByPosition.value.filter(project => deepSearch(project))
    }
    return sortByPosition.value
})
const sortedProjects = computed(() => {
    const hitManagers = searchResults.value.filter(project => {
        if (selectedManagers.value.length) {
            return project.manager.some(manager => selectedManagers.value.includes(manager.id));
        }
        return true;
    });
    const hitMembers = hitManagers.filter(project => {
        if (selectedMembers.value.length) {
            return project.members.some(member => selectedMembers.value.includes(member.id));
        }
        return true;
    });
    return hitMembers    
})
const getSelectableUsers = async() => {
    try {
        const data = await axios.post('/get_selectable_users').then(res => res.data)
        userList.value = data.users
    } catch (e) {

    }
}
const getProjects = async() => {
    try {
        const today = DateTime.now()
        const which_half = today.month >= 3 && today.month <= 9 ? 'first' : 'second'
        const year = which_half ==='second' ? (today.year - 1).toString() : today.year.toString
        const params = {
            year: year,
            which_half: which_half
        }
        projects.value = await axios.get('/get_projects', { params: params }).then(res => res.data)
        nextTick(() => {
            initialLoader.value = false
        })
        if(route.name == 'gantt-chart'){
            taskComponent.value?.setDate()
        }
    } catch (e) {
        notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
    }
}
const activeId = computed(() => {
    return route.params && route.params.projectId ?  (Array.isArray(route.params.projectId) ? parseInt(route.params.projectId[0]) : parseInt(route.params.projectId)) : null
})
const selectedProject = computed(() => {
    return activeId.value ? projects.value.find(ob => ob.id == activeId.value) : null
})
const authProjects = computed(() => {
    return projects.value.filter(project => {
        const membersArray = Array.isArray(project.members) ? project.members : Object.values(project.members) as User[];
        const managerArray = Array.isArray(project.manager) ? project.manager : Object.values(project.manager) as User[]
        const director = project?.director
        return membersArray.some((member: { id: number | null; }) => member && member.id === auth.id) 
            || managerArray.some((member: { id: number | null; }) => member && member.id === auth.id)
            || director?.id === auth.id;
    });
})
const viewUsers = (members: User[]) => {
    const data = {
        active: true,
        userList: members,
        title: 'プロジェクトメンバー'
    }
    projectUsers.setProjectUsers(data)
    
}

const sortableUsers = (which:string) => {
    const selectable = <User[]>[]
    projects.value.map(project => {
        const targets = project[which]
        if(targets){
            targets.forEach(manager => {
                if(!selectable.some(ob => ob.id === manager.id)){
                    selectable.push(manager)
                }
            })
        }
    })
    const findIndexAuth = selectable.findIndex(ob => ob.id === auth.id)
    const authUser = selectable[findIndexAuth]
    if(authUser && findIndexAuth > -1){
        selectable.splice(findIndexAuth, 1)
        selectable.unshift(authUser)
    }
    return selectable
}
const activeManagers = computed(() => {
    const target = projects.value.flatMap(project => project.manager).filter(m => selectedManagers.value.includes(m.id))
    const uniqueTargets = target.filter((v, i, a) => a.findIndex(t => (t.id === v.id)) === i)
    return uniqueTargets
})
const activeMembers = computed(() => {
    const target = projects.value.flatMap(project => project.members).filter(m => selectedMembers.value.includes(m.id))
    const uniqueTargets = target.filter((v, i, a) => a.findIndex(t => (t.id === v.id)) === i)
    return uniqueTargets
})
provide('authProjects', authProjects)
// provide('metricDate', metricDate)
provide('getProjects', getProjects)
</script>
<style scoped>
    @media screen and (max-width: 959px) {
        .project-cell{
            border-bottom: none;
            
        }
        .project-cell-row{
            margin: 0 15px 15px 15px !important;
            box-shadow: none !important;
        }
    }
</style>