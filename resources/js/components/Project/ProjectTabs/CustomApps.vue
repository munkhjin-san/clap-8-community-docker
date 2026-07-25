<template>
    <div class="ca-wrap">
        <Transition name="modalFade">
            <div v-if="loading" class="control-loader">
                <div class="spinner-mini" style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
            </div>
        </Transition>
        <div class="h-full overflow-auto p-[20px]">
            <div class="flex items-center justify-between mb-[16px]">
                <h2 class="text-[15px]">アプリ</h2>
                <button class="ca-primary" @click="newApp"><span class="text-[16px] leading-none">＋</span> 新規アプリ</button>
            </div>

            <p v-if="!loading && !definitions.length" class="text-[13px] text-gray-500 mt-[30px] text-center">
                このプロジェクトにはまだアプリがありません。「新規アプリ」から作成してください。
            </p>

            <div class="flex flex-col gap-[12px]">
                <div
                    v-for="def in definitions"
                    :key="def.id"
                    @click="openRecords(def.id)"
                    class="relative bg-[var(--background-color)] cursor-pointer p-[16px] border border-solid border-[var(--calendarBorder)] rounded-[10px] hover:border-[var(--primary-color)] transition"
                >
                    <div class="flex items-center gap-[10px]">
                        <span class="font-medium">{{ def.name }}</span>
                        <span v-if="!def.is_active" class="text-[11px] px-[8px] py-[2px] rounded-full bg-[var(--bg3)] text-gray-500">停止中</span>
                    </div>
                    <p v-if="def.description" class="text-[12px] text-gray-500 mt-[5px] line-clamp-2">{{ def.description }}</p>
                    <div class="flex gap-[16px] text-[12px] text-gray-500 mt-[8px]">
                        <span>申請 {{ def.records_count ?? 0 }}件</span>
                    </div>
                    <div class="absolute right-[10px] top-[10px]">
                        <ItemMenu :items="[
                            {title: '開く', action: () => openRecords(def.id)},
                            {title: '設計', action: () => openBuilder(def.id)},
                            {title: '削除', action: () => removeDefinition(def.id)},
                        ]"/>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useApi } from '@/composables/api'
import ItemMenu from '@/components/Global/ItemMenu.vue'
import type { FlowDefinitionListItem } from '@/types/flow'

const api = useApi()
const route = useRoute()
const router = useRouter()
const definitions = ref<FlowDefinitionListItem[]>([])
const loading = ref(true)
const projectId = computed(() => Number(route.params.projectId))

const getDefinitions = async () => {
    loading.value = true
    try {
        const data = await api.get('/flow_definitions', { project_id: projectId.value })
        definitions.value = Array.isArray(data) ? data as FlowDefinitionListItem[] : []
    } finally {
        loading.value = false
    }
}

const newApp = () => router.push({ name: 'flow-builder', query: { project: projectId.value } })
const openBuilder = (id: number) => router.push({ name: 'flow-builder', params: { flowId: id } })
const openRecords = (id: number) => router.push({ name: 'flow-records', params: { flowId: id } })
const removeDefinition = async (id: number) => {
    const data = await api.post('/flow_definition_delete', { id }, { ask: 'このアプリを削除しますか？', toast: '削除しました。' })
    data && getDefinitions()
}

onMounted(getDefinitions)
</script>

<style scoped>
.ca-wrap { position: relative; width: 100%; height: 100%; overflow: hidden; color: var(--primary-color); }
.ca-primary { display: flex; align-items: center; gap: 6px; padding: 7px 14px; font-size: 13px; color: #fff; background: var(--primary-button, var(--primary-color)); border: none; border-radius: 6px; cursor: pointer; }
</style>
