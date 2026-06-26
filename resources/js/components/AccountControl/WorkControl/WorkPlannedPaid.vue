<template>
    <div class="planned-paid">
        <Transition name="modalFade">
            <div v-if="loading" class="control-loader">
                <div class="spinner-mini" style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
            </div>
        </Transition>

        <div class="admin-sub-c-bar">
            <PostSearchBar
                className="newChatMemberSearch"
                style="width:auto;min-width: 300px;"
                @search-start="word => keywords = word"
            />
            <div class="admin-work-header">
                <YearPicker :selectedYear="year" @setDate="setDate" />
            </div>
        </div>

        <div class="summary-row">
            <div class="summary-cell">
                <span>対象期間</span>
                <strong>{{ year }}年</strong>
            </div>
            <div class="summary-cell">
                <span>対象者</span>
                <strong>{{ periodUserCount }}</strong>
            </div>
            <div class="summary-cell warning">
                <span>不足あり</span>
                <strong>{{ shortageCount }}</strong>
            </div>
        </div>

        <div class="table-wrap">
            <table class="planned-table">
                <thead>
                    <tr>
                        <th>名前</th>
                        <th>社員コード</th>
                        <th>付与日</th>
                        <th>設定期間</th>
                        <th>付与</th>
                        <th>計画必要</th>
                        <th>設定済み</th>
                        <th>不足</th>
                        <th>計画日</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="row in periodRows"
                        :key="`${row.user.id}-${row.period.id}`"
                        :class="{ short: row.period.status === 'short' }"
                    >
                        <td>{{ row.user.name }}</td>
                        <td>{{ row.user.user_code || '' }}</td>
                        <td>{{ row.period.period_start }}</td>
                        <td>{{ row.period.period_start }} - {{ row.period.period_end }}</td>
                        <td>{{ formatDays(row.period.granted_days) }}日</td>
                        <td>{{ formatDays(row.period.planned_required_days) }}日</td>
                        <td>{{ formatDays(row.period.planned_days) }}日</td>
                        <td>
                            <span :class="['status-pill', row.period.status]">
                                {{ row.period.status === 'short' ? `${formatDays(row.period.planned_remaining_days)}日` : 'OK' }}
                            </span>
                        </td>
                        <td class="planned-date-cell">
                            <span v-for="shift in row.period.shift_records" :key="shift.id">{{ shift.shift_day }}</span>
                            <span v-if="!row.period.shift_records?.length" class="muted">未設定</span>
                        </td>
                        <td>
                            <button type="button" class="plain-button" @click="openEditor(row.user, row.period)">変更</button>
                        </td>
                    </tr>
                    <tr v-if="periodRows.length === 0">
                        <td colspan="10" class="empty-cell">この年に対象の有休付与がありません。</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="overlay" v-if="editorOpen">
            <div class="planned-editor">
                <div class="recordFormTitle">
                    <p class="ml-5">計画有給日を変更</p>
                    <button type="button" class="editor-close" aria-label="閉じる" @click="closeEditor">
                        <CloseIcon :size="12" />
                    </button>
                </div>

                <div class="editor-body">
                    <div class="editor-summary">
                        <div>
                            <span>名前</span>
                            <strong>{{ editUser?.name }}</strong>
                        </div>
                        <div>
                            <span>設定期間</span>
                            <strong>{{ editPeriod?.period_start }} - {{ editPeriod?.period_end }}</strong>
                        </div>
                        <div>
                            <span>必要</span>
                            <strong>{{ formatDays(editPeriod?.planned_required_days || 0) }}日</strong>
                        </div>
                        <div>
                            <span>設定済み</span>
                            <strong>{{ formatDays(editPeriod?.planned_days || 0) }}日</strong>
                        </div>
                    </div>

                    <div v-if="!editPeriod?.shift_records?.length" class="empty-editor">
                        この期間に変更できる計画有給日がありません。新規設定は勤怠予定画面から行ってください。
                    </div>

                    <table v-else class="planned-table compact">
                        <thead>
                            <tr>
                                <th>変更後</th>
                                <th>現在</th>
                                <th>変更前</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="shift in editPeriod.shift_records" :key="shift.id">
                                <td>
                                    <input
                                        class="date-input"
                                        :class="{'date-color' : theme.dark}"
                                        :value="changedDateFor(shift)"
                                        type="date"
                                        :min="editPeriod.shift_window_start || editPeriod.period_start"
                                        :max="editPeriod.shift_window_end || editPeriod.period_end"
                                        @input="getShift($event.target.value, shift.id)"
                                    />
                                </td>
                                <td>{{ shift.shift_day }}</td>
                                <td>{{ shift.old_shift?.shift_day ?? '' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="editor-actions">
                    <button type="button" class="plain-button" @click="closeEditor">キャンセル</button>
                    <button type="button" class="plain-button primary" :disabled="processing || !changedShifts.length" @click="saveShift">
                        {{ processing ? '保存中...' : '保存する' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import YearPicker from '../../Global/YearPicker.vue'
import { computed, onMounted, ref } from 'vue';
import PostSearchBar from '../../Post/PostSearchBar.vue';
import { useApi } from '@/composables/api';
import CloseIcon from '../../Form/CloseIcon.vue';
import { useTheme } from '@/store/theme';
const keywords = ref('')
const plannedShifts = ref([])
const year = ref(new Date().getFullYear())
const editorOpen = ref(false)
const editUser = ref(null)
const editPeriod = ref(null)
const processing = ref(false)
const changedShifts = ref([])
const loading = ref(false)
const api = useApi()
const theme = useTheme()
onMounted(async () => {
    await getPlannedShifts()
})

const filteredData = computed(() => {
    const word = keywords.value.toLowerCase()
    if (!word) return plannedShifts.value

    return plannedShifts.value.filter(user => {
        const userHit = [user.name, user.user_code, user.joined_date].some(value => String(value || '').toLowerCase().includes(word))
        const periodHit = user.grant_periods?.some(period => {
            return Object.values(period).some(value => String(value || '').toLowerCase().includes(word))
        })

        return userHit || periodHit
    })
})

const periodRows = computed(() => {
    return filteredData.value.flatMap(user => {
        return (user.grant_periods || [])
            .filter(period => Number(period.planned_year) === Number(year.value))
            .map(period => ({ user, period }))
    })
})

const shortageCount = computed(() => {
    return periodRows.value.filter(row => row.period.status === 'short').length
})

const periodUserCount = computed(() => {
    return new Set(periodRows.value.map(row => row.user.id)).size
})

const getPlannedShifts = async() => {
    plannedShifts.value = await api.post('/get_planned_shifts', {year: year.value}, {loadingRef: loading}) || []
}

const setDate = (val) => {
    year.value = val.year
    getPlannedShifts()
}

const openEditor = (user, period) => {
    editorOpen.value = true
    editUser.value = user
    editPeriod.value = period
    changedShifts.value = []
}

const closeEditor = () => {
    editorOpen.value = false
    editUser.value = null
    editPeriod.value = null
    changedShifts.value = []
}

const changedDateFor = (shift) => {
    return changedShifts.value.find(item => item.id === shift.id)?.shift_day || shift.shift_day
}

const getShift = (val, id) => {
    const existingShiftIndex = changedShifts.value.findIndex(s => s.id === id)

    if (existingShiftIndex !== -1) {
        changedShifts.value[existingShiftIndex].shift_day = val
    } else {
        changedShifts.value.push({ id, shift_day: val })
    }

    changedShifts.value = changedShifts.value.filter(shift => {
        const original = editPeriod.value?.shift_records?.find(item => item.id === shift.id)
        return original && original.shift_day !== shift.shift_day
    })
}

const saveShift = async() => {
    if (!editUser.value || !editPeriod.value || !changedShifts.value.length) return

    await api.post('/change_planned_shifts',
        {
            shifts: changedShifts.value,
            userId: editUser.value.id,
            startDate: editPeriod.value.shift_window_start || editPeriod.value.period_start,
            endDate: editPeriod.value.shift_window_end || editPeriod.value.period_end,
        },
        {
            toast: '保存しました。',
            loadingRef: processing,
        }
    )

    await getPlannedShifts()
    closeEditor()
}

const formatDays = (value) => {
    return new Intl.NumberFormat('ja-JP', { maximumFractionDigits: 2 }).format(Number(value) || 0)
}
</script>

<style scoped>
.planned-paid {
    height: 100%;
    overflow: hidden;
    position: relative;
    display: flex;
    flex-direction: column;
    color: var(--primary-color);
}

.admin-work-header {
    display: flex;
    gap: 20px;
}

.summary-row {
    display: grid;
    grid-template-columns: repeat(3, minmax(120px, 1fr));
    gap: 20px;
    margin: 0 20px 20px;
}

.summary-cell {
    background: var(--background-color);
    padding: 10px 12px;
}

.summary-cell span {
    display: block;
    color: var(--third-color);
    font-size: 11px;
}

.summary-cell strong {
    display: block;
    margin-top: 4px;
    font-size: 18px;
}

.summary-cell.warning strong {
    color: #b42318;
}

.table-wrap {
    flex: 1;
    min-height: 0;
    overflow: auto;
    margin: 0 20px 20px;
    border: 1px solid var(--calendarBorder);
    background: var(--background-color);
}

.planned-table {
    border-collapse: collapse;
    width: 100%;
    font-size: 13px;
}

.planned-table.compact {
    font-size: 13px;
}

.planned-table td,
.planned-table th {
    /* border: 1px solid var(--formBorder); */
    padding: 8px;
    vertical-align: middle;
}

.planned-table th {
    position: sticky;
    top: -1px;
    z-index: 1;
    text-align: left;
    background-color: #363636;
    color: white;
}

.planned-table tr:nth-child(even) {
    background-color: var(--bg3);
}

.planned-table tr.short {
    background: rgba(180, 35, 24, 0.08);
}

.planned-date-cell {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    max-width: 280px;
}

.planned-date-cell span:not(.muted) {
    border: 1px solid var(--formBorder);
    padding: 2px 5px;
    background: var(--background-color);
}

.muted,
.empty-cell,
.empty-editor {
    color: var(--third-color);
}

.status-pill {
    display: inline-flex;
    min-width: 42px;
    justify-content: center;
    border: 1px solid var(--formBorder);
    padding: 3px 7px;
}

.status-pill.short {
    color: #b42318;
    border-color: #b42318;
}

.plain-button {
    border-radius: 0;
    background: var(--primary-button);
    color: #fff;
    cursor: pointer;
    min-height: 30px;
    padding: 0 10px;
    font-size: 12px;
}


.plain-button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.planned-editor {
    width: min(760px, calc(100vw - 40px));
    max-height: calc(100vh - 80px);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    background: var(--background-color);
    color: var(--primary-color);
    border: 1px solid var(--formBorder);
}

.recordFormTitle {
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid var(--formBorder);
}

.editor-close {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 34px;
    height: 34px;
    border: 0;
    background: transparent;
    color: var(--primary-color);
    cursor: pointer;
    padding: 0;
    margin-right: 20px;
}

.editor-body {
    padding: 16px;
    overflow: auto;
}

.editor-summary {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 8px;
    margin-bottom: 14px;
}

.editor-summary div {
    border: 1px solid var(--formBorder);
    padding: 8px;
}

.editor-summary span {
    display: block;
    color: var(--third-color);
    font-size: 11px;
}

.editor-summary strong {
    display: block;
    margin-top: 4px;
    font-size: 13px;
}

.editor-actions {
    display: flex;
    gap: 8px;
    justify-content: flex-end;
    border-top: 1px solid var(--formBorder);
    padding: 12px 16px;
}

.date-input {
    border: 1px solid var(--formBorder);
    border-radius: 0;
    background: var(--background-color);
    color: var(--primary-color);
    min-height: 30px;
    padding: 4px 6px;
}

@media screen and (max-width: 720px) {
    .summary-row,
    .editor-summary {
        grid-template-columns: 1fr;
    }

    .table-wrap {
        margin: 0 12px 12px;
    }
}
</style>
