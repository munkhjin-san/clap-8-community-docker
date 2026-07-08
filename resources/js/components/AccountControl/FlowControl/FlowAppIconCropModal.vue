<template>
    <Modal @close="emit('close')">
        <template #title><p>アイコンをアップロード</p></template>
        <template #content>
            <div class="fic-crop">
                <Cropper ref="cropperRef" />
            </div>
            <p class="fic-hint">正方形に切り抜かれます。</p>
            <div class="fic-actions">
                <LoaderButton @triggered="save" :loading="saving" content="保存する" />
            </div>
        </template>
    </Modal>
</template>

<script setup lang="ts">
import { ref, useTemplateRef } from 'vue'
import Modal from '@/components/Global/Modal.vue'
import Cropper from '@/components/Global/Cropper.vue'
import LoaderButton from '@/components/Global/LoaderButton.vue'

const emit = defineEmits<{ close: []; cropped: [string] }>()
const cropperRef = useTemplateRef<any>('cropperRef')
const saving = ref(false)

const downscale = (blob: Blob, size: number) => new Promise<string>((resolve, reject) => {
    const img = new Image()
    const url = URL.createObjectURL(blob)
    img.onload = () => {
        const canvas = document.createElement('canvas')
        canvas.width = size
        canvas.height = size
        const ctx = canvas.getContext('2d')
        if (!ctx) { reject(new Error('no ctx')); return }
        const s = Math.min(img.width, img.height)
        ctx.drawImage(img, (img.width - s) / 2, (img.height - s) / 2, s, s, 0, 0, size, size)
        URL.revokeObjectURL(url)
        resolve(canvas.toDataURL('image/webp', 0.85))
    }
    img.onerror = () => { URL.revokeObjectURL(url); reject(new Error('load failed')) }
    img.src = url
})

const save = async () => {
    if (!cropperRef.value) return
    saving.value = true
    try {
        const res = await cropperRef.value.complete()
        if (!res?.blob) return
        emit('cropped', await downscale(res.blob, 128))
    } finally {
        saving.value = false
    }
}
</script>

<style scoped>
.fic-crop { position: relative; height: 300px; border: 1px solid var(--calendarBorder); border-radius: 8px; overflow: hidden; }
.fic-hint { font-size: 12px; color: gray; margin-top: 8px; }
.fic-actions { display: flex; justify-content: flex-end; margin-top: 14px; }
</style>
