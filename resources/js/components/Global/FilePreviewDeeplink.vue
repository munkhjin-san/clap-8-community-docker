<script setup lang="ts">
import { useApi } from '@/composables/api'
import { useFilePreview } from '@/store/filePreview'
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
const route = useRoute(), router = useRouter()
const file = ref<any>(null), loading = ref(true), error = ref<string|null>(null)
const api = useApi()
const filePreview = useFilePreview()
onMounted(async () => {
    const id = route.params.fileId as string
    const meta = await api.get(`/drive/preview/${id}`)                 // GET /api/drive/{id}
    if (meta.type !== 'file') throw new Error('ファイルではありません')
    file.value = {
      ...meta,
      file_path: `/cdn/${meta.storage_path}`,
      doc_path: `/${meta.storage_path}`,
      mime_type: meta.mime?.split('/')[0],
      extension: meta.ext,
    }
    const data = {
        active: true,
        files: [file.value],
        source: 'deeplink',
        index: 0,
        message: null,
    }
    filePreview.setFilePreview(data)
    loading.value = false
})
</script>

<template>
  <div class="min-h-screen bg-[var(--bg)] text-[var(--fg)]">
    <div v-if="loading" class="p-6 text-sm opacity-70 text-[var(--primary-color)]">読み込み中…</div>
    <div v-else-if="error" class="p-6 text-sm text-red-400">{{ error }}</div>
    <div v-else class="p-3">
      <!-- your existing previewer, pointed at 'file' only -->
    </div>
  </div>
</template>
