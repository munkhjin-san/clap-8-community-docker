<template>
    <div class="settings-panel">
        <p class="panel-hint">通知が届かない場合は、お使いの端末に合わせた設定をご確認ください。</p>
        <div class="settings-card">
            <button class="settings-row" @click="guideStep = 1">
                <span class="settings-row__icon" v-html="icons.pc"></span>
                <span class="settings-row__body"><span class="settings-row__label">PC通知設定案内</span></span>
                <svg class="settings-row__chev" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg>
            </button>
            <button class="settings-row" @click="guideStep = 2">
                <span class="settings-row__icon" v-html="icons.android"></span>
                <span class="settings-row__body"><span class="settings-row__label">Android通知設定案内</span></span>
                <svg class="settings-row__chev" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg>
            </button>
            <button class="settings-row" @click="guideStep = 3">
                <span class="settings-row__icon" v-html="icons.apple"></span>
                <span class="settings-row__body"><span class="settings-row__label">iOS通知設定案内</span></span>
                <svg class="settings-row__chev" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg>
            </button>
        </div>
        <NotificationGuide @close="guideStep = 0" :guideStep="guideStep" :guideTitle="guideTitle"/>
    </div>
</template>

<script setup>
import NotificationGuide from '../NotificationGuide.vue'
import { computed, ref } from 'vue'
import { icons } from '../icons'

    const guideStep = ref(0)
    const guideTitle = computed(() => {
        switch (guideStep.value) {
            case 1: return 'PC通知設定案内'
            case 2: return 'Android通知設定案内'
            case 3: return 'IOS通知設定案内'
            default: return ''
        }
    })
</script>

<style lang="scss" scoped>
    /* Row styling mirrors the menu rows for the in-panel guide list. */
    .settings-row {
        width: 100%;
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 14px 16px;
        background: transparent;
        border: none;
        border-top: 1px solid var(--panel-separate);
        color: var(--primary-color);
        cursor: pointer;
        text-align: left;
        font-size: 15px;
        transition: background .12s ease;
    }
    .settings-row:first-child { border-top: none; }
    .settings-row:hover { background: color-mix(in srgb, var(--primary-color) 8%, transparent); }
    .settings-row__icon {
        flex: none;
        width: 38px;
        height: 38px;
        border-radius: 6px;
        background: var(--bg3);
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .settings-row__icon :deep(svg) { width: 20px; height: 20px; }
    .settings-row__body { display: flex; flex-direction: column; min-width: 0; flex: 1; }
    .settings-row__chev { flex: none; color: var(--sub-color); opacity: .7; }
</style>
