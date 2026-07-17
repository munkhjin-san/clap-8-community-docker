<template>
    <div class="admin-window fd-screen">
        <Transition name="modalFade">
            <div v-if="loading" class="control-loader">
                <div class="spinner-mini" style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
            </div>
        </Transition>

        <div class="post-header">
            <HamBurger v-if="responsive.mobile" />
            <div class="fd-head">
                <div class="fd-back" @click="back"><Back size="14" /></div>
                <span class="fd-title">自分の対応待ち</span>
                <span class="fd-count">{{ items.length }}件</span>
            </div>
        </div>

        <div class="fd-body">
            <p v-if="!loading && !items.length" class="fd-empty">対応待ちのレコードはありません。</p>
            <div v-for="it in items" :key="it.record_id" class="fd-item" @click="open(it)">
                <div class="fd-main">
                    <div class="fd-app">{{ it.app_name }}</div>
                    <div class="fd-rec">
                        <span class="fd-num">#{{ it.record_number }}</span>
                        <span v-if="it.status" class="fd-st">{{ it.status }}</span>
                    </div>
                </div>
                <span class="fd-arrow">›</span>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useApi } from '@/composables/api'
import { useResponsive } from '@/store/responsive'
import HamBurger from '@/components/Global/HamBurger.vue'
import Back from '@/components/Icons/Back.vue'

interface DashItem { app_id: number; app_name: string; record_id: number; record_number: number; status: string | null; updated_at?: string }

const api = useApi()
const router = useRouter()
const responsive = useResponsive()
const loading = ref(true)
const items = ref<DashItem[]>([])

const open = (it: DashItem) =>
    router.push({ name: 'flow-record-detail', params: { flowId: it.app_id, recordId: it.record_number } })
const back = () => router.push({ name: 'flow-control' })

onMounted(async () => {
    try {
        const data = await api.get('/flow_dashboard')
        items.value = (data?.items ?? []) as DashItem[]
    } finally {
        loading.value = false
    }
})
</script>

<style scoped>
.admin-window { color: var(--primary-color); }
.fd-screen { position: relative; display: flex; flex-direction: column; height: 100%; }
.fd-head { display: flex; align-items: center; gap: 10px; padding: 0 6px; }
.fd-back { display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px; border-radius: 6px; cursor: pointer; fill: var(--primary-color); }
.fd-back:hover { background: var(--bg3); }
.fd-title { font-size: 17px; }
.fd-count { font-size: 12px; color: gray; }
.fd-body { flex: 1; overflow: auto; padding: 16px 20px; display: flex; flex-direction: column; gap: 10px; }
.fd-empty { font-size: 13px; color: gray; text-align: center; margin-top: 40px; }
.fd-item { display: flex; align-items: center; gap: 12px; background: var(--background-color); border: 1px solid var(--calendarBorder); border-radius: 10px; padding: 14px 16px; cursor: pointer; transition: border-color .12s; }
.fd-item:hover { border-color: var(--primary-color); }
.fd-main { flex: 1; min-width: 0; }
.fd-app { font-size: 14px; font-weight: 500; }
.fd-rec { display: flex; align-items: center; gap: 10px; margin-top: 5px; }
.fd-num { font-size: 12px; color: gray; }
.fd-st { font-size: 11px; color: var(--primary-color); background: var(--bg3); padding: 2px 9px; border-radius: 10px; }
.fd-arrow { color: gray; font-size: 18px; flex-shrink: 0; }
</style>
