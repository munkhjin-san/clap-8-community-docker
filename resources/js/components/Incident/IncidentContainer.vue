<template>
    <div class="bg-[var(--background-color)] relative min-h-full pb-5">
        <div v-if="loading" class="spinner-micro fixed top-2/4 left-2/4"></div>
        <div v-if="optionsLoaded && !canViewIncidents" class="incident-no-permission mx-4">
            <h3>インシデント管理の権限がありません</h3>
            <p>インシデント一覧は管理者、上席者、担当プロジェクトのPM、または当事者・報告者のみ閲覧できます。</p>
        </div>
        <template v-else-if="canViewIncidents">
            
            <div class="incident-toolbar mx-4">
                <div id="incidentSort" class="relative flex border border-solid border-[var(--formBorder)]">
                    <div class="incident-sort-field relative bg-[var(--bg3)]">
                        <select id="selectedIncidentSearchQuerySelector" class="incident-sort-select text-[var(--primary-color)] bg-[var(--bg3)] pl-2 appearance-none pr-6" v-model="selectedSearchQuery.value">
                            <option v-for="option in searchQueryOptions" :key="option.value" :value="option.value">{{ option.name }}</option>
                        </select>
                        <div class="absolute top-[10px] rotate-[-90deg] right-2 pointer-events-none">
                            <Back size="10"/>
                        </div>
                    </div>
                    <div class="incident-sort-divider bg-[var(--formBorder)]"></div>
                    <div v-if="selectedSearchQuery.value === 'keyword'" class="incident-sort-control">
                        <input
                            v-model="filters.keyword"
                            type="text"
                            placeholder="検索ワードを入力"
                            class="ml-2 p-2 text-[var(--primary-color)]"
                        />
                    </div>
                    <div v-if="selectedSearchQuery.value === 'occurred_date'" class="incident-sort-date">
                        <input v-model="filters.occurred_from" type="date" class="incident-sort-input">
                        <span class="text-[12px] text-[gray]">〜</span>
                        <input v-model="filters.occurred_to" type="date" class="incident-sort-input">
                    </div>
                    <div v-if="selectedSearchQuery.value === 'point'" class="incident-sort-date">
                        <select v-model="filters.point_operator" class="incident-sort-input">
                            <option v-for="operator in pointOperatorOptions" :key="operator.value" :value="operator.value">
                                {{ operator.name }}
                            </option>
                        </select>
                        <input v-model="filters.point_value" type="number" min="0" placeholder="数値" class="incident-sort-input w-[90px]">
                    </div>
                    <div class="incident-sort-control" v-if="selectedSearchQuery.value === 'caused_by'">
                        <AssetUserPicker v-model="filters.caused_by" :users="filterUsers" teleport-target="#incidentSort"/>
                    </div>
                    <div class="incident-sort-control" v-if="selectedSearchQuery.value === 'reported_by'">
                        <AssetUserPicker v-model="filters.reported_by" :users="filterUsers" teleport-target="#incidentSort"/>
                    </div>
                    <div class="incident-sort-control" v-if="selectedSearchQuery.value === 'subject_user_id'">
                        <AssetUserPicker v-model="filters.subject_user_id" :users="filterUsers" teleport-target="#incidentSort"/>
                    </div>
                    <div class="incident-sort-control" v-if="selectedSearchQuery.value === 'decided_by'">
                        <AssetUserPicker v-model="filters.decided_by" :users="filterUsers" teleport-target="#incidentSort"/>
                    </div>
                    <div v-if="isMultiFilterKey(selectedSearchQuery.value)" class="incident-sort-control relative">
                        <div @click.stop="menu.setMenu({parent: 'incident-search-query-selector'})" class="incident-sort-picker">
                            <div class="incident-sort-empty cursor-pointer" v-if="!selectedMultiFilterValues.length">
                                <div class="h-full text-[gray] text-[12px] pointer-events-none flex justify-center px-3 items-center">選択してください</div>
                            </div>
                            <div v-else>
                                <div class="flex gap-2 flex-wrap px-3 py-2 cursor-pointer">
                                    <div v-for="value in selectedMultiFilterValues" :key="value" class="flex items-center gap-1 bg-[var(--bg3)] px-2 py-1 rounded text-[12px]">
                                        <span>{{ getOptionLabel(selectedSearchQuery.value, value) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <Teleport defer to="#incidentSort" :disabled="responsive.mobile ? false : true">
                            <Transition name="slidePop">
                                <div v-if="menu.parent == 'incident-search-query-selector'" id="incident-search-query-selector" class="absolute top-full left-0 w-max max-h-[400px] bg-[var(--background-color)] border border-solid border-[var(--secondary-background)] shadow-lg rounded-md overflow-auto z-10">
                                    <div class="p-3">
                                        <label v-for="option in selectorOptions[selectedSearchQuery.value]" :key="option.value" class="cursor-pointer hover:bg-[var(--secondary-background)] p-2 flex items-center gap-2 rounded-md text-[12px]" >
                                            <input type="checkbox" class="custom-f-checkbox" :value="option.value" v-model="selectedMultiFilterValues" />
                                            <span>{{ option.name }}</span>
                                        </label>
                                    </div>
                                </div>
                            </Transition>
                        </Teleport>
                    </div>
                </div>

                <div class="incident-toolbar-actions">
                    <button
                        v-if="activeFilterCount"
                        type="button"
                        class="incident-clear-filter"
                        @click="clearFilters"
                    >
                        フィルター解除
                    </button>
                    <LoaderButton
                        v-if="canManageIncidentSettings"
                        content="CSV出力"
                        style="margin: 0"
                        :loading="exporting"
                        @triggered="exportIncidentCsv"
                    />
                    <button
                        v-if="canManageIncidentSettings"
                        type="button"
                        class="incident-settings-button"
                        title="インシデント設定"
                        @click="settingsOpen = true"
                    >
                        <Gear size="16" fill="var(--primary-color)"/>
                        設定
                    </button>
                </div>
            </div>
            <div v-if="showTabs" class="incident-tab-container">
                <div
                    v-for="tab in visibleTabs"
                    :key="tab.value"
                    class="incident-tab"
                    :class="{ active: selectedTab === tab.value }"
                    @click.stop="selectedTab = tab.value"
                >
                    {{ tab.label }}
                </div>
            </div>
            <table v-if="selectedTab === 'report'" class="incident-table mx-4 w-[calc(100%-2rem)]">
                <thead>
                    <tr>
                        <td>発生日</td>
                        <td>当事者</td>
                        <td>報告者</td>
                        <td>プロジェクト</td>
                        <td>区分</td>
                        <td>ステータス</td>
                        <td>現在の担当者</td>
                        <td>ポイント</td>
                        <td>コメント</td>
                    </tr>
                </thead>
                <tbody>
                    <template v-if="incidentsData.data.length">
                        <template v-for="incident in incidentsData.data" :key="incident.id">
                            <tr
                                class="data-row cursor-pointer"
                                :class="{ expanded: activeIncident?.id === incident.id }"
                                @click.stop="openIncidentDetail(incident)"
                            >
                                <td>
                                    <div class="inner-col flex !items-center gap-2 relative">
                                        <span
                                            v-if="shouldShowUnreadDot(incident)"
                                            class="incident-unread-dot"
                                            :class="{ 'custom-heartbeat': isNewIncident(incident) }"
                                        ></span>
                                        <span class="mobile">発生日</span>
                                        <p class="text-[gray] text-[12px] under960:ml-auto">{{ formatDate(incident.occurred_date) }}</p>
                                    </div>
                                </td>
                                <td><div class="inner-col"><span class="mobile">対象者</span><UserPanel v-if="incident.caused_by_user" :user="incident.caused_by_user" with-name size="20" disable-instant/></div></td>
                                <td><div class="inner-col"><span class="mobile">報告者</span><UserPanel v-if="incident.reported_by_user" :user="incident.reported_by_user" with-name size="20" disable-instant/></div></td>
                                <td class="max-w-[180px] overflow-hidden text-ellipsis"><div class="inner-col"><span class="mobile">プロジェクト</span><p class="truncate">{{ incident.project_record?.name || '-' }}</p></div></td>
                                <td><div class="inner-col"><span class="mobile">区分</span>{{ incident.category?.name || '-' }}</div></td>
                                <td><div class="inner-col"><span class="mobile">ステータス</span>{{ incident.status || '未設定' }}</div></td>
                                <td>
                                    <div class="inner-col">
                                        <span class="mobile">現在の担当者</span>
                                        <div v-if="currentIncidentAssignees(incident).length" class="incident-current-assignees">
                                            <div
                                                v-for="user in currentIncidentAssignees(incident).slice(0, 3)"
                                                :key="user.id"
                                                class="incident-current-assignee"
                                            >
                                                <UserPanel
                                                    :user="user"
                                                    size="22"
                                                    disable-instant
                                                />
                                            </div>
                                            <span v-if="currentIncidentAssignees(incident).length > 3" class="incident-current-assignees-more">
                                                +{{ currentIncidentAssignees(incident).length - 3 }}
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td><div class="inner-col"><span class="mobile">ポイント</span>
                                    <div v-if="(incident.risk_level ?? 0) * (incident.severity_level ?? 0)" :style="{backgroundColor: riskLevelColor(incident)}" class="text-black w-6 h-6 rounded-full flex items-center justify-center text-[12px]">
                                        {{ (incident.risk_level ?? 0) * (incident.severity_level ?? 0) || '' }}
                                    </div>
                                </div></td>
                                <td>
                                    <div class="inner-col">
                                        <span class="mobile">コメント</span>
                                        <div class="flex items-center gap-2">
                                            <span>{{ incident.comments_count && incident.comments_count > 0 ? `${incident.comments_count}件` : '' }}</span>
                                            <Badge style="position: unset" v-if="incident.unread_comments_count" :count="incident.unread_comments_count" color="orange"/>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </template>
                    <tr v-else-if="fetchCount > 0">
                        <td colspan="9" class="!text-center">データがありません</td>
                    </tr>
                </tbody>
            </table>
            <div v-if="selectedTab === 'report'" class="mt-3 pb-3">
                <PostSearchPager
                    style="margin: 0;"
                    :possiblePage="incidentsData.last_page"
                    :activePath="incidentsData.current_page"
                    @setNavi="(index) => getIncidents(incidentsData.current_page + index)"
                    @setActivePage="(index) => getIncidents(index)"
                />
            </div>

            <table v-if="selectedTab === 'pending'" class="incident-table mx-4 w-[calc(100%-40px)]">
                <thead>
                    <tr>
                        <td>種別</td>
                        <td>対象者</td>
                        <td>プロジェクト</td>
                        <td>内容</td>
                        <td>担当者</td>
                        <td>検知日</td>
                    </tr>
                </thead>
                <tbody>
                    <template v-if="pendingCandidates.length">
                        <tr v-for="candidate in pendingCandidates" :key="`pending-${candidate.id}`" class="data-row">
                            <td><div class="inner-col"><span class="mobile">種別</span><span class="candidate-type-tag">{{ candidateLabel(candidate) }}</span></div></td>
                            <td><div class="inner-col"><span class="mobile">対象者</span><UserPanel v-if="candidate.subject" :user="(candidate.subject as any)" with-name size="20" disable-instant/><span v-else>不明</span></div></td>
                            <td class="max-w-[180px] overflow-hidden text-ellipsis"><div class="inner-col"><span class="mobile">プロジェクト</span><p class="truncate">{{ candidate.project?.name || '-' }}</p></div></td>
                            <td><div class="inner-col"><span class="mobile">内容</span><p class="whitespace-pre-wrap">{{ candidateContent(candidate) }}</p></div></td>
                            <td>
                                <div class="inner-col"><span class="mobile">対象者</span>
                                    <div v-if="candidate.audience === 'pm'">
                                        <UserPanel v-if="candidate.project" :user="(candidate.project?.manager?.[0] as any)" with-name size="20" disable-instant/>
                                        <span v-else>-</span>
                                    </div>
                                    <span v-else>役員</span>
                                </div>
                            </td>
                            <td><div class="inner-col"><span class="mobile">検知日</span><p class="text-[gray] text-[12px]">{{ formatDate(candidate.created_at) }}</p></div></td>
                        </tr>
                    </template>
                    <tr v-else><td colspan="6" class="!text-center">データがありません</td></tr>
                </tbody>
            </table>
            <div v-if="selectedTab === 'pending'" class="mt-3 pb-3">
                <PostSearchPager
                    style="margin: 0;"
                    :possiblePage="candidatesData.last_page"
                    :activePath="candidatesData.current_page"
                    @setNavi="(index) => getCandidates(candidatesData.current_page + index)"
                    @setActivePage="(index) => getCandidates(index)"
                />
            </div>

            <table v-if="selectedTab === 'dismissed'" class="incident-table mx-4 w-[calc(100%-40px)]">
                <thead>
                    <tr>
                        <td>種別</td>
                        <td>対象者</td>
                        <td>プロジェクト</td>
                        <td>却下理由</td>
                        <td>決定者</td>
                        <td>決定日</td>
                    </tr>
                </thead>
                <tbody>
                    <template v-if="dismissedCandidates.length">
                        <tr v-for="candidate in dismissedCandidates" :key="`dismissed-${candidate.id}`" class="data-row">
                            <td><div class="inner-col"><span class="mobile">種別</span><span class="candidate-type-tag">{{ candidateLabel(candidate) }}</span></div></td>
                            <td><div class="inner-col"><span class="mobile">対象者</span><UserPanel v-if="candidate.subject" :user="(candidate.subject as any)" with-name size="20" disable-instant/><span v-else>不明</span></div></td>
                            <td class="max-w-[180px] overflow-hidden text-ellipsis"><div class="inner-col"><span class="mobile">プロジェクト</span><p class="truncate">{{ candidate.project?.name || '-' }}</p></div></td>
                            <td><div class="inner-col"><span class="mobile">却下理由</span><p class="whitespace-pre-wrap">{{ candidate.decision_reason || '-' }}</p></div></td>
                            <td><div class="inner-col"><span class="mobile">決定者</span><UserPanel v-if="candidate.decided_by_user" :user="(candidate.decided_by_user as any)" with-name size="20" disable-instant/><span v-else>-</span></div></td>
                            <td><div class="inner-col"><span class="mobile">決定日</span><p class="text-[gray] text-[12px]">{{ fmtDateTime(candidate.decided_at) }}</p></div></td>
                        </tr>
                    </template>
                    <tr v-else><td colspan="6" class="!text-center">データがありません</td></tr>
                </tbody>
            </table>
            <div v-if="selectedTab === 'dismissed'" class="mt-3 pb-3">
                <PostSearchPager
                    style="margin: 0;"
                    :possiblePage="candidatesData.last_page"
                    :activePath="candidatesData.current_page"
                    @setNavi="(index) => getCandidates(candidatesData.current_page + index)"
                    @setActivePage="(index) => getCandidates(index)"
                />
            </div>

            <FloatButton v-if="canManageIncidentSettings && selectedTab === 'report'" class="fixed" @action="createModalOpen = true">
                <template #icon>
                    <AddIcon />
                </template>
            </FloatButton>
            <Teleport to="body">
                <Transition name="modalFade">
                    <IncidentDetailModal
                        v-if="activeIncident"
                        :incident="activeIncident"
                        @updated="handleIncidentUpdated"
                        @deleted="handleIncidentDeleted"
                        @close="handleIncidentClose"
                    />
                </Transition>
            </Teleport>
            <Teleport to="body">
                <Transition name="modalFade">
                    <IncidentSettingsManager
                        v-if="settingsOpen"
                        @close="settingsOpen = false"
                        @updated="getIncidentOptions"
                    />
                </Transition>
            </Teleport>
            <Teleport to="body">
                <Transition name="modalFade">
                    <IncidentDetailModal
                        v-if="createModalOpen"
                        create-mode
                        @created="handleIncidentCreated"
                        @close="handleCreateClose"
                    />
                </Transition>
            </Teleport>
        </template>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { DateTime } from 'luxon';
import { useRoute, useRouter } from 'vue-router';
import { useApi } from '@/composables/api';
import type { Incident, IncidentCategory } from '@/interface/incident';
import type { IncidentCandidate } from '@/interface/dashboard';
import type { User } from '@/interface/globalInterface';
import type { Project } from '@/interface/projectInterface';
import PostSearchPager from '@/components/Post/PostSearchPager.vue';
import AssetUserPicker from '@/components/Asset/AssetUserPicker.vue';
import Back from '@/components/Icons/Back.vue';
import Gear from '@/components/Icons/Gear.vue';
import { useMenuStore } from '@/store/menu';
import { useResponsive } from '@/store/responsive';
import UserPanel from '../Global/UserPanel.vue';
import FloatButton from '../Global/FloatButton.vue';
import AddIcon from '../Form/AddIcon.vue';
import IncidentDetailModal from './IncidentDetailModal.vue';
import IncidentSettingsManager from './IncidentSettingsManager.vue';
import { useDashboardStore } from '@/store/dashboard';
import Badge from '../Global/Badge.vue';
import LoaderButton from '../Global/LoaderButton.vue';

const api = useApi()
const route = useRoute()
const router = useRouter()
const dashboardStore = useDashboardStore()
const loading = ref(false)
const fetchCount = ref(0)
const activeIncident = ref<Incident | null>(null)
const openingIncidentId = ref<number | null>(null)
const settingsOpen = ref(false)
const createModalOpen = ref(false)
const exporting = ref(false)
const optionsLoaded = ref(false)
const menu = useMenuStore()
const responsive = useResponsive()
const perPage = 50
const incidentsData = ref<{
    data: Incident[]
    current_page: number
    last_page: number
    total: number
}>({
    data: [],
    current_page: 1,
    last_page: 0,
    total: 0,
})
const candidatesData = ref<{
    data: IncidentCandidate[]
    current_page: number
    last_page: number
    total: number
}>({
    data: [],
    current_page: 1,
    last_page: 0,
    total: 0,
})
const incidentOptions = ref<IncidentFilterOptions>({
    categories: [],
    filter_users: [],
    filter_projects: [],
    statuses: [],
    can_manage: false,
    can_view: false,
})
const tabs = [
    {value: 'report', label: '報告'},
    {value: 'pending', label: '未対応'},
    {value: 'dismissed', label: '却下履歴'}
]
const selectedTab = ref<string>('report')
const isNewIncident = (incident: Incident) => !incident.last_read_at && !(incident.read_histories?.length)
const shouldShowUnreadDot = (incident: Incident) => (isNewIncident(incident) || (incident.unread_update_logs_count ?? 0) > 0) && incident.status !== '完了'
const currentIncidentAssignees = (incident: Incident): User[] => {
    if (incident.status === '完了') return []

    const latestReport = [...(incident.reports ?? [])]
        .sort((a, b) => ((b.step ?? 0) - (a.step ?? 0)) || (b.id - a.id))
        .at(0)
    const reportAssignees = (latestReport?.assignees ?? [])
        .map(assignee => assignee.user)
        .filter((user): user is User => Boolean(user))

    if (reportAssignees.length) {
        return reportAssignees
    }

    return [...(incident.project_record?.manager ?? [])]
}
const filters = reactive<IncidentFilters>({
    keyword: '',
    occurred_from: '',
    occurred_to: '',
    caused_by: [],
    reported_by: [],
    project_record_id: [],
    incident_category_id: [],
    status: [],
    point_operator: 'gte',
    point_value: '',
    source_type: [],
    subject_user_id: [],
    decided_by: [],
})

type IncidentFilters = {
    keyword: string
    occurred_from: string
    occurred_to: string
    caused_by: number[]
    reported_by: number[]
    project_record_id: number[]
    incident_category_id: number[]
    status: string[]
    point_operator: IncidentPointOperator
    point_value: string
    source_type: string[]
    subject_user_id: number[]
    decided_by: number[]
}

type IncidentSearchQueryKey = keyof IncidentFilters | 'occurred_date' | 'point'
type IncidentMultiFilterKey = 'project_record_id' | 'incident_category_id' | 'status' | 'source_type'
type IncidentPointOperator = 'gt' | 'gte' | 'eq' | 'lte' | 'lt'

type IncidentFilterOptions = {
    categories: IncidentCategory[]
    filter_users: User[]
    filter_projects: Pick<Project, 'id' | 'name' | 'date_start' | 'date_end' | 'category'>[]
    statuses: string[]
    can_manage: boolean
    can_view: boolean
}

const filterUsers = computed(() => incidentOptions.value.filter_users ?? [])
const filterProjects = computed(() => incidentOptions.value.filter_projects ?? [])
const canManageIncidentSettings = computed(() => incidentOptions.value.can_manage)
const canViewIncidents = computed(() => incidentOptions.value.can_view)

// Only admins/directors (can_manage) get candidate data + the oversight tabs.
const visibleTabs = computed(() => canManageIncidentSettings.value ? tabs : tabs.filter(tab => tab.value === 'report'))
const showTabs = computed(() => visibleTabs.value.length > 1)
const pendingCandidates = computed<IncidentCandidate[]>(() => selectedTab.value === 'pending' ? candidatesData.value.data : [])
const dismissedCandidates = computed<IncidentCandidate[]>(() => selectedTab.value === 'dismissed' ? candidatesData.value.data : [])
const candidateMissedCount = (candidate: IncidentCandidate) =>
    candidate.context?.missed_count ?? candidate.context?.missed_dates?.length ?? 0
const candidateLabel = (candidate: IncidentCandidate) => {
    switch (candidate.source_type) {
        case 'daily_report_streak': return `日報未申請 ${candidateMissedCount(candidate)}回`
        case 'outcome_goal_submission': return '成果目標 未申請'
        case 'outcome_goal_pm_approval': return '成果目標 PM未承認'
        default: return 'アラート'
    }
}
const candidateContent = (candidate: IncidentCandidate) => {
    if (candidate.source_type === 'daily_report_streak') {
        return (candidate.context?.missed_dates ?? []).map(fmtDay).filter(Boolean).join('、') || '-'
    }
    return candidate.context?.goal_title || '-'
}
const fmtDay = (date?: string | null) => {
    if (!date) return ''
    const parsed = DateTime.fromISO(date)
    return parsed.isValid ? parsed.toFormat('yyyy/MM/dd') : date
}
const fmtDateTime = (date?: string | null) => {
    if (!date) return '-'
    const parsed = DateTime.fromISO(date)
    return parsed.isValid ? parsed.toFormat('yyyy/MM/dd HH:mm') : date
}
const selectedSearchQuery = ref({
    name: 'キーワード',
    value: 'keyword' as IncidentSearchQueryKey,
})

const selectorOptions = computed(() => {
    return {
        project_record_id: filterProjects.value.map(project => ({ name: project.name, value: project.id })),
        incident_category_id: incidentOptions.value.categories.map(category => ({ name: category.name || '未設定', value: category.id })),
        status: incidentOptions.value.statuses.map(status => ({ name: status, value: status })),
        source_type: [
            { name: '日報未申請', value: 'daily_report_streak' },
            { name: '成果目標 未申請', value: 'outcome_goal_submission' },
            { name: '成果目標 PM未承認', value: 'outcome_goal_pm_approval' },
        ],
    } as Record<string, Array<{ name: string; value: string | number }>>
})

const isMultiFilterKey = (key: IncidentSearchQueryKey): key is IncidentMultiFilterKey => {
    return ['project_record_id', 'incident_category_id', 'status', 'source_type'].includes(key)
}

const selectedMultiFilterValues = computed<Array<number | string>>({
    get() {
        const key = selectedSearchQuery.value.value
        return isMultiFilterKey(key) ? filters[key] : []
    },
    set(values) {
        const key = selectedSearchQuery.value.value
        if (!isMultiFilterKey(key)) return

        if (key === 'status') {
            filters.status = values.map(String)
            return
        }

        if (key === 'source_type') {
            filters.source_type = values.map(String)
            return
        }

        filters[key] = values.map(Number)
    },
})

const searchQueryOptions = computed(() => {
    if (selectedTab.value === 'report') {
        return [
            { name: 'キーワード', value: 'keyword' },
            { name: '発生日', value: 'occurred_date' },
            { name: '当事者', value: 'caused_by' },
            { name: '報告者', value: 'reported_by' },
            { name: 'プロジェクト', value: 'project_record_id' },
            { name: '区分', value: 'incident_category_id' },
            { name: 'ステータス', value: 'status' },
            { name: 'ポイント', value: 'point' },
        ]
    }
    const options = [
        { name: 'キーワード', value: 'keyword' },
        { name: '種別', value: 'source_type' },
        { name: 'プロジェクト', value: 'project_record_id' },
        { name: '対象者', value: 'subject_user_id' },
        { name: '期間', value: 'occurred_date' },
    ]
    if (selectedTab.value === 'dismissed') {
        options.push({ name: '決定者', value: 'decided_by' })
    }
    return options
})

const pointOperatorOptions: Array<{ name: string; value: IncidentPointOperator }> = [
    { name: '>', value: 'gt' },
    { name: '>=', value: 'gte' },
    { name: '=', value: 'eq' },
    { name: '<=', value: 'lte' },
    { name: '<', value: 'lt' },
]

const getOptionLabel = (queryKey: string, value: number | string) => {
    const option = selectorOptions.value[queryKey]?.find(opt => opt.value === value)
    return option ? option.name : value
}

const activeFilterCount = computed(() => {
    if (selectedTab.value !== 'report') {
        let c = filters.keyword ? 1 : 0
        c += filters.occurred_from || filters.occurred_to ? 1 : 0
        c += filters.source_type.length ? 1 : 0
        c += filters.project_record_id.length ? 1 : 0
        c += filters.subject_user_id.length ? 1 : 0
        c += filters.decided_by.length ? 1 : 0
        return c
    }
    let count = filters.keyword ? 1 : 0
    count += filters.occurred_from || filters.occurred_to ? 1 : 0
    count += filters.caused_by.length ? 1 : 0
    count += filters.reported_by.length ? 1 : 0
    count += filters.project_record_id.length ? 1 : 0
    count += filters.incident_category_id.length ? 1 : 0
    count += filters.status.length ? 1 : 0
    count += filters.point_value ? 1 : 0
    return count
})

onMounted(async () => {
    await getIncidentOptions()
    if (canViewIncidents.value) {
        syncIncidentRoute()
    }
})

watch(
    () => [route.params.type, route.params.itemId],
    () => {
        if (canViewIncidents.value) {
            syncIncidentRoute()
        }
    },
)

watch(
    filters,
    () => {
        if (!canViewIncidents.value) return
        if (selectedTab.value === 'report') {
            getIncidents(1)
        } else {
            getCandidates(1)
        }
    },
    { deep: true },
)

watch(selectedTab, (tab) => {
    // reset the search selector to a key valid for the new tab
    selectedSearchQuery.value = { name: 'キーワード', value: 'keyword' }
    if (tab === 'pending' || tab === 'dismissed') {
        candidatesData.value = { data: [], current_page: 1, last_page: 0, total: 0 }
        getCandidates(1)
    }
})

const getRouteIncidentId = () => {
    if (route.params.type !== 'incidents' || !route.params.itemId) return null
    const id = Number(route.params.itemId)
    return Number.isFinite(id) ? id : null
}

const incidentFilterParams = () => ({
    keyword: filters.keyword,
    occurred_from: filters.occurred_from || null,
    occurred_to: filters.occurred_to || null,
    caused_by: filters.caused_by,
    reported_by: filters.reported_by,
    project_record_id: filters.project_record_id,
    incident_category_id: filters.incident_category_id,
    status: filters.status,
    point_operator: filters.point_operator,
    point_value: filters.point_value || null,
})

const getIncidents = async (page?: number, keepActive = false) => {
    if (!canViewIncidents.value) return null

    loading.value = true
    const pageIndex = page ?? incidentsData.value.current_page
    const response = await api.get('/get_incidents', {
        page: pageIndex,
        per_page: perPage,
        ...incidentFilterParams(),
    })

    if (response) {
        incidentsData.value = response
        if (!keepActive) {
            activeIncident.value = null
        }
        fetchCount.value++
    }
    loading.value = false

    return response
}

const candidateFilterParams = () => ({
    keyword: filters.keyword || null,
    source_type: filters.source_type,
    project_record_id: filters.project_record_id,
    subject_user_id: filters.subject_user_id,
    decided_by: filters.decided_by,
    from: filters.occurred_from || null,
    to: filters.occurred_to || null,
})

const getCandidates = async (page?: number) => {
    if (!canManageIncidentSettings.value) return null
    if (selectedTab.value !== 'pending' && selectedTab.value !== 'dismissed') return null

    loading.value = true
    const pageIndex = page ?? candidatesData.value.current_page
    const response = await api.get('/incident_candidates', {
        status: selectedTab.value,
        page: pageIndex,
        per_page: perPage,
        ...candidateFilterParams(),
    })

    if (response) {
        candidatesData.value = response
    }
    loading.value = false

    return response
}

const resolveIncidentPage = async (incidentId: number) => {
    const response = await api.get('/incident_page', {
        id: incidentId,
        per_page: perPage,
        ...incidentFilterParams(),
    })

    return Number(response?.page ?? 1)
}

const syncIncidentRoute = async () => {
    const incidentId = getRouteIncidentId()

    if (!incidentId) {
        activeIncident.value = null
        if (!fetchCount.value) {
            await getIncidents(1)
        }
        return
    }

    openingIncidentId.value = incidentId

    if (activeIncident.value?.id === incidentId) {
        return
    }

    const currentIncident = incidentsData.value.data.find(item => item.id === incidentId)
    if (currentIncident) {
        activeIncident.value = currentIncident
        return
    }

    try {
        const page = await resolveIncidentPage(incidentId)
        await getIncidents(page, true)
    } catch {
        activeIncident.value = null
        return
    }

    if (openingIncidentId.value !== incidentId) return

    activeIncident.value = incidentsData.value.data.find(item => item.id === incidentId) ?? null
}

const getIncidentOptions = async () => {
    try {
        const response = await api.get('/incident_options', null, { silent: true })
        if (response) {
            incidentOptions.value = {
                categories: response.categories ?? [],
                filter_users: response.filter_users ?? response.users ?? [],
                filter_projects: response.filter_projects ?? response.projects ?? [],
                statuses: response.statuses ?? [],
                can_manage: response.can_manage ?? false,
                can_view: response.can_view ?? false,
            }
        }
    } finally {
        optionsLoaded.value = true
    }
}

const openIncidentDetail = (incident: Incident) => {
    if (!incident.id) return

    router.push({
        name: 'dashboard',
        params: {
            type: 'incidents',
            itemId: incident.id,
        },
    })
}

const clearFilters = () => {
    filters.keyword = ''
    filters.occurred_from = ''
    filters.occurred_to = ''
    filters.caused_by = []
    filters.reported_by = []
    filters.project_record_id = []
    filters.incident_category_id = []
    filters.status = []
    filters.point_operator = 'gte'
    filters.point_value = ''
    filters.source_type = []
    filters.subject_user_id = []
    filters.decided_by = []
    menu.close()
}

const exportIncidentCsv = async () => {
    if (exporting.value || !canManageIncidentSettings.value) return

    exporting.value = true
    try {
        const data = await api.get('/export_incident_csv', {
            ...incidentFilterParams(),
            mode: 'export',
        }, {}, {
            responseType: 'blob',
        })

        if (data) {
            const url = window.URL.createObjectURL(new Blob([data]))
            const link = document.createElement('a')
            link.href = url
            link.setAttribute('download', `インシデント${DateTime.now().toLocaleString(DateTime.DATETIME_SHORT)}.xlsx`)
            document.body.appendChild(link)
            link.click()
            link.remove()
            window.URL.revokeObjectURL(url)
        }
    } finally {
        setTimeout(() => {
            exporting.value = false
        }, 100)
    }
}

const handleIncidentUpdated = (incident: Incident) => {
    activeIncident.value = incident
    const index = incidentsData.value.data.findIndex(item => item.id === incident.id)
    if (index !== -1) {
        incidentsData.value.data[index] = incident
    }
    const attentionIndex = dashboardStore.collection.incidents.attention.findIndex(item => item.id === incident.id)
    if (attentionIndex !== -1) {
        dashboardStore.collection.incidents.attention[attentionIndex] = incident
    }
}

const handleIncidentCreated = (incident: Incident) => {
    createModalOpen.value = false
    activeIncident.value = incident
    getIncidents(1, true)
    router.push({
        name: 'dashboard',
        params: {
            type: 'incidents',
            itemId: incident.id,
        },
    })
}

const handleCreateClose = (refresh: boolean) => {
    createModalOpen.value = false
    if (refresh) {
        getIncidents(1)
    }
}

const handleIncidentClose = (refresh: boolean) => {
    activeIncident.value = null
    if (getRouteIncidentId()) {
        router.replace({
            name: 'dashboard',
            params: {
                type: 'incidents',
            },
        })
    }
    if (refresh) {
        getIncidents(incidentsData.value.current_page)
    }
}

const handleIncidentDeleted = (incident: Incident) => {
    incidentsData.value.data = incidentsData.value.data.filter(item => item.id !== incident.id)
    incidentsData.value.total = Math.max(0, incidentsData.value.total - 1)
    activeIncident.value = null
    if (getRouteIncidentId()) {
        router.replace({
            name: 'dashboard',
            params: {
                type: 'incidents',
            },
        })
    }
}

const formatDate = (date?: string | null) => {
    if (!date) return '-'
    const parsed = DateTime.fromISO(date)
    return parsed.isValid ? parsed.toFormat('yyyy/M/d') : date
}

const RISK_LEVEL_COLORS = [
    { min: 9, color: '#ff6347' }, // 出勤停止・降給・降格・諭旨退職
    { min: 6, color: '#ff826c' }, // 減給
    { min: 4, color: '#ffa191' }, // 訓戒
    { min: 2, color: '#ffc1b5' }, // 厳重注意
    { min: 1, color: '#ffe0da' }, // 注意処分
]

const riskLevelColor = (incident: Incident) => {
    const riskLevel = (incident.risk_level ?? 0) * (incident.severity_level ?? 0)
    return RISK_LEVEL_COLORS.find(l => riskLevel >= l.min)?.color ?? 'var(--bg2)'
}

</script>

<style lang="scss">
.incident-no-permission{
    border: 1px solid var(--calendarBorder);
    padding: 28px 20px;
    text-align: center;

    h3{
        font-size: 16px;
        font-weight: 700;
        margin-bottom: 8px;
    }

    p{
        color: gray;
        font-size: 13px;
    }
}

.incident-toolbar{
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 15px;
}

.incident-clear-filter{
    flex: none;
    min-height: 35px;
    padding: 0 12px;
    border: 1px solid var(--calendarBorder);
    color: var(--primary-color);
    background: var(--background-color);
    font-size: 12px;
}

.incident-clear-filter:hover{
    background: var(--bg3);
}

.incident-toolbar-actions{
    display: flex;
    align-items: stretch;
    gap: 8px;
    margin-left: auto;
}

.incident-settings-button{
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    min-height: 35px;
    padding: 0 12px;
    border: 1px solid var(--calendarBorder);
    color: var(--primary-color);
    background: var(--background-color);
    font-size: 12px;
}

.incident-settings-button:hover{
    background: var(--bg3);
}

#incidentSort{
    align-items: stretch;
    min-height: 35px;
    max-width: 100%;
}

.incident-sort-field,
.incident-sort-control{
    display: flex;
    align-items: stretch;
    min-height: 35px;
}

.incident-sort-field{
    flex: none;
}

.incident-sort-control{
    min-width: 0;
}

.incident-sort-select{
    min-height: 35px;
    height: 100%;
}

.incident-sort-divider{
    width: 1px;
    align-self: stretch;
}

.incident-sort-picker,
.incident-sort-empty{
    display: flex;
    align-items: stretch;
    min-height: 35px;
    width: 100%;
}

.incident-sort-date{
    display: flex;
    align-items: center;
    gap: 8px;
    min-height: 35px;
    padding: 0 10px;
}

.incident-sort-input{
    min-height: 35px;
    padding:0 8px;
    color: var(--primary-color);
    background: var(--background-color);
}

.incident-table{
    background-color: var(--background-color);
    width: 100%;
    border-collapse: collapse;
    border-spacing: 0;
    table-layout: fixed;
    color: var(--primary-color);
}

.incident-table td{
    padding: 10px;
    font-size: 13px;
    border-bottom: solid thin var(--calendarBorder);
    vertical-align: middle;
}

.incident-table thead td{
    padding: 16px 12px;
    font-size: 12px;
    font-weight: 700;
    background: var(--bg3);
    color: var(--primary-color);
    border-bottom: 1px solid var(--calendarBorder);
    overflow: visible;
}

.incident-table .data-row:hover{
    background: var(--bg3);
}

.incident-table .data-row.expanded{
    background: var(--selected-background);
}

.incident-table .data-row.expanded td{
    border-bottom: none;
}

.incident-unread-dot{
    display: inline-block;
    width: 6px;
    min-width: 6px;
    height: 6px;
    border-radius: 999px;
    background: tomato;
    position: unset;
}

.incident-comment-unread-badge{
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 18px;
    height: 18px;
    padding: 0 5px;
    border-radius: 999px;
    background: orange;
    color: white;
    font-size: 11px;
    line-height: 1;
}

.incident-current-assignees{
    display: flex;
    align-items: center;
    min-height: 24px;
}

.incident-current-assignees-more{
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 22px;
    height: 22px;
    border: 1px solid var(--calendarBorder);
    border-radius: 999px;
    background: var(--bg3);
    color: gray;
    font-size: 10px;
}

.incident-table .row-toggle{
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: inherit;
}

.incident-table .row-toggle:hover{
    background: var(--bg3);
}
/* Tinted header strip; the active tab is carved out in the body color so it
   connects to the panel below (classic connected folder tabs). */
.incident-tab-container {
    display: flex;
    font-size: 13px;
    margin: 1rem 1rem 1rem;
}

.incident-tab {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 10px 15px;
    cursor: pointer;
    white-space: nowrap;
    user-select: none;
    color: var(--third-color);
    transition: background .15s, color .15s;
    text-align: center;
    border-bottom: 1px solid transparent;
}

.incident-tab:hover {
    color: var(--primary-color);
}

.incident-tab.active {
    color: var(--primary-color);
    border-bottom: 1px solid var(--primary-color);
}

.incident-tab-count {
    font-size: 12px;
    color: gray;
}

.candidate-type-tag {
    display: inline-flex;
    align-items: center;
    width: fit-content;
    background: rgba(249, 115, 22, 0.14);
    color: #c2410c;
    font-size: 11px;
    font-weight: 700;
    line-height: 1;
    padding: 5px 8px;
    border-radius: 4px;
}
@media screen and (max-width: 959px) {
    .incident-unread-dot{
        display: inline-block;
        position: absolute;
        right: -12px;
        top: -14px;
        width: 10px;
        min-width: 10px;
        height: 10px;
        border-radius: 999px;
        background: tomato;
    }
    .incident-toolbar{
        flex-direction: column;
        align-items: stretch;
    }

    .incident-toolbar-actions{
        justify-content: flex-end;
        margin-left: 0;
    }

    .incident-table{
        thead{
            display: none;
        }
        tbody{
            tr{
                display: block;
                margin-bottom: 20px;
                border-bottom: solid thin var(--calendarBorder);
                border-top: solid thin var(--calendarBorder);

                td{
                    display: block;
                    border-left: solid thin var(--calendarBorder);
                    border-right: solid thin var(--calendarBorder);
                    border-bottom: none;
                    max-width: 100%;
                }
            }
            tr:first-of-type{
                margin-top: 20px;
            }
        }
    }

    .inner-col{
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 5px;
        width: 100%;
    }
}
</style>
