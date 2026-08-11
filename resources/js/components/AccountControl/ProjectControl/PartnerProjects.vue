<template>
    <Modal size="medium" :loader="loading || saving" @close="emit('close')">
        <template #title>
            <span>紐付けプロジェクト：{{ partner.name }}</span>
        </template>
        <template #content>
            <div class="partner-projects">
                <div class="partner-projects__bar">
                    <input
                        v-model="keyword"
                        type="text"
                        class="custom-a-input partner-projects__search"
                        placeholder="プロジェクト検索"
                    />
                    <span class="partner-projects__count">{{ selected.length }}件選択中</span>
                </div>

                <div class="partner-projects__list">
                    <label
                        v-for="project in filtered"
                        :key="project.id"
                        class="partner-projects__item"
                    >
                        <input
                            type="checkbox"
                            class="custom-f-checkbox"
                            :value="project.id"
                            v-model="selected"
                        />
                        <span class="partner-projects__name">{{ project.name }}</span>
                        <span class="partner-projects__meta">{{ PROJECT_STATUS_LABEL[project.status] ?? '不明' }}</span>
                    </label>
                    <p v-if="!filtered.length" class="partner-projects__empty">該当するプロジェクトはありません</p>
                </div>

                <div class="partner-projects__footer">
                    <LoaderButton content="保存する" :loading="saving" @triggered="save" />
                </div>
            </div>
        </template>
    </Modal>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import Modal from '@/components/Global/Modal.vue'
import LoaderButton from '@/components/Global/LoaderButton.vue'
import { useApi } from '@/composables/api'
import { useDialog } from '@/composables/dialog'
import { PROJECT_STATUS_LABEL } from '@/utils/tools'
import type { PartnerRecord } from '@/interface/partnerInterface'

interface SelectableProject {
    id: number
    name: string
    status: string
    date_start: string | null
    date_end: string | null
}

const props = defineProps<{ partner: PartnerRecord }>()
const emit = defineEmits(['close', 'saved'])

const api = useApi()
const { ping } = useDialog()

const projects = ref<SelectableProject[]>([])
const selected = ref<number[]>((props.partner.projects ?? []).map(p => p.id))
const keyword = ref('')
const loading = ref(false)
const saving = ref(false)

const filtered = computed(() => {
    const word = keyword.value.trim().toLowerCase()
    if (!word) return projects.value
    return projects.value.filter(project => project.name.toLowerCase().includes(word))
})

const getProjects = async () => {
    loading.value = true
    try {
        const response = await api.get('/admin/partners/selectable-projects')
        projects.value = response?.projects ?? []
    } catch {
        // メッセージは useApi が表示済み
    } finally {
        loading.value = false
    }
}

const save = async () => {
    if (saving.value) return
    saving.value = true
    try {
        const response = await api.put(`/admin/partners/${props.partner.id}/projects`, {
            project_ids: selected.value,
        })
        if (!response) return

        ping(response.message)
        emit('saved', response.partner)
    } catch {
        // メッセージは useApi が表示済み
    } finally {
        saving.value = false
    }
}

onMounted(getProjects)
</script>

<style scoped>
.partner-projects {
    display: flex;
    flex-direction: column;
    gap: 14px;
    font-size: 13px;
}

.partner-projects__bar {
    display: flex;
    align-items: center;
    gap: 12px;
}

.partner-projects__search {
    flex: 1;
}

.partner-projects__count {
    color: var(--third-color);
    font-size: 12px;
    white-space: nowrap;
}

.partner-projects__list {
    display: flex;
    flex-direction: column;
    max-height: 46vh;
    overflow: auto;
    border: 1px solid var(--calendarBorder);
}

.partner-projects__item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    border-bottom: 1px solid var(--calendarBorder);
    cursor: pointer;
    user-select: none;
}

.partner-projects__item:last-child {
    border-bottom: 0;
}

.partner-projects__name {
    flex: 1;
}

.partner-projects__meta {
    color: var(--third-color);
    font-size: 11px;
}

.partner-projects__empty {
    padding: 30px 12px;
    color: var(--third-color);
    text-align: center;
}

.partner-projects__footer {
    display: flex;
    justify-content: center;
}
</style>
