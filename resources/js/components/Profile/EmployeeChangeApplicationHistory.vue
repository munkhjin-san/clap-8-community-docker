<template>
    <Modal size="large" :loader="loading" @close="emit('close')">
        <template #title>
            <div class="employee-change-history-title">
                <strong>各種届出履歴</strong>
                <span>{{ applications.length }}件</span>
            </div>
        </template>
        <template #content>
            <div class="employee-change-history">
                <div v-if="!loading && !applications.length" class="employee-change-history-empty">
                    届出履歴はありません。
                </div>

                <div v-else class="employee-change-history-table-wrap">
                    <table class="employee-change-history-table">
                        <thead>
                            <tr>
                                <th>申請日</th>
                                <th>種類</th>
                                <th>適用日</th>
                                <th>ステータス</th>
                                <th>確認者</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template v-for="application in applications" :key="application.id">
                                <tr class="employee-change-history-row" @click="toggleDetail(application.id)">
                                    <td>{{ formatDateTime(application.created_at) }}</td>
                                    <td>{{ application.type_label || typeLabel(application.type) }}</td>
                                    <td>{{ formatDate(application.effective_date) }}</td>
                                    <td>
                                        <span :class="['employee-change-history-status', application.status]">
                                            {{ application.status_label || statusLabel(application.status) }}
                                        </span>
                                    </td>
                                    <td>{{ application.reviewed_by?.name ?? '-' }}</td>
                                </tr>
                                <tr v-if="openApplicationId === application.id" class="employee-change-history-detail-row">
                                    <td colspan="5">
                                        <div class="employee-change-history-detail">
                                            <div
                                                v-for="item in detailItems(application)"
                                                :key="item.label"
                                                class="employee-change-history-detail-item"
                                            >
                                                <span>{{ item.label }}</span>
                                                <strong>{{ item.value }}</strong>
                                            </div>
                                            <div v-if="application.files?.length" class="employee-change-history-detail-item">
                                                <span>添付</span>
                                                <Files :items="application.files" :path="`/various_changes/${application.type}`" />
                                            </div>
                                            <div v-if="application.review_comment" class="employee-change-history-comment">
                                                <span>レビューコメント</span>
                                                <p>{{ application.review_comment }}</p>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </template>
    </Modal>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { DateTime } from 'luxon';
import { useApi } from '@/composables/api';
import Modal from '@/components/Global/Modal.vue';
import {
    applicationStatusLabel,
    applicationTypeLabel,
    type ApplicationStatus,
    type EmployeeChangeApplication,
} from '@/interface/employeeInterface';
import Files from '../Global/Files.vue';

const emit = defineEmits(['close'])
const api = useApi()
const loading = ref(false)
const applications = ref<EmployeeChangeApplication[]>([])
const openApplicationId = ref<number | null>(null)

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

const loadApplications = async () => {
    const response = await api.get('/my_employee_change_applications', { limit: 50 }, {
        loadingRef: loading,
    })

    if (!response) return
    applications.value = response
}

const toggleDetail = (applicationId: number) => {
    openApplicationId.value = openApplicationId.value === applicationId ? null : applicationId
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
    loadApplications()
})
</script>

<style scoped>
.employee-change-history-title {
    align-items: center;
    display: flex;
    gap: 10px;
}

.employee-change-history-title strong {
    font-size: 15px;
}

.employee-change-history-title span {
    color: gray;
    font-size: 12px;
}

.employee-change-history {
    color: var(--primary-color);
}

.employee-change-history-table-wrap {
    overflow: auto;
}

.employee-change-history-table {
    border-collapse: collapse;
    width: 100%;
    box-sizing: border-box !important;
}

.employee-change-history-table th,
.employee-change-history-table td {
    border: 1px solid var(--formBorder);
    box-sizing: border-box !important;
    font-size: 12px;
    padding: 10px;
    text-align: left;
    vertical-align: top;
}

.employee-change-history-table th {
    background: var(--bg2);
    position: sticky;
    top: 0;
    z-index: 1;
}

.employee-change-history-row {
    cursor: pointer;
}

.employee-change-history-row:hover td {
    background: var(--bg2);
}

.employee-change-history-status {
    display: inline-flex;
    font-size: 11px;
    font-weight: 700;
    padding: 3px 8px;
}

.employee-change-history-status.submitted {
    background: #fff3cd;
    color: #7a5200;
}

.employee-change-history-status.reviewing {
    background: #dbeafe;
    color: #1d4ed8;
}

.employee-change-history-status.confirmed {
    background: #dcfce7;
    color: #166534;
}

.employee-change-history-status.denied {
    background: #fee2e2;
    color: #991b1b;
}

.employee-change-history-detail-row td {
    background: var(--bg2);
}

.employee-change-history-detail {
    display: grid;
    gap: 10px;
    grid-template-columns: repeat(3, minmax(0, 1fr));
}

.employee-change-history-detail-item,
.employee-change-history-comment {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.employee-change-history-detail-item span,
.employee-change-history-comment span {
    color: gray;
    font-size: 11px;
}

.employee-change-history-detail-item strong,
.employee-change-history-comment p {
    font-size: 13px;
    font-weight: 700;
    line-height: 1.5;
    white-space: pre-wrap;
}

.employee-change-history-comment {
    grid-column: 1 / -1;
}

.employee-change-history-empty {
    align-items: center;
    color: gray;
    display: flex;
    font-size: 13px;
    justify-content: center;
    min-height: 180px;
}

@media screen and (max-width: 959px) {
    .employee-change-history-detail {
        grid-template-columns: 1fr;
    }

    .employee-change-history-table th,
    .employee-change-history-table td {
        white-space: nowrap;
    }
}
</style>
