<template>
    <Modal @close="closeModal(false)">
        <template #title>
            <p>{{ editTarget ? 'Web会議設定を編集する' : 'Web会議設定を追加する' }}</p>
        </template>

        <template #content>
            <div class="si-box">
                <ShortInput
                    ref="labelRef"
                    v-model="params.label"
                    name="zoomLabel"
                    place-holder="設定名（必須）"
                    rules="required"
                    type="text"
                    custom-class="full"
                />
            </div>

            <div class="si-box">
                <ShortInput
                    ref="emailRef"
                    v-model="params.host_email"
                    name="zoomHostEmail"
                    place-holder="ホストメール（必須）"
                    rules="required|email"
                    type="email"
                    custom-class="full"
                />
            </div>

            <div class="si-box">
                <ShortInput
                    v-model="params.host_password"
                    name="zoomHostPassword"
                    :place-holder="secretPlaceholder('ホストパスワード', editTarget?.host_password_configured)"
                    type="password"
                    custom-class="full"
                />
            </div>

            <div class="si-box web-meeting-modal__section">
                <p class="web-meeting-modal__heading">Server-to-Server OAuth設定</p>
                <ShortInput
                    ref="accountIdRef"
                    v-model="params.account_id"
                    name="zoomAccountId"
                    place-holder="アカウントID（必須）"
                    rules="required"
                    type="text"
                    custom-class="full"
                />
                <ShortInput
                    ref="clientIdRef"
                    v-model="params.client_id"
                    name="zoomClientId"
                    place-holder="クライアントID（必須）"
                    rules="required"
                    type="text"
                    custom-class="full"
                />
                <ShortInput
                    v-model="params.client_secret"
                    name="zoomClientSecret"
                    :place-holder="secretPlaceholder('クライアントシークレット', editTarget?.client_secret_configured)"
                    type="password"
                    custom-class="full"
                />
            </div>

            <div class="si-box web-meeting-modal__section">
                <p class="web-meeting-modal__heading">Webhook設定</p>
                <ShortInput
                    v-model="params.webhook_secret"
                    name="zoomWebhookSecret"
                    :place-holder="secretPlaceholder('Webhookシークレットトークン', editTarget?.webhook_secret_configured)"
                    type="password"
                    custom-class="full"
                />
                <p v-if="editTarget" class="web-meeting-modal__url">
                    Webhook URL：<span>{{ webhookUrl(editTarget.slot) }}</span>
                </p>
            </div>

            <div class="si-box">
                <p class="form-title-small">利用状態</p>
                <div class="selectSwitchArea web-meeting-modal__switch">
                    <input id="zoomAccountActive" v-model="params.active" type="checkbox">
                    <label for="zoomAccountActive" class="cursor-pointer">
                        <span></span>
                        <div class="switch-toggle"></div>
                    </label>
                </div>
            </div>

            <div v-if="editTarget" class="si-box">
                <LoaderButton
                    :loading="testing"
                    content="Zoom API接続確認"
                    @triggered="testConnection"
                />
                <p
                    v-if="testResult"
                    class="web-meeting-modal__test-result"
                    :class="testResult.success ? 'is-success' : 'is-error'"
                >
                    {{ testResult.message }}
                </p>
            </div>

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
import { reactive, ref } from 'vue'
import Modal from '@/components/Global/Modal.vue'
import ShortInput from '@/components/Form/ShortInput.vue'
import LoaderButton from '@/components/Global/LoaderButton.vue'
import { useApi } from '@/composables/api'
import type { ZoomAccountSetting } from '@/interface/zoomAccountInterface'

const props = defineProps<{
    editTarget: ZoomAccountSetting | null
}>()
const emit = defineEmits<{
    close: [refresh: boolean]
}>()

const api = useApi()
const loading = ref(false)
const testing = ref(false)
const testResult = ref<{ success: boolean, message: string } | null>(null)
const labelRef = ref<InstanceType<typeof ShortInput> | null>(null)
const emailRef = ref<InstanceType<typeof ShortInput> | null>(null)
const accountIdRef = ref<InstanceType<typeof ShortInput> | null>(null)
const clientIdRef = ref<InstanceType<typeof ShortInput> | null>(null)
const params = reactive({
    label: props.editTarget?.label ?? '',
    host_email: props.editTarget?.host_email ?? '',
    host_password: '',
    account_id: props.editTarget?.account_id ?? '',
    client_id: props.editTarget?.client_id ?? '',
    client_secret: '',
    webhook_secret: '',
    active: props.editTarget?.active ?? false,
})

const secretPlaceholder = (label: string, configured = false) => configured
    ? `${label}（設定済み・変更時のみ入力）`
    : label

const webhookUrl = (slot: number) => slot < 3
    ? `${window.location.origin}/zoom${slot + 1}_event`
    : `${window.location.origin}/zoom/${slot}/event`

const closeModal = (refresh: boolean) => emit('close', refresh)

const validateForm = async () => {
    const validations = await Promise.all([
        labelRef.value?.validate(false),
        emailRef.value?.validate(false),
        accountIdRef.value?.validate(false),
        clientIdRef.value?.validate(false),
    ])
    return validations.every(result => result?.valid)
}

const apiPayload = () => ({
    ...params,
    host_password: params.host_password || null,
    client_secret: params.client_secret || null,
    webhook_secret: params.webhook_secret || null,
})

const testConnection = async () => {
    if (!props.editTarget || !(await validateForm())) return

    testing.value = true
    testResult.value = null
    try {
        const result = await api.post(
            `/admin/zoom-accounts/${props.editTarget.id}/test`,
            apiPayload(),
            { silent: true },
        ) as {
            message?: string
            meeting?: { host_email?: string, total_records?: number }
        }
        const meetingDetails = result.meeting?.host_email
            ? `ホスト：${result.meeting.host_email} / 予定ミーティング：${result.meeting.total_records ?? 0}件`
            : ''
        testResult.value = {
            success: true,
            message: `${result.message ?? 'Zoom APIへの接続に成功しました。'}${meetingDetails ? `（${meetingDetails}）` : ''}`,
        }
    } catch (error: unknown) {
        const responseMessage = (error as {
            response?: { data?: { message?: string } }
        })?.response?.data?.message
        testResult.value = {
            success: false,
            message: responseMessage ?? 'Zoom APIからエラー内容を取得できませんでした。',
        }
    } finally {
        testing.value = false
    }
}

const save = async () => {
    if (!(await validateForm())) return

    loading.value = true
    try {
        const path = props.editTarget
            ? `/admin/zoom-accounts/${props.editTarget.id}`
            : '/admin/zoom-accounts'
        const payload = apiPayload()
        if (props.editTarget) {
            await api.put(path, payload, { toast: 'Web会議設定を保存しました' })
        } else {
            await api.post(path, payload, { toast: 'Web会議設定を追加しました' })
        }
        closeModal(true)
    } finally {
        loading.value = false
    }
}
</script>

<style scoped>
.web-meeting-modal__section {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.web-meeting-modal__heading {
    font-size: 14px;
    font-weight: 600;
}

.web-meeting-modal__url {
    padding: 10px;
    background: var(--bg3);
    font-size: 12px;
    line-height: 1.6;
}

.web-meeting-modal__url span {
    user-select: all;
}

.web-meeting-modal__test-result {
    margin-top: 10px;
    padding: 10px;
    font-size: 12px;
    line-height: 1.6;
    overflow-wrap: anywhere;
}

.web-meeting-modal__test-result.is-success {
    color: #287a3e;
    background: #edf8f0;
}

.web-meeting-modal__test-result.is-error {
    color: #b42318;
    background: #fff1f0;
}

.web-meeting-modal__switch {
    width: 84px;
    margin-top: 12px;
}

.web-meeting-modal__switch label span::after {
    content: '無効';
    padding: 0 0 0 28px;
}

.web-meeting-modal__switch input[type='checkbox']:checked + label span::after {
    content: '有効';
    padding: 0 32px 0 10px;
}

.web-meeting-modal__switch input[type='checkbox']:checked ~ label .switch-toggle {
    transform: translateX(50px);
}
</style>
