<template>
    <p :class="titleClass" :style="titleStyle">{{boardTitle}}</p>
</template>

<script setup lang="ts">
import { type Board } from '@/interface/globalInterface';
import { useBoardTitle } from '@/composables/boardTitle';
    const props = defineProps<{
        item: Board
        titleClass?: string 
        titleStyle?: string
    }>()
    const boardTitle = useBoardTitle(props.item)   
</script>
