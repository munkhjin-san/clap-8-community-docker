<template>
    <div class="settings-panel">
        <!-- Enabled -->
        <template v-if="permission === 'granted'">
            <div class="status-banner status-banner--on">
                <span class="status-banner__dot"></span>
                <div>
                    <p class="status-banner__title">プッシュ通知は有効です</p>
                    <p class="status-banner__desc">この端末・ブラウザに通知が届きます。再登録が必要な場合は下のボタンから更新できます。</p>
                </div>
            </div>
            <div class="panel-actions panel-actions--left">
                <LoaderButton @triggered="enablePush" :loading="loading" :content="'通知を再登録'"/>
            </div>
        </template>

        <!-- Blocked -->
        <template v-else-if="permission === 'denied'">
            <p class="tfa-error">この端末・ブラウザでは通知がブロックされています。</p>
            <p class="panel-hint">ブラウザのサイト設定で通知を「許可」に変更すると有効にできます。設定手順は「通知設定案内」をご確認ください。</p>
            <div class="panel-actions panel-actions--left">
                <button class="settings-btn-secondary" @click="goGuide">通知設定案内を見る</button>
            </div>
        </template>

        <!-- Unsupported -->
        <template v-else-if="!supported">
            <p class="tfa-error">このブラウザ／デバイスはプッシュ通知に対応していません。</p>
        </template>

        <!-- Not enabled yet -->
        <template v-else>
            <p class="panel-hint">プッシュ通知を有効にすると、メンションやタスクの更新などをこの端末でリアルタイムに受け取れます。</p>
            <div class="panel-actions panel-actions--left">
                <LoaderButton @triggered="enablePush" :loading="loading" :content="'通知を有効にする'"/>
            </div>
        </template>
    </div>
</template>

<script setup>
import LoaderButton from '../../Global/LoaderButton.vue'
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useDialog } from '@/composables/dialog'
import { initPush } from '@/utils/push'

    const router = useRouter()
    const { toast } = useDialog()

    const supported = ref(true)
    const permission = ref('default') // 'granted' | 'denied' | 'default'
    const loading = ref(false)

    const readPermission = () => {
        supported.value = typeof Notification !== 'undefined' && 'serviceWorker' in navigator && 'PushManager' in window
        permission.value = (typeof Notification !== 'undefined') ? Notification.permission : 'default'
    }

    onMounted(readPermission)

    const enablePush = async () => {
        loading.value = true
        try {
            const data = await initPush()
            readPermission()
            if (data.ok) toast('通知を有効にしました。')
            else toast(data.reason || '通知を有効にできませんでした。')
        } finally {
            loading.value = false
        }
    }
    const goGuide = () => router.push({ name: 'settings-notification-guide' })
</script>
