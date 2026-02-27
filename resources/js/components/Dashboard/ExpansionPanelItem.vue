<template>
	<div
		class="expansion-panel"
		:class="[
			attrsClass,
			panelClass,
			isExpanded ? selectedClass : null,
			{ 'is-expanded': isExpanded, 'is-last-row': isLastRow },
		]"
		v-bind="rootAttrs"
	>
		<div
			:id="titleId"
			class="expansion-panel-title text-left bg-transparent border-0 flex hover:bg-[var(--bg3)] cursor-pointer items-center"
			:disabled="disabled"
			:aria-expanded="isExpanded"
			:aria-controls="contentId"
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
				v-show="isExpanded"
				class="expansion-panel-text"
				:id="contentId"
				role="region"
				:aria-labelledby="titleId"
			>
				<div class="expansion-panel-text__wrapper">
					<slot name="body" :expanded="isExpanded" :toggle="toggle" :disabled="disabled" />
				</div>
			</div>
		</transition>
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

		hideActions?: boolean
		tile?: boolean
	}>(),
	{
		defaultExpanded: false,
		disabled: false,
		readonly: false,
		static: false,
		selectedClass: '',
		panelClass: '',
		hideActions: false,
		tile: false,
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

const canToggle = computed(() => !props.disabled && !props.readonly && !props.static)

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
.is-expanded .expansion-panel-text {
    border-bottom: solid 1px var(--formBorder);
}
.expansion-panel-title{
    border-bottom: solid 1px var(--formBorder);
}
.is-expanded .expansion-panel-title{
    border-bottom: none;
}

.is-last-row .expansion-panel-title{
	border-bottom: none;
}

.is-last-row.is-expanded .expansion-panel-text {
	border-bottom: none;
}
</style>
