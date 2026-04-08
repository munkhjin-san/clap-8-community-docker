<template>
    <div class="overlay" @mousedown="closeModal">
        <div class="chatCreate scrollable" @mousedown.stop>
            <div class="recordFormTitle" style="display:flex">
                <p>リフレッシュ履歴</p>
                <div class="m-close-button" @click="closeModal" style="position:unset; margin:auto 0 auto auto">
                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" class="modalWindowCloseButton" viewBox="0 0 32 32">
                        <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                    </svg>
                </div>
            </div>

            <div class="refresh-history-user">{{ summary.user_name }}</div>

            <div class="refresh-history-summary">
                <div class="refresh-summary-card">
                    <span>現在保有額</span>
                    <strong>{{ formatCurrency(summary.current_balance) }}</strong>
                </div>
                <div class="refresh-summary-card">
                    <span>累計付与</span>
                    <strong>{{ formatCurrency(summary.total_granted) }}</strong>
                </div>
                <div class="refresh-summary-card">
                    <span>累計利用</span>
                    <strong>{{ formatCurrency(summary.total_used) }}</strong>
                </div>
                <div class="refresh-summary-card">
                    <span>累計失効</span>
                    <strong>{{ formatCurrency(summary.total_expired) }}</strong>
                </div>
            </div>

            <div class="refresh-history-table-wrap">
                <div v-if="loading" class="refresh-history-empty">確認中...</div>
                <div v-else-if="!entries.length" class="refresh-history-empty">履歴はありません。</div>
                <table v-else class="refresh-history-table">
                    <thead>
                        <tr>
                            <th>日付</th>
                            <th>種別</th>
                            <th>内容</th>
                            <th>金額</th>
                            <th>残高</th>
                            <th>期限</th>
                            <th>メモ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="entry in entries" :key="entry.id">
                            <td>{{ entry.date }}</td>
                            <td>
                                <span :class="['refresh-entry-chip', `is-${entry.type}`]">{{ typeLabel(entry.type) }}</span>
                            </td>
                            <td>{{ entry.title }}</td>
                            <td>{{ formatSignedAmount(entry.type, entry.amount) }}</td>
                            <td>{{ formatOptionalCurrency(entry.balance_after) }}</td>
                            <td>{{ entry.expires_at }}</td>
                            <td>{{ entry.note || '-' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useApi } from '@/composables/api'

const props = defineProps({
    userId: {
        type: Number,
        required: true
    }
})

const emit = defineEmits(['close'])
const api = useApi()
const loading = ref(true)
const summary = ref({
    user_id: props.userId,
    user_name: '',
    current_balance: 0,
    total_granted: 0,
    total_used: 0,
    total_expired: 0
})
const entries = ref([])

const loadHistory = async() => {
    const data = await api.get(`/refresh/users/${props.userId}/history`, null, {
        loadingRef: loading
    })

    if (!data) return

    summary.value = data.summary
    entries.value = data.entries ?? []
}

const closeModal = () => {
    emit('close')
}

const formatCurrency = (value) => {
    return `${Number(value || 0).toLocaleString()}円`
}

const formatOptionalCurrency = (value) => {
    if (value === null || value === undefined) {
        return '-'
    }

    return formatCurrency(value)
}

const formatSignedAmount = (type, value) => {
    const amount = Number(value || 0).toLocaleString()

    if (type === 'grant') {
        return `+${amount}円`
    }

    return `-${amount}円`
}

const typeLabel = (type) => {
    if (type === 'grant') return '付与'
    if (type === 'use') return '利用'
    return '失効'
}

onMounted(() => {
    loadHistory()
})
</script>

<style scoped>
.refresh-history-user {
    margin-bottom: 14px;
    font-size: 13px;
    color: var(--sub-color);
}

.refresh-history-summary {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
    margin-bottom: 16px;
}

.refresh-summary-card {
    display: flex;
    flex-direction: column;
    gap: 4px;
    padding: 10px 12px;
    border-radius: 10px;
    background: var(--bg3);
    color: var(--primary-color);
    font-size: 12px;
}

.refresh-summary-card strong {
    font-size: 16px;
    font-weight: 700;
}

.refresh-history-table-wrap {
    overflow: auto;
}

.refresh-history-table {
    width: 100%;
    border-collapse: collapse;
    color: var(--primary-color);
    font-size: 12px;
}

.refresh-history-table th,
.refresh-history-table td {
    padding: 10px 8px;
    text-align: left;
    border-bottom: 1px solid var(--calendarBorder);
    white-space: nowrap;
}

.refresh-history-table th:last-child,
.refresh-history-table td:last-child {
    min-width: 150px;
    white-space: normal;
}

.refresh-entry-chip {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 48px;
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 700;
}

.refresh-entry-chip.is-grant {
    background: rgba(55, 121, 104, 0.12);
    color: #256856;
}

.refresh-entry-chip.is-use {
    background: rgba(106, 127, 173, 0.18);
    color: #7e98cf;
}

.refresh-entry-chip.is-expire {
    background: rgba(184, 74, 74, 0.12);
    color: #b34c4c;
}

.refresh-history-empty {
    padding: 24px 8px;
    text-align: center;
    color: var(--sub-color);
    font-size: 13px;
}

@media (max-width: 768px) {
    .refresh-history-summary {
        grid-template-columns: 1fr;
    }
}
</style>
