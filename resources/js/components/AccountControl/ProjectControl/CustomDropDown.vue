<template>
  <div ref="dropdownRef" class="relative">
    <button
      type="button"
      class="w-[320px] max-w-[calc(100vw-2rem)] px-2 py-1 border border-solid border-[var(--normalBorder)] bg-[var(--background-color)] text-[var(--primary-color)] flex items-center justify-between gap-2"
      @click="toggleDropdown"
    >
      <span class="truncate text-left">{{ selectedLabel }}</span>
      <span class="text-xs">{{ open ? '▲' : '▼' }}</span>
    </button>

    <div
      v-if="open"
      class="absolute left-0 top-full mt-1 z-20 w-full border border-solid border-[var(--normalBorder)] bg-[var(--background-color)]"
    >
      <div class="p-2 border-b [border-bottom-style:solid] border-[var(--normalBorder)]">
        <input
          ref="searchInputRef"
          v-model="keyword"
          type="text"
          :placeholder="searchPlaceholder"
          class="w-full !box-border px-2 py-1 border border-solid border-[var(--normalBorder)] bg-[var(--background-color)] text-[var(--primary-color)]"
          @keydown.esc="closeDropdown"
        />
      </div>

      <div class="max-h-64 overflow-auto">
        <button
          v-for="option in filteredOptions"
          :key="option.id"
          type="button"
          class="w-full !box-border text-left px-3 py-2 text-sm hover:bg-[var(--bg3)]"
          :class="{ 'bg-[var(--bg3)]': option.id === modelValue }"
          @click="selectOption(option.id)"
        >
          {{ formatLabel(option) }}
        </button>

        <div
          v-if="!filteredOptions.length"
          class="px-3 py-2 text-xs text-[var(--primary-color)] opacity-70"
        >
          {{ emptyText }}
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue'

type OptionItem = {
  id: number
  name: string
  is_new?: number | boolean
}

const props = withDefaults(defineProps<{
  modelValue: number | null
  options: OptionItem[]
  placeholder?: string
  searchPlaceholder?: string
  emptyText?: string
}>(), {
  placeholder: 'プロジェクトを選択',
  searchPlaceholder: 'プロジェクト検索',
  emptyText: '該当するプロジェクトがありません',
})

const emit = defineEmits<{
  (e: 'update:modelValue', value: number | null): void
  (e: 'change', value: number | null): void
}>()

const dropdownRef = ref<HTMLElement | null>(null)
const searchInputRef = ref<HTMLInputElement | null>(null)
const open = ref(false)
const keyword = ref('')

const formatLabel = (option: OptionItem) => {
  if (option.is_new === undefined) return option.name
  return `${option.name} (${Number(option.is_new) === 1 ? '新規' : '既存'})`
}

const selectedOption = computed(() => props.options.find((option) => option.id === props.modelValue) ?? null)

const selectedLabel = computed(() => {
  if (!selectedOption.value) return props.placeholder
  return formatLabel(selectedOption.value)
})

const filteredOptions = computed(() => {
  const search = keyword.value.trim().toLowerCase()
  if (!search) return props.options
  return props.options.filter((option) => option.name.toLowerCase().includes(search))
})

const closeDropdown = () => {
  open.value = false
  keyword.value = ''
}

const toggleDropdown = async () => {
  if (open.value) {
    closeDropdown()
    return
  }
  open.value = true
  await nextTick()
  searchInputRef.value?.focus()
}

const selectOption = (id: number) => {
  emit('update:modelValue', id)
  emit('change', id)
  closeDropdown()
}

const handleDocumentClick = (event: MouseEvent) => {
  const target = event.target as Node | null
  if (!target) return
  if (dropdownRef.value && !dropdownRef.value.contains(target)) {
    closeDropdown()
  }
}

onMounted(() => {
  document.addEventListener('click', handleDocumentClick)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', handleDocumentClick)
})
</script>
