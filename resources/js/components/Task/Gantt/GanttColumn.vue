<template>
<div class="day-box":id="`g-task-${day.date_full}`">
    <div class="day-box-title" @click="jumpTo(DateTime.fromISO(day.date_full))">
        {{ day?.date_short }}<span v-if="viewType == 'month'">月</span>
    </div>
    <div  @click.stop="createTask"  class="day-box-tile">
        <GanttProjectItem
            v-for="project in day.projects" 
            :active-project="activeProject" 
            :date="day.date_full" 
            :key="project.id" 
            :project="project"
            :view-type="viewType"
        />
    </div>                   

</div>
</template>
<script setup lang="ts">
import { GanttColumnData, Task } from '@/interface/globalInterface';
import GanttProjectItem from './GanttProjectItem.vue';
import { GanttMethods, GanttMethodsKey } from '@/interface/keys';
import { inject } from 'vue';
import { useMenuStore } from '@/store/menu';
import { DateTime, DateTimeUnit } from 'luxon';
import { Project } from '@/interface/projectInterface';

const menu = useMenuStore()
const props = defineProps<{
  day: GanttColumnData
  activeProject: Project | null
  viewType: DateTimeUnit
}>()
const {fastCreate, jumpTo} = inject(GanttMethodsKey) as GanttMethods

const createTask = (event: { x: number; y: number }) => {
    menu.close()
    fastCreate({time: props.day.date_full, x: event.x, y:event.y, stamp: DateTime.now()})
}

</script>