<template>
    <div class="sub-task-item">         
        <div class="width-smooth"></div>
        <div class="sub-task-inner task-card-inner width-smooth" style="min-width: 24rem;" :id="`gantt-sub-${task.id}`" :style="{ background: background }"> 
            <div class="flex h-full">
                
                <div class="flex flex-col gap-[5px] h-full w-full">

                    <div class="flex justify-between cursor-pointer w-full items-center gap-[3px] min-h-[20px]">
                        <div class="flex w-fit"> 
                            <div v-for="user in task.executors?.slice(0, 3)" class="relative w-fit">
                                <UserPanel :disableInstant="true" :user="user" imgStyle="pointer-events: none" :imgClass="!isSubTask ? 'userMidIcon' : 'userSmallIcon'" :size="!isSubTask ? '25' : '15'"/>
                            </div>
                        </div>
                        <CloseIcon @click="emit('delete', task.id)"/>       
                    </div>
                    <div class="flex">
                        <div class="lg-triangle self-center mr-[10px]" :title="task.sub_tasks?.length ? `サブタスク${task.sub_tasks.length}件` : 'サブタスク'" :style="{transform: `rotate(${task.sub_tasks?.length ? 90 : 0}deg)`}" v-if="task.sub_tasks?.length || isSubTask"></div>
                        <div :style="{width: task.sub_tasks?.length || isSubTask ? 'calc(100% - 19px)' : '100%'}" class="flex flex-col gap-[5px]">
                            <div ref="remarksRef" contenteditable="true" class="w-[fit-content] editable-remark max-w-full overflow-hidden overflow-ellipsis whitespace-pre-line leading-[1.5] break-all p-1">{{task.remarks}}</div>
                            
                        </div>
                    </div>
                    <div class="text-[12px] relative w-fit">
                        <span :class="['cursor-pointer']">{{DateTime.fromISO(task.start_at).toLocaleString()}}</span> 
                        <span> ~ </span>
                        <span :class="['cursor-pointer']">{{ DateTime.fromISO(task.end_at).toLocaleString() }}</span>
                    </div>
                </div>
            </div>         
        </div>      
    </div>  
</template>
<script setup lang="ts">
import { DateTime } from 'luxon';
import { computed, useTemplateRef } from 'vue';
import UserPanel from '@/components/Global/UserPanel.vue';
import colors from 'assets/colors.json'
import { useAuthUserStore } from '@/store/auth';
import CloseIcon from '@/components/Form/CloseIcon.vue';
const auth = useAuthUserStore()
const emit = defineEmits(['delete'])
const remarksRef = useTemplateRef('remarksRef')
const props = defineProps<{
        task: any,
    }>()
const computedTaskId = computed(() => {
    return props.task.id
})
const isSubTask = computed(() => {
    return props.task.parent_task_id
})
const background = computed(() => {
    const colorIndex:number = auth.user && auth.user.color ? auth.user.color : 0
    return  colors[colorIndex]?.light
})
defineExpose({remarksRef, computedTaskId})
</script>
<style scoped>
.lg-triangle {
    width: 0px;
    height: 0px;
    border-style: solid;
    border-width: 5.5px 0 5.5px 9px;
    border-color: transparent transparent transparent var(--primary-color);
    transform: rotate(0);
    margin-top: 2px;
}
.editable-remark {
    border-radius: 0;
    outline: none;
}
.editable-remark:focus {
    box-shadow: 0 0 1px #000;
}
</style>