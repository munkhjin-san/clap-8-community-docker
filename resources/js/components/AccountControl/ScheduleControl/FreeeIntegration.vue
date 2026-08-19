<template>
    <div class="admin-window">
        <FloatButton title="freee連携を追加" @action="openModal(null)">
            <template #icon>
                <AddIcon size="15" fill="black" />
            </template>
        </FloatButton>

        <Transition name="modalFade">
            <div v-if="fetch === 0" class="control-loader">
                <div class="spinner-mini" style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
            </div>
        </Transition>

        <div v-if="credentials.length" class="freee-list">
            <div class="freee-callback">
                <p>freeeアプリ管理に登録するコールバックURL</p>
                <code class="select-all">{{ callbackUrl }}</code>
            </div>

            <div
                v-for="credential in credentials"
                :key="credential.id"
                class="freee-box mobile:bg-[var(--bg3)]"
            >
                <div class="freee-box__header">
                    <div>
                        <p class="freee-box__title">{{ credential.label }}</p>
                        <p class="freee-box__company">
                            <span>事業所</span>
                            {{ companyText(credential) }}
                        </p>
                    </div>

                    <ItemMenu :items="menuItems(credential)" />
                </div>

                <div class="freee-box__status">
                    <span :class="{ configured: credential.active }">
                        {{ credential.active ? '利用中' : '停止中' }}
                    </span>
                    <span :class="{ configured: credential.app_configured }">
                        アプリ設定 {{ credential.app_configured ? '設定済み' : '未設定' }}
                    </span>
                    <span :class="statusChipClass(credential)">
                        {{ statusText(credential) }}
                    </span>
                </div>

                <div v-if="credential.connected" class="freee-box__tokens">
                    <div class="freee-box__row">
                        <p>アクセストークン期限</p>
                        <span>{{ formatDateTime(credential.access_token_expires_at) }}</span>
                    </div>
                    <div class="freee-box__row">
                        <p>リフレッシュトークン期限</p>
                        <span :class="{ 'is-warning': isRefreshWindowTight(credential) }">
                            {{ formatDateTime(credential.refresh_token_expires_at) }}
                            <template v-if="credential.refresh_token_days_left !== null">
                                （残り{{ credential.refresh_token_days_left }}日）
                            </template>
                        </span>
                    </div>
                    <div class="freee-box__row">
                        <p>最終更新</p>
                        <span>
                            {{ formatDateTime(credential.last_refreshed_at) }}
                            （更新{{ credential.refresh_count }}回）
                        </span>
                    </div>
                    <div class="freee-box__row">
                        <p>認可</p>
                        <span>
                            {{ formatDateTime(credential.authorized_at) }}
                            <template v-if="credential.authorized_by_name">
                                / {{ credential.authorized_by_name }}
                            </template>
                        </span>
                    </div>
                </div>

                <p v-if="credential.last_error" class="freee-box__error">
                    {{ credential.last_error }}
                    <template v-if="credential.last_error_at">
                        （{{ formatDateTime(credential.last_error_at) }}）
                    </template>
                </p>

                <div class="freee-box__actions">
                    <!-- 事業所は確定しているが、まだ認可が済んでいない／切れている -->
                    <template v-if="needsConsent(credential)">
                        <ol class="freee-steps">
                            <li :class="{ done: credential.app_configured }">
                                freeeのアプリ管理でクライアントID・シークレットを発行し、上のコールバックURLを登録する
                            </li>
                            <li>
                                「{{ consentLabel(credential) }}」を押し、freeeにログインして連携を許可する
                            </li>
                            <li v-if="credential.out_of_band">
                                表示された認可コードを下の欄に貼り付ける
                            </li>
                        </ol>

                        <LoaderButton
                            :loading="busyId === credential.id"
                            :content="consentLabel(credential)"
                            @triggered="connect(credential)"
                        />

                        <!-- コールバックを受けられない環境：コードを手貼りする -->
                        <div v-if="credential.out_of_band" class="freee-box__oob">
                            <p class="freee-box__oob-label">認可コード</p>
                            <input
                                v-model="codeInput[credential.id]"
                                type="text"
                                class="freee-box__oob-input"
                                placeholder="freeeの画面に表示された文字列を貼り付け"
                                @keyup.enter="exchangeCode(credential)"
                            >
                            <LoaderButton
                                :loading="busyId === credential.id"
                                content="コードを送信する"
                                @triggered="exchangeCode(credential)"
                            />
                        </div>

                        <p class="freee-box__hint">
                            認可はブラウザでの操作が一度だけ必要です。以降は毎日の自動更新で人の操作は不要になります。
                        </p>
                    </template>

                    <!-- トークンはあるが事業所が未確定。このままではAPI呼び出しが全て失敗する -->
                    <template v-else-if="credential.awaiting_company_selection">
                        <p class="freee-box__notice">
                            認可は完了しましたが、事業所が特定できませんでした。利用する事業所を選択してください。
                        </p>
                        <select v-model="companyChoice[credential.id]" class="freee-box__select">
                            <option :value="null" disabled>事業所を選択</option>
                            <option
                                v-for="company in companyOptions[credential.id] ?? []"
                                :key="company.id ?? 0"
                                :value="company.id"
                            >
                                {{ company.name ?? '（名称なし）' }}（ID: {{ company.id }}）
                            </option>
                        </select>
                        <LoaderButton
                            :loading="busyId === credential.id"
                            content="事業所を設定する"
                            @triggered="selectCompany(credential)"
                        />
                    </template>

                    <!-- 連携済み。人の操作は不要な状態 -->
                    <template v-else>
                        <p class="freee-box__hint">
                            自動更新が有効です。次回更新は毎日3時40分。人の操作は不要です。
                        </p>
                    </template>
                </div>
            </div>
        </div>

        <div v-else-if="fetch > 0" class="freee-empty">
            現在データはありません
        </div>

        <Transition name="modalFade">
            <FreeeCredentialCreate
                v-if="modalOpen"
                :edit-target="editTarget"
                :default-callback-url="callbackUrl"
                @close="closeModal"
            />
        </Transition>
    </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import FloatButton from '@/components/Global/FloatButton.vue'
import ItemMenu from '@/components/Global/ItemMenu.vue'
import LoaderButton from '@/components/Global/LoaderButton.vue'
import AddIcon from '@/components/Form/AddIcon.vue'
import FreeeCredentialCreate from './FreeeCredentialCreate.vue'
import { useApi } from '@/composables/api'
import { useDialog } from '@/composables/dialog'
import type {
    FreeeCompaniesResponse,
    FreeeConnectResponse,
    FreeeConnectionCompany,
    FreeeCredentialIndexResponse,
    FreeeCredentialSetting,
    FreeeTestResponse,
} from '@/interface/freeeInterface'

const api = useApi()
const dialog = useDialog()
const route = useRoute()
const router = useRouter()

const fetch = ref(0)
const credentials = ref<FreeeCredentialSetting[]>([])
const callbackUrl = ref('')
const modalOpen = ref(false)
const editTarget = ref<FreeeCredentialSetting | null>(null)
const busyId = ref<number | null>(null)
const codeInput = ref<Record<number, string>>({})
const companyOptions = ref<Record<number, FreeeConnectionCompany[]>>({})
const companyChoice = ref<Record<number, number | null>>({})

const getCredentials = async () => {
    const response = await api.get('/admin/freee-credentials') as FreeeCredentialIndexResponse
    credentials.value = response.credentials ?? []
    callbackUrl.value = response.callback_url ?? ''
    fetch.value++

    // 事業所が未確定な連携があれば、選択肢を先に用意しておく。
    credentials.value
        .filter(credential => credential.awaiting_company_selection)
        .forEach(credential => loadCompanies(credential))
}

// 認可のやり直しが必要な状態（未認可・認可待ち・再認可要求）。
const needsConsent = (credential: FreeeCredentialSetting) =>
    !credential.connected || credential.reauthorization_required

const consentLabel = (credential: FreeeCredentialSetting) =>
    credential.reauthorization_required ? '再認可する' : '認可する'

const openModal = (credential: FreeeCredentialSetting | null) => {
    editTarget.value = credential
    modalOpen.value = true
}

const closeModal = (refresh: boolean) => {
    modalOpen.value = false
    editTarget.value = null
    if (refresh) getCredentials()
}

const companyText = (credential: FreeeCredentialSetting) => {
    if (!credential.company_id) return '未連携'
    return credential.company_name
        ? `${credential.company_name}（ID: ${credential.company_id}）`
        : `ID: ${credential.company_id}`
}

const statusText = (credential: FreeeCredentialSetting) => {
    if (credential.reauthorization_required) return '再認可が必要'
    if (credential.awaiting_company_selection) return '事業所未設定'
    if (credential.connected) return '連携中'
    if (credential.status === 'awaiting_consent') return '認可待ち'
    return '未認可'
}

const statusChipClass = (credential: FreeeCredentialSetting) => ({
    configured: credential.connected
        && !credential.reauthorization_required
        && !credential.awaiting_company_selection,
    attention: credential.reauthorization_required,
})

// 残りが2週間を切ったら、自動更新が止まっている可能性を疑う目印にする。
const isRefreshWindowTight = (credential: FreeeCredentialSetting) =>
    credential.refresh_token_days_left !== null && credential.refresh_token_days_left <= 14

const formatDateTime = (value: string | null) => {
    if (!value) return '—'
    const date = new Date(value)
    if (Number.isNaN(date.getTime())) return '—'
    return date.toLocaleString('ja-JP', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    })
}

const menuItems = (credential: FreeeCredentialSetting) => {
    const items: { title: string, action: () => void }[] = [
        { title: '編集する', action: () => openModal(credential) },
    ]

    if (credential.connected) {
        items.push(
            { title: '接続確認する', action: () => testConnection(credential) },
            { title: 'トークンを更新する', action: () => refreshToken(credential) },
            { title: '連携を解除する', action: () => disconnect(credential) },
        )
    }

    items.push({ title: '削除する', action: () => remove(credential) })

    return items
}

// 認可はfreeeの同意画面への遷移。
// コールバック方式はstateをセッションで照合するため同一タブで遷移する。
// OOB方式はコードを手貼りするため、この画面を残したまま別タブで開く。
const connect = async (credential: FreeeCredentialSetting) => {
    busyId.value = credential.id
    try {
        const result = await api.post(
            `/admin/freee-credentials/${credential.id}/connect`,
            {},
        ) as FreeeConnectResponse | null

        if (!result?.authorization_url) return

        if (result.out_of_band) {
            window.open(result.authorization_url, '_blank', 'noopener')
        } else {
            window.location.href = result.authorization_url
        }
    } finally {
        busyId.value = null
    }
}

const exchangeCode = async (credential: FreeeCredentialSetting) => {
    const code = (codeInput.value[credential.id] ?? '').trim()
    if (!code) {
        dialog.ping('認可コードを入力してください。')
        return
    }

    busyId.value = credential.id
    try {
        const result = await api.post(
            `/admin/freee-credentials/${credential.id}/exchange-code`,
            { code },
            { toast: 'freeeとの連携が完了しました' },
        )
        if (result !== null) {
            codeInput.value[credential.id] = ''
            getCredentials()
        }
    } finally {
        busyId.value = null
    }
}

const loadCompanies = async (credential: FreeeCredentialSetting) => {
    const result = await api.get(
        `/admin/freee-credentials/${credential.id}/companies`,
        null,
        { silent: true },
    ) as FreeeCompaniesResponse | null

    if (result?.companies) {
        companyOptions.value[credential.id] = result.companies
        companyChoice.value[credential.id] ??= null
    }
}

const selectCompany = async (credential: FreeeCredentialSetting) => {
    const companyId = companyChoice.value[credential.id]
    if (!companyId) {
        dialog.ping('事業所を選択してください。')
        return
    }

    busyId.value = credential.id
    try {
        const result = await api.post(
            `/admin/freee-credentials/${credential.id}/company`,
            { company_id: companyId },
            { toast: '事業所を設定しました' },
        )
        if (result !== null) getCredentials()
    } finally {
        busyId.value = null
    }
}

const testConnection = async (credential: FreeeCredentialSetting) => {
    const result = await api.post(
        `/admin/freee-credentials/${credential.id}/test`,
        {},
    ) as FreeeTestResponse | null

    if (!result) return

    const connection = result.connection
    const company = connection?.companies?.find(c => c.id === connection.company_id)
    const detail = connection
        ? [
            company?.name ?? connection.display_name ?? connection.email,
            connection.company_id ? `事業所ID: ${connection.company_id}` : null,
        ].filter(Boolean).join(' / ')
        : ''

    // 人事労務の権限が無い場合は成功として流さず、対処方法まで出す。
    const lines = [
        connection?.hr_available
            ? `freee APIに接続できました。人事労務APIも利用可能です。（${detail}）`
            : `freeeへの接続は成功しました。（${detail}）`,
    ]
    if (connection && !connection.hr_available) {
        lines.push(connection.hr_message ?? '人事労務APIが利用できません。')
    }

    dialog.ping(lines.join('\n'))
    getCredentials()
}

const refreshToken = async (credential: FreeeCredentialSetting) => {
    const result = await api.post(
        `/admin/freee-credentials/${credential.id}/refresh`,
        {},
        { toast: 'アクセストークンを更新しました' },
    )
    if (result !== null) getCredentials()
}

const disconnect = async (credential: FreeeCredentialSetting) => {
    const result = await api.post(
        `/admin/freee-credentials/${credential.id}/disconnect`,
        {},
        {
            ask: `${credential.label}の連携を解除しますか？再開には再度ブラウザでの認可が必要です。`,
            toast: 'freee連携を解除しました',
        },
    )
    if (result !== null) getCredentials()
}

const remove = async (credential: FreeeCredentialSetting) => {
    const result = await api.del(
        `/admin/freee-credentials/${credential.id}`,
        {},
        {
            ask: `${credential.label}を削除しますか？`,
            toast: 'freee連携設定を削除しました',
        },
    )
    if (result !== null) getCredentials()
}

// コールバックはサーバー側でリダイレクトされてくるので、結果はクエリで受け取る。
const consumeCallbackResult = () => {
    const result = route.query.freee_result
    if (!result) return

    const message = typeof route.query.freee_message === 'string' ? route.query.freee_message : ''
    dialog.ping(message || (result === 'connected' ? 'freeeとの連携が完了しました。' : 'freee連携に失敗しました。'))

    router.replace({ query: {} })
}

onMounted(async () => {
    await getCredentials()
    consumeCallbackResult()
})
</script>

<style scoped>
.freee-list {
    height: calc(100% - 40px);
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 20px;
    overflow: hidden auto;
}

.freee-callback {
    padding: 14px 18px;
    background: var(--bg3);
}

.freee-callback p {
    margin-bottom: 5px;
    color: gray;
    font-size: 11px;
}

.freee-callback code {
    display: block;
    font-family: inherit;
    font-size: 12px;
    line-height: 1.5;
    overflow-wrap: anywhere;
}

.freee-box {
    position: relative;
    padding: 22px;
    background: var(--background-color);
    font-size: 14px;
}

.freee-box__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 20px;
}

.freee-box__title {
    font-size: 16px;
    line-height: 1.4;
}

.freee-box__company {
    margin-top: 7px;
    color: var(--text-color);
    line-height: 1.5;
    overflow-wrap: anywhere;
}

.freee-box__company span {
    margin-right: 8px;
    color: gray;
    font-size: 12px;
}

.freee-box__status {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 18px;
}

.freee-box__status span {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 9px;
    background: var(--bg3);
    color: gray;
    font-size: 12px;
    line-height: 1.4;
}

.freee-box__status span::before {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #a1a1aa;
    content: '';
}

.freee-box__status span.configured {
    background: #edf8f0;
    color: #166534;
}

.freee-box__status span.configured::before {
    background: #22a447;
}

.freee-box__status span.attention {
    background: #fff1f0;
    color: #b42318;
}

.freee-box__status span.attention::before {
    background: #d0342c;
}

.freee-box__tokens {
    margin-top: 18px;
    padding-top: 14px;
    border-top: 1px solid var(--bg3);
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.freee-box__row {
    display: flex;
    flex-wrap: wrap;
    gap: 4px 12px;
    font-size: 12px;
    line-height: 1.5;
}

.freee-box__row p {
    min-width: 160px;
    color: gray;
}

.freee-box__row span.is-warning {
    color: #b42318;
}


.freee-box__error {
    margin-top: 14px;
    padding: 10px;
    background: #fff1f0;
    color: #b42318;
    font-size: 12px;
    line-height: 1.6;
    overflow-wrap: anywhere;
}

.freee-box__actions {
    margin-top: 18px;
    padding-top: 14px;
    border-top: 1px solid var(--bg3);
}

.freee-box__hint {
    margin-top: 10px;
    color: gray;
    font-size: 11px;
    line-height: 1.6;
    text-align: center;
}

.freee-steps {
    margin: 0 0 16px;
    padding: 0;
    list-style: none;
    counter-reset: freee-step;
    display: flex;
    flex-direction: column;
    gap: 7px;
}

.freee-steps li {
    counter-increment: freee-step;
    position: relative;
    padding-left: 26px;
    color: gray;
    font-size: 12px;
    line-height: 1.6;
}

.freee-steps li::before {
    position: absolute;
    top: 1px;
    left: 0;
    width: 17px;
    height: 17px;
    border: 1px solid var(--bg3);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    content: counter(freee-step);
}

.freee-steps li.done::before {
    border-color: #22a447;
    background: #edf8f0;
    color: #166534;
}

.freee-box__oob {
    margin-top: 16px;
    padding-top: 14px;
    border-top: 1px solid var(--bg3);
}

.freee-box__oob-label {
    margin-bottom: 6px;
    color: gray;
    font-size: 11px;
}

.freee-box__oob-input {
    width: 100%;
    margin-bottom: 12px;
    padding: 9px 11px;
    border: 1px solid var(--bg3);
    background: var(--background-color);
    color: var(--text-color);
    font-size: 13px;
}

.freee-box__oob-input:focus {
    border-color: var(--primary-button);
    outline: none;
}

.freee-box__notice {
    margin-bottom: 14px;
    padding: 10px;
    background: var(--bg3);
    color: var(--text-color);
    font-size: 12px;
    line-height: 1.6;
}

.freee-box__select {
    width: 100%;
    margin-bottom: 12px;
    padding: 9px 11px;
    border: 1px solid var(--bg3);
    background: var(--background-color);
    color: var(--text-color);
    font-size: 13px;
}

.freee-empty {
    height: 100%;
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: gray;
}
</style>
