<template>
    <div class="settings-panel">
        <!-- Enabled -->
        <template v-if="twoFa.status === 'enabled'">
            <div class="status-banner status-banner--on">
                <span class="status-banner__dot"></span>
                <div>
                    <p class="status-banner__title">二段階認証は有効です</p>
                    <p class="status-banner__desc">ログイン時に、パスワードに加えて認証アプリのコードが必要です。</p>
                </div>
            </div>
            <div class="tfa-actions">
                <button class="settings-btn-secondary" @click="loadRecoveryCodes">リカバリーコードを表示</button>
                <button class="settings-btn-secondary" @click="regenerateRecoveryCodes">再生成</button>
            </div>
            <div v-if="twoFa.recoveryCodes.length" class="settings-card settings-card--pad tfa-recovery">
                <p class="panel-hint" style="margin-top:0;">各コードは1回だけ使用できます。安全な場所に保管してください。</p>
                <ul class="recovery-list"><li v-for="c in twoFa.recoveryCodes" :key="c">{{ c }}</li></ul>
            </div>
            <div class="tfa-devices" v-if="trustedDevices.length">
                <p class="tfa-devices__title">
                    記憶された端末
                    <button class="settings-btn-secondary settings-btn-secondary--sm" style="margin-left:auto;" @click="revokeAllDevices">すべて解除</button>
                </p>
                <div class="settings-card">
                    <div v-for="d in trustedDevices" :key="d.id" class="device-row">
                        <div class="device-row__info">
                            <span class="device-row__name">{{ d.device_name || '不明な端末' }}<span v-if="d.is_current" class="device-row__current">（現在の端末）</span></span>
                            <span class="device-row__meta">最終利用: {{ formatDate(d.last_used_at) }} ／ 期限: {{ formatDate(d.expires_at) }}</span>
                        </div>
                        <button class="settings-btn-secondary settings-btn-secondary--sm" @click="revokeDevice(d.id)">解除</button>
                    </div>
                </div>
            </div>
            <div class="panel-actions panel-actions--danger">
                <LoaderButton @triggered="disable2fa" :loading="twoFa.loading" :content="'二段階認証を無効にする'"/>
            </div>
        </template>

        <!-- Enrolling -->
        <template v-else-if="twoFa.status === 'enrolling'">
            <p class="panel-hint">認証アプリ（Google Authenticator / Microsoft Authenticator など）で、以下のQRコードをスキャンしてください。</p>
            <div class="tfa-qr" v-html="twoFa.qrSvg"></div>
            <p v-if="twoFa.secretKey" class="tfa-secret">手動入力キー: <code>{{ twoFa.secretKey }}</code></p>
            <div v-if="twoFa.recoveryCodes.length" class="settings-card settings-card--pad tfa-recovery">
                <p class="panel-hint" style="margin-top:0;">リカバリーコード（紛失時用に保管してください）:</p>
                <ul class="recovery-list"><li v-for="c in twoFa.recoveryCodes" :key="c">{{ c }}</li></ul>
            </div>
            <label class="tfa-label">アプリに表示された6桁のコード</label>
            <input class="tfa-input" v-model="twoFa.confirmCode" inputmode="numeric" maxlength="6" placeholder="123456" autocomplete="one-time-code">
            <p v-if="twoFa.error" class="tfa-error">{{ twoFa.error }}</p>
            <div class="panel-actions panel-actions--left panel-actions--row">
                <LoaderButton @triggered="confirm2fa" :loading="twoFa.loading" :content="'確認して有効化'"/>
                <button class="settings-btn-secondary" @click="cancelEnroll">キャンセル</button>
            </div>
        </template>

        <!-- Idle -->
        <template v-else>
            <p class="panel-hint">二段階認証を有効にすると、ログイン時にパスワードに加えて認証アプリのワンタイムコードが必要になり、アカウントの安全性が高まります。</p>
            <div class="panel-actions panel-actions--left">
                <LoaderButton @triggered="enable2fa" :loading="twoFa.loading" :content="'二段階認証を有効にする'"/>
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
import { useSettingsUser, formatDate } from '../useSettings'

    const auth = useAuthUserStore()
    const api = useApi()
    const { ask, toast, ping } = useDialog()
    const { updateUser } = useSettingsUser()

    const twoFaEnabled = computed(() => !!(auth.user && auth.user.two_factor_confirmed_at))
    const twoFa = ref({
        status: 'idle', // 'idle' | 'enrolling' | 'enabled'
        qrSvg: '',
        secretKey: '',
        recoveryCodes: [],
        confirmCode: '',
        error: '',
        loading: false,
    })
    const trustedDevices = ref([])

    onMounted(() => {
        twoFa.value.status = twoFaEnabled.value ? 'enabled' : 'idle'
        if (twoFa.value.status === 'enabled') loadTrustedDevices()
    })

    const loadTrustedDevices = async () => {
        const res = await api.get('/trusted-devices', null, { silent: true })
        trustedDevices.value = Array.isArray(res) ? res : []
    }
    const revokeDevice = async (id) => {
        const answer = await ask('この端末の記憶を解除しますか。次回ログイン時にコード入力が必要になります。')
        if (!answer.value) return
        await api.del(`/trusted-devices/${id}`, null, { silent: true })
        await loadTrustedDevices()
        toast('解除しました。')
    }
    const revokeAllDevices = async () => {
        const answer = await ask('記憶されたすべての端末を解除しますか。')
        if (!answer.value) return
        await api.del('/trusted-devices', null, { silent: true })
        await loadTrustedDevices()
        toast('すべて解除しました。')
    }
    const enable2fa = async () => {
        twoFa.value.loading = true
        try {
            await api.post('/user/two-factor-authentication', null, { silent: true })
            const [qr, key, codes] = await Promise.all([
                api.get('/user/two-factor-qr-code', null, { silent: true }),
                api.get('/user/two-factor-secret-key', null, { silent: true }),
                api.get('/user/two-factor-recovery-codes', null, { silent: true }),
            ])
            twoFa.value.qrSvg = qr?.svg || ''
            twoFa.value.secretKey = key?.secretKey || ''
            twoFa.value.recoveryCodes = Array.isArray(codes) ? codes : []
            twoFa.value.error = ''
            twoFa.value.status = 'enrolling'
        } catch (e) {
            ping('二段階認証の開始に失敗しました。')
        } finally {
            twoFa.value.loading = false
        }
    }
    const confirm2fa = async () => {
        if (!twoFa.value.confirmCode) { twoFa.value.error = 'コードを入力してください。'; return }
        twoFa.value.loading = true
        try {
            await api.post('/user/confirmed-two-factor-authentication', { code: twoFa.value.confirmCode }, { silent: true })
            twoFa.value.confirmCode = ''
            twoFa.value.error = ''
            twoFa.value.qrSvg = ''
            await updateUser()
            twoFa.value.status = 'enabled'
            loadTrustedDevices()
            toast('二段階認証を有効にしました。')
        } catch (e) {
            twoFa.value.error = e?.response?.data?.message || 'コードが正しくありません。'
        } finally {
            twoFa.value.loading = false
        }
    }
    const cancelEnroll = async () => {
        try { await api.del('/user/two-factor-authentication', null, { silent: true }) } catch (e) { /* noop */ }
        Object.assign(twoFa.value, { status: 'idle', qrSvg: '', secretKey: '', recoveryCodes: [], confirmCode: '', error: '' })
        await updateUser()
    }
    const disable2fa = async () => {
        const answer = await ask('二段階認証を無効にしますか。')
        if (!answer.value) return
        twoFa.value.loading = true
        try {
            await api.del('/user/two-factor-authentication', null, { silent: true })
            twoFa.value.recoveryCodes = []
            trustedDevices.value = []
            await updateUser()
            twoFa.value.status = 'idle'
            toast('二段階認証を無効にしました。')
        } catch (e) {
            ping('無効化に失敗しました。')
        } finally {
            twoFa.value.loading = false
        }
    }
    const loadRecoveryCodes = async () => {
        const codes = await api.get('/user/two-factor-recovery-codes', null, { silent: true })
        twoFa.value.recoveryCodes = Array.isArray(codes) ? codes : []
    }
    const regenerateRecoveryCodes = async () => {
        const answer = await ask('リカバリーコードを再生成しますか。古いコードは使えなくなります。')
        if (!answer.value) return
        await api.post('/user/two-factor-recovery-codes', null, { silent: true })
        await loadRecoveryCodes()
        toast('リカバリーコードを再生成しました。')
    }
</script>
