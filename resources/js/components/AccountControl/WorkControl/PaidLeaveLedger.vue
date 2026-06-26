<template>
    <div class="leave-ledger">
        <Transition name="modalFade">
            <div v-if="loading" class="control-loader">
                <div class="spinner-mini" style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
            </div>
        </Transition>

        <header class="ledger-header">
            <div>
                <h1>有休台帳</h1>
                <p>付与、使用、失効、手動調整の履歴</p>
            </div>
            <div class="ledger-actions">
                <input v-model.trim="search" type="search" placeholder="名前・社員コード" />
                <button type="button" class="ledger-button" :disabled="loading" @click="loadUsers">更新</button>
            </div>
        </header>

        <div class="ledger-body">
            
            <section class="ledger-table-panel">
                <Transition name="modalFade">
                    <div v-if="tableLoading" class="table-loader">
                        <div class="spinner-mini" style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
                    </div>
                </Transition>
                <div class="ledger-table-scroll">
                    <table class="ledger-table">
                        <thead>
                            <tr>
                                <th>名前</th>
                                <th>社員コード</th>
                                <th>入社日</th>
                                <th>当年度付与日</th>
                                <th>残日数</th>
                                <th>付与</th>
                                <th>使用</th>
                                <th>調整</th>
                                <th>状態</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="user in users"
                                :key="user.account_id"
                                :class="{ selected: selectedAccountId === user.account_id, muted: !user.authoritative }"
                                @click="selectUser(user)"
                            >
                                <td>{{ user.name }}</td>
                                <td>{{ user.user_code || '' }}</td>
                                <td>{{ user.joined_date || '' }}</td>
                                <td :title="grantDateTitle(user)">
                                    {{ formatGrantDate(user) }}
                                </td>
                                <td>{{ formatBalance(user.balance_minutes, user.minutes_per_day) }}</td>
                                <td>{{ user.grant_count }}</td>
                                <td>{{ user.usage_count }}</td>
                                <td>{{ user.adjustment_count }}</td>
                                <td>{{ user.authoritative ? 'Glowd' : '未移行' }}</td>
                            </tr>
                            <tr v-if="users.length === 0">
                                <td colspan="9" class="state-cell">対象者がありません。</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <aside class="ledger-detail">
                <Transition name="modalFade">
                    <div v-if="detailLoading" class="detail-loader">
                        <div class="spinner-mini" style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
                    </div>
                </Transition>
                <div v-if="!detail" class="empty-detail">
                    <h2>メンバーを選択</h2>
                    <p>残数、付与、使用、調整履歴を確認できます。</p>
                </div>
                <template v-else>
                    <div class="detail-head">
                        <div>
                            <h2>{{ detail.account.name }}</h2>
                            <p>{{ detail.account.user_code || '' }}</p>
                        </div>
                        <strong>{{ formatBalance(detail.balance.minutes) }}</strong>
                    </div>

                    <section class="detail-section">
                        <h3>手動調整</h3>
                        <div class="adjust-form">
                            <input v-model="adjustForm.adjusted_on" type="date" :class="{'date-color': theme.dark}" />
                            <input v-model.number="adjustForm.amount_days" type="number" step="0.5" placeholder="+1 / -1" />
                            <button type="button" class="ledger-button primary" :disabled="savingAdjustment || !adjustForm.amount_days" @click="saveAdjustment">
                                保存
                            </button>
                        </div>
                    </section>

                    <section class="detail-section">
                        <h3>付与別残数</h3>
                        <div class="grant-list">
                            <div v-for="grant in detail.grants" :key="grant.id" class="grant-row">
                                <div>
                                    <strong>{{ grant.granted_at }} / {{ formatDays(grant.grant_days) }}日</strong>
                                    <small>残 {{ formatBalance(grant.remaining_minutes) }} / 期限 {{ grant.expires_at || 'なし' }}</small>
                                </div>
                                <span>{{ grant.source_system }}</span>
                            </div>
                            <div v-if="detail.grants.length === 0" class="state-cell">付与履歴がありません。</div>
                        </div>
                    </section>

                    <section class="detail-section">
                        <h3>履歴</h3>
                        <table class="ledger-table compact">
                            <thead>
                                <tr>
                                    <th>日付</th>
                                    <th>付与年度</th>
                                    <th>種別</th>
                                    <th>日数/時間</th>
                                    <th>登録者</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(event, index) in detail.events" :key="`${event.type}-${event.date}-${index}`">
                                    <td>{{ event.date }}</td>
                                    <td :title="allocationTitle(event)">{{ event.grant_year || '' }}</td>
                                    <td>{{ event.label }}</td>
                                    <td :class="{ negative: Number(event.amount_minutes) < 0, positive: Number(event.amount_minutes) > 0 }">
                                        {{ signedLeaveAmount(event) }}
                                    </td>
                                    <td>{{ event.actor?.name || '' }}</td>
                                </tr>
                                <tr v-if="detail.events.length === 0">
                                    <td colspan="5" class="state-cell">履歴がありません。</td>
                                </tr>
                            </tbody>
                        </table>
                    </section>
                </template>
            </aside>
        </div>
    </div>
</template>

<script setup>
import { useApi } from '@/composables/api';
import { useDebouncedRef } from '@/utils/tools';
import { DateTime } from 'luxon';
import { onMounted, reactive, ref, watch } from 'vue';
import { useTheme } from '@/store/theme';

const api = useApi()
const users = ref([])
const detail = ref(null)
const selectedAccountId = ref(null)
const loading = ref(false)
const detailLoading = ref(false)
const tableLoading = ref(false)
const savingAdjustment = ref(false)
const search = useDebouncedRef('', 600)
const adjustForm = reactive({
    adjusted_on: DateTime.now().toISODate(),
    amount_days: '',
})
const theme = useTheme();
onMounted(() => {
    loadUsers('initial')
})

watch(search, () => {
    loadUsers('table')
})

const loadUsers = async(whichLoading) => {
    const response = await api.get('/admin/paid-leave-ledger', { search: search.value || undefined }, { loadingRef: whichLoading === 'table' ? tableLoading : loading })
    users.value = response?.users || []
    if (selectedAccountId.value && !users.value.some(user => user.account_id === selectedAccountId.value)) {
        detail.value = null
        selectedAccountId.value = null
    }
}

const selectUser = async(user) => {
    selectedAccountId.value = user.account_id
    detail.value = await api.get(`/admin/paid-leave-ledger/${user.account_id}`, {}, { loadingRef: detailLoading })
}

const saveAdjustment = async() => {
    if (!selectedAccountId.value) return
    const payload = {
        adjusted_on: adjustForm.adjusted_on,
        amount_days: Number(adjustForm.amount_days),
        note: null,
    }
    const response = await api.post(`/admin/paid-leave-ledger/${selectedAccountId.value}/adjustments`, payload, {
        loadingRef: savingAdjustment,
        toast: '調整しました',
    })
    if (response) {
        detail.value = response
        adjustForm.amount_days = ''
        await loadUsers('table')
    }
}

const formatDays = (value) => new Intl.NumberFormat('ja-JP', { maximumFractionDigits: 2 }).format(Number(value) || 0)
const signedDays = (value) => {
    const number = Number(value) || 0
    return `${number > 0 ? '+' : ''}${formatDays(number)}`
}
const ledgerMinutesPerDay = () => Math.max(1, Number(detail.value?.balance?.minutes_per_day) || 480)
const formatBalance = (minutes, minutesPerDay = null) => formatLeaveMinutes(minutes, minutesPerDay)
const formatLeaveMinutes = (minutes, minutesPerDay = null) => {
    const total = Math.abs(Math.round(Number(minutes) || 0))
    const perDay = Math.max(1, Number(minutesPerDay) || ledgerMinutesPerDay())
    const days = Math.floor(total / perDay)
    const rest = total % perDay
    const hours = Math.floor(rest / 60)
    const mins = rest % 60

    let label = `${days}日`
    if (hours > 0) label += `${hours}時間`
    if (mins > 0) label += `${mins}分`

    return label
}
const signedLeaveAmount = (event) => {
    if (event?.type !== 'usage' || event?.usage_type !== 'hourly_shift') {
        return `${signedDays(event?.amount_days)}日`
    }

    const fallbackMinutes = Math.round((Number(event?.amount_days) || 0) * ledgerMinutesPerDay())
    const minutes = Number.isFinite(Number(event?.amount_minutes))
        ? Number(event.amount_minutes)
        : fallbackMinutes
    const sign = minutes > 0 ? '+' : minutes < 0 ? '-' : ''

    return `${sign}${formatLeaveMinutes(minutes)}`
}
const allocationTitle = (event) => {
    if (!Array.isArray(event?.grant_allocations) || event.grant_allocations.length === 0) return ''

    return event.grant_allocations
        .map(allocation => {
            const amount = event?.usage_type === 'hourly_shift'
                ? formatLeaveMinutes(allocation.amount_minutes)
                : `${formatDays(allocation.amount_days)}日`

            return `${allocation.grant_year || ''}: ${amount}`
        })
        .join(' / ')
}
const formatGrantDate = (user) => {
    if (!user?.current_grant_date) return ''

    return user.current_grant_status === 'expected'
        ? `${user.current_grant_date} 予定`
        : user.current_grant_date
}
const grantDateTitle = (user) => {
    if (!user?.current_grant_date) return ''

    const period = user.current_grant_period_end
        ? `${user.current_grant_date} - ${user.current_grant_period_end}`
        : user.current_grant_date
    const days = user.current_grant_days ? ` / ${formatDays(user.current_grant_days)}日` : ''
    const status = user.current_grant_status === 'expected' ? '予定' : '付与済み'

    return `${period}${days} / ${status}`
}
</script>

<style scoped>
.leave-ledger {
    position: relative;
    height: 100%;
    min-height: 0;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    color: var(--primary-color);
    background: var(--bg3);
}

.ledger-header {
    display: flex;
    justify-content: space-between;
    gap: 16px;
    padding: 16px 20px;
    background: var(--background-color);
    margin: 10px 20px 0;
}

.ledger-header h1,
.ledger-detail h2 {
    margin: 0;
    font-size: 18px;
}

.ledger-header p,
.detail-head p,
.empty-detail p {
    margin: 4px 0 0;
    color: var(--third-color);
    font-size: 12px;
}

.ledger-actions {
    display: flex;
    gap: 8px;
    align-items: center;
}

input {
    min-height: 32px;
    border: 1px solid var(--formBorder);
    border-radius: 0;
    background: var(--background-color);
    color: var(--primary-color);
    padding: 5px 8px;
    box-sizing: border-box !important;
}

.ledger-button {
    min-height: 32px;
    border-radius: 0;
    background: var(--primary-button);
    color: #fff;
    cursor: pointer;
    padding: 0 12px;
    font-size: 12px;
}



.ledger-button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.ledger-body {
    flex: 1;
    min-height: 0;
    display: grid;
    grid-template-columns: minmax(420px, 1fr) minmax(360px, 0.9fr);
    gap: 20px;
    padding: 20px;
}

.ledger-table-panel,
.ledger-detail {
    min-height: 0;
    background: var(--background-color);
}

.ledger-table-panel {
    position: relative;
    overflow: hidden;
}

.ledger-table-scroll {
    height: 100%;
    min-height: 0;
    overflow: auto;
}

.ledger-detail {
    position: relative;
    overflow: auto;
    padding: 14px;
}

.table-loader,
.detail-loader {
    position: absolute;
    inset: 0;
    z-index: 2;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--background-color);
}

.table-loader {
    z-index: 4;
}

.ledger-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
    box-sizing: border-box !important;
}

.ledger-table.compact {
    font-size: 12px;
}

.ledger-table th,
.ledger-table td {
    border: 1px solid var(--calendarBorder);
    padding: 8px;
    text-align: left;
    vertical-align: top;
}

.ledger-table th {
    position: sticky;
    top: -15px;
    z-index: 1;
    background: #363636;
    color: #fff;
}

.ledger-table tr {
    cursor: pointer;
}

.ledger-table tr:nth-child(even) {
    background: var(--bg3);
}

.ledger-table tr.selected {
    outline: 2px solid var(--third-color);
    outline-offset: -2px;
}

.ledger-table tr.muted {
    color: var(--third-color);
}

.state-cell,
.empty-detail {
    color: var(--third-color);
    text-align: center;
    padding: 20px;
}

.detail-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    border-bottom: 1px solid var(--formBorder);
    padding-bottom: 12px;
}

.detail-head strong {
    font-size: 22px;
}

.detail-section {
    margin-top: 16px;
}

.detail-section h3 {
    margin: 0 0 8px;
    font-size: 14px;
}

.adjust-form {
    display: grid;
    grid-template-columns: 130px 100px auto;
    gap: 8px;
}

.grant-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.grant-row {
    display: flex;
    justify-content: space-between;
    gap: 10px;
    border: 1px solid var(--formBorder);
    padding: 8px;
}

.grant-row strong,
.grant-row small {
    display: block;
}

.grant-row small {
    margin-top: 10px;
    color: var(--third-color);
}

.negative {
    color: #b42318;
}

.positive {
    color: #137333;
}

@media screen and (max-width: 1100px) {
    .ledger-body {
        grid-template-columns: 1fr;
    }
}
</style>
