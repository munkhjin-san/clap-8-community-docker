<template>
    <div class="settings-panel">
        <template v-if="emailOtp.status === 'enabled'">
            <div class="status-banner status-banner--on">
                <span class="status-banner__dot"></span>
                <div>
                    <p class="status-banner__title">メール二段階認証は有効です</p>
                    <p class="status-banner__desc">ログイン時に、登録メールアドレスへ送信される6桁コードの入力が必要です。</p>
                </div>
            </div>
            <div class="panel-actions panel-actions--danger">
                <LoaderButton @triggered="disableEmailOtp" :loading="emailOtp.loading" :content="'無効にする'"/>
            </div>
        </template>
        <template v-else-if="emailOtp.status === 'confirming'">
            <p class="panel-hint">登録メールアドレスに確認コードを送信しました。届いた6桁のコードを入力してください。</p>
            <label class="tfa-label">確認コード</label>
            <input class="tfa-input" v-model="emailOtp.code" inputmode="numeric" maxlength="6" placeholder="123456" autocomplete="one-time-code">
            <p v-if="emailOtp.error" class="tfa-error">{{ emailOtp.error }}</p>
            <div class="panel-actions panel-actions--left panel-actions--row">
                <LoaderButton @triggered="confirmEmailOtp" :loading="emailOtp.loading" :content="'確認して有効化'"/>
                <button class="settings-btn-secondary" @click="resendEmailOtp">再送</button>
            </div>
        </template>
        <template v-else>
            <p class="panel-hint">メール二段階認証を有効にすると、ログイン時に登録メールアドレスへ届く6桁コードの入力が必要になります。認証アプリが使えない場合の代替手段として便利です。（認証アプリを設定している場合はそちらが優先されます）</p>
            <div class="panel-actions panel-actions--left">
                <LoaderButton @triggered="enableEmailOtp" :loading="emailOtp.loading" :content="'有効にする'"/>
            </div>
        </template>
    </div>
</template>

<script setup>
import LoaderButton from '../../Global/LoaderButton.vue'
import { computed, onMounted, ref } from 'vue'
import { useAuthUserStore } from '@/store/auth'
import { useApi } from '@/composables/api'
import { useDialog } from '@/composables/dialog'
import { useSettingsUser } from '../useSettings'

    const auth = useAuthUserStore()
    const api = useApi()
    const { ask, toast, ping } = useDialog()
    const { updateUser } = useSettingsUser()

    const emailOtpEnabled = computed(() => !!(auth.user && auth.user.email_otp_enabled_at))
    const emailOtp = ref({ status: 'idle', code: '', error: '', loading: false }) // 'idle' | 'confirming' | 'enabled'

    onMounted(() => {
        emailOtp.value.status = emailOtpEnabled.value ? 'enabled' : 'idle'
    })

    const enableEmailOtp = async () => {
        emailOtp.value.loading = true
        try {
            await api.post('/user/email-otp/send', null, { silent: true })
            emailOtp.value.error = ''
            emailOtp.value.status = 'confirming'
        } catch (e) {
            emailOtp.value.error = e?.response?.data?.message || '送信に失敗しました。'
        } finally {
            emailOtp.value.loading = false
        }
    }
    const confirmEmailOtp = async () => {
        if (!emailOtp.value.code) { emailOtp.value.error = 'コードを入力してください。'; return }
        emailOtp.value.loading = true
        try {
            await api.post('/user/email-otp/confirm', { code: emailOtp.value.code }, { silent: true })
            emailOtp.value.code = ''
            emailOtp.value.error = ''
            await updateUser()
            emailOtp.value.status = 'enabled'
            toast('メール二段階認証を有効にしました。')
        } catch (e) {
            emailOtp.value.error = e?.response?.data?.message || 'コードが正しくありません。'
        } finally {
            emailOtp.value.loading = false
        }
    }
    const resendEmailOtp = async () => {
        await api.post('/user/email-otp/send', null, { silent: true })
        toast('コードを再送しました。')
    }
    const disableEmailOtp = async () => {
        const answer = await ask('メール二段階認証を無効にしますか。')
        if (!answer.value) return
        emailOtp.value.loading = true
        try {
            await api.del('/user/email-otp', null, { silent: true })
            await updateUser()
            emailOtp.value.status = 'idle'
            toast('無効にしました。')
        } catch (e) {
            ping('無効化に失敗しました。')
        } finally {
            emailOtp.value.loading = false
        }
    }
</script>
