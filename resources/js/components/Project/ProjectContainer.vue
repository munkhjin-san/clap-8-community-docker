<template>
    <div class="post-root project-root" :class="{'detail-open': compactList}">
        <!-- 詳細を全高で出すため、左カラム（ヘッダー＋一覧）と詳細をルート直下で分割する -->
        <div class="project-main-column">
        <div class="post-header justify-between">
            <HamBurger v-if="responsive.mobile"/>
            <div class="project-search-wrap">
                <PostSearchBar
                    className="newChatMemberSearch"
                    :customPlaceHolder="`プロジェクト検索`"
                    @search-start="(word) => {keywords = word}"
                />
            </div>
            <div class="project-view-bar">
                <div class="project-view-switch">
                    <div :class="['pv-item', {'pv-on': grouping === 'none'}]" @click="grouping = 'none'">一覧</div>
                    <div :class="['pv-item', {'pv-on': grouping === 'pm'}]" @click="grouping = 'pm'">PM別</div>
                </div>
                <div class="project-view-switch">
                    <div :class="['pv-item', {'pv-on': scope === 'all'}]" @click="scope = 'all'">すべて</div>
                    <div :class="['pv-item', {'pv-on': scope === 'mine'}]" @click="scope = 'mine'">担当部門</div>
                </div>
            </div>
            <div class="flex gap-4 mr-4 project-header-actions">
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
        <div class="post-container">
            <div class="project-list-pane scrollable">
            <div class="project-table" id="projectTable">
                <div class="project-header-row">
                    <div class="project-cell project-title-head cursor-pointer relative">
                        <div class="flex items-center justify-between">
                            プロジェクト
                        </div>
                        
                    </div>
                    <!-- <div class="project-cell whitespace-nowrap">部門</div> -->
                    <div class="project-cell pc cursor-pointer relative">
                        <div @click.stop="menu.setMenu({ parent: 'projectStatusSort' })" class="flex items-center gap-[5px] whitespace-nowrap">
                            ステータス
                            <Filter :filtered="selectedStatuses.length > 0" style="fill: var(--primary-color);" size="12"/>
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
                    <div class="project-cell pc cursor-pointer relative">
                        <div @click.stop="menu.setMenu({parent: 'projectDateSelect'})" class="flex items-center gap-[5px] whitespace-nowrap">
                            期間
                            <Filter :filtered="!!(start && end)" style="fill: var(--primary-color);" size="12"/>
                            
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
                    <div class="project-cell">取引先</div>
                    <div class="project-cell">顧客企業</div>
                    <div class="project-cell">業種区分</div>
                    <!-- <div class="project-cell">概要</div> -->

                    <div class="project-cell pc cursor-pointer relative">
                        <div @click.stop="menu.setMenu({parent: 'projectManagerSelect'})" class="flex items-center gap-[5px] whitespace-nowrap">
                            PM
                            <Filter :filtered="selectedManagers.length > 0" style="fill: var(--primary-color);" size="12"/>
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
                                custom-place-holder="PM検索"
                            />
                        </Transition>
                    </div>
                    <div class="project-cell pc cursor-pointer relative">
                        <div @click.stop="menu.setMenu({parent: 'projectMemberSelect'})" class="flex items-center gap-[5px] whitespace-nowrap">
                            メンバー
                            <Filter :filtered="selectedMembers.length > 0" style="fill: var(--primary-color);" size="12"/>
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
                <div
                    v-for="row in displayRows"
                    :key="row.key"
                    :class="rowClass(row)"
                    @click="rowClick(row)"
                >
                <!-- PM別グループの見出し行。空セルはカラム幅を保つための埋めセル -->
                <template v-if="row.kind === 'group'">
                    <div class="project-cell project-group-cell">
                        <div class="flex items-center gap-2">
                            <Back class="project-group-arrow" :class="{'is-open': !collapsedGroups.includes(row.group.key)}" size="10"/>
                            <UserPanel v-if="row.group.pm" disable-instant imgClass="u_icon_20" :user="row.group.pm" size="20"/>
                            <span class="project-group-name">{{ row.group.pm?.name ?? 'PM未設定' }}</span>
                            <span class="project-group-count">{{ row.group.projects.length }}</span>
                            <Badge style="position: unset;" title="グループ内バッジ" :count="groupBadges(row.group)" v-if="groupBadges(row.group) > 0"/>
                        </div>
                    </div>
                    <div class="project-cell project-group-cell pc" v-for="n in 8" :key="n"></div>
                </template>
                <template v-else>
                    <div class="project-cell project-title-cell">
                        <div class="flex w-full">
                            <div class="flex gap-2 items-center relative w-full">
                                <p class="max-w-[calc(100%-60px)] overflow-hidden whitespace-nowrap text-ellipsis"><span v-if="row.project.status != 'running' && row.project.status != 'completed'">【未締結】</span>{{ row.project.name }}</p>
                                <div class="flex items-center gap-1">
                                    <Badge style="position: unset;" title="確認バッジ" :count="confirmBadges(row.project.id)" v-if="confirmBadges(row.project.id) > 0"/>
                                    <Badge style="position: unset;" title="コメントバッジ" :count="commentBadges(row.project.id)" color="orange" v-if="commentBadges(row.project.id) > 0"/>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="project-cell pc">
                        {{ PROJECT_STATUS_LABEL[row.project.status] ?? '不明' }}
                    </div>
                    <div class="project-cell pc">
                        <div v-if="row.project?.date_start">{{ DateTime.fromISO(row.project.date_start).toLocaleString(DateTime.DATE_SHORT) }} ~ {{ DateTime.fromISO(row.project.date_end).toLocaleString(DateTime.DATE_SHORT) }}</div>
                    </div>
                    <div class="project-cell pc">
                        <div style="position: relative;">
                            <div class="text-wrap">
                                <p v-for="cat in row.project.category || []">{{ cat }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="project-cell pc">
                        <div style="position: relative;">
                            <div class="text-wrap">
                                <p v-for="partner in row.project.partner_records || []" :key="partner.id">{{ partner.name }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="project-cell pc">
                        <div style="position: relative;">
                            <div class="text-wrap">
                                <p v-for="customer in row.project.customers || []">{{ customer }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="project-cell pc">
                        <div style="position: relative;">
                            <div class="text-wrap">
                                <p v-for="type in row.project.industry_type || []">{{ type }}</p>
                            </div>
                        </div>
                    </div>
                    <!-- PM別表示では見出しのPMは省き、共同PMだけを出して重複表示の理由を示す -->
                    <div class="project-cell pc">
                        <div class="flex items-center gap-1">
                            <span class="project-co-pm-label" v-if="effectiveGrouping === 'pm' && rowManagers(row).length">共同</span>
                            <div class="flex">
                                <UserPanel v-for="member in rowManagers(row)" imgClass="u_icon_20" :user="member" size="20"/>
                            </div>
                        </div>
                    </div>
                    <div class="project-cell pc" style="overflow: hidden">
                        <div class="flex">
                            <div class="flex" @click.stop="viewUsers(row.project.members)">
                                <UserPanel v-for="member in row.project.members.slice(0, 5)" :disable-instant="true" imgClass="u_icon_20" :user="member" size="20"/>
                            </div>
                            <span class="my-[auto] ml-[5px] text-[12px] cursor-pointer whitespace-nowrap" v-if="row.project.members.length > 5">...({{row.project.members.length}})</span>
                        </div>
                    </div>
                </template>
                </div>
            </div>
            </div>
        </div>
        </div>
        <Transition name="detailShift">
            <Teleport to="body" :disabled="!responsive.mobile">
                <div class="project-detail-pane" v-if="detailOpen">
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
import { nextTick, onMounted, ref, computed, provide, useTemplateRef, watch } from 'vue';
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
import Filter from '../Icons/Filter.vue';
import Badge from '../Global/Badge.vue';
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
const userList = ref<User[]>([])
const editData = ref<Project | null>(null)
const createWindow = ref(false)
const selectedManagers = ref<number[]>([])
const selectedMembers = ref<number[]>([])
const selectedStatuses = ref<string[]>(Object.keys(PROJECT_STATUS_LABEL).filter(k => k !== 'completed'))
const badge = useBadgeStore()
const taskComponent = useTemplateRef<ComponentExposed<typeof TaskComponent>>('taskComponent')
const totalFinanceWindow = ref(false)
const api = useApi()
const start = ref<DateTime | null>(null)
const end = ref<DateTime | null>(null)
const { getProjects, projectList, usersProjects } = useProject()

// 表示設定（グループ化・表示範囲・折りたたみ）はlocalStorageに保存する
type Grouping = 'none' | 'pm'
type Scope = 'all' | 'mine'
const VIEW_STORAGE_KEY = 'projectList:view'
const savedView = (() => {
    try {
        return JSON.parse(localStorage.getItem(VIEW_STORAGE_KEY) || '{}')
    } catch {
        return {}
    }
})()
const grouping = ref<Grouping>(savedView.grouping === 'pm' ? 'pm' : 'none')
const scope = ref<Scope>(savedView.scope === 'mine' ? 'mine' : 'all')
// スマホでは切替UIを出さないので常に「一覧 × すべて」。
// 保存済みのPC側の設定を書き換えないよう、表示用の値だけを差し替える
const effectiveGrouping = computed<Grouping>(() => responsive.mobile ? 'none' : grouping.value)
const effectiveScope = computed<Scope>(() => responsive.mobile ? 'all' : scope.value)
const collapsedGroups = ref<number[]>(Array.isArray(savedView.collapsed) ? savedView.collapsed.map(Number) : [])
watch([grouping, scope, collapsedGroups], () => {
    localStorage.setItem(VIEW_STORAGE_KEY, JSON.stringify({
        grouping: grouping.value,
        scope: scope.value,
        collapsed: collapsedGroups.value,
    }))
}, { deep: true })

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

// 詳細パネルはフレックスの兄弟要素。サイドメニューの開閉やビューポート変化は
// レイアウトが吸収するので、位置やサイズの実測は不要
const detailOpen = computed(() => route.name !== 'project')
const compactList = computed(() => detailOpen.value && !responsive.mobile)

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

        const deepSearch: (obj: any) => boolean = (obj: any) => {
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

    const scopeOk = effectiveScope.value === 'all' || belongsToMe(project);

    return managerOk && memberOk && statusOk && badgeOk && completedOk && scopeOk;
  });
});

// 自分が関わるプロジェクト（メンバー / PM / ディレクター）の判定
const ownIdSet = computed(() => new Set(usersProjects.value.map(project => project.id)))
const belongsToMe = (project: Project) => ownIdSet.value.has(project.id)

const NO_PM_KEY = -1
type ProjectGroup = { key: number, pm: User | null, projects: Project[] }
type DisplayRow =
    | { kind: 'group', key: string, group: ProjectGroup }
    | { kind: 'project', key: string, project: Project, groupKey: number }

// PMは多対多なので、共同PMのプロジェクトは各PMのグループに現れる（意図的な重複）
const pmGroups = computed<ProjectGroup[]>(() => {
    const map = new Map<number, ProjectGroup>()
    const push = (key: number, pm: User | null, project: Project) => {
        const group = map.get(key) ?? { key, pm, projects: [] }
        group.projects.push(project)
        map.set(key, group)
    }
    sortedProjects.value.forEach(project => {
        const pms = (project.manager ?? []).filter(Boolean)
        if(!pms.length){
            push(NO_PM_KEY, null, project)
            return
        }
        pms.forEach(pm => push(pm.id, pm, project))
    })
    const groups = [...map.values()]
    // 自分のグループを先頭、PM未設定を末尾、それ以外はPM名順
    groups.sort((a, b) => {
        if(a.key === auth.id) return -1
        if(b.key === auth.id) return 1
        if(a.key === NO_PM_KEY) return 1
        if(b.key === NO_PM_KEY) return -1
        return (a.pm?.name ?? '').localeCompare(b.pm?.name ?? '', 'ja')
    })
    // グループ内でも自分が関わるプロジェクトを優先し、それ以外は元の並びを保つ
    groups.forEach(group => {
        group.projects = [
            ...group.projects.filter(belongsToMe),
            ...group.projects.filter(project => !belongsToMe(project)),
        ]
    })
    return groups
})

const displayRows = computed<DisplayRow[]>(() => {
    if(effectiveGrouping.value !== 'pm'){
        return sortedProjects.value.map(project => ({
            kind: 'project' as const,
            key: `p-${project.id}`,
            project,
            groupKey: NO_PM_KEY,
        }))
    }
    const rows: DisplayRow[] = []
    pmGroups.value.forEach(group => {
        rows.push({ kind: 'group', key: `g-${group.key}`, group })
        if(collapsedGroups.value.includes(group.key)) return
        group.projects.forEach(project => {
            rows.push({ kind: 'project', key: `p-${group.key}-${project.id}`, project, groupKey: group.key })
        })
    })
    return rows
})

const rowClass = (row: DisplayRow) => {
    if(row.kind === 'group') return 'project-group-row'
    return ['project-cell-row', { 'selected-project-cell': Number(route.params.projectId) === row.project.id }]
}
const rowClick = (row: DisplayRow) => {
    if(row.kind === 'group'){
        toggleGroup(row.group.key)
        return
    }
    jumpToProject(row.project)
}

// PM別表示では見出しのPMを除いた共同PMのみを返す
const rowManagers = (row: DisplayRow) => {
    if(row.kind !== 'project') return []
    const managers = row.project.manager ?? []
    if(effectiveGrouping.value !== 'pm') return managers
    return managers.filter(manager => manager.id !== row.groupKey)
}
const groupBadges = (group: ProjectGroup) => {
    return group.projects.reduce((total, project) => total + confirmBadges(project.id) + commentBadges(project.id), 0)
}
const toggleGroup = (key: number) => {
    const index = collapsedGroups.value.indexOf(key)
    if(index > -1){
        collapsedGroups.value.splice(index, 1)
    }else{
        collapsedGroups.value.push(key)
    }
}

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

const sortableUsers = (which: 'manager' | 'members') => {
    const selectable: User[] = []
    projectList.value.forEach(project => {
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
    (badge.checkItemConfirmByFilter[projectId] ?? 0) +
    (badge.kintoneContractChangesByProject[projectId] ?? 0)
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
provide('editProjects', (rec: Project) => {editData.value = rec; createWindow.value = true})
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
        width: 280px;
        min-width: 280px;
        max-width: 280px;
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