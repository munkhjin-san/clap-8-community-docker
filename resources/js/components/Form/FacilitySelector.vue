<template>
    <div
        ref="selectorRef"
        class="facility-resource"
        :class="{
            'facility-resource--open': menuOpen,
        }"
    >
        <div class="facility-resource__selector">
            <div class="facility-resource__heading">
                <span class="facility-resource__icon" aria-hidden="true">
                    <svg v-if="target === 'qualified_institution'" class="facility-resource__line-icon" viewBox="0 0 24 24" fill="none">
                        <path d="M5 21V3H19V21M3 21H21" />
                        <rect x="8" y="6" width="3" height="3" />
                        <rect x="13" y="6" width="3" height="3" />
                        <rect x="8" y="12" width="3" height="3" />
                        <rect x="13" y="12" width="3" height="3" />
                    </svg>
                    <svg v-else-if="target === 'zoom_value'" class="facility-resource__line-icon" viewBox="0 0 24 24" fill="none">
                        <rect x="3" y="6" width="12" height="12" rx="2" />
                        <path d="M15 10L21 7.5V16.5L15 14" />
                    </svg>
                    <MyCarIcon v-else class="facility-resource__car-icon" :size="12" />
                </span>
                <p class="facility-resource__title">{{ placeHolder }}</p>
            </div>

            <div
                class="facility-resource__trigger"
                :class="{
                    'facility-resource__trigger--selected': selectedItems != null,
                    'facility-resource__trigger--unavailable': selectedUnavailable,
                }"
            >
                <div
                    v-if="selectedItems != null"
                    class="facility-resource__selected-chip"
                    :class="{ 'facility-resource__selected-chip--unavailable': selectedUnavailable }"
                >
                    <button
                        type="button"
                        class="facility-resource__chip-label"
                        :aria-expanded="menuOpen"
                        :aria-label="`${placeHolder}を選択`"
                        :disabled="spinner || !validScheduleTime"
                        @click.stop="toggleMenu"
                        @keydown.esc="menuOpen = false"
                    >
                        <span class="facility-resource__selection-label" :title="selectedLabel">{{ selectedLabel }}</span>
                        <span v-if="selectedUnavailable" class="facility-resource__selection-state">
                            {{ selectedUnavailableLabel }}
                        </span>
                    </button>
                    <button
                        type="button"
                        class="facility-resource__chip-clear"
                        :aria-label="`${placeHolder}の選択を解除`"
                        :disabled="spinner"
                        @click.stop="clearSelection"
                    >
                        <CloseIcon :size="7" />
                    </button>
                </div>
                <button
                    v-else
                    type="button"
                    class="facility-resource__value-button"
                    :aria-expanded="menuOpen"
                    :aria-label="`${placeHolder}を選択`"
                    :disabled="spinner || !validScheduleTime"
                    @click.stop="toggleMenu"
                    @keydown.esc="menuOpen = false"
                >
                    <span class="facility-resource__selection-label facility-resource__placeholder">
                        {{ !validScheduleTime ? '日時を設定してください' : selectedLabel }}
                    </span>
                </button>
                <button
                    type="button"
                    class="facility-resource__toggle"
                    :aria-expanded="menuOpen"
                    :aria-label="`${placeHolder}の選択肢を開く`"
                    :disabled="spinner || !validScheduleTime"
                    @click.stop="toggleMenu"
                    @keydown.esc="menuOpen = false"
                >
                    <span v-if="spinner" class="facility-resource__spinner" aria-label="空き状況を確認中"></span>
                    <svg v-else class="facility-resource__chevron" viewBox="0 0 12 12" fill="none" aria-hidden="true">
                        <path d="M2 4L6 8L10 4" />
                    </svg>
                </button>
            </div>

            <Transition name="facility-menu">
                <div v-if="menuOpen" class="facility-resource__menu" role="listbox">
                    <div v-if="options.length" class="facility-resource__options">
                        <button
                            v-for="option in options"
                            :key="option.id"
                            type="button"
                            role="option"
                            class="facility-resource__option"
                            :class="{
                                'facility-resource__option--selected': selectedItems === option.id,
                                'facility-resource__option--disabled': !option.availablity,
                            }"
                            :aria-selected="selectedItems === option.id"
                            :disabled="spinner || !option.availablity"
                            @click="selectOption(option)"
                        >
                            <span class="facility-resource__option-label">{{ option.label }}</span>
                            <span v-if="selectedItems === option.id && option.availablity" class="facility-resource__option-state facility-resource__option-state--selected">
                                選択中
                            </span>
                            <span v-else-if="selectedItems === option.id" class="facility-resource__option-state facility-resource__option-state--unavailable">
                                {{ option.unavailable_reason || '選択不可' }}
                            </span>
                            <span v-else-if="option.availablity" class="facility-resource__option-state facility-resource__option-state--available">
                                空き
                            </span>
                            <span v-else class="facility-resource__option-state facility-resource__option-state--unavailable">
                                {{ option.unavailable_reason || '選択不可' }}
                            </span>
                        </button>
                    </div>
                    <p v-else class="facility-resource__empty">利用できる項目はありません。</p>
                </div>
            </Transition>
        </div>
        <slot name="details" />
    </div>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, useTemplateRef, watch } from 'vue'
import { useApi } from '@/composables/api'
import type { RepeatDataType } from '@/interface/calendarInterface'
import MyCarIcon from '@/components/Icons/MyCarIcon.vue'
import CloseIcon from '@/components/Form/CloseIcon.vue'

type FacilityTarget = 'qualified_institution' | 'qualified_car' | 'zoom_value'

type FacilityOption = {
    label: string
    id: string
    availablity: boolean
    unavailable_reason?: string | null
}

interface Props {
    placeHolder: string
    repeatSpan: RepeatDataType
    repetitionFlag: number
    target: FacilityTarget
    time_start: string
    time_end: string
    once_date: string
    editId: number | string | null
    edit_all_record: boolean
}

const props = defineProps<Props>()
const selectedItems = defineModel<string | null>()
const options = ref<FacilityOption[]>([])
const spinner = ref(false)
const menuOpen = ref(false)
const selectorRef = useTemplateRef<HTMLElement>('selectorRef')
const api = useApi()
let requestSerial = 0

const selectedOption = computed(() => {
    return options.value.find((option) => option.id === selectedItems.value) ?? null
})

const selectedLabel = computed(() => {
    if (selectedOption.value) return selectedOption.value.label
    if (selectedItems.value != null) return `選択済み（ID: ${selectedItems.value}）`
    return '選択してください'
})

const selectedUnavailable = computed(() => {
    return selectedItems.value != null
        && !spinner.value
        && (selectedOption.value == null || !selectedOption.value.availablity)
})

const selectedUnavailableLabel = computed(() => {
    return '選択不可'
})

const validScheduleTime = computed(() => {
    return isValidTime(props.time_start) && isValidTime(props.time_end)
})

const getPossibleItems = async () => {
    const params = {
        editId: props.editId,
        target: props.target,
        repeat: props.repetitionFlag,
        repeat_span: props.repeatSpan,
        time_start: props.time_start,
        time_end: props.time_end,
        once_date: props.once_date,
        edit_repeat: props.edit_all_record,
    }

    if (!isValidTime(params.time_start) || !isValidTime(params.time_end)) {
        spinner.value = false
        return
    }

    const serial = ++requestSerial
    spinner.value = true

    try {
        const data = await api.post('/get_possible_facilities', params) as FacilityOption[] | null
        if (serial !== requestSerial || !data) return

        options.value = data
    } catch {
        options.value = []
        menuOpen.value = false
    } finally {
        if (serial === requestSerial) spinner.value = false
    }
}

const toggleMenu = () => {
    if (spinner.value || !validScheduleTime.value) return
    menuOpen.value = !menuOpen.value
    if (menuOpen.value) void getPossibleItems()
}

const selectOption = (option: FacilityOption) => {
    if (!option.availablity) return
    selectedItems.value = option.id
    menuOpen.value = false
}

const clearSelection = () => {
    selectedItems.value = null
    menuOpen.value = false
}

const isValidTime = (time: string) => {
    const timeRegex = /^\d{1,2}:\d{2}$/
    if (!timeRegex.test(time)) return false

    const [hour, minute] = time.split(':').map(Number)
    return hour >= 0 && hour <= 23 && minute >= 0 && minute <= 59
}

const closeOnOutsideClick = (event: MouseEvent) => {
    const target = event.target
    if (target instanceof Node && !selectorRef.value?.contains(target)) {
        menuOpen.value = false
    }
}

onMounted(() => document.addEventListener('click', closeOnOutsideClick))
onBeforeUnmount(() => {
    requestSerial += 1
    document.removeEventListener('click', closeOnOutsideClick)
})

watch(
    () => [
        props.editId,
        props.target,
        props.repetitionFlag,
        props.repeatSpan,
        props.time_start,
        props.time_end,
        props.once_date,
        props.edit_all_record,
    ],
    () => {
        void getPossibleItems()
    },
    { deep: true, immediate: true },
)
</script>

<style scoped>
.facility-resource {
    position: relative;
    width: 100%;
    min-width: 0;
    box-sizing: border-box !important;
    transition: border-color 160ms ease, background-color 160ms ease, transform 160ms ease;
}

.facility-resource__selector {
    position: relative;
}

.facility-resource--open {
    z-index: 40;
}

.facility-resource__heading {
    display: flex;
    align-items: center;
    gap: 8px;
    min-width: 0;
}

.facility-resource__icon {
    display: grid;
    place-items: center;
    width: 20px;
    height: 20px;
    flex: 0 0 20px;
    color: var(--primary-color);
}

.facility-resource__line-icon {
    width: 18px;
    height: 18px;
    stroke: currentColor;
    stroke-width: 1.6;
    stroke-linecap: round;
    stroke-linejoin: round;
}

.facility-resource__car-icon {
    display: block;
    width: 18px;
    height: 12px;
    max-width: 100%;
}

.facility-resource__title {
    min-width: 0;
    overflow: hidden;
    color: var(--primary-color);
    font-size: 12px;
    font-weight: 600;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.facility-resource__trigger {
    display: flex;
    align-items: center;
    width: 100%;
    min-width: 0;
    height: 40px;
    margin-top: 7px;
    box-sizing: border-box !important;
    color: var(--primary-color);
    border: 1px solid var(--primary-color);
    background: var(--background-color);
}

.facility-resource__trigger--selected {
    padding: 5px 5px 5px 6px;
}

.facility-resource__trigger:hover,
.facility-resource__trigger:focus-within {
    border-color: var(--primary-color);
}

.facility-resource__placeholder {
    color: var(--sub-color);
}

.facility-resource__value-button,
.facility-resource__chip-label,
.facility-resource__chip-clear,
.facility-resource__toggle {
    color: inherit;
    font: inherit;
    border: 0;
    background: transparent;
}

.facility-resource__value-button {
    min-width: 0;
    flex: 1 1 auto;
    align-self: stretch;
    padding: 8px 10px;
    font-size: 12px;
    text-align: left;
    cursor: pointer;
}

.facility-resource__selected-chip {
    display: flex;
    align-items: center;
    height: 28px;
    min-width: 0;
    max-width: calc(100% - 32px);
    background: var(--bg3);
}

.facility-resource__selected-chip--unavailable {
    border-left: 2px solid tomato;
    background: var(--bg3);
}

.facility-resource__chip-label {
    display: flex;
    align-items: center;
    gap: 4px;
    min-width: 0;
    flex: 1 1 auto;
    padding: 6px 2px 6px 7px;
    font-size: 11px;
    text-align: left;
    cursor: pointer;
}

.facility-resource__chip-clear {
    display: grid;
    place-items: center;
    width: 26px;
    height: 28px;
    flex: 0 0 26px;
    padding: 0;
    cursor: pointer;
    touch-action: manipulation;
}

.facility-resource__chip-clear:hover {
    background: color-mix(in srgb, var(--bg3) 76%, var(--primary-color));
}

.facility-resource__toggle {
    display: grid;
    place-items: center;
    width: 32px;
    height: 38px;
    flex: 0 0 32px;
    margin-left: auto;
    padding: 0;
    cursor: pointer;
}

.facility-resource__value-button:disabled,
.facility-resource__chip-label:disabled,
.facility-resource__chip-clear:disabled,
.facility-resource__toggle:disabled {
    cursor: wait;
    opacity: 0.68;
}

.facility-resource__value-button:focus-visible,
.facility-resource__chip-label:focus-visible,
.facility-resource__chip-clear:focus-visible,
.facility-resource__toggle:focus-visible {
    outline: 1px solid var(--primary-color);
    outline-offset: -2px;
}

.facility-resource__selection-label {
    display: block;
    flex: 1 1 auto;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.facility-resource__selection-state {
    flex: 0 0 auto;
    max-width: 38px;
    overflow: hidden;
    color: tomato;
    font-size: 8px;
    font-weight: 600;
    line-height: 1;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.facility-resource__chevron {
    width: 12px;
    height: 12px;
    flex: 0 0 12px;
    stroke: currentColor;
    stroke-width: 1.4;
    stroke-linecap: round;
    stroke-linejoin: round;
    pointer-events: none;
    transition: transform 160ms ease;
}

.facility-resource--open .facility-resource__chevron {
    transform: rotate(180deg);
}

.facility-resource__spinner {
    width: 13px;
    height: 13px;
    flex: 0 0 13px;
    border: 2px solid var(--formBorder);
    border-top-color: var(--primary-color);
    border-radius: 50%;
    pointer-events: none;
    animation: facility-spin 700ms linear infinite;
}

.facility-resource__menu {
    position: absolute;
    z-index: 30;
    top: 100%;
    left: 0;
    width: 100%;
    box-sizing: border-box !important;
    padding: 6px;
    border: 1px solid var(--primary-color);
    border-top: 0;
    background: var(--background-color);
    box-shadow: 0 12px 30px color-mix(in srgb, black 24%, transparent);
}

.facility-resource__options {
    max-height: 230px;
    overflow-y: auto;
}

.facility-resource__option {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    width: 100%;
    min-height: 38px;
    padding: 8px 9px;
    box-sizing: border-box !important;
    color: var(--primary-color);
    font: inherit;
    font-size: 12px;
    text-align: left;
    border: 0;
    border-bottom: 1px solid var(--calendarBorder);
    background: transparent;
    cursor: pointer;
}

.facility-resource__option:last-child {
    border-bottom: 0;
}

.facility-resource__option:hover:not(:disabled),
.facility-resource__option:focus-visible:not(:disabled),
.facility-resource__option--selected {
    background: var(--bg3);
    outline: none;
}

.facility-resource__option--disabled {
    cursor: not-allowed;
    opacity: 0.58;
}

.facility-resource__option-label {
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.facility-resource__option-state {
    flex: 0 0 auto;
    padding: 2px 6px;
    font-size: 9px;
    white-space: nowrap;
    background: var(--bg3);
}

.facility-resource__option-state--available {
    color: color-mix(in srgb, var(--primary-color) 60%, #3ba55d);
}

.facility-resource__option-state--selected {
    color: var(--primary-color);
}

.facility-resource__option-state--unavailable {
    color: tomato;
}

.facility-resource__empty {
    padding: 18px 8px;
    color: var(--sub-color);
    font-size: 11px;
    text-align: center;
}

.facility-menu-enter-active,
.facility-menu-leave-active {
    transition: opacity 120ms ease, transform 120ms ease;
    transform-origin: top;
}

.facility-menu-enter-from,
.facility-menu-leave-to {
    opacity: 0;
    transform: translateY(-4px);
}

@keyframes facility-spin {
    to { transform: rotate(360deg); }
}
</style>
