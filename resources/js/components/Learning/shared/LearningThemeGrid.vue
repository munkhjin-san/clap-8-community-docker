<template>
    <div :class="['learning-theme-grid', { 'learning-theme-grid--single': themes.length === 1 }]">
        <LearningThemeCard
            v-for="theme in themes"
            :key="theme.id"
            :theme="theme"
            @select="emit('select', $event)"
        />
    </div>
</template>

<script setup lang="ts">
import type { LearningTheme } from '@/types/learning'
import LearningThemeCard from './LearningThemeCard.vue'

defineProps<{
    themes: LearningTheme[]
}>()

const emit = defineEmits<{
    select: [theme: LearningTheme]
}>()
</script>

<style scoped>
.learning-theme-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(360px, 1fr));
    padding: 0 20px 20px 20px;
    gap: 28px;
}

.learning-theme-grid--single {
    grid-template-columns: minmax(360px, 440px);
    justify-content: start;
}

.learning-theme-grid > * {
    box-sizing: border-box;
    min-width: 0;
}

@media screen and (max-width: 959px) {
    .learning-theme-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }
}
</style>
