<template>
    <div class="planned-leave-approval">

        <div v-if="loading" class="approval-state">
            <div class="spinner-mini"></div>
        </div>

        <div v-else-if="!requests.length" class="approval-state text-[gray]">
            対応が必要な申請はありません。
        </div>

        <div v-else class="approval-table-wrap">
            <table class="approval-table">
                <thead>
                    <tr>
                        <th>申請者</th>
                        <th>プロジェクト</th>
                        <th>変更日</th>
                        <th>理由</th>
                        <th>PM承認</th>
                        <th>状態</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="request in requests" :key="request.id">
                        <td data-label="申請者">
                            <div class="request-user truncate">
                                <UserPanel v-if="request.user" :user="request.user" size="25" with-name disable-instant />
                                <span v-else>-</span>
                            </div>
                            <div class="request-created">{{ formatDateTime(request.created_at) }}</div>
                        </td>
                        <td data-label="プロジェクト">
                            <div class="project-name">{{ request.project_record?.name ?? '-' }}</div>
                        </td>
                        <td data-label="変更日">
                            <div class="date-change">
                                <span>{{ formatDate(request.original_date) }}</span>
                                <span class="date-arrow">→</span>
                                <strong>{{ formatDate(request.requested_date) }}</strong>
                            </div>
                        </td>
                        <td data-label="理由" class="reason-cell">
                            <button
                                type="button"
                                class="reason-trigger"
                                @click.stop="toggleReason(request.id)"
                            >
                                <span class="reason-text">{{ request.reason || '未入力' }}</span>
                            </button>
                            <div v-if="openedReasonId === request.id" class="reason-popover" @click.stop>
                                <button type="button" class="reason-popover-close" @click="openedReasonId = null">×</button>
                                <div>{{ request.reason || '未入力' }}</div>
                            </div>
                        </td>
                        <td data-label="PM承認">
                            <div class="pm-approval">
                                <span v-if="request.pm_id" class="pm-chip is-done">{{ request.pm_approver?.name ?? request.pmApprover?.name ?? '承認済み' }}</span>
                                <span v-else-if="request.pm_approval_required" class="pm-chip is-waiting">未承認</span>
                                <span v-else class="pm-chip">不要</span>
                                <small v-if="request.pm_id">{{ formatDateTime(request.pm_approval_date) }}</small>
                            </div>
                        </td>
                        <td data-label="状態">
                            <span :class="['approval-status', `is-${request.status}`]">{{ request.status_label }}</span>
                        </td>
                        <td data-label="操作">
                            <div v-if="canRespond(request)" class="approval-actions">
                                <button
                                    type="button"
                                    class="approval-button is-approve"
                                    :disabled="isProcessing(request)"
                                    @click="respond(request, 'approve')"
                                >
                                    {{ approveLabel }}
                                </button>
                                <button
                                    type="button"
                                    class="approval-button is-reject"
                                    :disabled="isProcessing(request)"
                                    @click="respond(request, 'reject')"
                                >
                                    却下
                                </button>
                            </div>
                            <span v-else class="action-empty">-</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
<script lang="ts" setup>
import UserPanel from '@/components/Global/UserPanel.vue';
import { useApi } from '@/composables/api';
import { useDialog } from '@/composables/dialog';
import type { PlannedLeaveChangeRequest } from '@/interface/workInterface';
import { useAuthUserStore } from '@/store/auth';
import { useDashboardStore } from '@/store/dashboard';
import { DateTime } from 'luxon';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

defineProps<{
    userId?: number | null
}>()

type ApprovalAction = 'approve' | 'reject'

const api = useApi()
const auth = useAuthUserStore()
const dashboardStore = useDashboardStore()
const { ask } = useDialog()
const loading = ref(false)
const processingIds = ref<number[]>([])
const requests = ref<PlannedLeaveChangeRequest[]>([])
const openedReasonId = ref<number | null>(null)

const roleLabel = computed(() => auth.isAdmin ? '管理者: すべての未処理申請' : 'PM: 担当プロジェクトのPM承認待ち申請')
const approveLabel = computed(() => auth.isAdmin ? '承認' : 'PM承認')

const loadRequests = async () => {
    loading.value = true
    try {
        requests.value = await api.get('/planned_leave_change_requests') ?? []
    } finally {
        loading.value = false
    }
}

const isProcessing = (request: PlannedLeaveChangeRequest) => processingIds.value.includes(request.id)
const toggleReason = (requestId: number) => {
    openedReasonId.value = openedReasonId.value === requestId ? null : requestId
}
const closeReason = () => {
    openedReasonId.value = null
}
const canRespond = (request: PlannedLeaveChangeRequest) => {
    if (request.status !== 'pending') return false
    if (auth.isAdmin) return true
    return request.pm_approval_required && !request.pm_id
}

const respond = async (request: PlannedLeaveChangeRequest, action: ApprovalAction) => {
    if (!canRespond(request)) return

    const actionLabel = action === 'approve' ? approveLabel.value : '却下'
    const confirmed = await ask(`${actionLabel}しますか？`)
    if (!confirmed.value) return

    processingIds.value.push(request.id)
    try {
        const response = await api.patch('/planned_leave_change_request/respond', {
            id: request.id,
            action,
        }, {
            toast: action === 'approve' ? `${actionLabel}しました。` : '却下しました。',
        })

        if (response) {
            await loadRequests()
            await dashboardStore.getBatchDashboardData(['timesheet'])
        }
    } finally {
        processingIds.value = processingIds.value.filter(id => id !== request.id)
    }
}

const formatDate = (value: string | Date) => {
    const parsed = DateTime.fromISO(value.toString())
    return parsed.isValid ? parsed.toFormat('yyyy/M/d') : value.toString()
}

const formatDateTime = (value?: string | Date | null) => {
    if (!value) return '-'
    const parsed = DateTime.fromISO(value.toString())
    return parsed.isValid ? parsed.toFormat('yyyy/M/d HH:mm') : value.toString()
}

onMounted(() => {
    loadRequests()
    document.addEventListener('click', closeReason)
})

onBeforeUnmount(() => {
    document.removeEventListener('click', closeReason)
})
</script>
<style scoped>
.planned-leave-approval{
    border-top: 1px solid var(--calendarBorder);
    flex: 1 1 auto;
    display: flex;
}
.approval-header,
.approval-actions {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}
.approval-title {
    color: var(--primary-color);
    font-size: 14px;
    font-weight: 700;
}
.approval-subtitle,
.approval-meta,
.approval-reason span,
.date-flow span {
    color: gray;
    font-size: 12px;
}
.approval-state {
    min-height: 120px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
}
.approval-table-wrap {
    margin-top: 16px;
    overflow-x: auto;
    border: 1px solid var(--calendarBorder);
    width: 100%;
    
}
.approval-table {
    width: 100%;
    min-width: 860px;
    border-collapse: collapse;
    font-size: 12px;
}
.approval-table thead {
    background: var(--bg3);
}
.approval-table th {
    padding: 10px 12px;
    border-bottom: 1px solid var(--calendarBorder);
    color: gray;
    font-weight: 600;
    text-align: left;
    white-space: nowrap;
}
.approval-table td {
    padding: 12px;
    border-bottom: 1px solid var(--calendarBorder);
    vertical-align: middle;
}
.approval-table tbody tr:last-child td {
    border-bottom: 0;
}

.request-user {
    min-width: 150px;
}
.request-created {
    margin-top: 5px;
    color: gray;
    font-size: 11px;
}
.project-name {
    max-width: 180px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.date-change {
    display: flex;
    align-items: center;
    gap: 7px;
    white-space: nowrap;
}
.date-change strong {
    color: var(--primary-color);
}
.date-arrow {
    color: gray;
}
.reason-cell {
    position: relative;
}
.reason-trigger {
    display: block;
    width: 100%;
    max-width: 240px;
    padding: 0;
    border: 0;
    background: transparent;
    text-align: left;
    cursor: pointer;
}
.reason-text {
    max-width: 240px;
    display: -webkit-box;
    overflow: hidden;
    color: var(--font2);
    line-height: 1.55;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    -webkit-box-orient: vertical;
}
.reason-popover {
    position: absolute;
    z-index: 20;
    top: 4px;
    right: 8px;
    width: min(360px, 70vw);
    max-height: 220px;
    overflow-y: auto;
    padding: 10px 12px;
    border: 1px solid var(--calendarBorder);
    background: var(--background-color);
    box-shadow: 0 10px 24px rgba(0, 0, 0, 0.14);
    color: var(--font2);
    font-size: 12px;
    line-height: 1.7;
    white-space: pre-wrap;
}
.reason-popover-close {
    position: absolute;
    top: 4px;
    right: 6px;
    width: 22px;
    height: 22px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0;
    border-radius: 999px;
    background: transparent;
    color: gray;
    font-size: 16px;
    line-height: 1;
}
.reason-popover-close:hover {
    background: var(--bg3);
    color: var(--primary-color);
}
.pm-approval {
    display: grid;
    gap: 4px;
}
.pm-approval small {
    color: gray;
    font-size: 11px;
}
.pm-chip {
    width: fit-content;
    padding: 3px 7px;
    border-radius: 999px;
    background: var(--bg3);
    color: gray;
    font-size: 11px;
    white-space: nowrap;
}
.pm-chip.is-done {
    color: #15803d;
}
.pm-chip.is-waiting {
    color: #b45309;
}
.approval-status {
    padding: 4px 8px;
    border-radius: 999px;
    background: var(--bg3);
    color: gray;
    font-size: 11px;
    white-space: nowrap;
}
.approval-status.is-pending {
    color: #b45309;
}
.approval-status.is-approved {
    color: #15803d;
}
.approval-status.is-rejected {
    color: #b91c1c;
}
.approval-actions {
    justify-content: flex-start;
    gap: 6px;
    white-space: nowrap;
}
.approval-button {
    padding: 5px 9px;
    border-radius: 4px;
    font-size: 12px;
}
.approval-button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
.approval-button.is-approve {
    background: var(--primary-color);
    color: var(--background-color);
}
.approval-button.is-reject {
    background: var(--background-color);
    color: tomato;
}
.action-empty {
    color: gray;
}
@media (max-width: 760px) {
    .approval-table-wrap {
        overflow: visible;
        border: 0;
        background: transparent;
    }
    .approval-table,
    .approval-table thead,
    .approval-table tbody,
    .approval-table tr,
    .approval-table td {
        display: block;
        width: 100%;
        min-width: 0;
    }
    .approval-table thead {
        display: none;
    }
    .approval-table tr {
        margin-bottom: 10px;
        border: 1px solid var(--calendarBorder);
        border-radius: 6px;
        background: var(--bg3);
        overflow: hidden;
    }
    .approval-table td {
        display: grid;
        grid-template-columns: 86px minmax(0, 1fr);
        gap: 10px;
        padding: 10px 12px;
    }
    .approval-table td::before {
        content: attr(data-label);
        color: gray;
        font-size: 11px;
        line-height: 1.7;
    }
    .approval-table tbody tr:hover {
        background: var(--bg3);
    }
    .project-name,
    .reason-text {
        max-width: none;
    }
    .reason-trigger {
        max-width: none;
    }
    .reason-popover {
        top: 8px;
        right: 12px;
        width: min(320px, calc(100vw - 56px));
    }
    .approval-actions {
        flex-wrap: wrap;
    }
}
</style>
