<template>
    <div ref="girdParent">
        <!-- <div ref="girdParent" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-5"> -->
        <masonry-wall :items="members" :ssr-columns="1" :column-width="200" :gap="16">
            <template #default="{ item, index }">
            <DailyMessageItem 
                :left-edge="horizontalLimit.left" 
                :right-edge="horizontalLimit.right" 
                :user="item" 
                :key="item.id"
                @refresh="(data) => emit('refresh', data)"
            />        
            </template>
        </masonry-wall>    
        <!-- </div> -->
    </div>
</template>
<script setup lang="ts">
import { computed, useTemplateRef } from 'vue';
import DailyMessageItem from './DailyMessageItem.vue';
import { DailyMessageUser } from '@/interface/globalInterface';
defineProps<{
    members: DailyMessageUser[];
}>()
const emit = defineEmits<{
    refresh: [data: DailyMessageUser]
}>()

const gridParent = useTemplateRef('girdParent');

const horizontalLimit = computed(() => {
    if(!gridParent.value) {
        return {
            left: 0, right: 0
        }
    }
    const rect = gridParent.value.getBoundingClientRect();
    return {
        left: rect.left,
        right: rect.right
    }
})
</script>