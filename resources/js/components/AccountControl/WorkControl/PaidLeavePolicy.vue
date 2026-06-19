<template>
    <div class="admin-window paid-leave-policy">
        <Transition name="modalFade">
            <div v-if="loading" class="control-loader">
                <div class="spinner-mini" style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
            </div>
        </Transition>

        <header class="policy-header">
            <div>
                <h1>有休ルール</h1>
                <p>入社日を基準にした付与、期限、時間単位利用の設定</p>
            </div>
            <div class="header-actions">
                <button type="button" class="pl-button subtle" :disabled="loading" @click="loadPolicy">更新</button>
                <button type="button" class="pl-button primary" :disabled="savingSettings || !policyForm" @click="saveSettings">
                    {{ savingSettings ? '保存中...' : '設定保存' }}
                </button>
            </div>
        </header>

        <div v-if="policyForm" class="policy-content">
            <section class="metric-grid" aria-label="有休ルール集計">
                <div class="metric-cell">
                    <span>有効ルール</span>
                    <strong>{{ summary.active_rule_count }}</strong>
                </div>
                <div class="metric-cell">
                    <span>6ヶ月最低</span>
                    <strong>{{ formatDays(summary.legal_minimum_days_at_6_months) }}日</strong>
                </div>
                <div class="metric-cell">
                    <span>最大付与</span>
                    <strong>{{ formatDays(summary.max_grant_days) }}日</strong>
                </div>
                <div class="metric-cell">
                    <span>失効</span>
                    <strong>{{ policyForm.expires_after_months }}ヶ月</strong>
                </div>
            </section>

            <div class="workspace">
                <section class="settings-panel">
                    <div class="section-head">
                        <h2>基本設定</h2>
                    </div>
                    <div class="form-grid">
                        <label class="check-field">
                            <input v-model="policyForm.active" type="checkbox" />
                            有効
                        </label>
                        <label>
                            適用開始日
                            <input v-model="policyForm.effective_from" type="date" :class="{'date-color' : theme.dark}"/>
                        </label>
                        <label>
                            初回付与
                            <div class="input-with-unit">
                                <input v-model.number="policyForm.first_grant_after_months" type="number" min="0" max="240" />
                                <span>ヶ月後</span>
                            </div>
                        </label>
                        <label>
                            定期付与間隔
                            <div class="input-with-unit">
                                <input v-model.number="policyForm.annual_grant_interval_months" type="number" min="1" max="60" />
                                <span>ヶ月</span>
                            </div>
                        </label>
                        <label>
                            失効期限
                            <div class="input-with-unit">
                                <input v-model.number="policyForm.expires_after_months" type="number" min="1" max="120" />
                                <span>ヶ月</span>
                            </div>
                        </label>
                        <label>
                            出勤率基準
                            <div class="input-with-unit">
                                <input v-model.number="policyForm.minimum_attendance_rate" type="number" min="0" max="100" step="0.01" />
                                <span>%</span>
                            </div>
                        </label>
                        <label class="check-field">
                            <input v-model="policyForm.carryover_enabled" type="checkbox" />
                            繰越あり
                        </label>
                        <label class="check-field">
                            <input v-model="policyForm.allow_negative_balance" type="checkbox" />
                            マイナス残数許可
                        </label>
                    </div>

                    <div class="section-head compact">
                        <h2>時間単位</h2>
                    </div>
                    <div class="form-grid">
                        <label class="check-field">
                            <input v-model="policyForm.hourly_leave_enabled" type="checkbox" />
                            時間単位有休
                        </label>
                        <label>
                            差引単位
                            <div class="input-with-unit">
                                <input v-model.number="policyForm.hourly_deduction_unit_minutes" type="number" min="1" max="480" />
                                <span>分</span>
                            </div>
                        </label>
                        <label>
                            1日換算
                            <div class="input-with-unit">
                                <input v-model.number="policyForm.minutes_per_leave_day" type="number" min="1" max="1440" />
                                <span>分</span>
                            </div>
                        </label>
                        <label>
                            年間上限
                            <div class="input-with-unit">
                                <input v-model.number="policyForm.max_hourly_leave_days_per_year" type="number" min="0" max="365" step="0.5" />
                                <span>日</span>
                            </div>
                        </label>
                        <label class="wide">
                            メモ
                            <textarea v-model.trim="policyForm.memo" rows="3" />
                        </label>
                    </div>
                </section>

                <section class="rules-panel">
                    <div class="section-head">
                        <h2>付与日数</h2>
                        <button type="button" class="pl-button subtle" @click="startCreate">
                            <AddIcon :size="10" />
                            ルール追加
                        </button>
                    </div>

                    <div v-if="underMinimumRules.length" class="warning-strip">
                        法定最低日数を下回るルールがあります。
                    </div>

                    <div class="table-scroll">
                        <table class="rules-table">
                            <thead>
                                <tr>
                                    <th>勤続</th>
                                    <th>法定最低</th>
                                    <th>付与</th>
                                    <th>状態</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="rule in rules"
                                    :key="rule.id"
                                    :class="{ selected: selectedRuleId === rule.id, invalid: rule.grant_days < rule.legal_min_days }"
                                    @click="selectRule(rule)"
                                >
                                    <td>
                                        <strong>{{ rule.label }}</strong>
                                        <small>{{ rule.service_months }}ヶ月</small>
                                    </td>
                                    <td>{{ formatDays(rule.legal_min_days) }}日</td>
                                    <td>{{ formatDays(rule.grant_days) }}日</td>
                                    <td>{{ rule.active ? '有効' : '停止' }}</td>
                                </tr>
                                <tr v-if="rules.length === 0">
                                    <td colspan="4" class="empty-cell">付与ルールがありません。</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <aside class="editor-panel">
                    <template v-if="editorOpen">
                        <div class="section-head">
                            <h2>{{ ruleForm.id ? 'ルール編集' : 'ルール追加' }}</h2>
                            <button type="button" class="icon-button" aria-label="閉じる" @click="closeEditor">
                                <CloseIcon :size="10" />
                            </button>
                        </div>

                        <div class="editor-form">
                            <label>
                                表示名
                                <input v-model.trim="ruleForm.label" type="text" />
                            </label>
                            <label>
                                勤続月数
                                <div class="input-with-unit">
                                    <input v-model.number="ruleForm.service_months" type="number" min="0" max="600" />
                                    <span>ヶ月</span>
                                </div>
                            </label>
                            <label>
                                法定最低
                                <div class="input-with-unit">
                                    <input v-model.number="ruleForm.legal_min_days" type="number" min="0" max="365" step="0.5" />
                                    <span>日</span>
                                </div>
                            </label>
                            <label>
                                付与日数
                                <div class="input-with-unit">
                                    <input v-model.number="ruleForm.grant_days" type="number" min="0" max="365" step="0.5" />
                                    <span>日</span>
                                </div>
                            </label>
                            <label>
                                並び順
                                <input v-model.number="ruleForm.sort_order" type="number" min="0" max="65535" />
                            </label>
                            <label class="check-field">
                                <input v-model="ruleForm.active" type="checkbox" />
                                有効
                            </label>
                            <label class="wide">
                                メモ
                                <textarea v-model.trim="ruleForm.memo" rows="4" />
                            </label>
                        </div>

                        <div v-if="ruleForm.grant_days < ruleForm.legal_min_days" class="warning-strip">
                            付与日数は法定最低日数以上にしてください。
                        </div>

                        <div class="form-actions">
                            <button v-if="ruleForm.id" type="button" class="pl-button danger" @click="deleteRule">削除</button>
                            <button type="button" class="pl-button subtle" @click="resetRuleForm">リセット</button>
                            <button type="button" class="pl-button primary" :disabled="savingRule || !canSaveRule" @click="saveRule">
                                {{ savingRule ? '保存中...' : '保存' }}
                            </button>
                        </div>
                    </template>

                    <div v-else class="editor-empty">
                        <h2>ルールを選択</h2>
                        <p>左の表から編集、または新しい勤続年数のルールを追加できます。</p>
                        <button type="button" class="pl-button primary" @click="startCreate">
                            <AddIcon :size="10" />
                            ルール追加
                        </button>
                    </div>
                </aside>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { useApi } from '@/composables/api';
import { useDialog } from '@/composables/dialog';
import { computed, onMounted, reactive, ref } from 'vue';
import AddIcon from '../../Form/AddIcon.vue';
import CloseIcon from '../../Form/CloseIcon.vue';
import { useTheme } from '@/store/theme';

interface PaidLeavePolicy {
    id: number;
    name: string;
    active: boolean;
    effective_from: string | null;
    first_grant_after_months: number;
    annual_grant_interval_months: number;
    expires_after_months: number;
    minimum_attendance_rate: number;
    carryover_enabled: boolean;
    hourly_leave_enabled: boolean;
    hourly_deduction_unit_minutes: number;
    minutes_per_leave_day: number;
    max_hourly_leave_days_per_year: number;
    allow_negative_balance: boolean;
    memo: string | null;
}

interface PaidLeaveRule {
    id: number;
    service_months: number;
    label: string;
    legal_min_days: number;
    grant_days: number;
    active: boolean;
    sort_order: number;
    memo: string | null;
}

interface PaidLeaveSummary {
    rule_count: number;
    active_rule_count: number;
    max_grant_days: number;
    legal_minimum_days_at_6_months: number;
}

interface PaidLeavePayload {
    policy: PaidLeavePolicy;
    rules: PaidLeaveRule[];
    summary: PaidLeaveSummary;
}

interface RuleForm {
    id: number | null;
    service_months: number;
    label: string;
    legal_min_days: number;
    grant_days: number;
    active: boolean;
    sort_order: number;
    memo: string;
}

const api = useApi();
const { ping } = useDialog();
const loading = ref(false);
const savingSettings = ref(false);
const savingRule = ref(false);
const policyForm = ref<PaidLeavePolicy | null>(null);
const rules = ref<PaidLeaveRule[]>([]);
const selectedRuleId = ref<number | null>(null);
const editorOpen = ref(false);
const summary = reactive<PaidLeaveSummary>({
    rule_count: 0,
    active_rule_count: 0,
    max_grant_days: 0,
    legal_minimum_days_at_6_months: 10,
});
const ruleForm = reactive<RuleForm>(emptyRuleForm());
const theme = useTheme()
const underMinimumRules = computed(() => rules.value.filter(rule => Number(rule.grant_days) < Number(rule.legal_min_days)));
const selectedRule = computed(() => rules.value.find(rule => rule.id === selectedRuleId.value) ?? null);
const canSaveRule = computed(() => {
    return Number.isFinite(Number(ruleForm.service_months))
        && Number.isFinite(Number(ruleForm.legal_min_days))
        && Number.isFinite(Number(ruleForm.grant_days))
        && Number(ruleForm.grant_days) >= Number(ruleForm.legal_min_days);
});

onMounted(() => {
    loadPolicy();
});

function emptyRuleForm(): RuleForm {
    return {
        id: null,
        service_months: 6,
        label: '',
        legal_min_days: 10,
        grant_days: 10,
        active: true,
        sort_order: 1,
        memo: '',
    };
}

async function loadPolicy() {
    const response = await api.get('/admin/paid-leave-policy', {}, { loadingRef: loading }) as PaidLeavePayload | null;
    if (!response) {
        return;
    }

    hydrate(response);
}

function hydrate(payload: PaidLeavePayload) {
    policyForm.value = {
        ...payload.policy,
        effective_from: payload.policy.effective_from ?? '',
        memo: payload.policy.memo ?? '',
    };
    rules.value = payload.rules ?? [];
    Object.assign(summary, payload.summary);

    if (selectedRuleId.value) {
        const refreshed = rules.value.find(rule => rule.id === selectedRuleId.value);
        if (refreshed) {
            selectRule(refreshed);
        } else {
            closeEditor();
        }
    }
}

async function saveSettings() {
    if (!policyForm.value) {
        return;
    }

    const payload = {
        ...policyForm.value,
        effective_from: policyForm.value.effective_from || null,
        memo: policyForm.value.memo || null,
    };

    const response = await api.put('/admin/paid-leave-policy/settings', payload, {
        loadingRef: savingSettings,
        toast: '保存しました',
    }) as PaidLeavePayload | null;

    if (response) {
        hydrate(response);
    }
}

function startCreate() {
    const maxRule = rules.value.reduce<PaidLeaveRule | null>((current, rule) => {
        if (!current) return rule;
        return Number(rule.service_months) > Number(current.service_months) ? rule : current;
    }, null);
    const nextMonths = maxRule ? Number(maxRule.service_months) + 12 : 6;
    const nextDays = maxRule ? Number(maxRule.grant_days) : 10;

    Object.assign(ruleForm, {
        ...emptyRuleForm(),
        service_months: nextMonths,
        label: serviceMonthsLabel(nextMonths),
        legal_min_days: nextDays,
        grant_days: nextDays,
        sort_order: nextMonths,
    });
    selectedRuleId.value = null;
    editorOpen.value = true;
}

function selectRule(rule: PaidLeaveRule) {
    selectedRuleId.value = rule.id;
    editorOpen.value = true;
    Object.assign(ruleForm, ruleToForm(rule));
}

function closeEditor() {
    selectedRuleId.value = null;
    editorOpen.value = false;
    Object.assign(ruleForm, emptyRuleForm());
}

function resetRuleForm() {
    if (selectedRule.value) {
        Object.assign(ruleForm, ruleToForm(selectedRule.value));
        return;
    }

    Object.assign(ruleForm, emptyRuleForm());
}

function ruleToForm(rule: PaidLeaveRule): RuleForm {
    return {
        id: rule.id,
        service_months: Number(rule.service_months),
        label: rule.label,
        legal_min_days: Number(rule.legal_min_days),
        grant_days: Number(rule.grant_days),
        active: Boolean(rule.active),
        sort_order: Number(rule.sort_order),
        memo: rule.memo ?? '',
    };
}

async function saveRule() {
    if (!canSaveRule.value) {
        ping('付与日数は法定最低日数以上にしてください。');
        return;
    }

    const payload = {
        service_months: Number(ruleForm.service_months),
        label: ruleForm.label || serviceMonthsLabel(Number(ruleForm.service_months)),
        legal_min_days: Number(ruleForm.legal_min_days),
        grant_days: Number(ruleForm.grant_days),
        active: Boolean(ruleForm.active),
        sort_order: Number(ruleForm.sort_order),
        memo: ruleForm.memo || null,
    };

    const response = ruleForm.id
        ? await api.put(`/admin/paid-leave-policy/rules/${ruleForm.id}`, payload, {
            loadingRef: savingRule,
            toast: '保存しました',
        }) as PaidLeavePayload | null
        : await api.post('/admin/paid-leave-policy/rules', payload, {
            loadingRef: savingRule,
            toast: '作成しました',
        }) as PaidLeavePayload | null;

    if (!response) {
        return;
    }

    hydrate(response);
    const saved = response.rules.find(rule => Number(rule.service_months) === payload.service_months);
    if (saved) {
        selectRule(saved);
    }
}

async function deleteRule() {
    if (!ruleForm.id) {
        return;
    }

    const response = await api.del(`/admin/paid-leave-policy/rules/${ruleForm.id}`, {}, {
        ask: 'この付与ルールを削除しますか？',
        loadingRef: savingRule,
        toast: '削除しました',
    }) as PaidLeavePayload | null;

    if (response) {
        hydrate(response);
        closeEditor();
    }
}

function formatDays(value: number | string) {
    return new Intl.NumberFormat('ja-JP', { maximumFractionDigits: 2 }).format(Number(value) || 0);
}

function serviceMonthsLabel(months: number) {
    if (months < 12) {
        return `${months}ヶ月`;
    }

    const years = Math.floor(months / 12);
    const remainingMonths = months % 12;

    return remainingMonths === 0 ? `${years}年` : `${years}年${remainingMonths}ヶ月`;
}
</script>

<style scoped>
.paid-leave-policy {
    position: relative;
    height: 100%;
    min-height: 0;
    overflow: hidden;
    color: var(--primary-color);
    background: var(--bg3);
}

.policy-header {
    flex: 0 0 auto;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    padding: 16px 20px 12px;
    background: var(--background-color);
    margin: 10px 20px 0px;
}

.policy-header h1,
.section-head h2,
.editor-empty h2 {
    margin: 0;
    font-size: 18px;
    font-weight: 700;
}

.policy-header p,
.editor-empty p {
    margin: 6px 0 0;
    color: var(--third-color);
    font-size: 12px;
}

.header-actions,
.form-actions,
.section-head {
    display: flex;
    align-items: center;
    gap: 8px;
}

.header-actions,
.form-actions {
    flex-wrap: wrap;
    justify-content: flex-end;
}

.pl-button,
.icon-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    border-radius: 0;
    background: var(--primary-button);
    color: #fff;
    cursor: pointer;
    font-size: 12px;
    line-height: 1;
}

.pl-button {
    min-height: 32px;
    padding: 0 12px;
}


.pl-button :deep(svg) {
    fill: #fff;
}

.pl-button.danger {
    color: tomato;
}

.pl-button:disabled {
    cursor: not-allowed;
    opacity: 0.5;
}

.icon-button {
    width: 28px;
    height: 28px;
}

.policy-content {
    flex: 1;
    min-height: 0;
    overflow: hidden auto;
    padding: 20px;
}

.metric-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(120px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.metric-cell,
.settings-panel,
.rules-panel,
.editor-panel {
    background: var(--background-color);
}

.metric-cell {
    padding: 10px 12px;
}

.metric-cell span {
    display: block;
    color: var(--third-color);
    font-size: 11px;
}

.metric-cell strong {
    display: block;
    margin-top: 4px;
    font-size: 18px;
}

.workspace {
    display: grid;
    grid-template-columns: minmax(280px, 0.9fr) minmax(360px, 1.2fr) minmax(300px, 0.9fr);
    gap: 20px;
    align-items: start;
}

.settings-panel,
.rules-panel,
.editor-panel {
    min-height: 260px;
    padding: 14px;
}

.section-head {
    justify-content: space-between;
    margin-bottom: 12px;
}

.section-head.compact {
    margin-top: 18px;
}

.section-head h2 {
    font-size: 15px;
}

.form-grid,
.editor-form {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
}

.editor-form {
    grid-template-columns: 1fr;
}

label {
    display: flex;
    flex-direction: column;
    gap: 5px;
    font-size: 12px;
    color: var(--third-color);
}

label.wide {
    grid-column: 1 / -1;
}

input,
textarea,
select {
    width: 100%;
    min-height: 32px;
    border: 1px solid var(--formBorder);
    border-radius: 0;
    background: var(--background-color);
    color: var(--primary-color);
    font-size: 13px;
    padding: 6px 8px;
    outline: none;
    box-sizing: border-box !important;
}

textarea {
    resize: vertical;
}

.check-field {
    flex-direction: row;
    align-items: center;
    color: var(--primary-color);
}

.check-field input {
    width: auto;
    min-height: auto;
}

.input-with-unit {
    display: flex;
    align-items: center;
}

.input-with-unit input {
    min-width: 0;
}

.input-with-unit span {
    display: inline-flex;
    align-items: center;
    align-self: stretch;
    border: 1px solid var(--formBorder);
    border-left: 0;
    padding: 0 8px;
    color: var(--third-color);
    font-size: 12px;
    white-space: nowrap;
}

.table-scroll {
    overflow: auto;
    max-height: calc(100vh - 285px);
}

.rules-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
    box-sizing: border-box !important;
}

.rules-table th,
.rules-table td {
    border: 1px solid var(--calendarBorder);
    padding: 8px;
    text-align: left;
    vertical-align: middle;
}

.rules-table th {
    position: sticky;
    top: 0;
    z-index: 1;
    background: #363636;
    color: #fff;
    font-weight: 600;
}

.rules-table tbody tr {
    cursor: pointer;
}

.rules-table tbody tr:nth-child(even) {
    background: var(--bg3);
}

.rules-table tbody tr.selected {
    outline: 2px solid var(--third-color);
    outline-offset: -2px;
}

.rules-table tbody tr.invalid {
    background: rgba(180, 35, 24, 0.08);
}

.rules-table strong,
.rules-table small {
    display: block;
}

.rules-table small {
    margin-top: 2px;
    color: var(--third-color);
    font-size: 11px;
}

.empty-cell,
.editor-empty {
    text-align: center;
    color: var(--third-color);
}

.editor-empty {
    display: flex;
    min-height: 220px;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    gap: 10px;
}

.warning-strip {
    margin: 10px 0;
    border: 1px solid #b42318;
    border-radius: 0;
    color: #b42318;
    padding: 8px 10px;
    font-size: 12px;
}

.form-actions {
    margin-top: 14px;
}

@media screen and (max-width: 1200px) {
    .workspace {
        grid-template-columns: 1fr;
    }

    .table-scroll {
        max-height: none;
    }
}

@media screen and (max-width: 720px) {
    .policy-header,
    .header-actions {
        align-items: stretch;
        flex-direction: column;
    }

    .metric-grid,
    .form-grid {
        grid-template-columns: 1fr;
    }

    .policy-content {
        padding: 12px;
    }
}
</style>
