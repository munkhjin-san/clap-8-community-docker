<template>
    <Modal @close="handleClose" :loader="loading < 1">
        <template #title>
            <p>{{ reportTitle }}</p>
        </template>
        <!-- <template #menu>
            <div v-if="viewData" class="modal-menu">
                <button type="button" @click="editCase">編集</button>
                <button type="button" class="danger" @click="deleteCase">削除</button>
            </div>
        </template> -->
        <template #content>
            <div class="case-create">
                <section class="case-create__context">
                    <div>
                        <p class="eyebrow">プロジェクト</p>
                        <p class="project-name">{{ selectedProject.name }}</p>
                    </div>
                    <div class="case-create__period">
                        <p class="eyebrow">対象月</p>
                        <div class="month-switcher">
                            <button class="ghost-button" type="button" @click="stepPeriod(-1)">
                                <Back size="12" />
                            </button>
                            <select v-model="params.period" class="period-select">
                                <option v-for="month in monthsArray" :key="month" :value="month">
                                    {{ DateTime.fromISO(month).toFormat('yyyy年M月') }}
                                </option>
                            </select>
                            <button class="ghost-button" type="button" @click="stepPeriod(1)">
                                <Back size="12" class="rotate-180" />
                            </button>
                        </div>
                        <div v-if="historyChips.length" class="history-chips">
                            <button
                                v-for="chip in historyChips"
                                :key="chip.value"
                                type="button"
                                class="history-chip"
                                :class="{ active: chip.value === params.period }"
                                @click="params.period = chip.value"
                            >
                                {{ chip.label }}
                            </button>
                        </div>
                    </div>
                </section>

                <section v-if="viewData" class="case-summary">
                    <div class="case-summary__header">
                        <div class="case-summary__user">
                            <UserPanel size="32" with-name :user="viewData.reporter" />
                            <span class="period-pill">{{ periodLabel }}</span>
                        </div>
                        <div class="case-summary__meta">
                            <span class="badge">{{ kindLabelMap[viewData.kind] ?? '―' }}</span>
                            <span class="badge soft" v-if="viewData.kind === 'PIPELINE'">{{ stageLabelText(viewData.stage) }}</span>
                            <span class="badge soft" v-else-if="viewData.kind === 'ACTUAL'">{{ deliveryLabelText(viewData.delivery_status) }}</span>
                        </div>
                    </div>
                    <div class="case-metrics">
                        <div class="metric">
                            <p>目標件数</p>
                            <strong>{{ viewData.case_count }}</strong>
                        </div>
                        <div class="metric">
                            <p>金額(円)</p>
                            <strong>{{ formatYen(viewData.amount) }}</strong>
                        </div>
                        <div class="metric">
                            <p>状態</p>
                            <strong>{{ statusText }}</strong>
                        </div>
                    </div>
                    <div v-if="viewData.notes" class="case-notes">
                        <p class="eyebrow">ノート</p>
                        <p>{{ viewData.notes }}</p>
                    </div>
                    <div class="summary-actions">
                        <button type="button" class="cta" @click="editCase">編集</button>
                        <button type="button" class="cta danger" @click="deleteCase">削除</button>
                    </div>
                </section>

                <section v-else class="case-form">
                    <div v-if="hasPrivilage" class="si-box">
                        <MemberSelector 
                            placeHolder="メンバー"
                            :options="members"
                            :multiple="false"
                            :close-on-select="true"
                            v-model="params.member"
                        />
                    </div>
                    <div class="si-box">
                        <p class="mb-2">区分</p>
                        <div class="kind-toggle">
                            <button
                                v-for="option in KIND_TAB_OPTIONS"
                                :key="option.kind"
                                type="button"
                                class="kind-chip"
                                :class="{ active: params.kind === option.kind }"
                                @click="setKind(option.kind)"
                            >
                                {{ option.label }}
                            </button>
                        </div>
                    </div>
                    <div v-if="params.kind === 'PIPELINE'" class="si-box">
                        <ItemSelector
                            :options="pipelineStageOptions"
                            v-model="params.stage"
                            place-holder="確度"
                            :multiple="false"
                            label="label"
                            :reduce="option => option.value"
                            :clearable="false"
                            :closeOnSelect="true"
                        />
                    </div>
                    <div v-else-if="params.kind === 'ACTUAL'" class="si-box">
                        <ItemSelector
                            :options="deliveryOptions"
                            v-model="params.delivery_status"
                            place-holder="実績ステータス"
                            :multiple="false"
                            label="label"
                            :reduce="option => option.value"
                            :clearable="false"
                            :closeOnSelect="true"
                        />
                    </div>
                    <div class="si-box">
                        <ShortInput 
                            v-model="params.case_count"
                            place-holder="目標件数"
                            type="number"
                        />
                    </div>
                    <div class="si-box">
                        <ShortInput 
                            v-model="params.amount"
                            place-holder="金額(円)"
                            type="number"
                        />
                    </div>
                    <div class="si-box">
                        <LongInput 
                            v-model="params.notes"
                            place-holder="ノート"
                        />
                    </div>
                    <div class="si-box flex gap-4 justify-center">
                        <LoaderButton
                            style="margin: 0"
                            :loading="savingType === 2"
                            @triggered="submitCase(2)"
                            content="申請する"
                        />
                    </div>
                </section>
            </div>
        </template>
    </Modal>
</template>
<script setup lang="ts">
import Back from '@/components/Icons/Back.vue';
import ItemSelector from '@/components/Form/ItemSelector.vue';
import ShortInput from '@/components/Form/ShortInput.vue';
import LoaderButton from '@/components/Global/LoaderButton.vue';
import Modal from '@/components/Global/Modal.vue';
import { computed, reactive, ref, watch } from 'vue';
import { DateTime, Interval } from 'luxon';
import { useApi } from '@/composables/api';
import LongInput from '@/components/Form/LongInput.vue';
import { Project } from '@/interface/projectInterface';
import MemberSelector from '@/components/Form/MemberSelector.vue';
import { User } from '@/interface/globalInterface';
import UserPanel from '@/components/Global/UserPanel.vue';
import {
    KIND_TAB_OPTIONS,
    STAGE_PIPELINE_LIST,
    STAGE_LABEL,
    DELIVERY_LABEL,
    type RecordKind,
    type Stage,
    type DeliveryStatus,
} from '@/utils/case';

const props = defineProps<{
    selectedProject: Project
    projectId: number;
    reportYear: number;
    reportMonth: number;
    hasPrivilage: boolean;
    selectedCase: {
        memberId: number;
        memberName: string;
        status: string;
        activeCase: { id: number; reportDate: string | null } | null;
        reportDate: string | null;
        timeline: Record<string, { id: number; reportDate: string | null }[]>;
    } | null;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'saved'): void;
}>();
const api = useApi();
const reportTitle = computed(() => '案件報告');
const members = computed(() => props.selectedProject.members);
const viewData = ref<any | null>(null);
const loading = ref(0);
const savingType = ref<1 | 2 | null>(null);
const editingCaseId = ref<number | null>(null);
const suspendAutoFetch = ref(false);
const lastFetchKey = ref<string | null>(null);
const kindLabelMap = Object.fromEntries(KIND_TAB_OPTIONS.map(item => [item.kind, item.label]));

type Params = {
    client_name: string
    amount: string
    kind: RecordKind
    stage: Stage
    delivery_status: DeliveryStatus
    notes: string
    case_count: string
    period: string
    member: User | null
}
const defaultPeriod = props.selectedCase?.reportDate ?? DateTime.now().startOf('month').minus({ months: 1 }).toISODate();
const params = reactive<Params>({
    client_name: '',
    amount: '',
    kind: 'PIPELINE',
    stage: 'C',
    delivery_status: 'ORDERED_NOT_COMPLETED',
    notes: '',
    case_count: '',
    period: defaultPeriod,
    member: null
});

const pipelineStageOptions = computed(() => STAGE_PIPELINE_LIST.map(stage => ({ value: stage, label: STAGE_LABEL[stage] })));
const deliveryOptions = computed(() => Object.entries(DELIVERY_LABEL).map(([value, label]) => ({ value: value as DeliveryStatus, label })));
const historyMap = computed<Record<string, { id: number; reportDate: string | null }[]>>(() => props.selectedCase?.timeline ?? {});
const caseIdForPeriod = computed<number | null>(() => {
    const period = params.period;
    if (period && historyMap.value[period]?.length) {
        return historyMap.value[period][0].id;
    }
    return props.selectedCase?.activeCase?.id ?? null;
});
const monthsArray = computed(() => {
    const interval = Interval.fromDateTimes(
        DateTime.now().startOf('month').minus({ months: 6 }),
        DateTime.now().endOf('month').plus({ months: 6 })
    );
    const base = interval.isValid
        ? interval.splitBy({ months: 1 }).map(i => i.start?.toISODate() || '').filter(date => date)
        : [];
    const pool = new Set<string>(base as string[]);
    Object.keys(historyMap.value).forEach(key => pool.add(key));
    if (props.selectedCase?.reportDate) {
        pool.add(props.selectedCase.reportDate);
    }
    if (params.period) {
        pool.add(params.period);
    }
    return Array.from(pool)
        .map(date => ({ date, dt: DateTime.fromISO(date) }))
        .filter(item => item.dt.isValid)
        .sort((a, b) => a.dt.toMillis() - b.dt.toMillis())
        .map(item => item.date);
});
const historyChips = computed(() => {
    return Object.keys(historyMap.value)
        .map(period => ({
            value: period,
            label: DateTime.fromISO(period).toFormat('yy/MM'),
        }))
        .filter(item => DateTime.fromISO(item.value).isValid)
        .sort((a, b) => DateTime.fromISO(b.value).toMillis() - DateTime.fromISO(a.value).toMillis());
});
const periodLabel = computed(() => params.period ? DateTime.fromISO(params.period).toFormat('yyyy年M月') : '対象月');
const statusText = computed(() => {
    if (!viewData.value) return '―';
    if (viewData.value.kind === 'PIPELINE') {
        return stageLabelText(viewData.value.stage);
    }
    if (viewData.value.kind === 'ACTUAL') {
        return deliveryLabelText(viewData.value.delivery_status);
    }
    return '目標値';
});

const setKind = (next: RecordKind) => {
    if (params.kind === next) return;
    params.kind = next;
    if (next === 'PIPELINE') {
        params.stage = 'C';
        params.delivery_status = 'ORDERED_NOT_COMPLETED';
    } else if (next === 'ACTUAL') {
        params.stage = 'WON';
        params.delivery_status = 'ORDERED_NOT_COMPLETED';
    } else {
        params.stage = 'WON';
        params.delivery_status = 'ORDERED_NOT_COMPLETED';
    }
};
const stageLabelText = (stage?: Stage | null) => stage ? (STAGE_LABEL[stage] ?? stage) : '—';
const deliveryLabelText = (delivery?: DeliveryStatus | null) => delivery ? (DELIVERY_LABEL[delivery] ?? delivery) : '—';
const formatYen = (value?: number | null) => {
    if (value == null) return '―';
    return `${new Intl.NumberFormat('ja-JP').format(value)}円`;
};

const resolveSelectedCaseKey = computed(() => {
    if (!props.selectedCase) return null;
    return `${props.selectedCase.memberId}-${props.selectedCase.status}-${props.selectedCase.reportDate ?? ''}`;
});

watch(resolveSelectedCaseKey, (next, prev) => {
    if (next && next !== prev && props.selectedCase?.reportDate) {
        params.period = props.selectedCase.reportDate;
    }
    if (!next && prev && !editingCaseId.value) {
        params.period = DateTime.now().startOf('month').minus({ months: 1 }).toISODate();
    }
    lastFetchKey.value = null;
});

const refreshView = async () => {
    if (suspendAutoFetch.value) return;
    const currentId = caseIdForPeriod.value;
    if (!currentId) {
        viewData.value = null;
        loading.value = 1;
        return;
    }
    const key = `${currentId}-${params.period}`;
    if (lastFetchKey.value === key && viewData.value) {
        return;
    }
    lastFetchKey.value = key;
    loading.value = 0;
    try {
        const data = await api.post('/view_case', { id: currentId, period: params.period });
        viewData.value = data;
        console.log(viewData.value)
    } finally {
        loading.value = 1;
    }
};

watch(
    () => [caseIdForPeriod.value, props.selectedCase],
    () => {
        if (suspendAutoFetch.value) return;
        refreshView();
    },
    { immediate: true }
);

const hasPrivilage = computed(() => props.hasPrivilage);

const resetForm = () => {
    params.client_name = '';
    params.amount = '';
    params.case_count = '';
    params.notes = '';
    params.kind = 'PIPELINE';
    params.stage = 'C';
    params.delivery_status = 'ORDERED_NOT_COMPLETED';
    if (!hasPrivilage.value) {
        params.member = null;
    }
    editingCaseId.value = null;
    suspendAutoFetch.value = false;
    viewData.value = null;
};
const stepPeriod = (offset: number) => {
    const current = params.period ? DateTime.fromISO(params.period) : DateTime.now().startOf('month');
    const next = current.plus({ months: offset }).startOf('month').toISODate();
    params.period = next ?? '';
};
const editCase = () => {
    if (!viewData.value) return;
    editingCaseId.value = viewData.value.id;
    suspendAutoFetch.value = true;
    params.client_name = viewData.value.client_name ?? '';
    params.amount = String(viewData.value.amount ?? '');
    params.kind = viewData.value.kind || 'PIPELINE';
    params.stage = viewData.value.stage || (params.kind === 'PIPELINE' ? 'C' : 'WON');
    params.delivery_status = viewData.value.delivery_status || 'ORDERED_NOT_COMPLETED';
    params.case_count = String(viewData.value.case_count ?? '');
    params.notes = viewData.value.notes ?? '';
    params.period = viewData.value.report_date ?? params.period;
    params.member = viewData.value.reporter || null;
    viewData.value = null;
};
const deleteCase = async () => {
    if (!viewData.value?.id) return;
    await api.del(`/delete_case/${viewData.value.id}`, {}, { ask: '案件を削除しますか？', toast: '削除しました。' });
    emit('saved');
};
const submitCase = async (type: 1 | 2) => {
    if (savingType.value) return;
    const amount = Number(params.amount || 0);
    const caseCount = Number(params.case_count || 0);
    const payload = {
        kind: params.kind,
        stage: params.kind === 'PIPELINE' ? params.stage : params.kind === 'ACTUAL' ? 'WON' : null,
        delivery_status: params.kind === 'ACTUAL' ? params.delivery_status : null,
        client_name: params.client_name.trim() || null,
        case_count: Number.isNaN(caseCount) ? 0 : caseCount,
        amount: Number.isNaN(amount) ? 0 : amount,
        notes: params.notes || null,
        report_date: params.period || '',
        state: type === 2 ? 'submitted' : 'draft',
        member_id: params.member?.id ?? null,
    };
    savingType.value = type;
    try {
        if (editingCaseId.value) {
            await api.put(`/projects/${props.projectId}/cases/${editingCaseId.value}`, payload, { toast: type === 2 ? '案件を更新しました。' : '一時保存しました。' });
        } else {
            await api.post(`/projects/${props.projectId}/cases`, payload, { toast: type === 2 ? '案件を申請しました。' : '一時保存しました。' });
        }
        emit('saved');
        resetForm();
    } finally {
        savingType.value = null;
    }
};
const handleClose = () => {
    resetForm();
    emit('close');
};
</script>

<style scoped>
.case-create {
    display: flex;
    flex-direction: column;
    gap: 16px;
}
.case-create__context {
    display: flex;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 16px;
    border: 1px solid var(--calendarBorder);
    padding: 16px;
    background: var(--background-color);
}
.case-create__period {
    min-width: 220px;
    flex: 1;
}
.eyebrow {
    font-size: 11px;
    letter-spacing: 0.08em;
    color: #64748b;
    text-transform: uppercase;
    margin-bottom: 4px;
}
.project-name {
    font-size: 18px;
    font-weight: 600;
}
.month-switcher {
    display: flex;
    align-items: center;
    gap: 8px;
}
.period-select {
    border: 1px solid var(--primary-color);
    padding: 8px 12px;
    min-width: 180px;
    background: var(--background-color);
    color: var(--primary-color);
}
.history-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-top: 8px;
}
.history-chip {
    border: 1px solid var(--normalBorder);
    padding: 4px 10px;
    font-size: 12px;
    background: transparent;
}
.history-chip.active {
    background: var(--hoverBorder);
    color: #fff;
    border-color: var(--hoverBorder);
}
.case-summary,
.case-form {
    border: 1px solid var(--calendarBorder);
    padding: 20px;
    background: var(--background-color);
    box-shadow: 0 6px 16px rgba(15, 23, 42, 0.05);
}
.case-summary__header {
    display: flex;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 16px;
}
.case-summary__user {
    display: flex;
    align-items: center;
    gap: 12px;
}
.period-pill {
    border: 1px solid var(--calendarBorder);
    padding: 4px 12px;
    font-size: 12px;
}
.case-summary__meta {
    display: flex;
    align-items: center;
    gap: 8px;
}
.badge {
    display: inline-flex;
    align-items: center;
    padding: 4px 10px;
    font-size: 12px;
    background: var(--hoverBorder);
    color: #fff;
}
.badge.soft {
    background: var(--bg3);
    color: var(--primary-color);
}
.case-metrics {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 12px;
    margin-top: 16px;
}
.metric {
    border: 1px solid var(--calendarBorder);
    padding: 12px;
    background: var(--bg3);
}
.metric p {
    font-size: 12px;
    color: #64748b;
    margin-bottom: 4px;
}
.metric strong {
    font-size: 18px;
}
.case-notes {
    margin-top: 16px;
    background: var(--bg3);
    padding: 12px;
    font-size: 14px;
    line-height: 1.6;
}
.summary-actions {
    margin-top: 16px;
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}
.summary-actions .cta {
    border: 1px solid var(--normalBorder);
    padding: 8px 16px;
    background: var(--background-color);
    font-size: 13px;
}
.summary-actions .cta.danger {
    border-color: #fecaca;
    color: #b91c1c;
}
.case-form .si-box {
    padding: 12px;
}
.case-form .si-box + .si-box {
    margin-top: 12px;
}
.kind-toggle {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}
.kind-chip {
    border: 1px solid var(--normalBorder);
    padding: 6px 12px;
    font-size: 13px;
    background: var(--background-color);
    color: var(--primary-color);
    transition: background 0.2s ease, color 0.2s ease, border-color 0.2s ease;
}
.kind-chip.active {
    background: var(--hoverBorder);
    border-color: var(--hoverBorder);
    color: #fff;
}
.modal-menu {
    display: flex;
    gap: 8px;
}
.modal-menu button {
    border: 1px solid var(--normalBorder);
    padding: 6px 12px;
    font-size: 12px;
    background: var(--background-color);
}
.modal-menu button.danger {
    border-color: #fecaca;
    color: #b91c1c;
}
.ghost-button {
    border: 1px solid var(--normalBorder);
    background: var(--background-color);
    padding: 6px 10px;
}
</style>
