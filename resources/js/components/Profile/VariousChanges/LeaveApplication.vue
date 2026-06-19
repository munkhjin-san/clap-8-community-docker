<template>
<div class="space-y-8">
    <div class="leave-mode-grid" role="radiogroup" aria-label="休職申請の種類">
        <label
            v-for="option in modeOptions"
            :key="option.value"
            :class="['leave-mode-card', { selected: params.mode === option.value }]"
        >
            <input
                v-model="params.mode"
                class="sr-only"
                name="leave_mode"
                type="radio"
                :value="option.value"
            />
            <span>{{ option.label }}</span>
        </label>
    </div>
    <p v-if="modeError" class="leave-application-error">{{ modeError }}</p>

    <div v-if="params.mode === 'illness'" class="space-y-8">
        <ShortInput
            ref="illnessNameRef"
            v-model="params.illness.illness_name"
            custom-class="full"
            name="illness_name"
            place-holder="傷病名"
            rules="required|max:100"
        />

        <div class="flex gap-8 under960:flex-col">
            <div class="flex-1">
                <ShortInput
                    ref="illnessStartRef"
                    v-model="params.illness.start_date"
                    custom-class="full"
                    name="illness_start_date"
                    place-holder="傷病期間開始"
                    rules="required"
                    type="date"
                />
            </div>
            <div class="flex-1">
                <ShortInput
                    ref="illnessEndRef"
                    v-model="params.illness.end_date"
                    custom-class="full"
                    name="illness_end_date"
                    place-holder="傷病期間終了"
                    rules="required"
                    type="date"
                />
            </div>
        </div>
    </div>

    <div v-if="params.mode === 'childbirth_childcare'" class="space-y-8">
        <ShortInput
            ref="expectedBirthDateRef"
            v-model="params.childbirth_childcare.expected_birth_date"
            class="fit"
            name="expected_birth_date"
            place-holder="出産予定日"
            rules="required"
            type="date"
        />

        <div class="flex gap-8 under960:flex-col">
            <div class="flex-1">
                <ShortInput
                    ref="maternityStartRef"
                    v-model="params.childbirth_childcare.maternity_leave_start"
                    custom-class="full"
                    name="maternity_leave_start"
                    place-holder="産休開始日"
                    rules="required"
                    type="date"
                />
            </div>
            <div class="flex-1">
                <ShortInput
                    ref="maternityEndRef"
                    v-model="params.childbirth_childcare.maternity_leave_end"
                    custom-class="full"
                    name="maternity_leave_end"
                    place-holder="産休終了予定日"
                    rules="required"
                    type="date"
                />
            </div>
        </div>

        <div class="flex gap-8 under960:flex-col">
            <div class="flex-1">
                <ShortInput
                    ref="childcareStartRef"
                    v-model="params.childbirth_childcare.childcare_leave_start"
                    custom-class="full"
                    name="childcare_leave_start"
                    place-holder="育児休業開始予定日"
                    rules="required"
                    type="date"
                />
            </div>
            <div class="flex-1">
                <ShortInput
                    ref="childcareEndRef"
                    v-model="params.childbirth_childcare.childcare_leave_end"
                    custom-class="full"
                    name="childcare_leave_end"
                    place-holder="育児休業終了予定日"
                    rules="required"
                    type="date"
                />
            </div>
        </div>
    </div>
</div>
</template>

<script setup lang="ts">
import { reactive, ref } from 'vue';
import ShortInput from '@/components/Form/ShortInput.vue';

type LeaveMode = 'illness' | 'childbirth_childcare'

type IllnessPayload = {
    illness_name: string
    start_date: string
    end_date: string
}

type ChildbirthChildcarePayload = {
    expected_birth_date: string
    maternity_leave_start: string
    maternity_leave_end: string
    childcare_leave_start: string
    childcare_leave_end: string
}

type LeaveApplicationPayload = {
    mode: LeaveMode | ''
    illness: IllnessPayload
    childbirth_childcare: ChildbirthChildcarePayload
}

const modeOptions: { label: string; value: LeaveMode }[] = [
    { label: '傷病', value: 'illness' },
    { label: '出産・育児', value: 'childbirth_childcare' },
]

const params = reactive<LeaveApplicationPayload>({
    mode: '',
    illness: {
        illness_name: '',
        start_date: '',
        end_date: '',
    },
    childbirth_childcare: {
        expected_birth_date: '',
        maternity_leave_start: '',
        maternity_leave_end: '',
        childcare_leave_start: '',
        childcare_leave_end: '',
    },
})

const modeError = ref('')

const illnessNameRef = ref<InstanceType<typeof ShortInput> | null>(null)
const illnessStartRef = ref<InstanceType<typeof ShortInput> | null>(null)
const illnessEndRef = ref<InstanceType<typeof ShortInput> | null>(null)

const expectedBirthDateRef = ref<InstanceType<typeof ShortInput> | null>(null)
const maternityStartRef = ref<InstanceType<typeof ShortInput> | null>(null)
const maternityEndRef = ref<InstanceType<typeof ShortInput> | null>(null)
const childcareStartRef = ref<InstanceType<typeof ShortInput> | null>(null)
const childcareEndRef = ref<InstanceType<typeof ShortInput> | null>(null)

const validateTargets = async (targets: Array<{ validate?: () => Promise<{ valid: boolean }> } | null>) => {
    let result = true
    for (const target of targets) {
        const validation = await target?.validate?.() || { valid: false }
        result = result && validation.valid
    }

    return result
}

const validate = async () => {
    modeError.value = params.mode ? '' : '傷病または出産・育児を選択してください。'
    if (modeError.value) return false

    if (params.mode === 'illness') {
        return await validateTargets([
            illnessNameRef.value,
            illnessStartRef.value,
            illnessEndRef.value,
        ])
    }

    return await validateTargets([
        expectedBirthDateRef.value,
        maternityStartRef.value,
        maternityEndRef.value,
        childcareStartRef.value,
        childcareEndRef.value,
    ])
}

const getPayload = () => ({
    mode: params.mode,
    detail: params.mode === 'illness'
        ? { ...params.illness }
        : { ...params.childbirth_childcare },
})

defineExpose({ validate, getPayload })
</script>

<style scoped>
.leave-mode-grid {
    display: grid;
    gap: 14px;
    grid-template-columns: repeat(2, minmax(0, 1fr));
}

.leave-mode-card {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 48px;
    border: 1px solid var(--calendarBorder);
    color: var(--primary-color);
    cursor: pointer;
    font-size: 14px;
    font-weight: 700;
}

.leave-mode-card.selected {
    box-shadow: 0 0 0 1px var(--primary-color);
}

.leave-application-error {
    color: tomato;
    font-size: 11px;
}

@media screen and (max-width: 639px) {
    .leave-mode-grid {
        grid-template-columns: 1fr;
    }
}
</style>
