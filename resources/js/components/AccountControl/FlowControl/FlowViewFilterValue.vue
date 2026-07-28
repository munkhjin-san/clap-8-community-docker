<template>
    <!-- option fields -->
    <div v-if="options.length && multiMode" class="fv-multi">
        <label v-for="o in options" :key="o" class="fv-opt">
            <input type="checkbox" :checked="arr.includes(o)" @change="toggle(o)"> {{ o }}
        </label>
    </div>
    <FlowSearchSelect v-else-if="options.length" :model-value="single || null" :options="optionChoices" placeholder="選択" @update:model-value="(val) => single = val" />

    <!-- user / member -->
    <select v-else-if="isUser && multiMode" multiple v-model="arrModel" class="custom-a-input !box-border w-full fv-userlist">
        <option v-for="u in users || []" :key="u.id" :value="u.id">{{ u.name }}</option>
    </select>
    <FlowSearchSelect v-else-if="isUser" :model-value="singleNum || null" :options="userOptions" placeholder="ユーザーを選択" @update:model-value="(val) => singleNum = val" />

    <!-- typed inputs -->
    <input v-else-if="type === 'number' || type === '$number'" type="number" v-model="single" class="custom-a-input !box-border w-full">
    <!-- date/datetime: pick a moving target (今日/今月/…) or a fixed date. A saved view with 今月
         keeps meaning "this month" next month; a fixed date would silently go stale. -->
    <div v-else-if="isDateType" class="fv-date">
        <select :value="dateMode" class="custom-a-input !box-border w-full" @change="onDateModeChange">
            <option value="fixed">日付を指定</option>
            <option v-for="(label, token) in DYNAMIC_DATE_TOKENS" :key="token" :value="token">{{ label }}</option>
        </select>
        <input
            v-if="dateMode === 'fixed'"
            :type="type === 'date' ? 'date' : 'datetime-local'"
            v-model="single"
            class="custom-a-input !box-border w-full"
            :style="{ colorScheme: nativeScheme }"
        >
    </div>
    <input v-else-if="type === 'time'" type="time" v-model="single" class="custom-a-input !box-border w-full" :style="{ colorScheme: nativeScheme }">
    <select v-else-if="type === 'toggle'" v-model="single" class="custom-a-input !box-border w-full">
        <option value="true">オン</option>
        <option value="false">オフ</option>
    </select>
    <input v-else type="text" v-model="single" class="custom-a-input !box-border w-full" placeholder="値">
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { isSystemColumn, FLOW_SYS_STATUS } from '@/types/flow'
import type { FlowField, FlowOptionUser, FlowViewOperator } from '@/types/flow'
import FlowSearchSelect from './FlowSearchSelect.vue'
import { useTheme } from '@/store/theme'
import { DYNAMIC_DATE_PREFIX, DYNAMIC_DATE_TOKENS, isDynamicDate } from '@/utils/flowDynamicDate'

const props = defineProps<{
    fieldRef: number | string
    operator: FlowViewOperator
    fields: FlowField[]
    users?: FlowOptionUser[]
    statuses?: string[]
    modelValue: any[]
}>()
const emit = defineEmits<{ 'update:modelValue': [any[]] }>()
const theme = useTheme()
// native date/time pickers render their icon per `color-scheme`; follow the app theme (dark-mode visibility)
const nativeScheme = computed(() => (theme.dark ? 'dark' : 'light'))

const field = computed(() => (isSystemColumn(props.fieldRef) ? null : props.fields.find((f) => f.id === Number(props.fieldRef)) ?? null))
const type = computed<string>(() => {
    if (isSystemColumn(props.fieldRef)) {
        return props.fieldRef === '$record_number' ? '$number' : props.fieldRef === FLOW_SYS_STATUS ? '$status' : '$datetime'
    }
    return field.value?.input_type ?? 'short'
})
const options = computed<string[]>(() => {
    if (props.fieldRef === FLOW_SYS_STATUS) return props.statuses ?? []
    return ['select', 'radio', 'checkbox'].includes(type.value) ? (field.value?.options ?? []) : []
})
const isUser = computed(() => type.value === 'user' || type.value === 'member')
const userOptions = computed(() => (props.users || []).map((u) => ({ value: u.id, label: u.name })))
const optionChoices = computed(() => options.value.map((o) => ({ value: o, label: o })))
const multiMode = computed(() => props.operator === 'includes_any')

/* ---- date / datetime: fixed value vs dynamic token ---- */
const isDateType = computed(() => ['date', 'datetime', '$datetime'].includes(type.value))
// the stored value IS the mode: "@today" => dynamic, anything else => a fixed date
const dateMode = computed(() => (isDynamicDate(props.modelValue?.[0]) ? String(props.modelValue[0]).slice(1) : 'fixed'))
const onDateModeChange = (e: Event) => {
    const mode = (e.target as HTMLSelectElement).value
    // switching to a token replaces the value outright; switching back clears it so the
    // date input starts empty rather than showing a stale "@today"
    emit('update:modelValue', mode === 'fixed' ? [] : [DYNAMIC_DATE_PREFIX + mode])
}

const single = computed({
    get: () => (props.modelValue?.[0] ?? '') as any,
    set: (v: any) => emit('update:modelValue', v === '' || v == null ? [] : [v]),
})
const singleNum = computed({
    get: () => (props.modelValue?.[0] ?? '') as any,
    set: (v: any) => emit('update:modelValue', v === '' || v == null ? [] : [Number(v)]),
})
const arr = computed<any[]>(() => (Array.isArray(props.modelValue) ? props.modelValue : []))
const arrModel = computed({
    get: () => arr.value,
    set: (v: any[]) => emit('update:modelValue', v.map(Number)),
})
const toggle = (o: string) => {
    const next = arr.value.slice()
    const i = next.indexOf(o)
    if (i >= 0) next.splice(i, 1)
    else next.push(o)
    emit('update:modelValue', next)
}
</script>

<style scoped>
/* mode + date sit side by side. The minimums are floors, not guesses at a split: a native date
   input needs ~135px to show 2026/07/28 plus its picker icon, and squeezing it below that clips
   the day silently. Where the cell can't seat both (the ad-hoc filter modal), they wrap instead. */
.fv-date { display: flex; flex-wrap: wrap; gap: 6px; }
/* explicit bases, not auto: both children carry w-full, and `flex-basis: auto` would resolve to
   that 100% and push each onto its own row even in a cell with room for the pair */
.fv-date > select { flex: 1 1 100px; min-width: 100px; }
.fv-date > input { flex: 1 1 135px; min-width: 135px; }
.fv-multi { display: flex; flex-wrap: wrap; gap: 8px; padding: 4px 0; }
.fv-opt { font-size: 12px; display: inline-flex; align-items: center; gap: 4px; cursor: pointer; }
.fv-userlist { min-height: 64px; }
</style>
