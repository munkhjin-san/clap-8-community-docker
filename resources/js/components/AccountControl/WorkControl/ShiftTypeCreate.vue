<template>
    <Modal @close="closeModal(false)">
        <template #title>
            <p>{{ editTarget ? 'シフト種別を編集する' : '新しいシフト種別を作成する' }}</p>
        </template>
        <template #content>
            <div class="si-box">
                <ShortInput name="shiftName" placeHolder="シフト種別名（必須）" :rules="'required'" customClass="full"
                    ref="nameRef" type="text" v-model="params.name" />
            </div>
            <div class="si-box">
                <ShortInput name="shiftAbbr" placeHolder="略称" customClass="full" type="text" v-model="params.abbreviation" />
            </div>
            <div class="si-box">
                <ItemSelector
                    ref="categoryRef"
                    name="shiftCategory"
                    placeHolder="カテゴリを選択（必須）"
                    rules="required"
                    label="label"
                    :multiple="false"
                    :options="categories"
                    :reduce="(o) => o.value"
                    :clearable="false"
                    :closeOnSelect="true"
                    v-model="params.category"
                />
                <p class="st-hint">給与・勤怠の計算に使われる種別です。</p>
            </div>
            <div class="si-box" v-if="isHourly">
                <ShortInput name="shiftHours" placeHolder="時間数（必須）" :rules="'required'" customClass="full"
                    ref="hoursRef" type="number" v-model="params.hours" />
            </div>
            <div class="si-box">
                <ShortInput name="shiftValue" placeHolder="値（数値・任意）" customClass="full" type="number" v-model="params.value" />
            </div>
            <div class="si-box shift-fullday">
                <input id="shiftFullDay" type="checkbox" v-model="params.full_day">
                <label for="shiftFullDay">終日扱いにする</label>
            </div>
            <div class="si-box shift-fullday">
                <input id="shiftActive" type="checkbox" v-model="params.active">
                <label for="shiftActive">有効（選択肢に表示する）</label>
            </div>
            <p class="st-hint">無効にすると入力画面の選択肢から隠れますが、既存の勤務時間計算には引き続き使われます。</p>
            <div class="si-box">
                <LoaderButton @triggered="save" :loading="loading" content="保存する" />
            </div>
        </template>
    </Modal>
</template>
<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue';
import Modal from '@/components/Global/Modal.vue';
import ShortInput from '@/components/Form/ShortInput.vue';
import ItemSelector from '@/components/Form/ItemSelector.vue';
import LoaderButton from '@/components/Global/LoaderButton.vue';
import { useApi } from '@/composables/api';

type CategoryOption = { value: string; label: string; hours: boolean }
type ShiftType = { id: number; name: string; abbreviation?: string | null; value?: number | null; full_day?: boolean | null; category?: string | null; hours?: number | null; active?: boolean | null }

// The seeded 慶弔/転勤/ODA records carry these fine sub-categories (which the
// attendance report routes on), but the catalog now offers only the unified
// `special_leave`. So when editing such a record we show the unified option and
// preserve the original sub-category on save unless the admin picks a new one.
const SPECIAL_LEAVE = 'special_leave'
const SPECIAL_LEAVE_SUBTYPES = ['special_leave_condolence', 'special_leave_transfer', 'special_leave_oda', 'comp_holiday']

const props = defineProps<{
    editTarget: ShiftType | null;
    categories: CategoryOption[];
}>()
const emit = defineEmits(['close'])
const api = useApi()

const originalCategory = props.editTarget?.category ?? ''

const params = reactive<{ id?: number; name: string; abbreviation: string; value: number | string | null; full_day: boolean; category: string; hours: number | string | null; active: boolean }>({
    id: props.editTarget?.id,
    name: props.editTarget?.name ?? '',
    abbreviation: props.editTarget?.abbreviation ?? '',
    value: props.editTarget?.value ?? null,
    full_day: !!props.editTarget?.full_day,
    category: SPECIAL_LEAVE_SUBTYPES.includes(originalCategory) ? SPECIAL_LEAVE : originalCategory,
    hours: props.editTarget?.hours ?? null,
    active: props.editTarget ? props.editTarget.active !== false : true,
})
const loading = ref(false)
const nameRef = ref<InstanceType<typeof ShortInput> | null>(null)
const hoursRef = ref<InstanceType<typeof ShortInput> | null>(null)
const categoryRef = ref<InstanceType<typeof ItemSelector> | null>(null)

const isHourly = computed(() => props.categories.find(c => c.value === params.category)?.hours ?? false)

// Clear hours when switching to a category that does not use it.
watch(() => params.category, () => {
    if (!isHourly.value) params.hours = null
})

const closeModal = (flag: boolean) => {
    emit('close', flag)
}

const save = async () => {
    const nameValid = ((await nameRef.value?.validate()) || { valid: false }).valid
    const categoryValid = ((await categoryRef.value?.validate()) || { valid: false }).valid
    const hoursValid = isHourly.value
        ? ((await hoursRef.value?.validate()) || { valid: false }).valid
        : true

    if (!nameValid || !categoryValid || !hoursValid) return

    // Preserve a seeded sub-category (慶弔/転勤/ODA) when the admin left the
    // unified "特別休暇" selected — only downgrade/route-change if they picked a
    // genuinely different category.
    const category = (params.category === SPECIAL_LEAVE && SPECIAL_LEAVE_SUBTYPES.includes(originalCategory))
        ? originalCategory
        : params.category

    const payload = {
        name: params.name,
        abbreviation: params.abbreviation?.trim() || null,
        value: params.value === '' || params.value === null ? null : Number(params.value),
        full_day: params.full_day,
        category,
        active: params.active,
        hours: isHourly.value ? (params.hours === '' || params.hours === null ? null : Number(params.hours)) : null,
    }

    // Re-categorizing an existing type changes its payroll/attendance mapping —
    // confirm before saving so it is never accidental.
    const recategorizing = !!params.id && params.category !== (props.editTarget?.category ?? '')

    loading.value = true
    try {
        let res
        if (params.id) {
            res = await api.patch(`/community_context/shift_types/${params.id}`, payload, {
                toast: 'シフト種別を保存しました',
                ...(recategorizing ? { ask: `「${params.name}」のカテゴリを変更すると、給与・勤怠の計算区分が変わります。変更しますか？` } : {}),
            })
        } else {
            res = await api.post('/community_context/shift_types', payload, { toast: 'シフト種別を保存しました' })
        }
        if (res === null) return // cancelled via confirm, or request failed — keep modal open
        emit('close', true)
    } finally {
        loading.value = false
    }
}
</script>
<style lang="scss" scoped>
.shift-fullday {
    display: flex;
    align-items: center;
    gap: 10px;
}
.st-hint {
    margin-top: 4px;
    font-size: 11px;
    color: var(--text-secondary, #888);
}
</style>
