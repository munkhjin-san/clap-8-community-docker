<template>
    <div class="settings-panel">
        <p class="panel-hint">モバイル下部のメニューバーを表示するか選択します。</p>
        <div class="settings-card">
            <label class="option-row" :class="{ 'option-row--active': auth.user.footer_view == 1 }">
                <span class="option-row__label">表示する</span>
                <input :checked="auth.user.footer_view == 1" @change="footerMenuToggle" class="option-radio" type="radio" name="footer" :value="1">
                <span class="option-check"></span>
            </label>
            <label class="option-row" :class="{ 'option-row--active': auth.user.footer_view == 0 }">
                <span class="option-row__label">表示しない</span>
                <input :checked="auth.user.footer_view == 0" @change="footerMenuToggle" class="option-radio" type="radio" name="footer" :value="2">
                <span class="option-check"></span>
            </label>
        </div>
    </div>
</template>

<script setup>
import { useAuthUserStore } from '@/store/auth'
import { useApi } from '@/composables/api'

    const auth = useAuthUserStore()
    const api = useApi()

    const footerMenuToggle = (event) => {
        const v = event.target.value == 1
        auth.setFooterView(v)
        api.patch('set_footer_view', { value: v })
    }
</script>
