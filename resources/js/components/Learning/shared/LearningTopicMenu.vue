<template>
    <TransitionGroup name="t-list" class="topic-container" tag="div" style="padding-bottom: 20px;">
        <button
            v-for="item in items"
            :key="item.id"
            type="button"
            :class="['topic-item', 'learning-topic-menu__item', {'inactive-theme': item.disabled}]"
            @click="emit('select', item)"
        >
            <span class="learning-topic-menu__title-row">
                <LearningStatusMark v-if="item.completed || item.tone" :tone="item.tone ?? 'complete'" />
                <span class="learning-topic-menu__text">
                    <span class="topic-title">{{ item.title }}</span>
                    <span v-for="line in item.meta" :key="line" class="learning-topic-menu__meta">{{ line }}</span>
                </span>
            </span>

            <span v-if="item.children?.length" class="learning-topic-menu__children">
                <span v-for="child in item.children" :key="child.id" class="learning-topic-menu__child">
                    <LearningStatusMark v-if="child.tone" :tone="child.tone" />
                    <span>{{ child.title }}</span>
                </span>
            </span>
        </button>
    </TransitionGroup>
</template>

<script setup lang="ts">
import LearningStatusMark from '@/components/Learning/shared/LearningStatusMark.vue'

export interface LearningTopicMenuChild {
    id: number | string
    title: string
    tone?: 'complete' | 'warning'
}

export interface LearningTopicMenuItem {
    id: number | string
    title: string
    value?: number
    disabled?: boolean
    completed?: boolean
    tone?: 'complete' | 'warning'
    meta?: string[]
    children?: LearningTopicMenuChild[]
}

defineProps<{
    items: LearningTopicMenuItem[]
}>()

const emit = defineEmits<{
    select: [item: LearningTopicMenuItem]
}>()
</script>

<style scoped>
.learning-topic-menu__item {
    border: 1px solid var(--formBorder);
    text-align: left;
}

.learning-topic-menu__title-row,
.learning-topic-menu__child {
    display: flex;
    align-items: center;
    gap: 5px;
}

.learning-topic-menu__text {
    display: flex;
    flex-direction: column;
    line-height: 1.5;
    min-width: 0;
}

.learning-topic-menu__meta {
    font-size: 12px;
}

.learning-topic-menu__children {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-top: 10px;
}

.learning-topic-menu__child span:last-child {
    overflow: hidden;
    text-overflow: ellipsis;
}
</style>
