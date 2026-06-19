<template>
    <div v-if="auth.isAdmin" class="admin-window employee-change-admin">
        <div class="employee-change-toolbar">
            <div>
                <h2>各種届出</h2>
            </div>
            <div class="employee-change-filters">
                <select v-model="filters.status" @change="fetchApplications(1)">
                    <option v-for="option in statusOptions" :key="option.value" :value="option.value">
                        {{ option.label }}
                    </option>
                </select>
                <select v-model="filters.type" @change="fetchApplications(1)">
                    <option v-for="option in typeOptions" :key="option.value" :value="option.value">
                        {{ option.label }}
                    </option>
                </select>
                <button class="employee-change-button" type="button" @click="fetchApplications(applications.current_page)">
                    更新
                </button>
            </div>
        </div>

        <div class="employee-change-table-wrap">
            <div v-if="loading" class="control-loader">読み込み中</div>
            <table class="employee-change-table">
                <thead>
                    <tr>
                        <th>申請日</th>
                        <th>氏名</th>
                        <th>種類</th>
                        <th>適用日</th>
                        <th>ステータス</th>
                        <th>確認者</th>
                    </tr>
                </thead>
                <tbody>
                    <template v-for="application in applications.data" :key="application.id">
                        <tr
                            :class="{ active: detailApplication?.id === application.id }"
                            class="employee-change-clickable-row"
                            @click="openApplication(application)"
                        >
                            <td>{{ formatDateTime(application.created_at) }}</td>
                            <td>{{ application.user?.name ?? '-' }}</td>
                            <td>{{ application.type_label || typeLabel(application.type) }}</td>
                            <td>{{ formatDate(application.effective_date) }}</td>
                            <td>
                                <span :class="['employee-change-status', application.status]">
                                    {{ application.status_label || statusLabel(application.status) }}
                                </span>
                            </td>
                            <td>{{ application.reviewed_by?.name ?? '-' }}</td>
                        </tr>
                    </template>
                    <tr v-if="!loading && !applications.data.length">
                        <td colspan="6" class="employee-change-empty">申請はありません。</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="employee-change-pagination" v-if="applications.last_page > 1">
            <button
                type="button"
                :disabled="applications.current_page <= 1"
                @click="fetchApplications(applications.current_page - 1)"
            >
                前へ
            </button>
            <span>{{ applications.current_page }} / {{ applications.last_page }}</span>
            <button
                type="button"
                :disabled="applications.current_page >= applications.last_page"
                @click="fetchApplications(applications.current_page + 1)"
            >
                次へ
            </button>
        </div>

        <Modal
            v-if="detailApplication"
            size="large"
            :loader="detailLoading"
            @close="closeDetail"
        >
            <template #title>
                <div class="employee-change-modal-title">
                    <strong>{{ detailApplication.type_label || typeLabel(detailApplication.type) }}</strong>
                    <span>{{ detailApplication.user?.name ?? '-' }}</span>
                    <span :class="['employee-change-status', detailApplication.status]">
                        {{ detailApplication.status_label || statusLabel(detailApplication.status) }}
                    </span>
                </div>
            </template>
            <template #content>
                <div class="employee-change-detail">
                    <div class="employee-change-modal-meta">
                        <div>
                            <span>申請日</span>
                            <strong>{{ formatDateTime(detailApplication.created_at) }}</strong>
                        </div>
                        <div>
                            <span>適用日</span>
                            <strong>{{ formatDate(detailApplication.effective_date) }}</strong>
                        </div>
                        <div>
                            <span>確認者</span>
                            <strong>{{ detailApplication.reviewed_by?.name ?? '-' }}</strong>
                        </div>
                    </div>

                    <div class="employee-change-detail-grid">
                        <div
                            v-for="item in detailItems(detailApplication)"
                            :key="item.label"
                            class="employee-change-detail-item"
                        >
                            <span>{{ item.label }}</span>
                            <strong>{{ item.value || '-' }}</strong>
                        </div>
                    </div>

                    <div v-if="detailApplication.files?.length" class="employee-change-files">
                        <span>添付</span>
                        <Files :items="detailApplication.files" :path="`/various_changes/${detailApplication.type}`" />
                    </div>

                    <!-- <textarea
                        v-model="reviewComments[detailApplication.id]"
                        class="employee-change-review-comment"
                        placeholder="レビューコメント"
                    ></textarea> -->
                    <LongInput
                        v-model="reviewComments[detailApplication.id]"
                        place-holder="レビューコメント"
                    />
                    <div class="employee-change-modal-actions">
                        <button
                            type="button"
                            :disabled="actionId === detailApplication.id || detailApplication.status === 'reviewing'"
                            @click="updateStatus(detailApplication, 'reviewing')"
                        >
                            レビュー中
                        </button>
                        <button
                            type="button"
                            :disabled="actionId === detailApplication.id || detailApplication.status === 'confirmed'"
                            @click="updateStatus(detailApplication, 'confirmed')"
                        >
                            承認
                        </button>
                        <button
                            type="button"
                            :disabled="actionId === detailApplication.id || detailApplication.status === 'denied'"
                            @click="updateStatus(detailApplication, 'denied')"
                        >
                            却下
                        </button>
                    </div>
                </div>
            </template>
        </Modal>
    </div>
    <div v-else class="employee-change-denied">
        アクセス権限ありません。
    </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref, watch } from 'vue';
import { DateTime } from 'luxon';
import { useRoute, useRouter } from 'vue-router';
import { useApi } from '@/composables/api';
import { useAuthUserStore } from '@/store/auth';
import Modal from '@/components/Global/Modal.vue';
import {
    applicationStatusFilterOptions,
    applicationStatusLabel,
    applicationTypeFilterOptions,
    applicationTypeLabel,
    type ApplicationStatus,
    type ApplicationStatusFilter,
    type ApplicationTypeFilter,
    type EmployeeChangeApplication,
} from '@/interface/employeeInterface';
import LongInput from '../Form/LongInput.vue';
import Files from '../Global/Files.vue';



type Pagination<T> = {
    data: T[]
    current_page: number
    last_page: number
    total: number
}

const api = useApi()
const auth = useAuthUserStore()
const route = useRoute()
const router = useRouter()
const loading = ref(false)
const detailLoading = ref(false)
const actionId = ref<number | null>(null)
const detailApplication = ref<EmployeeChangeApplication | null>(null)
const reviewComments = reactive<Record<number, string>>({})
const filters = reactive<{
    status: ApplicationStatusFilter
    type: ApplicationTypeFilter
}>({
    status: 'submitted',
    type: 'all',
})

const applications = ref<Pagination<EmployeeChangeApplication>>({
    data: [],
    current_page: 1,
    last_page: 1,
    total: 0,
})

const statusOptions = applicationStatusFilterOptions
const typeOptions = applicationTypeFilterOptions
const statusLabel = (status: ApplicationStatus) => applicationStatusLabel(status)
const typeLabel = applicationTypeLabel

const formatDate = (value?: string | null) => {
    if (!value) return '-'
    const date = DateTime.fromISO(value)
    return date.isValid ? date.toFormat('yyyy/M/d') : value
}

const formatDateTime = (value: string) => {
    const date = DateTime.fromISO(value)
    return date.isValid ? date.toFormat('yyyy/M/d HH:mm') : value
}

const fetchApplications = async (page = 1) => {
    const response = await api.get('/employee_change_applications', {
        page,
        status: filters.status,
        type: filters.type,
    }, {
        loadingRef: loading,
    })

    if (!response) return
    applications.value = response
    applications.value.data.forEach((application) => {
        reviewComments[application.id] = application.review_comment ?? ''
    })
}

const openApplication = (application: EmployeeChangeApplication) => {
    detailApplication.value = application
    reviewComments[application.id] = application.review_comment ?? ''
    router.push({
        name: 'employee-change-application-detail',
        params: { applicationId: application.id },
    })
}

const closeDetail = () => {
    detailApplication.value = null
    router.push({ name: 'employee-change-applications' })
}

const fetchApplicationDetail = async (applicationId: string | number | undefined) => {
    if (!applicationId) {
        detailApplication.value = null
        return
    }

    const cached = applications.value.data.find(application => String(application.id) === String(applicationId))
    if (cached) {
        detailApplication.value = cached
        reviewComments[cached.id] = cached.review_comment ?? ''
    }

    const response = await api.get(`/employee_change_applications/${applicationId}`, null, {
        loadingRef: detailLoading,
    })

    if (!response) return
    detailApplication.value = response
    reviewComments[response.id] = response.review_comment ?? ''
}

const updateStatus = async (application: EmployeeChangeApplication, status: ApplicationStatus) => {
    actionId.value = application.id
    try {
        const response = await api.patch(`/employee_change_applications/${application.id}/review`, {
            status,
            review_comment: reviewComments[application.id] ?? '',
        }, {
            ask: `${typeLabel(application.type)}を${statusLabel(status)}にしますか？`,
            toast: '届出の状態を更新しました。',
        })

        if (!response) return
        detailApplication.value = response
        reviewComments[response.id] = response.review_comment ?? ''
        await fetchApplications(applications.value.current_page)
    } finally {
        actionId.value = null
    }
}

const detailItems = (application: EmployeeChangeApplication) => {
    if (application.profile_detail) {
        const detail = application.profile_detail
        return [
            { label: '変更種別', value: application.type_label || typeLabel(application.type) },
            { label: '適用日', value: formatDate(detail.effective_date) },
            { label: '事由', value: detail.reason },
            { label: '氏名', value: [detail.last_name, detail.first_name].filter(Boolean).join(' ') },
            { label: '氏名カナ', value: [detail.last_name_kana, detail.first_name_kana].filter(Boolean).join(' ') },
            { label: '住所', value: detail.address },
            { label: '扶養操作', value: detail.dependent_action === 'add' ? '追加' : detail.dependent_action === 'remove' ? '削除' : '' },
            { label: '続柄', value: detail.relationship },
            { label: '年収', value: detail.annual_income },
            { label: '扶養対象者', value: detail.dependent_name },
            { label: '扶養対象者カナ', value: detail.dependent_name_kana },
            { label: '生年月日', value: formatDate(detail.birth_date) },
            { label: '性別', value: detail.gender },
            { label: '扶養対象者住所', value: detail.dependent_address },
            { label: '退職日', value: formatDate(detail.retired_on) },
            { label: '就職日', value: formatDate(detail.employment_on) },
            { label: '勤務地', value: detail.work_location },
            { label: '経路', value: detail.route },
            { label: '定期金額', value: detail.monthly_pass_amount },
            { label: '片道距離', value: detail.one_way_distance },
        ].filter(item => item.value && item.value !== '-')
    }

    if (application.leave_detail) {
        const detail = application.leave_detail
        return [
            { label: '休職種別', value: detail.leave_type === 'illness' ? '傷病' : '出産・育児' },
            { label: '傷病名', value: detail.illness_name },
            { label: '開始日', value: formatDate(detail.start_date) },
            { label: '終了日', value: formatDate(detail.end_date) },
            { label: '出産予定日', value: formatDate(detail.expected_birth_date) },
            { label: '産休開始日', value: formatDate(detail.maternity_leave_start) },
            { label: '産休終了日', value: formatDate(detail.maternity_leave_end) },
            { label: '育休開始日', value: formatDate(detail.childcare_leave_start) },
            { label: '育休終了日', value: formatDate(detail.childcare_leave_end) },
        ].filter(item => item.value && item.value !== '-')
    }

    if (application.commute_detail) {
        const detail = application.commute_detail
        const commuteTypeLabels: Record<string, string> = {
            public_transportation: '公共交通機関',
            car: '自家用車',
            bicycle: '自転車通勤',
            walking: '徒歩',
        }

        return [
            { label: '通勤方法', value: commuteTypeLabels[detail.commute_type] ?? detail.commute_type },
            { label: '適用日', value: formatDate(detail.effective_date) },
            { label: '経路', value: detail.route },
            { label: '定期金額', value: detail.pass_amount },
            { label: 'その他交通費', value: detail.other_amount },
            { label: '駐輪場代', value: detail.parking_amount },
            { label: '片道距離', value: detail.one_way_distance },
            { label: '車種', value: detail.car_type },
        ].filter(item => item.value && item.value !== '-')
    }

    return []
}

onMounted(() => {
    if (auth.isAdmin) {
        fetchApplications()
        fetchApplicationDetail(route.params.applicationId as string | undefined)
    }
})

watch(() => route.params.applicationId, (applicationId) => {
    if (auth.isAdmin) {
        fetchApplicationDetail(applicationId as string | undefined)
    }
})
</script>

<style scoped>
.employee-change-admin {
    color: var(--primary-color);
}

.employee-change-denied {
    align-items: center;
    color: var(--primary-color);
    display: flex;
    height: 100%;
    justify-content: center;
    width: 100%;
}

.employee-change-toolbar {
    align-items: center;
    display: flex;
    gap: 16px;
    justify-content: space-between;
    padding: 20px;
}

.employee-change-toolbar h2 {
    font-size: 18px;
    font-weight: 700;
}

.employee-change-toolbar p {
    color: gray;
    font-size: 12px;
    margin-top: 4px;
}

.employee-change-filters {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.employee-change-filters select {
    background: var(--background-color);
    border: 1px solid var(--formBorder);
    color: var(--primary-color);
    height: 34px;
    padding: 0 10px;
}

.employee-change-button,
.employee-change-modal-actions button,
.employee-change-pagination button {
    background: #000;
    color: #fff;
    cursor: pointer;
    font-size: 12px;
    min-height: 32px;
    padding: 0 12px;
}

.employee-change-modal-actions button:disabled,
.employee-change-pagination button:disabled {
    cursor: not-allowed;
    opacity: 0.35;
}

.employee-change-table-wrap {
    flex: 1;
    margin: 0 20px 20px;
    overflow: auto;
    position: relative;
}

.employee-change-table {
    border-collapse: collapse;
    width: 100%;
    box-sizing: border-box !important;
}

.employee-change-table th,
.employee-change-table td {
    border: 1px solid var(--formBorder);
    font-size: 12px;
    padding: 10px;
    text-align: left;
    vertical-align: top;
    box-sizing: border-box !important;
}

.employee-change-table th {
    background: var(--bg2);
    position: sticky;
    top: 0;
    z-index: 1;
}

.employee-change-table tr.active td {
    background: var(--bg2);
}

.employee-change-clickable-row {
    cursor: pointer;
}

.employee-change-clickable-row:hover td {
    background: var(--bg2);
}

.employee-change-status {
    display: inline-flex;
    font-size: 11px;
    font-weight: 700;
    padding: 3px 8px;
}

.employee-change-status.submitted {
    background: #fff3cd;
    color: #7a5200;
}

.employee-change-status.reviewing {
    background: #dbeafe;
    color: #1d4ed8;
}

.employee-change-status.confirmed {
    background: #dcfce7;
    color: #166534;
}

.employee-change-status.denied {
    background: #fee2e2;
    color: #991b1b;
}

.employee-change-modal-title {
    align-items: center;
    display: flex;
    gap: 10px;
}

.employee-change-modal-title strong {
    font-size: 15px;
}

.employee-change-modal-title span {
    font-size: 12px;
}

.employee-change-modal-meta {
    display: grid;
    gap: 10px;
    grid-template-columns: repeat(3, minmax(0, 1fr));
}

.employee-change-modal-meta div {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.employee-change-modal-meta span {
    color: gray;
    font-size: 11px;
}

.employee-change-modal-meta strong {
    font-size: 13px;
    font-weight: 700;
}

.employee-change-detail {
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.employee-change-detail-grid {
    display: grid;
    gap: 10px;
    grid-template-columns: repeat(3, minmax(0, 1fr));
}

.employee-change-detail-item,
.employee-change-files {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.employee-change-detail-item span,
.employee-change-files span {
    color: gray;
    font-size: 11px;
}

.employee-change-detail-item strong,
.employee-change-files strong {
    font-size: 13px;
    font-weight: 700;
    line-height: 1.5;
    white-space: pre-wrap;
}

.employee-change-review-comment {
    background: var(--background-color);
    border: 1px solid var(--formBorder);
    color: var(--primary-color);
    min-height: 70px;
    padding: 10px;
    resize: vertical;
    width: 100%;
}

.employee-change-modal-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    justify-content: flex-end;
}

.employee-change-empty {
    color: gray;
    text-align: center !important;
}

.employee-change-pagination {
    align-items: center;
    display: flex;
    gap: 12px;
    justify-content: center;
    padding: 0 20px 20px;
}

@media screen and (max-width: 959px) {
    .employee-change-toolbar {
        align-items: flex-start;
        flex-direction: column;
    }

    .employee-change-detail-grid {
        grid-template-columns: 1fr;
    }

    .employee-change-modal-meta {
        grid-template-columns: 1fr;
    }
}
</style>
