<template>
    <div class="settings-panel">
        <p class="panel-hint">パスキーを使うと、指紋・顔認証やデバイスのPIN・セキュリティキーで、パスワードなしにログインできます。最も安全で、別の端末でもログインしやすい方法です。</p>
        <div v-if="!passkeySupported" class="tfa-error">このブラウザ／デバイスはパスキーに対応していません。</div>
        <template v-else>
            <label class="tfa-label">このパスキーの名前</label>
            <input class="tfa-input tfa-input--text" v-model="passkeyName" placeholder="例: 私のiPhone / 会社のMac">
            <div class="panel-actions panel-actions--left">
                <LoaderButton @triggered="addPasskey" :loading="passkeyLoading" :content="'パスキーを追加'"/>
            </div>
            <div class="tfa-devices" v-if="passkeys.length">
                <p class="tfa-devices__title">登録済みパスキー</p>
                <div class="settings-card">
                    <div v-for="p in passkeys" :key="p.id" class="device-row">
                        <div class="device-row__info">
                            <span class="device-row__name">{{ p.name }}</span>
                            <span class="device-row__meta">最終利用: {{ formatDate(p.last_used_at) }} ／ 登録: {{ formatDate(p.created_at) }}</span>
                        </div>
                        <button class="settings-btn-secondary settings-btn-secondary--sm" @click="deletePasskey(p.id)">削除</button>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>

<script setup>
import LoaderButton from '../../Global/LoaderButton.vue'
import { onMounted, ref } from 'vue'
import { useApi } from '@/composables/api'
import { useDialog } from '@/composables/dialog'
import { createPasskey, passkeysSupported } from '@/utils/webauthn'
import { formatDate } from '../useSettings'

    const api = useApi()
    const { ask, toast, ping } = useDialog()

    const passkeys = ref([])
    const passkeySupported = ref(true)
    const passkeyName = ref('')
    const passkeyLoading = ref(false)

    onMounted(async () => {
        passkeySupported.value = passkeysSupported()
        if (passkeySupported.value) await loadPasskeys()
    })

    const loadPasskeys = async () => {
        const res = await api.get('/user/passkeys', null, { silent: true })
        passkeys.value = Array.isArray(res) ? res : []
    }
    const addPasskey = async () => {
        if (!passkeysSupported()) { ping('このブラウザはパスキーに対応していません。'); return }
        passkeyLoading.value = true
        try {
            const { options } = await api.get('/user/passkeys/options', null, { silent: true })
            const credential = await createPasskey(options)
            await api.post('/user/passkeys', { name: passkeyName.value || 'パスキー', credential }, { silent: true })
            passkeyName.value = ''
            await loadPasskeys()
            toast('パスキーを追加しました。')
        } catch (e) {
            // NotAllowedError = user cancelled / timed out; stay quiet.
            if (e?.name !== 'NotAllowedError') ping('パスキーの登録に失敗しました。')
        } finally {
            passkeyLoading.value = false
        }
    }
    const deletePasskey = async (id) => {
        const answer = await ask('このパスキーを削除しますか。')
        if (!answer.value) return
        await api.del(`/user/passkeys/${id}`, null, { silent: true })
        await loadPasskeys()
        toast('削除しました。')
    }
</script>
