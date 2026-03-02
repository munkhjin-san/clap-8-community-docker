<template>
    <div
        class="v-expansion-panels grid under960:!grid-cols-1"
        :class="attrsClass"
        :style="gridStyle"
        v-bind="rootAttrs"
    >       
        <slot></slot>        
    </div>
</template>
<script setup lang="ts">
import { computed, provide, ref, useAttrs } from 'vue'
import { EXPANSION_GRID_KEY, type ExpansionGridModelValue, type ExpansionPanelValue } from './expansionGridContext'

defineOptions({ inheritAttrs: false })

const props = withDefaults(
    defineProps<{
        col: number
        modelValue?: ExpansionGridModelValue
        defaultValue?: ExpansionGridModelValue
    }>(),
    {
        defaultValue: null,
    },
)

const emit = defineEmits<{
    'update:modelValue': [value: ExpansionGridModelValue]
    change: [value: ExpansionGridModelValue]
}>()

const attrs = useAttrs()
const attrsClass = computed(() => attrs.class as any)

const rootAttrs = computed(() => {
    const { class: _class, ...rest } = attrs
    return rest
})

const isControlled = computed(() => props.modelValue !== undefined)
const internalValue = ref<ExpansionGridModelValue>(props.defaultValue)

const activeValue = computed<ExpansionGridModelValue>({
    get() {
        return isControlled.value ? (props.modelValue ?? null) : internalValue.value
    },
    set(value) {
        if (!isControlled.value) internalValue.value = value
        emit('update:modelValue', value)
        emit('change', value)
    },
})

function isActive(value: ExpansionPanelValue) {
    return activeValue.value === value
}

function setActive(value: ExpansionGridModelValue) {
    activeValue.value = value
}

function toggle(value: ExpansionPanelValue) {
    activeValue.value = activeValue.value === value ? null : value
}

const cols = computed(() => Math.max(1, Number(props.col || 1)))

const registered = ref<ExpansionPanelValue[]>([])

function register(value: ExpansionPanelValue) {
    if (registered.value.includes(value)) return
    registered.value.push(value)
}

function unregister(value: ExpansionPanelValue) {
    const index = registered.value.indexOf(value)
    if (index === -1) return
    registered.value.splice(index, 1)
}

function isLastRow(value: ExpansionPanelValue) {
    const index = registered.value.indexOf(value)
    if (index === -1) return false

    const total = registered.value.length
    const c = cols.value
    const lastRowCount = total % c === 0 ? c : total % c
    const lastRowStart = total - lastRowCount
    return index >= lastRowStart
}

provide(EXPANSION_GRID_KEY, {
    cols,
    activeValue: computed(() => activeValue.value),
    isActive,
    setActive,
    toggle,
    register,
    unregister,
    isLastRow,
})

const gridStyle = computed(() => {
    return {
        gridTemplateColumns: `repeat(${cols.value}, minmax(0, 1fr))`,
    }
})
</script>