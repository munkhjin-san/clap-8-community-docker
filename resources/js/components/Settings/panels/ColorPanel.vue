<template>
    <div class="settings-panel">
        <p class="panel-hint">アクセントカラーを選択してください。</p>
        <div class="color-grid">
            <button
                v-for="(color, index) in avialableColors"
                :key="index"
                class="color-chip"
                :class="{ 'color-chip--selected': chosenColor == color.id }"
                :style="{ backgroundColor: color.light }"
                @click="chosenColor = color.id"
                :aria-label="color.name"
            >
                <svg v-if="chosenColor == color.id" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#1a1a1a" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
            </button>
        </div>
        <div class="panel-actions">
            <LoaderButton @triggered="setSelectedColor" :loading="loader" :content="'保存'"/>
        </div>
    </div>
</template>

<script setup>
import colors from '../../../../assets/colors.json'
import LoaderButton from '../../Global/LoaderButton.vue'
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useApi } from '@/composables/api'
import { useAuthUserStore } from '@/store/auth'
import { useSettingsUser } from '../useSettings'

    const router = useRouter()
    const api = useApi()
    const auth = useAuthUserStore()
    const { updateUser } = useSettingsUser()

    const avialableColors = colors
    const chosenColor = ref(auth.user ? auth.user.color : (colors[0] ? colors[0].id : ''))
    const loader = ref(false)

    const setSelectedColor = async () => {
        if (loader.value) return
        loader.value = true
        await api.post('/profile_set_color', { value: chosenColor.value }, { toast: '保存しました。' })
        loader.value = false
        await updateUser()
        router.push({ name: 'settings' })
    }
</script>
