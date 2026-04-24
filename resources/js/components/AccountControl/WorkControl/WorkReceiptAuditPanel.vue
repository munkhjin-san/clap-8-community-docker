<template>
    <div class="receipt-audit-panel">
        <div class="receipt-audit-filters">
            <div class="filter-presets">
                <span class="filter-presets-label">クイック:</span>
                <button type="button" class="preset-chip" @click="applyPreset('remanded')">差戻しのみ</button>
                <button type="button" class="preset-chip" @click="applyPreset('ocrApplied')">OCR反映あり</button>
                <button type="button" class="preset-chip" @click="applyPreset('deleted')">削除あり</button>
                <button type="button" class="preset-chip" @click="applyPreset('submitted')">申請のみ</button>
            </div>

            <div class="filter-row">
                <div class="filter-field">
                    <label class="filter-label">社員</label>
                    <select class="audit-input" v-model="filters.user_id">
                        <option value="">全社員</option>
                        <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }}</option>
                    </select>
                </div>
                <div class="filter-field">
                    <label class="filter-label">イベント</label>
                    <select class="audit-input" v-model="filters.event_type">
                        <option value="">全イベント</option>
                        <option v-for="eventOption in eventOptions" :key="eventOption.value" :value="eventOption.value">
                            {{ eventOption.label }}
                        </option>
                    </select>
                </div>
                <div class="filter-field">
                    <label class="filter-label">取引先</label>
                    <input v-model="filters.merchant" @keydown.enter="loadEvents(1)" type="text" placeholder="取引先名" class="audit-input">
                </div>
                <div class="filter-field">
                    <label class="filter-label">申請状態</label>
                    <select class="audit-input" v-model="filters.approval_state">
                        <option value="">全状態</option>
                        <option v-for="state in approvalStateOptions" :key="state.value" :value="state.value">{{ state.label }}</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label class="filter-label">領収書日付</label>
                    <div class="filter-range">
                        <input v-model="filters.receipt_date_from" type="date" :class="['audit-input', { 'date-color': theme.dark }]">
                        <span class="filter-range-sep">〜</span>
                        <input v-model="filters.receipt_date_to" type="date" :class="['audit-input', { 'date-color': theme.dark }]">
                    </div>
                </div>
                <div class="filter-group">
                    <label class="filter-label">金額</label>
                    <div class="filter-range">
                        <input v-model="filters.amount_min" @keydown.enter="loadEvents(1)" type="number" placeholder="下限" class="audit-input">
                        <span class="filter-range-sep">〜</span>
                        <input v-model="filters.amount_max" @keydown.enter="loadEvents(1)" type="number" placeholder="上限" class="audit-input">
                    </div>
                </div>
            </div>

            <div class="filter-actions">
                <div class="filter-action-buttons">
                    <button type="button" class="audit-button" @click="loadEvents(1)">検索</button>
                    <button type="button" class="audit-button audit-button-secondary" :disabled="listLoading || !events.length" @click="downloadExport">CSV出力</button>
                    <button type="button" class="audit-button audit-button-secondary" @click="resetFilters">リセット</button>
                </div>
                <span v-if="pagination.total > 0" class="filter-total-count">{{ pagination.total.toLocaleString() }} 件</span>
            </div>

            <div v-if="activeFilterSummary.length" class="filter-summary">
                <span class="filter-summary-label">適用中:</span>
                <button
                    v-for="item in activeFilterSummary"
                    :key="item.key"
                    type="button"
                    class="filter-summary-chip"
                    @click="clearFilter(item.key)"
                >{{ item.label }} <span class="filter-summary-dismiss">×</span></button>
            </div>
        </div>

        <div class="receipt-audit-layout">
            <div class="receipt-audit-list">
                <template v-if="listLoading">
                    <div v-for="i in 4" :key="i" class="skeleton-item">
                        <div class="skeleton-line skeleton-title"></div>
                        <div class="skeleton-line skeleton-sub"></div>
                        <div class="skeleton-line skeleton-meta"></div>
                    </div>
                </template>
                <div v-else-if="!events.length" class="list-feedback">
                    <span>条件に一致する監査ログはありません。<br><small>フィルターを変更してみてください。</small></span>
                </div>
                <template v-else>
                    <div
                        v-for="event in events"
                        :key="event.id"
                        class="receipt-audit-list-item"
                        :class="{ active: selectedEvent?.id === event.id }"
                        @click="selectEvent(event)"
                    >
                        <div class="receipt-audit-list-top">
                            <div class="receipt-audit-title">{{ eventTypeLabel(event.event_type) }}</div>
                            <span class="event-chip" :class="eventChipClass(event.event_type)">{{ eventChipText(event.event_type) }}</span>
                        </div>
                        <div class="receipt-audit-summary">{{ eventSummary(event) }}</div>
                        <div class="receipt-audit-meta">対象日: {{ formatDate(event.timecard_day) }}</div>
                        <div class="receipt-audit-meta">申請状態: {{ approvalStateLabel(event.approval_state) }}</div>
                        <div v-if="event.internal_control_status" class="receipt-audit-meta">
                            内部統制:
                            <span class="control-status-chip" :class="internalControlStatusClass(event.internal_control_status)">
                                {{ internalControlStatusLabel(event.internal_control_status) }}
                            </span>
                        </div>
                        <div class="receipt-audit-meta">記録時刻: {{ formatDateTime(event.occurred_at) }}</div>
                    </div>
                </template>
                <div v-if="pagination.total > 0" class="list-pagination">
                    <PostSearchPager 
                        style="margin: 0;"
                        :possiblePage="pagination.last_page"
                        :activePath="pagination.page"
                        @setNavi="(index) => changePage(pagination.page + index)"
                        @setActivePage="(index) => changePage(index)"
                    />
                </div>
            </div>

            <div class="receipt-audit-detail" :class="{ 'detail-is-loading': detailLoading && !!detail }">
                <div v-if="detailLoading && !detail" class="receipt-audit-empty">詳細を読み込み中です。</div>
                <template v-if="detail">
                    <div class="detail-header">
                        <div>
                            <div class="detail-title-row">
                                <h3>{{ eventTypeLabel(detail.event_type) }}</h3>
                                <span class="event-chip" :class="eventChipClass(detail.event_type)">{{ eventChipText(detail.event_type) }}</span>
                            </div>
                            <p>{{ formatDateTime(detail.occurred_at) }}</p>
                            <div class="detail-narrative">{{ detailNarrative(detail) }}</div>
                        </div>
                    </div>

                    <div class="detail-grid">
                        <div><strong>対象者:</strong> {{ detail.subject?.name || '-' }}</div>
                        <div><strong>操作者:</strong> {{ detail.actor?.name || '-' }}</div>
                        <div><strong>対象日:</strong> {{ formatDate(detail.timecard?.day || detail.metadata?.timecard_day) }}</div>
                        <div><strong>申請状態:</strong> {{ approvalStateLabel(detail.timecard?.status_flag ?? detail.metadata?.approval_state) }}</div>
                        <div><strong>取引先:</strong> {{ detail.timecard_cost?.merchant_name || detail.after_state?.merchant_name || detail.before_state?.merchant_name || detail.ocr_run?.normalized_result?.merchant_name || '-' }}</div>
                        <div><strong>金額:</strong> {{ amountLabel(detail.timecard_cost?.expenses ?? detail.after_state?.expenses ?? detail.before_state?.expenses ?? detail.ocr_run?.normalized_result?.amount) }}</div>
                        <div v-if="detail.internal_control_status">
                            <strong>内部統制:</strong>
                            <span class="control-status-chip" :class="internalControlStatusClass(detail.internal_control_status)">
                                {{ internalControlStatusLabel(detail.internal_control_status) }}
                            </span>
                        </div>
                    </div>

                    <button v-if="detail.receipt_file_url" type="button" @click="openReceiptFile" class="audit-button">領収書を表示</button>

                    <div v-if="diffRows(detail.before_state, detail.after_state).length" class="detail-section">
                        <h4>変更内容</h4>
                        <table class="detail-table">
                            <thead>
                                <tr>
                                    <th>項目</th>
                                    <th>変更前</th>
                                    <th>変更後</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="row in diffRows(detail.before_state, detail.after_state)" :key="row.key">
                                    <td>{{ row.label }}</td>
                                    <td>{{ row.before }}</td>
                                    <td>{{ row.after }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-else-if="fieldRows(detail.after_state).length" class="detail-section">
                        <h4>記録内容</h4>
                        <table class="detail-table">
                            <tbody>
                                <tr v-for="row in fieldRows(detail.after_state)" :key="row.key">
                                    <td>{{ row.label }}</td>
                                    <td>{{ row.value }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-else-if="fieldRows(detail.before_state).length" class="detail-section">
                        <h4>削除前の内容</h4>
                        <table class="detail-table">
                            <tbody>
                                <tr v-for="row in fieldRows(detail.before_state)" :key="row.key">
                                    <td>{{ row.label }}</td>
                                    <td>{{ row.value }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-if="metadataRows(detail.metadata).length" class="detail-section">
                        <h4>補足情報</h4>
                        <table class="detail-table">
                            <tbody>
                                <tr v-for="row in metadataRows(detail.metadata)" :key="row.key">
                                    <td>{{ row.label }}</td>
                                    <td>{{ row.value }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-if="detail.ocr_run?.normalized_result" class="detail-section">
                        <h4>OCR読取結果</h4>
                        <table class="detail-table">
                            <tbody>
                                <tr v-for="row in fieldRows(detail.ocr_run.normalized_result)" :key="row.key">
                                    <td>{{ row.label }}</td>
                                    <td>{{ row.value }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <details class="detail-section technical-section">
                        <summary>技術情報を表示</summary>
                        <div v-if="detail.ocr_run?.raw_response" class="detail-section-inner">
                            <h4>OCR生データ</h4>
                            <pre>{{ pretty(detail.ocr_run.raw_response) }}</pre>
                        </div>
                        <div class="detail-section-inner">
                            <h4>Raw JSON</h4>
                            <pre>{{ pretty({ before_state: detail.before_state, after_state: detail.after_state, metadata: detail.metadata }) }}</pre>
                        </div>
                    </details>
                </template>
                <div v-if="!detail && !detailLoading" class="receipt-audit-empty">左側から監査ログを選択してください。</div>
            </div>
        </div>
    </div>
</template>
<script setup>
import { reactive, ref, watch } from 'vue'
import { DateTime } from 'luxon'
import { useApi } from '@/composables/api'
import { useTheme } from '@/store/theme'
import PostSearchPager from '@/components/Post/PostSearchPager.vue'

const props = defineProps({
    month: {
        type: String,
        default: '',
    },
    users: {
        type: Array,
        default: () => [],
    },
})

const api = useApi()
const events = ref([])
const selectedEvent = ref(null)
const detail = ref(null)
const listLoading = ref(false)
const detailLoading = ref(false)
const activeFilterSummary = ref([])
const theme = useTheme()
const pagination = reactive({
    page: 1,
    per_page: 50,
    last_page: 1,
    total: 0,
})

const fieldLabelMap = {
    day: '対象日',
    status_flag: '申請状態',
    approval_state: '申請状態',
    internal_control_status: '内部統制',
    type: '勘定科目',
    transport_type: '交通手段',
    departure_place: '出発',
    arrival_place: '到着',
    department: '部門',
    content: '内容',
    expenses: '金額',
    merchant_name: '取引先',
    receipt_date: '領収書日付',
    currency: '通貨',
    receipt_source_type: '保存区分',
    file_path: '領収書ファイル',
    applied_fields: '反映項目',
    model: 'OCRモデル',
    provider: 'OCR提供元',
    status: 'OCR状態',
    ocr_run_id: 'OCR実行ID',
    timecard_day: '対象日',
    tax_amount: '消費税額',
    notes: 'メモ',
    amount: 'OCR金額',
    start_time: '出勤',
    end_time: '退勤',
    approved_by: '承認者ID',
    work_group_id: 'プロジェクトID',
}

const eventLabelMap = {
    timecard_saved_draft: '日報を下書き保存',
    timecard_updated: '日報を更新',
    timecard_submitted: '日報を申請',
    timecard_remanded: '日報を差戻し',
    timecard_approved: '日報を承認',
    timecard_approval_cancelled: '承認を取り消し',
    cost_created: '経費を追加',
    cost_updated: '経費を更新',
    cost_deleted: '経費を削除',
    receipt_uploaded: '領収書をアップロード',
    receipt_removed: '領収書を削除',
    ocr_extracted: 'OCRで領収書を読取',
    ocr_failed: 'OCR読取に失敗',
    ocr_applied: 'OCR結果を反映',
}

const eventChipMap = {
    timecard_saved_draft: { text: '下書き', className: 'chip-neutral' },
    timecard_updated: { text: '更新', className: 'chip-info' },
    timecard_submitted: { text: '申請', className: 'chip-info' },
    timecard_remanded: { text: '差戻し', className: 'chip-warn' },
    timecard_approved: { text: '承認', className: 'chip-success' },
    timecard_approval_cancelled: { text: '取消', className: 'chip-neutral' },
    cost_created: { text: '追加', className: 'chip-success' },
    cost_updated: { text: '更新', className: 'chip-info' },
    cost_deleted: { text: '削除', className: 'chip-danger' },
    receipt_uploaded: { text: '添付', className: 'chip-info' },
    receipt_removed: { text: '添付削除', className: 'chip-danger' },
    ocr_extracted: { text: 'OCR読取', className: 'chip-neutral' },
    ocr_failed: { text: 'OCR失敗', className: 'chip-danger' },
    ocr_applied: { text: 'OCR反映', className: 'chip-info' },
}

const approvalStateMap = {
    0: '下書き',
    1: '申請中',
    2: '承認済み',
    10: '差戻し',
}

const internalControlStatusMap = {
    recorded: '記録済み',
    sealed: '封印済み',
}

const receiptSourceTypeMap = {
    paper_scan: '紙領収書スキャン',
    electronic: '電子取引',
}

const transportTypeMap = {
    1: '電車のみ',
    2: '電車・バス',
    3: 'タクシー',
    4: '飛行機',
    5: 'その他',
}

const costTypeMap = {
    1: '交通費',
    2: '通信費',
    3: '宿泊費',
    4: '旅費交通費',
    5: '消耗品費',
    6: '交際費',
    7: '支払手数料',
    8: '福利厚生費',
}

const eventOptions = Object.entries(eventLabelMap).map(([value, label]) => ({ value, label }))
const approvalStateOptions = Object.entries(approvalStateMap).map(([value, label]) => ({ value, label }))

const filters = reactive({
    user_id: '',
    event_type: '',
    merchant: '',
    receipt_date_from: '',
    receipt_date_to: '',
    amount_min: '',
    amount_max: '',
    approval_state: '',
})

const ignoredFieldKeys = ['id', 'draft_uuid']

const eventTypeLabel = (value) => eventLabelMap[value] ?? value ?? '-'
const eventChipClass = (value) => eventChipMap[value]?.className ?? 'chip-neutral'
const eventChipText = (value) => eventChipMap[value]?.text ?? '記録'
const approvalStateLabel = (value) => approvalStateMap[String(value)] ?? (value ?? '-')
const internalControlStatusLabel = (value) => internalControlStatusMap[value] ?? (value ?? '-')
const internalControlStatusClass = (value) => value === 'sealed' ? 'control-status-sealed' : 'control-status-recorded'

const formatDate = (value) => {
    if (!value) return '-'

    const parsed = DateTime.fromISO(String(value))
    if (parsed.isValid) {
        return parsed.toFormat('yyyy/MM/dd')
    }

    return String(value)
}

const formatDateTime = (value) => {
    if (!value) return '-'

    const parsed = DateTime.fromISO(String(value))
    if (parsed.isValid) {
        return parsed.toFormat('yyyy/MM/dd HH:mm')
    }

    return String(value)
}

const amountLabel = (value) => {
    if (value === null || value === undefined || value === '') return '-'
    return `${Number(value).toLocaleString()}円`
}

const formatValue = (key, value) => {
    if (value === null || value === undefined || value === '') return '-'
    if (Array.isArray(value)) {
        return value.map((item) => formatValue(key, item)).join('、')
    }
    if (key === 'status_flag' || key === 'approval_state') {
        return approvalStateLabel(value)
    }
    if (key === 'internal_control_status') {
        return internalControlStatusLabel(value)
    }
    if (key === 'type') {
        return costTypeMap[value] ?? value
    }
    if (key === 'transport_type') {
        return transportTypeMap[value] ?? value
    }
    if (key === 'receipt_source_type') {
        return receiptSourceTypeMap[value] ?? value
    }
    if (key === 'expenses' || key === 'amount' || key === 'tax_amount') {
        return amountLabel(value)
    }
    if (key === 'receipt_date' || key === 'day' || key === 'timecard_day') {
        return formatDate(value)
    }
    if (key === 'occurred_at' || String(value).includes('T')) {
        const parsed = DateTime.fromISO(String(value))
        if (parsed.isValid) {
            return parsed.toFormat('yyyy/MM/dd HH:mm')
        }
    }
    if (key === 'file_path') {
        return 'あり'
    }

    return String(value)
}

const fieldRows = (state) => {
    if (!state) return []

    const keys = Object.keys(state).filter((key) => !ignoredFieldKeys.includes(key))
    return keys.map((key) => ({
        key,
        label: fieldLabelMap[key] ?? key,
        value: formatValue(key, state[key]),
    }))
}

const metadataRows = (metadata) => {
    if (!metadata) return []

    const keys = ['model', 'provider', 'status', 'ocr_run_id', 'timecard_day', 'approval_state', 'applied_fields']
    return keys
        .filter((key) => metadata[key] !== undefined && metadata[key] !== null && metadata[key] !== '')
        .map((key) => ({
            key,
            label: fieldLabelMap[key] ?? key,
            value: formatValue(key, metadata[key]),
        }))
}

const diffRows = (beforeState, afterState) => {
    const before = beforeState ?? {}
    const after = afterState ?? {}
    const keys = Array.from(new Set([...Object.keys(before), ...Object.keys(after)]))
        .filter((key) => !ignoredFieldKeys.includes(key))

    return keys
        .filter((key) => JSON.stringify(before[key] ?? null) !== JSON.stringify(after[key] ?? null))
        .map((key) => ({
            key,
            label: fieldLabelMap[key] ?? key,
            before: formatValue(key, before[key]),
            after: formatValue(key, after[key]),
        }))
}

const eventSummary = (event) => {
    const subjectName = event.subject?.name ?? '対象者'
    const merchant = event.merchant_name ? ` / ${event.merchant_name}` : ''
    const amount = event.expenses ? ` / ${amountLabel(event.expenses)}` : ''

    return `${subjectName} - ${eventTypeLabel(event.event_type)}${merchant}${amount}`
}

const detailNarrative = (detailValue) => {
    const subjectName = detailValue.subject?.name ?? '対象者'
    const actorName = detailValue.actor?.name ?? '担当者'
    const merchantName = detailValue.timecard_cost?.merchant_name
        || detailValue.after_state?.merchant_name
        || detailValue.before_state?.merchant_name
    const amountValue = detailValue.timecard_cost?.expenses
        ?? detailValue.after_state?.expenses
        ?? detailValue.before_state?.expenses
        ?? detailValue.ocr_run?.normalized_result?.amount
    const amountText = amountValue ? amountLabel(amountValue) : ''

    if (detailValue.event_type === 'ocr_applied') {
        const appliedFields = detailValue.metadata?.applied_fields?.map((field) => fieldLabelMap[field] ?? field).join('、') || 'OCR読取結果'
        return `${subjectName}の日報で、${actorName}が${appliedFields}をOCR結果から反映しました。`
    }
    if (detailValue.event_type === 'cost_updated') {
        return `${subjectName}の経費記録を更新しました${merchantName ? `。取引先は「${merchantName}」です。` : '。'}`
    }
    if (detailValue.event_type === 'cost_created') {
        return `${subjectName}の経費記録を追加しました${amountText ? `。金額は${amountText}です。` : '。'}`
    }
    if (detailValue.event_type === 'cost_deleted') {
        return `${subjectName}の経費記録を削除しました。`
    }
    if (detailValue.event_type === 'receipt_uploaded') {
        return `${subjectName}の経費に領収書ファイルを添付しました。`
    }
    if (detailValue.event_type === 'receipt_removed') {
        return `${subjectName}の経費から領収書ファイルを外しました。`
    }
    if (detailValue.event_type === 'ocr_extracted') {
        return `${subjectName}の領収書に対してOCR読取を実行しました。`
    }
    if (detailValue.event_type === 'ocr_failed') {
        return `${subjectName}の領収書に対するOCR読取が失敗しました。`
    }
    if (detailValue.event_type === 'timecard_submitted') {
        return `${subjectName}の日報が申請されました。`
    }
    if (detailValue.event_type === 'timecard_approved') {
        return `${subjectName}の日報を${actorName}が承認しました。`
    }
    if (detailValue.event_type === 'timecard_remanded') {
        return `${subjectName}の日報を${actorName}が差し戻しました。`
    }
    if (detailValue.event_type === 'timecard_approval_cancelled') {
        return `${subjectName}の日報の承認を取り消しました。`
    }
    if (detailValue.event_type === 'timecard_saved_draft') {
        return `${subjectName}の日報が下書き保存されました。`
    }
    if (detailValue.event_type === 'timecard_updated') {
        return `${subjectName}の日報内容が更新されました。`
    }

    return `${subjectName}に対して「${eventTypeLabel(detailValue.event_type)}」が記録されました。`
}

const refreshFilterSummary = () => {
    const summary = []
    if (filters.user_id) {
        const user = props.users.find((item) => String(item.id) === String(filters.user_id))
        if (user) summary.push({ key: 'user_id', label: `社員: ${user.name}` })
    }
    if (filters.event_type) {
        summary.push({ key: 'event_type', label: `イベント: ${eventTypeLabel(filters.event_type)}` })
    }
    if (filters.merchant) {
        summary.push({ key: 'merchant', label: `取引先: ${filters.merchant}` })
    }
    if (filters.receipt_date_from) {
        summary.push({ key: 'receipt_date_from', label: `領収書(開始): ${formatDate(filters.receipt_date_from)}` })
    }
    if (filters.receipt_date_to) {
        summary.push({ key: 'receipt_date_to', label: `領収書(終了): ${formatDate(filters.receipt_date_to)}` })
    }
    if (filters.amount_min) {
        summary.push({ key: 'amount_min', label: `下限: ${amountLabel(filters.amount_min)}` })
    }
    if (filters.amount_max) {
        summary.push({ key: 'amount_max', label: `上限: ${amountLabel(filters.amount_max)}` })
    }
    if (filters.approval_state !== '') {
        summary.push({ key: 'approval_state', label: `状態: ${approvalStateLabel(filters.approval_state)}` })
    }
    activeFilterSummary.value = summary
}

const openReceiptFile = () => {
    if (detail.value?.receipt_file_url) {
        window.open(detail.value.receipt_file_url, '_blank', 'noopener')
    }
}

const buildAuditQuery = ({ exportMode = false } = {}) => {
    const params = {}

    Object.entries(filters).forEach(([key, value]) => {
        if (value !== '' && value !== null && value !== undefined) {
            params[key] = value
        }
    })

    if (props.month) {
        params.month = props.month
    }

    if (exportMode) {
        params.export = 1
        return params
    }

    params.page = pagination.page
    params.per_page = pagination.per_page

    return params
}

const downloadExport = () => {
    const query = new URLSearchParams()

    Object.entries(buildAuditQuery({ exportMode: true })).forEach(([key, value]) => {
        query.set(key, String(value))
    })

    window.open(`/work_audit_logs?${query.toString()}`, '_blank', 'noopener')
}

const clearFilter = (key) => {
    filters[key] = ''
    loadEvents(1)
}

const loadEvents = async (page = pagination.page) => {
    const nextPage = Number(page)
    pagination.page = Number.isFinite(nextPage) && nextPage > 0 ? nextPage : 1
    refreshFilterSummary()
    const params = buildAuditQuery()

    const response = await api.get('/work_audit_logs', params, {
        silent: true,
        loadingRef: listLoading,
    })
    if (!response) return

    if (Array.isArray(response)) {
        events.value = response
        pagination.total = response.length
        pagination.last_page = 1
    } else {
        events.value = response.data ?? []
        pagination.page = response.meta?.current_page ?? pagination.page
        pagination.last_page = response.meta?.last_page ?? 1
        pagination.per_page = response.meta?.per_page ?? pagination.per_page
        pagination.total = response.meta?.total ?? events.value.length
    }

    if (!selectedEvent.value && events.value.length) {
        await selectEvent(events.value[0])
    } else if (selectedEvent.value) {
        const matched = events.value.find((event) => event.id === selectedEvent.value.id)
        if (!matched) {
            selectedEvent.value = null
            detail.value = null
        }
    }
}

const selectEvent = async (event) => {
    selectedEvent.value = event
    const response = await api.get(`/work_audit_logs/${event.id}`, null, {
        silent: true,
        loadingRef: detailLoading,
    })
    if (!response) return

    detail.value = response
}

const resetFilters = () => {
    filters.user_id = ''
    filters.event_type = ''
    filters.merchant = ''
    filters.receipt_date_from = ''
    filters.receipt_date_to = ''
    filters.amount_min = ''
    filters.amount_max = ''
    filters.approval_state = ''
    loadEvents(1)
}

const applyPreset = (preset) => {
    if (preset === 'remanded') {
        filters.event_type = 'timecard_remanded'
        filters.approval_state = '10'
    } else if (preset === 'ocrApplied') {
        filters.event_type = 'ocr_applied'
        filters.approval_state = ''
    } else if (preset === 'deleted') {
        filters.event_type = 'cost_deleted'
        filters.approval_state = ''
    } else if (preset === 'submitted') {
        filters.event_type = 'timecard_submitted'
        filters.approval_state = '1'
    }

    loadEvents(1)
}

const changePage = (nextPage) => {
    if (nextPage < 1 || nextPage > pagination.last_page || listLoading.value) {
        return
    }

    loadEvents(nextPage)
}

const pretty = (value) => JSON.stringify(value ?? {}, null, 2)

watch(() => props.month, () => {
    selectedEvent.value = null
    detail.value = null
    pagination.page = 1
    loadEvents(1)
}, { immediate: true })
</script>
<style scoped>
    .receipt-audit-panel {
        --audit-surface: var(--background-color);
        --audit-surface-muted: var(--bg3);
        --audit-surface-soft: var(--bg2);
        --audit-border: var(--calendarBorder);
        --audit-border-strong: var(--formBorder);
        --audit-shadow: 0 10px 18px rgba(0, 0, 0, 0.08);
        --audit-shadow-soft: 0 6px 12px rgba(0, 0, 0, 0.05);
        --audit-text-soft: var(--third-color, var(--primary-color));
        --audit-accent: var(--primary-color);
        height: 100%;
        box-sizing: border-box !important;
        display: flex;
        flex-direction: column;
        gap: 18px;
        padding: 0 16px;
        min-height: 0;
        overflow: hidden;
        /* background: var(--background-color); */
        color: var(--primary-color);
    }

    .receipt-audit-filters {
        display: flex;
        flex-direction: column;
        gap: 12px;
        padding: 16px;
        border: 1px solid var(--audit-border);
        border-radius: 18px;
        background: var(--audit-surface-muted);
        box-shadow: var(--audit-shadow-soft);
    }

    .filter-row {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
        align-items: flex-end;
    }

    .filter-field,
    .filter-group {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .filter-label {
        font-size: 11px;
        font-weight: 600;
        color: var(--audit-text-soft);
        padding-left: 2px;
        white-space: nowrap;
    }

    .filter-range {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .filter-range-sep {
        font-size: 13px;
        color: var(--audit-text-soft);
        white-space: nowrap;
    }

    .filter-actions {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
    }

    .filter-action-buttons {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .filter-total-count {
        font-size: 13px;
        font-weight: 600;
        color: var(--audit-text-soft);
        white-space: nowrap;
    }

    .filter-presets {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .filter-presets-label {
        font-size: 11px;
        font-weight: 600;
        color: var(--audit-text-soft);
        white-space: nowrap;
    }

    .filter-summary {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        align-items: center;
        font-size: 12px;
        padding-top: 2px;
    }

    .filter-summary-label {
        font-weight: 700;
        color: var(--audit-text-soft);
    }

    .filter-summary-chip,
    .preset-chip {
        border: 1px solid var(--audit-border-strong);
        padding: 7px 12px;
        background: var(--audit-surface);
        font-size: 12px;
        color: var(--primary-color);
        border-radius: 999px;
        transition: transform 0.18s ease, box-shadow 0.18s ease, background-color 0.18s ease;
    }

    .filter-summary-chip {
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    .filter-summary-dismiss {
        font-size: 10px;
        opacity: 0.55;
        line-height: 1;
        font-weight: 400;
    }

    .preset-chip {
        cursor: pointer;
    }

    .preset-chip:hover,
    .preset-chip:focus-visible,
    .filter-summary-chip:hover {
        transform: translateY(-1px);
        box-shadow: var(--audit-shadow-soft);
        background: var(--audit-surface-soft);
    }

    .receipt-audit-layout {
        display: grid;
        grid-template-columns: 340px minmax(0, 1fr);
        gap: 16px;
        min-height: 0;
        flex: 1;
    }

    .receipt-audit-list,
    .receipt-audit-detail {
        border: 1px solid var(--audit-border);
        overflow: auto;
        min-height: 0;
        background: var(--audit-surface);
        box-shadow: var(--audit-shadow);
    }

    .receipt-audit-list-item {
        position: relative;
        padding: 18px 18px 16px;
        border-bottom: 1px solid var(--audit-border);
        cursor: pointer;
        transition: transform 0.18s ease, background-color 0.18s ease, box-shadow 0.18s ease;
        background: transparent;
    }

    .receipt-audit-list-item:last-child {
        border-bottom: none;
    }

    .receipt-audit-list-item::before {
        content: '';
        position: absolute;
        left: 0;
        top: 16px;
        bottom: 16px;
        width: 4px;
        border-radius: 999px;
        background: transparent;
        transition: background-color 0.18s ease;
    }

    .receipt-audit-list-item:hover {
        background: var(--audit-surface-muted);
        transform: translateY(-1px);
        box-shadow: inset 0 0 0 1px var(--audit-border-strong);
    }

    .receipt-audit-list-top,
    .detail-title-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
    }

    .receipt-audit-list-item.active {
        background: var(--selected-background, var(--audit-surface-muted));
        box-shadow:
            inset 0 0 0 1px var(--audit-border-strong),
            var(--audit-shadow-soft);
        transform: translateY(-1px);
    }

    .receipt-audit-list-item.active::before {
        background: var(--audit-accent);
    }

    .receipt-audit-title {
        font-weight: 700;
        margin-bottom: 8px;
        font-size: 15px;
        color: var(--primary-color);
        letter-spacing: 0.01em;
    }

    .receipt-audit-summary {
        font-size: 13px;
        line-height: 1.7;
        margin-bottom: 10px;
        color: var(--primary-color);
    }

    .event-chip {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 76px;
        padding: 5px 11px;
        font-size: 11px;
        font-weight: 700;
        border-radius: 999px;
        border: 1px solid transparent;
    }

    .chip-neutral {
        color: var(--primary-color);
        background: var(--audit-surface-muted);
        border-color: var(--audit-border-strong);
    }

    .chip-info {
        color: #4b84c4;
        background: transparent;
        border-color: currentColor;
    }

    .chip-success {
        color: #46a36c;
        background: transparent;
        border-color: currentColor;
    }

    .chip-warn {
        color: #cf9e3e;
        background: transparent;
        border-color: currentColor;
    }

    .chip-danger {
        color: #d46d6d;
        background: transparent;
        border-color: currentColor;
    }

    .receipt-audit-meta {
        font-size: 12px;
        line-height: 1.55;
        color: var(--audit-text-soft);
    }

    .control-status-chip {
        display: inline-flex;
        align-items: center;
        margin-left: 6px;
        padding: 2px 8px;
        border-radius: 999px;
        border: 1px solid transparent;
        font-size: 11px;
        font-weight: 700;
        vertical-align: middle;
    }

    .control-status-recorded {
        color: #9b7b2d;
        background: rgba(207, 158, 62, 0.12);
        border-color: rgba(207, 158, 62, 0.34);
    }

    .control-status-sealed {
        color: #2f8a55;
        background: rgba(70, 163, 108, 0.12);
        border-color: rgba(70, 163, 108, 0.32);
    }

    .receipt-audit-detail {
        padding: 22px;
    }

    .detail-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 16px;
        flex-wrap: wrap;
    }

    .detail-header h3,
    .detail-section h4 {
        margin: 0 0 8px;
    }

    .detail-header p {
        margin: 0;
        font-size: 12px;
        color: var(--audit-text-soft);
    }

    .detail-narrative {
        margin-top: 14px;
        padding: 16px 18px;
        background: var(--audit-surface-muted);
        border: 1px solid var(--audit-border-strong);
        border-radius: 16px;
        box-shadow: var(--audit-shadow-soft);
        font-size: 13px;
        line-height: 1.7;
        color: var(--primary-color);
    }

    .detail-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
        margin: 18px 0;
        font-size: 13px;
    }

    .detail-grid > div {
        padding: 12px 14px;
        border: 1px solid var(--audit-border);
        border-radius: 14px;
        background: var(--audit-surface-muted);
        color: var(--primary-color);
    }

    .detail-section {
        margin-top: 20px;
        padding: 16px 18px;
        border: 1px solid var(--audit-border);
        border-radius: 18px;
        background: var(--audit-surface);
        box-shadow: var(--audit-shadow-soft);
    }

    .detail-section h4 {
        color: var(--primary-color);
    }

    .detail-section pre {
        background: var(--audit-surface-soft);
        padding: 14px;
        overflow: auto;
        font-size: 12px;
        margin: 0;
        border-radius: 14px;
        border: 1px solid var(--audit-border);
    }

    .detail-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        font-size: 13px;
        overflow: hidden;
        border: 1px solid var(--audit-border);
        border-radius: 14px;
    }

    .detail-table td,
    .detail-table th {
        border-bottom: 1px solid var(--audit-border);
        padding: 11px 12px;
        text-align: left;
        vertical-align: top;
    }

    .detail-table td + td,
    .detail-table th + th {
        border-left: 1px solid var(--audit-border);
    }

    .detail-table tbody tr:last-child td {
        border-bottom: none;
    }

    .detail-table th {
        background: var(--audit-surface-muted);
        color: var(--primary-color);
    }

    .audit-input {
        padding: 0 12px;
        height: 40px;
        border: 1px solid var(--audit-border-strong);
        color: var(--primary-color);
        background: var(--audit-surface);
        border-radius: 12px;
    }

    .audit-button {
        border: 1px solid var(--primary-button);
        color: #fff;
        background: var(--primary-button);
        padding: 9px 16px;
        width: fit-content;
        border-radius: 12px;
        box-shadow: var(--audit-shadow-soft);
        transition: transform 0.18s ease, box-shadow 0.18s ease, filter 0.18s ease;
        cursor: pointer;
    }

    .audit-button:hover,
    .audit-button:focus-visible {
        transform: translateY(-1px);
        box-shadow: 0 12px 22px rgba(0, 0, 0, 0.12);
        filter: saturate(1.05);
    }

    .audit-button:disabled {
        opacity: 0.55;
        cursor: not-allowed;
        transform: none;
        box-shadow: var(--audit-shadow-soft);
        filter: none;
    }

    .audit-button-secondary {
        border-color: var(--audit-border-strong);
        color: var(--primary-color);
        background: var(--audit-surface);
    }

    .receipt-audit-empty,
    .list-feedback {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 180px;
        font-size: 13px;
        padding: 24px;
        color: var(--audit-text-soft);
        text-align: center;
        line-height: 1.7;
    }

    .skeleton-item {
        padding: 18px 18px 16px;
        border-bottom: 1px solid var(--audit-border);
        display: flex;
        flex-direction: column;
        gap: 9px;
    }

    .skeleton-line {
        border-radius: 6px;
        background: linear-gradient(
            90deg,
            var(--audit-surface-muted) 25%,
            var(--audit-surface-soft) 50%,
            var(--audit-surface-muted) 75%
        );
        background-size: 200% 100%;
        animation: skeleton-shimmer 1.4s ease-in-out infinite;
    }

    .skeleton-title { height: 15px; width: 58%; }
    .skeleton-sub { height: 12px; width: 82%; }
    .skeleton-meta { height: 11px; width: 42%; }

    @keyframes skeleton-shimmer {
        0% { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }

    .detail-is-loading {
        opacity: 0.4;
        pointer-events: none;
        transition: opacity 0.25s ease;
    }

    .technical-section summary {
        cursor: pointer;
        font-weight: 700;
    }

    .detail-section-inner {
        margin-top: 12px;
    }

    .list-pagination {
        position: sticky;
        bottom: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 10px 12px;
        border-top: 1px solid var(--audit-border);
        background: var(--audit-surface);
    }

    .pagination-summary {
        font-size: 12px;
        color: var(--audit-text-soft);
    }

    @media (max-width: 900px) {
        .receipt-audit-panel {
            padding: 10px;
        }

        .receipt-audit-layout {
            grid-template-columns: 1fr;
        }

        .detail-grid {
            grid-template-columns: 1fr;
        }

        .receipt-audit-detail,
        .receipt-audit-filters {
            padding: 16px;
        }
    }
</style>
