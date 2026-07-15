<template>
    <div class="lcontrol">
        <div class="learning-theme-grid__items">
            <div
                v-for="theme in themes"
                :key="theme.id"
                class="theme-item learning-theme-grid__item"
            >
                <button
                    type="button"
                    class="learning-theme-grid__main"
                    @click="emit('open-theme', theme)"
                >
                    <div class="learning-theme-grid__theme-title">{{ theme.title }}</div>
                    <div class="learning-theme-grid__meta">
                        <span>アクティブ：{{ theme.active ? 'ON' : 'OFF' }}</span>
                    </div>
                    <div class="learning-theme-grid__meta">
                        <span>タイプ：{{ structureLabel(theme) }}</span>
                    </div>
                    <div class="learning-theme-grid__meta">
                        <span>アーカイブ：{{ theme.archive ? 'ON' : 'OFF' }}</span>
                    </div>
                    <div class="learning-theme-grid__meta">
                        <span>カテゴリー：{{ categoryLabel(theme) }}</span>
                    </div>
                    <div class="learning-theme-grid__meta">
                        <span>AI設定：{{ theme.ai_configs?.length || 0 }}件</span>
                    </div>
                </button>
                <div class="learning-theme-grid__menu">
                    <ItemMenu :items="themeMenuItems(theme)"/>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import ItemMenu from '@/components/Global/ItemMenu.vue'
import type { MenuList } from '@/interface/globalInterface'
import type { LearningTheme } from '@/types/learning'

defineProps<{
    themes: LearningTheme[]
}>()

const emit = defineEmits<{
    'open-theme': [theme: LearningTheme]
    'edit-theme': [theme: LearningTheme]
    'delete-theme': [id: number]
}>()

const themeMenuItems = (theme: LearningTheme): MenuList[] => [
    { title: '編集する', action: () => emit('edit-theme', theme) },
    { title: '削除する', action: () => emit('delete-theme', theme.id) },
]

const categoryLabel = (theme: LearningTheme) => {
    return theme.categories?.length
        ? theme.categories.map(category => category.name).join(' / ')
        : '未設定'
}

const isEnabled = (value: number | boolean | null | undefined) => {
    return value === true || value === 1
}

const structureLabel = (theme: LearningTheme) => {
    if (isEnabled(theme.portfolio)) return 'ポートフォリオ'
    if (isEnabled(theme.has_case_study)) return 'ケーススタディ'
    return '未設定'
}
</script>

<style scoped>
.learning-theme-grid__title{
    padding: 20px;
}

.learning-theme-grid__items{
    display: grid;
    grid-template-columns: repeat(3, minmax(200px, 1fr));
    gap: 20px;
    padding: 20px;
    margin: 0 20px;
    background: var(--background-color);
}

.learning-theme-grid__item{
    position: relative;
}

.learning-theme-grid__main{
    display: block;
    max-width: 90%;
    overflow: hidden;
    text-align: left;
    text-overflow: ellipsis;
    border: 0;
    background: transparent;
    color: inherit;
    padding: 0;
}

.learning-theme-grid__theme-title{
    font-size: 20px;
}

.learning-theme-grid__meta{
    margin-top: 15px;
    font-size: 12px;
    color: gray;
}

.learning-theme-grid__menu{
    position: absolute;
    right: 10px;
    top: 10px;
}
</style>
