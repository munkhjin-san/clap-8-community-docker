<template>
    <div>
        <button
            class="learning-theme-tabs__back"
            type="button"
            @click="emit('back')"
        >
            <Back />
            <span class="learning-theme-tabs__title">{{ theme?.title }}</span>
        </button>
        <div class="sub-tab-container learning-theme-tabs__tabs">
            <button
                v-for="tab in tabs"
                :key="tab.routeName"
                type="button"
                :class="['sub-tab-item', { 'selected-sub-tab': currentRouteName === tab.routeName }]"
                @click="emit('open-tab', tab.routeName)"
            >
                {{ tab.label }}
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import Back from '@/components/Icons/Back.vue'
import type { LearningTheme } from '@/types/learning'
import { isEnabled } from '@/utils/learningProgress'

const props = defineProps<{
    theme: LearningTheme | null
    currentRouteName: string | symbol | null | undefined
}>()

const emit = defineEmits<{
    back: []
    'open-tab': [routeName: string]
}>()

const tabs = computed(() => [
    { routeName: 'content', label: 'コンテンツ' },
    {
        routeName: isEnabled(props.theme?.has_case_study) ? 'case-study' : 'trainee',
        label: '参加者',
    },
    { routeName: 'non-trainee', label: '未参加者' },
    { routeName: 'assistant', label: 'AIアシスタント' },
])
</script>

<style scoped>
.learning-theme-tabs__back{
    display: flex;
    align-items: center;
    gap: 15px;
    position: sticky;
    top: 0;
    z-index: 7;
    width: 100%;
    padding: 20px;
    border: 0;
    background: var(--bg3);
    color: inherit;
    text-align: left;
}

.learning-theme-tabs__title{
    font-size: 16px;
    font-weight: 400;
}

.learning-theme-tabs__tabs{
    margin: 0 20px;
    background: var(--background-color);

}
</style>
