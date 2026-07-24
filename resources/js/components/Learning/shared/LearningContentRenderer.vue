<template>
    <div class="learning-content-renderer bg-[var(--background-color)]">
        <template v-for="(segment, index) in segments" :key="index">
            <div
                v-if="segment.type === 'html'"
                class="learning-content-renderer__html"
                v-html="segment.html"
            ></div>
            <video
                v-else-if="segment.type === 'video'"
                class="ls-video"
                controls
            >
                <source :src="segment.src">
            </video>
            <LearningPdfViewer
                v-else
                :src="segment.src"
            />
        </template>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import LearningPdfViewer from '@/components/Learning/shared/LearningPdfViewer.vue'
import { parseLearningContent } from '@/utils/learningContent'

const props = defineProps<{
    content?: string | null
}>()

const segments = computed(() => parseLearningContent(props.content))
</script>

<style scoped>
.learning-content-renderer {
    min-width: 0;
    word-break: break-word;
}

.learning-content-renderer__html :deep(p:last-child) {
    margin-bottom: 0;
}
</style>
