<template>
    <div class="px-5 py-5 h-[calc(100vh-126px)] overflow-auto">
        <div class="max-w-[420px]">
            <p class="text-[14px] mb-[10px]">チェックカテゴリ</p>
            <div class="flex gap-[10px]">
                <input
                    v-model="label"
                    type="text"
                    class="custom-a-input"
                    placeholder="チェックカテゴリを入力"
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
                v-for="category in categories"
                :key="category.id"
                class="flex items-center gap-[10px] bg-[var(--background-color)] border border-solid border-[var(--calendarBorder)] px-[15px] py-[12px]"
            >
                <span>{{ category.label }}</span>
                <button type="button" class="ml-auto text-[12px]" @click="edit(category)">編集</button>
                <button
                    type="button"
                    class="text-[12px] text-red-500"
                    @click="remove(category.id)"
                >
                    削除
                </button>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { useApi } from '@/composables/api'
import { onMounted, ref } from 'vue'

type CheckItemCategory = {
    id: number
    key: string
    label: string
    name?: string
}

const api = useApi()
const categories = ref<CheckItemCategory[]>([])
const label = ref('')
const editingId = ref<number | null>(null)

const load = async() => {
    const data = await api.get('/check_item_categories')
    categories.value = Array.isArray(data) ? data as CheckItemCategory[] : []
}
const save = async() => {
    if (!label.value.trim()) return
    await api.post('/check_item_categories', {
        id: editingId.value,
        label: label.value.trim(),
    }, {
        toast: '保存しました。',
    })
    label.value = ''
    editingId.value = null
    load()
}
const edit = (category: CheckItemCategory) => {
    editingId.value = category.id
    label.value = category.label
}
const remove = async(id: number) => {
    await api.del(`/check_item_categories/${id}`, {}, {
        ask: '削除しますか？',
        toast: '削除しました。',
    })
    load()
}

onMounted(() => {
    load()
})
</script>
