<template>
<div class="space-y-8">
    <div class="dependent-mode-grid" role="radiogroup" aria-label="扶養変更の種類">
        <label
            v-for="option in modeOptions"
            :key="option.value"
            :class="['dependent-mode-card', { selected: params.mode === option.value }]"
        >
            <input
                v-model="params.mode"
                class="sr-only"
                name="dependent_mode"
                type="radio"
                :value="option.value"
            />
            <span>{{ option.label }}</span>
        </label>
    </div>
    <p v-if="modeError" class="dependent-change-error">{{ modeError }}</p>

    <div v-if="params.mode === 'add'" class="space-y-8">
        <ShortInput
            ref="addDateRef"
            v-model="params.add.effective_date"
            class="fit"
            name="add_effective_date"
            place-holder="追加する日付"
            rules="required"
            type="date"
        />

        <div class="flex gap-8 under960:flex-col">
            <div class="flex-1">
                <ShortInput
                    ref="relationshipRef"
                    v-model="params.add.relationship"
                    custom-class="full"
                    name="relationship"
                    place-holder="続柄"
                    rules="required|max:100"
                />
            </div>
            <div class="flex-1">
                <ShortInput
                    ref="annualIncomeRef"
                    v-model="params.add.annual_income"
                    custom-class="full"
                    name="annual_income"
                    place-holder="追加する方の年収（概算）"
                    rules="required|max:100"
                />
            </div>
        </div>

        <ShortInput
            ref="addReasonRef"
            v-model="params.add.reason"
            custom-class="full"
            name="add_reason"
            place-holder="追加する事由"
            rules="required|max:1000"
        />

        <div class="flex gap-8 under960:flex-col">
            <div class="flex-1">
                <ShortInput
                    ref="addNameRef"
                    v-model="params.add.name"
                    custom-class="full"
                    name="add_name"
                    place-holder="追加する方の氏名（漢字）"
                    rules="required|max:100"
                />
            </div>
            <div class="flex-1">
                <ShortInput
                    ref="addNameKanaRef"
                    v-model="params.add.name_kana"
                    custom-class="full"
                    name="add_name_kana"
                    place-holder="追加する方の氏名（カナ）"
                    rules="required|max:100"
                />
            </div>
        </div>

        <div class="flex gap-8 under960:flex-col">
            <div class="flex-1">
                <ShortInput
                    ref="addBirthdayRef"
                    v-model="params.add.birth_date"
                    custom-class="full"
                    name="add_birth_date"
                    place-holder="追加する方の生年月日"
                    rules="required"
                    type="date"
                />
            </div>
            <div class="flex-1 flex">
                <div>                
                    <div class="dependent-field-label">追加する方の性別</div>
                    <div class="dependent-radio-row">
                        <label v-for="gender in genderOptions" :key="gender" class="dependent-radio-label">
                            <input v-model="params.add.gender" class="custom-f-radio" type="radio" name="add_gender" :value="gender" />
                            {{ gender }}
                        </label>
                    </div>
                </div>
                <ShortInput
                    v-if="params.add.gender === 'その他'"
                    ref="addGenderOtherRef"
                    v-model="params.add.gender_other"
                    custom-class="full"
                    name="add_gender_other"
                    place-holder="その他"
                    rules="required|max:100"
                    class="ml-3"
                />
                <p v-if="genderError" class="dependent-change-error mt-1">{{ genderError }}</p>
            </div>
        </div>

        <LongInput
            ref="addAddressRef"
            v-model="params.add.address"
            custom-class="full"
            name="add_address"
            place-holder="追加する方の住所"
            rules="required|max:1000"
        />

        <ShortInput
            ref="retiredOnRef"
            v-model="params.add.retired_on"
            class="fit min-w-[300px]"
            name="retired_on"
            place-holder="追加される方が退職された場合は退職日を入力"
            type="date"
        />
    </div>

    <div v-if="params.mode === 'remove'" class="space-y-8">
        <ShortInput
            ref="removeDateRef"
            v-model="params.remove.effective_date"
            class="fit"
            name="remove_effective_date"
            place-holder="削除する日付"
            rules="required"
            type="date"
        />

        <ShortInput
            ref="removeReasonRef"
            v-model="params.remove.reason"
            custom-class="full"
            name="remove_reason"
            place-holder="削除する事由"
            rules="required|max:1000"
        />

        <div class="flex gap-8 under960:flex-col">
            <div class="flex-1">
                <ShortInput
                    ref="removeNameRef"
                    v-model="params.remove.name"
                    custom-class="full"
                    name="remove_name"
                    place-holder="削除する方の氏名（漢字）"
                    rules="required|max:100"
                />
            </div>
            <div class="flex-1">
                <ShortInput
                    ref="removeNameKanaRef"
                    v-model="params.remove.name_kana"
                    custom-class="full"
                    name="remove_name_kana"
                    place-holder="削除する方の氏名（カナ）"
                    rules="required|max:100"
                />
            </div>
        </div>

        <div class="flex gap-8 under960:flex-col">
            <div class="flex-1">
                <ShortInput
                    ref="removeBirthdayRef"
                    v-model="params.remove.birth_date"
                    custom-class="full"
                    name="remove_birth_date"
                    place-holder="削除する方の生年月日"
                    rules="required"
                    type="date"
                />
            </div>
            <div class="flex-1">
                <ShortInput
                    ref="employmentOnRef"
                    v-model="params.remove.employment_on"
                    custom-class="full"
                    name="employment_on"
                    place-holder="削除する方が就職される場合は就職される日付"
                    type="date"
                />
            </div>
        </div>
    </div>
</div>
</template>

<script setup lang="ts">
import { reactive, ref, watch } from 'vue';
import LongInput from '@/components/Form/LongInput.vue';
import ShortInput from '@/components/Form/ShortInput.vue';

type DependentMode = 'add' | 'remove'
type Gender = '男' | '女' | 'その他' | ''

type AddDependentPayload = {
    effective_date: string
    relationship: string
    reason: string
    name: string
    name_kana: string
    birth_date: string
    gender: Gender
    gender_other: string
    annual_income: string
    address: string
    retired_on: string
}

type RemoveDependentPayload = {
    effective_date: string
    reason: string
    name: string
    name_kana: string
    birth_date: string
    employment_on: string
}

type DependentChangePayload = {
    mode: DependentMode | ''
    add: AddDependentPayload
    remove: RemoveDependentPayload
}

const modeOptions: { label: string; value: DependentMode }[] = [
    { label: '追加', value: 'add' },
    { label: '削除', value: 'remove' },
]
const genderOptions: Gender[] = ['男', '女', 'その他']

const params = reactive<DependentChangePayload>({
    mode: '',
    add: {
        effective_date: '',
        relationship: '',
        reason: '',
        name: '',
        name_kana: '',
        birth_date: '',
        gender: '',
        gender_other: '',
        annual_income: '',
        address: '',
        retired_on: '',
    },
    remove: {
        effective_date: '',
        reason: '',
        name: '',
        name_kana: '',
        birth_date: '',
        employment_on: '',
    },
})

const modeError = ref('')
const genderError = ref('')

const addDateRef = ref<InstanceType<typeof ShortInput> | null>(null)
const relationshipRef = ref<InstanceType<typeof ShortInput> | null>(null)
const addReasonRef = ref<InstanceType<typeof ShortInput> | null>(null)
const addNameRef = ref<InstanceType<typeof ShortInput> | null>(null)
const addNameKanaRef = ref<InstanceType<typeof ShortInput> | null>(null)
const addBirthdayRef = ref<InstanceType<typeof ShortInput> | null>(null)
const addGenderOtherRef = ref<InstanceType<typeof ShortInput> | null>(null)
const annualIncomeRef = ref<InstanceType<typeof ShortInput> | null>(null)
const addAddressRef = ref<InstanceType<typeof LongInput> | null>(null)
const retiredOnRef = ref<InstanceType<typeof ShortInput> | null>(null)

const removeDateRef = ref<InstanceType<typeof ShortInput> | null>(null)
const removeReasonRef = ref<InstanceType<typeof ShortInput> | null>(null)
const removeNameRef = ref<InstanceType<typeof ShortInput> | null>(null)
const removeNameKanaRef = ref<InstanceType<typeof ShortInput> | null>(null)
const removeBirthdayRef = ref<InstanceType<typeof ShortInput> | null>(null)
const employmentOnRef = ref<InstanceType<typeof ShortInput> | null>(null)

watch(() => params.add.gender, (gender) => {
    if (gender !== 'その他') {
        params.add.gender_other = ''
    }
})

const validateTargets = async (targets: Array<{ validate?: () => Promise<{ valid: boolean }> } | null>) => {
    let result = true
    for (const target of targets) {
        const validation = await target?.validate?.() || { valid: false }
        result = result && validation.valid
    }

    return result
}

const validateAdd = async () => {
    const targets = [
        addDateRef.value,
        relationshipRef.value,
        addReasonRef.value,
        addNameRef.value,
        addNameKanaRef.value,
        addBirthdayRef.value,
        annualIncomeRef.value,
        addAddressRef.value,
        retiredOnRef.value,
    ]

    if (params.add.gender === 'その他') {
        targets.push(addGenderOtherRef.value)
    }

    genderError.value = params.add.gender ? '' : '性別を選択してください。'

    return await validateTargets(targets) && !genderError.value
}

const validateRemove = async () => {
    return await validateTargets([
        removeDateRef.value,
        removeReasonRef.value,
        removeNameRef.value,
        removeNameKanaRef.value,
        removeBirthdayRef.value,
        employmentOnRef.value,
    ])
}

const validate = async () => {
    modeError.value = params.mode ? '' : '追加または削除を選択してください。'
    if (modeError.value) return false

    if (params.mode === 'add') {
        return await validateAdd()
    }

    return await validateRemove()
}

const getPayload = () => ({
    mode: params.mode,
    detail: params.mode === 'add'
        ? {
            ...params.add,
            gender: params.add.gender === 'その他' ? params.add.gender_other : params.add.gender,
        }
        : { ...params.remove },
})

defineExpose({ validate, getPayload })
</script>

<style scoped>
.dependent-mode-grid {
    display: grid;
    gap: 14px;
    grid-template-columns: repeat(2, minmax(0, 1fr));
}

.dependent-mode-card {
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

.dependent-mode-card.selected {
    box-shadow: 0 0 0 1px var(--primary-color);
}

.dependent-field-label {
    color: var(--primary-color);
    font-size: 13px;
    font-weight: 700;
    margin-bottom: 10px;
}

.dependent-radio-row {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
}

.dependent-radio-label {
    align-items: center;
    color: var(--primary-color);
    cursor: pointer;
    display: flex;
    font-size: 13px;
    gap: 8px;
}

.dependent-change-error {
    color: tomato;
    font-size: 11px;
}

@media screen and (max-width: 639px) {
    .dependent-mode-grid {
        grid-template-columns: 1fr;
    }
}
</style>
