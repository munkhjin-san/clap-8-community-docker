<template>
    <header class="learning-lesson-header">
        <button class="learning-lesson-header__back" type="button" @click="emit('back')">
            <Back />
        </button>
        <nav class="learning-lesson-header__breadcrumbs">
            <span v-for="(item, index) in breadcrumbs" :key="index" class="learning-lesson-header__crumb">
                <button type="button" @click="emit('navigate', item.route)">
                    {{ item.label }}
                </button>
                <span v-if="index + 1 !== breadcrumbs.length">／</span>
            </span>
        </nav>
    </header>
</template>

<script setup lang="ts">
import type { RouteLocationRaw } from 'vue-router'
import Back from '@/components/Icons/Back.vue'

export interface LearningBreadcrumbItem {
    label: string
    route: RouteLocationRaw
}

defineProps<{
    breadcrumbs: LearningBreadcrumbItem[]
}>()

const emit = defineEmits<{
    back: []
    navigate: [route: RouteLocationRaw]
}>()
</script>

<style scoped>
.learning-lesson-header {
    height: 50px;
    display: flex;
    align-items: center;
    position: sticky;
    top: 0;
    background: var(--background-color);
    z-index: 3;
    border-bottom: 1px solid var(--formBorder);
    box-sizing: border-box !important;
}

.learning-lesson-header__back {
    height: 50px;
    width: 50px;
    min-width: 50px;
    border: 0;
    background: transparent;
    color: var(--primary-color);
    fill: var(--primary-color);
    cursor: pointer;
    display: flex;
    justify-content: center;
    align-items: center;
}

.learning-lesson-header__breadcrumbs {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 5px;
}

.learning-lesson-header__crumb {
    display: inline-flex;
    gap: 5px;
    align-items: center;
}

.learning-lesson-header__crumb button {
    border: 0;
    background: transparent;
    color: var(--primary-color);
    cursor: pointer;
    padding: 0;
}
</style>
