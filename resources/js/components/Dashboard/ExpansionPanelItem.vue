<template>
	<div
		class="expansion-panel rounded min-w-0"
		:class="[
			attrsClass,
			panelClass,
			isExpanded ? selectedClass : null,
			{ 'is-expanded': isExpanded, },
		]"
		v-bind="rootAttrs"
        :style="{
            gridColumn: isExpanded ? `span ${expandedCol}` : 'unset'
        }"
	>
		<div
			:id="titleId"
			:class="[
				'expansion-panel-title text-left border-0 flex  cursor-pointer items-center',
				titleClass,
                {'bg-[var(--selected-background)]' : isExpanded},
                {'hover:bg-[var(--bg3)]' : !isExpanded}
			]"
			:disabled="disabled"
			:aria-expanded="isExpanded"
			:aria-controls="contentId"
            :style="{
                borderRadius: isExpanded ? '5px 5px 0 0 ' : '5px'
                
            }"
			@click="onTitleClick"
		>
			<slot name="title" :expanded="isExpanded" :toggle="toggle" :disabled="disabled" />
		</div>

		<transition
			name="expansion-panel"
			@enter="onEnter"
			@after-enter="onAfterEnter"
			@leave="onLeave"
			@after-leave="onAfterLeave"
		>
			<div
				v-if="isExpanded"
				class="expansion-panel-text  bg-[var(--selected-background)]"
				:id="contentId"
				role="region"
				:aria-labelledby="titleId"
                style="border-radius: 0 0 5px 5px;"
			>
				<div class="expansion-panel-text__wrapper">
					<slot name="body" :expanded="isExpanded" :toggle="toggle" :disabled="disabled" />
				</div>
			</div>
		</transition>
        <div class="d-separator-line" v-if="!isLastRow"></div>
	</div>
</template>

<script setup lang="ts">
import { computed, inject, onBeforeUnmount, onMounted, ref, useAttrs } from 'vue'
import { EXPANSION_GRID_KEY, type ExpansionPanelValue } from './expansionGridContext'

defineOptions({ inheritAttrs: false })

const props = withDefaults(
	defineProps<{
		modelValue?: boolean
		defaultExpanded?: boolean
		value?: ExpansionPanelValue
		disabled?: boolean
		readonly?: boolean
		static?: boolean

		selectedClass?: string
		panelClass?: string | string[] | Record<string, boolean>
		titleClass?: string | string[] | Record<string, boolean>

		hideActions?: boolean
		tile?: boolean
        col?: number
	}>(),
	{
		defaultExpanded: false,
		disabled: false,
		readonly: false,
		static: false,
		selectedClass: '',
		panelClass: '',
		titleClass: '',
		hideActions: false,
		tile: false,
        col: 1,
	},
)

const emit = defineEmits<{
	'update:modelValue': [value: boolean]
	change: [value: boolean]
	toggle: [value: boolean]
}>()

const attrs = useAttrs()

const attrsClass = computed(() => attrs.class)

const rootAttrs = computed(() => {
	const { class: _class, ...rest } = attrs
	return rest
})

const grid = inject(EXPANSION_GRID_KEY, null)
const expandedCol = computed(() => {
    const requestedCol = Math.max(1, Number(props.col || 1))
    return grid ? Math.min(requestedCol, grid.cols.value) : requestedCol
})

const isControlled = computed(() => props.modelValue !== undefined)
const internalExpanded = ref<boolean>(props.defaultExpanded)

const isExpanded = computed<boolean>(() => {
	if (grid) return grid.isActive(panelValue.value)
	return isControlled.value ? Boolean(props.modelValue) : internalExpanded.value
})

const uid = ref(`expansion-${Math.random().toString(36).slice(2, 10)}`)
const panelValue = computed<ExpansionPanelValue>(() => props.value ?? uid.value)

onMounted(() => {
	grid?.register(panelValue.value)
})

onBeforeUnmount(() => {
	grid?.unregister(panelValue.value)
})

const isLastRow = computed(() => (grid ? grid.isLastRow(panelValue.value) : false))

const titleId = computed(() => `${uid.value}-title`)
const contentId = computed(() => `${uid.value}-content`)

const canToggle = computed(() => !props.disabled && !props.readonly)

function setExpanded(value: boolean) {
	if (!canToggle.value) return

	if (grid) {
		grid.setActive(value ? panelValue.value : null)
		emit('change', value)
		emit('toggle', value)
		return
	}

	if (!isControlled.value) internalExpanded.value = value
	emit('update:modelValue', value)
	emit('change', value)
	emit('toggle', value)
}

function toggle() {
	setExpanded(!isExpanded.value)
}

function onTitleClick() {
	toggle()
}

function nextFrame(callback: () => void) {
	requestAnimationFrame(() => requestAnimationFrame(callback))
}

function onEnter(el: Element) {
	const element = el as HTMLElement
	element.style.height = '0'
	element.style.overflow = 'hidden'
	element.style.willChange = 'height'
	nextFrame(() => {
		element.style.height = `${element.scrollHeight}px`
	})
}

function onAfterEnter(el: Element) {
	const element = el as HTMLElement
	element.style.height = 'auto'
	element.style.overflow = ''
	element.style.willChange = ''
}

function onLeave(el: Element) {
	const element = el as HTMLElement
	element.style.height = `${element.scrollHeight}px`
	element.style.overflow = 'hidden'
	element.style.willChange = 'height'
	nextFrame(() => {
		element.style.height = '0'
	})
}

function onAfterLeave(el: Element) {
	const element = el as HTMLElement
	element.style.height = ''
	element.style.overflow = ''
	element.style.willChange = ''
}
</script>

<style scoped>
.expansion-panel-enter-active,
.expansion-panel-leave-active {
	transition: height 200ms ease;
}


.is-expanded .expansion-panel-title{
    border-bottom: none;
}

.d-separator-line{
    height: 1px;
    background-color: var(--panel-separate);
    width: calc(100% - 20px);
    margin: -1px auto;
    z-index: 4;
    position: relative;
}
</style>
