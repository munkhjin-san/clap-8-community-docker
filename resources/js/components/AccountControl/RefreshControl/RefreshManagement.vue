<template>
    <div class="refresh-management">
        <section class="header-strip">
            <div class="metric-row">
                <article class="metric-card">
                    <span>対象者</span>
                    <strong>{{ eligibleCount }}</strong>
                </article>
                <article class="metric-card">
                    <span>そのまま付与</span>
                    <strong>{{ readyCount }}</strong>
                </article>
                <article class="metric-card">
                    <span>要調整</span>
                    <strong>{{ reviewCount }}</strong>
                </article>
            </div>

            <div class="header-side">
                <div class="source-row">
                    <span class="source-chip eligible">対象: 正社員/ 契約社員</span>
                    <span class="source-chip manual">年次付与: 手動</span>
                    <span class="source-chip auto">グラウドナイン: 自動</span>
                    <span class="source-chip auto">チャレンジ: 自動</span>
                </div>

                <button type="button" class="sync-button" :disabled="syncing || loading || saving" @click="syncKintone">
                    {{ syncing ? '同期中' : 'Kintone同期' }}
                </button>
            </div>
        </section>

        <section class="workflow-layout">
            <aside class="queue-panel">
                <div class="panel-head">
                    <h2>対象者一覧</h2>
                    <span class="panel-badge">{{ filteredEmployees.length }}名</span>
                </div>

                <div class="queue-filter">
                    <input
                        v-model.trim="searchWord"
                        type="text"
                        placeholder="社員名で検索"
                    >

                    <div class="filter-pills">
                        <button
                            v-for="filter in filters"
                            :key="filter.key"
                            type="button"
                            :class="['filter-pill', { active: activeFilter === filter.key }]"
                            @click="activeFilter = filter.key"
                        >
                            {{ filter.label }}
                        </button>
                    </div>
                </div>

                <div class="queue-list">
                    <div v-if="loading" class="queue-empty">
                        読込中
                    </div>
                    <div v-else-if="!filteredEmployees.length" class="queue-empty">
                        対象者なし
                    </div>
                    <button
                        v-for="employee in filteredEmployees"
                        :key="employee.id"
                        type="button"
                        :class="['queue-item', { active: selectedEmployeeId === employee.id }]"
                        @click="selectedEmployeeId = employee.id"
                    >
                        <div class="queue-main">
                            <UserPanel :user="employee.user" size="30" disable-instant imgClass="userNormalIcon" />
                            <div class="queue-copy">
                                <strong>{{ employee.user.name }}</strong>
                                <p>{{ employmentTypeLabel(employee.positionId) }} ・ {{ formatDisplayDate(employee.user.joined_date) }} ・ {{ yearsWorkedLabel(employee.user.joined_date) }}</p>
                            </div>
                        </div>

                        <div class="queue-side">
                            <span :class="['status-badge', statusMeta(employee.status).tone]">{{ statusMeta(employee.status).label }}</span>
                            <p class="status-note">{{ employee.statusReason }}</p>
                            <strong>{{ formatCurrency(employee.suggestedAmount) }}</strong>
                        </div>
                    </button>
                </div>
            </aside>

            <section v-if="selectedEmployee" class="detail-panel">
                <div class="detail-head">
                    <div class="detail-identity">
                        <UserPanel :user="selectedEmployee.user" size="40" disable-instant imgClass="userNormalIcon" />
                        <div>
                            <h2>{{ selectedEmployee.user.name }}</h2>
                            <p>{{ employmentTypeLabel(selectedEmployee.positionId) }} ・ {{ formatDisplayDate(selectedEmployee.user.joined_date) }} ・ {{ yearsWorkedLabel(selectedEmployee.user.joined_date) }}</p>
                        </div>
                    </div>

                    <div class="detail-actions">
                        <span :class="['status-badge', statusMeta(selectedEmployee.status).tone]">
                            {{ statusMeta(selectedEmployee.status).label }}
                        </span>
                        <button type="button" class="add-grant-button" @click="toggleGrantEditor">
                            {{ grantEditorButtonLabel }}
                        </button>
                    </div>
                </div>

                <div class="summary-grid">
                    <div class="summary-card">
                        <span>現在保有額</span>
                        <strong>{{ formatCurrency(selectedEmployee.currentBalance) }}</strong>
                    </div>
                    <div class="summary-card">
                        <span>累計付与</span>
                        <strong>{{ formatCurrency(selectedEmployee.totalGranted) }}</strong>
                    </div>
                    <div class="summary-card">
                        <span>累計利用</span>
                        <strong>{{ formatCurrency(selectedEmployee.totalUsed) }}</strong>
                    </div>
                    <div class="summary-card">
                        <span>累計失効</span>
                        <strong>{{ formatCurrency(selectedEmployee.totalExpired) }}</strong>
                    </div>
                </div>

                <section v-if="selectedEmployee.pendingUsages.length" class="usage-review-panel">
                    <div class="section-head">
                        <h3>利用確認</h3>
                    </div>

                    <div class="usage-review-list">
                        <article v-for="usage in selectedEmployee.pendingUsages" :key="usage.id" class="usage-review-card">
                            <div class="usage-review-copy">
                                <strong>{{ usage.title }}</strong>
                                <p>{{ usage.appliedAt }} ・ 申請額 {{ formatCurrency(usage.requestedAmount) }}</p>
                                <p v-if="usage.note">{{ usage.note }}</p>
                            </div>
                            <div class="usage-review-actions">
                                <label>
                                    <span>確定利用額</span>
                                    <input v-model.number="pendingUsageAmounts[usage.id]" type="number" min="1" step="100">
                                </label>
                                <button
                                    type="button"
                                    class="usage-confirm-button"
                                    :disabled="saving"
                                    @click="confirmUsage(usage.id)"
                                >
                                    利用確定
                                </button>
                            </div>
                        </article>
                    </div>
                </section>

                <div class="content-layout">
                    <aside class="decision-panel">
                        <div class="section-head">
                            <h3>判定材料</h3>
                        </div>

                        <div class="decision-list">
                            <div v-for="item in selectedDecisionRows" :key="item.label" class="decision-row">
                                <span>{{ item.label }}</span>
                                <strong>{{ item.value }}</strong>
                            </div>
                        </div>

                        <div class="decision-note">
                            <span>今年の判断メモ</span>
                            <p>{{ selectedEmployee.judgementNote }}</p>
                        </div>
                    </aside>

                    <section class="ledger-panel">
                        <div class="ledger-toolbar">
                            <div class="ledger-tabs">
                                <button
                                    v-for="tab in ledgerTabs"
                                    :key="tab.key"
                                    type="button"
                                    :class="['ledger-tab', { active: activeLedgerTab === tab.key }]"
                                    @click="activeLedgerTab = tab.key"
                                >
                                    {{ tab.label }}
                                </button>
                            </div>
                        </div>

                        <div class="ledger-table-wrap">
                            <table class="ledger-table">
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
                                    <tr v-if="!visibleLedgerRows.length" class="ledger-empty">
                                        <td colspan="7">履歴なし</td>
                                    </tr>
                                    <tr v-for="row in visibleLedgerRows" :key="row.id">
                                        <td>{{ row.date }}</td>
                                        <td>
                                            <span :class="['row-kind', row.kindTone]">{{ row.kindLabel }}</span>
                                        </td>
                                        <td>{{ row.title }}</td>
                                        <td :class="['amount-cell', row.amountTone]">{{ row.amountLabel }}</td>
                                        <td>{{ row.balanceLabel }}</td>
                                        <td>{{ row.expiryLabel }}</td>
                                        <td>{{ row.note || '-' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>
            </section>
            <section v-else class="detail-panel detail-empty">
                <p>{{ loading ? '読込中' : '表示できる対象者がありません。' }}</p>
            </section>
        </section>

        <Transition name="fade">
            <div v-if="showGrantEditor" class="grant-overlay" @click.self="showGrantEditor = false">
                <section class="grant-drawer">
                    <div class="section-head">
                        <h3>新規付与</h3>
                        <button type="button" class="close-button" :disabled="saving" @click="closeGrantEditor">
                            閉じる
                        </button>
                    </div>

                    <div class="grant-drawer-copy">
                        <strong>{{ selectedEmployee?.user.name }}</strong>
                        <p>{{ draft.registrationStatus === 'ready' ? '登録OKで保存すると実際に付与履歴へ追加されます。' : '下書き・保留は判定内容だけ保存します。' }}</p>
                        <p v-if="hasLinkedGrant" class="linked-grant-alert">実付与あり: すでに付与履歴へ反映されています。</p>
                    </div>

                    <div class="form-grid">
                        <label>
                            <span>付与区分</span>
                            <select v-model="draft.grantType" :disabled="saving">
                                <option
                                    v-for="option in grantTypeOptions"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </option>
                            </select>
                        </label>

                        <label>
                            <span>付与日</span>
                            <input v-model="draft.grantDate" :class="[{ 'date-color': theme.dark }]" type="date" :disabled="saving">
                        </label>

                        <label>
                            <span>金額</span>
                            <input v-model.number="draft.amount" type="number" min="0" step="100" :disabled="saving">
                        </label>

                        <label>
                            <span>処理状態</span>
                            <select v-model="draft.registrationStatus" :disabled="saving || hasLinkedGrant">
                                <option value="draft">下書き</option>
                                <option value="ready">登録OK</option>
                                <option value="hold">保留</option>
                            </select>
                        </label>

                        <label class="full-width">
                            <span>算定メモ</span>
                            <textarea
                                v-model="draft.judgementNote"
                                rows="4"
                                placeholder="勤続年数、在籍日数、休職・育休の調整理由"
                                :disabled="saving"
                            />
                        </label>
                    </div>

                    <div class="grant-preview">
                        <div class="preview-card">
                            <span>今回追加する付与</span>
                            <strong>{{ formatCurrency(draft.amount) }}</strong>
                            <p>{{ selectedGrantTypeLabel }} ・ {{ formatDisplayDate(draft.grantDate) }}</p>
                        </div>

                        <div class="preview-side">
                            <div class="check-box">
                                <span>失効日</span>
                                <strong>{{ expiryDate }}</strong>
                            </div>
                            <div class="check-box">
                                <span>状態</span>
                                <strong>{{ registrationLabel }}</strong>
                            </div>
                        </div>
                    </div>

                    <div class="drawer-actions">
                        <button
                            v-if="canDiscardDraft"
                            type="button"
                            class="drawer-button danger"
                            :disabled="saving"
                            @click="discardReviewDraft"
                        >
                            破棄
                        </button>
                        <button type="button" class="drawer-button ghost" :disabled="saving" @click="closeGrantEditor">
                            キャンセル
                        </button>
                        <button type="button" class="drawer-button primary" :disabled="saving || !selectedEmployee" @click="saveGrant">
                            {{ saving ? '保存中' : '保存' }}
                        </button>
                    </div>
                </section>
            </div>
        </Transition>
    </div>
</template>

<script lang="ts" setup>
import UserPanel from '@/components/Global/UserPanel.vue';
import { useApi } from '@/composables/api';
import { DateTime } from 'luxon';
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { User } from '@/interface/globalInterface';
import { useTheme } from '@/store/theme';

type EmployeeStatus = 'ready' | 'review' | 'done';
type QueueFilter = 'all' | EmployeeStatus;
type GrantType = 'annual' | 'adjustment';
type RegistrationStatus = 'draft' | 'ready' | 'hold';
type LedgerTab = 'grant' | 'activity' | 'all';

interface DecisionItem {
    label: string;
    value: string;
}

interface EmployeeGrant {
    id: string;
    source: string;
    grantedAt: string;
    expiresAt: string;
    amount: number;
    remaining: number | null;
    note: string;
}

interface EmployeeActivity {
    id: string;
    type: 'use' | 'expire' | 'adjust';
    title: string;
    happenedAt: string;
    amount: number;
    balanceAfter: number | null;
    relatedExpiry?: string;
    note: string;
}

interface PendingUsage {
    id: number;
    postId: number;
    requestedAmount: number;
    appliedAt: string;
    title: string;
    note: string;
}

interface EmployeeRecord {
    id: number;
    user: User;
    positionId: number;
    hireDate: string;
    attendanceStatus: string;
    leaveStatus: string;
    annualBaseAmount: number;
    currentBalance: number;
    totalGranted: number;
    totalUsed: number;
    totalExpired: number;
    suggestedAmount: number;
    judgementNote: string;
    status: EmployeeStatus;
    statusReason: string;
    reviewDraft: {
        hasSavedReview: boolean;
        hasRegisteredGrant: boolean;
        grantType: GrantType;
        grantDate: string;
        amount: number;
        registrationStatus: RegistrationStatus;
        judgementNote: string;
    };
    pendingUsages: PendingUsage[];
    grants: EmployeeGrant[];
    activities: EmployeeActivity[];
}

interface LedgerRow {
    id: string;
    date: string;
    kindLabel: string;
    kindTone: 'grant' | 'use' | 'expire' | 'adjust';
    title: string;
    amountLabel: string;
    amountTone: 'plus' | 'minus';
    balanceLabel: string;
    expiryLabel: string;
    note: string;
}

interface RefreshManagementResponse {
    year: number;
    employees: EmployeeRecord[];
    stats: {
        eligible_count: number;
        ready_count: number;
        review_count: number;
        done_count: number;
    };
}

const filters: { key: QueueFilter; label: string }[] = [
    { key: 'all', label: 'すべて' },
    { key: 'ready', label: 'そのまま' },
    { key: 'review', label: '要調整' },
    { key: 'done', label: '登録済み' },
];

const ledgerTabs: { key: LedgerTab; label: string }[] = [
    { key: 'all', label: '全履歴' },
    { key: 'grant', label: '付与履歴' },
    { key: 'activity', label: '利用・失効履歴' },
    
];

const grantTypeOptions: { value: GrantType; label: string }[] = [
    { value: 'annual', label: '年次付与' },
    { value: 'adjustment', label: '手動調整' },
];
const theme = useTheme()
const api = useApi();
const loading = ref(false);
const saving = ref(false);
const syncing = ref(false);
const employees = ref<EmployeeRecord[]>([]);
const stats = ref<RefreshManagementResponse['stats']>({
    eligible_count: 0,
    ready_count: 0,
    review_count: 0,
    done_count: 0,
});
const searchWord = ref('');
const activeFilter = ref<QueueFilter>('all');
const activeLedgerTab = ref<LedgerTab>('all');
const showGrantEditor = ref(false);
const selectedEmployeeId = ref<number>(0);
const eligibleEmployees = computed(() => employees.value);
const pendingUsageAmounts = reactive<Record<number, number>>({});

const draft = reactive({
    grantType: 'annual' as GrantType,
    grantDate: DateTime.now().startOf('year').toFormat('yyyy-LL-dd'),
    amount: 0,
    registrationStatus: 'draft' as RegistrationStatus,
    judgementNote: '',
});

const filteredEmployees = computed(() => {
    const keyword = searchWord.value.toLowerCase();

    return eligibleEmployees.value.filter((employee) => {
        const hitFilter = activeFilter.value === 'all' || employee.status === activeFilter.value;
        const hitKeyword = !keyword || String(employee.user.name ?? '').toLowerCase().includes(keyword);
        return hitFilter && hitKeyword;
    });
});

const selectedEmployee = computed(() => {
    return eligibleEmployees.value.find((employee) => employee.id === selectedEmployeeId.value) ?? null;
});

const eligibleCount = computed(() => stats.value.eligible_count);
const readyCount = computed(() => stats.value.ready_count);
const reviewCount = computed(() => stats.value.review_count);

const selectedDecisionRows = computed<DecisionItem[]>(() => {
    if (!selectedEmployee.value) return [];

    return [
        { label: '雇用区分', value: employmentTypeLabel(selectedEmployee.value.positionId) },
        { label: '勤続年数', value: yearsWorkedLabel(selectedEmployee.value.user.joined_date).replace('勤続 ', '') },
        { label: '休職・育休', value: selectedEmployee.value.leaveStatus },
        { label: '今年の基準額', value: formatCurrency(selectedEmployee.value.annualBaseAmount) },
    ];
});

const selectedGrantTypeLabel = computed(() => {
    return grantTypeOptions.find((option) => option.value === draft.grantType)?.label ?? '年次付与';
});

const expiryDate = computed(() => {
    const date = DateTime.fromISO(draft.grantDate);
    if (!date.isValid) return '日付未設定';
    return date.plus({ year: 1 }).toFormat('yyyy.MM.dd');
});

const registrationLabel = computed(() => {
    if (draft.registrationStatus === 'ready') return '登録OK';
    if (draft.registrationStatus === 'hold') return '保留';
    return '下書き';
});

const hasLinkedGrant = computed(() => {
    return !!selectedEmployee.value?.reviewDraft.hasRegisteredGrant;
});

const canDiscardDraft = computed(() => {
    if (!selectedEmployee.value) return false;
    return selectedEmployee.value.reviewDraft.hasSavedReview
        && !selectedEmployee.value.reviewDraft.hasRegisteredGrant
        && ['draft', 'hold'].includes(selectedEmployee.value.reviewDraft.registrationStatus);
});

const grantEditorButtonLabel = computed(() => {
    if (!selectedEmployee.value) return '+ 付与追加';
    if (!selectedEmployee.value.reviewDraft.hasSavedReview) return '+ 付与追加';
    if (selectedEmployee.value.reviewDraft.hasRegisteredGrant && selectedEmployee.value.reviewDraft.registrationStatus === 'hold') {
        return '保留内容を確認';
    }
    if (selectedEmployee.value.reviewDraft.hasRegisteredGrant && selectedEmployee.value.reviewDraft.registrationStatus === 'draft') {
        return '下書きを確認';
    }
    if (selectedEmployee.value.reviewDraft.hasRegisteredGrant) return '登録内容を確認';
    if (selectedEmployee.value.reviewDraft.registrationStatus === 'hold') return '保留内容を編集';
    if (selectedEmployee.value.reviewDraft.registrationStatus === 'draft') return '下書きを再開';
    return '+ 付与追加';
});

const grantRows = computed<LedgerRow[]>(() => {
    return (selectedEmployee.value?.grants ?? []).map((grant) => ({
        id: grant.id,
        date: grant.grantedAt,
        kindLabel: '付与',
        kindTone: 'grant',
        title: grant.source,
        amountLabel: `+${formatCurrency(grant.amount)}`,
        amountTone: 'plus',
        balanceLabel: formatNullableCurrency(grant.remaining),
        expiryLabel: grant.expiresAt || '-',
        note: grant.note,
    }));
});

const activityRows = computed<LedgerRow[]>(() => {
    return (selectedEmployee.value?.activities ?? []).map((activity) => ({
        id: activity.id,
        date: activity.happenedAt,
        kindLabel: activity.type === 'use' ? '利用' : activity.type === 'expire' ? '失効' : '調整',
        kindTone: activity.type,
        title: activity.title,
        amountLabel: `${activity.type === 'adjust' && activity.amount > 0 ? '+' : '-'}${formatCurrency(activity.amount)}`,
        amountTone: activity.type === 'adjust' && activity.amount > 0 ? 'plus' : 'minus',
        balanceLabel: formatNullableCurrency(activity.balanceAfter),
        expiryLabel: activity.relatedExpiry || '-',
        note: activity.note,
    }));
});

const allRows = computed<LedgerRow[]>(() => {
    return [...grantRows.value, ...activityRows.value].sort((a, b) => {
        return DateTime.fromFormat(b.date, 'yyyy.MM.dd').toMillis() - DateTime.fromFormat(a.date, 'yyyy.MM.dd').toMillis();
    });
});

const visibleLedgerRows = computed(() => {
    if (activeLedgerTab.value === 'grant') return grantRows.value;
    if (activeLedgerTab.value === 'activity') return activityRows.value;
    return allRows.value;
});

const employmentTypeLabel = (positionId: number) => {
    if ([6, 11, 16].includes(positionId)) return '正社員';
    if (positionId === 12) return '契約社員';
    return '対象外';
};

const statusMeta = (status: QueueFilter) => {
    if (status === 'ready') return { label: 'そのまま付与', tone: 'neutral' as const };
    if (status === 'review') return { label: '要調整', tone: 'warning' as const };
    if (status === 'done') return { label: '登録済み', tone: 'done' as const };
    return { label: '未分類', tone: 'done' as const };
};

const formatCurrency = (value: number) => {
    return `${new Intl.NumberFormat('ja-JP').format(value)}円`;
};

const formatNullableCurrency = (value: number | null | undefined) => {
    if (value == null) return '-';
    return formatCurrency(value);
};

const formatDisplayDate = (value: string) => {
    const date = DateTime.fromISO(value);
    return date.isValid ? date.toFormat('yyyy.MM.dd') : value;
};

const yearsWorkedLabel = (hireDate: string) => {
    const start = DateTime.fromISO(hireDate);
    if (!start.isValid) return '';
    const years = Math.max(0, Math.floor(DateTime.now().diff(start, 'years').years));
    return `勤続 ${years}年`;
};

const serviceYears = (hireDate: string) => {
    const start = DateTime.fromISO(hireDate);
    if (!start.isValid) return 0;
    return Math.max(0, Math.floor(DateTime.now().diff(start, 'years').years));
};

const closeGrantEditor = () => {
    if (saving.value) return;
    showGrantEditor.value = false;
};

const applyDraftFromEmployee = (employee: EmployeeRecord) => {
    draft.grantType = employee.reviewDraft.grantType;
    draft.grantDate = employee.reviewDraft.grantDate;
    draft.amount = employee.reviewDraft.amount;
    draft.registrationStatus = employee.reviewDraft.registrationStatus;
    draft.judgementNote = employee.reviewDraft.judgementNote;
};

const toggleGrantEditor = () => {
    if (!selectedEmployee.value) return;

    if (!showGrantEditor.value) {
        applyDraftFromEmployee(selectedEmployee.value);
    }

    showGrantEditor.value = !showGrantEditor.value;
};

const getRefreshManagement = async () => {
    const data = await api.get('/refresh/management', null, {
        loadingRef: loading,
        silent: true,
    }) as RefreshManagementResponse | null;

    if (!data) return;

    employees.value = data.employees ?? [];
    stats.value = data.stats ?? {
        eligible_count: 0,
        ready_count: 0,
        review_count: 0,
        done_count: 0,
    };
    if (employees.value.length && !employees.value.some((employee) => employee.id === selectedEmployeeId.value)) {
        selectedEmployeeId.value = employees.value[0].id;
    }
};

const saveGrant = async () => {
    if (!selectedEmployee.value) return;

    const result = await api.post('/refresh/management/grants', {
        user_id: selectedEmployee.value.id,
        grant_type: draft.grantType,
        grant_date: draft.grantDate,
        amount: draft.amount,
        registration_status: draft.registrationStatus,
        judgement_note: draft.judgementNote,
        annual_base_amount: selectedEmployee.value.annualBaseAmount,
        leave_status: selectedEmployee.value.leaveStatus,
        service_years: serviceYears(selectedEmployee.value.user.joined_date),
    }, {
        loadingRef: saving,
        toast: draft.registrationStatus === 'ready' ? '付与を登録しました。' : '判定内容を保存しました。',
    });

    if (!result) return;

    showGrantEditor.value = false;
    await getRefreshManagement();
};

const discardReviewDraft = async () => {
    if (!selectedEmployee.value || !selectedEmployee.value.reviewDraft.hasSavedReview) return;

    const grantYear = DateTime.fromISO(selectedEmployee.value.reviewDraft.grantDate).year;
    if (!grantYear) return;

    const result = await api.del('/refresh/management/reviews', {
        user_id: selectedEmployee.value.id,
        grant_year: grantYear,
    }, {
        loadingRef: saving,
        ask: '保存済みの下書き・保留を破棄しますか？',
        toast: '判定内容を破棄しました。',
    });

    if (!result) return;

    showGrantEditor.value = false;
    await getRefreshManagement();
};

const confirmUsage = async (usageId: number) => {
    const amount = Number(pendingUsageAmounts[usageId] ?? 0);
    if (!amount) return;

    const result = await api.patch(`/refresh/usages/${usageId}/confirm`, {
        amount,
    }, {
        loadingRef: saving,
        ask: 'この金額で利用確定しますか？',
        toast: '利用を確定しました。',
    });

    if (!result) return;

    await getRefreshManagement();
};

const syncKintone = async () => {
    const result = await api.post('/refresh/kintone/sync', null, {
        loadingRef: syncing,
        ask: 'Kintoneのリフレッシュデータを同期しますか？',
        toast: 'Kintone同期が完了しました。',
    });

    if (!result) return;

    await getRefreshManagement();
};

watch(selectedEmployee, (employee) => {
    if (!employee) return;
    applyDraftFromEmployee(employee);
    Object.keys(pendingUsageAmounts).forEach((key) => {
        delete pendingUsageAmounts[Number(key)];
    });
    employee.pendingUsages.forEach((usage) => {
        pendingUsageAmounts[usage.id] = usage.requestedAmount;
    });
    showGrantEditor.value = false;
    activeLedgerTab.value = 'all';
}, { immediate: true });

watch(filteredEmployees, (list) => {
    if (!list.length) {
        selectedEmployeeId.value = 0;
        showGrantEditor.value = false;
        return;
    }
    if (!list.some((employee) => employee.id === selectedEmployeeId.value)) {
        selectedEmployeeId.value = list[0].id;
    }
});

onMounted(() => {
    getRefreshManagement();
});
</script>

<style lang="scss" scoped>
input, select, textarea {
    box-sizing: border-box !important;
}
.refresh-management {
    position: relative;
    height: 100%;
    display: flex;
    flex-direction: column;
    gap: 8px;
    padding: 0 18px 18px;
    color: var(--primary-color);
}

.header-strip {
    display: flex;
    justify-content: space-between;
    gap: 8px;
    align-items: center;
}

.header-side {
    min-width: 320px;
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 6px;
}

.metric-row {
    flex: 1;
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 8px;
}

.metric-card {
    padding: 10px 12px;
    border-radius: 8px;
    background: var(--background-color);
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.metric-card span,
.summary-card span,
.decision-row span,
.decision-note span,
.form-grid span,
.preview-card span,
.check-box span {
    font-size: 11px;
    color: var(--text2);
}

.metric-card strong,
.summary-card strong,
.check-box strong {
    font-size: 18px;
    line-height: 1;
}

.source-row {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    justify-content: flex-end;
}

.source-chip {
    height: 28px;
    padding: 0 8px;
    border-radius: 4px;
    display: inline-flex;
    align-items: center;
    background: rgba(255, 255, 255, 0.05);
    box-shadow: inset 0 0 0 1px rgba(127, 127, 127, 0.18);
    font-size: 11px;
    font-weight: 600;
}

.source-chip.eligible {
    background: rgba(106, 127, 173, 0.18);
    color: #7e98cf;
}

.source-chip.manual {
    background: rgba(106, 127, 173, 0.18);
    color: #7e98cf;
}

.source-chip.auto {
    background: rgba(55, 121, 104, 0.18);
    color: #4c957c;
}

.sync-button {
    height: 30px;
    padding: 0 12px;
    border-radius: 4px;
    border: 1px solid var(--formBorder);
    background: var(--background-color);
    color: var(--primary-color);
    cursor: pointer;
    font-size: 12px;
}

.sync-button:disabled,
.close-button:disabled,
.drawer-button:disabled {
    cursor: default;
    opacity: 0.6;
}

.workflow-layout {
    flex: 1;
    min-height: 0;
    display: grid;
    grid-template-columns: 360px minmax(0, 1fr);
    gap: 10px;
}

.queue-panel,
.detail-panel {
    min-height: 0;
    border-radius: 8px;
    background: var(--background-color);
}

.queue-panel {
    display: flex;
    flex-direction: column;
    padding: 10px;
    gap: 8px;
}

.detail-panel {
    display: flex;
    flex-direction: column;
    padding: 12px;
    gap: 10px;
    overflow: auto;
}

.panel-head,
.detail-head,
.section-head,
.ledger-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 8px;
}

.panel-head h2,
.detail-head h2,
.section-head h3 {
    margin: 0;
}

.panel-head h2,
.section-head h3 {
    font-size: 14px;
}

.detail-head h2 {
    font-size: 16px;
}

.detail-head p,
.queue-copy p,
.preview-card p,
.decision-note p,
.grant-drawer-copy p {
    margin: 4px 0 0;
    font-size: 11px;
    color: var(--text2);
}

.linked-grant-alert {
    color: #256856 !important;
    font-weight: 700;
}

.panel-badge,
.mini-chip,
.close-button {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 11px;
    background: var(--bg3);
}

.close-button {
    border: 1px solid var(--formBorder);
    color: var(--primary-color);
    cursor: pointer;
}

.queue-filter {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.queue-filter input,
.form-grid input,
.form-grid select,
.form-grid textarea {
    width: 100%;
    border: 1px solid var(--formBorder);
    border-radius: 6px;
    background: var(--background-color);
    color: var(--primary-color);
    padding: 10px 12px;
    resize: vertical;
}

.filter-pills,
.ledger-tabs,
.detail-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    align-items: center;
}

.filter-pill,
.ledger-tab {
    height: 30px;
    padding: 0 10px;
    border-radius: 4px;
    border: 1px solid var(--formBorder);
    background: var(--background-color);
    color: var(--primary-color);
    cursor: pointer;
    font-size: 12px;
}

.filter-pill.active,
.ledger-tab.active {
    background: #4b4b4b;
    border-color: #4b4b4b;
    color: #fff;
}

.queue-list {
    flex: 1;
    min-height: 0;
    overflow: auto;
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.queue-empty,
.detail-empty {
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--text2);
    font-size: 12px;
}

.queue-empty {
    min-height: 120px;
    border-radius: 6px;
    background: var(--bg3);
}

.queue-item {
    padding: 10px;
    border-radius: 6px;
    background: var(--bg3);
    color: var(--primary-color);
    display: flex;
    justify-content: space-between;
    gap: 8px;
    text-align: left;
    cursor: pointer;
}

.queue-item.active {
    box-shadow: inset 3px 0 0 #4b4b4b;
    background: rgba(0, 0, 0, 0.03);
}

.queue-main {
    display: flex;
    gap: 8px;
    align-items: center;
    min-width: 0;
}

.queue-copy {
    min-width: 0;
}

.queue-copy strong {
    display: block;
    font-size: 14px;
}

.queue-side {
    display: flex;
    flex-direction: column;
    align-items: end;
    gap: 4px;
    flex-shrink: 0;
}

.status-note {
    margin: 0;
    font-size: 11px;
    color: var(--text2);
    text-align: right;
}

.status-badge {
    min-width: 88px;
    text-align: center;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 700;
}

.status-badge.neutral {
    background: rgba(55, 121, 104, 0.12);
    color: #256856;
}

.status-badge.warning {
    background: rgba(106, 127, 173, 0.18);
    color: #7e98cf;
}

.status-badge.done {
    background: rgba(55, 121, 104, 0.22);
    color: #7fd0b4;
}

.add-grant-button {
    height: 36px;
    padding: 0 14px;
    border-radius: 6px;
    border: 1px solid #4b4b4b;
    background: #4b4b4b;
    color: #fff;
    cursor: pointer;
    font-size: 12px;
}

.detail-identity {
    display: flex;
    align-items: center;
    gap: 12px;
}

.summary-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 8px;
}

.summary-card,
.check-box {
    padding: 10px 12px;
    border-radius: 6px;
    background: var(--bg3);
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.content-layout {
    display: grid;
    grid-template-columns: 300px minmax(0, 1fr);
    gap: 10px;
    min-height: 0;
}

.decision-panel,
.ledger-panel {
    min-height: 0;
    padding: 10px;
    border-radius: 8px;
    background: var(--bg3);
    overflow: auto;
}

.usage-review-panel {
    padding: 10px;
    border-radius: 8px;
    background: var(--bg3);
}

.usage-review-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-top: 8px;
}

.usage-review-card {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    padding: 10px;
    border-radius: 6px;
    background: var(--background-color);
}

.usage-review-copy strong {
    display: block;
    font-size: 13px;
}

.usage-review-copy p {
    margin: 4px 0 0;
    font-size: 11px;
    color: var(--text2);
}

.usage-review-actions {
    display: flex;
    align-items: end;
    gap: 8px;
}

.usage-review-actions label {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.usage-review-actions input {
    width: 120px;
    border: 1px solid var(--formBorder);
    border-radius: 6px;
    background: var(--background-color);
    color: var(--primary-color);
    padding: 8px 10px;
}

.usage-confirm-button {
    height: 36px;
    padding: 0 12px;
    border-radius: 6px;
    border: 1px solid #4b4b4b;
    background: #4b4b4b;
    color: #fff;
    font-size: 12px;
    cursor: pointer;
    white-space: nowrap;
}

.usage-confirm-button:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.decision-panel .section-head {
    margin-bottom: 8px;
    padding-bottom: 6px;
}

.decision-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.decision-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 8px;
    padding: 8px 10px;
    border-radius: 6px;
    background: var(--background-color);
}

.decision-row strong {
    text-align: right;
    font-size: 13px;
}

.decision-note {
    margin-top: 12px;
    padding: 12px;
    border-radius: 6px;
    background: var(--background-color);
}

.form-grid {
    margin-top: 10px;
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
}

.form-grid label {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.full-width {
    grid-column: 1 / -1;
}

.grant-preview {
    margin-top: 10px;
    display: grid;
    grid-template-columns: minmax(0, 1fr) 220px;
    gap: 8px;
}

.preview-card {
    padding: 14px;
    border-radius: 6px;
    background: var(--bg3);
    color: var(--primary-color);
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.preview-card span,
.preview-card p {
    color: var(--text2);
}

.preview-card strong {
    font-size: 22px;
    line-height: 1;
}

.preview-side {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.grant-overlay {
    position: absolute;
    inset: 0;
    background: rgba(0, 0, 0, 0.2);
    display: flex;
    justify-content: flex-end;
    padding: 10px;
    z-index: 20;
}

.grant-drawer {
    width: min(520px, 100%);
    height: 100%;
    border-radius: 8px;
    background: var(--background-color);
    padding: 14px;
    display: flex;
    flex-direction: column;
    overflow: auto;
    box-sizing: border-box !important;
}

.grant-drawer-copy {
    margin-top: 8px;
}

.grant-drawer-copy strong {
    display: block;
}

.grant-drawer-copy p {
    margin: 4px 0 0;
    font-size: 11px;
    color: var(--text2);
}

.drawer-actions {
    margin-top: auto;
    padding-top: 14px;
    display: flex;
    justify-content: flex-end;
    gap: 8px;
}

.drawer-button {
    height: 36px;
    padding: 0 14px;
    border-radius: 6px;
    border: 1px solid transparent;
    cursor: pointer;
    font-size: 12px;
}

.drawer-button.ghost {
    background: transparent;
    border-color: var(--formBorder);
    color: var(--primary-color);
}

.drawer-button.primary {
    background: #4b4b4b;
    border-color: #4b4b4b;
    color: #fff;
}

.drawer-button.danger {
    background: rgba(184, 74, 74, 0.12);
    border-color: rgba(184, 74, 74, 0.25);
    color: #a33d3d;
}

.ledger-table-wrap {
    margin-top: 10px;
    overflow: auto;
    border-radius: 6px;
}

.ledger-table {
    width: 100%;
    border-collapse: collapse;
    background: var(--background-color);
    font-size: 12px;
}

.ledger-table th,
.ledger-table td {
    padding: 10px;
    border-bottom: 1px solid var(--formBorder);
    text-align: left;
    white-space: nowrap;
}

.ledger-table th {
    position: sticky;
    top: 0;
    background: #4b4b4b;
    color: #fff;
    z-index: 1;
}

.ledger-empty td {
    text-align: center;
    color: var(--text2);
}

.row-kind {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 56px;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 700;
}

.row-kind.grant {
    background: rgba(55, 121, 104, 0.12);
    color: #256856;
}

.row-kind.use {
    background: rgba(106, 127, 173, 0.18);
    color: #7e98cf;
}

.row-kind.expire {
    background: rgba(184, 74, 74, 0.12);
    color: #a33d3d;
}

.row-kind.adjust {
    background: rgba(75, 75, 75, 0.08);
    color: #4b4b4b;
}

.amount-cell.plus {
    color: #256856;
    font-weight: 700;
}

.amount-cell.minus {
    color: #a33d3d;
    font-weight: 700;
}

@media screen and (max-width: 1240px) {
    .workflow-layout,
    .content-layout,
    .summary-grid,
    .grant-preview {
        grid-template-columns: 1fr;
    }
}

@media screen and (max-width: 720px) {
    .refresh-management {
        padding: 0 12px 12px;
    }

    .header-strip,
    .metric-row {
        display: grid;
        grid-template-columns: 1fr;
    }

    .header-side {
        min-width: 0;
        align-items: stretch;
    }

    .source-row {
        justify-content: flex-start;
    }

    .workflow-layout {
        display: flex;
        flex-direction: column;
    }

    .queue-panel,
    .detail-panel,
    .decision-panel,
    .ledger-panel,
    .grant-drawer {
        padding: 12px;
    }

    .form-grid,
    .summary-grid {
        grid-template-columns: 1fr;
    }

    .queue-item,
    .detail-head,
    .detail-identity,
    .decision-row {
        flex-direction: column;
        align-items: start;
    }

    .queue-side {
        align-items: start;
    }

    .status-note {
        text-align: left;
    }

    .ledger-table th,
    .ledger-table td {
        padding: 10px;
    }
}
</style>
