<template>
    <div class="post-root">        
        <div class="post-header justify-between">
            <HamBurger v-if="responsive.mobile"/>
            <div class="project-search-wrap">
                <PostSearchBar 
                    className="newChatMemberSearch" 
                    :customPlaceHolder="`プロジェクト検索`" 
                    @search-start="(word) => {keywords = word}"
                />                
            </div>
            <div class="c-bar-button mr-4" @click="totalFinanceWindow = true">
                収支集計
            </div>            
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
                    <div class="project-cell cursor-pointer relative">
                        <div @click.stop="menu.setMenu({parent: 'projectDateSelect'})" class="flex items-center gap-[5px] whitespace-nowrap">
                            期間
                            <Back class="rotate-[270deg]" size="10"/>
                        </div>
                        <div v-if="start && end" class="flex flex-wrap">
                           {{ start.toLocaleString(DateTime.DATE_SHORT) }} ~ {{ end.toLocaleString(DateTime.DATE_SHORT) }}
                        </div>
                        <Transition name="slidePop">
                            <ProjectDateSort
                                v-if="menu.parent == 'projectDateSelect'" 
                                id="projectDateSelect"
                                v-model:date_start="start"
                                v-model:date_end="end"
                                @filter="filterByDate"
                                @reset="getProjects"
                            />
                        </Transition>
                    </div>
                    <div class="project-cell">サービスカテゴリ</div>
                    <div class="project-cell">顧客企業</div>
                    <div class="project-cell">業種区分</div>
                    <div class="project-cell">概要</div>

                    <div class="project-cell cursor-pointer relative">
                        <div @click.stop="menu.setMenu({parent: 'projectManagerSelect'})" class="flex items-center gap-[5px] whitespace-nowrap">
                            管理者
                            <Back class="rotate-[270deg]" size="10"/>
                        </div>
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
                        <div @click.stop="menu.setMenu({parent: 'projectMemberSelect'})" class="flex items-center gap-[5px] whitespace-nowrap">
                            メンバー
                            <Back class="rotate-[270deg]" size="10"/>
                        </div>
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
                <div @click="jumpToProject(project)" class="project-cell-row" :class="[{'selected-project-cell' : Number(route.params.projectId) == project.id}]" v-for="project in sortedProjects">
                    <div class="project-cell project-title-cell">                        
                        <div class="flex w-full">
                            <div class="flex gap-2 items-center relative w-full">
                                <p class="max-w-[calc(100%-60px)] overflow-hidden whitespace-nowrap text-ellipsis">{{ project.name }}</p>
                                <WeatherIcon v-if="project.project_conditions.length" :which="project.project_conditions[0].value" size="15"/>
                                <div class="flex items-center">
                                    <span class="side-notification" style="position: unset;width:15px" v-if="totalBadges(project.id) > 0">{{ totalBadges(project.id) }}</span>
                                </div>
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
                                <p v-for="type in project.industry_type || []">{{ type }}</p>
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
                        <div class=flex>
                            <UserPanel v-for="member in project.manager" imgClass="u_icon_20" :user="member" size="20"/>
                        </div>
                    </div>
                    <div class="project-cell pc" style="overflow: hidden">
                        <div class="flex">
                            <div class="flex" @click="viewUsers(project.members)">
                                <UserPanel v-for="member in project.members.slice(0, 5)" :disable-instant="true" imgClass="u_icon_20" :user="member" size="20"/>
                            </div>
                            <span class="my-[auto] ml-[5px] text-[12px] cursor-pointer whitespace-nowrap" v-if="project.members.length > 10">...({{project.members.length}})</span>
                        </div>                        
                    </div>                    
                </div>                
            </div>
            <Transition name="lessonShift">
                <Teleport to="body" :disabled="!responsive.mobile">
                    <div class="project-slide-window" v-if="route.name !== 'project'">                       
                        <router-view v-slot="{ Component }">
                            <component
                                :is="Component"
                                :userList="userList"
                                :maxInterval="totalSpan"
                                ref="taskComponent"
                            />
                        </router-view>                        
                    </div>
                </Teleport>
            </Transition>

        </div>
        <FloatButton @action="createWindow = true" v-if="auth.activeUser.position_id <= 6 && (route.name == 'gantt-chart' || route.name == 'project')">
            <template #icon>
                <AddIcon size="15" fill="black"/>
            </template>
        </FloatButton>
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
                :projects="projectList.filter(pr => pr.name !== '役員')"
                :ownProjectIds="ownProjectIds"
                @close="totalFinanceWindow = false"
            />
        </Transition>

    </div>
    
</template>
<script lang="ts" setup>
import { Project } from '@/interface/projectInterface';
import { nextTick, onMounted, ref, computed, provide, useTemplateRef } from 'vue';
import UserPanel from '../Global/UserPanel.vue';
import PostSearchBar from '../Post/PostSearchBar.vue';
import { useMenuStore } from '@/store/menu';
import { useRoute, useRouter } from 'vue-router';
import { useAuthUserStore } from '@/store/auth';
import HamBurger from '../Global/HamBurger.vue';
import { useResponsive } from '@/store/responsive';
import { User } from '@/interface/globalInterface';
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
import { useProject } from '@/composables/project';
import Back from '../Icons/Back.vue';
import AddIcon from '../Form/AddIcon.vue';
import { useApi } from '@/composables/api';
import ProjectDateSort from './ProjectDateSort.vue';
import { useDialog } from '@/composables/dialog';
const keywords = ref('')
const initialLoader = ref(true)
const menu = useMenuStore()
const route = useRoute()
const router = useRouter()
const auth = useAuthUserStore()
const responsive = useResponsive()
const projectUsers = useProjectUsers()
const userList = ref([])
const editData = ref(null)
const createWindow = ref(false)
const selectedManagers = ref<number[]>([])
const selectedMembers = ref<number[]>([])
const badge = useBadgeStore()
const taskComponent = useTemplateRef<ComponentExposed<typeof TaskComponent>>('taskComponent')
const totalFinanceWindow = ref(false)
const api = useApi()
const start = ref<DateTime | null>(null)
const end = ref<DateTime | null>(null)
const { getProjects, projectList, usersProjects } = useProject()
const { ping } = useDialog()
onMounted(async() => {
    await getProjectData();
    getSelectableUsers()
})
const ownProjectIds = computed(() => {
    const savedIds = JSON.parse(localStorage.getItem('projectIds') || '[]')
    return savedIds ?? usersProjects.value.map(p => p.id)
})
const totalSpan = computed(() => {
    let startPoint: DateTime = DateTime.now().startOf('year');
    let endPoint: DateTime = DateTime.now().plus({ year: 1 }).endOf('year');
    projectList.value.forEach((project: { date_start?: string; date_end?: string }) => {
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
        return projectList.value.filter(
                project => project?.members.some(member => member.id === auth.id))
    }
    return projectList.value
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

    const data = await api.post('/get_selectable_users')
    userList.value = data.users

}
const getProjectData = async() => {

    await getProjects()
    nextTick(() => {
        initialLoader.value = false
    })

}



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
        projectList.value.map(project => {
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
    const target = projectList.value.flatMap(project => project.manager).filter(m => selectedManagers.value.includes(m.id))
    const uniqueTargets = target.filter((v, i, a) => a.findIndex(t => (t.id === v.id)) === i)
    return uniqueTargets
})
const activeMembers = computed(() => {
    const target = projectList.value.flatMap(project => project.members).filter(m => selectedMembers.value.includes(m.id))
    const uniqueTargets = target.filter((v, i, a) => a.findIndex(t => (t.id === v.id)) === i)
    return uniqueTargets
})
const filterByDate = () => {
    if (!start.value || !end.value) {
        ping('両方の日付を選択してください')
        return
    }
    getProjects(start.value, end.value)
    menu.close()
}
const plainText = (text?: string | null) => {
    if(!text) return ''
    return text.replace(/<("[^"]*"|'[^']*'|[^'">])*>/g,'').replace(/&nbsp;/g, '')
}
const totalBadges = (projectId: number) => {
    return badge.goalsBadgeByFilter([{by: 'project_id', value: projectId}]).length + 
    badge.salaryIssueByFilter([{by: 'project_id', value: projectId}]).length +
    badge.assetsBadgeByFilter([{by: 'project_id', value: projectId}]).length + 
    badge.taskCommentBadgeByFilter([{by: 'project_id', value: projectId}]).length 
    // + badge.financeCommentBadgeByFilter([{by: 'project_id', value: projectId}]).length
}
const jumpToProject = (project: Project) => {
    const routeName = route.name === 'project' ? 'overview' : 
                     (route.matched.some(rt => rt.name === 'project-members') ? 'project-members' : route.name);
    
    router.push({
        name: routeName,
        params: { projectId: project?.id }
    });
}
provide('editProjects', (rec) => {editData.value = rec; createWindow.value = true})
provide('setTotalFinanceWindow', (flag:boolean) => {totalFinanceWindow.value = flag})
</script>
<style scoped>
    .project-title-cell{
        min-width: 200px;
        max-width: 200px;
    }
    @media screen and (max-width: 959px) {
        .project-cell{
            border-bottom: none;
            
        }
        .project-cell-row{
            margin: 0 15px 15px 15px !important;
            box-shadow: none !important;
        }
        .project-title-cell{
            min-width: 100%;
            max-width: 100%;
        }
    }
</style>