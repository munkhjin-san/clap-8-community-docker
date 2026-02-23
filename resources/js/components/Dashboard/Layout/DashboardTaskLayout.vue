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
        <svg class="mr-1 h-[15px] appIcon" version="1.1" xmlns="http://www.w3.org/2000/svg" width="17" viewBox="0 0 37 34">
            <path d="M36.297 0.493c-0.529-0.407-1.289-0.312-1.742 0.177l-2.463 2.656-2.479 2.698c-1.644 1.805-3.295 3.607-4.927 5.425-1.633 1.815-3.274 3.625-4.9 5.446-0.906 1.016-1.818 2.030-2.726 3.046-0.293 0.329-0.814 0.303-1.073-0.054-0.061-0.083-0.124-0.169-0.187-0.252l-0.538-0.737-1.64-2.19c-0.726-0.977-1.471-1.94-2.22-2.9l-1.134-1.428c-0.384-0.472-0.767-0.947-1.16-1.413-0.435-0.515-1.21-0.637-1.791-0.225-0.567 0.401-0.704 1.19-0.355 1.792 0.296 0.513 0.607 1.020 0.914 1.528l0.961 1.551c0.652 1.030 1.306 2.056 1.978 3.069l1.509 2.284 0.509 0.755c0.68 1.007 1.366 2.011 2.070 3.003l0.082 0.115c0.095 0.133 0.207 0.252 0.339 0.36 0.794 0.645 1.97 0.495 2.63-0.283 1.569-1.848 3.105-3.724 4.657-5.585 1.564-1.876 3.113-3.766 4.667-5.649 1.558-1.882 3.096-3.779 4.641-5.67l2.304-2.852 2.291-2.858c0.436-0.547 0.358-1.364-0.22-1.809z"></path>
            <path d="M30.798 13.688c-0.736 0.045-1.297 0.682-1.307 1.417l-0.182 13.496c-0.004 0.298-0.247 0.532-0.545 0.527-1.719-0.029-3.439-0.041-5.158-0.055l-7.281-0.017-7.281-0.001-5.073 0.015c-0.257 0-0.465-0.21-0.462-0.466 0.019-1.7 0.019-3.398 0.019-5.098l-0.026-7.281-0.026-7.279-0.033-5.239c-0.001-0.21 0.168-0.38 0.378-0.381 1.558-0.010 3.114-0.023 4.671-0.031l20.184-0.204c0.809-0.008 1.46-0.691 1.409-1.517-0.046-0.754-0.701-1.326-1.457-1.334l-20.136-0.204c-2.244-0.012-4.486-0.037-6.729-0.038-0.915 0-1.66 0.739-1.667 1.655v0.010l-0.049 7.281-0.024 7.279-0.026 7.281c0 2.427 0 4.854 0.055 7.279l0.001 0.037c0.022 0.925 0.777 1.67 1.709 1.673l7.281 0.022 7.281-0.003 7.281-0.018c2.427-0.018 4.854-0.029 7.281-0.106l0.074-0.003c0.86-0.026 1.542-0.736 1.531-1.603l-0.212-15.725c-0.015-0.787-0.68-1.421-1.482-1.372z"></path>
        </svg>
    </template>
    <div v-if="!fullscreen" class="mx-3 mb-3">   
        <v-expansion-panels>
            <v-expansion-panel hide-actions static :tile="true" class="rm-p" v-for="(task, index) in data.data" :key="index">
                <v-expansion-panel-title class="task-panel">
                    <template v-slot:default="{ expanded }">
                        <ListBox @getBoardTasks=" emit('refreshData', data.type)" :item="task" boxClass="w-full h-full" :isBoard="false" mode="minimal"/>
                    </template>
                </v-expansion-panel-title>
                <v-expansion-panel-text>
                    <ListBox @getBoardTasks=" emit('refreshData', data.type)" :item="task" boxClass="" :isBoard="false"/>
                </v-expansion-panel-text>
            </v-expansion-panel>
        </v-expansion-panels>
    </div> 
        <div v-if="fullscreen" class="px-4">
            <div v-for="task in data.data" :key="task.id" class="py-2 text-[14px] border-b border-[var(--border-color)] last:border-b-0">
                <ListBox :item="task" boxClass="" :isBoard="false"/>
            </div>
        </div>
    </BaseLayout>
</template>
 
<script setup lang="ts">
import { Task } from '@/interface/globalInterface'
import { useTemplateRef } from 'vue'
import ListBox from '@/components/Task/List/ListBox.vue';
import BaseLayout from './BaseLayout.vue';

const props = defineProps<{
    data: {
        title: string,
        data: Task[],
        order?: number,
        type: string
        canResize?: boolean
        canFullscreen?: boolean
    },
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

