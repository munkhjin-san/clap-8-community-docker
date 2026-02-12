<template>
    <BaseLayout
        :title="data.title" 
        :count="data.data.length" 
        :fullscreen="fullscreen" 
        :type="data.type" 
        @toggle="(el, title) =>emit('toggle', el, data.type)" 
        @resize="emit('resize', data.type)"
    >
    <div v-if="!fullscreen" class="mx-3 mb-3">   
        <v-expansion-panels>
            <v-expansion-panel hide-actions static :tile="true" class="rm-p" v-for="(task, index) in data.data" :key="index">
                <v-expansion-panel-title class="task-panel">
                    <template v-slot:default="{ expanded }">
                        <ListBox :item="task" boxClass="w-full h-full" :isBoard="false" mode="minimal"/>
                    </template>
                </v-expansion-panel-title>
                <v-expansion-panel-text>
                    <ListBox :item="task" boxClass="" :isBoard="false"/>
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
    },
    fullscreen: boolean
}>()

const emit = defineEmits<{
    resize: [type: string]
    toggle: [el: HTMLElement | null, title: string]
}>()
  

defineExpose({
    cardType: props.data.type,
})
</script>

