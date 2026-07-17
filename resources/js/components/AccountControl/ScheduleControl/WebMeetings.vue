<template>
    <div class="admin-window">
        <FloatButton title="Web会議設定を追加" @action="openModal(null)">
            <template #icon>
                <AddIcon size="15" fill="black" />
            </template>
        </FloatButton>

        <Transition name="modalFade">
            <div v-if="fetch === 0" class="control-loader">
                <div class="spinner-mini" style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
            </div>
        </Transition>

        <div v-if="accounts.length" class="web-meeting-list">
            <div
                v-for="account in accounts"
                :key="account.id"
                class="web-meeting-box mobile:bg-[var(--bg3)]"
            >
                <div class="web-meeting-box__header">
                    <div>
                        <p class="web-meeting-box__title">{{ account.label }}</p>
                        <p class="web-meeting-box__host">
                            <span>ホスト</span>
                            {{ account.host_email }}
                        </p>
                    </div>

                    <ItemMenu :items="[
                        { title: '編集する', action: () => openModal(account) },
                        { title: '接続確認する', action: () => testConnection(account) },
                        { title: '削除する', action: () => remove(account) },
                    ]" />
                </div>

                <div class="web-meeting-box__status">
                    <span :class="{ configured: account.active }">
                        {{ account.active ? '利用中' : '停止中' }}
                    </span>
                    <span :class="{ configured: account.api_configured }">
                        API認証 {{ account.api_configured ? '設定済み' : '未設定' }}
                    </span>
                    <span :class="{ configured: account.webhook_secret_configured }">
                        Webhook {{ account.webhook_secret_configured ? '設定済み' : '未設定' }}
                    </span>
                </div>

                <div class="web-meeting-box__webhook">
                    <p>Webhook URL</p>
                    <code class="select-all">{{ webhookUrl(account.slot) }}</code>
                </div>
            </div>
        </div>

        <div v-else-if="fetch > 0" class="web-meeting-empty">
            現在データはありません
        </div>

        <Transition name="modalFade">
            <WebMeetingCreate
                v-if="modalOpen"
                :edit-target="editTarget"
                @close="closeModal"
            />
        </Transition>
    </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import FloatButton from '@/components/Global/FloatButton.vue'
import ItemMenu from '@/components/Global/ItemMenu.vue'
import AddIcon from '@/components/Form/AddIcon.vue'
import WebMeetingCreate from './WebMeetingCreate.vue'
import { useApi } from '@/composables/api'
import type { ZoomAccountSetting } from '@/interface/zoomAccountInterface'

const api = useApi()
const fetch = ref(0)
const accounts = ref<ZoomAccountSetting[]>([])
const modalOpen = ref(false)
const editTarget = ref<ZoomAccountSetting | null>(null)

const getAccounts = async () => {
    accounts.value = await api.get('/admin/zoom-accounts') as ZoomAccountSetting[]
    fetch.value++
}

const openModal = (account: ZoomAccountSetting | null) => {
    editTarget.value = account
    modalOpen.value = true
}

const closeModal = (refresh: boolean) => {
    modalOpen.value = false
    editTarget.value = null
    if (refresh) getAccounts()
}

const testConnection = async (account: ZoomAccountSetting) => {
    await api.post(`/admin/zoom-accounts/${account.id}/test`, {}, {
        toast: 'Zoom APIへの接続に成功しました',
    })
}

const remove = async (account: ZoomAccountSetting) => {
    const result = await api.del(`/admin/zoom-accounts/${account.id}`, {}, {
        ask: `${account.label}を削除しますか？`,
        toast: 'Web会議設定を削除しました',
    })
    if (result !== null) getAccounts()
}

const webhookUrl = (slot: number) => slot < 3
    ? `${window.location.origin}/zoom${slot + 1}_event`
    : `${window.location.origin}/zoom/${slot}/event`

onMounted(getAccounts)
</script>

<style scoped>
.web-meeting-list {
    height: calc(100% - 40px);
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 20px;
    overflow: hidden auto;
}

.web-meeting-box {
    position: relative;
    padding: 22px;
    background: var(--background-color);
    font-size: 14px;
}

.web-meeting-box__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 20px;
}

.web-meeting-box__title {
    font-size: 16px;
    font-weight: 600;
    line-height: 1.4;
}

.web-meeting-box__host {
    margin-top: 7px;
    color: var(--text-color);
    line-height: 1.5;
    overflow-wrap: anywhere;
}

.web-meeting-box__host span {
    margin-right: 8px;
    color: gray;
    font-size: 12px;
}

.web-meeting-box__status {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 18px;
}

.web-meeting-box__status span {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 9px;
    background: var(--bg3);
    color: gray;
    font-size: 12px;
    line-height: 1.4;
}

.web-meeting-box__status span::before {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #a1a1aa;
    content: '';
}

.web-meeting-box__status span.configured {
    background: #edf8f0;
    color: #166534;
}

.web-meeting-box__status span.configured::before {
    background: #22a447;
}

.web-meeting-box__webhook {
    margin-top: 18px;
    padding-top: 14px;
    border-top: 1px solid var(--bg3);
}

.web-meeting-box__webhook p {
    margin-bottom: 5px;
    color: gray;
    font-size: 11px;
}

.web-meeting-box__webhook code {
    display: block;
    font-family: inherit;
    font-size: 12px;
    line-height: 1.5;
    overflow-wrap: anywhere;
}

.web-meeting-empty {
    height: 100%;
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: gray;
}
</style>
