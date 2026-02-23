<template>
    <div class="p-3">
        <ItemStatus :status="goal.status" class="my-2" type="project_goal"/>
        <div v-if="!isCompleted">
            <div v-if="isInDeadline">
                <div>期日まであと{{ daysLeft }}日</div>
            </div>
            <div v-else>
                <div class="text-[tomato] text-[12px]">期日を過ぎています（{{ -daysLeft }}日）</div>
            </div>
        </div>       
        <div class="mt-2">        
            <router-link class="jump-link text-[12px]" :to="{name: 'dashboard', params: { type: 'overdueGoals', itemId: goal.id}}">{{ '詳細'}}</router-link>
        </div>
    </div>
</template>
<script setup lang="ts">
import { ProjectGoal } from '@/interface/projectInterface';
import { DateTime } from 'luxon';
import { computed } from 'vue';
import ItemStatus from './ItemStatus.vue';

const props = defineProps<{
    goal: ProjectGoal
}>()

const isInDeadline = computed(() => {
    const now = DateTime.local();
    const deadline = DateTime.fromISO(props.goal.end_date);
    return now <= deadline;
})
const daysLeft = computed(() => {
    const now = DateTime.local();
    const deadline = DateTime.fromISO(props.goal.end_date);
    const diff = deadline.diff(now, 'days').days;
    return Math.ceil(diff);
})

const isCompleted = computed(() => {
    return props.goal.status === 9
})
</script>