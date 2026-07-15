<template>
    <div class="learning-category-control">
        <div class="learning-category-control__form">
            <ShortInput
                v-model="draftName"
                place-holder="カテゴリー名"
                custom-class="full"
                type="text"
            />
            <button
                class="admin-button learning-category-control__save"
                type="button"
                @click="saveDraft"
            >
                {{ editingId ? '更新' : '追加' }}
            </button>
            <button
                v-if="editingId"
                class="commentEditButton learning-category-control__cancel"
                type="button"
                @click="resetDraft"
            >
                キャンセル
            </button>
        </div>

        <div class="learning-category-control__list">
            <div
                v-for="(category, index) in categories"
                :key="category.id"
                class="learning-category-control__row"
            >
                <div class="learning-category-control__name">{{ category.name }}</div>
                <div class="learning-category-control__actions">
                    <button @click="move(index, -1)" class="w-6 h-6 border border-solid border-1 border-[var(--formBorder)] rounded flex items-center justify-center">
                        <Back class="w-2 h-2 rotate-90" />
                    </button>
                    <button @click="move(index, 1)" class="w-6 h-6 border border-solid border-1 border-[var(--formBorder)] rounded flex items-center justify-center">
                        <Back class="w-2 h-2 rotate-[270deg]" />
                    </button>
                    <button @click="edit(category)" class="w-6 h-6 border border-solid border-1 border-[var(--formBorder)] rounded flex items-center justify-center">
                        <Edit class="w-3 h-3" />
                    </button>
                    <button @click="remove(category.id)" class="w-6 h-6 border border-solid border-1 border-[var(--formBorder)] rounded flex items-center justify-center">
                        <Trash class="w-3 h-3" />
                    </button>
                </div>
            </div>
            <div v-if="!categories.length" class="learning-category-control__empty">
                カテゴリーはありません。
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import ShortInput from '@/components/Form/ShortInput.vue'
import { useLearningApi } from '@/composables/learningApi'
import type { LearningTheme, LearningThemeCategory } from '@/types/learning'
import Back from '@/components/Icons/Back.vue';
import Edit from '@/components/Icons/Edit.vue';
import Trash from '@/components/Icons/Trash.vue';

const props = defineProps<{
    themes: LearningTheme[]
}>()

const learningApi = useLearningApi()
const categories = ref<LearningThemeCategory[]>([])
const draftName = ref('')
const editingId = ref<number | null>(null)

onMounted(() => {
    loadCategories()
})

const loadCategories = async() => {
    categories.value = await learningApi.getThemeCategories()
}

const resetDraft = () => {
    draftName.value = ''
    editingId.value = null
}

const saveDraft = async() => {
    const name = draftName.value.trim()
    if (!name) return

    await learningApi.saveThemeCategory({
        id: editingId.value,
        name,
    }, Boolean(editingId.value))
    resetDraft()
    await loadCategories()
}

const edit = (category: LearningThemeCategory) => {
    editingId.value = category.id
    draftName.value = category.name
}

const remove = async(id: number) => {
    await learningApi.deleteThemeCategory(id)
    if (editingId.value === id) {
        resetDraft()
    }
    await loadCategories()
}

const move = async(index: number, direction: -1 | 1) => {
    const nextIndex = index + direction
    if (nextIndex < 0 || nextIndex >= categories.value.length) return

    const next = [...categories.value]
    const [item] = next.splice(index, 1)
    next.splice(nextIndex, 0, item)
    categories.value = next
    await persistOrder()
}

const persistOrder = async() => {
    categories.value = await learningApi.reorderThemeCategories(
        categories.value.map(category => category.id),
    )
}
</script>

<style scoped>
.learning-category-control{
    display: flex;
    flex-direction: column;
    gap: 20px;
    padding: 20px;
    background: var(--background-color);
    margin: 0 20px;
}

.learning-category-control__form{
    align-items: center;
    display: grid;
    gap: 12px;
    grid-template-columns: minmax(220px, 1fr) auto auto;
}

.learning-category-control__save,
.learning-category-control__cancel{
    width: fit-content;
}

.learning-category-control__list{
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.learning-category-control__row{
    align-items: center;
    background: var(--background-color);
    border: 1px solid var(--bg3);
    display: flex;
    gap: 16px;
    justify-content: space-between;
    padding: 14px;
}

.learning-category-control__name{
    align-items: center;
    display: flex;
    gap: 10px;
}

.learning-category-control__actions{
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    justify-content: flex-end;
}

.learning-category-control__empty{
    color: var(--light-color);
    padding: 20px 0;
}

@media screen and (max-width: 700px) {
    .learning-category-control__form,
    .learning-category-control__row{
        align-items: stretch;
        grid-template-columns: 1fr;
    }

    .learning-category-control__row{
        flex-direction: column;
    }
}
</style>
