<template>
    <Modal @close="emit('close')">
        <template #title>
            <p>{{ `新しいコンタクトを作成する`}}</p>
        </template>
        <template #content>
            <div>
                <div>
                  <p class="text-[14px] mb-[10px]">コンタクト種類（必須）</p>
                  <div class="flex">
                      <div class="max-w-[50%] relative">
                          <select v-model="contact_type_id" class="border border-solid border-[var(--primary-color)] h-[40px] m-h-[40px] px-[10px] text-[var(--primary-color)]">
                              <option v-for="type in types" :value="type.id">{{ type.title }}</option>
                          </select>
                          <p v-if="typeError" class="text-[12px] text-[tomato]">必須です。</p>
                      </div>
                      <div v-if="contact_type_id == -1" class="relative">
                          <input class="-ml-[1px] border border-solid border-[var(--primary-color)] h-[40px] m-h-[40px] px-[10px] text-[var(--primary-color)]"  v-model="pseudo_type" type="text"/>
                          <p v-if="typeInputError" class="text-[12px] text-[tomato]">必須です。</p>
                      </div>              
                  </div>
              </div>
                <div
                class="mt-8 !box-border relative flex flex-col items-center justify-center w-full p-8 text-center border-2 bg-[var(--bg3)] border-[var(--bg2)] cursor-pointer"
                 :class="borderStyle"
                  @click="openFileDialog"
                  @keydown.space.prevent="openFileDialog"
                  @keydown.enter.prevent="openFileDialog"
                  tabindex="0"          
                  role="button" 
                  aria-label="Upload files"
                  @dragenter.prevent="onDragEnter"
                  @dragleave.prevent="onDragLeave"
                  @dragover.prevent
                  @drop.prevent="onDrop"
                >

                <UploadIcon class="w-12 h-12 text-[gray] mb-4" />
                <label class="font-semibold text-[gray]">
                    {{ labelText }}
                </label>
                <p class="text-xs text-[gray] mt-1">PNG, JPG, or WEBP</p>

                <input
                    ref="fileInput"
                    :id="inputId"
                    name="file-upload"
                    type="file"
                    multiple
                    class="sr-only"
                    accept="image/png, image/jpeg, image/webp"
                    :disabled="isProcessing"
                    @change="onInputChange"
                />
                </div>

              <div v-if="items.length" class="mt-6">
                <div class="flex items-center justify-between mb-3">
                  <p class="text-[14px]">
                    アップロードされたファイル <span class="ml-2 text-xs text-gray-400">({{ items.length }})</span>
                  </p>
                  <CommandButton 
                    :buttons="[
                      {
                        title: 'すべてクリア', action: () => clearAll()
                      }
                    ]"
                  />
                </div>

                <ul class="pl-0 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                  <li
                    v-for="(it, i) in items"
                    :key="`${it.name}-${i}`"
                    class="group relative overflow-hidden ring-1 ring-gray-700/70 bg-gray-800/50 hover:bg-gray-800 transition-all"
                  >
                    <!-- Preview -->
                    <div class="aspect-video w-full">
                      <img
                        v-if="it.isImage"
                        :src="it.src"
                        :alt="`Preview ${it.name}`"
                        class="w-full h-full object-cover"
                        loading="lazy"
                        decoding="async"
                      />
                      <div v-else class="w-full h-full flex items-center justify-center bg-gray-900/60">
                        <!-- fallback icon -->
                        <svg class="w-10 h-10 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M8 16h8M8 12h8m-6 8h6a2 2 0 0 0 2-2V9.5L14.5 4H8a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2z"/>
                        </svg>
                      </div>
                    </div>

                    <!-- Bottom info bar -->
                    <div class="absolute inset-x-0 bottom-0 p-2 bg-gradient-to-t from-black/70 via-black/40 to-transparent">
                      <div class="text-xs text-white truncate" :title="it.name">{{ it.name }}</div>
                      <div class="mt-0.5 flex items-center gap-2 text-[11px] text-gray-300">
                        <span class="px-1.5 py-0.5 rounded bg-black/40 ring-1 ring-white/10">{{ it.sizeLabel }}</span>
                        <span v-if="it.type" class="px-1.5 py-0.5 rounded bg-black/40 ring-1 ring-white/10 truncate max-w-[8rem]">
                          {{ it.type }}
                        </span>
                      </div>
                    </div>

                    <!-- Hover actions -->
                    <div class="absolute top-2 right-2 flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                      <button
                        type="button"
                        class="p-1.5 rounded-md flex bg-black/60 hover:bg-black/80 text-white ring-1 ring-white/10"
                        @click="removeAt(i)"
                        aria-label="Remove file"
                      >
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                      </button>
                      <a
                        :href="it.src"
                        target="_blank"
                        rel="noopener"
                        class="p-1.5 rounded-md flex bg-black/60 hover:bg-black/80 text-white ring-1 ring-white/10"
                        aria-label="Open preview"
                      >
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M14 3h7v7m0-7L10 14M5 5v14h14"/>
                        </svg>
                      </a>
                    </div>
                  </li>
                </ul>
              </div>
              <div class="si-box">
                <LoaderButton :loading="isProcessing" @triggered="execute" content="実行する"/>
              </div>
            </div>
        </template>
    </Modal>
</template>
<script setup lang="ts">
import CommandButton from '@/components/Global/CommandButton.vue'
import LoaderButton from '@/components/Global/LoaderButton.vue'
import Modal from '@/components/Global/Modal.vue'
import { ContactType } from '@/interface/contactInterface'
import { ref, computed, watch, onBeforeUnmount, toRaw } from 'vue'

import { defineComponent, h } from 'vue'

const UploadIcon = defineComponent({
  name: 'UploadIcon',
  inheritAttrs: false,
  setup(_, { attrs }) {
    return () =>
      h('svg', { class: attrs.class, viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor' }, [
        h('path', {
          'stroke-linecap': 'round',
          'stroke-linejoin': 'round',
          'stroke-width': '2',
          d: 'M4 16v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2M16 8l-4-4m0 0L8 8m4-4v12'
        })
      ])
  }
})

type BatchCreationEmits = {
  (e: 'files-selected', payload): void
  (e: 'close'): void
}

const props = defineProps<{
  isProcessing: boolean
  contactTypes: ContactType[]
}>()
const contact_type_id = ref<number | null>(null)
const pseudo_type = ref<string>('')
const trigger = ref(0)
const types = computed(() => {
    const types = [...toRaw(props.contactTypes)]
    types.push({
        id: -1,
        title: 'その他（新規作成）'
    })
    return types
})
const typeError = computed(() => {
    return trigger.value && (contact_type_id.value == undefined || contact_type_id.value == null)
})
const typeInputError = computed(() => {
    return contact_type_id.value == -1 && !pseudo_type.value
})
const emit = defineEmits<BatchCreationEmits>()

watch(() => props.contactTypes, (types) => {
  if (!contact_type_id.value && types?.length) {
    contact_type_id.value = types[0]?.id ?? null
  }
}, { immediate: true })

const previews = ref<string[]>([])
const fileNames = ref<string[]>([])
const isDragging = ref(false)
const fileInput = ref<HTMLInputElement | null>(null);
const openFileDialog = () => fileInput.value?.click();

const inputId = `file-upload-${Math.random().toString(36).slice(2)}`

const selectedFiles = ref<File[]>([]); // if you have it

const formatBytes = (n?: number) => {
  if (!n && n !== 0) return '';
  const u = ['B','KB','MB','GB']; let i = 0; let x = n;
  while (x >= 1024 && i < u.length - 1) { x /= 1024; i++; }
  return `${x.toFixed(x < 10 && i ? 1 : 0)} ${u[i]}`;
};

const items = computed(() =>
  previews.value.map((src, i) => {
    const f = selectedFiles.value[i];
    const name = fileNames[i] ?? (f?.name ?? `file-${i + 1}`);
    const type = f?.type || '';
    return {
      src,
      name,
      type,
      sizeLabel: f ? formatBytes(f.size) : '',
      isImage: type.startsWith('image/')
    };
  })
);

const removeAt = (i: number) => {
  const url = previews[i];
  previews.value.splice(i, 1);
  fileNames.value.splice(i, 1);

  if (url?.startsWith('blob:')) URL.revokeObjectURL(url);

  selectedFiles.value.splice(i, 1);
}

const clearAll = () => {

  previews.value.forEach(u => u?.startsWith('blob:') && URL.revokeObjectURL(u));
  previews.value.splice(0);
  fileNames.value.splice(0);
  selectedFiles.value.splice(0);
}
const handleFileChange = (fileList: FileList | null) => {
  if (!fileList || fileList.length === 0) return

  const files = Array.from(fileList)
  const newPreviews = files.map(f => URL.createObjectURL(f))
  const newNames = files.map(f => f.name)

  selectedFiles.value.push(...files)
  previews.value.push(...newPreviews)
  fileNames.value.push(...newNames)
}
const execute = () => {
  if (selectedFiles.value.length === 0) return
  trigger.value++
  if (typeError.value || typeInputError.value) return
  const payload = {
    files: selectedFiles.value,
    type: contact_type_id.value,
    p_type: pseudo_type.value
  }
  emit('files-selected', payload)
}
const onDragEnter = () => {
  isDragging.value = true
}

const onDragLeave = (e: DragEvent) => {
  
  const related = e.relatedTarget as Node | null
  if (!related || !(e.currentTarget as Node).contains(related)) {
    isDragging.value = false
  }
}

const onDrop = (e: DragEvent) => {
  isDragging.value = false
  if (!props.isProcessing) {
    handleFileChange(e.dataTransfer?.files ?? null)
  }
}

const onInputChange = (e: Event) => {
  const target = e.target as HTMLInputElement
  handleFileChange(target.files)
  if (target) {
    target.value = ''
  }
}

const borderStyle = computed(() => {
  if (props.isProcessing) return 'border-gray-600'
  return isDragging.value ? 'border-brand-secondary' : 'border-gray-500'
})

const labelText = computed(() => {
  if (props.isProcessing) return '処理中...'
  return '名刺画像をここにドラッグ＆ドロップするか、クリックして選択してください'
})

watch(previews, (old, _new, onCleanup) => {
  onCleanup(() => old.forEach(url => URL.revokeObjectURL(url)))
})
onBeforeUnmount(() => {
  previews.value.forEach(url => URL.revokeObjectURL(url))
})
</script>
