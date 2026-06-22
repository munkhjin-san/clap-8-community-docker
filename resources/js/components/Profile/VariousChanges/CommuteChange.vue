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
            rules="required"
        />

        <div class="flex gap-8 under960:flex-col">
            <div class="flex-1">
                <ShortInput
                    ref="publicPassAmountRef"
                    v-model="params.public_transportation.pass_amount"
                    custom-class="full"
                    name="public_pass_amount"
                    place-holder="定期金額"
                    rules="required"
                />
            </div>
            <div class="flex-1">
                <ShortInput
                    ref="publicOneWayFareRef"
                    v-model="params.public_transportation.one_way_fare"
                    custom-class="full"
                    name="public_one_way_fare"
                    place-holder="片道代"
                />
            </div>
            <div class="flex-1">
                <ShortInput
                    ref="publicOtherAmountRef"
                    v-model="params.public_transportation.other_amount"
                    custom-class="full"
                    name="public_other_amount"
                    place-holder="その他の交通費金額（バス代等）"
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
            自家用車での通勤の方は、燃料種別や自宅から勤務地までの距離をご入力ください。なお、自家用車での通勤の場合、車検証の写しを提出していただく必要があります。
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
                    ref="carFuelTypeRef"
                    v-model="params.car.fuel_type"
                    :clearable="false"
                    :close-on-select="true"
                    :multiple="false"
                    :options="fuelTypeOptions"
                    class="commute-change-selector"
                    name="fuel_type"
                    place-holder="燃料を選択してください"
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
                    rules="required"
                    class="fit"
                    type="number"
                />
            </div>
        </div>
        <div class="commute-change-file">
            <FileUploader
                ref="vehicleInspectionFilesRef"
                v-model="params.car.vehicle_inspection_files"
                accept=".jpg,.jpeg,.png,.pdf"
                custom-place-holder="車検証"
                path="/various_changes/commute_change/vehicle_inspection"
                rules="required"
            />
            <p v-if="vehicleInspectionFileError" class="commute-change-error">{{ vehicleInspectionFileError }}</p>
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
            雨天時に利用する通勤方法も入力してください。<br>
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
                    rules="required"
                />
            </div>
            
        </div>


       

        <div class="flex gap-8 under960:flex-col">
             <div class="flex-1">
                <ShortInput
                    ref="bicycleRainyCommuteMethodRef"
                    v-model="params.bicycle.rainy_commute_method"
                    custom-class="full"
                    name="bicycle_rainy_commute_method"
                    place-holder="雨天時の通勤方法"
                    rules="required"
                />
            </div>
            <div class="flex-1">
                <ShortInput
                    ref="bicycleOtherAmountRef"
                    v-model="params.bicycle.other_amount"
                    custom-class="full"
                    name="bicycle_other_amount"
                    place-holder="その他の交通費金額（定期対象外のバス代等）"
                />
            </div>
            <div class="flex-1">
                <ShortInput
                    ref="bicycleParkingAmountRef"
                    v-model="params.bicycle.parking_amount"
                    custom-class="full"
                    name="bicycle_parking_amount"
                    place-holder="駐輪場代"
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

    <div v-else-if="params.mode === 'walking'" class="space-y-8">
        <ShortInput
            ref="walkingEffectiveDateRef"
            v-model="params.walking.effective_date"
            class="fit"
            name="walking_effective_date"
            place-holder="通勤変更適用日"
            rules="required"
            type="date"
        />
    </div>
</div>
</template>

<script setup lang="ts">
import type { Component } from 'vue';
import { reactive, ref, watch } from 'vue';
import FileUploader from '@/components/Form/FileUploader.vue';
import ItemSelector from '@/components/Form/ItemSelector.vue';
import LongInput from '@/components/Form/LongInput.vue';
import ShortInput from '@/components/Form/ShortInput.vue';
import BicycleChangeIcon from './Icons/BicycleChange.vue';
import CarChangeIcon from './Icons/CarChange.vue';
import PublicTransportationChangeIcon from './Icons/PublicTransportationChange.vue';
import WalkingChangeIcon from './Icons/WalkingChange.vue';

type CommuteMode = 'public_transportation' | 'car' | 'bicycle' | 'walking'
type FuelType = 'レギュラー' | 'ハイオク'

type UploadedFile = {
    id: number
    [key: string]: unknown
}

type PublicTransportationPayload = {
    effective_date: string
    route: string
    pass_amount: string
    one_way_fare: string
    other_amount: string
    share_with_pm: boolean
}

type CarPayload = {
    effective_date: string
    one_way_distance: string
    fuel_type: FuelType
    vehicle_inspection_files: UploadedFile[]
    share_with_pm: boolean
}

type BicyclePayload = {
    effective_date: string
    route: string
    rainy_commute_method: string
    other_amount: string
    parking_amount: string
    share_with_pm: boolean
}

type WalkingPayload = {
    effective_date: string
}

type CommuteChangePayload = {
    mode: CommuteMode | ''
    public_transportation: PublicTransportationPayload
    car: CarPayload
    bicycle: BicyclePayload
    walking: WalkingPayload
}

const modeOptions: { label: string; value: CommuteMode; icon: Component }[] = [
    { label: '公共交通機関', value: 'public_transportation', icon: PublicTransportationChangeIcon },
    { label: '自家用車', value: 'car', icon: CarChangeIcon },
    { label: '自転車通勤', value: 'bicycle', icon: BicycleChangeIcon },
    { label: '徒歩', value: 'walking', icon: WalkingChangeIcon },
]

const fuelTypeOptions: FuelType[] = ['レギュラー', 'ハイオク']

const params = reactive<CommuteChangePayload>({
    mode: '',
    public_transportation: {
        effective_date: '',
        route: '',
        pass_amount: '',
        one_way_fare: '',
        other_amount: '',
        share_with_pm: false,
    },
    car: {
        effective_date: '',
        one_way_distance: '',
        fuel_type: 'レギュラー',
        vehicle_inspection_files: [],
        share_with_pm: false,
    },
    bicycle: {
        effective_date: '',
        route: '',
        rainy_commute_method: '',
        other_amount: '',
        parking_amount: '',
        share_with_pm: false,
    },
    walking: {
        effective_date: '',
    },
})

const modeError = ref('')
const shareWithPmError = ref('')
const vehicleInspectionFileError = ref('')

const publicEffectiveDateRef = ref<InstanceType<typeof ShortInput> | null>(null)
const publicRouteRef = ref<InstanceType<typeof LongInput> | null>(null)
const publicPassAmountRef = ref<InstanceType<typeof ShortInput> | null>(null)
const publicOneWayFareRef = ref<InstanceType<typeof ShortInput> | null>(null)
const publicOtherAmountRef = ref<InstanceType<typeof ShortInput> | null>(null)
const carEffectiveDateRef = ref<InstanceType<typeof ShortInput> | null>(null)
const carOneWayDistanceRef = ref<InstanceType<typeof ShortInput> | null>(null)
const carFuelTypeRef = ref<InstanceType<typeof ItemSelector> | null>(null)
const vehicleInspectionFilesRef = ref<InstanceType<typeof FileUploader> | null>(null)
const bicycleEffectiveDateRef = ref<InstanceType<typeof ShortInput> | null>(null)
const bicycleRouteRef = ref<InstanceType<typeof ShortInput> | null>(null)
const bicycleRainyCommuteMethodRef = ref<InstanceType<typeof ShortInput> | null>(null)
const bicycleOtherAmountRef = ref<InstanceType<typeof ShortInput> | null>(null)
const bicycleParkingAmountRef = ref<InstanceType<typeof ShortInput> | null>(null)
const walkingEffectiveDateRef = ref<InstanceType<typeof ShortInput> | null>(null)

watch(() => params.mode, () => {
    modeError.value = ''
    shareWithPmError.value = ''
    vehicleInspectionFileError.value = ''
})

const validateTargets = async (targets: Array<{ validate?: () => Promise<{ valid: boolean } | undefined> } | null>) => {
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
    ])

    shareWithPmError.value = params.public_transportation.share_with_pm ? '' : 'PMへの共有確認が必要です。'

    return validInputs && !shareWithPmError.value
}

const validateCar = async () => {
    const validInputs = await validateTargets([
        carEffectiveDateRef.value,
        carOneWayDistanceRef.value,
        carFuelTypeRef.value,
        vehicleInspectionFilesRef.value,
    ])

    vehicleInspectionFileError.value = params.car.vehicle_inspection_files.length > 1 ? '車検証は1ファイルのみ添付してください。' : ''
    shareWithPmError.value = params.car.share_with_pm ? '' : 'PMへの共有確認が必要です。'

    return validInputs && !vehicleInspectionFileError.value && !shareWithPmError.value
}

const validateBicycle = async () => {
    const validInputs = await validateTargets([
        bicycleEffectiveDateRef.value,
        bicycleRouteRef.value,
        bicycleRainyCommuteMethodRef.value,
    ])

    shareWithPmError.value = params.bicycle.share_with_pm ? '' : 'PMへの共有確認が必要です。'

    return validInputs && !shareWithPmError.value
}

const validateWalking = async () => {
    return await validateTargets([
        walkingEffectiveDateRef.value,
    ])
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

    if (params.mode === 'walking') {
        return await validateWalking()
    }

    return false
}

const getPayload = () => ({
    mode: params.mode,
    detail: params.mode === 'public_transportation'
        ? { ...params.public_transportation }
        : params.mode === 'car'
            ? {
                ...params.car,
                vehicle_inspection_file_ids: params.car.vehicle_inspection_files.map(file => file.id),
            }
            : params.mode === 'bicycle'
                ? {
                    ...params.bicycle,
                    other_amount: String(params.bicycle.other_amount ?? ''),
                    parking_amount: String(params.bicycle.parking_amount ?? ''),
                }
                : params.mode === 'walking'
                    ? { ...params.walking }
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

.commute-change-file {
    display: flex;
    flex-direction: column;
    gap: 6px;
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
