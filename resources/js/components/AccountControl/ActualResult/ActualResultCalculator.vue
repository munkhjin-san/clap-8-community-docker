<template>
    <div class="admin-window actual-result" :data-theme="dataTheme">
        <Transition name="modalFade">
            <div v-if="loading" class="control-loader">
                <div class="spinner-mini" style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
            </div>
        </Transition>

        <header class="pl-topbar" aria-label="実績操作">
            <div class="pl-topbar-actions">
                <MonthPickerNew v-model:year="selectedYear" v-model:month="selectedMonth" @set-date="handleMonthPicked" />
                <button v-if="result?.exists" type="button" class="pl-btn pl-ghost" :disabled="loading" @click="exportCsv">
                    CSV出力
                </button>
                <label class="pl-btn pl-ghost" :class="{ disabled: loading }">
                    CSV選択
                    <input
                        ref="fileInput"
                        type="file"
                        accept=".csv,text/csv"
                        :disabled="loading"
                        @change="handleFileChange"
                    />
                </label>
            </div>
        </header>

        <main class="actual-body">
            <section
                class="file-info"
                :class="{ dragging }"
                @dragenter.prevent="dragging = true"
                @dragover.prevent="dragging = true"
                @dragleave.prevent="dragging = false"
                @drop.prevent="handleDrop"
            >
                <div class="file-info-main">
                    <div class="file-icon">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><path d="M14 2v6h6"></path><line x1="8" y1="13" x2="16" y2="13"></line><line x1="8" y1="17" x2="16" y2="17"></line></svg>
                    </div>
                    <div class="file-info-text">
                        <div class="file-name">{{ fileLabel }}</div>
                        <div class="file-meta">
                            <span class="file-status" :class="fileStatus.tone">● {{ fileStatus.label }}</span>
                            <template v-if="result?.exists">
                                <span class="dot">·</span><span>{{ result.file.period || selectedMonthKey }}</span>
                                <span class="dot">·</span><span>{{ result.file.detail_rows }} 明細</span>
                                <span class="dot">·</span><span>{{ result.summary.departments }} 部門</span>
                                <span class="dot">·</span><span>{{ sourceModeLabel }}</span>
                                <button
                                    v-if="reserveWarnings.length"
                                    type="button"
                                    class="warning-link"
                                    @click="warningOpen = true"
                                >
                                    警告 {{ reserveWarnings.length }}件
                                </button>
                            </template>
                            <template v-else>
                                <span class="dot">·</span><span>{{ emptyMonthMessage }}</span>
                            </template>
                            <span v-if="uploadError" class="upload-error">{{ uploadError }}</span>
                        </div>
                    </div>
                </div>
                <button type="button" class="pl-btn pl-primary" :disabled="!selectedFile || loading" @click="calculate">
                    {{ loading ? '保存中...' : '計算して保存' }}
                </button>
            </section>

            <template v-if="result?.exists">
                <section class="kpi-grid" aria-label="実績サマリー">
                    <div v-for="kpi in kpis" :key="kpi.label" class="kpi-card">
                        <span v-if="kpi.bar" class="kpi-bar"></span>
                        <div class="kpi-label">{{ kpi.label }}</div>
                        <div class="kpi-value" :style="{ color: kpi.color }">{{ kpi.value }}</div>
                        <div class="kpi-sub">{{ kpi.sub }}</div>
                    </div>
                </section>

                <div class="actual-workspace">
                    <section class="table-panel">
                        <div class="toolbar">
                            <label class="search-box">
                                <svg class="search-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"></circle><line x1="21" y1="21" x2="16.5" y2="16.5"></line></svg>
                                <input v-model.trim="search" type="search" placeholder="部門名・番号で検索" />
                            </label>
                            <label class="sort-box">
                                <span>並び順</span>
                                <select v-model="sortKey">
                                    <option value="real_profit">利益順</option>
                                    <option value="normal_profit">通常利益順</option>
                                    <option value="external_sales">売上高順</option>
                                    <option value="internal_sales">内部売上順</option>
                                    <option value="sg_and_a_expenses">販管費順</option>
                                    <option value="cost_of_goods_sold">売上原価順</option>
                                    <option value="indirect_allocation_expense">間接費配賦順</option>
                                    <option value="department">部門名順</option>
                                </select>
                            </label>
                        </div>

                        <div class="table-scroll">
                            <table class="actual-table">
                                <thead>
                                    <tr>
                                        <th class="col-name">部門</th>
                                        <th>売上高</th>
                                        <th>内部売上</th>
                                        <th>売上原価</th>
                                        <th>販管費</th>
                                        <th>間接費配賦</th>
                                        <th>通常利益</th>
                                        <th>利益</th>
                                        <th class="col-edge">利益率</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="row in displayRows"
                                        :key="row.dep.department"
                                        :class="{ selected: selectedDepartmentName === row.dep.department }"
                                        @click="selectDepartment(row.dep)"
                                    >
                                        <td class="main-cell">
                                            <p>{{ row.dep.department }}</p>
                                            <small v-if="row.dep.project_record_id">#{{ row.dep.project_record_id }}</small>
                                        </td>
                                        <td v-for="(cell, index) in row.cells" :key="index" class="num-cell" :style="{ color: cell.color }">{{ cell.text }}</td>
                                        <td class="num-cell profit-cell" :style="{ color: row.profit.color }">{{ row.profit.text }}</td>
                                        <td class="num-cell rate-cell" :style="{ color: row.margin.color }">{{ row.margin.text }}</td>
                                    </tr>
                                    <tr v-if="displayRows.length === 0">
                                        <td colspan="9" class="empty-cell">該当する部門はありません。</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <aside class="detail-panel">
                        <template v-if="selectedDepartment">
                            <div class="detail-header">
                                <div class="detail-head-row">
                                    <div class="detail-title">
                                        <span class="detail-eyebrow">部門明細</span>
                                        <p class="detail-name">{{ selectedDepartment.department }}</p>
                                    </div>
                                    <div class="detail-profit">
                                        <span>利益</span>
                                        <p :style="{ color: selectedDepartment.real_profit < 0 ? 'var(--neg)' : 'var(--text)' }">
                                            {{ formatCurrency(selectedDepartment.real_profit) }}
                                        </p>
                                        <small class="detail-rate" :style="{ color: selectedDepartment.real_profit < 0 ? 'var(--neg)' : 'var(--text-3)' }">
                                            利益率 {{ formatMargin(selectedDepartment.real_margin) }}
                                        </small>
                                    </div>
                                </div>
                                <div class="detail-sub-row">
                                    <small v-if="sourceDepartmentLabel(selectedDepartment)" class="source-departments">
                                        元部門: {{ sourceDepartmentLabel(selectedDepartment) }}
                                    </small>
                                    <small v-else class="source-departments"></small>
                                    <button type="button" class="pl-btn pl-ghost pl-ghost-sm" :disabled="!selectedDepartment.id" @click="openAddAccount">
                                        明細追加
                                    </button>
                                </div>
                            </div>

                            <div class="detail-body">
                                <div class="detail-stats">
                                    <div v-for="(metric, index) in detailMetrics" :key="index" class="stat-cell" :class="{ filler: metric.filler }">
                                        <template v-if="!metric.filler">
                                            <div class="stat-label">{{ metric.label }}</div>
                                            <div class="stat-value" :style="{ color: metric.color }">{{ metric.value }}</div>
                                        </template>
                                    </div>
                                </div>

                                <div class="account-heading">勘定科目明細</div>
                                <div class="account-list">
                                    <div v-for="account in selectedDepartment.accounts" :key="accountKey(account)" class="account-row">
                                        <div class="account-info">
                                            <span :class="['category-pill', account.category]">{{ account.bucket_label || categoryLabel(account.category) }}</span>
                                            <p class="account-name">{{ account.account_name }}</p>
                                            <small>{{ accountDetailLabel(account) }}</small>
                                        </div>
                                        <div class="account-actions">
                                            <div class="account-amount" :style="{ color: account.amount < 0 ? 'var(--neg)' : 'var(--text)' }">
                                                {{ formatCurrency(account.amount) }}
                                            </div>
                                            <button
                                                v-if="selectedDepartment.id && !isCalculatedAccount(account)"
                                                type="button"
                                                class="pl-btn pl-ghost pl-ghost-sm"
                                                @click.stop="editAccount(account)"
                                            >
                                                編集
                                            </button>
                                        </div>
                                    </div>
                                    <div v-if="selectedDepartment.accounts.length === 0" class="account-empty">
                                        明細はありません。
                                    </div>
                                </div>
                            </div>

                            <div class="history-panel">
                                <div class="history-header">
                                    <span class="history-title">編集履歴</span>
                                    <div class="history-header-actions">
                                        <span class="history-count">{{ selectedDepartmentHistories.length }}件</span>
                                        <button
                                            v-if="selectedDepartmentHistories.length > 0"
                                            type="button"
                                            class="pl-btn pl-ghost pl-ghost-sm"
                                            @click="openHistory(selectedDepartmentHistories[0])"
                                        >
                                            履歴を見る
                                        </button>
                                    </div>
                                </div>
                                <div v-if="selectedDepartmentHistories.length === 0" class="history-empty">
                                    この部門の手動編集はまだありません。
                                </div>
                            </div>
                        </template>
                        <div v-else class="empty-detail">
                            <h2>部門を選択</h2>
                            <p>勘定科目別の内訳を確認できます。</p>
                        </div>
                    </aside>
                </div>
            </template>

            <section v-else class="empty-result">
                <h2>{{ selectedMonthKey }} の実績データは未保存です</h2>
                <p>CSVをアップロードして、この月の実績を保存してください。</p>
            </section>
        </main>

        <Modal v-if="editorOpen" custom-class="actual-result-edit-modal" @close="closeAccountEditor">
            <template #title>
                <div class="actual-modal-title">
                    <span>{{ selectedDepartment?.department }}</span>
                    <p class="text-base">{{ editingAccountKey ? '明細編集' : '明細追加' }}</p>
                </div>
            </template>
            <template #content>
                <div class="manual-editor-grid actual-modal-form">
                    <label>
                        <span>既存科目から選択</span>
                        <select v-model="accountForm.template_key" @change="applyAccountTemplate">
                            <option value="">選択なし</option>
                            <option v-for="option in editableAccountOptions" :key="option.account_key" :value="option.account_key">
                                {{ option.account_name }} / {{ option.source_department }}
                            </option>
                        </select>
                    </label>
                    <label>
                        <span>勘定科目名</span>
                        <input v-model.trim="accountForm.account_name" type="text" />
                    </label>
                    <label>
                        <span>区分</span>
                        <select v-model="accountForm.category" @change="handleAccountCategoryChange">
                            <option value="expense">費用</option>
                            <option value="sales">売上</option>
                        </select>
                    </label>
                    <label>
                        <span>金額</span>
                        <input
                            v-model="amountInput"
                            type="text"
                            inputmode="numeric"
                            placeholder="0"
                            @input="handleAmountInput"
                        />
                    </label>
                    <label>
                        <span>メモ</span>
                        <input v-model.trim="accountForm.note" type="text" placeholder="任意" />
                    </label>
                </div>

                <div class="actual-modal-actions">
                    <span v-if="editError" class="upload-error">{{ editError }}</span>
                    <LoaderButton
                        v-if="editingAccountKey"
                        class="!m-0 actual-loader-button"
                        :loading="loading"
                        content="削除"
                        @triggered="deleteAccount"
                    />
                    <LoaderButton
                        class="!m-0 actual-loader-button"
                        :class="{ 'actual-loader-button-disabled': !canSaveAccount || loading }"
                        :loading="loading"
                        content="保存"
                        @triggered="saveAccountIfValid"
                    />
                </div>
            </template>
        </Modal>

        <Modal
            v-if="historyOpen"
            custom-class="actual-result-history-modal"
            disable-scroll
            body-style="height: calc(100% - 80px); overflow: hidden;"
            @close="closeHistory"
        >
            <template #title>
                <div class="actual-modal-title">
                    <span>{{ selectedDepartment?.department }}</span>
                    <p class="text-base">編集履歴</p>
                </div>
            </template>
            <template #content>
                <div class="actual-history-list">
                    <button
                        v-for="history in selectedDepartmentHistories"
                        :key="history.id"
                        type="button"
                        class="history-row"
                        :class="{ selected: selectedHistory?.id === history.id }"
                        @click="selectedHistory = history"
                    >
                        <div>
                            {{ historyActionLabel(history.action) }} / {{ historyAccountName(history) }}
                            <small>{{ formatHistoryDate(history.created_at) }} / {{ history.editor_name || `user:${history.edited_by || '-'}` }}</small>
                        </div>
                        <span>{{ historyAmountLabel(history) }}</span>
                    </button>
                </div>
            </template>
        </Modal>

        <Modal v-if="warningOpen" custom-class="actual-result-warning-modal" @close="warningOpen = false">
            <template #title>
                <div class="actual-modal-title">
                    <span>{{ selectedMonthKey }}</span>
                    <p class="text-base">積立金計算の警告</p>
                </div>
            </template>
            <template #content>
                <ul class="actual-warning-list">
                    <li v-for="(warning, index) in reserveWarnings" :key="index">{{ warning }}</li>
                </ul>
            </template>
        </Modal>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { DateTime, type MonthNumbers } from 'luxon';
import { mkConfig, generateCsv, download } from 'export-to-csv';
import { useApi } from '@/composables/api';
import { useDialog } from '@/composables/dialog';
import { useTheme } from '@/store/theme';
import MonthPickerNew from '@/components/Global/MonthPickerNew.vue';
import Modal from '@/components/Global/Modal.vue';
import LoaderButton from '@/components/Global/LoaderButton.vue';
import type {
    ActualAccount,
    ActualAccountCategory,
    ActualAccountOption,
    ActualDepartment,
    ActualEditHistory,
    ActualResult,
    ActualResultSortKey,
} from '@/interface/actualResultInterface';

type ActualDepartmentExportKey =
    | 'department'
    | 'project_record_id'
    | 'external_sales'
    | 'internal_sales'
    | 'sales'
    | 'cost_of_goods_sold'
    | 'sg_and_a_expenses'
    | 'indirect_allocation_expense'
    | 'normal_profit'
    | 'performance_bonus_reserve'
    | 'real_profit'
    | 'real_margin'
    | 'basic_bonus_reserve'
    | 'paid_leave_reserve'
    | 'welfare_reserve'
    | 'refresh_reserve'
    | 'reserve_transfer_sales';
type ActualResultExportKey = ActualDepartmentExportKey | 'month';

interface DetailMetric {
    label: string;
    value: string;
    color: string;
    filler: boolean;
}

const actualResultExportColumns: { header: string; key: ActualResultExportKey }[] = [
    { header: '月', key: 'month' },
    { header: '部門', key: 'department' },
    { header: 'project_record_id', key: 'project_record_id' },
    { header: '売上高合計', key: 'external_sales' },
    { header: '内部売上高合計', key: 'internal_sales' },
    { header: '売上高', key: 'sales' },
    { header: '売上原価', key: 'cost_of_goods_sold' },
    { header: '販管費合計', key: 'sg_and_a_expenses' },
    { header: '間接費配賦', key: 'indirect_allocation_expense' },
    { header: '通常利益', key: 'normal_profit' },
    { header: '業績連動賞与積立金', key: 'performance_bonus_reserve' },
    { header: '利益', key: 'real_profit' },
    { header: '利益率', key: 'real_margin' },
    { header: '基本賞与積立金', key: 'basic_bonus_reserve' },
    { header: '有給積立金', key: 'paid_leave_reserve' },
    { header: '福利厚生積立金', key: 'welfare_reserve' },
    { header: 'リフレッシュ積立金', key: 'refresh_reserve' },
    { header: '積立振替売上', key: 'reserve_transfer_sales' },
];

const api = useApi();
const dialog = useDialog();
const theme = useTheme();
const showZeroAsDash = true;
const now = DateTime.now();
const fileInput = ref<HTMLInputElement | null>(null);
const selectedYear = ref(now.year);
const selectedMonth = ref(
  now.minus({ months: 1 }).month as MonthNumbers
);
const selectedFile = ref<File | null>(null);
const result = ref<ActualResult | null>(null);
const accountOptions = ref<ActualAccountOption[]>([]);
const editHistories = ref<ActualEditHistory[]>([]);
const selectedDepartmentName = ref('');
const loading = ref(false);
const dragging = ref(false);
const uploadError = ref('');
const editError = ref('');
const search = ref('');
const sortKey = ref<ActualResultSortKey>('real_profit');
const editingAccountKey = ref('');
const editorOpen = ref(false);
const historyOpen = ref(false);
const warningOpen = ref(false);
const selectedHistory = ref<ActualEditHistory | null>(null);
const amountInput = ref('0');
const accountForm = ref({
    template_key: '',
    account_code: '',
    account_name: '',
    category: 'expense' as ActualAccountCategory,
    bucket: 'ordinary_expense',
    bucket_label: '通常経費',
    amount: 0,
    note: '',
});

const selectedMonthKey = computed(() => `${selectedYear.value}-${String(selectedMonth.value).padStart(2, '0')}`);
const emptyMonthMessage = computed(() => `${selectedMonthKey.value} の保存済みデータはありません。CSVをアップロードしてください。`);
const editableAccountOptions = computed(() => accountOptions.value.filter((option) => !isCalculatedSource(option.bucket, option.amount_source || '')));
const canSaveAccount = computed(() => !!selectedDepartment.value?.id && accountForm.value.account_name.trim() !== '');
const selectedDepartmentHistories = computed(() => {
    const department = selectedDepartment.value?.department;

    if (!department) return [];

    return editHistories.value
        .filter((history) => history.department_name === department)
        .slice(0, 8);
});

const filteredDepartments = computed(() => {
    const query = search.value.toLowerCase();
    const rows = [...(result.value?.departments || [])].filter((department) => {
        return !query
            || department.department.toLowerCase().includes(query)
            || (department.source_departments || []).some((source) => source.toLowerCase().includes(query));
    });

    rows.sort((a, b) => {
        if (sortKey.value === 'department') {
            return a.department.localeCompare(b.department, 'ja');
        }

        return (b[sortKey.value] as number) - (a[sortKey.value] as number);
    });

    return rows;
});

const selectedDepartment = computed(() => {
    if (!result.value) return null;

    return result.value.departments.find((department) => department.department === selectedDepartmentName.value)
        || result.value.departments[0]
        || null;
});

const dataTheme = computed(() => (theme.dark ? 'dark' : 'light'));

const fileLabel = computed(() => selectedFile.value?.name
    || result.value?.file.title
    || result.value?.file.name
    || `${selectedMonthKey.value} のCSVファイル`);

const fileStatus = computed(() => {
    if (selectedFile.value) return { label: '未保存（計算待ち）', tone: 'pending' };
    if (result.value?.exists) return { label: '保存済み', tone: 'saved' };

    return { label: '未保存', tone: 'idle' };
});
const reserveWarnings = computed(() => result.value?.file.generated_reserve_warnings || []);
const sourceModeLabel = computed(() => {
    const labels = {
        csv_finalized: '確定CSV',
        reserve_csv_uploaded: '積立CSV',
        auto_calculated: '自動計算',
    } as const;

    return labels[result.value?.file.calculation_source_mode || 'auto_calculated'];
});

const ratio = (part: number, whole: number) => (whole ? `${((part / whole) * 100).toFixed(1)}%` : '—');

const kpis = computed(() => {
    const summary = result.value?.summary;

    if (!summary) return [];

    const sales = summary.external_sales;

    return [
        { label: '売上高', value: formatCurrency(summary.external_sales), sub: `全${summary.departments}部門 合計`, color: 'var(--text)', bar: false },
        { label: '内部売上', value: formatCurrency(summary.internal_sales), sub: `売上高比 ${ratio(summary.internal_sales, sales)}`, color: 'var(--text)', bar: false },
        { label: '売上原価', value: formatCurrency(summary.cost_of_goods_sold), sub: `原価率 ${ratio(summary.cost_of_goods_sold, sales)}`, color: 'var(--text)', bar: false },
        { label: '販管費', value: formatCurrency(summary.sg_and_a_expenses), sub: `売上高比 ${ratio(summary.sg_and_a_expenses, sales)}`, color: 'var(--text)', bar: false },
        { label: '間接費配賦', value: formatCurrency(summary.indirect_allocation_expense), sub: `売上高比 ${ratio(summary.indirect_allocation_expense, sales)}`, color: 'var(--text)', bar: false },
        { label: '通常利益', value: formatCurrency(summary.normal_profit), sub: `利益率 ${ratio(summary.normal_profit, sales)}`, color: summary.normal_profit < 0 ? 'var(--neg)' : 'var(--pos)', bar: false },
        { label: '業績賞与', value: formatCurrency(summary.performance_bonus_reserve), sub: `売上高比 ${ratio(summary.performance_bonus_reserve, sales)}`, color: 'var(--text)', bar: false },
        { label: '利益', value: formatCurrency(summary.real_profit), sub: `利益率 ${summary.real_margin === null ? ratio(summary.real_profit, sales) : formatMargin(summary.real_margin)}`, color: summary.real_profit < 0 ? 'var(--neg)' : 'var(--pos)', bar: true },
    ];
});

const numCell = (value: number, baseColor = 'var(--text)', isProfit = false) => {
    if (value === 0) return { text: showZeroAsDash ? '—' : formatCurrency(0), color: 'var(--text-3)' };

    return {
        text: formatCurrency(value),
        color: isProfit ? (value < 0 ? 'var(--neg)' : 'var(--text)') : baseColor,
    };
};

const displayRows = computed(() => filteredDepartments.value.map((department) => ({
    dep: department,
    cells: [
        numCell(department.external_sales, 'var(--text)'),
        numCell(department.internal_sales, 'var(--text-2)'),
        numCell(department.cost_of_goods_sold, 'var(--text-2)'),
        numCell(department.sg_and_a_expenses, 'var(--text-2)'),
        numCell(department.indirect_allocation_expense, 'var(--text-2)'),
        numCell(department.normal_profit, 'var(--text)', true),
    ],
    profit: numCell(department.real_profit, 'var(--text)', true),
    margin: {
        text: formatMargin(department.real_margin),
        color: department.real_profit < 0 ? 'var(--neg)' : 'var(--text-2)',
    },
})));

const detailMetrics = computed<DetailMetric[]>(() => {
    const department = selectedDepartment.value;

    if (!department) return [];

    const dash = showZeroAsDash ? '—' : formatCurrency(0);
    const amount = (label: string, value: number, profit = false): DetailMetric => {
        if (value === 0) return { label, value: dash, color: 'var(--text-3)', filler: false };

        return {
            label,
            value: formatCurrency(value),
            color: profit ? (value < 0 ? 'var(--neg)' : 'var(--pos)') : 'var(--text)',
            filler: false,
        };
    };

    return [
        amount('売上高', department.external_sales),
        amount('内部売上', department.internal_sales),
        amount('売上原価', department.cost_of_goods_sold),
        amount('販管費', department.sg_and_a_expenses),
        amount('間接費配賦', department.indirect_allocation_expense),
        amount('通常利益', department.normal_profit, true),
        amount('業績賞与', department.performance_bonus_reserve),
        amount('利益', department.real_profit, true),
        { label: '利益率', value: formatMargin(department.real_margin), color: department.real_profit < 0 ? 'var(--neg)' : 'var(--pos)', filler: false },
        { label: '通常利益率', value: formatMargin(department.margin), color: department.normal_profit < 0 ? 'var(--neg)' : 'var(--pos)', filler: false },
        amount('基本賞与', department.basic_bonus_reserve),
        amount('有給', department.paid_leave_reserve),
        amount('福利厚生', department.welfare_reserve),
        amount('リフレッシュ', department.refresh_reserve),
        amount('振替売上', department.reserve_transfer_sales),
    ];
});

const handleMonthPicked = async ({ year, month }: { year: number; month: MonthNumbers }) => {
    selectedYear.value = year;
    selectedMonth.value = month;
    await loadMonth();
};

const loadMonth = async () => {
    uploadError.value = '';
    editError.value = '';

    const data = await api.get('/admin/actual-results', { month: selectedMonthKey.value }, {
        loadingRef: loading,
        silent: true,
        cancel: true,
    }) as ActualResult | { exists: false } | null;

    if (data?.exists) {
        result.value = data as ActualResult;
        selectedDepartmentName.value = result.value.departments[0]?.department || '';
        await loadAccountOptions();
        await loadEditHistories();
    } else {
        result.value = null;
        selectedDepartmentName.value = '';
        accountOptions.value = [];
        editHistories.value = [];
    }

    selectedFile.value = null;
    resetFileInput();
    closeAccountEditor();
    closeHistory();
};

const loadAccountOptions = async () => {
    if (!result.value?.exists) {
        accountOptions.value = [];
        return;
    }

    const data = await api.get('/admin/actual-results/account-options', { month: selectedMonthKey.value }, {
        silent: true,
        cancel: true,
    }) as { options: ActualAccountOption[] } | null;

    accountOptions.value = data?.options || [];
};

const loadEditHistories = async () => {
    if (!result.value?.exists) {
        editHistories.value = [];
        return;
    }

    const data = await api.get('/admin/actual-results/edit-histories', { month: selectedMonthKey.value }, {
        silent: true,
        cancel: true,
    }) as { histories: ActualEditHistory[] } | null;

    editHistories.value = data?.histories || [];
};

const handleFileChange = (event: Event) => {
    const input = event.target as HTMLInputElement;
    setFile(input.files?.[0] || null);
};

const handleDrop = (event: DragEvent) => {
    dragging.value = false;
    setFile(event.dataTransfer?.files?.[0] || null);
};

const setFile = (file: File | null) => {
    uploadError.value = '';

    if (!file) {
        selectedFile.value = null;
        return;
    }

    if (!file.name.toLowerCase().endsWith('.csv')) {
        uploadError.value = 'CSVファイルを選択してください。';
        selectedFile.value = null;
        return;
    }

    selectedFile.value = file;
};

const calculate = async () => {
    if (!selectedFile.value) {
        uploadError.value = 'CSVファイルを選択してください。';
        return;
    }

    const hasManualEdits = result.value?.departments.some((department) => department.manual_adjusted) ?? false;

    if (result.value?.exists) {
        const question = hasManualEdits
            ? `${selectedMonthKey.value} は保存済みで、手動編集もあります。編集内容を破棄してCSVで上書きしますか？`
            : `${selectedMonthKey.value} の保存済み実績を、このCSVで上書きしますか？`;
        const confirmed = await dialog.ask(question, {
            answers: [
                { value: true, label: '上書き' },
                { value: false, label: 'キャンセル' },
            ],
        });

        if (!confirmed.value) return;
    }

    await submitCalculation({
        overwriteConfirmed: !!result.value?.exists,
        discardManualEdits: hasManualEdits,
    });
};

type CalculationConfirmations = {
    overwriteConfirmed: boolean;
    discardManualEdits: boolean;
};

const submitCalculation = async (confirmations: CalculationConfirmations) => {
    if (!selectedFile.value) return;

    const formData = new FormData();
    formData.append('file', selectedFile.value);
    formData.append('month', selectedMonthKey.value);
    formData.append('overwrite_confirmed', confirmations.overwriteConfirmed ? '1' : '0');
    formData.append('discard_manual_edits', confirmations.discardManualEdits ? '1' : '0');

    try {
        const data = await api.post('/admin/actual-results/calculate', formData, {
            loadingRef: loading,
            toast: '実績を保存しました。',
            silent: true,
        }) as ActualResult | null;

        if (data) {
            result.value = data;
            selectedDepartmentName.value = data.departments[0]?.department || '';
            uploadError.value = '';
            selectedFile.value = null;
            resetFileInput();
            await loadAccountOptions();
            await loadEditHistories();
            closeAccountEditor();
            closeHistory();
            warningOpen.value = false;
        }
    } catch (error: any) {
        const errors = error?.response?.data?.errors as Record<string, string[]> | undefined;

        uploadError.value = Object.values(errors || {})[0]?.[0]
            || error?.response?.data?.message
            || 'CSVの計算または保存に失敗しました。';
    }
};

const exportCsv = () => {
    if (!result.value?.exists) return;

    const rows = result.value.departments.map((department) => {
        return actualResultExportColumns.reduce<Record<string, string | number | null>>((row, column) => {
            row[column.header] = column.key === 'month'
                ? selectedMonthKey.value
                : department[column.key] ?? '';

            return row;
        }, {});
    });

    const csvConfig = mkConfig({
        useKeysAsHeaders: true,
        filename: `actual-results-${selectedMonthKey.value}`,
        useBom: true,
        replaceUndefinedWith: '',
    });
    const csv = generateCsv(csvConfig)(rows);
    download(csvConfig)(csv);
};

const selectDepartment = (department: ActualDepartment) => {
    selectedDepartmentName.value = department.department;
    closeAccountEditor();
    closeHistory();
};

const resetAccountForm = () => {
    editingAccountKey.value = '';
    editError.value = '';
    accountForm.value = {
        template_key: '',
        account_code: '',
        account_name: '',
        category: 'expense',
        bucket: 'ordinary_expense',
        bucket_label: '通常経費',
        amount: 0,
        note: '',
    };
    amountInput.value = '0';
};

const openAddAccount = () => {
    resetAccountForm();
    editorOpen.value = true;
};

const closeAccountEditor = () => {
    editorOpen.value = false;
    resetAccountForm();
};

const editAccount = (account: ActualAccount) => {
    editingAccountKey.value = accountKey(account);
    editError.value = '';
    accountForm.value = {
        template_key: '',
        account_code: account.account_code || '',
        account_name: account.account_name || '',
        category: account.category,
        bucket: account.bucket || 'ordinary_expense',
        bucket_label: account.bucket_label || '',
        amount: account.amount || 0,
        note: '',
    };
    amountInput.value = formatMoneyInput(account.amount || 0);
    editorOpen.value = true;
};

const applyAccountTemplate = () => {
    const option = accountOptions.value.find((row) => row.account_key === accountForm.value.template_key);
    if (!option) return;

    accountForm.value.account_code = option.account_code || '';
    accountForm.value.account_name = option.account_name;
    accountForm.value.category = option.category;
    accountForm.value.bucket = option.bucket;
    accountForm.value.bucket_label = option.bucket_label;
};

const handleAccountCategoryChange = () => {
    accountForm.value.template_key = '';
    accountForm.value.bucket = accountForm.value.category === 'sales' ? 'operating_sales' : 'ordinary_expense';
    accountForm.value.bucket_label = accountForm.value.category === 'sales' ? '売上' : '通常経費';
};

const handleAmountInput = () => {
    const raw = amountInput.value;

    if (raw.trim() === '-') {
        accountForm.value.amount = 0;
        return;
    }

    const amount = parseMoneyInput(raw);
    accountForm.value.amount = amount;
    amountInput.value = formatMoneyInput(amount);
};

const saveAccount = async () => {
    const department = selectedDepartment.value;
    if (!department?.id) return;
    const actionLabel = editingAccountKey.value ? '更新' : '追加';

    try {
        const data = await api.patch(`/admin/actual-results/departments/${department.id}/accounts`, {
            account_key: editingAccountKey.value || null,
            account: {
                account_code: accountForm.value.account_code,
                account_name: accountForm.value.account_name,
                category: accountForm.value.category,
                bucket: accountForm.value.bucket,
                bucket_label: accountForm.value.bucket_label,
                amount: accountForm.value.amount,
            },
            note: accountForm.value.note || null,
        }, {
            loadingRef: loading,
        }) as ActualResult | null;

        await applySavedResult(data);
        dialog.toast(`明細を${actionLabel}しました。`);
    } catch (error) {
        editError.value = '明細の保存に失敗しました。';
    }
};

const saveAccountIfValid = () => {
    if (!canSaveAccount.value || loading.value) return;
    void saveAccount();
};

const deleteAccount = async () => {
    const department = selectedDepartment.value;
    if (!department?.id || !editingAccountKey.value) return;

    const confirmed = await dialog.ask('この明細を削除しますか？', {
        answers: [
            { value: true, label: '削除' },
            { value: false, label: 'キャンセル' },
        ],
    });

    if (!confirmed.value) {
        return;
    }

    try {
        const data = await api.patch(`/admin/actual-results/departments/${department.id}/accounts`, {
            account_key: editingAccountKey.value,
            delete: true,
            note: accountForm.value.note || null,
        }, {
            loadingRef: loading,
        }) as ActualResult | null;

        await applySavedResult(data);
        dialog.toast('明細を削除しました。');
    } catch (error) {
        editError.value = '明細の削除に失敗しました。';
    }
};

const openHistory = (history: ActualEditHistory) => {
    selectedHistory.value = history;
    historyOpen.value = true;
};

const closeHistory = () => {
    historyOpen.value = false;
    selectedHistory.value = null;
};

const applySavedResult = async (data: ActualResult | null) => {
    if (!data) return;

    const previousDepartment = selectedDepartment.value?.department || data.departments[0]?.department || '';
    result.value = data;
    selectedDepartmentName.value = data.departments.some((department) => department.department === previousDepartment)
        ? previousDepartment
        : data.departments[0]?.department || '';
    closeAccountEditor();
    closeHistory();
    await loadAccountOptions();
    await loadEditHistories();
};

const resetFileInput = () => {
    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

const formatCurrency = (value: number) => `${new Intl.NumberFormat('ja-JP').format(Math.round(value || 0))}円`;
const formatMoneyInput = (value: number) => new Intl.NumberFormat('ja-JP').format(Math.round(value || 0));
const parseMoneyInput = (value: string) => {
    const negative = value.trim().startsWith('-');
    const digits = value.replace(/[^\d]/g, '');
    const amount = digits === '' ? 0 : Number(digits);

    return negative ? -amount : amount;
};
const formatMargin = (value: number | null) => value === null ? '-' : `${Number(value).toFixed(1)}%`;
const categoryLabel = (category: ActualAccountCategory) => category === 'sales' ? '売上' : '費用';
const accountKey = (account: ActualAccount) => account.account_key || `${account.account_code}|${account.account_name}|${account.category}|${account.bucket}`;
const sourceDepartmentLabel = (department: ActualDepartment) => {
    const sources = department.source_departments || [];

    if (sources.length <= 1 && sources[0] === department.department) {
        return '';
    }

    return sources.join(' / ');
};
const accountDetailLabel = (account: ActualAccount) => {
    const base = `${account.account_code || 'コードなし'} / ${account.rows}行`;
    const sources = account.source_departments || [];

    return sources.length > 1 ? `${base} / ${sources.join(' / ')}` : base;
};
const historyActionLabel = (action: string) => {
    if (action === 'add_account') return '追加';
    if (action === 'update_account') return '更新';
    if (action === 'delete_account') return '削除';

    return action;
};
const historyAccountName = (history: ActualEditHistory) => {
    return history.after_value?.account_name
        || history.before_value?.account_name
        || history.account_key
        || '明細';
};
const historyAmountLabel = (history: ActualEditHistory) => {
    const before = history.before_value?.amount;
    const after = history.after_value?.amount;

    if (before === undefined && after === undefined) return '';
    if (before === undefined) return formatCurrency(after || 0);
    if (after === undefined) return `${formatCurrency(before)} → 削除`;

    return `${formatCurrency(before)} → ${formatCurrency(after)}`;
};
const formatHistoryDate = (value: string | null) => {
    if (!value) return '-';

    const date = DateTime.fromISO(value);

    return date.isValid ? date.toFormat('yyyy/MM/dd HH:mm') : value;
};
const isCalculatedAccount = (account: ActualAccount) => isCalculatedSource(account.bucket, account.amount_source || '');
const isCalculatedSource = (bucket: string, amountSource: string) => {
    return [
        'performance_bonus_reserve',
        'indirect_allocation_expense',
        'reserve_transfer_sales',
        'indirect_allocation_sales',
    ].includes(bucket) || [
        'generated_charge',
        'generated_internal_sales',
        'generated_bonus_accrual',
        'timecard_kintone',
    ].includes(amountSource);
};

onMounted(loadMonth);
</script>

<style scoped>
.actual-result {
    --bg: var(--bg3);
    --surface: var(--background-color);
    --surface-2: var(--secondary-background);
    --border: var(--normalBorder);
    --border-2: var(--formBorder);
    --text: var(--primary-color);
    --text-2: var(--sub-color);
    --btn: var(--primary-button);

    display: flex;
    flex-direction: column;
    height: 100%;
    min-height: 0;
    overflow: hidden;
    color: var(--text);
    background: var(--bg);
    font-family: 'Noto Sans JP', -apple-system, BlinkMacSystemFont, sans-serif;
}

.actual-result[data-theme="dark"] {
    --text-3: #898989;
    --accent: #4ec98a;
    --accent-soft: rgba(78, 201, 138, 0.16);
    --selected: #454545;
    --pos: #4ec98a;
    --neg: #f28b82;
    --exp: #e8a85c;
    --exp-soft: rgba(232, 168, 92, 0.16);
    --btn-text: #e4e6eb;
    --shadow: 0 1px 2px rgba(0, 0, 0, 0.35);
}

.actual-result[data-theme="light"] {
    --text-3: #b0b0b0;
    --accent: #1f9d57;
    --accent-soft: rgba(31, 157, 87, 0.13);
    --selected: #dcdcdc;
    --pos: #1f9d57;
    --neg: #d93025;
    --exp: #c2730f;
    --exp-soft: rgba(194, 115, 15, 0.13);
    --btn-text: #ffffff;
    --shadow: 0 1px 2px rgba(20, 30, 55, 0.06), 0 1px 3px rgba(20, 30, 55, 0.05);
}

/* buttons */
.pl-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    height: 32px;
    padding: 0 13px;
    border: 1px solid var(--border-2);
    background: var(--surface);
    color: var(--text);
    font-family: inherit;
    font-size: 13px;
    white-space: nowrap;
    cursor: pointer;
}

.pl-btn:disabled,
.pl-btn.disabled {
    opacity: 0.45;
    cursor: not-allowed;
    pointer-events: none;
}

.pl-ghost {
    color: var(--text-2);
}

.pl-ghost:hover {
    background: var(--surface-2);
    color: var(--text);
}

.pl-ghost-sm {
    height: 28px;
    padding: 0 12px;
    font-size: 12px;
    color: var(--text);
}

.pl-primary {
    height: 34px;
    padding: 0 16px;
    border-color: var(--btn);
    background: var(--btn);
    color: var(--btn-text);
}

.pl-primary:hover:not(:disabled) {
    filter: brightness(1.15);
}

.pl-btn input[type="file"] {
    display: none;
}

/* top bar */
.pl-topbar {
    flex: 0 0 auto;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    padding: 14px 22px;
    border-bottom: 1px solid var(--border);
    background: var(--bg);
}

.pl-topbar-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

/* body */
.actual-body {
    flex: 1;
    min-height: 0;
    display: flex;
    flex-direction: column;
    gap: 14px;
    padding: 18px 22px;
    overflow: hidden;
}

/* file info */
.file-info {
    flex: 0 0 auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 13px 16px;
    border: 1px solid var(--border);
    background: var(--surface);
    box-shadow: var(--shadow);
}

.file-info.dragging {
    border-color: var(--accent);
    box-shadow: 0 0 0 2px var(--accent-soft);
}

.file-info-main {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
}

.file-icon {
    flex: none;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 34px;
    height: 34px;
    background: var(--surface-2);
    color: var(--text-2);
}

.file-info-text {
    min-width: 0;
}

.file-name {
    overflow: hidden;
    font-size: 13.5px;
    color: var(--text);
    text-overflow: ellipsis;
    white-space: nowrap;
}

.file-meta {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 3px;
    font-size: 11.5px;
    color: var(--text-3);
    flex-wrap: wrap;
}

.file-meta .dot {
    opacity: 0.5;
}

.file-status.saved {
    color: var(--pos);
}

.file-status.pending {
    color: var(--exp);
}

.file-status.idle {
    color: var(--text-3);
}

.upload-error {
    color: var(--exp);
}

.warning-link {
    padding: 0;
    border: 0;
    background: transparent;
    color: var(--exp);
    font: inherit;
    cursor: pointer;
    text-decoration: underline;
}

/* KPI cards */
.kpi-grid {
    flex: 0 0 auto;
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 11px;
}

.kpi-card {
    position: relative;
    padding: 13px 15px 12px;
    overflow: hidden;
    border: 1px solid var(--border);
    background: var(--surface);
    box-shadow: var(--shadow);
}

.kpi-bar {
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 3px;
    background: var(--pos);
}

.kpi-label {
    font-size: 12px;
    color: var(--text-2);
}

.kpi-value {
    margin-top: 5px;
    font-size: 23px;
    line-height: 1.1;
    letter-spacing: -0.01em;
    font-variant-numeric: tabular-nums;
}

.kpi-sub {
    margin-top: 4px;
    font-size: 11px;
    color: var(--text-3);
}

/* workspace */
.actual-workspace {
    flex: 1;
    min-height: 0;
    display: flex;
    gap: 14px;
}

/* table panel */
.table-panel {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    border: 1px solid var(--border);
    background: var(--surface);
    box-shadow: var(--shadow);
}

.toolbar {
    flex: 0 0 auto;
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    border-bottom: 1px solid var(--border);
}

.search-box {
    position: relative;
    flex: 1;
    min-width: 0;
}

.search-icon {
    position: absolute;
    left: 11px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-3);
    pointer-events: none;
}

.search-box input {
    padding-left: 33px;
}

.sort-box {
    flex: none;
    display: flex;
    align-items: center;
    gap: 8px;
}

.sort-box span {
    font-size: 12px;
    color: var(--text-3);
    white-space: nowrap;
}

.sort-box select {
    width: auto;
    cursor: pointer;
}

input,
select {
    width: 100%;
    height: 34px;
    min-height: 34px;
    padding: 6px 12px;
    border: 1px solid var(--normalBorder);
    background: var(--secondary-background);
    color: var(--primary-color);
    font-family: inherit;
    font-size: 13px;
    line-height: 1.4;
    outline: none;
    box-sizing: border-box !important;
}

input:focus,
select:focus {
    border-color: var(--accent, #4ec98a);
}

.table-scroll {
    flex: 1;
    overflow: auto;
}

.actual-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}

.actual-table th {
    position: sticky;
    top: 0;
    z-index: 2;
    padding: 10px 14px;
    border-bottom: 1px solid var(--border-2);
    background: var(--surface-2);
    color: var(--text-3);
    font-size: 11px;
    letter-spacing: 0.03em;
    text-align: right;
    white-space: nowrap;
}

.actual-table th.col-name {
    text-align: left;
}

.actual-table th.col-edge {
    padding-right: 16px;
}

.actual-table td {
    padding: 9px 14px;
    border-bottom: 1px solid var(--border);
    white-space: nowrap;
}

.actual-table tbody tr {
    cursor: pointer;
}

.actual-table tbody tr:hover td {
    background: var(--surface-2);
}

.actual-table tbody tr.selected td {
    background: var(--selected);
}

.actual-table tbody tr.selected td:first-child {
    box-shadow: inset 3px 0 0 var(--text-2);
}

.num-cell {
    text-align: right;
    font-variant-numeric: tabular-nums;
}



.rate-cell {
    padding-right: 16px;
}

.main-cell p {
    font-size: 13px;
    color: var(--text);
}

.main-cell small {
    display: block;
    margin-top: 1px;
    font-size: 11px;
    color: var(--text-3);
}

.empty-cell {
    padding: 34px 12px !important;
    text-align: center;
    color: var(--text-3);
}

/* detail panel */
.detail-panel {
    width: 372px;
    flex: none;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    border: 1px solid var(--border);
    background: var(--surface);
    box-shadow: var(--shadow);
}

.detail-header {
    flex: 0 0 auto;
    padding: 17px 18px 15px;
    border-bottom: 1px solid var(--border);
}

.detail-head-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 12px;
}

.detail-title {
    min-width: 0;
}

.detail-eyebrow {
    font-size: 11px;
    letter-spacing: 0.07em;
    color: var(--text-3);
}

.detail-name {
    margin-top: 4px;
    overflow: hidden;
    font-size: 17px;
    color: var(--text);
    text-overflow: ellipsis;
    white-space: nowrap;
}

.detail-profit {
    flex: none;
    text-align: right;
}

.detail-profit span {
    font-size: 11px;
    color: var(--text-3);
}

.detail-profit p {
    margin-top: 2px;
    font-size: 17px;
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
}

.detail-rate {
    display: block;
    margin-top: 3px;
    font-size: 11px;
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
}

.detail-sub-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    margin-top: 11px;
}

.source-departments {
    flex: 1;
    min-width: 0;
    overflow: hidden;
    font-size: 11.5px;
    color: var(--text-3);
    text-overflow: ellipsis;
    white-space: nowrap;
}

.detail-body {
    flex: 1;
    min-height: 0;
    overflow: auto;
    padding: 15px 18px;
}

.detail-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1px;
    overflow: hidden;
    border: 1px solid var(--border);
    background: var(--border);
}

.stat-cell {
    min-height: 46px;
    padding: 9px 11px;
    background: var(--surface);
}

.stat-label {
    font-size: 10px;
    color: var(--text-3);
}

.stat-value {
    margin-top: 3px;
    font-size: 12px;
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
}

.account-heading {
    margin: 20px 0 6px;
    font-size: 11px;
    letter-spacing: 0.05em;
    color: var(--text-3);
}

.account-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    padding: 11px 0;
    border-bottom: 1px solid var(--border);
}

.account-info {
    min-width: 0;
}

.account-name {
    margin-top: 4px;
    overflow: hidden;
    font-size: 12.5px;
    color: var(--text);
    text-overflow: ellipsis;
    white-space: nowrap;
}

.account-info small {
    display: block;
    margin-top: 1px;
    overflow: hidden;
    font-size: 11px;
    color: var(--text-3);
    text-overflow: ellipsis;
    white-space: nowrap;
}

.account-actions {
    flex: none;
    display: flex;
    align-items: center;
    gap: 8px;
}

.account-amount {
    font-size: 12.5px;
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
}

.account-empty {
    padding: 16px 0;
    font-size: 12px;
    color: var(--text-3);
}

.category-pill {
    display: inline-block;
    padding: 2px 7px;
    font-size: 10px;
}

.category-pill.sales {
    background: var(--accent-soft);
    color: var(--accent);
}

.category-pill.expense {
    background: var(--exp-soft);
    color: var(--exp);
}

/* history footer */
.history-panel {
    flex: 0 0 auto;
    padding: 12px 18px;
    border-top: 1px solid var(--border);
    background: var(--surface-2);
}

.history-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
}

.history-title {
    font-size: 12px;
    color: var(--text-2);
}

.history-header-actions {
    display: flex;
    align-items: center;
    gap: 8px;
}

.history-count {
    font-size: 12px;
    color: var(--text-3);
}

.history-empty {
    margin-top: 4px;
    font-size: 11px;
    color: var(--text-3);
}

/* empty states */
.empty-result,
.empty-detail {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 6px;
    color: var(--text-3);
    text-align: center;
}

.empty-result {
    flex: 1;
    border: 1px solid var(--border);
    background: var(--surface);
    box-shadow: var(--shadow);
}

.empty-detail {
    flex: 1;
    padding: 20px;
}

.empty-result h2,
.empty-detail h2 {
    margin: 0;
    font-size: 18px;
    color: var(--text);
}

.empty-result p,
.empty-detail p {
    margin: 0;
    font-size: 12px;
}

/* modals (teleported - use global theme vars) */
.manual-editor-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
}

.actual-modal-form {
    grid-template-columns: 1fr;
}

.actual-modal-form label {
    display: flex;
    flex-direction: column;
    gap: 5px;
    font-size: 12px;
    color: var(--primary-color);
}

.actual-modal-title span {
    display: block;
    font-size: 11px;
    color: gray;
}

.actual-modal-title p {
    margin-top: 4px;
    font-size: 16px;
}

.actual-modal-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 12px;
    margin-top: 16px;
}

.actual-modal-actions .upload-error {
    margin-right: auto;
}

.actual-loader-button {
    min-width: 86px;
}

.actual-loader-button-disabled {
    opacity: 0.55;
    pointer-events: none;
}

.actual-history-list {
    height: 100%;
    display: grid;
    align-content: start;
    gap: 2px;
    overflow: auto;
    border-top: 1px solid var(--formBorder);
}

.actual-warning-list {
    display: grid;
    gap: 8px;
    margin: 0;
    padding: 0 0 0 18px;
    color: var(--primary-color);
    font-size: 13px;
}

.history-row {
    width: 100%;
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 8px;
    padding: 8px 10px;
    border: 0;
    border-top: 1px solid var(--formBorder);
    background: transparent;
    color: var(--primary-color);
    cursor: pointer;
    text-align: left;
    font-size: 12px;
}

.history-row:hover,
.history-row.selected {
    background: var(--secondary-background);
}

.history-row small {
    display: block;
    margin-top: 2px;
    color: gray;
    font-size: 11px;
}

.history-row span {
    align-self: center;
    color: gray;
    white-space: nowrap;
}

:deep(.actual-result-edit-modal) {
    width: min(520px, calc(100vw - 40px)) !important;
    height: auto !important;
    max-height: calc(100vh - 80px);
}

:deep(.actual-result-history-modal) {
    width: min(640px, calc(100vw - 40px)) !important;
    height: min(640px, calc(100vh - 80px)) !important;
}

@media screen and (max-width: 1180px) {
    .kpi-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .actual-workspace {
        flex-direction: column;
    }

    .detail-panel {
        width: 100%;
        min-height: 520px;
    }
}
</style>
