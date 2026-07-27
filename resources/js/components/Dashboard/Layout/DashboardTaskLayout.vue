<template>
    <BaseLayout
        :title="data.title" 
        :count="data.data.length" 
        :fullscreen="fullscreen" 
        :type="data.type" 
        :can-resize="data.canResize"
        :can-fullscreen="data.canFullscreen"
        @toggle="(el, title) =>emit('toggle', el, data.type)" 
        @resize="emit('resize', data.type)"
    >
    <template #icon>
        <TaskIcon size="17" class="mr-1"/>
    </template>
    <div v-if="!fullscreen" class="m-5">   
        <ExpansionGrid class="gap-x-4" :col="Number(data.col?.split('-')[2] ?? 1)">
            <ExpansionPanelItem
                hide-actions
                static
                :tile="true"
                class="rm-p"
                title-class="task-panel"
                v-for="(task, index) in data.data"
                :key="task.id ?? index"
                :value="task.id ?? index"
            >
                <template #title>
                    <ListBox @getBoardTasks="emit('refreshData', data.type)" :item="task" boxClass="w-full h-full" :isBoard="false" mode="minimal" />
                </template>
                <template #body>
                    <ListBox @getBoardTasks="emit('refreshData', data.type)" :item="task" boxClass="" :isBoard="false" />
                </template>
            </ExpansionPanelItem>
        </ExpansionGrid>
    </div> 
        <div v-if="fullscreen" class="px-4">
            <div v-for="task in data.data" :key="task.id" class="py-2 text-[14px] border-b border-[var(--border-color)] last:border-b-0">
                <ListBox :item="task" boxClass="" :isBoard="false"/>
            </div>
        </div>
    </BaseLayout>
</template>
 
<script setup lang="ts">
import type { DashboardTaskCard } from '@/interface/dashboard'
import ListBox from '@/components/Task/List/ListBox.vue';
import BaseLayout from './BaseLayout.vue';
import ExpansionGrid from '../ExpansionGrid.vue';
import ExpansionPanelItem from '../ExpansionPanelItem.vue';
import TaskIcon from '@/components/Icons/TaskIcon.vue';

const props = defineProps<{
    data: DashboardTaskCard,
    fullscreen: boolean
}>()

const emit = defineEmits<{
    resize: [type: string]
    toggle: [el: HTMLElement | null, title: string]
    refreshData: [key: string]
}>()
  

defineExpose({
    cardType: props.data.type,
})
</script>
