<template>
    <BaseLayout 
        :title="`成果目標（${selectedDate.short_name}）`" 
        :count="goals.length" 
        :fullscreen="fullscreen" 
        :type="data.type"
        @toggle="(el, title) => emit('toggle', el, data.type)" 
        @resize="emit('resize', data.type)"
        v-slot="{ parentElement }"
    >
        <div v-if="loading" class="fixed h-full w-full z-[-1] flex items-center justify-center">
            <div class="spinner-mini"></div>
        </div>
        <div>
            <div v-if="!fullscreen" class="mx-3 mb-3">
                <v-expansion-panels v-if="goals.length">
                    <v-expansion-panel hide-actions static :tile="true" class="rm-p" v-for="(goal) in goals" :key="goal.id">       
                        <v-expansion-panel-title>
                            <template v-slot:default="{ expanded }">
                                <div class="flex items-center px-2 text-[13px] leading-normal overflow-hidden whitespace-nowrap">
                                    <div class="mr-1 ml-[-5px]" v-if="goal.status === 9">
                                        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="13" viewBox="0 0 38 32" style="fill: rgb(100, 188, 68);; margin-left: 4px;">
                                            <path data-v-3c7a9f1f="" d="M36.486 0.324c-0.666-0.515-1.629-0.396-2.204 0.22l-3.039 3.271-3.060 3.328c-2.031 2.23-4.067 4.452-6.086 6.689-2.025 2.234-8.487 9.367-9.743 10.772-0.132 0.15-0.369 0.129-0.486-0.025-1.060-1.399-2.287-3.028-3.468-4.519-1.161-1.465-2.516-3.22-3.271-4.144-0.755-0.927-1.702-2.093-2.191-2.668-0.528-0.625-1.457-0.791-2.182-0.329-0.765 0.489-0.973 1.521-0.518 2.307 0.367 0.636 2.307 3.801 2.307 3.801 0.801 1.27 3.213 5.039 3.699 5.791 0.487 0.751 1.194 1.782 1.879 2.788 0.684 1.004 1.52 2.313 2.429 3.264s2.487 0.627 3.321-0.358c1.932-2.282 9.588-11.527 11.498-13.857 1.916-2.327 3.815-4.668 5.719-7.004l2.842-3.517 2.823-3.535c0.548-0.687 0.451-1.716-0.272-2.276z"></path>
                                        </svg>
                                    </div>
                                    <div v-if="goalIsOverWeek(goal)" class="mr-1">
                                        <span style="position: unset;" :class="['side-notification !w-2 !min-w-2 !h-2', 'custom-heartbeat',  ]"></span>  
                                    </div>
                                    <div class="overflow-hidden whitespace-nowrap text-ellipsis">{{ goal.title }}</div>
                                    <div class="flex items-center gap-1 ml-2">
                                        <span class="side-notification" style="position: unset;width:15px;z-index: 1;" v-if="badge.goalsBadgeByFilter([{by: 'id', value: goal.id}]).length">
                                            {{ badge.goalsBadgeByFilter([{by: 'id', value: goal.id}]).length }}
                                        </span>
                                        <span v-if="badge.goalIssueCommentBadgeByFilter([{by: 'project_goal_id', value: goal.id}]).length" class="side-notification bg-[orange] ml-1" style="position: unset;z-index: 1;">
                                            {{ badge.goalIssueCommentBadgeByFilter([{by: 'project_goal_id', value: goal.id}]).length }}
                                        </span>   
                                    </div>
                                </div>
                                
                            </template>
                        </v-expansion-panel-title>
                        <v-expansion-panel-text>
                            <MonthlyGoalItemCompact :goal="goal"/>
                        </v-expansion-panel-text>
                    </v-expansion-panel>
                </v-expansion-panels>
                <div v-else class="text-center text-sm text-[gray] py-3">
                    成果目標が設定されていません。
                </div>
            </div>
            <div v-show="fullscreen" class="px-4 pb-4">
                <div>
                    <div class="flex justify-between mb-4 flex-wrap gap-3">
                        <div class="flex-1">
                            <div class="w-fit min-w-[150px]">
                                <GoalUserPicker 
                                    v-if="selectedDate.year && selectedDate.which_half"
                                    v-model="selectedUser" 
                                    :year="selectedDate.year"
                                    :which_half="selectedDate.which_half"
                                />
                            </div>
                            
                        </div>
                        <div class="evaluation-date">
                            <select name="locales" v-model="selectedDate" class="dropDownSelector cursor-pointer" style="width: fit-content; padding: 5px 10px;">
                                <option :value="date" v-for="date in targetDates">{{ date.name}}</option>
                            </select>
                        </div>
                        
                    </div>
                    <MonthlyGoalContainer 
                        :user-id="selectedUser ? selectedUser.id : undefined" 
                        :span="selectedDate ? `${selectedDate.year}-${selectedDate.which_half}` : undefined"                         
                        :scrollParent="parentElement"
                        from="dashboard"
                        class="!p-0 !h-[unset] max-w-[100%]"
                    />
                </div>
            </div>
        </div>
    </BaseLayout>
</template>
<script setup lang="ts">
import { User, UserWithGoals } from '@/interface/globalInterface';
import BaseLayout from './BaseLayout.vue';
import { computed, onMounted, ref, watch } from 'vue';
import { useAuthUserStore } from '@/store/auth';
import { detailedDateOptions } from '@/utils/tools';
import { DateTime } from "luxon";
import { useGoal } from '@/composables/dashboard';
import { useBadgeStore } from '@/store/badge';
import { ProjectGoal } from '@/interface/projectInterface';
import MonthlyGoalContainer from '@/components/Project/MonthlyGoal/MonthlyGoalContainer.vue';
import GoalUserPicker from '@/components/Project/MonthlyGoal/GoalUserPicker.vue';
import MonthlyGoalItemCompact from '@/components/Project/MonthlyGoal/MonthlyGoalItemCompact.vue';

const props = defineProps<{
    data: {
        title: string,
        data: UserWithGoals[],
        order?: number,
        type: string
    },
    fullscreen: boolean
}>()

const emit = defineEmits<{
    resize: [type: string]
    toggle: [el: HTMLElement | null, title: string]
}>()

const badge = useBadgeStore()

const { goals, getGoals, loading, goalStatus, salaryIssueStatus } = useGoal()
const auth = useAuthUserStore()
const selectedUserId = ref<number>(0)
const selectedUser = ref<User | null>(auth.user)

const targetDates = detailedDateOptions()
const selectedDate = ref<typeof targetDates[0]>({ name: '', year: '0', which_half: '', short_name: '' })
onMounted(() => {

    const now = DateTime.local();
    const start = now.set({ month: 4, day: 1 }).startOf("day");   // Apr 1
    const end   = now.set({ month: 9, day: 30 }).endOf("day");    // Sep 30
    const span = (now >= start && now <= end) ? "first" : "second";
    const year = span === "first" ? now.year : now.year - 1;
    const selected = targetDates.find( date => date.year.toString() === year.toString() && date.which_half === span )
    if(selected){
        selectedDate.value = selected
    }

    if(auth.id){
        selectedUser.value = auth.user
    }
    getGoals(selectedUser.value?.id ?? 0 ,year, span)
})

const myGoals = computed(() => props.data.data.find( user => user.id === auth.id )?.outcome_goals ?? [])
const kpiCalculation = (steps: any) => {
    if(steps && steps.length){
        const totalProgress = steps.reduce((acc: number, step: any) => {
            return acc + step.progress
        }, 0)
        
        const maxProgress = steps.length * 100
        return Math.round((totalProgress / maxProgress) * 100)
    }
    return 0
}
const overallScore = (goal: ProjectGoal) => {
    if(!goal.steps || goal.steps.length === 0) return goal.achievement_rate
    const kpi = kpiCalculation(goal.steps)
    const kgi = goal.achievement_rate
    const sum = kpi + kgi
    return Math.round(sum / 2)
}
const totalOverallScore = (goals: ProjectGoal[]) => {
    if (!goals.length) return 0

    return goals.reduce((acc, goal) => {
        return acc + overallScore(goal)
    }, 0)
}

const goalIsOverdue = (goal: ProjectGoal) => {
    if(goal.status === 9) return false
    const now = DateTime.local();
    const deadline = DateTime.fromISO(goal.end_date);
    return now > deadline;
}

const goalIsOverWeek = (goal: ProjectGoal) => {
    if(goal.status === 9) return false
    const now = DateTime.local();
    const deadline = DateTime.fromISO(goal.end_date);
    const diffInDays = now.diff(deadline, 'days').days;
    console.log('diffInDays', diffInDays)
    return diffInDays > 7 && diffInDays >= 0;
}
defineExpose({
    cardType: props.data.type,
})

watch([selectedUser, selectedDate], () => {
    if(selectedUser.value && selectedDate.value){
        getGoals(selectedUser.value.id, Number(selectedDate.value.year), selectedDate.value.which_half)
    }
})
</script>
<style>
.compact-selector .vs--single.vs--open .vs__selected, .vs--single.vs--loading .vs__selected {
    position: relative;
    opacity: .4;
}
</style>