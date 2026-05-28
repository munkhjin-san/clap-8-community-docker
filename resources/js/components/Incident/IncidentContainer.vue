<template>
    <div class="bg-[var(--background-color)] relative min-h-full pb-5">
        <div v-if="loading" class="spinner-micro fixed top-2/4 left-2/4"></div>
        <div v-if="optionsLoaded && !canViewIncidents" class="incident-no-permission mx-4">
            <h3>インシデント管理の権限がありません</h3>
            <p>インシデント一覧は管理者、上席者、または担当プロジェクトのPMのみ閲覧できます。</p>
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
                <button
                    v-if="canManageIncidentSettings"
                    type="button"
                    class="incident-settings-button"
                    title="インシデント設定"
                    @click="settingsOpen = true"
                >
                    <Gear size="16" />
                    設定
                </button>
            </div>
        </div>
        <table class="incident-table mx-4 w-[calc(100%-40px)]">
            <thead>
                <tr>
                    <td>発生日</td>
                    <td>当事者</td>
                    <td>報告者</td>
                    <td>プロジェクト</td>
                    <td>区分</td>
                    <td>ステータス</td>
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
                            <td><div class="inner-col"><span class="mobile">発生日</span><p class="text-[gray] text-[12px]">{{ formatDate(incident.occurred_date) }}</p></div></td>
                            <td><div class="inner-col"><span class="mobile">対象者</span><UserPanel v-if="incident.caused_by_user" :user="incident.caused_by_user" with-name size="20" disable-instant/></div></td>
                            <td><div class="inner-col"><span class="mobile">報告者</span><UserPanel v-if="incident.reported_by_user" :user="incident.reported_by_user" with-name size="20" disable-instant/></div></td>
                            <td class="max-w-[180px] overflow-hidden text-ellipsis"><div class="inner-col"><span class="mobile">プロジェクト</span><p class="truncate">{{ incident.project_record?.name || '-' }}</p></div></td>
                            <td><div class="inner-col"><span class="mobile">区分</span>{{ incident.category?.name || '-' }}</div></td>
                            <td><div class="inner-col"><span class="mobile">ステータス</span>{{ incident.status || '未設定' }}</div></td>
                            <td><div class="inner-col"><span class="mobile">ポイント</span>
                                <div v-if="(incident.risk_level ?? 0) * (incident.severity_level ?? 0)" :style="{backgroundColor: riskLevelColor(incident)}" class="w-6 h-6 rounded-full flex items-center justify-center text-[12px]">
                                    {{ (incident.risk_level ?? 0) * (incident.severity_level ?? 0) || '' }}
                                </div>
                            </div></td>
                            <td><div class="inner-col"><span class="mobile">コメント</span>{{ incident.comments_count && incident.comments_count > 0 ? `${incident.comments_count}件` : '' }}</div></td>
                        </tr>
                    </template>
                </template>
                <tr v-else-if="fetchCount > 0">
                    <td colspan="8" class="!text-center">データがありません</td>
                </tr>
            </tbody>
        </table>
        <div class="mt-3">
            <PostSearchPager
                style="margin: 0;"
                :possiblePage="incidentsData.last_page"
                :activePath="incidentsData.current_page"
                @setNavi="(index) => getIncidents(incidentsData.current_page + index)"
                @setActivePage="(index) => getIncidents(index)"
            />
        </div>
        <FloatButton v-if="canManageIncidentSettings" class="fixed" @action="createModalOpen = true">
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

const api = useApi()
const route = useRoute()
const router = useRouter()
const loading = ref(false)
const fetchCount = ref(0)
const activeIncident = ref<Incident | null>(null)
const openingIncidentId = ref<number | null>(null)
const settingsOpen = ref(false)
const createModalOpen = ref(false)
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
const incidentOptions = ref<IncidentFilterOptions>({
    categories: [],
    filter_users: [],
    filter_projects: [],
    statuses: [],
    can_manage: false,
    can_view: false,
})
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
}

type IncidentSearchQueryKey = keyof IncidentFilters | 'occurred_date' | 'point'
type IncidentMultiFilterKey = 'project_record_id' | 'incident_category_id' | 'status'
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
const selectedSearchQuery = ref({
    name: 'キーワード',
    value: 'keyword' as IncidentSearchQueryKey,
})

const selectorOptions = computed(() => {
    return {
        project_record_id: filterProjects.value.map(project => ({ name: project.name, value: project.id })),
        incident_category_id: incidentOptions.value.categories.map(category => ({ name: category.name || '未設定', value: category.id })),
        status: incidentOptions.value.statuses.map(status => ({ name: status, value: status })),
    } as Record<string, Array<{ name: string; value: string | number }>>
})

const isMultiFilterKey = (key: IncidentSearchQueryKey): key is IncidentMultiFilterKey => {
    return ['project_record_id', 'incident_category_id', 'status'].includes(key)
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

        filters[key] = values.map(Number)
    },
})

const searchQueryOptions = [
    { name: 'キーワード', value: 'keyword' },
    { name: '発生日', value: 'occurred_date' },
    { name: '当事者', value: 'caused_by' },
    { name: '報告者', value: 'reported_by' },
    { name: 'プロジェクト', value: 'project_record_id' },
    { name: '区分', value: 'incident_category_id' },
    { name: 'ステータス', value: 'status' },
    { name: 'ポイント', value: 'point' },
]

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
        getIncidents(1)
    },
    { deep: true },
)

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
    menu.close()
}

const handleIncidentUpdated = (incident: Incident) => {
    activeIncident.value = incident
    const index = incidentsData.value.data.findIndex(item => item.id === incident.id)
    if (index !== -1) {
        incidentsData.value.data[index] = incident
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

@media screen and (max-width: 959px) {
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
