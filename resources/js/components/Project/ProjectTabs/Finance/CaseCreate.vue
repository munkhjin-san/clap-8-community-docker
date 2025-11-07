<template>
    <Modal @close="emit('close')" :loader="loading < 1">
        <template #title>
            <p>{{ reportTitle }}</p>
        </template>
        <template #menu>
            <ItemMenu 
                v-if="selectedCaseId"
                :items="[
                    {title: '編集', action: () => editCase()},
                    {title: '削除', action: () => deleteCase()}
                ]"
            />
        </template>
        <template #content>
            <div>
                {{ selectedProject.name }}
            </div>
            <div class="si-box">
                <div class="mb-[10px]">対象月</div>
                <select v-model="params.period" class="border border-solid border-[var(--primary-color)] h-[40px] m-h-[40px] px-[10px] text-[var(--primary-color)] appearance-none rounded-none">
                    <option v-for="month in monthsArray" :value="month">{{ DateTime.fromISO(month).toFormat('yyyy年M月') }}</option>
                </select>
            </div>
            <div v-if="viewData" class="bg-[var(--bg3)] pb-2 px-2">
                <div class="si-box pt-2">
                    <p class="mb-2">メンバー</p>
                    <UserPanel size="20" with-name :user="viewData.reporter"/>
                </div>
                <div class="si-box">
                    <p class="mb-2">区分</p>
                    <p>{{ kindLabelMap[viewData.kind] ?? '―' }}</p>
                </div>
                <div class="si-box" v-if="viewData.kind === 'PIPELINE'">
                    <p class="mb-2">営業ステージ</p>
                    <p>{{ stageLabelText(viewData.stage) }}</p>
                </div>
                <div class="si-box" v-else-if="viewData.kind === 'ACTUAL'">
                    <p class="mb-2">実績ステータス</p>
                    <p>{{ deliveryLabelText(viewData.delivery_status) }}</p>
                </div>
                <!-- <div class="si-box">
                    <p class="mb-2">顧客</p>
                    <p>{{ viewData.client_name }}</p>
                </div> -->
                <div class="si-box">
                    <p class="mb-2">目標件数</p>
                    <p>{{ viewData.case_count }}</p>
                </div>
                <div class="si-box">
                    <p class="mb-2">金額(円)</p>
                    <p>{{ viewData.amount }}円</p>
                </div>
                <div v-if="viewData.notes" class="si-box">
                    <p class="mb-2">ノート</p>
                    <p>{{ viewData.notes }}</p>
                </div>
            </div>
            <div v-else>
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
                <!-- <div class="si-box">
                    <ShortInput 
                        v-model="params.client_name"
                        place-holder="顧客"
                    />
                </div> -->
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
                    <!-- <LoaderButton
                        style="margin: 0"
                        :loading="savingType === 1"
                        @triggered="submitCase(1)"
                        content="一時保存"
                    /> -->
                    <LoaderButton
                        style="margin: 0"
                        :loading="savingType === 2"
                        @triggered="submitCase(2)"
                        content="申請する"
                    />
                </div>
            </div>
        </template>
    </Modal>
</template>
<script setup lang="ts">
import ItemSelector from '@/components/Form/ItemSelector.vue';
import ShortInput from '@/components/Form/ShortInput.vue';
import LoaderButton from '@/components/Global/LoaderButton.vue';
import Modal from '@/components/Global/Modal.vue';
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { DateTime, Interval } from 'luxon';
import { useApi } from '@/composables/api';
import LongInput from '@/components/Form/LongInput.vue';
import { Project } from '@/interface/projectInterface';
import MemberSelector from '@/components/Form/MemberSelector.vue';
import { User } from '@/interface/globalInterface';
import UserPanel from '@/components/Global/UserPanel.vue';
import ItemMenu from '@/components/Global/ItemMenu.vue';
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
    selectedCaseId: number | null;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'saved'): void;
}>();
const api = useApi();
const members = computed(() => {
    return props.selectedProject.members
})
const viewData = ref()
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
const params = reactive<Params>({
    client_name: '',
    amount: '',
    kind: 'PIPELINE',
    stage: 'C',
    delivery_status: 'ORDERED_NOT_COMPLETED',
    notes: '',
    case_count: '',
    period: DateTime.now().startOf('month').minus({ months: 1 }).toISODate(),
    member: null
});
const pipelineStageOptions = computed(() => STAGE_PIPELINE_LIST.map(stage => ({ value: stage, label: STAGE_LABEL[stage] })));
const deliveryOptions = computed(() => Object.entries(DELIVERY_LABEL).map(([value, label]) => ({ value: value as DeliveryStatus, label })));
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
const editCase = () => {
    params.client_name = viewData.value.client_name
    params.amount = String(viewData.value.amount ?? '')
    params.kind = viewData.value.kind || 'PIPELINE'
    params.stage = viewData.value.stage || (params.kind === 'PIPELINE' ? 'C' : 'WON')
    params.delivery_status = viewData.value.delivery_status || 'ORDERED_NOT_COMPLETED'
    params.case_count = String(viewData.value.case_count ?? '')
    params.notes = viewData.value.notes
    const sql = viewData.value?.report_date;
    if (sql) {
        const iso = DateTime.fromSQL(sql).toISODate();
        if (iso) {
            params.period = iso; 
        }
    }
    params.member = viewData.value.reporter
    viewData.value = null
}
const deleteCase = async() => {
    await api.del(`/delete_case/${props.selectedCaseId}`, {}, {ask: '案件を削除しまか？', toast: '削除しました。'})
    emit('saved');
}
const savingType = ref<1 | 2 | null>(null);
const loading = ref(0)
const reportTitle = computed(() => {
    return `案件報告`;
});
const monthsArray = computed(() => {
    const interval = Interval.fromDateTimes(
        DateTime.now().startOf('month').minus({ months: 6 }),
        DateTime.now().endOf('month').plus({ months: 6 })
    );
    if (!interval.isValid) {
        return [];
    }
    return interval.splitBy({ months: 1 }).map(i => i.start?.toISODate() || '').filter(date => date);
})
const reportDate = computed(() => {
    return DateTime.fromObject({ year: props.reportYear, month: props.reportMonth }, { zone: 'Asia/Tokyo' })
        .startOf('month')
        .toISODate();
});

const resetForm = () => {
    params.client_name = '';
    params.amount = '';
    params.case_count = '';
    params.notes = '';
    params.kind = 'PIPELINE';
    params.stage = 'C';
    params.delivery_status = 'ORDERED_NOT_COMPLETED';
};

const submitCase = async (type: 1 | 2) => {
    if (savingType.value) return;
    const amount = Number(params.amount || 0);
    const caseCount = Number(params.case_count || 0);

    savingType.value = type;
    
    await api.post(
        `/projects/${props.projectId}/cases`,
        {
            kind: params.kind,
            stage: params.kind === 'PIPELINE' ? params.stage : params.kind === 'ACTUAL' ? 'WON' : null,
            delivery_status: params.kind === 'ACTUAL' ? params.delivery_status : null,
            client_name: params.client_name.trim(),
            case_count: Number.isNaN(caseCount) ? 0 : caseCount,
            amount: Number.isNaN(amount) ? 0 : amount,
            notes: params.notes || null,
            report_date: params.period || '',
            state: type === 2 ? 'submitted' : 'draft',
            member_id: params.member?.id
        },
        { toast: type === 2 ? '案件を申請しました。' : '一時保存しました。' },
    );
    emit('saved');
    resetForm();


    savingType.value = null;
    
};
const get_case = async() => {
    const data = await api.post('/view_case', {id: props.selectedCaseId, period: params.period})
    viewData.value = data
    loading.value += 1
}
watch(() => params.period, (newVal) => {
    if (props.selectedCaseId && newVal) {
        get_case()
    }
})
onMounted(() => {
    if (props.selectedCaseId) {
        get_case()
    } else {
        loading.value += 1
    }
})
</script>

<style scoped>
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
</style>
