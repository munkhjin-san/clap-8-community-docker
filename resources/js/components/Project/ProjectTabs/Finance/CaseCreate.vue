<template>
    <Modal @close="emit('close')" :loader="loading < 1">
        <template #title>
            <p>{{ reportTitle }}</p>
        </template>
        <template #menu>
            <ItemMenu 
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
                    <p class="mb-2">ステータス</p>
                    <p>{{ viewData.status }}</p>
                </div>
                <div class="si-box">
                    <p class="mb-2">顧客</p>
                    <p>{{ viewData.client_name }}</p>
                </div>
                <div class="si-box">
                    <p class="mb-2">案件</p>
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
                    <ItemSelector 
                        :options="statusOptions"
                        v-model="params.status"
                        place-holder="ステータス"
                        :multiple="false"
                        label="option"
                        :reduce="option => option"
                        :clearable="false"
                        :closeOnSelect="true"
                    />
                </div>
                <div class="si-box">
                    <ShortInput 
                        v-model="params.client_name"
                        place-holder="顧客"
                    />
                </div>
                <div class="si-box">
                    <ShortInput 
                        v-model="params.case_count"
                        place-holder="案件"
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
const statusOptions = ['目標値', '★竣工済', '①受注済未竣工', '②確度A', '③確度B', '④確度C', '⑤確度D、E'];
type Params = {
    client_name: string
    amount: string
    status: string
    notes: string
    case_count: string
    period: string
    member: User | null
}
const params = reactive<Params>({
    client_name: '',
    amount: '',
    status: statusOptions[0],
    notes: '',
    case_count: '',
    period: DateTime.now().startOf('month').minus({ months: 1 }).toISODate(),
    member: null
});
const editCase = () => {
    params.client_name = viewData.value.client_name
    params.amount = viewData.value.amount
    params.status = viewData.value.status
    params.case_count = viewData.value.case_count
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
    params.status = statusOptions[0];
};

const submitCase = async (type: 1 | 2) => {
    if (savingType.value) return;
    const amount = Number(params.amount || 0);
    const caseCount = Number(params.case_count || 0);

    savingType.value = type;
    
    await api.post(
        `/projects/${props.projectId}/cases`,
        {
            status: params.status,
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
// watch([params.period, props.selectedCaseId] => () )
onMounted(() => {
    if (props.selectedCaseId) {
        get_case()
    } else {
        loading.value += 1
    }
})
</script>
