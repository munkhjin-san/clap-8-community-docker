<template>
    <div>
        <div class="flex flex-col space-y-6 px-5 h-[calc(100vh-126px)] max-h-screen overflow-hidden relative">
            <div class="flex items-center gap-3 flex-wrap">
                <div class="flex items-center gap-2 flex-wrap">
                    <label class="text-xs text-[var(--primary-color)]">プロジェクト種別</label>
                    <CustomDropDown
                        v-model="selectedProjectTypeId"
                        :options="projectTypeOptions"
                    />
                </div>
            </div>
            <div class="overflow-auto">
                <table class="w-full text-sm text-[var(--primary-color)]">
                    <thead class="bg-[var(--background-color)] sticky top-0 z-10">
                        <tr>
                            <th class="text-left px-3 py-2">カテゴリー</th>
                            <th class="text-left px-3 py-2">項目</th>
                            <th class="text-left px-3 py-2">階層</th>
                            <th class="text-left px-3 py-2">操作</th>
                        </tr>
                    </thead>
                    <tbody v-if="rows.length">
                        <tr v-for="item in rows" :key="item.id">
                            <td class="px-3 py-2">{{ item.categoryLabel }}</td>
                            <td class="px-3 py-2">{{ item.label }}</td>
                            <td class="px-3 py-2">{{ item.parent_id ? '子項目' : '親項目' }}</td>
                            <td class="px-3 py-2">
                                <button class="text-xs px-2 py-1 border border-solid border-[var(--normalBorder)] mr-1 text-[var(--primary-color)]" @click="openEdit(item)">編集</button>
                                <button class="text-xs px-2 py-1 border border-solid border-[var(--normalBorder)] text-red-500" @click="deleteItem(item)">削除</button>
                            </td>
                        </tr>
                    </tbody>
                    <tbody v-else>
                        <tr>
                            <td
                                colspan="9"
                                class="py-16 !text-center text-sm text-[color:var(--muted-text,#9ca3af)]"
                            >
                                テンプレートが登録されていません。プロジェクト種別を選択してチェック項目を追加してください。
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <FloatButton title="科目追加" @action="openCreate">
                <template #icon>
                    <AddIcon size="15" fill="black"/>
                </template>
            </FloatButton>
        </div>
        <CheckItemCreate
            v-if="openModal"
            :edit-data="editData"
            :selected-project-type-id="selectedProjectTypeId"
            :available-parents="parentOptions"
            @close="openModal = false"
            @refresh="refresh"
        />
    </div>
</template>
<script lang="ts" setup>
import { ProjectType } from '@/interface/projectInterface';
import { computed, onMounted, ref, watch } from 'vue';
import CustomDropDown from './CustomDropDown.vue';
import { useApi } from '@/composables/api';
import FloatButton from '@/components/Global/FloatButton.vue';
import AddIcon from '@/components/Form/AddIcon.vue';
import CheckItemCreate from './CheckItemCreate.vue';

type TemplateItem = {
    id: number
    label: string
    parent_id?: number | null
    category?: { id: number; label: string } | null
    category_label?: string | null
    children?: TemplateItem[]
}

const openModal = ref(false)
const editData = ref<TemplateItem | null>(null)
const api = useApi()
const projectTypes = ref<ProjectType[]>([])
const selectedProjectTypeId = ref<number | null>(null)
const templates = ref<TemplateItem[]>([])

const projectTypeOptions = computed(() => projectTypes.value.map((type) => ({ id: type.id, name: type.label })))
const parentOptions = computed(() => templates.value.map((item) => ({ id: item.id, label: item.label })))
const rows = computed(() => templates.value.flatMap((item) => {
    const parentRow = {
        ...item,
        categoryLabel: item.category?.label ?? item.category_label ?? '未分類',
    }
    const childRows = (item.children ?? []).map((child) => ({
        ...child,
        categoryLabel: child.category?.label ?? child.category_label ?? item.category?.label ?? item.category_label ?? '未分類',
    }))
    return [parentRow, ...childRows]
}))

const getProjectTypes = async() => {
    const data = await api.get('/project_types')
    projectTypes.value = Array.isArray(data) ? data as ProjectType[] : []
    if (!selectedProjectTypeId.value && projectTypes.value.length) {
        selectedProjectTypeId.value = projectTypes.value[0].id
    }
}
const refresh = async() => {
    if (!selectedProjectTypeId.value) {
        templates.value = []
        return
    }
    const data = await api.get('/project_checkitem_templates', { project_type_id: selectedProjectTypeId.value })
    templates.value = Array.isArray(data) ? data as TemplateItem[] : []
}
const openEdit = (item: TemplateItem) => {
    editData.value = item
    openModal.value = true
}
const deleteItem = async(item: TemplateItem & { categoryLabel?: string }) => {
    const delmsg = `${item.categoryLabel ?? '未分類'}<br>${item.label}を削除しまか？`
    await api.del(`/delete_checkitem/${item.id}`, {}, {
        toast: '削除しました。',
        ask: delmsg,
    })
    refresh()
}
const openCreate = () => {
    openModal.value = true
    editData.value = null
}

onMounted(() => {
    getProjectTypes()
})

watch(selectedProjectTypeId, () => {
    refresh()
}, { immediate: true })
</script>
<style scoped>
table{
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    background-color: var(--background-color);
    font-size: 14px;
    box-sizing: border-box !important;
}
thead th {
  border-top: 1px solid var(--calendarBorder);
}
th, td {
    border-bottom: 1px solid var(--calendarBorder);
    padding: 8px;
    text-align: left;
    font-size: 13px;
    white-space: nowrap;
    border-right: 1px solid var(--calendarBorder);
}
th:first-of-type {
  border-left: 1px solid var(--calendarBorder);
}
td:first-of-type {
  border-left: 1px solid var(--calendarBorder);
}
</style>
