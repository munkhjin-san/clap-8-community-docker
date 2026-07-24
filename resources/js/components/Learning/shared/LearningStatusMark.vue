<template>
    <span :class="['learning-status-mark', `learning-status-mark--${tone}`]">
        <span class="learning-status-mark__dot"></span>
    </span>
</template>

<script setup lang="ts">
withDefaults(defineProps<{
    tone?: 'complete' | 'warning'
}>(), {
    tone: 'complete',
})
</script>

<style scoped>
/* One text-line tall so the dot lands on the first line of the adjacent text
   (the row uses align-items: flex-start), not centered against the whole block. */
.learning-status-mark {
    display: inline-flex;
    align-items: center;
    height: 1.5em;
    min-width: 10px;
    flex: none;
}

.learning-status-mark__dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: var(--check-inactive);
}

.learning-status-mark--complete .learning-status-mark__dot {
    background: rgb(100, 188, 68);
}

.learning-status-mark--warning .learning-status-mark__dot {
    background: rgb(255, 165, 0);
}
</style>
