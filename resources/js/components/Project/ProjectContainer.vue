<template>
    <div class="post-root">
        
        <div class="post-header">
            <HamBurger v-if="responsive.mobile"/>
            <div class="project-search-wrap">
                <PostSearchBar 
                    className="newChatMemberSearch" 
                    :customPlaceHolder="`プロジェクト検索`" 
                    @search-start="(word) => {keywords = word}"
                />                
            </div>
            <!-- <div class="flex gap-[10px] ml-auto mr-[20px]">
                <router-link :to="{name: 'assets'}" class="c-bar-button whitespace-nowrap">物品</router-link>
                <router-link :to="{name: 'gantt-chart'}" class="c-bar-button whitespace-nowrap">ガントチャート</router-link>
            </div> -->
            
        </div>
        <Transition name="modalFade">
            <div class="cal-month-loader" style="height: calc(100% - 60px); top: 60px;" v-if="initialLoader && route.name === 'project'">
                <div id="loaderMini">
                    <div class="spinner-mini" style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
                </div>
            </div>
        </Transition>
        <div class="post-container scrollable">
            
            <div class="project-table">
                <div class="project-header-row">
                    <div class="project-cell" style="min-width: 230px;">プロジェクト名</div>
                    <div class="project-cell">期間</div>
                    <div class="project-cell">サービスカテゴリ</div>
                    <div class="project-cell">顧客企業</div>
                    <div class="project-cell">パートナー企業</div>
                    <div class="project-cell">概要</div>

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
                <div @click="router.push({name: route.name == 'project' ? 'overview' : route.name, params: { projectId: project?.id}})" class="project-cell-row" :class="[{'selected-project-cell' : Number(route.params.projectId) == project.id}]" v-for="project in sortedProjects">
                    <div class="project-cell" style="min-width: 200px;width: 200px;">                        
                        <div class="flex justify-between w-full">
                            <div class="flex gap-2 items-center" style="position: relative;">
                                <p>{{ project.name }}</p>
                                <WeatherIcon v-if="project.project_conditions.length" :which="project.project_conditions[0].value" size="15" style="margin-top: 3px;"/>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="side-notification" style="position: unset;width:15px" v-if="totalBadges(project.id) > 0">{{ totalBadges(project.id) }}</span>
                            </div>
                            
                        </div>
                    </div>
                    <div class="project-cell pc">
                        <div v-if="project?.date_start">{{ DateTime.fromISO(project.date_start).toLocaleString(DateTime.DATE_SHORT) }} ~ {{ DateTime.fromISO(project.date_end).toLocaleString(DateTime.DATE_SHORT) }}</div>
                    </div>
                    <div class="project-cell pc">
                        <div style="position: relative;">
                            <div class="text-wrap">
                                <p v-for="cat in project.category || []">{{ cat }}</p>
                            </div>
                        </div>                        
                    </div>
                    <div class="project-cell pc">
                        <div style="position: relative;">
                            <div class="text-wrap">
                                <p v-for="customer in project.customers || []">{{ customer }}</p>
                            </div>
                        </div>                        
                    </div>
                    <div class="project-cell pc">
                        <div style="position: relative;">
                            <div class="text-wrap">
                                <p v-for="partner in project.partners || []">{{ partner }}</p>
                            </div>
                        </div>                        
                    </div>
                    <div class="project-cell pc">
                        <div style="position: relative;">
                            <div class="text-wrap">
                                {{ plainText(project.description) }}
                            </div>
                        </div>
                        
                    </div>


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
            <Transition name="lessonShift">
                <div class="project-slide-window" v-if="route.name !== 'project'">
                    <router-view v-slot="{ Component }">
                        <component
                            @getProjects="getProjects"
                            :is="Component"
                            :selectedProject="selectedProject"
                            :userList="userList"
                            :maxInterval="totalSpan"
                            ref="taskComponent"
                        />
                    </router-view>
                </div>
            </Transition>

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
        <Transition name="modalFade">
            <ProjectTotalFinance 
                v-if="totalFinanceWindow"
                :projects="projects.filter(pr => pr.name !== '役員')"
                @close="totalFinanceWindow = false"
            />
        </Transition>

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
import { Dialog, User } from '@/interface/globalInterface';
import { useProjectUsers } from '@/store/projectUsers';
import { useBadgeStore } from '@/store/badge';
import { DateTime, Interval } from 'luxon';
import TaskComponent from '../Task/TaskComponent.vue';
import { ComponentExposed } from 'vue-component-type-helpers'
import ProjectCreate from '../AccountControl/ProjectControl/ProjectCreate.vue';
import FloatButton from '../Global/FloatButton.vue';
import WeatherIcon from '../Global/WeatherIcon.vue';
import ProjectMemberSort from './ProjectMemberSort.vue';
import ProjectTotalFinance from './ProjectTotalFinance.vue';
const projects = ref<Project[]>([])
const keywords = ref('')
const initialLoader = ref(true)
const menu = useMenuStore()
const route = useRoute()
const router = useRouter()
const auth = useAuthUserStore()
const responsive = useResponsive()
const { notify } = inject<Dialog>('dialog')!
const projectUsers = useProjectUsers()
const userList = ref([])
const editData = ref(null)
const createWindow = ref(false)
const selectedManagers = ref<number[]>([])
const selectedMembers = ref<number[]>([])
const badge = useBadgeStore()
const taskComponent = useTemplateRef<ComponentExposed<typeof TaskComponent>>('taskComponent')
const totalFinanceWindow = ref(false)
onMounted(async() => {
    await getProjects();
    getSelectableUsers()
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
        const year = which_half ==='second' ? (today.year - 1).toString() : today.year.toString()
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
const plainText = (text?: string | null) => {
    if(!text) return ''
    return text.replace(/<("[^"]*"|'[^']*'|[^'">])*>/g,'').replace(/&nbsp;/g, '')
}
const totalBadges = (projectId: number) => {
    return badge.goalsBadgeByFilter([{by: 'project_id', value: projectId}]).length + 
    badge.salaryIssueByFilter([{by: 'project_id', value: projectId}]).length +
    badge.assetsBadgeByFilter([{by: 'project_id', value: projectId}]).length + 
    badge.taskCommentBadgeByFilter([{by: 'project_id', value: projectId}]).length
}
provide('authProjects', authProjects)
// provide('metricDate', metricDate)
provide('getProjects', getProjects)
provide('editProjects', (rec) => {editData.value = rec; createWindow.value = true})
provide('setTotalFinanceWindow', (flag:boolean) => {totalFinanceWindow.value = flag})
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