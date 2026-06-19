<template>
<div class="space-y-8">
    <div class="commute-mode-grid" role="radiogroup" aria-label="交通費変更の種類">
        <label
            v-for="option in modeOptions"
            :key="option.value"
            :class="['commute-mode-card', { selected: params.mode === option.value }]"
        >
            <input
                v-model="params.mode"
                class="sr-only"
                name="commute_mode"
                type="radio"
                :value="option.value"
            />
            <component :is="option.icon" />
            <span>{{ option.label }}</span>
        </label>
    </div>
    <p v-if="modeError" class="commute-change-error">{{ modeError }}</p>

    <div v-if="params.mode === 'public_transportation'" class="space-y-8">
        <p class="commute-change-note">
            公共交通機関での通勤の方は、最寄りのバス停や電車の駅をご入力ください。また支給されるのは定期金額となりますので、定期金額の入力もお願いします。
        </p>

        <ShortInput
            ref="publicEffectiveDateRef"
            v-model="params.public_transportation.effective_date"
            class="fit"
            name="public_effective_date"
            place-holder="通勤変更適用日"
            rules="required"
            type="date"
        />

        <LongInput
            ref="publicRouteRef"
            v-model="params.public_transportation.route"
            custom-class="full"
            name="public_route"
            place-holder="公共交通機関のルート"
            rules="required|max:1000"
        />

        <div class="flex gap-8 under960:flex-col">
            <div class="flex-1">
                <ShortInput
                    ref="publicPassAmountRef"
                    v-model="params.public_transportation.pass_amount"
                    custom-class="full"
                    name="public_pass_amount"
                    place-holder="定期金額"
                    rules="required|max:100"
                />
            </div>
            <div class="flex-1">
                <ShortInput
                    ref="publicOtherAmountRef"
                    v-model="params.public_transportation.other_amount"
                    custom-class="full"
                    name="public_other_amount"
                    place-holder="その他の交通費金額（バス代等）"
                    rules="max:100"
                />
            </div>
        </div>

        <div>
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" v-model="params.public_transportation.share_with_pm" class="custom-f-checkbox" />
                今回の変更をPMに共有済み。
            </label>
            <p v-if="shareWithPmError" class="commute-change-error mt-1">{{ shareWithPmError }}</p>
        </div>
    </div>

    <div v-else-if="params.mode === 'car'" class="space-y-8">
        <p class="commute-change-note">
            自家用車での通勤の方は、車の車種（普通自動車／軽自動車）や自宅から勤務地までの距離をご入力ください。なお、自家用車での通勤の場合、車の任意保険加入証明書の写し／車検証の写しを提出していただく必要があります。
        </p>
        <div class="flex gap-8 under960:flex-col">
            <ShortInput
                ref="carEffectiveDateRef"
                v-model="params.car.effective_date"
                class="fit"
                name="car_effective_date"
                place-holder="通勤変更適用日"
                rules="required"
                custom-class="min-h-[40px]"
                type="date"
            />

            <div class="min-w-[200px]">
                <ItemSelector
                    ref="carTypeRef"
                    v-model="params.car.car_type"
                    :clearable="true"
                    :close-on-select="true"
                    :multiple="false"
                    :options="carTypeOptions"
                    class="commute-change-selector"
                    name="car_type"
                    place-holder="車種を選択してください"
                    rules="required"
                />
            </div>
            <div class="flex-1">
                <ShortInput
                    ref="carOneWayDistanceRef"
                    v-model="params.car.one_way_distance"
                    custom-class="min-h-[40px]"
                    name="car_one_way_distance"
                    place-holder="片道の距離（km）"
                    rules="required|max:100"
                    class="fit"
                    type="number"
                />
            </div>
        </div>
        <div>
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" v-model="params.car.share_with_pm" class="custom-f-checkbox" />
                今回の変更をPMに共有済み。
            </label>
            <p v-if="shareWithPmError" class="commute-change-error mt-1">{{ shareWithPmError }}</p>
        </div>
    </div>

    <div v-else-if="params.mode === 'bicycle'" class="space-y-8">
        <p class="commute-change-note">
            自転車通勤を希望する方、または公共交通機関から自転車通勤に変更される方は、最寄りのバス停や電車の駅をご入力ください。<br>
            支給されるのは定期金額の半額となりますので、定期金額の入力もお願いします。<br>
            駐輪場においては、自己負担となります。<br>
            ※通勤場所までが2km以内で通勤手当支給対象外の場合、駐輪場代は会社負担となります。
        </p>
        <div class="flex under960:flex-col gap-8">
            <ShortInput
                ref="bicycleEffectiveDateRef"
                v-model="params.bicycle.effective_date"
                class="fit"
                name="bicycle_effective_date"
                place-holder="通勤変更適用日"
                rules="required"
                type="date"
            />
            <div class="flex-1">
                <ShortInput
                    ref="bicycleRouteRef"
                    v-model="params.bicycle.route"
                    custom-class="full"
                    name="bicycle_route"
                    place-holder="公共交通機関のルート"
                    rules="required|max:1000"
                />
            </div>
            
        </div>


       

        <div class="flex gap-8 under960:flex-col">
             <div class="flex-1">
                <ShortInput
                    ref="bicyclePassAmountRef"
                    v-model="params.bicycle.pass_amount"
                    custom-class="full"
                    name="bicycle_pass_amount"
                    place-holder="定期金額"
                    rules="required|max:100"
                    type="number"
                />
                <p class="commute-change-field-note">
                    ※通勤場所までの距離が2km以上の場合、定期金額の半額が通勤手当として支給されます。ここには、定期金額の満額を記載してください。
                </p>
            </div>
            <div class="flex-1">
                <ShortInput
                    ref="bicycleOtherAmountRef"
                    v-model="params.bicycle.other_amount"
                    custom-class="full"
                    name="bicycle_other_amount"
                    place-holder="その他の交通費金額（定期対象外のバス代等）"
                    type="number"
                />
            </div>
            <div class="flex-1">
                <ShortInput
                    ref="bicycleParkingAmountRef"
                    v-model="params.bicycle.parking_amount"
                    custom-class="full"
                    name="bicycle_parking_amount"
                    place-holder="駐輪場代"
                    type="number"
                />
                <p class="commute-change-field-note">
                    通勤場所までの距離が2km未満の場合、駐輪場代は会社が負担します。※通勤手当対象者は記載不要
                </p>
            </div>
        </div>

        <div>
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" v-model="params.bicycle.share_with_pm" class="custom-f-checkbox" />
                今回の変更をPMに共有済み。
            </label>
            <p v-if="shareWithPmError" class="commute-change-error mt-1">{{ shareWithPmError }}</p>
        </div>
    </div>

    <div v-else-if="params.mode && params.mode !== 'walking'" class="commute-change-empty">
        詳細項目は後で追加します。
    </div>
</div>
</template>

<script setup lang="ts">
import type { Component } from 'vue';
import { reactive, ref } from 'vue';
import ItemSelector from '@/components/Form/ItemSelector.vue';
import LongInput from '@/components/Form/LongInput.vue';
import ShortInput from '@/components/Form/ShortInput.vue';
import BicycleChangeIcon from './Icons/BicycleChange.vue';
import CarChangeIcon from './Icons/CarChange.vue';
import PublicTransportationChangeIcon from './Icons/PublicTransportationChange.vue';
import WalkingChangeIcon from './Icons/WalkingChange.vue';

type CommuteMode = 'public_transportation' | 'car' | 'bicycle' | 'walking'

type PublicTransportationPayload = {
    effective_date: string
    route: string
    pass_amount: string
    other_amount: string
    share_with_pm: boolean
}

type CarPayload = {
    effective_date: string
    one_way_distance: string
    car_type: string
    share_with_pm: boolean
}

type BicyclePayload = {
    effective_date: string
    route: string
    pass_amount: string
    other_amount: string
    parking_amount: string
    share_with_pm: boolean
}

type CommuteChangePayload = {
    mode: CommuteMode | ''
    public_transportation: PublicTransportationPayload
    car: CarPayload
    bicycle: BicyclePayload
}

const modeOptions: { label: string; value: CommuteMode; icon: Component }[] = [
    { label: '公共交通機関', value: 'public_transportation', icon: PublicTransportationChangeIcon },
    { label: '自家用車', value: 'car', icon: CarChangeIcon },
    { label: '自転車通勤', value: 'bicycle', icon: BicycleChangeIcon },
    { label: '徒歩', value: 'walking', icon: WalkingChangeIcon },
]

const carTypeOptions = [
    '普通自動車',
    '軽自動車',
    '自動二輪（大型）',
    '自動二輪（中型）',
    '原動機付自転車',
]

const params = reactive<CommuteChangePayload>({
    mode: '',
    public_transportation: {
        effective_date: '',
        route: '',
        pass_amount: '',
        other_amount: '',
        share_with_pm: false,
    },
    car: {
        effective_date: '',
        one_way_distance: '',
        car_type: '',
        share_with_pm: false,
    },
    bicycle: {
        effective_date: '',
        route: '',
        pass_amount: '',
        other_amount: '',
        parking_amount: '',
        share_with_pm: false,
    },
})

const modeError = ref('')
const shareWithPmError = ref('')

const publicEffectiveDateRef = ref<InstanceType<typeof ShortInput> | null>(null)
const publicRouteRef = ref<InstanceType<typeof LongInput> | null>(null)
const publicPassAmountRef = ref<InstanceType<typeof ShortInput> | null>(null)
const publicOtherAmountRef = ref<InstanceType<typeof ShortInput> | null>(null)
const carEffectiveDateRef = ref<InstanceType<typeof ShortInput> | null>(null)
const carOneWayDistanceRef = ref<InstanceType<typeof ShortInput> | null>(null)
const carTypeRef = ref<InstanceType<typeof ItemSelector> | null>(null)
const bicycleEffectiveDateRef = ref<InstanceType<typeof ShortInput> | null>(null)
const bicycleRouteRef = ref<InstanceType<typeof ShortInput> | null>(null)
const bicyclePassAmountRef = ref<InstanceType<typeof ShortInput> | null>(null)
const bicycleOtherAmountRef = ref<InstanceType<typeof ShortInput> | null>(null)
const bicycleParkingAmountRef = ref<InstanceType<typeof ShortInput> | null>(null)

const validateTargets = async (targets: Array<{ validate?: () => Promise<{ valid: boolean }> } | null>) => {
    let result = true
    for (const target of targets) {
        const validation = await target?.validate?.() || { valid: false }
        result = result && validation.valid
    }

    return result
}

const validatePublicTransportation = async () => {
    const validInputs = await validateTargets([
        publicEffectiveDateRef.value,
        publicRouteRef.value,
        publicPassAmountRef.value,
        publicOtherAmountRef.value,
    ])

    shareWithPmError.value = params.public_transportation.share_with_pm ? '' : 'PMへの共有確認が必要です。'

    return validInputs && !shareWithPmError.value
}

const validateCar = async () => {
    const validInputs = await validateTargets([
        carEffectiveDateRef.value,
        carOneWayDistanceRef.value,
        carTypeRef.value,
    ])

    shareWithPmError.value = params.car.share_with_pm ? '' : 'PMへの共有確認が必要です。'

    return validInputs && !shareWithPmError.value
}

const validateBicycle = async () => {
    const validInputs = await validateTargets([
        bicycleEffectiveDateRef.value,
        bicycleRouteRef.value,
        bicyclePassAmountRef.value,
        bicycleOtherAmountRef.value,
        bicycleParkingAmountRef.value,
    ])

    shareWithPmError.value = params.bicycle.share_with_pm ? '' : 'PMへの共有確認が必要です。'

    return validInputs && !shareWithPmError.value
}

const validate = async () => {
    modeError.value = params.mode ? '' : '交通費変更の種類を選択してください。'
    if (modeError.value) return false

    if (params.mode === 'public_transportation') {
        return await validatePublicTransportation()
    }

    if (params.mode === 'car') {
        return await validateCar()
    }

    if (params.mode === 'bicycle') {
        return await validateBicycle()
    }

    return true
}

const getPayload = () => ({
    mode: params.mode,
    detail: params.mode === 'public_transportation'
        ? { ...params.public_transportation }
        : params.mode === 'car'
            ? { ...params.car }
            : params.mode === 'bicycle'
                ? { ...params.bicycle }
                : {},
})

defineExpose({ validate, getPayload })
</script>

<style scoped>
.commute-mode-grid {
    display: grid;
    gap: 14px;
    grid-template-columns: repeat(4, minmax(0, 1fr));
}

.commute-mode-card {
    display: flex;
    align-items: center;
    flex-direction: column;
    gap: 8px;
    justify-content: center;
    min-height: 86px;
    border: 1px solid var(--calendarBorder);
    color: var(--primary-color);
    cursor: pointer;
    font-size: 14px;
    font-weight: 700;
    text-align: center;
}

.commute-mode-card.selected {
    box-shadow: 0 0 0 1px var(--primary-color);
}

.commute-change-note {
    color: gray;
    font-size: 12px;
    line-height: 1.7;
}

.commute-change-field-note {
    color: gray;
    font-size: 11px;
    line-height: 1.6;
    margin-top: 8px;
}

.commute-change-selector {
    max-width: 360px;
}

.commute-change-error {
    color: tomato;
    font-size: 11px;
}

.commute-change-empty {
    color: gray;
    font-size: 13px;
    text-align: center;
}

@media screen and (max-width: 639px) {
    .commute-mode-grid {
        grid-template-columns: 1fr;
    }
}
</style>
