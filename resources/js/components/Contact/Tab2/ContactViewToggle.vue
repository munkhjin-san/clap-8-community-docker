<template>
    <button 
        title="表示切替"  
        :class="['float-b']" 
        @click="emit('action')" 
        :style="{bottom: '70px'}"
    >
        <Grid v-if="type == 'table'"/>
        <List v-if="type == 'grid'"/>
    </button>
</template>
<script setup lang="ts">
import Grid from '@/components/Icons/Grid.vue';
import List from '@/components/Icons/List.vue';

const props = defineProps<{
    type: string
}>()
const emit = defineEmits<{
    action: []
}>()
</script>