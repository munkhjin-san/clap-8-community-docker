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
                  @click="openFileDialog('gallery')"
                  @keydown.space.prevent="openFileDialog('gallery')"
                  @keydown.enter.prevent="openFileDialog('gallery')"
                  tabindex="0"          
                  role="button" 
                  aria-label="Upload files"
                  @dragenter.prevent="onDragEnter"
                  @dragleave.prevent="onDragLeave"
                  @dragover.prevent
                  @drop.prevent="onDrop"
                >
                <div class="flex gap-5 mb-4 items-center">
                  <UploadIcon class="w-8 h-8 text-[gray]" />
                  <span class="text-[gray]">|</span>
                  <div class="flex" @click.stop="openFileDialog('camera')">
                    <svg version="1.1" fill="gray" stroke-width="2" xmlns="http://www.w3.org/2000/svg" width="30" height="32"
                        viewBox="0 0 40 32">
                        <path d="M39.981 14.258c-0.008-1.304-0.034-2.608-0.072-3.91l-0.013-0.488-0.008-0.147c-0.002-0.053-0.005-0.104-0.010-0.16-0.008-0.107-0.021-0.216-0.034-0.326-0.062-0.438-0.181-0.904-0.413-1.365-0.227-0.458-0.576-0.901-1.005-1.234s-0.914-0.554-1.379-0.678c-0.234-0.064-0.462-0.106-0.686-0.133-0.222-0.027-0.45-0.038-0.642-0.040l-5.653-0.126-0.434-2.070-0.166-0.8-0.085-0.4-0.006-0.029-0.008-0.034-0.029-0.104c-0.019-0.067-0.038-0.138-0.062-0.197-0.043-0.125-0.096-0.246-0.155-0.363-0.238-0.472-0.606-0.874-1.048-1.162-0.442-0.286-0.966-0.458-1.494-0.482h-13.248c-0.528 0.024-1.050 0.195-1.494 0.482-0.442 0.286-0.808 0.69-1.048 1.16-0.059 0.118-0.112 0.24-0.155 0.365-0.022 0.059-0.043 0.13-0.062 0.197l-0.027 0.104-0.008 0.034-0.006 0.026-0.083 0.4-0.166 0.802-0.435 2.070-5.517 0.12c-0.019 0-0.061 0.005-0.091 0.006-0.062 0.005-0.133 0.010-0.19 0.016l-0.17 0.021c-0.117 0.016-0.23 0.034-0.347 0.058-0.462 0.094-0.941 0.251-1.398 0.509-0.456 0.258-0.888 0.622-1.218 1.067-0.333 0.442-0.557 0.957-0.67 1.466-0.058 0.25-0.090 0.515-0.102 0.746l-0.026 0.522c-0.018 0.347-0.032 0.698-0.042 1.043-0.050 1.389-0.070 2.774-0.074 4.157-0.002 2.766 0.074 5.523 0.206 8.282 0.067 1.379 0.154 2.757 0.256 4.138l0.019 0.256 0.029 0.302c0.024 0.21 0.058 0.429 0.106 0.656 0.053 0.229 0.122 0.472 0.23 0.728 0.106 0.254 0.256 0.525 0.462 0.781 0.205 0.256 0.474 0.485 0.75 0.65 0.275 0.168 0.554 0.278 0.805 0.352 0.506 0.146 0.926 0.182 1.309 0.216 0.384 0.032 0.722 0.042 1.075 0.059 0.349 0.014 0.701 0.035 1.046 0.045 0.694 0.024 1.389 0.048 2.080 0.066 1.384 0.037 2.766 0.066 4.149 0.088s2.763 0.042 4.142 0.045c1.381 0.003 2.762 0.013 4.142-0.002 0 0 5.853 0.010 7.995-0.043 2.664-0.066 6.666 0.003 7.994-0.221s2.035-0.939 2.277-1.926c0.24-0.99 0.35-2.55 0.466-3.853 0.107-1.301 0.205-2.605 0.275-3.909 0.126-2.608 0.19-5.219 0.166-7.829zM29.059 29.288c-2.749-0.013-5.501-0.016-8.248-0.050l-8.248-0.080c-1.373-0.018-2.747-0.035-4.117-0.067-0.688-0.013-3.501-0.056-4.056-0.144-0.557-0.088-1.011-0.781-1.117-1.357-0.104-0.573-0.174-2.706-0.235-4.062-0.122-2.714-0.186-5.432-0.173-8.142 0.006-1.357 0.032-2.71 0.085-4.056 0.010-0.339 0.022-0.675 0.042-1.011l0.027-0.504c0.006-0.109 0.018-0.186 0.035-0.269 0.038-0.16 0.099-0.285 0.178-0.39 0.080-0.106 0.182-0.197 0.325-0.277 0.142-0.082 0.325-0.144 0.536-0.186 0.053-0.011 0.109-0.019 0.165-0.027l0.086-0.010 0.067-0.006c0.013 0 0.014-0.002 0.037-0.002l0.064-0.002 0.13-0.002 0.256-0.006 2.056-0.035 4.114-0.077c0.666-0.011 1.259-0.475 1.402-1.15v-0.005c0 0 0.563-2.661 0.813-3.838 0.069-0.322 0.354-0.552 0.685-0.552 2.155 0 9.829 0.003 11.984 0.005 0.331 0 0.618 0.23 0.685 0.554l0.811 3.832 0.002 0.006c0.136 0.646 0.706 1.138 1.398 1.149l6.845 0.117c0.134 0 0.232 0.005 0.333 0.016s0.19 0.027 0.27 0.050c0.163 0.043 0.28 0.101 0.365 0.166s0.15 0.141 0.213 0.261c0.062 0.118 0.114 0.286 0.144 0.488 0.008 0.051 0.014 0.101 0.018 0.155 0.003 0.027 0.005 0.056 0.006 0.083l0.005 0.096 0.018 0.482c0.043 1.283 0.077 2.565 0.093 3.848 0.014 1.282 0.019 2.563 0.011 3.846-0.011 1.282-0.042 2.56-0.086 3.838-0.066 2.117-0.186 4.234-0.342 6.349-0.037 0.515-0.467 0.915-0.987 0.928-0.853 0.018-1.71 0.029-2.57 0.030-1.374 0.008-2.747 0.011-4.122 0.008z"></path>
                        <path d="M26.002 12.024c-0.773-0.794-1.704-1.44-2.734-1.878-1.029-0.438-2.149-0.661-3.262-0.662-1.114 0.005-2.234 0.229-3.259 0.67-1.026 0.443-1.952 1.093-2.72 1.886-1.536 1.594-2.397 3.798-2.36 5.982l0.045 0.813c0.034 0.272 0.082 0.541 0.123 0.81 0.056 0.266 0.13 0.531 0.197 0.794 0.080 0.262 0.179 0.515 0.272 0.773 0.414 1.011 1.037 1.934 1.819 2.693s1.712 1.357 2.722 1.749c1.008 0.395 2.094 0.576 3.162 0.554v-0.048c1.062 0.034 2.144-0.144 3.149-0.534 1.011-0.384 1.936-0.984 2.72-1.734 0.782-0.75 1.416-1.666 1.84-2.672 0.429-1.008 0.651-2.102 0.662-3.194 0.014-1.094-0.184-2.2-0.592-3.226-0.406-1.027-1.014-1.976-1.782-2.774zM23.93 21.946c-0.499 0.531-1.107 0.962-1.774 1.267-0.669 0.306-1.398 0.485-2.15 0.522v-0.050c-0.746-0.026-1.472-0.2-2.139-0.501-0.664-0.302-1.269-0.73-1.768-1.253-1-1.046-1.629-2.453-1.603-3.909 0.021-0.728 0.178-1.437 0.469-2.091 0.288-0.651 0.714-1.24 1.229-1.723 0.514-0.488 1.122-0.866 1.771-1.117 0.648-0.253 1.341-0.384 2.040-0.382s1.39 0.136 2.037 0.392c0.646 0.254 1.251 0.635 1.76 1.123 0.51 0.485 0.931 1.070 1.219 1.72 0.288 0.648 0.443 1.355 0.462 2.078 0.022 1.442-0.552 2.862-1.552 3.923z"></path>
                    </svg>
                  </div>
                </div>
                
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
let opening = false
const openFileDialog = (mode: 'gallery' | 'camera') => {
  if (opening) return
  opening = true
  const el = fileInput.value
  if (!el) { opening = false; return }

  el.value = ''

  el.multiple = true
  el.removeAttribute('capture')
  el.setAttribute('accept', 'image/png,image/jpeg,image/webp');

  if (mode === 'camera') {
    el.setAttribute('accept', 'image/*')
    el.setAttribute('capture', 'enviroment')
  }
  requestAnimationFrame(() => {
    el.click()
    setTimeout(() => { opening = false; }, 250);
  })
}

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
