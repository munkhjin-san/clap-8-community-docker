<template>
    <div class="trainee-preview-cell">
        <button
            type="button"
            class="trainee-preview-cell__summary"
            @click.stop="toggle"
        >
            <slot name="summary" />
        </button>
        <div
            v-if="open"
            class="trainee-preview-cell__popup"
            :class="{ 'trainee-preview-cell__popup--left': align === 'left' }"
        >
            <slot />
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useMenuStore } from '@/store/menu'

const props = withDefaults(defineProps<{
    menuName: string
    recordId: number
    align?: 'left' | 'right'
}>(), {
    align: 'right',
})

const menu = useMenuStore()

const open = computed(() => menu.name === props.menuName && menu.id === props.recordId)

const toggle = () => {
    menu.setMenu({
        id: props.recordId,
        name: props.menuName,
    })
}
</script>

<style scoped>
.trainee-preview-cell{
    max-height: 40px;
    line-height: 1.5;
    position: relative;
}

.trainee-preview-cell__summary{
    display: block;
    width: 100%;
    max-height: 40px;
    overflow: hidden;
    white-space: break-spaces;
    word-break: break-all;
    text-align: left;
    background: transparent;
    color: inherit;
    border: 0;
    padding: 0;
    line-height: inherit;
}

.trainee-preview-cell__popup{
    position: absolute;
    top: 0;
    left: auto;
    right: 0;
    z-index: 5;
    width: max-content;
    max-width: 40vw;
    overflow: hidden;
    white-space: break-spaces;
    word-break: break-all;
    line-height: 1.5;
    background: var(--background-color);
    border: 1px solid var(--calendarBorder);
    padding: 15px;
    text-align: left;
}

.trainee-preview-cell__popup--left{
    left: 0;
    right: auto;
}
</style>
