<template>
    <p :class="titleClass" :style="titleStyle">{{boardTitle}}</p>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useAuthUserStore } from '@/store/auth'
import { type Board } from '@/interface/globalInterface';
import { DateTime } from 'luxon';
import { useBoardTitle } from '@/composables/boardTitle';
    const auth = useAuthUserStore()
    const props = defineProps<{
        item: Board
        titleClass?: string 
        titleStyle?: string
    }>()
    const boardTitle = useBoardTitle(props.item)   
</script>
