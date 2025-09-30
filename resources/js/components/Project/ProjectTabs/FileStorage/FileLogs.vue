<template>
    <div class="flex flex-col">
        <!-- <div class="flex items-center justify-between py-3 sticky top-0 bg-[var(--background-color)] z-10">
            <h3 class="text-sm font-semibold">アクティビティ</h3>
            <div class="flex gap-3 items-center">
                <button
                    class="text-xs px-3 py-1 bg-[var(--primary-button)] text-white border border-white/20 disabled:opacity-60 disabled:cursor-not-allowed"
                    @click="fetchLogs"
                    :disabled="loading || !resolvedProjectId"
                >
                    {{ loading ? '更新中…' : '再読み込み' }}
                </button>
            </div>
        </div> -->

        <p v-if="!resolvedProjectId" class="text-xs">
            プロジェクトを選択するとアクティビティが表示されます。
        </p>
        <p v-else-if="error" class="text-xs text-red-400">
            {{ error }}
        </p>
        <p v-else-if="loading && formattedEntries.length === 0" class="text-xs">
            読み込み中…
        </p>
        <div v-else class="border border-[var(--calendarToday)] border-solid overflow-hidden">
            <div
                v-if="formattedEntries.length === 0"
                class="px-4 py-6 text-xs text-center"
            >
                履歴はまだありません。
            </div>
            <div
                v-for="entry in formattedEntries"
                :key="entry.id"
                class="px-4 py-3 border-b border-[var(--calendarToday)] [border-bottom-style:solid] last:border-b-0"
            >
                <div class="flex items-start justify-between gap-3 text-xs">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-[10px] uppercase tracking-wide bg-[var(--calendarBorder)] px-1.5 py-0.5 rounded">{{ entry.label }}</span>
                            <!-- <span class="text-[10px] uppercase tracking-wide bg-[var(--calendarBorder)] px-1.5 py-0.5 rounded">
                                {{ entry.badge }}
                            </span> -->
                            <span class="truncate">{{ entry.itemName }}</span>
                        </div>
                        <div v-if="entry.pathSummary" class="mt-1 text-[11px]">
                            パス：{{ entry.pathSummary }}
                        </div>
                        <div v-if="entry.info" class="mt-0.5 text-[11px]">
                            IPアドレス：{{ entry.info }}
                        </div>
                    </div>
                    <span class="whitespace-nowrap">{{ entry.time }}</span>
                </div>
                <div class="mt-1 flex items-center gap-2 text-[11px]">
                    <!-- <UserPanel
                        v-if="entry.user"
                        :user="entry.user"
                        size="20"
                        :disableInstant="true"
                    /> -->
                    <span>メンバー：{{ entry.user?.name ?? '不明なユーザー' }}</span>
                </div>
            </div>
        </div>
    </div>
</template>

<script lang="ts" setup>
import { computed, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useApi } from '@/composables/api'
import { DateParser, fileSizeParser } from '@/utils/tools'
import UserPanel from '@/components/Global/UserPanel.vue'

const props = defineProps<{ projectId?: number | null; itemId?: string | null }>()
const api = useApi()
const route = useRoute()

const resolvedProjectId = computed(() => {
    if (props.projectId != null) return Number(props.projectId)
    const param = route.params.projectId
    return param ? Number(param) : null
})

const loading = ref(false)
const error = ref<string | null>(null)
const entries = ref<any[]>([])

const ACTION_LABELS: Record<string, string> = {
    created: '作成',
    uploaded: 'アップロード',
    moved: '移動',
    downloaded: 'ダウンロード',
    accessed: 'アクセス',
    deleted: '削除',
}

const BADGES: Record<string, string> = {
    created: 'NEW',
    uploaded: 'UP',
    moved: 'MOVE',
    downloaded: 'DL',
    accessed: 'VIEW',
    deleted: 'DEL',
}

const fetchLogs = async () => {
    if (!resolvedProjectId.value) return
    loading.value = true
    error.value = null
    try {
        const params: Record<string, any> = {
            project_id: resolvedProjectId.value,
            limit: 100,
        }
        if (props.itemId) params.item_id = props.itemId
        const res = await api.get('/drive/logs', params, { silent: true })
        entries.value = Array.isArray(res?.data) ? res.data : []
    } catch (err: any) {
        error.value = err?.response?.data?.message || 'ログの取得に失敗しました'
    } finally {
        loading.value = false
    }
}

watch([resolvedProjectId, () => props.itemId], ([project, item], [prevProject, prevItem]) => {
    if (project && (project !== prevProject || item !== prevItem)) {
        fetchLogs()
    }
}, { immediate: true })

const formattedEntries = computed(() => entries.value.map(entry => {
    const action = entry.action ?? ''
    const label = ACTION_LABELS[action] ?? action
    const badge = BADGES[action] ?? action.toUpperCase()
    const itemName = entry.item_name ?? '(不明なアイテム)'

    const infoParts: string[] = []
    if (entry.client_ip) infoParts.push(entry.client_ip)

   
    const pathSummary = entry.action === 'moved' ? `ホーム/${entry.from_path ?? ''} → ホーム/${entry.to_path}` : `ホーム/${entry.from_path ?? ''}`
 
    const time = entry.timestamp ? DateParser(entry.timestamp) : '-'

    return {
        id: entry.id,
        label,
        badge,
        itemName,
        pathSummary,
        info: infoParts.join(' / '),
        time,
        user: entry.user ?? null,
    }
}))
</script>
