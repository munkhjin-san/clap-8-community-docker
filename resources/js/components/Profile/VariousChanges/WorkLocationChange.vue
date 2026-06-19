<template>
<div class="space-y-8">
    <ShortInput
        ref="locationRef"
        v-model="params.work_location"
        custom-class="full"
        name="work_location"
        place-holder="勤務変更場所"
        rules="required|max:100"
    />

    <ShortInput
        ref="effectiveDateRef"
        v-model="params.effective_date"
        class="fit"
        name="effective_date"
        place-holder="勤務地変更日"
        rules="required"
        type="date"
    />

    <LongInput
        ref="routeRef"
        v-model="params.route"
        custom-class="full"
        name="route"
        place-holder="自宅から勤務地までの経路"
        rules="required|max:1000"
    />

    <div class="flex gap-8 under960:flex-col">
        <div class="flex-1">
            <ShortInput
                ref="monthlyPassAmountRef"
                v-model="params.monthly_pass_amount"
                custom-class="full"
                name="monthly_pass_amount"
                place-holder="公共交通機関の場合は定期金額_1か月分"
                rules="max:100"
            />
        </div>
        <div class="flex-1">
            <ShortInput
                ref="oneWayDistanceRef"
                v-model="params.one_way_distance"
                custom-class="full"
                name="one_way_distance"
                place-holder="マイカー通勤の場合は、距離_片道"
                rules="max:100"
            />
        </div>
    </div>

    <div>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" v-model="params.share_with_pm" class="custom-f-checkbox" />
            今回の変更をPMに共有済み。
        </label>
        <p v-if="shareWithPmError" class="work-location-error mt-1">{{ shareWithPmError }}</p>
    </div>

    <p class="work-location-note">
        住所変更・氏姓変更は、住民票を添付お願い致します。
    </p>
</div>
</template>

<script setup lang="ts">
import { reactive, ref } from 'vue';
import LongInput from '@/components/Form/LongInput.vue';
import ShortInput from '@/components/Form/ShortInput.vue';

type WorkLocationChangePayload = {
    work_location: string
    effective_date: string
    route: string
    monthly_pass_amount: string
    one_way_distance: string
    share_with_pm: boolean
}

const params = reactive<WorkLocationChangePayload>({
    work_location: '',
    effective_date: '',
    route: '',
    monthly_pass_amount: '',
    one_way_distance: '',
    share_with_pm: false,
})

const locationRef = ref<InstanceType<typeof ShortInput> | null>(null)
const effectiveDateRef = ref<InstanceType<typeof ShortInput> | null>(null)
const routeRef = ref<InstanceType<typeof LongInput> | null>(null)
const monthlyPassAmountRef = ref<InstanceType<typeof ShortInput> | null>(null)
const oneWayDistanceRef = ref<InstanceType<typeof ShortInput> | null>(null)
const shareWithPmError = ref('')

const validate = async () => {
    const targets = [
        locationRef.value,
        effectiveDateRef.value,
        routeRef.value,
        monthlyPassAmountRef.value,
        oneWayDistanceRef.value,
    ]

    let result = true
    for (const target of targets) {
        const validation = await target?.validate() || { valid: false }
        result = result && validation.valid
    }

    shareWithPmError.value = params.share_with_pm ? '' : 'PMへの共有確認が必要です。'

    return result && !shareWithPmError.value
}

const getPayload = () => ({ ...params })

defineExpose({ validate, getPayload })
</script>

<style scoped>
.work-location-error {
    color: tomato;
    font-size: 11px;
}

.work-location-note {
    color: gray;
    font-size: 12px;
}
</style>
