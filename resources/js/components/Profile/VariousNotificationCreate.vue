

<template>
    <Modal size="large" @close="emit('close', false)">
        <template #title>
            各種届出
        </template>
        <template #content>
            <div v-if="activeStep === 'type'" class="notification-type-step">
                <div class="notification-type-header">
                    <p class="notification-type-heading">
                        届出の種類を選択してください
                    </p>
                </div>
                <div class="notification-type-grid" role="radiogroup" aria-label="届出の種類">
                    <label
                        v-for="type in notificationTypes"
                        :key="type.value"
                        :class="[
                            'notification-type-card',
                            { 'notification-type-card-selected': selectedNotificationType === type.value }
                        ]"
                    >
                        <input
                            v-model="selectedNotificationType"
                            class="sr-only"
                            name="various-notification-type"
                            type="radio"
                            :value="type.value"
                        />
                        <span class="notification-type-icon" aria-hidden="true">
                            <component :is="type.icon" />
                        </span>
                        <span class="notification-type-copy">
                            <span class="notification-type-title">
                                {{ type.label }}
                            </span>
                            <span class="notification-type-description">
                                {{ type.description }}
                            </span>
                        </span>
                        <span class="notification-type-check" aria-hidden="true">
                            <svg class="h-3 w-3" viewBox="0 0 12 10" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 5L4.2 8.2L11 1" />
                            </svg>
                        </span>
                    </label>
                </div>
                <div class="text-[14px] text-[gray]">計画有給の変更申請は<router-link :to="{name: 'timesheet', query: {action: 'request_planned_leave_change', user_id: userId}}" class="jump-link" @click="">こちら</router-link>から行えます。</div>
                <LoaderButton :loading="false" content="次へ" class="mt-4" @triggered="goToForm"/>
            </div>
            <div v-else class="notification-form-step">
                <div class="notification-form-header">
                    <button class="notification-form-back" type="button" @click="activeStep = 'type'">
                        <Back />
                        戻る
                    </button>
                    <div>
                        <p class="notification-type-heading">
                            {{ selectedTypeLabel }}
                        </p>
                    </div>
                </div>
                <NameChangeForm
                    v-if="selectedNotificationType === 'name_change'"
                    ref="nameChangeRef"
                />
                <AddressChangeForm
                    v-else-if="selectedNotificationType === 'address_change'"
                    ref="addressChangeRef"
                />
                <AddRemoveDependent
                    v-else-if="selectedNotificationType === 'dependent_change'"
                    ref="dependentChangeRef"
                />
                <LeaveApplication
                    v-else-if="selectedNotificationType === 'leave_request'"
                    ref="leaveApplicationRef"
                />
                <WorkLocationChange
                    v-else-if="selectedNotificationType === 'work_location_change'"
                    ref="workLocationChangeRef"
                />
                <CommuteChange
                    v-else-if="selectedNotificationType === 'commute_change'"
                    ref="commuteChangeRef"
                />
                <div v-else class="notification-form-empty">
                    この届出フォームは準備中です。
                </div>
                <!-- <div class="notification-type-actions">
                    <button
                        class="notification-form-submit"
                        type="button"
                        :disabled="submitting"
                        @click="submitForm"
                    >
                        申請する
                    </button>
                </div> -->
                <LoaderButton :loading="submitting" @triggered="submitForm" content="申請する"/>
            </div>
        </template>
    </Modal>
</template>
<script setup lang="ts">
import type { Component } from 'vue';
import { computed, ref } from 'vue';
import { useApi } from '@/composables/api';
import Modal from '../Global/Modal.vue';
import Back from '../Icons/Back.vue';
import AddressChangeIcon from './VariousChanges/Icons/AddressChange.vue';
import DependentChangeIcon from './VariousChanges/Icons/DependentChange.vue';
import LeaveRequestIcon from './VariousChanges/Icons/LeaveRequest.vue';
import NameChangeIcon from './VariousChanges/Icons/NameChange.vue';
import PublicTransportationChangeIcon from './VariousChanges/Icons/PublicTransportationChange.vue';
import WorkLocationChangeIcon from './VariousChanges/Icons/WorkLocationChange.vue';
import LoaderButton from '../Global/LoaderButton.vue';
import AddressChangeForm from './VariousChanges/AddressChangeForm.vue';
import AddRemoveDependent from './VariousChanges/AddRemoveDependent.vue';
import CommuteChange from './VariousChanges/CommuteChange.vue';
import LeaveApplication from './VariousChanges/LeaveApplication.vue';
import NameChangeForm from './VariousChanges/NameChangeForm.vue';
import WorkLocationChange from './VariousChanges/WorkLocationChange.vue';

const props = defineProps<{
    userId: number | null
}>()

const emit = defineEmits<{
    close: [boolean]
}>()

type NotificationType = {
    label: string
    value: string
    description: string
    icon: Component
}

const selectedNotificationType = ref<string | null>(null)
const activeStep = ref<'type' | 'form'>('type')
const submitting = ref(false)
const api = useApi()
const nameChangeRef = ref<InstanceType<typeof NameChangeForm> | null>(null)
const addressChangeRef = ref<InstanceType<typeof AddressChangeForm> | null>(null)
const dependentChangeRef = ref<InstanceType<typeof AddRemoveDependent> | null>(null)
const leaveApplicationRef = ref<InstanceType<typeof LeaveApplication> | null>(null)
const workLocationChangeRef = ref<InstanceType<typeof WorkLocationChange> | null>(null)
const commuteChangeRef = ref<InstanceType<typeof CommuteChange> | null>(null)

const selectedTypeLabel = computed(() => {
    return notificationTypes.find(type => type.value === selectedNotificationType.value)?.label ?? ''
})

const notificationTypes: NotificationType[] = [
    {
        label: '氏名変更',
        value: 'name_change',
        description: '戸籍・表示名などの氏名更新',
        icon: NameChangeIcon,
    },
    {
        label: '住所変更',
        value: 'address_change',
        description: '現住所・連絡先住所の変更',
        icon: AddressChangeIcon,
    },
    {
        label: '扶養追加・削除',
        value: 'dependent_change',
        description: '扶養家族の追加または削除',
        icon: DependentChangeIcon,
    },
    {
        label: '休職申請',
        value: 'leave_request',
        description: '休職・復職に関する申請',
        icon: LeaveRequestIcon,
    },
    {
        label: '勤務地変更',
        value: 'work_location_change',
        description: '所属拠点・勤務場所の変更',
        icon: WorkLocationChangeIcon,
    },
    {
        label: '交通費変更',
        value: 'commute_change',
        description: '通勤方法・経路・交通費の変更',
        icon: PublicTransportationChangeIcon,
    },
]

const goToForm = () => {
    if (!selectedNotificationType.value) return
    activeStep.value = 'form'
}

const submitForm = async () => {
    const selectedType = selectedNotificationType.value
    if (!selectedType) return

    const activeForm = selectedType === 'name_change'
        ? nameChangeRef.value
        : selectedType === 'address_change'
            ? addressChangeRef.value
            : selectedType === 'dependent_change'
                ? dependentChangeRef.value
                : selectedType === 'leave_request'
                    ? leaveApplicationRef.value
                    : selectedType === 'work_location_change'
                        ? workLocationChangeRef.value
                        : selectedType === 'commute_change'
                            ? commuteChangeRef.value
                            : null

    if (!activeForm) return
    const isValid = await activeForm.validate()
    if (!isValid) return

    const response = await api.post('/employee_change_applications', {
        user_id: props.userId,
        type: selectedType,
        detail: activeForm.getPayload(),
    }, {
        loadingRef: submitting,
        toast: '届出を申請しました。',
    })

    if (response) {
        emit('close', true)
    }
}
</script>
<style scoped>
.notification-type-step {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.notification-type-header {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.notification-type-heading {
    color: var(--primary-color);
    font-size: 15px;
    font-weight: 700;
}

.notification-type-lead {
    color: gray;
    font-size: 12px;
}

.notification-type-grid {
    display: grid;
    gap: 30px;
    grid-template-columns: repeat(2, minmax(0, 1fr));
}

.notification-type-card {
    position: relative;
    display: flex;
    align-items: center;
    gap: 12px;
    min-height: 76px;
    padding: 0px 38px 0px 10px;
    border: 1px solid var(--calendarBorder);
    background: var(--background-color);
    cursor: pointer;
    transition: border-color 0.18s ease, background 0.18s ease, box-shadow 0.18s ease, transform 0.18s ease;
}

.notification-type-card:hover,
.notification-type-card:focus-within {
    background: var(--bg3);
}

.notification-type-card-selected {
    background: var(--background-color);
    box-shadow: 0 0 0 1px var(--primary-color);
    
}

.notification-type-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 52px;
    height: 52px;
    min-width: 52px;
    color: var(--primary-color);
}


.notification-type-copy {
    display: flex;
    min-width: 0;
    flex: 1;
    flex-direction: column;
    gap: 5px;
}

.notification-type-title {
    display: block;
    color: var(--primary-color);
    font-size: 14px;
    font-weight: 700;
    line-height: 1.35;
}

.notification-type-description {
    display: block;
    color: gray;
    font-size: 11px;
    line-height: 1.35;
}

.notification-type-check {
    position: absolute;
    top: 12px;
    right: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 20px;
    height: 20px;
    border: 1px solid transparent;
    border-radius: 9999px;
    color: transparent;
    transition: background 0.18s ease, color 0.18s ease, border-color 0.18s ease;
}

.notification-type-card-selected .notification-type-check {
    border-color: var(--primary-color);
    background: var(--primary-color);
    color: var(--background-color);
}

.notification-type-actions {
    display: flex;
    justify-content: flex-end;
    padding-top: 16px;
}

.notification-type-next {
    min-width: 104px;
    padding: 9px 20px;
    border-radius: 6px;
    background: var(--primary-color);
    color: #fff !important;
    font-size: 14px;
    font-weight: 700;
    transition: opacity 0.18s ease;
}

.notification-type-next:hover:not(:disabled) {
    opacity: 0.9;
}

.notification-type-next:disabled {
    cursor: not-allowed;
    background: gray;
    opacity: 0.45;
}

.notification-form-step {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.notification-form-header {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 20px;
}

.notification-form-back {
    min-width: 64px;
    padding: 7px 12px;
    border-radius: 999px;
    background: var(--bg3);
    color: var(--primary-color);
    font-size: 12px;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 10px;
    justify-content: center;
}
.notification-form-empty {
    padding: 28px 0;
    color: gray;
    font-size: 13px;
    text-align: center;
}

.notification-form-submit {
    min-width: 104px;
    padding: 9px 20px;
    border-radius: 6px;
    background: var(--primary-color);
    color: #fff !important;
    font-size: 14px;
    font-weight: 700;
}

@media screen and (max-width: 639px) {
    .notification-type-grid {
        grid-template-columns: 1fr;
    }

    .notification-type-card {
        min-height: 80px;
    }

    .notification-form-header {
        flex-direction: column;
    }
}
</style>
