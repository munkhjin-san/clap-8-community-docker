<template>
    <Modal size="large" @close="emit('close')">
        <template #title>
            <p>インシデント設定</p>
        </template>

        <template #content>
            <div class="incident-settings">
                <div class="incident-settings-tabs">
                    <button
                        type="button"
                        :class="{ active: activeTab === 'statuses' }"
                        @click="activeTab = 'statuses'"
                    >
                        ステータス
                    </button>
                    <button
                        type="button"
                        :class="{ active: activeTab === 'categories' }"
                        @click="activeTab = 'categories'"
                    >
                        区分
                    </button>
                    <button
                        type="button"
                        :class="{ active: activeTab === 'punishments' }"
                        @click="activeTab = 'punishments'"
                    >
                        懲罰区分
                    </button>
                    <div v-if="loading" class="ml-auto text-[12px] text-[gray]">読み込み中...</div>
                </div>

                <div v-show="activeTab === 'statuses'">
                    <div ref="statusesSortParent" class="incident-settings-list">
                        <div
                            v-for="status in statuses"
                            :key="status.id"
                            class="incident-settings-row sortable-status"
                        >
                            <DragHandle class="status-handler" />
                            <input v-model="status.name" type="text" placeholder="ステータス名" />
                            <button type="button" @click="updateStatus(status)">保存</button>
                            <button type="button" @click="deleteStatus(status)">削除</button>
                        </div>
                    </div>
                    <div class="incident-settings-create">
                        <input v-model="newStatusName" type="text" placeholder="新しいステータス" @keydown.enter.prevent="createStatus" />
                        <button type="button" @click="createStatus">追加</button>
                    </div>
                </div>

                <div v-show="activeTab === 'categories'">
                    <div ref="categoriesSortParent" class="incident-settings-list">
                        <div
                            v-for="category in categories"
                            :key="category.id"
                            class="incident-settings-row sortable-category"
                        >
                            <DragHandle class="category-handler" />
                            <input v-model="category.name" type="text" placeholder="区分名" />
                            <input v-model="category.description" type="text" placeholder="説明" />
                            <button type="button" @click="updateCategory(category)">保存</button>
                            <button type="button" @click="deleteCategory(category)">削除</button>
                        </div>
                    </div>
                    <div class="incident-settings-create">
                        <input v-model="newCategoryName" type="text" placeholder="新しい区分" @keydown.enter.prevent="createCategory" />
                        <input v-model="newCategoryDescription" type="text" placeholder="説明" @keydown.enter.prevent="createCategory" />
                        <button type="button" @click="createCategory">追加</button>
                    </div>
                </div>

                <div v-show="activeTab === 'punishments'">
                    <div ref="punishmentsSortParent" class="incident-settings-list">
                        <div
                            v-for="punishment in punishments"
                            :key="punishment.id"
                            class="incident-settings-row sortable-punishment"
                        >
                            <DragHandle class="punishment-handler" />
                            <input v-model="punishment.name" type="text" placeholder="懲罰区分名" />
                            <input v-model="punishment.description" type="text" placeholder="説明" />
                            <button type="button" @click="updatePunishment(punishment)">保存</button>
                            <button type="button" @click="deletePunishment(punishment)">削除</button>
                        </div>
                    </div>
                    <div class="incident-settings-create">
                        <input v-model="newPunishmentName" type="text" placeholder="新しい懲罰区分" @keydown.enter.prevent="createPunishment" />
                        <input v-model="newPunishmentDescription" type="text" placeholder="説明" @keydown.enter.prevent="createPunishment" />
                        <button type="button" @click="createPunishment">追加</button>
                    </div>
                </div>
            </div>
        </template>
    </Modal>
</template>

<script setup lang="ts">
import { h, nextTick, onMounted, ref, useTemplateRef, watch } from 'vue';
import { moveArrayElement, useSortable } from '@vueuse/integrations/useSortable';
import Modal from '@/components/Global/Modal.vue';
import { useApi } from '@/composables/api';
import { useDialog } from '@/composables/dialog';

type IncidentSettingStatus = {
    id: number
    name: string
    sort_order: number | null
}

type IncidentSettingCategory = {
    id: number
    name: string
    description: string | null
    sort_order: number | null
}

type IncidentSettingPunishment = {
    id: number
    name: string
    description: string | null
    sort_order: number | null
}

const DragHandle = (props: { class?: string }) => h('button', {
    type: 'button',
    class: ['incident-settings-handle', props.class],
    title: '並び替え',
}, '⋮⋮')

const emit = defineEmits<{
    close: []
    updated: []
}>()

const api = useApi()
const dialog = useDialog()
const activeTab = ref<'statuses' | 'categories' | 'punishments'>('statuses')
const loading = ref(false)
const statuses = ref<IncidentSettingStatus[]>([])
const categories = ref<IncidentSettingCategory[]>([])
const punishments = ref<IncidentSettingPunishment[]>([])
const newStatusName = ref('')
const newCategoryName = ref('')
const newCategoryDescription = ref('')
const newPunishmentName = ref('')
const newPunishmentDescription = ref('')
const statusesSortParent = useTemplateRef<HTMLElement | null>('statusesSortParent')
const categoriesSortParent = useTemplateRef<HTMLElement | null>('categoriesSortParent')
const punishmentsSortParent = useTemplateRef<HTMLElement | null>('punishmentsSortParent')

const fetchSettings = async () => {
    loading.value = true
    const data = await api.get('/incident_settings')
    statuses.value = data?.statuses ?? []
    categories.value = data?.categories ?? []
    punishments.value = data?.punishments ?? []
    loading.value = false
    emit('updated')
}

const persistStatusOrder = async () => {
    await api.post('/incident_statuses/reorder', { ids: statuses.value.map(status => status.id) })
    await fetchSettings()
}

const persistCategoryOrder = async () => {
    await api.post('/incident_categories/reorder', { ids: categories.value.map(category => category.id) })
    await fetchSettings()
}

const persistPunishmentOrder = async () => {
    await api.post('/incident_punishments/reorder', { ids: punishments.value.map(punishment => punishment.id) })
    await fetchSettings()
}

const sortableStatuses = useSortable(statusesSortParent, statuses, {
    animation: 150,
    handle: '.status-handler',
    draggable: '.sortable-status',
    watchElement: true,
    disabled: true,
    onEnd: (event: { oldIndex?: number | null; newIndex?: number | null }) => {
        moveSortItem(statuses.value, event, () => persistStatusOrder())
    },
})

const sortableCategories = useSortable(categoriesSortParent, categories, {
    animation: 150,
    handle: '.category-handler',
    draggable: '.sortable-category',
    watchElement: true,
    disabled: true,
    onEnd: (event: { oldIndex?: number | null; newIndex?: number | null }) => {
        moveSortItem(categories.value, event, () => persistCategoryOrder())
    },
})

const sortablePunishments = useSortable(punishmentsSortParent, punishments, {
    animation: 150,
    handle: '.punishment-handler',
    draggable: '.sortable-punishment',
    watchElement: true,
    disabled: true,
    onEnd: (event: { oldIndex?: number | null; newIndex?: number | null }) => {
        moveSortItem(punishments.value, event, () => persistPunishmentOrder())
    },
})

const moveSortItem = <T,>(
    list: T[],
    event: { oldIndex?: number | null; newIndex?: number | null },
    persist: () => Promise<void>
) => {
    const oldIndex = typeof event.oldIndex === 'number' ? event.oldIndex : -1
    const newIndex = typeof event.newIndex === 'number' ? event.newIndex : -1
    if (oldIndex < 0 || newIndex < 0 || oldIndex === newIndex) return
    if (oldIndex >= list.length || newIndex >= list.length) return

    moveArrayElement(list, oldIndex, newIndex, event as any)
    nextTick(() => persist())
}

watch(
    () => loading.value,
    (isLoading) => {
        sortableStatuses.option('disabled', isLoading)
        sortableCategories.option('disabled', isLoading)
        sortablePunishments.option('disabled', isLoading)
    },
    { immediate: true }
)

const createStatus = async () => {
    const name = newStatusName.value.trim()
    if (!name) {
        dialog.ping('ステータス名を入力してください。')
        return
    }

    await api.post('/incident_status', { name }, { toast: '追加しました' })
    newStatusName.value = ''
    await fetchSettings()
}

const updateStatus = async (status: IncidentSettingStatus) => {
    const name = status.name.trim()
    if (!name) {
        dialog.ping('ステータス名を入力してください。')
        return
    }

    await api.put('/incident_status', { id: status.id, name }, { toast: '保存しました' })
    await fetchSettings()
}

const deleteStatus = async (status: IncidentSettingStatus) => {
    const confirmed = await dialog.ask('ステータスを削除しますか？')
    if (!confirmed.value) return

    await api.del('/incident_status', { id: status.id }, { toast: '削除しました' })
    await fetchSettings()
}

const createCategory = async () => {
    const name = newCategoryName.value.trim()
    if (!name) {
        dialog.ping('区分名を入力してください。')
        return
    }

    await api.post('/incident_category', {
        name,
        description: newCategoryDescription.value.trim() || null,
    }, { toast: '追加しました' })
    newCategoryName.value = ''
    newCategoryDescription.value = ''
    await fetchSettings()
}

const updateCategory = async (category: IncidentSettingCategory) => {
    const name = category.name.trim()
    if (!name) {
        dialog.ping('区分名を入力してください。')
        return
    }

    await api.put('/incident_category', {
        id: category.id,
        name,
        description: category.description?.trim() || null,
    }, { toast: '保存しました' })
    await fetchSettings()
}

const deleteCategory = async (category: IncidentSettingCategory) => {
    const confirmed = await dialog.ask('区分を削除しますか？')
    if (!confirmed.value) return

    await api.del('/incident_category', { id: category.id }, { toast: '削除しました' })
    await fetchSettings()
}

const createPunishment = async () => {
    const name = newPunishmentName.value.trim()
    if (!name) {
        dialog.ping('懲罰区分名を入力してください。')
        return
    }

    await api.post('/incident_punishment', {
        name,
        description: newPunishmentDescription.value.trim() || null,
    }, { toast: '追加しました' })
    newPunishmentName.value = ''
    newPunishmentDescription.value = ''
    await fetchSettings()
}

const updatePunishment = async (punishment: IncidentSettingPunishment) => {
    const name = punishment.name.trim()
    if (!name) {
        dialog.ping('懲罰区分名を入力してください。')
        return
    }

    await api.put('/incident_punishment', {
        id: punishment.id,
        name,
        description: punishment.description?.trim() || null,
    }, { toast: '保存しました' })
    await fetchSettings()
}

const deletePunishment = async (punishment: IncidentSettingPunishment) => {
    const confirmed = await dialog.ask('懲罰区分を削除しますか？')
    if (!confirmed.value) return

    await api.del('/incident_punishment', { id: punishment.id }, { toast: '削除しました' })
    await fetchSettings()
}

onMounted(() => {
    fetchSettings()
})
</script>

<style scoped lang="scss">
.incident-settings{
    color: var(--primary-color);
}

.incident-settings-tabs{
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 16px;
}

.incident-settings-tabs button,
.incident-settings-row button,
.incident-settings-create button{
    border: 1px solid var(--formBorder);
    background: var(--background-color);
    color: var(--primary-color);
    padding: 8px 12px;
    font-size: 12px;
}

.incident-settings-tabs button.active{
    background: var(--bg3);
}

.incident-settings-list{
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.incident-settings-row,
.incident-settings-create{
    display: flex;
    align-items: center;
    gap: 8px;
    border: 1px solid var(--formBorder);
    padding: 10px;
}

.incident-settings-create{
    margin-top: 14px;
    background: var(--bg3);
}

.incident-settings-row input,
.incident-settings-create input{
    min-width: 0;
    flex: 1;
    border: 1px solid var(--formBorder);
    background: var(--background-color);
    color: var(--primary-color);
    padding: 8px 10px;
}

.incident-settings-handle{
    width: 30px;
    cursor: grab;
    color: gray;
    letter-spacing: -3px;
}
</style>
