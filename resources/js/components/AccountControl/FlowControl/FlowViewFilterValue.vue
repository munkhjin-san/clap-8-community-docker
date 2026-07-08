<template>
    <!-- option fields -->
    <div v-if="options.length && multiMode" class="fv-multi">
        <label v-for="o in options" :key="o" class="fv-opt">
            <input type="checkbox" :checked="arr.includes(o)" @change="toggle(o)"> {{ o }}
        </label>
    </div>
    <select v-else-if="options.length" v-model="single" class="custom-a-input !box-border w-full">
        <option value="">--</option>
        <option v-for="o in options" :key="o" :value="o">{{ o }}</option>
    </select>

    <!-- user / member -->
    <select v-else-if="isUser && multiMode" multiple v-model="arrModel" class="custom-a-input !box-border w-full fv-userlist">
        <option v-for="u in users || []" :key="u.id" :value="u.id">{{ u.name }}</option>
    </select>
    <select v-else-if="isUser" v-model="singleNum" class="custom-a-input !box-border w-full">
        <option :value="''">--</option>
        <option v-for="u in users || []" :key="u.id" :value="u.id">{{ u.name }}</option>
    </select>

    <!-- typed inputs -->
    <input v-else-if="type === 'number' || type === '$number'" type="number" v-model="single" class="custom-a-input !box-border w-full">
    <input v-else-if="type === 'date'" type="date" v-model="single" class="custom-a-input !box-border w-full">
    <input v-else-if="type === 'datetime' || type === '$datetime'" type="datetime-local" v-model="single" class="custom-a-input !box-border w-full">
    <input v-else-if="type === 'time'" type="time" v-model="single" class="custom-a-input !box-border w-full">
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

const props = defineProps<{
    fieldRef: number | string
    operator: FlowViewOperator
    fields: FlowField[]
    users?: FlowOptionUser[]
    statuses?: string[]
    modelValue: any[]
}>()
const emit = defineEmits<{ 'update:modelValue': [any[]] }>()

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
const multiMode = computed(() => props.operator === 'includes_any')

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
.fv-multi { display: flex; flex-wrap: wrap; gap: 8px; padding: 4px 0; }
.fv-opt { font-size: 12px; display: inline-flex; align-items: center; gap: 4px; cursor: pointer; }
.fv-userlist { min-height: 64px; }
</style>
