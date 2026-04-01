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
            <div class="flex gap-4 mr-4">
                <div v-if="auth.hasPrivilage" class="c-bar-button" @click="router.push({name: 'resource'})">
                    リソース
                </div>
                <div class="c-bar-button" @click="router.push({name: 'total-finance'})">
                    集計
                </div>
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
            
            <div class="project-table" id="projectTable">
                <div class="project-header-row">
                    <div class="project-cell cursor-pointer relative" style="min-width: 230px;">
                        <div class="flex items-center justify-between">
                            プロジェクト名
                            <div class="boardMenuContainer justify-center" title="バッジフィルター" @click="cycleColor" >
                                <svg :class="['relative inline-flex side-app-icon']" id="b" data-name="Layer 2" xmlns="http://www.w3.org/2000/svg" height="13" viewBox="0 0 26.41919 29.13772">

                                    <path :class="outerClass" d="M25.46937,20.17081c-.70157-.20665-1.32643-.61917-1.87535-1.10779-.1568-.43648-.2581-1.13743-.33702-1.71444-.2225-1.77338-.33726-3.59861-.56897-5.4005-.22351-1.9303-.65829-4.09964-1.93567-5.68236-1.05961-1.35788-2.51198-2.47899-4.19012-2.91805-.0658-.01722-.11203-.07538-.11195-.1434.001-.8888.00221-1.94369.00192-1.9432C16.45214.56411,15.88663-.00073,15.18968,0c-.00286.00051-3.96694-.00011-3.96953.00062-.69612.00084-1.25979.56579-1.25898,1.26191l.00226,1.94288c.00008.06801-.04613.12651-.11193.14374-1.67792.43923-3.13019,1.55944-4.18939,2.91792-1.86672,2.37936-1.9019,5.58021-2.22435,8.42136-.08596.90176-.16644,1.79824-.27633,2.66088-.08537.60037-.14744,1.16066-.33508,1.69806-.00388.01078-.00802.02054-.01188.02924-.19033.17051-.68718.56181-.96943.70605-.28916.16669-.58436.30487-.90013.39433C.98598,20.14662-.00334,21.33119,0,21.33203c.00131.01459.01589,2.7284.01765,2.74052.00408.63617.5239,1.14843,1.16004,1.14315,2.0113-.01184,5.39329-.0454,8.30579-.07639-.02771.11275-.03795.23168-.02528.35381.0066.05091.014.15524.02378.20547.13131.86932.46419,1.65877,1.08913,2.32183,1.04497,1.09449,2.67812,1.35306,4.10748.9145,1.40367-.50467,2.30335-2.00152,2.31968-3.44421.00735-.11528-.00095-.22141-.02067-.31957,2.90046.02099,6.25362.04069,8.25747.05276.64152.0037,1.16458-.51332,1.16828-1.15494.00291-.01111.01182-2.72508.01582-2.73693.00406-.00028-.99096-1.1922-.9498-1.16122ZM23.53751,19.01346s.00189.00185.00246.00241c-.00576-.00511-.0117-.01033-.0117-.01033-.00951-.00825.00483.00376.00924.00791ZM4.50227,20.77505c.77889-.73563.8919-2.13398,1.05415-3.10645.12693-.93274.21555-1.83966.30959-2.73968.18698-1.70995.34235-3.53648.77929-5.15119.50719-1.77265,1.89472-3.33763,3.64385-3.93923.33143-.11169.72801-.20262,1.01175-.27704.79491-.20847,1.00739-.4591,1.00819-1.15157,0,0,.001-1.21602.00199-2.07077.00011-.0927.07529-.16779.16799-.16778l1.49104.00018c.09269.00001.16785.07511.16794.1678.00085.85468.00171,2.07055.00171,2.07055.00065.69404.30215,1.01419,1.03218,1.18829,2.14928.25246,4.04109,2.18953,4.59419,4.17994.65426,2.52882.71689,5.26877,1.08458,7.89121.08309.58847.1788,1.23172.35708,1.84252.12604.4087.27062.81712.61218,1.18053.65105.60789,1.40711,1.13518,2.23592,1.48691.00177.30622.00211.36492.00413.71447-3.20041.01824-8.21071.05023-10.85363.07756-.09407.00438-.18152.01785-.2632.03828-2.70646-.03188-7.49877-.0829-10.59794-.10624.00229-.35488.00269-.41594.00469-.72693.79208-.3363,1.51617-.83581,2.15234-1.40138ZM14.98405,25.28165c-.00131-.01802-.00753.00505-.01195.01151l-.01633.03654c-.1602.35679-.38517.7931-.68657,1.0144-.13804.11186-.296.14441-.4478.22326-.30975.15002-.85722.17629-1.18854.07089-.59101-.15053-.94177-.73933-1.09829-1.31046l-.00988-.03727c-.00224-.00651-.00632-.02997-.00539-.01128-.00577-.05568-.01705-.10928-.03107-.16164.52858-.00584,1.01918-.01128,1.45482-.01622.08168.02043.16919.0339.26332.03839.51974.00533,1.1319.01083,1.80206.01628-.00981.0403-.01812.08205-.02437.12561Z"/>
                                    <path :class="innerClass" d="M13.21335,22.97128c2.64294-.02728,7.65326-.05933,10.85364-.07751-.00201-.34955-.00238-.40826-.00415-.71448-.8288-.35175-1.58484-.87903-2.2359-1.48694-.34155-.3634-.48615-.77185-.61218-1.18054-.17828-.61078-.27399-1.25403-.35706-1.84253-.36774-2.62238-.43036-5.36237-1.08459-7.89117-.5531-1.99042-2.44495-3.92749-4.59418-4.17993-.73004-.17413-1.03156-.49426-1.03223-1.18829,0,0-.00085-1.21588-.00171-2.07056-.00006-.09271-.0752-.16779-.16791-.16779l-1.49103-.00018c-.09271-.00006-.16791.07507-.16803.16772-.00098.8548-.00195,2.0708-.00195,2.0708-.00079.69244-.21332.94312-1.00818,1.15155-.28375.0744-.68036.16534-1.01178.27704-1.74915.60162-3.13666,2.16656-3.64386,3.93921-.43689,1.61475-.59229,3.44128-.77924,5.15125-.09406.89996-.18268,1.80688-.30963,2.73962-.16223.97253-.27527,2.37085-1.05414,3.10651-.63617.56555-1.36029,1.06506-2.15234,1.40137-.00201.31097-.00238.37207-.0047.72693,3.09918.02332,7.89148.07434,10.59796.10626.08167-.02045.16913-.03394.26318-.03833Z"/>

                                </svg>
                            </div>
                            
                        </div>
                        
                    </div>
                    <div class="project-cell whitespace-nowrap">部門</div>
                    <div class="project-cell cursor-pointer relative">
                        <div @click.stop="menu.setMenu({ parent: 'projectStatusSort' })" class="flex items-center gap-[5px] whitespace-nowrap">
                            ステータス
                            <Back class="rotate-[270deg]" size="10"/>
                        </div>
                        <Transition name="slidePop">
                            <div 
                                v-if="menu.parent == 'projectStatusSort'"
                                id="projectStatusSort"
                                class="workMemberSelector p-[10px]"
                            >
                                <div class="mb-[10px]">
                                    <CommandButton :buttons="[{title: 'リセット', action: () => {selectedStatuses = []; menu.close()}}]"/>
                                </div>         
                                <div class="flex flex-col gap-[10px]" v-if="PROJECT_STATUS_LABEL">
                                    <div v-for="(option, index) in PROJECT_STATUS_LABEL">
                                        <label class="cursor-pointer select-none whitespace-nowrap flex items-center gap-[5px]">
                                            <input type="checkbox" class="custom-f-checkbox rounded-[3px]" name="class-selector"  v-model="selectedStatuses" :value="index"/>
                                            {{ option }}
                                        </label>
                                    </div>
                                </div>                  
                            </div>
                        </Transition>
                        
                    </div>
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
                    <!-- <div class="project-cell">概要</div> -->

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
                                <p class="max-w-[calc(100%-60px)] overflow-hidden whitespace-nowrap text-ellipsis"><span v-if="project.status != 'running' && project.status != 'completed'">【未締結】</span>{{ project.name }}</p>
                                <div class="flex items-center gap-1">
                                    <span title="確認バッジ" class="side-notification" style="position: unset;width:15px;" v-if="confirmBadges(project.id) > 0">{{ confirmBadges(project.id) }}</span>
                                    <span title="コメントバッジ" class="side-notification" style="position: unset;width:15px;background-color:#F28C28;" v-if="commentBadges(project.id) > 0">{{ commentBadges(project.id) }}</span>
                                </div>
                            </div>                           
                        </div>
                    </div>
                    <div class="project-cell pc">
                        {{ project.is_new ? '新規' : '既存' }}
                    </div>
                    <div class="project-cell pc">
                        {{ PROJECT_STATUS_LABEL[project.status] ?? '不明' }}
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
                    <!-- <div class="project-cell pc">
                        <div style="position: relative;">
                            <div class="text-wrap">
                                {{ plainText(project.description) }}
                            </div>
                        </div>                        
                    </div> -->
                    <div class="project-cell pc">
                        <div class=flex>
                            <UserPanel v-for="member in project.manager" imgClass="u_icon_20" :user="member" size="20"/>
                        </div>
                    </div>
                    <div class="project-cell pc" style="overflow: hidden">
                        <div class="flex">
                            <div class="flex" @click.stop="viewUsers(project.members)">
                                <UserPanel v-for="member in project.members.slice(0, 5)" :disable-instant="true" imgClass="u_icon_20" :user="member" size="20"/>
                            </div>
                            <span class="my-[auto] ml-[5px] text-[12px] cursor-pointer whitespace-nowrap" v-if="project.members.length > 5">...({{project.members.length}})</span>
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
                                :projects="projectList"
                                :ownProjectIds="ownProjectIds"
                                ref="taskComponent"
                            />
                        </router-view>                        
                    </div>
                </Teleport>
            </Transition>

        </div>
        <FloatButton @action="createWindow = true" id="projectCreate" v-if="(auth.activeUser.position_id && auth.activeUser.position_id <= 6 || auth.isAdmin) && (route.name == 'gantt-chart' || route.name == 'project')">
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
import ProjectMemberSort from './ProjectMemberSort.vue';
import { useProject } from '@/composables/project';
import Back from '../Icons/Back.vue';
import AddIcon from '../Form/AddIcon.vue';
import { useApi } from '@/composables/api';
import ProjectDateSort from './ProjectDateSort.vue';
import { useDialog } from '@/composables/dialog';
import { useTour } from '@/composables/useTour';
import { useTutorialStore } from '@/store/tutorial';
import { PROJECT_STATUS_LABEL } from '@/utils/tools';
import ResourceSort from './Resource/ResourceSort.vue';
import CommandButton from '../Global/CommandButton.vue';
type ColorState = 0 | 1 | 2;
const state = ref<ColorState>(0);

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
const selectedStatuses = ref<string[]>([])
const badge = useBadgeStore()
const taskComponent = useTemplateRef<ComponentExposed<typeof TaskComponent>>('taskComponent')
const totalFinanceWindow = ref(false)
const api = useApi()
const start = ref<DateTime | null>(null)
const end = ref<DateTime | null>(null)
const { getProjects, projectList, usersProjects } = useProject()
const { ping } = useDialog()
const { startTour } = useTour() 
const tutorialStore = useTutorialStore()
onMounted(async() => {
    await getProjectData();
    getSelectableUsers()
    if(tutorialStore.state.active && tutorialStore.state.name.includes('project.create')){
        startTour('project.create', { version: '2025-09' });
    }
    if(tutorialStore.state.active && tutorialStore.state.name.includes('project.details')){
        startTour('project.details', { version: '2025-09'})
    }
})
const outerClass = computed(() => {
  if (state.value === 1) return "is-orange";
  return "is-default";
});

const innerClass = computed(() => {
  if (state.value === 1) return "is-orange";
  return "is-none"; // default: no fill for inner
});
const cycleColor = () => {
    state.value = ((state.value + 1) % 2) as ColorState;
}
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
  const managers = selectedManagers.value;
  const members = selectedMembers.value;
  const statuses = selectedStatuses.value;
  const badgeState = state.value;

  return searchResults.value.filter(project => {
    const managerOk =
      !managers.length ||
      (project.manager ?? []).some(m => managers.includes(m.id));

    const memberOk =
      !members.length ||
      (project.members ?? []).some(m => members.includes(m.id));

    const statusOk =
      !statuses.length || statuses.includes(project.status);

    const showCompleted = statuses.includes('completed');
    const completedOk = showCompleted || project.status !== 'completed';

    const badgeOk =
      badgeState === 0 ||
      (badgeState === 1 && !!project.has_comment_badge);

    return managerOk && memberOk && statusOk && badgeOk && completedOk;
  });
});

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
const commentBadges = (projectId: number) => {
    return badge.taskCommentBadgeByFilter([{by: 'project_id', value: projectId}]).length + 
    (badge.financeCommentBadgeByFilter({by: 'project_id', value: projectId})?.total_unread ?? 0) + 
    badge.goalIssueCommentBadgeByFilter([{by: 'project_id', value: projectId}]).length +
    (badge.projectReportMap[projectId] ?? 0)
}
const totalBadges = (projectId: number) => {
    return badge.goalsBadgeByFilter([{by: 'project_id', value: projectId}]).length + 
    badge.salaryIssueByFilter([{by: 'project_id', value: projectId}]).length +
    badge.assetsBadgeByFilter([{by: 'project_id', value: projectId}]).length + 
    badge.taskCommentBadgeByFilter([{by: 'project_id', value: projectId}]).length + 
    (badge.financeCommentBadgeByFilter({by: 'project_id', value: projectId})?.total_unread ?? 0) + 
    badge.goalIssueCommentBadgeByFilter([{by: 'project_id', value: projectId}]).length
}
const confirmBadges = (projectId: number) => {
    return badge.goalsBadgeByFilter([{by: 'project_id', value: projectId}]).length +
    badge.salaryIssueByFilter([{by: 'project_id', value: projectId}]).length +
    badge.assetsBadgeByFilter([{by: 'project_id', value: projectId}]).length +
    (badge.checkItemConfirmByFilter[projectId] ?? 0)
}
const jumpToProject = (project: Project) => {
    const routeName = route.name === 'project' ? 'overview' : route.name
    
    router.push({
        name: routeName,
        params: { projectId: project?.id }
    });
}
const deleteProject = async(project: Project) => {
    const data = await api.del('/delete_project', {id: project.id}, {
        ask: 'プロジェクトを削除しますか？',
        toast: '削除しました。'
    })
    router.push({name: 'project'})
    data && getProjects()
}
provide('deleteProject', (rec: Project) => deleteProject(rec))
provide('editProjects', (rec) => {editData.value = rec; createWindow.value = true})
provide('setTotalFinanceWindow', (flag:boolean) => {totalFinanceWindow.value = flag})
</script>
<style scoped>
    .is-default {
        fill: var(--primary-color);
    }
    .is-none {
        fill: transparent;
    }
    .is-red {
        fill: tomato;
    }
    .is-orange {
        fill: #F28C28;
    }
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