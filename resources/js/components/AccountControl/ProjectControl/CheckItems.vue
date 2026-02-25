<template>
    <div>
        <div class="flex flex-col space-y-6 px-5 h-[calc(100vh-126px)] max-h-screen overflow-hidden relative">
            <div class="flex items-center gap-3 flex-wrap">
                <div class="flex items-center gap-2 flex-wrap">
                    <label class="text-xs text-[var(--primary-color)]">プロジェクト</label>
                    <CustomDropDown 
                        v-model="selectedProjectId"
                        :options="projects"
                    />
                </div>
                <button @click="defaultData" class="px-3 py-1 border border-solid border-[var(--normalBorder)] hover:border-[var(--hoverBorder)] text-xs">
                    データ追加
                </button>
            </div>
            <div class="overflow-auto">
                <table class="w-full text-sm text-[var(--primary-color)]">
                    <thead class="bg-[var(--background-color)] sticky top-0 z-10">
                        <tr>
                        <th class="text-left px-3 py-2">カテゴリー</th>
                        <th class="text-left px-3 py-2">項目</th>
                        <th class="text-left px-3 py-2">ステータス</th>
                        <th class="text-left px-3 py-2">操作</th>
                        </tr>
                    </thead>
                    <tbody v-if="checkItems && checkItems.length">
                        <tr v-for="item in checkItems" :key="item.id">
                        <td class="px-3 py-2">{{ item.category }}</td>
                        <td class="px-3 py-2">{{ item.label }}</td>
                        <td class="px-3 py-2">{{ statusLabel[item.status] }}</td>
                        
                        <td class="px-3 py-2">
                            <button class="text-xs px-2 py-1 border border-solid border-[var(--normalBorder)] mr-1" @click="openEdit(item)">編集</button>
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
                            科目が登録されていません。左上のプロジェクトを選択するか、科目を追加してください。
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
            <Transition name="modalFade">
                <div class="cal-month-loader" style="height: 100%; top: 20px;" v-if="dataLoader">
                    <div id="loaderMini">
                        <div class="spinner-mini" style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
                    </div>
                </div>
            </Transition>
        </div>
        <CheckItemCreate 
            v-if="checkItems?.length && openModal"
            :edit-data="editData"
            :selectedProjectId="selectedProjectId"
            @close="openModal = false"
            @refresh="refresh"
        />
    </div>
</template>
<script lang="ts" setup>
import { useProject } from '@/composables/project';
import { Project, ProjectCheckItem } from '@/interface/projectInterface';
import { computed, ref } from 'vue';
import CustomDropDown from './CustomDropDown.vue';
import { useApi } from '@/composables/api';
import FloatButton from '@/components/Global/FloatButton.vue';
import AddIcon from '@/components/Form/AddIcon.vue';
import CheckItemCreate from './CheckItemCreate.vue';
const statusLabel = {
    done: '完了',
    pending: '未定',
    na: '対象外'
}
const openModal = ref(false)
const dataLoader = ref(false)
const editData = ref<ProjectCheckItem | null>(null)
const { projectList, refreshProject } = useProject()
const projects = computed(() => {
    return projectList.value.map((p: Project) => ({id: p.id, name: p.name, checkitems: p.checkitems}))
})
const api = useApi()
const selectedProjectId = ref<number | null>(projects.value?.[0]?.id ?? null)
const checkItems = computed(() => {
    if (!selectedProjectId.value) return
    return projects.value.find(p => p.id === selectedProjectId.value)?.checkitems
})
const openEdit = (item: ProjectCheckItem) => {
    editData.value = item
    openModal.value = true
}
const deleteItem = async(item: ProjectCheckItem) => {
    const delmsg = `${item.category}<br>${item.label}を削除しまか？`
    await api.del(`/delete_checkitem/${item.id}`, {}, {
        toast: '削除しました。',
        ask: delmsg
    })
    refresh()
}
const openCreate = () => {
    openModal.value = true
    editData.value = null
}
const defaultData = async() => {
    if (!selectedProjectId.value) return
    await api.post('/ensure_checkitems', { id: selectedProjectId.value}, {
        toast: 'デフォルトデータを追加しました。',
        loadingRef: dataLoader
    })
    refresh()
}
const refresh = async() => {
    if (!selectedProjectId.value) return
    refreshProject(selectedProjectId.value)
}
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