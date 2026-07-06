<template>
    <div class="settings-menu">
        <header v-if="responsive.mobile" class="settings-bar">
            <HamBurger class="settings-bar__ham"/>
            <h1 class="settings-bar__title">設定</h1>
        </header>

        <div class="settings-scroll">
            <div class="settings-shell">
                <!-- profile (mobile only — PC shows it elsewhere in the chrome) -->
                <div v-if="responsive.mobile" class="profile-card">
                    <UserPanel :user="user" :size="30" withName disableInstant class="profile-card__user"/>
                </div>

                <section v-for="group in menuGroups" :key="group.title || 'misc'" class="settings-group">
                    <p v-if="group.title" class="settings-group__title">{{ group.title }}</p>
                    <div class="settings-card">
                        <button
                            v-for="item in group.items"
                            :key="item.key"
                            class="settings-row"
                            :class="{ 'settings-row--danger': item.danger, 'settings-row--active': item.to && isActive(item.to) }"
                            @click="runItem(item)"
                        >
                            <span class="settings-row__icon" v-html="item.icon"></span>
                            <span class="settings-row__body">
                                <span class="settings-row__label">{{ item.label }}</span>
                                <span v-if="item.desc" class="settings-row__desc">{{ item.desc }}</span>
                            </span>

                            <span v-if="item.swatch" class="settings-row__swatch" :style="{ background: item.swatch }"></span>
                            <span
                                v-else-if="item.badge"
                                class="settings-pill"
                                :class="item.badgeOn ? 'settings-pill--on' : 'settings-pill--muted'"
                            >{{ item.badge }}</span>

                            <svg v-if="!item.danger" class="settings-row__chev" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg>
                        </button>
                    </div>
                </section>
            </div>
        </div>
    </div>
</template>

<script setup>
import colors from '../../../assets/colors.json'
import UserPanel from '../Global/UserPanel.vue'
import HamBurger from '../Global/HamBurger.vue'
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthUserStore } from '@/store/auth'
import { useResponsive } from '@/store/responsive'
import { useApi } from '@/composables/api'
import { useDialog } from '@/composables/dialog'
import { icons } from './icons'

    const auth = useAuthUserStore()
    const responsive = useResponsive()
    const route = useRoute()
    const router = useRouter()
    const api = useApi()
    const { ask } = useDialog()

    const user = computed(() => auth.user)
    const permission = ref('未許可')

    const twoFaEnabled = computed(() => !!(auth.user && auth.user.two_factor_confirmed_at))
    const emailOtpEnabled = computed(() => !!(auth.user && auth.user.email_otp_enabled_at))
    const currentColor = computed(() => colors.find(c => c.id == (auth.user ? auth.user.color : 0)))
    const colorSwatch = computed(() => currentColor.value ? currentColor.value.light : 'var(--bg2)')
    const themeLabel = computed(() => {
        const v = localStorage.getItem('dark')
        if (v === '1') return 'ダーク'
        if (v === '2') return 'ライト'
        return 'ブラウザと同じ'
    })

    const go = (name) => router.push({ name })
    const isActive = (name) => route.name === name
    const runItem = (item) => {
        if (item.to) go(item.to)
        else if (typeof item.action === 'function') item.action()
    }

    const logoutConfirm = async () => {
        const answer = await ask('ログアウトしますか。')
        if (!answer.value) return
        document.getElementById('logout-form').submit()
    }

    const menuGroups = computed(() => {
        const groups = []
        groups.push({
            title: 'セキュリティ',
            items: [
                { key: 'pw', label: 'パスワードの変更', desc: 'ログインパスワードを更新', icon: icons.lock, to: 'settings-password' },
                { key: '2fa', label: '二段階認証', desc: '認証アプリでログインを保護', icon: icons.shield, to: 'settings-two-factor', badge: twoFaEnabled.value ? '有効' : '未設定', badgeOn: twoFaEnabled.value },
                { key: 'eotp', label: 'メール二段階認証', desc: 'メールに届くコードで保護', icon: icons.mail, to: 'settings-email-otp', badge: emailOtpEnabled.value ? '有効' : '未設定', badgeOn: emailOtpEnabled.value },
                { key: 'passkey', label: 'パスキー', desc: '指紋・顔認証でパスワードレス', icon: icons.key, to: 'settings-passkeys' },
            ],
        })

        const appearance = [
            { key: 'color', label: 'カラー設定', desc: 'アクセントカラーを選ぶ', icon: icons.palette, to: 'settings-color', swatch: colorSwatch.value },
            { key: 'theme', label: 'テーマ設定', desc: themeLabel.value, icon: icons.theme, to: 'settings-theme' },
        ]
        if (responsive.mobile) {
            appearance.push({ key: 'footer', label: 'フッターメニュー表示', desc: '下部メニューバーの表示切替', icon: icons.layout, to: 'settings-footer-menu' })
        }
        groups.push({ title: '表示・外観', items: appearance })

        groups.push({
            title: '通知',
            items: [
                { key: 'push', label: '通知設定', desc: 'プッシュ通知の許可', icon: icons.bell, to: 'settings-notification', badge: permission.value, badgeOn: permission.value === '許可済み' },
                { key: 'guide', label: '通知設定案内', desc: '通知が届かない場合', icon: icons.info, to: 'settings-notification-guide' },
            ],
        })

        const account = [
            { key: 'sign', label: 'マイサイン', desc: '署名を作成・編集', icon: icons.pen, to: 'settings-signature' },
        ]
        if ([540, 608, 516, 604].includes(auth.activeUser.id)) {
            account.push({ key: 'ical', label: 'スケジュール設定', desc: 'カレンダー連携URLを発行', icon: icons.calendar, to: 'settings-schedule' })
        }
        groups.push({ title: 'アカウント', items: account })

        groups.push({
            title: '',
            items: [
                { key: 'logout', label: 'ログアウト', icon: icons.logout, action: logoutConfirm, danger: true },
            ],
        })

        return groups
    })

    onMounted(() => {
        permission.value = (typeof Notification !== 'undefined' && Notification.permission === 'granted') ? '許可済み' : '未許可'
    })
</script>

<style lang="scss" scoped>
    .settings-menu {
        height: 100%;
        display: flex;
        flex-direction: column;
        min-height: 0;
        background: var(--bg3);
    }

    .profile-card {
        display: flex;
        align-items: center;
        padding: 16px;
        background: var(--background-color);
        border: 1px solid var(--normalBorder);
        border-radius: 6px;
        margin-bottom: 22px;
    }

    .settings-group { margin-bottom: 22px; }
    .settings-group__title {
        font-size: 12px;
        font-weight: 600;
        letter-spacing: .04em;
        color: var(--sub-color);
        margin: 0 0 8px 14px;
        text-transform: uppercase;
    }

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
    .settings-row:active { background: color-mix(in srgb, var(--primary-color) 14%, transparent); }
    .settings-row--active { background: var(--selected-background); }
    .settings-row--active:hover { background: var(--selected-background); }
    .settings-row__icon {
        flex: none;
        width: 38px;
        height: 38px;
        border-radius: 6px;
        background: var(--bg3);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--primary-color);
    }
    .settings-row__icon :deep(svg) { width: 20px; height: 20px; }
    .settings-row__body { display: flex; flex-direction: column; min-width: 0; flex: 1; gap: 2px; }
    .settings-row__label { font-size: 15px; line-height: 1.3; }
    .settings-row__desc { font-size: 12px; color: var(--sub-color); line-height: 1.3; }
    .settings-row__chev { flex: none; color: var(--sub-color); opacity: .7; }

    .settings-row--danger { color: #e5484d; }
    .settings-row--danger .settings-row__icon { background: rgba(229,72,77,.12); color: #e5484d; }
    .settings-row--danger:hover { background: rgba(229,72,77,.08); }

    .settings-row__swatch {
        width: 22px;
        height: 22px;
        flex: none;
        border-radius: 5px;
        border: 1px solid var(--formBorder);
    }

    .settings-pill {
        flex: none;
        font-size: 11px;
        font-weight: 600;
        padding: 3px 9px;
        border-radius: 4px;
        white-space: nowrap;
    }
    .settings-pill--on { background: rgba(22,163,74,.14); color: #16a34a; }
    .settings-pill--muted { background: var(--bg3); color: var(--sub-color); }
</style>
