<template>
    <Modal size="large" custom-class="incident-detail-modal" @close="emit('close', false)">
        <template #title>{{ isCreateMode ? 'インシデント報告【新規作成】' : editMode ? 'インシデント報告【編集】' : 'インシデント報告' }}</template>
        <template #menu>
            <ItemMenu v-if="!isCreateMode && canEditIncident && menuItems.length" :items="menuItems" />
        </template>
        <template #content>
            <div v-if="viewMode === 'detail'" class="incident-detail-shell">
                <aside class="incident-detail-side">
                    <div v-if="!isReporterCreateMode" class="incident-detail-score" :style="{ borderColor: riskLevelColor(localIncident) }">
                        <div class="flex items-center gap-2 py-4">
                            <span>{{ incidentPoint(localIncident) || '-' }}</span>
                            <small>ポイント</small>
                        </div>                       
                    </div>
                    <div class="incident-detail-facts">
                        <div v-if="!isReporterCreateMode">
                            <span>ステータス</span>
                            <div v-if="editMode && canEditIncident" class="mt-3 w-full">
                                <select v-model="mutableParams.status" class="custom-a-input">
                                    <option value="" disabled>ステータスを選択</option>
                                    <option
                                        v-for="status in incidentOptions.statuses"
                                        :key="status"
                                        :value="status"
                                    >
                                        {{ status }}
                                    </option>
                                </select>
                            </div>
                            <strong v-else>{{ localIncident.status || '未設定' }}</strong>
                        </div>
                        <div v-if="!isReporterCreateMode">
                            <span>発生日</span>
                            <div v-if="editMode && canEditIncident" class="mt-3 w-full">
                                <input
                                    v-model="mutableParams.occurred_date"
                                    type="date"
                                    class="custom-a-input"
                                    :class="{'date-color' : theme.dark }"
                                />
                            </div>
                            <strong v-else>{{ formatDate(localIncident.occurred_date) }}</strong>
                        </div>
                        <div v-if="!isReporterCreateMode">
                            <span>指導日</span>
                            <div v-if="editMode && canEditIncident" class="mt-3 w-full">
                                <input
                                    v-model="mutableParams.instruction_date"
                                    type="date"
                                    class="custom-a-input"
                                    :class="{'date-color' : theme.dark }"
                                />
                            </div>
                            <strong v-else>{{ formatDate(localIncident.instruction_date) }}</strong>
                        </div>
                        <div>
                            <span>区分</span>
                            <div v-if="editMode && canEditIncident" class="mt-3 w-full">
                                <select
                                    v-model="mutableParams.incident_category_id"
                                    class="custom-a-input"
                                >
                                    <option :value="null">未設定</option>
                                    <option
                                        v-for="category in incidentOptions.categories"
                                        :key="category.id"
                                        :value="category.id"
                                    >
                                        {{ category.name || `区分 ${category.id}` }}
                                    </option>
                                </select>
                            </div>
                            <strong v-else>{{ localIncident.category?.name || '未設定' }}</strong>
                        </div>
                        <div v-if="!isReporterCreateMode">
                            <span>懲罰区分</span>
                            <div v-if="editMode && canEditIncident" class="mt-3 w-full">
                                <select
                                    v-model="mutableParams.incident_punishment_id"
                                    class="custom-a-input"
                                >
                                    <option :value="null">未設定</option>
                                    <option
                                        v-for="punishment in incidentOptions.punishments"
                                        :key="punishment.id"
                                        :value="punishment.id"
                                    >
                                        {{ punishment.name || `懲罰区分 ${punishment.id}` }}
                                    </option>
                                </select>
                            </div>
                            <strong v-else>{{ localIncident.punishment?.name || '未設定' }}</strong>
                        </div>
                    </div>
                </aside>

                <main class="incident-detail-main">
                    <section class="incident-detail-section">
                        <!-- <h3>関係者</h3> -->
                        <div class="post-separetor"><div>関係者</div></div>
                        <div class="incident-people-grid">
                            <div class="flex flex-col gap-3">
                                <span v-if="!editMode">当事者</span>
                                <MemberSelector
                                    v-if="editMode && canEditIncident"
                                    v-model="selectedCausedByUser"
                                    :multiple="false"
                                    :close-on-select="true"
                                    :options="userOptions"
                                    place-holder="当事者を選択"
                                />
                                <template v-else>
                                    <UserPanel v-if="localIncident.caused_by_user" :user="localIncident.caused_by_user" with-name size="25" disable-instant/>
                                    <strong v-else>-</strong>
                                </template>
                            </div>
                            <div class="flex flex-col gap-3">
                                <span>報告者</span>
                                <UserPanel v-if="localIncident.reported_by_user" :user="localIncident.reported_by_user" with-name size="25" disable-instant/>
                                <strong v-else>-</strong>
                            </div>
                            <div>
                                <span v-if="!editMode">プロジェクト</span>
                                <ItemSelector
                                    v-if="editMode && canEditIncident"
                                    v-model="mutableParams.project_record_id"
                                    :multiple="false"
                                    :clearable="true"
                                    :close-on-select="true"
                                    :options="projectOptions"
                                    :reduce="option => option?.id ?? option"
                                    label="name"
                                    place-holder="プロジェクトを選択"
                                />
                                <strong v-else>{{ localIncident.project_record?.name || '-' }}</strong>
                            </div>
                            <div class="flex flex-col">
                                <span v-if="!editMode">関係者</span>
                                <ShortInput
                                    v-if="editMode && canEditIncident"
                                    v-model="mutableParams.related_parties"
                                    place-holder="関係者"
                                />
                                <strong v-else>{{ localIncident.related_parties || '-' }}</strong>
                            </div>
                        </div>
                    </section>

                    <section class="incident-detail-section">
                        <div class="post-separetor"><div>発生内容</div></div>
                        <div v-if="!editMode" class="incident-field-stack">
                            <DetailItem label="概要" :value="localIncident.description" />
                            <DetailItem label="発生場所" :value="localIncident.occured_location" />
                            <DetailItem label="原因" :value="localIncident.reason" />
                        </div>
                        <div v-else class="flex flex-col gap-6">
                            <div>
                                <LongInput v-model="mutableParams.description" place-holder="インシデントの概要を入力" />
                            </div>
                            <div>
                                <ShortInput v-model="mutableParams.occured_location" place-holder="発生場所を入力" />
                            </div>
                            <div>
                                <LongInput v-model="mutableParams.reason" place-holder="インシデントの原因を入力" />
                            </div>
                        </div>
                    </section>

                    <section class="incident-detail-section">
                        <div class="post-separetor"><div>対応・再発防止</div></div>
                        <div v-if="!editMode" class="incident-field-stack">                            
                            <DetailItem label="再発防止策" :value="localIncident.prevention" />
                            <DetailItem label="再発防止策の実施状況" :value="localIncident.prevention_apply_status" />
                            <DetailItem label="是正対応" :value="localIncident.resolution" />
                            <DetailItem label="指導内容" :value="localIncident.instruction" />
                        </div>
                        <div v-else class="flex flex-col gap-6">                            
                            <div>
                                <LongInput v-model="mutableParams.prevention" place-holder="再発防止策を入力" />
                            </div>
                            <div>
                                <ShortInput v-model="mutableParams.prevention_apply_status" place-holder="再発防止策の実施状況を入力" />
                            </div>
                            <div>
                                <LongInput v-model="mutableParams.resolution" place-holder="是正対応を入力" />
                            </div>
                            <div>
                                <LongInput v-model="mutableParams.instruction" place-holder="指導内容を入力" />
                            </div>
                        </div>
                    </section>

                    <section v-if="canEditAdminFields" class="incident-detail-section">
                        <div class="post-separetor"><div>管理情報</div></div>
                        <div v-if="!editMode" class="flex gap-6 mb-4 w-fit">
                            <div class="flex flex-col gap-2 bg-[--bg3] p-4 rounded-lg">
                                <DetailItem label="リスクレベル" :value="localIncident.risk_level" />
                            </div>
                            <div class="flex flex-col gap-2 bg-[--bg3] p-4 rounded-lg">
                                <DetailItem label="損害レベル" :value="localIncident.severity_level" />
                            </div>
                        </div>
                        <div v-if="editMode" class="flex mb-4 w-fit gap-6">
                            <div class="flex flex-col gap-2 bg-[--bg3] p-4 rounded-lg">
                                <span class="text-[12px]">リスクレベル</span>
                                <select
                                    v-model="mutableParams.risk_level"
                                    class="custom-a-input"
                                    :rules="canEditAdminFields ? 'min:1|max:3' : ''"
                                    :disabled="!canEditAdminFields"
                                >
                                    <option value="">リスクレベルを選択</option>
                                    <option value="1">1</option>
                                    <option value="2">2</option>
                                    <option value="3">3</option>
                                </select>
                            </div>
                            <div class="flex flex-col gap-2 bg-[--bg3] p-4 rounded-lg">
                                <span class="text-[12px]">損害レベル</span>
                                <select
                                    v-model="mutableParams.severity_level"
                                    class="custom-a-input"
                                    :rules="canEditAdminFields ? 'min:1|max:3' : ''"
                                    :disabled="!canEditAdminFields"
                                >
                                    <option value="">損害レベルを選択</option>
                                    <option value="1">1</option>
                                    <option value="2">2</option>
                                    <option value="3">3</option>
                                </select>
                            </div>
                        </div>
                        <div class="incident-admin-grid">                            
                            <template v-if="!editMode">                               

                                <DetailItem label="損害額" :value="formatAmount(localIncident.amount_of_damage)" />
                                <DetailItem label="支払先" :value="localIncident.payee" />
                                <DetailItem label="費用詳細" :value="localIncident.expense_details" />                                
                            </template>
                            <template v-else>
                                <div>
                                    <ShortInput
                                        v-model="mutableParams.amount_of_damage"
                                        type="number"
                                        place-holder="損害額を入力"
                                    />
                                </div>
                                <div>
                                    <ShortInput v-model="mutableParams.payee" place-holder="支払先を入力" />
                                </div>
                                <div class="col-span-2">
                                    <ShortInput v-model="mutableParams.expense_details" place-holder="費用詳細を入力" />
                                </div>
                            </template>
                        </div>
                        <div class="mt-6">
                            <div v-if="!editMode" class="incident-field-stack">
                                <DetailItem label="委員会メンバー" :value="localIncident.committee_members" />
                                <DetailItem label="委員会決定" :value="localIncident.committee_decision" />
                                <DetailItem label="委員会決定日" :value="formatDate(localIncident.committee_decision_date)" />
                                <DetailItem label="メモ" :value="localIncident.memo" />
                                <DetailItem label="顛末コメント" :value="localIncident.aftermath_comment" />
                            </div>
                            <div v-else class="flex flex-col gap-6">
                                <div class="incident-admin-grid">
                                     <ShortInput
                                        v-model="mutableParams.committee_decision_date"
                                        type="date"
                                        place-holder="委員会決定日を入力"
                                    />
                                    <ShortInput v-model="mutableParams.committee_members" place-holder="委員会メンバーを入力" />
                                </div>
                                <div>
                                    <LongInput v-model="mutableParams.committee_decision" place-holder="委員会決定を入力" />
                                </div>
                                <div>
                                    <LongInput v-model="mutableParams.memo" place-holder="メモを入力" />
                                </div>
                                <div>
                                    <LongInput v-model="mutableParams.aftermath_comment" place-holder="顛末コメントを入力" />
                                </div>
                            </div>
                        </div>
                    </section>
                    <section class="incident-detail-section">
                        <h3>添付ファイル</h3>
                        <FileUploader
                            v-if="editMode && canEditIncident"
                            v-model="uploadedFiles"
                            path="/incident_files"
                            custom-place-holder="ファイルを添付"
                        />
                        <PostFiles
                            v-else-if="localIncident.files?.length"
                            :items="localIncident.files"
                            path="incident_files"
                        />
                        <div v-else class="text-[12px] text-[gray]">添付ファイルはありません。</div>
                    </section>
                    <AppCommentSection
                        v-if="!editMode && !isCreateMode"
                        commentable-type="incident"
                        :commentable-id="localIncident.id"
                        title="コメント"
                        @count-changed="handleCommentCountChanged"
                    />
                </main>
            </div>
            <div v-else class="incident-history">
                <div class="incident-history-header">
                    <div>
                        <h3>更新履歴</h3>
                    </div>
                    <button type="button" class="jump-link bg-inherit text-sm" @click="viewMode = 'detail'">
                        詳細へ戻る
                    </button>
                </div>
                <div v-if="historyLoading" class="py-8 flex justify-center">
                    <div class="spinner-micro"></div>
                </div>
                <div v-else-if="incidentLogs.length" class="incident-history-list">
                    <div v-for="log in incidentLogs" :key="log.id" class="incident-history-item">
                        <div class="incident-history-meta">
                            <div>
                                <strong>{{ actionLabel(log.action) }}</strong>
                                <span>{{ formatDateTime(log.created_at) }}</span>
                            </div>
                            <UserPanel v-if="log.user" :user="log.user" with-name size="22" disable-instant/>
                            <span v-else class="text-[12px] text-[gray]">System</span>
                        </div>
                        <div v-if="log.note" class="incident-history-note">{{ log.note }}</div>
                        <div v-if="displayLogChanges(log)" class="incident-history-changes">
                            <div v-for="(change, field) in displayLogChanges(log)" :key="field" class="incident-history-change">
                                <span>{{ fieldLabel(String(field)) }}</span>
                                <div>
                                    <del>{{ formatLogValue(change?.display_old ?? change?.old) }}</del>
                                    <strong>→</strong>
                                    <ins>{{ formatLogValue(change?.display_new ?? change?.new) }}</ins>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-else class="incident-history-empty">
                    更新履歴はありません。
                </div>
            </div>
            <div v-if="editMode" class="incident-detail-actions">
                <button type="button" class="jump-link bg-inherit text-sm" @click="cancelEdit">
                    キャンセル
                </button>
                <LoaderButton
                    style="margin: 0"
                    :loading="saving"
                    :content="isCreateMode ? '作成する' : hasChanges ? '保存する' : '変更なし'"
                    @triggered="saveChanges"
                />
            </div>
        </template>
    </Modal>
</template>

<script setup lang="ts">
import { DateTime } from 'luxon';
import { computed, h, onMounted, ref, watch } from 'vue';
import Modal from '@/components/Global/Modal.vue';
import UserPanel from '@/components/Global/UserPanel.vue';
import { Incident, IncidentCategory, IncidentPunishment } from '@/interface/incident';
import { UpdateLog, UpdateLogAction } from '@/interface/updateLog';
import { useApi } from '@/composables/api';
import { useAuthUserStore } from '@/store/auth';
import ItemMenu from '@/components/Global/ItemMenu.vue';
import LoaderButton from '@/components/Global/LoaderButton.vue';
import { CommonFile, MenuList, User } from '@/interface/globalInterface';
import { useDialog } from '@/composables/dialog';
import MemberSelector from '@/components/Form/MemberSelector.vue';
import ItemSelector from '@/components/Form/ItemSelector.vue';
import type { Project } from '@/interface/projectInterface';
import LongInput from '../Form/LongInput.vue';
import ShortInput from '../Form/ShortInput.vue';
import AppCommentSection from '@/components/Global/AppCommentSection.vue';
import FileUploader from '@/components/Form/FileUploader.vue';
import PostFiles from '@/components/Post/PostFiles.vue';
import { useTheme } from '@/store/theme.js';

const props = defineProps<{
    incident?: Incident
    createMode?: boolean
    reporterMode?: boolean
    initialIncident?: Partial<Incident>
}>()

const emit = defineEmits<{
    close: [refresh: boolean]
    updated: [incident: Incident]
    created: [incident: Incident]
    deleted: [incident: Incident]
}>()
const api = useApi()
const auth = useAuthUserStore()
const dialog = useDialog()
const canEditAdminFields = computed(() => auth.isAdmin || auth.isBoss)
const canEditIncident = computed(() => canEditAdminFields.value || auth.isPM)
const isCreateMode = computed(() => props.createMode ?? false)
const isReporterCreateMode = computed(() => isCreateMode.value && (props.reporterMode ?? false))
const createBlankIncident = (): Incident => ({
    id: 0,
    title: null,
    description: null,
    caused_by: null,
    incident_category_id: null,
    occurred_date: null,
    project_record_id: null,
    status: null,
    reported_by: auth.activeUser?.id ?? null,
    reported_by_user: auth.activeUser,
    comments_count: 0,
    files: [],
    ...props.initialIncident,
})
const editMode = ref(isCreateMode.value)
const viewMode = ref<'detail' | 'history'>('detail')
const saving = ref(false)
const deleting = ref(false)
const historyLoading = ref(false)
const localIncident = ref<Incident>({ ...(props.incident ?? createBlankIncident()) })
const mutableParams = ref<Partial<Incident>>({ ...(props.incident ?? createBlankIncident()) })
const selectedCausedByUser = ref<User | null>(props.incident?.caused_by_user ?? null)
const uploadedFiles = ref<CommonFile[]>([...(props.incident?.files ?? [])])
const incidentLogs = ref<UpdateLog[]>([])
type IncidentProjectOption = Pick<Project, 'id' | 'name' | 'date_start' | 'date_end' | 'category'>
const incidentOptions = ref<{
    categories: IncidentCategory[]
    punishments: IncidentPunishment[]
    users: User[]
    projects: IncidentProjectOption[]
    statuses: string[]
}>({
    categories: [],
    punishments: [],
    users: [],
    projects: [],
    statuses: [],
})
const editableKeys = [
    'status',
    'occurred_date',
    'instruction_date',
    'incident_category_id',
    'incident_punishment_id',
    'project_record_id',
    'related_parties',
    'description',
    'occured_location',
    'reason',
    'instruction',
    'prevention',
    'prevention_apply_status',
    'resolution',
    'risk_level',
    'severity_level',
    'amount_of_damage',
    'payee',
    'expense_details',
    'committee_decision_date',
    'committee_members',
    'committee_decision',
    'memo',
    'aftermath_comment',
] as const satisfies readonly (keyof Incident)[]

const adminOnlyKeys = [
    'risk_level',
    'severity_level',
    'amount_of_damage',
    'payee',
    'expense_details',
    'committee_decision_date',
    'committee_members',
    'committee_decision',
    'memo',
    'aftermath_comment',
] as const satisfies readonly (keyof Incident)[]
const theme = useTheme()
const isProjectManager = computed(() => {
    return localIncident.value.project_record?.members?.some(member => member.id === auth.user?.id) || false
})

const menuItems = computed<MenuList[]>(() => {
    const items: MenuList[] = [
        {
            title: viewMode.value === 'history' ? '詳細を見る' : '更新履歴',
            action: () => viewMode.value === 'history' ? viewMode.value = 'detail' : showHistory(),
        },
    ]

    if (!canEditIncident.value) return items

    return [
        
        {
            title: editMode.value ? '編集をキャンセル' : '編集',
            action: () => editMode.value ? cancelEdit() : startEdit(),
        },
        {
            title: deleting.value ? '削除中...' : '削除',
            action: deleteIncident,
        },
        ...items,
    ]
})

const buildPayload = () => {
    const payload: Partial<Incident> = {}

    if (isCreateMode.value) {
        for (const key of editableKeys) {
            if (!canEditAdminFields.value && (adminOnlyKeys as readonly (keyof Incident)[]).includes(key)) continue
            const nextValue = normalizeUpdateValue(key, mutableParams.value[key])
            if (nextValue !== null && nextValue !== '') {
                ;(payload as any)[key] = nextValue
            }
        }

        const nextCausedBy = selectedCausedByUser.value?.id ?? null
        if (nextCausedBy) {
            payload.caused_by = nextCausedBy
        }

        const nextFileIds = uploadedFiles.value.map(file => file.id).sort((a, b) => a - b)
        if (nextFileIds.length) {
            ;(payload as Partial<Incident> & { file_ids: number[] }).file_ids = nextFileIds
        }

        return payload
    }

    for (const key of editableKeys) {
        if (!canEditAdminFields.value && (adminOnlyKeys as readonly (keyof Incident)[]).includes(key)) continue
        const nextValue = normalizeUpdateValue(key, mutableParams.value[key])
        const currentValue = normalizeUpdateValue(key, localIncident.value[key])

        if (nextValue !== currentValue) {
            ;(payload as any)[key] = nextValue
        }
    }

    const nextCausedBy = selectedCausedByUser.value?.id ?? null
    const currentCausedBy = localIncident.value.caused_by ?? null
    if (nextCausedBy !== currentCausedBy) {
        payload.caused_by = nextCausedBy
    }

    const nextFileIds = uploadedFiles.value.map(file => file.id).sort((a, b) => a - b)
    const currentFileIds = (localIncident.value.files ?? []).map(file => file.id).sort((a, b) => a - b)
    if (JSON.stringify(nextFileIds) !== JSON.stringify(currentFileIds)) {
        ;(payload as Partial<Incident> & { file_ids: number[] }).file_ids = nextFileIds
    }

    return payload
}

const hasChanges = computed(() => isCreateMode.value || Object.keys(buildPayload()).length > 0)

const userOptions = computed(() => {
    const users = [...incidentOptions.value.users]
    const currentUser = localIncident.value.caused_by_user

    if (currentUser && !users.some(user => user.id === currentUser.id)) {
        users.push(currentUser)
    }

    return users
})

const projectOptions = computed(() => {
    const projects = [...incidentOptions.value.projects]
    const currentProject = localIncident.value.project_record

    if (currentProject && !projects.some(project => project.id === currentProject.id)) {
        projects.push(currentProject)
    }

    return projects
})

onMounted(() => {
    loadIncidentOptions()
})

watch(
    () => props.incident,
    (incident) => {
        const nextIncident = incident ?? createBlankIncident()
        localIncident.value = { ...nextIncident }
        mutableParams.value = { ...nextIncident }
        selectedCausedByUser.value = nextIncident.caused_by_user ?? null
        uploadedFiles.value = [...(nextIncident.files ?? [])]
        editMode.value = isCreateMode.value
        viewMode.value = 'detail'
        incidentLogs.value = []
    },
)
const loadIncidentOptions = async () => {
    const data = await api.get('/incident_options', null, { silent: true })
    if (!data) return

    incidentOptions.value = {
        categories: data.categories ?? [],
        punishments: data.punishments ?? [],
        users: data.users ?? [],
        projects: data.projects ?? [],
        statuses: data.statuses ?? [],
    }
}

const DetailItem = (props: { label: string; value?: string | number | null }) => {
    const value = props.value === null || props.value === undefined || props.value === '' ? '-' : String(props.value)
    return h('div', { class: 'incident-detail-item' }, [
        h('span', props.label),
        h('p', value),
    ])
}
const formatDate = (date?: string | null) => {
    if (!date) return '-'
    const parsed = DateTime.fromISO(date)
    return parsed.isValid ? parsed.toFormat('yyyy/M/d') : date
}

const formatDateTime = (date?: string | null) => {
    if (!date) return '-'
    const parsed = DateTime.fromISO(date)
    return parsed.isValid ? parsed.toFormat('yyyy/M/d HH:mm') : date
}

const normalizeDate = (date?: string | null) => {
    if (!date) return null
    const parsed = DateTime.fromISO(date)
    return parsed.isValid ? parsed.toISODate() : date
}

const normalizeUpdateValue = (key: keyof Incident, value: unknown) => {
    if (key === 'occurred_date' || key === 'instruction_date' || key === 'committee_decision_date') {
        return normalizeDate(value as string | null | undefined)
    }

    if (key === 'risk_level' || key === 'severity_level' || key === 'amount_of_damage') {
        if (value === '' || value === null || value === undefined) return null
        const numericValue = Number(value)
        return Number.isFinite(numericValue) ? numericValue : value
    }

    return value === undefined ? null : value
}

const formatAmount = (amount?: number | null) => {
    if (amount === null || amount === undefined) return '-'
    return amount.toLocaleString()
}

const incidentPoint = (incident: Incident) => {
    return (incident.risk_level ?? 0) * (incident.severity_level ?? 0)
}

const RISK_LEVEL_COLORS = [
    { min: 9, color: '#ff6347' },
    { min: 6, color: '#ff826c' },
    { min: 4, color: '#ffa191' },
    { min: 2, color: '#ffc1b5' },
    { min: 1, color: '#ffe0da' },
]

const riskLevelColor = (incident: Incident) => {
    const riskLevel = incidentPoint(incident)
    return RISK_LEVEL_COLORS.find(l => riskLevel >= l.min)?.color ?? 'var(--bg2)'
}
const startEdit = () => {
    mutableParams.value = { ...localIncident.value }
    selectedCausedByUser.value = localIncident.value.caused_by_user ?? null
    editMode.value = true
}

const cancelEdit = () => {
    if (isCreateMode.value) {
        emit('close', false)
        return
    }

    mutableParams.value = { ...localIncident.value }
    selectedCausedByUser.value = localIncident.value.caused_by_user ?? null
    uploadedFiles.value = [...(localIncident.value.files ?? [])]
    editMode.value = false
}

const showHistory = async () => {
    if (isCreateMode.value) return
    editMode.value = false
    viewMode.value = 'history'

    if (incidentLogs.value.length) return

    historyLoading.value = true
    try {
        const data = await api.get('/incident_logs', { id: localIncident.value.id })
        incidentLogs.value = data ?? []
    } finally {
        historyLoading.value = false
    }
}

const saveChanges = async () => {
    if (saving.value || !hasChanges.value) return

    saving.value = true
    try {
        const payload = buildPayload()
        const res = isCreateMode.value
            ? await api.post('/incident_record_create', payload, { toast: 'インシデントを作成しました。' })
            : await api.post('/incident_record_update', {
                id: localIncident.value.id,
                ...payload,
            }, { toast: 'インシデントを更新しました。' })

        if (res?.incident) {
            localIncident.value = res.incident
            mutableParams.value = { ...res.incident }
            uploadedFiles.value = [...(res.incident.files ?? [])]
            editMode.value = false
            incidentLogs.value = []
            if (isCreateMode.value) {
                emit('created', res.incident)
            } else {
                emit('updated', res.incident)
            }
        }
    } finally {
        saving.value = false
    }
}

const handleCommentCountChanged = (count: number) => {
    localIncident.value.comments_count = count
    emit('updated', { ...localIncident.value })
}

const deleteIncident = async () => {
    if (deleting.value) return

    const answer = await dialog.ask('このインシデントを削除しますか？', {
        answers: [
            { value: true, label: '削除する' },
            { value: false, label: 'キャンセル' },
        ],
    })

    if (!answer.value) return

    deleting.value = true
    try {
        const res = await api.post('/incident_record_delete', {
            id: localIncident.value.id,
        }, { toast: 'インシデントを削除しました。' })

        if (res?.deleted) {
            emit('deleted', localIncident.value)
            emit('close', true)
        }
    } finally {
        deleting.value = false
    }
}

const actionLabel = (action: UpdateLogAction) => {
    const labels: Record<string, string> = {
        created: '作成',
        updated: '更新',
        status_changed: 'ステータス変更',
        deleted: '削除',
        restored: '復元',
    }

    return labels[action] ?? action
}

const fieldLabel = (field: string) => {
    const labels: Record<string, string> = {
        status: 'ステータス',
        occurred_date: '発生日',
        instruction_date: '指導日',
        incident_category_id: '区分',
        incident_punishment_id: '懲罰区分',
        caused_by: '当事者',
        project_record_id: 'プロジェクト',
        files: '添付ファイル',
        related_parties: '関係者',
        description: '概要',
        occured_location: '発生場所',
        reason: '原因',
        instruction: '指導内容',
        prevention: '再発防止策',
        prevention_apply_status: '再発防止策の実施状況',
        resolution: '解決内容',
        risk_level: 'リスクレベル',
        severity_level: '損害レベル',
        amount_of_damage: '損害額',
        payee: '支払先',
        expense_details: '費用詳細',
        committee_decision_date: '委員会決定日',
        committee_members: '委員会メンバー',
        committee_decision: '委員会決定',
        memo: 'メモ',
        aftermath_comment: '顛末コメント',
        deleted_at: '削除日時',
    }

    return labels[field] ?? field
}

const displayLogChanges = (log: UpdateLog) => {
    return log.display_changes ?? log.changes
}

const formatLogValue = (value: unknown) => {
    if (value === null || value === undefined || value === '') return '未設定'
    if (Array.isArray(value)) return value.length ? value.join('、') : '未設定'
    if (typeof value === 'object') return JSON.stringify(value)
    return String(value)
}
</script>

<style lang="scss">
.incident-detail-modal{
    max-width: 1120px;
}

.incident-detail-title{
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
}

.incident-detail-mark{
    width: 8px;
    height: 42px;
    flex-shrink: 0;
}

.incident-detail-kicker{
    font-size: 11px;
    color: gray;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    margin-bottom: 4px;
}

.incident-detail-title h2{
    font-size: 17px;
    font-weight: 700;
    line-height: 1.35;
    max-width: 760px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.incident-detail-shell{
    display: grid;
    grid-template-columns: 220px minmax(0, 1fr);
    gap: 24px;
    color: var(--primary-color);
}

.incident-detail-side{
    border-right: 1px solid var(--calendarBorder);
    padding-right: 18px;
}

.incident-detail-score{
    border-left: 6px solid var(--calendarBorder);
    background: var(--bg3);
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding-left: 14px;
    margin-bottom: 18px;
}

.incident-detail-score span{
    font-size: 28px;
    line-height: 1;
}

.incident-detail-score small,
.incident-detail-facts span,
.incident-people-grid span,
.incident-detail-item span{
    font-size: 11px;
    color: gray;
}

.incident-detail-facts{
    display: flex;
    flex-direction: column;
    gap: 30px;
}

.incident-detail-facts > div{
    border-bottom: 1px solid var(--calendarBorder);
    padding-bottom: 10px;
}

.incident-detail-facts strong{
    display: block;
    margin-top: 4px;
    font-size: 13px;
    font-weight: 700;
}

.incident-detail-main{
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 20px;
}


.incident-detail-section h3{
    margin: 0 0 12px;
    font-size: 14px;
}

.incident-people-grid,
.incident-admin-grid{
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 30px;
}

.incident-people-grid strong{
    display: block;
    margin-top: 5px;
    font-size: 13px;
}

.incident-field-stack{
    display: flex;
    flex-direction: column;
    gap: 30px;
}

.incident-detail-item{
    min-width: 0;
}

.incident-detail-item p{
    margin-top: 4px;
    white-space: pre-wrap;
    line-height: 1.6;
    font-size: 13px;
}

.incident-detail-actions{
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 14px;
    margin-top: 24px;
    padding-top: 18px;
    border-top: 1px solid var(--calendarBorder);
}

.incident-history-header{
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 16px;
    padding-bottom: 14px;
    border-bottom: 1px solid var(--calendarBorder);
    margin-bottom: 16px;
}

.incident-history-kicker{
    color: gray;
    font-size: 11px;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    margin-bottom: 4px;
}

.incident-history-header h3{
    font-size: 17px;
    font-weight: 700;
}

.incident-history-list{
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.incident-history-item{
    border-left: 3px solid var(--calendarBorder);
    background: var(--bg3);
    padding: 12px 14px;
}

.incident-history-meta{
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 10px;
}

.incident-history-meta strong{
    display: block;
    font-size: 13px;
    margin-bottom: 3px;
}

.incident-history-meta span,
.incident-history-change span,
.incident-history-note{
    color: gray;
    font-size: 11px;
}

.incident-history-note{
    margin-bottom: 10px;
    white-space: pre-wrap;
}

.incident-history-changes{
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.incident-history-change{
    display: grid;
    grid-template-columns: 130px minmax(0, 1fr);
    gap: 12px;
    font-size: 12px;
    line-height: 1.5;
}

.incident-history-change div{
    display: flex;
    align-items: center;
    gap: 8px;
    min-width: 0;
}

.incident-history-change del{
    color: gray;
    text-decoration: line-through;
    overflow-wrap: anywhere;
}

.incident-history-change ins{
    color: var(--primary-color);
    text-decoration: none;
    font-weight: 700;
    overflow-wrap: anywhere;
}

.incident-history-empty{
    color: gray;
    text-align: center;
    padding: 36px 0;
    font-size: 13px;
}

@media screen and (max-width: 959px) {
    .incident-detail-shell{
        grid-template-columns: 1fr;
    }

    .incident-detail-side{
        border-right: none;
        border-bottom: 1px solid var(--calendarBorder);
        padding-right: 0;
        padding-bottom: 16px;
    }

    .incident-people-grid,
    .incident-admin-grid{
        grid-template-columns: 1fr;
    }

    .incident-history-change{
        grid-template-columns: 1fr;
        gap: 4px;
    }
}
</style>
