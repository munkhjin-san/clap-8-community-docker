<template>
    <div class="sp-wrapper" @click="keyListView = false">
        <!-- Header -->
        <header class="sp-header">
            <div class="sp-header-left">
                <button
                    v-if="isMobile && !isParentRoute"
                    class="sp-back-btn"
                    @click.stop="router.push({ name: 'dashboard-support' })"
                >
                    <Back />
                </button>
                <span class="sp-header-icon" v-if="selectedRoute == 'dashboard-support' || !isMobile">
                    <component :is="SupportHomeIcon" />
                </span>
                <h1 v-if="selectedRoute == 'dashboard-support' || !isMobile" class="sp-header-title">サポートデスク</h1>
                <span class="mobile text-[14px]" v-if="isMobile && !isParentRoute && selectedRoute">{{ selectedNavItem?.label }}</span>
            </div>
            <button v-if="selectedRoute == 'dashboard-support' || !isMobile" @click.stop="router.push({ name: 'dashboard' })">
                <CloseIcon fill="var(--primary-color)" size="12" />
            </button>
        </header>

        <!-- Body -->
        <div class="sp-body">

            <!-- Sidebar / Category nav -->
            <nav
                v-show="isMobile ? isParentRoute : !isParentRoute"
                :class="['sp-nav', { 'sp-nav--mobile': isMobile }]"
            >
                <router-link
                    v-for="item in visibleNavItems"
                    :key="item.name"
                    :to="{ name: item.name }"
                    :class="[
                        'sp-nav-item',
                        {
                            'sp-nav-item--active': selectedRoute.includes(item.name),
                            'sp-nav-item--attention': item.attention,
                        }
                    ]"
                    @click.stop
                >
                    <span class="sp-nav-item-icon">
                        <component :is="item.icon" />
                    </span>
                    <span class="sp-nav-item-label">{{ item.label }}</span>
                    <Badge style="position:unset" v-if="item.name === 'system_updates' && collection.systemUpdates.length > 0" :count="collection.systemUpdates.length" />
                    <svg v-if="isMobile" class="sp-nav-chevron" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </router-link>
            </nav>

            <!-- Content area -->
            <div
                v-show="!isMobile || !isParentRoute"
                class="sp-content"
                :class="{ 'sp-content--welcome': isParentRoute }"
            >
                <div v-if="isParentRoute" class="support-welcome">
                    <section class="support-priority">
                        <button
                            type="button"
                            class="support-priority-card support-priority-card--emergency"
                            @click="router.push({ name: 'emergency_contact', query: { type: 'emergency' } })"
                        >
                            <span class="support-priority-label">緊急時</span>
                            <h3>緊急連絡はこちら</h3>
                            <small>至急共有が必要な内容を、経営管理本部・取締役へ通知します。</small>
                            <span class="support-priority-action">連絡する →</span>
                        </button>
                        <button
                            type="button"
                            class="support-priority-card support-priority-card--incident"
                            @click="router.push({ name: 'emergency_contact', query: { type: 'incident' } })"
                        >
                            <span class="support-priority-label">事故・トラブル</span>
                            <h3>インシデント報告はこちら</h3>
                            <small>事故、紛失、ハラスメントなどの報告を登録できます。</small>
                            <span class="support-priority-action">報告する →</span>
                        </button>
                    </section>

                    <section class="support-welcome-section">
                        <div class="support-welcome-section-head">
                            <h3>サポートメニュー</h3>
                            <span>確認・相談したい内容を選んでください</span>
                        </div>
                        <div class="support-welcome-grid">
                            <button
                                v-for="item in welcomeItems"
                                :key="item.name"
                                class="support-welcome-card"
                                @click="router.push({ name: item.name })"
                            >
                                <span class="support-welcome-card-icon">
                                    <component :is="item.icon" />
                                </span>
                                <span class="support-welcome-card-body">
                                    <h3>{{ item.label }}</h3>
                                    <small>{{ item.description }}</small>
                                </span>
                                <span class="support-welcome-card-arrow">→</span>
                            </button>
                        </div>
                    </section>
                </div>
                <router-view v-slot="{ Component }">
                    <transition name="supportShift" mode="out-in">
                        <component
                            :is="Component"
                            :qaList="qaList"
                            :tagList="tagList"
                            @setChatBoxWindow="val => chatBoxWindow = val"
                            @setKeyWord="setKeyWord"
                            @refresh="getSupportData"
                        />
                    </transition>
                </router-view>
            </div>
        </div>
    </div>
</template>
<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import { useResponsive } from '@/store/responsive';
import { useAuthUserStore } from '@/store/auth';
import { useApi } from '@/composables/api';
import CloseIcon from '../Form/CloseIcon.vue';
import { useRouter } from 'vue-router';
import Back from '../Icons/Back.vue';
import { useDashboardStore } from '@/store/dashboard.ts';
import Badge from '../Global/Badge.vue';
import SupportHomeIcon from '../Icons/SupportHomeIcon.vue';
import SupportEmergencyIcon from '../Icons/SupportEmergencyIcon.vue';
import SupportFaqIcon from '../Icons/SupportFaqIcon.vue';
import SupportRegulationIcon from '../Icons/SupportRegulationIcon.vue';
import SupportMailIcon from '../Icons/SupportMailIcon.vue';
import SupportInboxIcon from '../Icons/SupportInboxIcon.vue';
import SupportPhoneIcon from '../Icons/SupportPhoneIcon.vue';
import SupportAiIcon from '../Icons/SupportAiIcon.vue';
import SupportSystemUpdateIcon from '../Icons/SupportSystemUpdateIcon.vue';

    const route = useRoute()
    const router = useRouter()
    const responsive = useResponsive()
    const auth = useAuthUserStore()
    const qanda_info = ref([])
    const tag_list = ref([])
    const key_word_list = ref([])
    const keyListView = ref(false)
    const searchWord = ref('')
    const viewTrayUsers = [610, 516, 517, 519, 518, 526, 494, 604, 765]
    const api = useApi()
    const chatBoxWindow = ref(false)
    const { collection } = useDashboardStore()

    const isMobile = computed(() => responsive.mobile)
    const selectedRoute = computed(() => route.name)
    const isParentRoute = computed(() => selectedRoute.value === 'dashboard-support')


    const navItems = [
        {
            name: 'dashboard-support',
            label: 'サポートホーム',
            icon: SupportHomeIcon,
        },
        {
            name: 'ai_chat',
            label: 'AIチャット',
            icon: SupportAiIcon,
        },
        {
            name: 'emergency_contact',
            label: '緊急連絡・インシデント報告',
            attention: true,
            icon: SupportEmergencyIcon,
        },
        {
            name: 'faq',
            label: 'よくある質問',
            icon: SupportFaqIcon,
        },
        {
            name: 'regulations',
            label: '就業規則及び各種の規定',
            icon: SupportRegulationIcon,
        },
        {
            name: 'email_consult',
            label: 'メール相談',
            icon: SupportMailIcon,
        },
        {
            name: 'email_inbox',
            label: 'メール相談（受信BOX）',
            adminOnly: true,
            icon: SupportInboxIcon,
        },
        {
            name: 'phone_consult',
            label: '電話相談',
            icon: SupportPhoneIcon,
        },
        {
            name: 'system_updates',
            label: 'システム保守・更新情報',
            icon: SupportSystemUpdateIcon,
        },
    ]

    const itemDescriptions = {
        emergency_contact: '緊急連絡やインシデント報告を送信できます。',
        faq: '操作や手続きでよくある質問を検索できます。',
        regulations: '就業規則、会社ルール、各種規定を確認できます。',
        email_consult: '文章で残したい相談や確認事項を送信できます。',
        email_inbox: '管理者向けのメール相談受付状況を確認できます。',
        phone_consult: '電話相談窓口や外部相談先を確認できます。',
        ai_chat: '規定やFAQをもとにAIへ質問できます。',
        system_updates: 'システム保守、更新、障害などのお知らせを確認できます。',
    }

    const selectedNavItem = computed(() => navItems.find(item => item.name === selectedRoute.value))
    const visibleNavItems = computed(() => navItems.filter(item => {
        if (isMobile.value && item.name === 'dashboard-support') return false
        return !item.adminOnly || auth.isAdmin
    }))
    const welcomeItems = computed(() => visibleNavItems.value
        .filter(item => item.name !== 'dashboard-support')
        .map(item => ({
            ...item,
            description: itemDescriptions[item.name] || 'サポート項目を開きます。',
        })))

    const tagList = computed(() => tag_list.value)
    const qaList = computed(() => {
        if (!searchWord.value) return qanda_info.value
        return qanda_info.value.filter(ob => {
            const area = ob.question + ob.answer + ob.content + ob.tag_text
            return area.includes(searchWord.value)
        })
    })
    const setKeyWord = (text) => {
        searchWord.value = text
        keyListView.value = false
    }
    const getSupportData = async () => {
        const data = await api.post('/support_record_list')
        qanda_info.value = data.record_list
        tag_list.value = data.tag_list
        tag_list.value.unshift({ id: 0, text: '全て' })
        key_word_list.value = data.key_word_list
    }
    onMounted(getSupportData)

</script>
<style lang="scss">
/* ── Transition ──────────────────────────────────────── */
.supportShift-enter-active,
.supportShift-leave-active {
    transition: transform 0.2s ease, opacity 0.2s ease;
}
.supportShift-enter-from,
.supportShift-leave-to {
    transform: translateX(40px);
    opacity: 0;
}

/* ── Wrapper ─────────────────────────────────────────── */
.sp-wrapper {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: var(--background-color);
    z-index: 35;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

/* ── Header ──────────────────────────────────────────── */
.sp-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 16px 0 20px;
    height: 60px;
    min-height: 60px;
    flex-shrink: 0;
    background: var(--background-color);
    border-bottom: 1px solid var(--bg3);
}

.sp-header-left {
    display: flex;
    align-items: center;
    gap: 10px;
    color: var(--primary-color);
}

.sp-back-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 34px;
    height: 34px;
    border-radius: 8px;
    color: var(--primary-color);
    cursor: pointer;
    transition: background 0.15s;
    margin-left: -4px;
    flex-shrink: 0;
}
.sp-back-btn:hover { background: var(--bg3); }

.sp-header-icon {
    display: flex;
    align-items: center;
    color: var(--primary-color);
    opacity: 0.55;
}

.sp-header-title {
    font-size: 15px;
    font-weight: 600;
    color: var(--primary-color);
    margin: 0;
    letter-spacing: 0.01em;
}

.sp-close-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 38px;
    height: 38px;
    border-radius: 8px;
    color: var(--primary-color);
    cursor: pointer;
    transition: background 0.15s, opacity 0.15s;
    opacity: 0.45;
    flex-shrink: 0;
}
.sp-close-btn:hover { background: var(--bg3); opacity: 1; }

/* ── Body ────────────────────────────────────────────── */
.sp-body {
    display: flex;
    flex: 1;
    overflow: hidden;
}

/* ── Sidebar nav ─────────────────────────────────────── */
.sp-nav {
    width: 280px;
    min-width: 280px;
    background: var(--bg3);
    border-right: 1px solid rgba(128, 128, 128, 0.12);
    display: flex;
    flex-direction: column;
    padding: 20px 0 16px;
    overflow-y: auto;
    flex-shrink: 0;
}

.sp-nav-label {
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: var(--primary-color);
    opacity: 0.35;
    padding: 0 18px 10px;
}
.sp-nav-item-icon, .sp-header-icon {
    fill: var(--primary-color);
}
.sp-nav-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 14px;
    margin: 0px 14px;
    border-radius: 5px;
    border-left: 3px solid transparent;
    color: var(--primary-color);
    text-decoration: none;
    font-size: 13.5px;
    font-weight: 400;
    cursor: pointer;
    transition: background 0.15s, border-color 0.15s, font-weight 0.1s;
    line-height: 1.35;
    white-space: nowrap;
}
.sp-nav-item:hover {
    background: rgba(128, 128, 128, 0.1);
    text-decoration: none;
    color: var(--primary-color);
}
.sp-nav-item--active {
    background: var(--background-color);
    font-weight: 600;
}
.sp-nav-item--active:hover { background: var(--background-color); }




.sp-nav-item-icon {
    display: flex;
    align-items: center;
    flex-shrink: 0;
    opacity: 0.65;
    transition: opacity 0.15s;
}
.sp-nav-item--active .sp-nav-item-icon { opacity: 1; }

.sp-nav-item-label { flex: 1; }

.sp-nav-chevron {
    flex-shrink: 0;
    opacity: 0.3;
}

/* ── Content area ────────────────────────────────────── */
.sp-content {
    flex: 1;
    min-width: 0;
    overflow-y: auto;
    overflow-x: hidden;
    background: var(--background-color);
    color: var(--primary-color);
}

.sp-content--welcome {
    background: var(--background-color);
}

/* ── Root welcome ───────────────────────────────────── */
.support-welcome {
    width: min(100%, calc(100vw - 120px), 1020px);
    box-sizing: border-box;
    margin: 0 auto;
    padding: 28px clamp(16px, 3vw, 32px) 36px;
    min-height: 100%;
}

.support-priority {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 30px;
    margin-bottom: 34px;
}

.support-priority-card {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 7px;
    min-height: 112px;
    padding: 16px 18px;
    text-align: left;
    color: var(--primary-color);
    border: 1px solid rgba(128, 128, 128, 0.18);
    background: var(--bg3);
    transition: background 0.15s, border-color 0.15s;
}

.support-priority-card:hover {
    background: rgba(128, 128, 128, 0.1);
    border-color: rgba(128, 128, 128, 0.3);
}

.support-priority-card--emergency {
    background: rgba(249, 115, 22, 0.07);
}

.support-priority-card--incident {
    background: rgba(15, 118, 110, 0.07);
}

.support-priority-card--emergency .support-priority-label {
    color: #c2410c;
}

.support-priority-card--incident .support-priority-label {
    color: #0f766e;
}

.support-priority-label {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.04em;
    color: gray;
}

.support-priority-card strong {
    font-size: 17px;
    font-weight: 700;
}

.support-priority-card small {
    max-width: 520px;
    color: var(--primary-color);
    font-size: 12px;
    line-height: 1.55;
    opacity: 0.72;
}

.support-priority-action {
    margin-top: auto;
    font-size: 12.5px;
    font-weight: 700;
    color: var(--primary-color);
}

.support-welcome-section {
    margin-top: 0;
}

.support-welcome-section-head {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 16px;
}

.support-welcome-section-head h3 {
    font-size: 16px;
    font-weight: 700;
    margin: 0;
}

.support-welcome-section-head span {
    font-size: 12px;
    color: gray;
}

.support-welcome-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 30px;
}

.support-welcome-card {
    display: flex;
    align-items: center;
    gap: 13px;
    min-height: 84px;
    padding: 16px 18px;
    text-align: left;
    background: var(--background-color);
    border: 1px solid rgba(128, 128, 128, 0.18);
    color: var(--primary-color);
    transition: border-color 0.15s, background 0.15s;
}

.support-welcome-card:hover {
    background: rgba(128, 128, 128, 0.08);
    border-color: rgba(128, 128, 128, 0.34);
}

.support-welcome-card-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    min-width: 32px;
    height: 32px;
    color: var(--primary-color);
    fill: var(--primary-color);
    opacity: 0.72;
}

.support-welcome-card-body {
    display: flex;
    flex-direction: column;
    gap: 7px;
    min-width: 0;
    flex: 1;
}

.support-welcome-card-body strong {
    font-size: 14px;
    font-weight: 700;
}

.support-welcome-card-body small {
    font-size: 12px;
    line-height: 1.6;
    color: gray;
}

.support-welcome-card-arrow {
    color: gray;
    font-size: 16px;
}

/* ── Mobile nav override ─────────────────────────────── */
.sp-nav--mobile {
    width: 100%;
    min-width: unset;
    border-right: none;
    padding: 16px 0;
}
.sp-nav--mobile .sp-nav-label {
    padding: 0 20px 12px;
}
.sp-nav--mobile .sp-nav-item {
    margin: 6px 12px;
    padding: 14px 16px;
    font-size: 14.5px;
    border-left: none;
    border-radius: 10px;
    background: var(--background-color);
    border: 1px solid rgba(128, 128, 128, 0.1);
}
.sp-nav--mobile .sp-nav-item:hover {
    background: var(--background-color);
}
.sp-nav--mobile .sp-nav-item--active {
    border-left: none;
    background: var(--background-color);
}
.sp-nav--mobile .sp-nav-item-icon { opacity: 0.7; }
.sp-nav--mobile .sp-nav-item--active .sp-nav-item-icon { opacity: 1; }
.sp-nav--mobile .sp-nav-chevron { opacity: 0.25; }

/* ── Shared child-view helpers (used by child components) */
.answerBox {
    white-space: break-spaces;
    line-height: 1.7;
}
.qandaContent {
    padding: 20px;
    line-height: 1.7;
    white-space: break-spaces;
}
.qandaContent:hover {
    cursor: pointer;
    background-color: var(--bg3);
}
.support-tag-selector {
    display: flex;
    flex-wrap: wrap;
}
.support-tag {
    padding: 15px;
    cursor: pointer;
    transition: background-color 0.2s;
    background-color: var(--background-color);
}
.tag-selected { background-color: var(--bg3); }
.support-content-inner {
    padding: 20px;
    background: var(--background-color);
    margin-right: 20px;
}
.support-title {
    font-size: 20px;
    margin-bottom: 20px;
    margin-top: 10px;
}
@media screen and (max-width: 959px) {
    .support-title { padding: 0 20px; }
    .support-content-inner { margin: 0; }
    .support-welcome {
        width: 100%;
        padding: 18px;
    }
    .support-priority {
        grid-template-columns: 1fr;
    }
    .support-welcome-section-head {
        display: block;
    }
}
</style>
