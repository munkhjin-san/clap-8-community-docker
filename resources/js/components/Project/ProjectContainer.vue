<template>
    <div class="post-root" v-if="auth.id == auth.activeUser.id">
        
        <div class="post-header" v-if="route.name === 'project'">
            <HamBurger v-if="responsive.mobile"/>
            <div class="project-search-wrap">
                <PostSearchBar 
                    className="newChatMemberSearch" 
                    :customPlaceHolder="`プロジェクト検索`" 
                    v-model="keywords"
                />                
            </div>
            <!-- <div class="c-bar-wrap">
                <div @click.stop="menu.setMenu( { id: 8, name: 'sortProject'})" class="c-bar-button" style="margin-left: 15px;">
                    ソート
                </div>
                <Transition name="modalFade">
                    <div v-if="menu.name == 'sortProject'" id="sortProject" class="calendarMemberSelector">
                        <div id="sortProject" style=" max-height: 50vh; overflow-y: auto;">
                            <div>
                                
                                <div style="padding: 0 15px;">                                                
                                    <label v-for="option in sortOptions" class="cal-member-check" style="align-self: center;padding-left: 20px;padding-bottom: 0;margin-bottom: 0;display: flex;margin: 5px 0;">
                                        <input :value="option.value" :checked="sortType === option.value" @click="toggleRadio(option.value)" name="memberCheckBox" type="radio">
                                        <span class="cal-check-mark" style="top: 5px;"></span>
                                        <div class="left-panel-items" style="width: auto;padding:5px 0;margin:0;user-select: none;cursor:pointer;background: inherit;">                    
                                            <p class="userName">{{option.name}}</p>                                    
                                        </div>
                                    </label>  
                                </div>     
                            </div>
                        </div>
                    </div>
                </Transition>
            </div> -->
        </div>
        <Transition name="modalFade">
            <div class="cal-month-loader" style="height: calc(100% - 60px); top: 60px;" v-if="initialLoader">
                <div id="loaderMini">
                    <div class="spinner-mini" style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
                </div>
            </div>
        </Transition>
        <div class="post-container scrollable">
            <router-view v-slot="{ Component }">
                <transition name="lessonShift">
                    <component
                        v-if="selectedProject"
                        :is="Component"
                        :selectedProject="selectedProject"
                        :selectedDate="selectedDate"
                        :key="selectedProject?.id"
                    />
                </transition>
            </router-view>
            <div class="project-table" v-if="route.name === 'project'">
                <div class="project-header-row">
                    <div class="project-cell">プロジェクト名</div>
                    <div class="project-cell">概要</div>
                    <div class="project-cell">戦略</div>
                    <div class="project-cell">期間</div>
                    <div class="project-cell">取締役</div>
                    <div class="project-cell">管理者</div>
                    <div class="project-cell">メンバー</div>
                    
                </div>
                <div class="project-cell-row" v-for="project in searchResults">
                    <div class="project-cell" data-label="プロジェクト名" @click="router.push({name: 'projectdetail', params: { projectId: project?.id}})">
                        <div class="user-link">
                            {{ project.name }}
                        </div>
                    </div>
                    <div class="project-cell" data-label="概要">
                        <div style="position: relative;">
                            <div class="text-wrap" @click.stop="menu.setMenu({name: 'overviewBox', id: project?.id})">
                                {{ project.overview }}
                            </div>
                            <div @click="menu.close()" style="width: 100%" class="comment-box" id="overviewBox" v-if="menu.name == 'overviewBox' && menu.id == project?.id">
                                <div style="word-break: break-word;">{{ project.overview }}</div>                              
                            </div>
                        </div>
                        
                    </div>
                    <div class="project-cell" data-label="戦略">
                        <div style="position: relative">
                            <div class="text-wrap" @click.stop="menu.setMenu({name: 'strategyBox', id: project?.id})">
                                {{ project.strategy }}
                            </div>
                            <div @click="menu.close()" style="width: 100%" class="comment-box" id="strategyBox" v-if="menu.name == 'strategyBox' && menu.id == project?.id">
                                <div style="word-break: break-word;">{{ project.strategy }}</div>                              
                            </div>
                        </div>
                        
                    </div>
                    <div class="project-cell" data-label="期間">
                        <div v-if="project?.date_start">{{ project.date_start }} ～ {{ project.date_end }}</div>
                    </div>
                    <div class="project-cell" data-label="取締役">
                        <div>
                            <UserIcon v-if="project?.director" imgClass="u_icon_20" :user="project?.director" size="30"/>
                        </div>
                    </div>
                    <div class="project-cell" data-label="管理者">
                        <div>
                            <UserIcon v-for="member in project.manager" imgClass="u_icon_20" :user="member" size="30"/>
                        </div>
                    </div>
                    <div class="project-cell" data-label="メンバー" style="overflow: hidden">
                        <div style="display: flex;">
                            <div style="display: flex;" @click="viewUsers(project.members)">
                                <UserIcon v-for="member in project.members.slice(0, 15)" :disable-instant="true" imgClass="u_icon_20" :user="member" size="30"/>
                            </div>
                            <span style="margin: auto 0; cursor: pointer; font-size: 12px;" v-if="project.members.length > 15">...({{project.members.length}})</span>
                        </div>
                        
                    </div>
                    
                </div>
                
            </div>
        </div>
        
    </div>
    <div v-else style="height: 100%;width: 100%;">
        <div v-if="responsive.mobile" style="min-height: 60px;display: flex;align-items: center">
            <HamBurger/>
        </div>        
        <div style="color:var(--primary-color);height: 100%;width: 100%;text-align: center;justify-content: center;display: flex;align-items: center;flex-direction: column;">
            <p>アクセス権限ありません。</p>
            <router-link class="l-button" style="margin: 30px 0 70px 0;" to="/board">ボードへ戻る</router-link>
        </div>        
    </div>
</template>
<script lang="ts" setup>
import { Project } from '@/interface/projectInterface';
import axios from 'axios';
import { nextTick, onMounted, ref, computed, provide, watch, inject } from 'vue';
import UserIcon from '../Board/Mixed/UserIcon.vue';
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
const projects = ref<Project[]>([])
const keywords = ref('')
const initialLoader = ref(true)
const menu = useMenuStore()
const route = useRoute()
const router = useRouter()
const auth = useAuthUserStore()
const responsive = useResponsive()
// const selectedDate = ref<string | undefined>('')
// const metricDate = ref<string | undefined>('')
const evaluationDate = ref('')
const { notify } = inject<Dialog>('dialog')!
const options = detailedDateOptions()
const sortType = ref(0)
const projectUsers = useProjectUsers()
const sortOptions = [
    {
        value: 1,
        name: 'プロジェクトメンバー'
    },
    {
        value: 2,
        name: 'プロジェクト取締役'
    },
    {
        value: 3,
        name: 'プロジェクト管理者'
    },
    {
        value: 4,
        name: 'プロジェクトメンター'
    }
]
onMounted(async() => {
    setInitialDates()
    await getProjects();
})

const toggleRadio = (value: number) => {
    if (sortType.value === value) {
        sortType.value = 0
    } else {
        sortType.value = value
    }
}
const setInitialDates = () => {
    const currentMonth = moment().month();
    if (currentMonth >= 1 && currentMonth < 7) {
        evaluationDate.value = moment().month(1).date(1).format('YYYY-MM-DD');
    } else {
        evaluationDate.value = moment().month(7).date(1).format('YYYY-MM-DD');
    }
};
const selectedDate = computed(() => {
    return options.find(ob => ob.evaluationDate === evaluationDate.value)
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
    switch (sortType.value) {
        case 1: 
            return searchResults.value.filter(
                project => project?.members.some(member => member.id === auth.id))
        case 2: 
            return searchResults.value.filter(
                project => project?.director?.id === auth.id)
        case 3:
            return searchResults.value.filter(
                project => project?.manager.some(manager => manager.id === auth.id))
        case 4:
            return searchResults.value.filter(
                project => project.members.some(
                    member => member?.evaluation?.mentor?.id === auth.id)
            )
        default:
            return searchResults.value
    }
    
})
const getProjects = async() => {
    const params = { 
        evaluation_date: evaluationDate.value
    }
    try {
        projects.value = await axios.get('/get_projects', {params: params}).then(res => res.data)
        nextTick(() => {
            initialLoader.value = false
        })
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
        return membersArray.some((member: { id: number | null; }) => member && member.id === auth.activeUser.id) 
            || managerArray.some((member: { id: number | null; }) => member && member.id === auth.activeUser.id);
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
provide('authProjects', authProjects)
provide('selectedDate', selectedDate)
provide('evaluationDate', evaluationDate)
// provide('metricDate', metricDate)
provide('getProjects', getProjects)
provide('setDates', setInitialDates)
</script>
<style lang="scss">
    .project-table {
        display: table;
        border-collapse: collapse;
        width: 100%;
        font-size: 13px;
        background-color: var(--background-color);
        color: var(--primary-color);
    }

    .project-cell {
        display: table-cell;
        border: 1px solid var(--calendarBorder);
        text-align: left;
        padding: 5px 15px;
        line-height: normal;
        max-width: 250px;
        height: 40px;
        vertical-align: middle;
    }
    .text-wrap {
        white-space: break-spaces;
        overflow: hidden;
        text-overflow: ellipsis;
        word-break: break-word;
        max-height: 40px;
    }
    .project-header-row {

        display: table-row;
        position: sticky;
        top: -1px;
        background-color: var(--background-color);
        z-index: 1;
    }
    .project-cell-row {
        display: table-row;
    }
    .project-cell-row:hover{
        background-color: var(--bg3);
        cursor: pointer;
    }
    .project-search-wrap {
        margin-left: 20px;
        width: 30%;
    }
    @media screen and (max-width: 959px)  {
        // .project-table {
        //     overflow-x: auto;
        //     white-space: nowrap;
        //     display: block;
        //     scrollbar-width: none;
        // }
        .project-search-wrap {
            width: calc(100% - 140px);
            margin-left: unset;
        }
        .project-table {
            display: block; /* Display block for cards */
            background-color: unset;
        }

        .project-header-row {
            display: none; /* Hide the header on mobile */
        }

        .project-cell-row {
            display: block;
            border: 1px solid var(--calendarBorder);
            margin-bottom: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            padding: 10px;
            background-color: var(--background-color);
        }

        .project-cell {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border: none;
            border-bottom: 1px solid var(--calendarBorder);
            max-width: 100%;
            word-break: keep-all;
        }

        .project-cell:last-child {
            border-bottom: none; /* Remove bottom border for the last item */
        }

        .project-cell::before {
            content: attr(data-label);
            // font-weight: bold;
            flex: 1;
            color: var(--secondary-color);
            margin-right: 10px;
            text-transform: capitalize;
        }
    }
</style>