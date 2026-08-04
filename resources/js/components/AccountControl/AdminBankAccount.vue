<template>
    <div class="bank-modal">
        <div class="bank-head">
            <span class="bank-title">振込口座</span>
            <span class="bank-user">{{ user?.name }}</span>
            <button class="bank-x" title="閉じる" @click="emit('close')"><CloseIcon size="10" /></button>
        </div>

        <div v-if="loading" class="bank-loading">読み込み中…</div>

        <template v-else>
            <div class="bank-row">
                <label>口座名義人</label>
                <input v-model="form.account_holder" type="text" class="custom-a-input !box-border" placeholder="ﾀﾅｶ ﾀﾛｳ">
            </div>
            <div class="bank-row">
                <label>金融機関名</label>
                <input v-model="form.bank_name" type="text" class="custom-a-input !box-border" placeholder="ゆうちょ銀行">
            </div>
            <div class="bank-row">
                <label>支店名</label>
                <input v-model="form.branch_name" type="text" class="custom-a-input !box-border" placeholder="〇〇支店">
            </div>
            <div class="bank-row">
                <label>フリガナ</label>
                <input v-model="form.account_holder_kana" type="text" class="custom-a-input !box-border" placeholder="ﾀﾅｶ ﾀﾛｳ">
            </div>

            <!-- 番号: 保存済みの値はフォームに載せない。伏せ字と「表示」だけを置き、入力すると差し替え。 -->
            <div class="bank-row">
                <label>口座番号</label>
                <div class="bank-num">
                    <input
                        v-model="form.account_number"
                        type="text"
                        inputmode="numeric"
                        class="custom-a-input !box-border"
                        :placeholder="account?.has_number ? '変更する場合のみ入力' : '00123456'"
                    >
                    <template v-if="account?.has_number">
                        <span class="bank-mask">{{ revealed ?? account.account_number_masked }}</span>
                        <button class="bank-btn" :disabled="revealing" @click="reveal">
                            {{ revealed ? '隠す' : '表示' }}
                        </button>
                    </template>
                </div>
            </div>
            <p class="bank-hint">
                口座番号は暗号化して保存されます。空欄のまま保存すると口座番号は変更されません。「表示」は記録されます。
            </p>

            <div class="bank-actions">
                <button v-if="account" class="bank-btn danger" @click="remove">削除</button>
                <span class="bank-spacer"></span>
                <span v-if="account?.updated_at" class="bank-meta">
                    最終更新 {{ fmt(account.updated_at) }}<template v-if="account.updated_by"> · {{ account.updated_by }}</template>
                </span>
                <button class="bank-btn" @click="emit('close')">キャンセル</button>
                <LoaderButton :loading="saving" content="保存" @triggered="save" />
            </div>

            <template v-if="logs.length">
                <div class="bank-logline"></div>
                <div class="bank-logs">
                    <div class="bank-logs-h">操作履歴</div>
                    <div v-for="l in logs" :key="l.id" class="bank-log">
                        <span class="bank-log-a">{{ actionLabel(l.action) }}</span>
                        <span class="bank-log-w">{{ l.actor ?? '—' }}</span>
                        <span class="bank-log-t">{{ fmt(l.created_at) }}</span>
                    </div>
                </div>
            </template>
        </template>
    </div>
</template>

<script setup lang="ts">
/**
 * 管理画面 > アカウント の振込口座 CRUD（管理者のみ）。
 *
 * 平文の番号をフォームの初期値にしない：保存済みの番号は伏せ字で示し、必要なときだけ「表示」で
 * 取りに行く。こうしておくと、他の項目だけ直したい人が番号を画面に出さずに済み、出したという
 * 事実はサーバ側で必ず記録される。
 */
import { onMounted, reactive, ref } from 'vue'
import { useApi } from '@/composables/api'
import LoaderButton from '@/components/Global/LoaderButton.vue'
import CloseIcon from '@/components/Form/CloseIcon.vue'

const props = defineProps<{ user: { id: number; name?: string } | null }>()
const emit = defineEmits<{ (e: 'close'): void; (e: 'saved'): void }>()

const api = useApi()

type Account = {
    account_holder?: string | null
    bank_name?: string | null
    branch_name?: string | null
    account_holder_kana?: string | null
    account_number_masked?: string | null
    has_number?: boolean
    updated_by?: string | null
    updated_at?: string | null
}

const loading = ref(true)
const saving = ref(false)
const revealing = ref(false)
const revealed = ref<string | null>(null)
const account = ref<Account | null>(null)
const logs = ref<{ id: number; action: string; actor?: string | null; created_at?: string }[]>([])

const form = reactive({
    account_holder: '',
    bank_name: '',
    branch_name: '',
    account_holder_kana: '',
    /** 空 = 変更しない。保存済みの平文はここに入れない。 */
    account_number: '',
})

const load = async () => {
    if (!props.user?.id) return
    loading.value = true
    try {
        const data = await api.get(`/admin/bank-accounts/${props.user.id}`)
        account.value = data?.account ?? null
        form.account_holder = account.value?.account_holder ?? ''
        form.bank_name = account.value?.bank_name ?? ''
        form.branch_name = account.value?.branch_name ?? ''
        form.account_holder_kana = account.value?.account_holder_kana ?? ''
        form.account_number = ''
        revealed.value = null
        await loadLogs()
    } finally {
        loading.value = false
    }
}

const loadLogs = async () => {
    const data = await api.get(`/admin/bank-accounts/${props.user!.id}/logs`)
    logs.value = data?.logs ?? []
}

const reveal = async () => {
    if (revealed.value) { revealed.value = null; return }
    revealing.value = true
    try {
        const data = await api.post(`/admin/bank-accounts/${props.user!.id}/reveal`, {}, { silent: true })
        if (data) {
            revealed.value = data.account_number ?? null
            await loadLogs()   // 表示した事実がすぐ履歴に出る
        }
    } finally {
        revealing.value = false
    }
}

const save = async () => {
    saving.value = true
    try {
        const data = await api.put(`/admin/bank-accounts/${props.user!.id}`, { ...form }, { toast: '保存しました。' })
        if (data) {
            account.value = data.account ?? null
            form.account_number = ''
            revealed.value = null
            emit('saved')
            await loadLogs()
        }
    } finally {
        saving.value = false
    }
}

const remove = async () => {
    const data = await api.del(`/admin/bank-accounts/${props.user!.id}`, null, { ask: 'この口座情報を削除しますか？', toast: '削除しました。' })
    if (data) { emit('saved'); emit('close') }
}

const actionLabel = (a: string) =>
    ({ reveal: '表示', create: '登録', update: '変更', delete: '削除' } as Record<string, string>)[a] ?? a

const fmt = (v?: string | null) => {
    if (!v) return ''
    const d = new Date(v)
    if (isNaN(d.getTime())) return ''
    const p = (n: number) => String(n).padStart(2, '0')
    return `${d.getFullYear()}/${p(d.getMonth() + 1)}/${p(d.getDate())} ${p(d.getHours())}:${p(d.getMinutes())}`
}

onMounted(load)
</script>

<style scoped>
.bank-modal { box-sizing: border-box !important; width: 520px; max-width: 94vw; max-height: 88vh; overflow-y: auto; padding: 18px 20px 20px; background: var(--background-color); border: 1px solid var(--formBorder); border-radius: 8px; }
.bank-head { display: flex; align-items: center; gap: 10px; padding-bottom: 12px; border-bottom: 1px solid var(--calendarBorder); }
.bank-title { font-size: 13px; }
.bank-user { font-size: 12px; color: var(--sub-color); }
.bank-x { margin-left: auto; border: none; background: none; cursor: pointer; padding: 4px; }
.bank-loading { padding: 28px 0; text-align: center; font-size: 12px; color: var(--sub-color); }
.bank-row { display: flex; align-items: center; gap: 10px; margin-top: 12px; }
.bank-row > label { width: 84px; flex: none; font-size: 12px; color: var(--sub-color); }
.bank-row input { flex: 1; }
.bank-num { flex: 1; display: flex; align-items: center; gap: 8px; }
.bank-num input { flex: 1; }
.bank-mask { font-size: 12px; font-variant-numeric: tabular-nums; color: var(--font-color); white-space: nowrap; }
.bank-hint { margin-top: 10px; font-size: 11px; color: var(--sub-color); line-height: 1.6; }
.bank-actions { display: flex; align-items: center; gap: 8px; margin-top: 18px; }
.bank-spacer { flex: 1; }
.bank-meta { font-size: 11px; color: var(--sub-color); }
.bank-btn { box-sizing: border-box !important; padding: 6px 12px; font-size: 12px; border: 1px solid var(--formBorder); border-radius: 4px; background: var(--background-color); color: var(--font-color); cursor: pointer; }
.bank-btn:hover:not(:disabled) { background: var(--bg3); }
.bank-btn:disabled { cursor: default; opacity: .6; }
.bank-btn.danger { color: #e2574c; border-color: #e2574c; }
.bank-logline { margin-top: 18px; border-top: 1px solid var(--calendarBorder); }
.bank-logs { margin-top: 12px; }
.bank-logs-h { font-size: 11px; color: var(--sub-color); letter-spacing: .04em; margin-bottom: 6px; }
.bank-log { display: flex; align-items: center; gap: 10px; padding: 4px 0; font-size: 11px; color: var(--sub-color); }
.bank-log-a { width: 34px; flex: none; color: var(--font-color); }
.bank-log-w { flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.bank-log-t { font-variant-numeric: tabular-nums; }
</style>
