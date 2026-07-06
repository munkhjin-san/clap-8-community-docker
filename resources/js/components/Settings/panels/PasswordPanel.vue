<template>
    <div class="settings-panel">
        <p class="panel-hint">アカウントを保護するため、定期的な変更をおすすめします。</p>

        <label class="tfa-label">現在のパスワード</label>
        <input class="tfa-input tfa-input--text" type="password" v-model="currentPassword" autocomplete="current-password" placeholder="現在のパスワード">

        <label class="tfa-label">新しいパスワード</label>
        <input class="tfa-input tfa-input--text" type="password" v-model="newPassword" autocomplete="new-password" placeholder="8文字以上">

        <label class="tfa-label">新しいパスワードの確認</label>
        <input class="tfa-input tfa-input--text" type="password" v-model="newPasswordConfirm" autocomplete="new-password" placeholder="もう一度入力">

        <p v-if="error" class="tfa-error">{{ error }}</p>

        <div class="panel-actions panel-actions--left">
            <LoaderButton @triggered="reset" :loading="loader" :content="'保存'"/>
        </div>
    </div>
</template>

<script setup>
import LoaderButton from '../../Global/LoaderButton.vue'
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useApi } from '@/composables/api'
import { useDialog } from '@/composables/dialog'

    const router = useRouter()
    const api = useApi()
    const { toast } = useDialog()

    const currentPassword = ref('')
    const newPassword = ref('')
    const newPasswordConfirm = ref('')
    const loader = ref(false)
    const error = ref('')

    const reset = async () => {
        error.value = ''
        if (!currentPassword.value) { error.value = '現在のパスワードを入力してください。'; return }
        if (!newPassword.value || newPassword.value.length < 8) { error.value = '新しいパスワードは8文字以上で入力してください。'; return }
        if (newPassword.value !== newPasswordConfirm.value) { error.value = '新しいパスワードが一致しません。'; return }

        loader.value = true
        try {
            await api.post('/user_pass_change_api', {
                current: currentPassword.value,
                password: newPassword.value,
                password_confirmation: newPasswordConfirm.value,
            })
            currentPassword.value = newPassword.value = newPasswordConfirm.value = ''
            toast('変更しました。')
            router.push({ name: 'settings' })
        } finally {
            loader.value = false
        }
    }
</script>
