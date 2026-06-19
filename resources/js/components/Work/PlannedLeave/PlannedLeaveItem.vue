<template>
    <div class="leave-item-root">
        <div class="planned-leave-row">
            <div>
                <p class="planned-leave-date">{{ formatDate }}</p>
                <div :class="['planned-leave-status', { 'is-used': isUsed }]">
                    {{ isUsed ? '使用済み' : '予定' }}
                </div>
            </div>
            <CommandButton v-if="!isUsed && (!item.planned_leave_change_request || item.planned_leave_change_request.status !== 'pending')" :buttons="[
                { title: '変更申請', action: () => {
                    changeRequest = true
                }}
            ]"/>
        </div>
        <div v-if="existingRequest" class="exisiting-requests existing-requests">
            <div class="existing-request-header">
                <div>
                    <p class="existing-request-title">変更申請</p>
                    <p class="existing-request-subtitle">
                        {{ formatIsoDate(existingRequest.created_at) }}
                    </p>
                </div>
                <span :class="['existing-request-status', statusClass]">
                    {{ existingRequest.status_label }}
                </span>
            </div>
            <div>
                <div class="existing-request-date-flow">
                    <p>{{ formatIsoDate(existingRequest.original_date) }}</p>
                    <span class="existing-request-arrow">→</span>
                    <p>{{ formatIsoDate(existingRequest.requested_date) }}</p>
                </div>
            </div>
            <div class="existing-request-body">
                <div class="existing-request-reason">
                    <span class="existing-request-label">理由</span>
                    <p>{{ existingRequest.reason || '理由なし' }}</p>
                </div>
            </div>
        </div>
        <div v-if="changeRequest" class="mt-4 mb-2">
            
            
            <div class="mt-4 ">
                <div v-if="pmApprovalNeeded && selectableProjects" class="my-4 bg-[var(--background-color)]">
                    <ItemSelector
                        :clearable="true"
                        :close-on-select="true"
                        :multiple="false"
                        v-model="selectedProject"
                        :options="selectableProjects"
                        :key="selectableProjects.length"
                        rules="required"
                        ref="selectedProjectRef"
                        place-holder="プロジェクトを選択してください"
                    />
                </div>
                <p class="text-[12px] mt-4 my-2">
                    変更申請日
                </p>
                <ShortInput 
                    type="date"
                    customClass="date fit !m-0"
                    class="m-0"
                    rules="required"
                    ref="selectedDateRef"
                    v-model="selectedDate"
                />
                <div class="text-[12px] text-[gray] mt-4">{{ possibleSpan }}</div>
                <div class="mt-4">
                    <LongInput 
                        v-model="reason"
                        place-holder="変更理由（前後1か月の変更の場合、理由は不可＊なしと記載してください）"
                    />
                </div>
                <div class="mt-4">
                    <LoaderButton class="!m-0" :loading="loading" content="送信" @triggered="submitChangeRequest()"/>
                </div>
            </div>
            
        </div>
    </div>
</template>
<script setup lang="ts">
import ItemSelector from '@/components/Form/ItemSelector.vue';
import LongInput from '@/components/Form/LongInput.vue';
import ShortInput from '@/components/Form/ShortInput.vue';
import CommandButton from '@/components/Global/CommandButton.vue';
import LoaderButton from '@/components/Global/LoaderButton.vue';
import { useApi } from '@/composables/api';
import { useDialog } from '@/composables/dialog';
import { Shift, tempData } from '@/interface/workInterface.js';
import { DateTime } from 'luxon';
import { computed, ref, useTemplateRef } from 'vue';

const props = defineProps<{
    item: Shift
    workTemp: tempData | null
    pmApprovalNeeded: boolean
    selectableProjects: {id: number, name: string}[]
}>()
const emit = defineEmits<{
    updated: []
}>()
const changeRequest = ref(false)
const loading = ref(false)
const api = useApi()
const selectedDateRef = useTemplateRef('selectedDateRef')
const selectedProjectRef = useTemplateRef('selectedProjectRef')
const selectedDate = ref('')
const selectedProject = ref<number | null>(null)
const { toast } = useDialog()
const reason = ref('')
const existingRequest = computed(() => props.item.planned_leave_change_request ?? null)
const statusClass = computed(() => {
    return existingRequest.value ? `is-${existingRequest.value.status}` : ''
})

const possibleSpan = computed(() => {
    if(!props.workTemp) return ''
    const instance = DateTime.fromISO(props.workTemp.date)
    if(!instance.isValid) return ''
    return `変更可能期間: ${instance.toFormat('yyyy/M/d')} 〜 ${instance.plus({ years: 1 }).minus({ days: 1 }).toFormat('yyyy/M/d')}`
})
const formatDate = computed(() => {
    return DateTime.fromISO(shiftDayValue(props.item.shift_day)).toFormat('yyyy / M / d (ccc)')
})
const shiftDayValue = (value: string | number | Date) => value.toString()
const formatIsoDate = (value: string | Date) => {
    const date = DateTime.fromISO(value.toString())
    return date.isValid ? date.toFormat('yyyy/M/d') : value.toString()
}

const isUsed = computed(() => {
    return shiftDayValue(props.item.shift_day) < DateTime.now().toISODate()
})


const submitChangeRequest = async () => {
    const validationTargets = [selectedDateRef.value, selectedProjectRef.value].filter(item => item !== null)
    let isValid = true
    for(const target of validationTargets){
        const validation = await target?.validate()   
        if(!validation?.valid){
            isValid = false
        }
    }
    if(!isValid) return
    const res = await api.post('/planned_leave_change_request', {
        shift_id: props.item.id,
        change_request_date: selectedDate.value,
        project_id: selectedProject.value,
        reason: reason.value,
        pm_approval_required: props.pmApprovalNeeded
    })
    if(res){
        toast('変更申請を送信しました。')
        changeRequest.value = false
        emit('updated')
    }

}
</script>
<style scoped>
.leave-item-root {
    min-height: 48px;
    padding: 15px 12px;
    border: 1px solid var(--calendarBorder);
    border-radius: 4px;
    background: var(--bg3);
}
.planned-leave-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
}
.planned-leave-date {
    font-size: 14px;
}
.planned-leave-weekday {
    margin-top: 2px;
    font-size: 11px;
    opacity: 0.7;
}
.planned-leave-status {
    margin-top: 10px;
    font-size: 12px;
    color: gray
}
.existing-requests {
    margin-top: 14px;
    padding: 14px;
    border: 1px solid var(--calendarBorder);
    border-radius: 6px;
    background: var(--background-color);
}
.existing-request-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 14px;
}
.existing-request-title {
    color: var(--primary-color);
    font-size: 13px;
    font-weight: 700;
    line-height: 1.4;
}
.existing-request-subtitle {
    margin-top: 2px;
    color: var(--third-color);
    font-size: 11px;
    line-height: 1.5;
}
.existing-request-status {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 24px;
    padding: 2px 9px;
    background: var(--bg3);
    color: var(--third-color);
    font-size: 11px;
    line-height: 1.2;
    white-space: nowrap;
}
.existing-request-status.is-pending {
    background: rgba(214, 148, 39, 0.14);
    color: #a46412;
}
.existing-request-status.is-approved {
    background: rgba(47, 125, 85, 0.14);
    color: #2f7d55;
}
.existing-request-status.is-rejected {
    background: rgba(190, 62, 62, 0.14);
    color: #b43b3b;
}
.existing-request-body {
    display: grid;
    gap: 12px;
    margin-top: 12px;
}
.existing-request-date-flow {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr);
    align-items: center;
    gap: 10px;
    padding: 12px;
    border-radius: 4px;
    background: var(--bg3);
    width: fit-content;
    margin-top: 20px;
}
.existing-request-date-flow p,
.existing-request-reason p {
    margin: 3px 0 0;
    color: var(--primary-color);
    font-size: 13px;
    font-weight: 600;
    line-height: 1.5;
    word-break: break-word;
}
.existing-request-label {
    color: var(--third-color);
    font-size: 10px;
    font-weight: 700;
    line-height: 1.4;
}
.existing-request-arrow {
    color: var(--third-color);
    font-size: 15px;
}
.existing-request-reason {
    padding: 0 2px;
}
@media (max-width: 520px) {
    .existing-request-date-flow {
        grid-template-columns: 1fr;
        gap: 8px;
    }
    .existing-request-arrow {
        transform: rotate(90deg);
        justify-self: flex-start;
    }
}
</style>
