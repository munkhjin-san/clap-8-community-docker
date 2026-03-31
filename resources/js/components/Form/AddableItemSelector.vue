<template>
  <div>
    <div
      ref="selectorRef"
      :class="['form-wrapper', { focused: hasSelectedValue || focus }]"
      style="position: relative;"
    >
      <span
        style="z-index: 5"
        :class="['form-plc', { 'focused-plc': hasSelectedValue }]"
      >
        {{ placeHolder }}
      </span>

      <v-autocomplete
        class="one-selector global-user-select"
        :items="allOptions"
        item-title="title"
        item-value="value"
        :multiple="multiple"
        :clearable="clearable"
        :chips="multiple"
        :closable-chips="multiple"
        flat
        tile
        bg-color="var(--background-color)"
        hide-details
        :menu-props="{
          scrollStrategy: 'close',
          closeOnContentClick: closeOnSelect,
          maxWidth: selectorRef ? selectorRef.clientWidth : undefined
        }"
        v-model:search="searchKeyword"
        :model-value="selectedItems"
        :loading="loading"
        @update:modelValue="handleModelUpdate"
        @update:search="emitSearch"
        @focusin="focus = true"
        @focusout="focus = false"
        @keydown.enter.prevent="addCustomOption"
      >
        <template #append-item>
          <v-list-item
            v-if="canAddCustom"
            density="compact"
            style="color: var(--primary-color)"
            :ripple="false"
            :title="`「${searchKeyword.trim()}」を追加`"
            @click="addCustomOption"
          />
        </template>

        <template #chip="{ props: chipProps, item }">
          <v-chip
            v-bind="chipProps"
            :text="item.raw.title"
            :close-icon="CloseIcon"
            rounded="0"
            density="compact"
            closable
          />
        </template>

        <template #item="{ props: itemProps, item }">
          <v-list-item
            v-bind="itemProps"
            :title="item.raw.title"
            rounded="0"
            density="compact"
            :ripple="false"
            variant="flat"
          />
        </template>

        <template #no-data>
          <div style="padding: 6px 0;">
            <div v-if="!canAddCustom" style="font-size: 14px; opacity: 0.8; padding: 10px; color: var(--primary-color);">
              アイテムはありません。
            </div>
          </div>
        </template>

        <template #loader="{ isActive }">
          <Transition name="modalFade">
            <div v-if="isActive">
              <div
                class="spinner-mini"
                style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"
              />
            </div>
          </Transition>
        </template>
      </v-autocomplete>
    </div>

    <p v-if="error" class="i-error">{{ error }}</p>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import 'styles/selector.css'
import { validator } from '@/validation/validator'
import { useApi } from '@/composables/api'
import CloseIcon from './CloseIcon.vue'

type NormalizedOption = {
  raw: any
  title: string
  value: any
}

interface Props {
  placeHolder?: string
  name?: string
  rules?: string
  multiple?: boolean
  options?: any[] | Record<string, any>
  path?: string
  clearable?: boolean
  label?: string
  reduce?: (option: any) => any
  closeOnSelect?: boolean
  allowCustom?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  placeHolder: '',
  name: 'optionSelector',
  rules: '',
  multiple: true,
  options: () => [],
  path: '',
  clearable: true,
  label: 'name',
  reduce: (option: any) => option?.id ?? option?.value ?? option,
  closeOnSelect: false,
  allowCustom: true,
})

const emit = defineEmits<{
  (e: 'search', keyword: string): void
  (e: 'create-option', option: any): void
}>()

const selectedItems = defineModel<any>()
const selectorRef = ref<HTMLElement | null>(null)
const sourceOptions = ref<any[]>([])
const customOptions = ref<any[]>([])
const searchKeyword = ref('')
const loading = ref(false)
const error = ref('')
const trigger = ref(false)
const focus = ref(false)
const api = useApi()

const normalizeOptionsInput = (options: any[] | Record<string, any> | undefined | null) => {
  if (!options) return []
  return Array.isArray(options) ? options : Object.values(options)
}

const getReducedValue = (option: any) => {
  try {
    return props.reduce(option)
  } catch {
    return option
  }
}

const getOptionTitle = (option: any) => {
  if (option === null || option === undefined) return ''
  if (typeof option !== 'object') return String(option)

  const byLabel = props.label ? option?.[props.label] : undefined
  if (byLabel !== null && byLabel !== undefined && byLabel !== '') return String(byLabel)

  if (option.label !== null && option.label !== undefined) return String(option.label)
  if (option.name !== null && option.name !== undefined) return String(option.name)
  if (option.title !== null && option.title !== undefined) return String(option.title)

  const reduced = getReducedValue(option)
  return reduced === null || reduced === undefined ? '' : String(reduced)
}

const toNormalized = (option: any): NormalizedOption => ({
  raw: option,
  title: getOptionTitle(option),
  value: getReducedValue(option),
})

const optionValueKey = (value: any) => {
  if (value === null || value === undefined) return '__null__'
  if (typeof value === 'object') {
    try {
      return `obj:${JSON.stringify(value)}`
    } catch {
      return `obj:${String(value)}`
    }
  }
  return `${typeof value}:${String(value)}`
}

const allOptions = computed<NormalizedOption[]>(() => {
  const map = new Map<string, NormalizedOption>()

  for (const option of sourceOptions.value) {
    const normalized = toNormalized(option)
    map.set(optionValueKey(normalized.value), normalized)
  }

  for (const option of customOptions.value) {
    const normalized = toNormalized(option)
    const key = optionValueKey(normalized.value)
    if (!map.has(key)) map.set(key, normalized)
  }

  return Array.from(map.values())
})

const isSameValue = (a: any, b: any) => {
  if (Object.is(a, b)) return true
  if (a === null || a === undefined || b === null || b === undefined) return false
  if (typeof a === 'object' || typeof b === 'object') {
    try {
      return JSON.stringify(a) === JSON.stringify(b)
    } catch {
      return false
    }
  }
  return String(a) === String(b)
}

const hasSelectedValue = computed(() => {
  const value = selectedItems.value
  if (props.multiple) return Array.isArray(value) && value.length > 0
  return value !== null && value !== undefined && value !== ''
})

const canAddCustom = computed(() => {
  if (!props.allowCustom) return false
  const keyword = searchKeyword.value.trim()
  if (!keyword) return false

  return !allOptions.value.some((option) => option.title.toLowerCase() === keyword.toLowerCase())
})

const isPrimitiveOption = (option: any) => option === null || option === undefined || typeof option !== 'object'

const usePrimitiveCustomOption = computed(() => {
  const candidates = sourceOptions.value.filter((option) => option !== null && option !== undefined)
  if (candidates.length === 0) return true
  return candidates.every((option) => isPrimitiveOption(option))
})

const buildCustomOption = (keyword: string) => {
  if (usePrimitiveCustomOption.value) return keyword
  return {
    id: keyword,
    name: keyword,
    label: keyword,
    title: keyword,
    value: keyword,
    custom: true,
  }
}

const emitSearch = (keyword?: string) => {
  emit('search', keyword ?? searchKeyword.value ?? '')
}

const addCustomOption = () => {
  if (!canAddCustom.value) return

  const keyword = searchKeyword.value.trim()
  if (!keyword) return

  const customOption = buildCustomOption(keyword)
  const reducedValue = getReducedValue(customOption)
  const exists = allOptions.value.some((option) => isSameValue(option.value, reducedValue))

  if (!exists) customOptions.value = [...customOptions.value, customOption]

  if (props.multiple) {
    const current = Array.isArray(selectedItems.value) ? [...selectedItems.value] : []
    if (!current.some((value) => isSameValue(value, reducedValue))) current.push(reducedValue)
    selectedItems.value = current
  } else {
    selectedItems.value = reducedValue
  }

  emit('create-option', customOption)
  searchKeyword.value = ''
  emitSearch('')
}

const handleModelUpdate = (value: any) => {
  selectedItems.value = value
}

const getPossibleItems = async () => {
  if (!props.path) return

  loading.value = true
  const endpoint = props.path.startsWith('/') ? props.path : `/${props.path}`
  const response = await api.get(endpoint)
  sourceOptions.value = normalizeOptionsInput(response)
  loading.value = false
}

const validate = async (passive?: boolean) => {
  if (passive && !trigger.value) return

  const { isValid, errorMessage } = await validator(props.rules || '', selectedItems.value)
  error.value = errorMessage || ''
  trigger.value = true
  return { valid: isValid }
}

watch(
  () => props.path,
  () => {
    if (props.path) void getPossibleItems()
  },
)

watch(
  () => props.options,
  (options) => {
    sourceOptions.value = normalizeOptionsInput(options)
  },
  { deep: true },
)

onMounted(() => {
  const localOptions = normalizeOptionsInput(props.options)
  if (localOptions.length) {
    sourceOptions.value = localOptions
  } else if (props.path) {
    void getPossibleItems()
  }
})

defineExpose({ validate })
</script>

<style lang="scss">
.one-selector {
  width: 100%;
  border: 1px solid var(--primary-color) !important;
}
.v-field__loader{
    left: auto;
    right: 15px;
    width: fit-content;
    top: 20px;
}
</style>
