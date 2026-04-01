<template>
    <div class="px-5 py-5 h-[calc(100vh-126px)] overflow-auto">
        <div class="max-w-[420px]">
            <p class="text-[14px] mb-[10px]">プロジェクト種別</p>
            <div class="flex gap-[10px]">
                <input
                    v-model="label"
                    type="text"
                    class="custom-a-input"
                    placeholder="プロジェクト種別を入力"
                >
                <button
                    type="button"
                    class="px-[15px] border border-solid border-[var(--normalBorder)]"
                    @click="save"
                >
                    保存
                </button>
            </div>
        </div>
        <div class="mt-[30px] flex flex-col gap-[10px]">
            <div
                v-for="type in projectTypes"
                :key="type.id"
                class="flex items-center gap-[10px] bg-[var(--background-color)] border border-solid border-[var(--calendarBorder)] px-[15px] py-[12px]"
            >
                <span>{{ type.label }}</span>
                <button class="ml-auto text-[12px]" @click="edit(type)">編集</button>
                <button
                    v-if="type.key !== 'default'"
                    class="text-[12px] text-red-500"
                    @click="remove(type.id)"
                >
                    削除
                </button>
            </div>
        </div>
    </div>
</template>
<script setup lang="ts">
import { useApi } from '@/composables/api'
import type { ProjectType } from '@/interface/projectInterface'
import { onMounted, ref } from 'vue'

const api = useApi()
const projectTypes = ref<ProjectType[]>([])
const label = ref('')
const editingId = ref<number | null>(null)

const load = async() => {
    const data = await api.get('/project_types')
    projectTypes.value = Array.isArray(data) ? data as ProjectType[] : []
}
const save = async() => {
    if (!label.value.trim()) return
    await api.post('/project_types', {
        id: editingId.value,
        label: label.value.trim(),
    }, {
        toast: '保存しました。',
    })
    label.value = ''
    editingId.value = null
    load()
}
const edit = (type: ProjectType) => {
    editingId.value = type.id
    label.value = type.label
}
const remove = async(id: number) => {
    await api.del(`/project_types/${id}`, {}, {
        ask: '削除しますか？',
        toast: '削除しました。',
    })
    load()
}

onMounted(() => {
    load()
})
</script>
