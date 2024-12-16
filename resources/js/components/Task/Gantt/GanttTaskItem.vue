<template>
    <div class="px-[15px] w-[calc(100% - 40px)]">
        <GanttTaskBody 
            :sub-task-count="task.sub_tasks.length"
            :task="task" 
            :interval="virtualSpan.interval" 
            :which="'main'"
            :from="from"
            :mainTask="null"
            :project="project"
            @setFullText="(data) => emit('setFullText', data)"
            @set-commenting-task-id="(id) => emit('setCommentingTaskId', id)"
        />
        <div v-if="task.sub_tasks.length" class="sub-task-wrap">
            <div class="sub-task-container">
                <GanttTaskBody 
                    v-for="subTask in filteredSubTasks(task)" 
                    :sub-task-count="0"
                    :task="subTask"
                    :interval="virtualSpan.interval"
                    :which="'sub'"
                    :from="from"
                    :project="project"
                    :mainTask="task"
                    @setFullText="(data) => emit('setFullText', data)"
                    @set-commenting-task-id="(id) => emit('setCommentingTaskId', id)"
                    />
            </div>
        </div>       
    </div>
</template>
<script setup lang="ts">
import { Task, TaskUser } from '@/interface/globalInterface';
import { Project, QuickEditText, VirtualSpan } from '@/interface/projectInterface';

import { DateTime } from 'luxon';
import GanttTaskBody from './GanttTaskBody.vue';

const props = defineProps<{
    task: Task
    virtualSpan: VirtualSpan
    from?: string
    project: Project
}>()

const emit = defineEmits<{
    setFullText: [data: QuickEditText]
    setCommentingTaskId: [id: number]
}>()

const filteredSubTasks = (task:Task) => {
    const filtered = task.sub_tasks.filter( sub => {        
        return props.virtualSpan.interval.contains(DateTime.fromISO(sub.start_at)) || props.virtualSpan.interval.contains(DateTime.fromISO(sub.end_at))
    })
    return filtered
}


</script>