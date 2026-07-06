<template>
    <div v-if="user" class="settings-root" :class="{ 'settings-root--mobile': responsive.mobile }">

        <!-- ===================== PC: master + detail ===================== -->
        <template v-if="!responsive.mobile">
            <SettingsMenu class="settings-col settings-col--master"/>
            <div class="settings-col settings-col--detail">
                <div v-if="!hasDetail" class="settings-empty">
                    <Gear :size="48" class="settings-empty__icon"/>
                    <p>左の一覧から項目を選択してください</p>
                </div>
                <div v-else class="settings-scroll">
                    <div class="settings-shell settings-shell--detail">
                        <div class="detail-card">
                            <router-view v-slot="{ Component }">
                                <transition name="detail-swap" mode="out-in">
                                    <component :is="Component" :key="route.name"/>
                                </transition>
                            </router-view>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <!-- ===================== Mobile: sliding panes ===================== -->
        <template v-else>
            <transition :name="paneTransition">
                <SettingsMenu v-if="!hasDetail" key="menu" class="settings-pane"/>
                <div v-else key="detail" class="settings-pane settings-col--detail">
                    <header class="settings-bar">
                        <button class="settings-back" @click="back" aria-label="戻る">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
                        </button>
                        <h1 class="settings-bar__title">{{ sectionTitle }}</h1>
                    </header>
                    <div class="settings-scroll">
                        <div class="settings-shell">
                            <router-view/>
                        </div>
                    </div>
                </div>
            </transition>
        </template>
    </div>
</template>

<script setup>
import './settings-shared.css'
import SettingsMenu from './SettingsMenu.vue'
import Gear from '../Icons/Gear.vue'
import { computed, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthUserStore } from '@/store/auth'
import { useResponsive } from '@/store/responsive'

    const auth = useAuthUserStore()
    const responsive = useResponsive()
    const route = useRoute()
    const router = useRouter()

    const user = computed(() => auth.user)
    const hasDetail = computed(() => route.name !== 'settings')

    const TITLES = {
        'settings-password': 'パスワードの変更',
        'settings-two-factor': '二段階認証',
        'settings-email-otp': 'メール二段階認証',
        'settings-passkeys': 'パスキー',
        'settings-color': 'カラー設定',
        'settings-theme': 'テーマ設定',
        'settings-notification': '通知設定',
        'settings-signature': 'マイサイン',
        'settings-schedule': 'スケジュール設定',
        'settings-notification-guide': '通知設定案内',
        'settings-footer-menu': 'フッターメニュー表示',
    }
    const sectionTitle = computed(() => TITLES[route.name] || '設定')

    const back = () => router.push({ name: 'settings' })

    // Slide direction: forward (menu → detail) pushes left, back pops right.
    const navDir = ref('forward')
    watch(hasDetail, (now) => { navDir.value = now ? 'forward' : 'back' })
    const paneTransition = computed(() => navDir.value === 'forward' ? 'pane-fwd' : 'pane-back')
</script>

<style lang="scss" scoped>
    .settings-root {
        width: 100%;
        height: 100%;
        display: flex;
        background: var(--bg3);
        color: var(--primary-color);
        overflow: hidden;
    }

    .settings-col { display: flex; flex-direction: column; min-height: 0; }
    .settings-col--master {
        width: 360px;
        flex: none;
        border-right: 1px solid var(--normalBorder);
    }
    .settings-col--detail { flex: 1; min-width: 0; }

    /* Detail content sits in a contained, left-aligned column — it does not
       stretch to fill the whole pane. */
    .settings-shell--detail {
        max-width: 680px;
        margin: 0;
        padding: 40px 48px 56px;
    }

    /* The whole right panel sits in one wide rounded card, matching the
       left-side item cards. Nested cards inside panels are flattened so we
       never get a card-in-card. */
    .detail-card {
        background: var(--background-color);
        border: 1px solid var(--normalBorder);
        border-radius: 6px;
        padding: 24px 26px;
    }
    .detail-card :deep(.settings-card) {
        background: transparent;
        border: none;
        border-radius: 0;
    }
    .detail-card :deep(.settings-card--pad) { padding: 0; }

    /* Empty state (PC, nothing selected) — centered in the whole pane */
    .settings-empty {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 16px;
        color: var(--sub-color);
        text-align: center;
    }
    .settings-empty :deep(.settings-empty__icon) { opacity: .45; fill: var(--sub-color); }
    .settings-empty p { font-size: 14px; margin: 0; }

    /* PC content swap — gentle horizontal slide + fade */
    .detail-swap-enter-active { transition: opacity .24s ease, transform .24s cubic-bezier(.22,.61,.36,1); }
    .detail-swap-leave-active { transition: opacity .14s ease, transform .14s ease; }
    .detail-swap-enter-from { opacity: 0; transform: translateX(18px); }
    .detail-swap-leave-to { opacity: 0; transform: translateX(-10px); }

    /* ===== Mobile sliding panes ===== */
    .settings-root--mobile { position: relative; }
    .settings-pane {
        position: absolute;
        inset: 0;
        background: var(--bg2);
    }
    .pane-fwd-enter-active, .pane-fwd-leave-active,
    .pane-back-enter-active, .pane-back-leave-active {
        transition: transform .26s ease;
        will-change: transform;
    }
    /* forward: incoming detail slides in from right, outgoing menu drifts left */
    .pane-fwd-enter-from { transform: translateX(100%); }
    .pane-fwd-leave-to { transform: translateX(-28%); }
    /* back: incoming menu slides in from left, outgoing detail slides out right */
    .pane-back-enter-from { transform: translateX(-28%); }
    .pane-back-leave-to { transform: translateX(100%); }
</style>
