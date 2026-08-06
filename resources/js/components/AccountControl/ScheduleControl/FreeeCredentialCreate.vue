<template>
    <Modal @close="closeModal(false)">
        <template #title>
            <p>{{ editTarget ? 'freee連携を編集する' : 'freee連携を追加する' }}</p>
        </template>

        <template #content>
            <div class="si-box">
                <ShortInput
                    ref="labelRef"
                    v-model="params.label"
                    name="freeeLabel"
                    place-holder="設定名（必須）"
                    rules="required"
                    type="text"
                    custom-class="full"
                />
            </div>

            <div class="si-box freee-modal__section">
                <p class="freee-modal__heading">OAuthアプリ設定</p>
                <ShortInput
                    ref="clientIdRef"
                    v-model="params.client_id"
                    name="freeeClientId"
                    place-holder="クライアントID（必須）"
                    rules="required"
                    type="text"
                    custom-class="full"
                />
                <ShortInput
                    v-model="params.client_secret"
                    name="freeeClientSecret"
                    :place-holder="secretPlaceholder"
                    type="password"
                    custom-class="full"
                />
                <ShortInput
                    ref="redirectUriRef"
                    v-model="params.redirect_uri"
                    name="freeeRedirectUri"
                    place-holder="コールバックURL（必須）"
                    rules="required"
                    type="text"
                    :disabled="useOutOfBand"
                    custom-class="full"
                />
                <p class="freee-modal__note">
                    freeeのアプリ管理に登録したコールバックURLと完全に一致させてください。
                </p>

                <div class="freee-modal__oob">
                    <input id="freeeUseOob" v-model="useOutOfBand" type="checkbox">
                    <label for="freeeUseOob" class="cursor-pointer">
                        コールバックURLを登録できない（認可コードを手入力する）
                    </label>
                </div>
                <p v-if="useOutOfBand" class="freee-modal__note">
                    freeeのアプリ管理には
                    <span class="select-all">{{ OOB_REDIRECT_URI }}</span>
                    を登録してください。認可後、画面に表示されるコードを一覧画面に貼り付けます。
                </p>
            </div>

            <div class="si-box">
                <p class="form-title-small">利用状態</p>
                <div class="selectSwitchArea freee-modal__switch">
                    <input id="freeeCredentialActive" v-model="params.active" type="checkbox">
                    <label for="freeeCredentialActive" class="cursor-pointer">
                        <span></span>
                        <div class="switch-toggle"></div>
                    </label>
                </div>
            </div>

            <p class="freee-modal__note">
                保存後、一覧の「認可する」からブラウザでfreeeにログインし連携を許可してください。
                認可が必要なのは最初の一度だけです。
            </p>

            <div class="si-box">
                <LoaderButton
                    :loading="loading"
                    content="保存する"
                    @triggered="save"
                />
            </div>
        </template>
    </Modal>
</template>

<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import Modal from '@/components/Global/Modal.vue'
import ShortInput from '@/components/Form/ShortInput.vue'
import LoaderButton from '@/components/Global/LoaderButton.vue'
import { useApi } from '@/composables/api'
import type { FreeeCredentialSetting } from '@/interface/freeeInterface'

const props = defineProps<{
    editTarget: FreeeCredentialSetting | null
    defaultCallbackUrl: string
}>()
const emit = defineEmits<{
    close: [refresh: boolean]
}>()

const api = useApi()
const loading = ref(false)
const labelRef = ref<InstanceType<typeof ShortInput> | null>(null)
const clientIdRef = ref<InstanceType<typeof ShortInput> | null>(null)
const redirectUriRef = ref<InstanceType<typeof ShortInput> | null>(null)

// freeeがコールバックせず認可コードを画面表示する固定値。サーバー側の定数と一致させる。
const OOB_REDIRECT_URI = 'urn:ietf:wg:oauth:2.0:oob'

const params = reactive({
    label: props.editTarget?.label ?? '',
    client_id: props.editTarget?.client_id ?? '',
    client_secret: '',
    redirect_uri: props.editTarget?.redirect_uri ?? props.defaultCallbackUrl ?? '',
    active: props.editTarget?.active ?? true,
})

const useOutOfBand = ref(params.redirect_uri === OOB_REDIRECT_URI)
// 直前に入力していたコールバックURLを覚えておき、チェックを外したら戻す。
const lastCallbackUrl = ref(
    params.redirect_uri === OOB_REDIRECT_URI
        ? (props.defaultCallbackUrl ?? '')
        : params.redirect_uri,
)

watch(useOutOfBand, (enabled) => {
    if (enabled) {
        lastCallbackUrl.value = params.redirect_uri
        params.redirect_uri = OOB_REDIRECT_URI
    } else {
        params.redirect_uri = lastCallbackUrl.value || props.defaultCallbackUrl || ''
    }
})

const secretPlaceholder = computed(() => props.editTarget?.client_secret_configured
    ? 'クライアントシークレット（設定済み・変更時のみ入力）'
    : 'クライアントシークレット（必須）')

const closeModal = (refresh: boolean) => emit('close', refresh)

const validateForm = async () => {
    const validations = await Promise.all([
        labelRef.value?.validate(false),
        clientIdRef.value?.validate(false),
        redirectUriRef.value?.validate(false),
    ])
    return validations.every(result => result?.valid)
}

const save = async () => {
    if (!(await validateForm())) return

    loading.value = true
    try {
        const payload = {
            ...params,
            client_secret: params.client_secret || null,
        }

        if (props.editTarget) {
            await api.put(`/admin/freee-credentials/${props.editTarget.id}`, payload, {
                toast: 'freee連携設定を保存しました',
            })
        } else {
            await api.post('/admin/freee-credentials', payload, {
                toast: 'freee連携設定を追加しました',
            })
        }
        closeModal(true)
    } finally {
        loading.value = false
    }
}
</script>

<style scoped>
.freee-modal__section {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.freee-modal__heading {
    font-size: 14px;
}

.freee-modal__note {
    color: gray;
    font-size: 11px;
    line-height: 1.6;
}

.freee-modal__oob {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    font-size: 12px;
    line-height: 1.5;
}

.freee-modal__oob input {
    margin-top: 2px;
}

.freee-modal__switch {
    width: 84px;
    margin-top: 12px;
}

.freee-modal__switch label span::after {
    content: '無効';
    padding: 0 0 0 28px;
}

.freee-modal__switch input[type='checkbox']:checked + label span::after {
    content: '有効';
    padding: 0 32px 0 10px;
}

.freee-modal__switch input[type='checkbox']:checked ~ label .switch-toggle {
    transform: translateX(50px);
}
</style>
