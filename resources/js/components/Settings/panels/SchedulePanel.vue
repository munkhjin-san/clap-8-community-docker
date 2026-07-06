<template>
    <div class="settings-panel">
        <p class="panel-hint">カレンダーアプリに購読できるURLを発行します（iCalendar形式）。</p>
        <div v-if="icalUrl.status" class="ical-box">
            <div ref="icalRef" class="ical-box__url">{{ icalUrl.url }}</div>
            <button @click.prevent="copyUrl" class="settings-btn-secondary">コピー</button>
        </div>
        <div class="panel-actions panel-actions--left">
            <LoaderButton content="URL生成" :loading="urlCreating" @triggered="createUrl"/>
        </div>
    </div>
</template>

<script setup>
import LoaderButton from '../../Global/LoaderButton.vue'
import { onMounted, ref } from 'vue'
import { useAuthUserStore } from '@/store/auth'
import { useApi } from '@/composables/api'
import { useDialog } from '@/composables/dialog'

    const auth = useAuthUserStore()
    const api = useApi()
    const { ping } = useDialog()

    const urlCreating = ref(false)
    const icalRef = ref(null)
    const icalUrl = ref({ status: false, url: '' })

    onMounted(() => {
        if (auth.user && auth.user.ical_key) {
            icalUrl.value = {
                status: true,
                url: `${window.location.origin}/export_ical?id=${auth.id}&token=${auth.user.ical_key}`,
            }
        }
    })

    const createUrl = async () => {
        const response = await api.get('/ical_url_generate')
        if (response.success) {
            icalUrl.value = { status: true, url: response.url }
        }
    }
    const copyUrl = () => {
        const selectedText = icalRef.value ? icalRef.value.textContent : ''
        if (!selectedText) { ping('コピーに失敗しました。'); return }
        navigator.clipboard.writeText(selectedText)
            .then(() => ping('コピーしました。'))
            .catch((error) => ping('テキストをクリップチャットにコピーできません:', error))
    }
</script>
