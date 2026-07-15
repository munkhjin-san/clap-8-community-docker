<template>
    <div class="learning-category-control">
        <div class="learning-category-control__form">
            <input
                v-model="draftName"
                type="text"
                class="learning-category-control__input"
                placeholder="カテゴリー名"
                @keyup.enter="saveDraft"
            />
            <LoaderButton
                :content="editingId ? '更新' : '追加'"
                @triggered="saveDraft"
            />
            <button
                v-if="editingId"
                type="button"
                class="learning-category-control__cancel"
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
                    <button
                        type="button"
                        class="lcc-default-btn"
                        :class="{ 'lcc-default-btn--on': category.is_default }"
                        @click="toggleDefault(category)"
                    >
                        <span class="lcc-default-dot" :class="{ 'lcc-default-dot--on': category.is_default }"></span>
                        デフォルト
                    </button>
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
import LoaderButton from '@/components/Global/LoaderButton.vue'
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

const toggleDefault = async(category: LearningThemeCategory) => {
    categories.value = await learningApi.setDefaultThemeCategory(category.id)
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

.learning-category-control__input{
    width: 100%;
    box-sizing: border-box;
    padding: 8px 12px;
    font-size: 13px;
    border: 1px solid var(--calendarBorder);
    background: var(--background-color);
    color: var(--primary-color);
}
.learning-category-control__input::placeholder{
    color: var(--third-color);
}

.learning-category-control__cancel{
    width: fit-content;
    font-size: 13px;
    padding: 8px 14px;
    border: 1px solid var(--calendarBorder);
    background: transparent;
    color: var(--primary-color);
    cursor: pointer;
}
.learning-category-control__cancel:hover{
    background: var(--bg3);
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

.lcc-default-btn{
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    padding: 4px 12px;
    border: 1px solid var(--calendarBorder);
    background: transparent;
    color: var(--third-color);
    cursor: pointer;
}
.lcc-default-btn--on{
    color: var(--primary-color);
    border-color: var(--primary-color);
}
.lcc-default-dot{
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: var(--check-inactive);
}
.lcc-default-dot--on{
    background: #2e9e4f;
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
