<template>
    <BaseLayout 
        :title="`成果目標（${selectedDate.short_name}）`" 
        :count="0" 
        :fullscreen="fullscreen" 
        :type="data.type"
        :can-resize="data.canResize"
        :can-fullscreen="data.canFullscreen"
        @toggle="(el, title) => emit('toggle', el, data.type)" 
        @resize="emit('resize', data.type)"
    >
        <template #icon>
            <svg class="side-app-icon mr-2" version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 45 32" style="height: 18px; min-width: 17px; overflow: visible;">
                <path d="M32.084 18.983c0.433 0.409 0.938 0.722 1.492 0.95 0.433 0.18 1.191 0.265 1.732 0.265 0.553 0 1.191-0.096 1.72-0.277 0.553-0.192 1.059-0.529 1.492-0.938s0.782-0.914 1.023-1.456c0.241-0.553 0.373-1.155 0.373-1.756 0.024-1.203-0.457-2.43-1.323-3.284-0.433-0.433-0.938-0.782-1.504-1.011-0.565-0.241-1.179-0.361-1.78-0.361s-1.215 0.12-1.78 0.361c-0.565 0.241-1.083 0.577-1.504 1.011-0.854 0.866-1.347 2.081-1.323 3.284-0.012 1.203 0.517 2.394 1.383 3.212zM33.829 14.28c0.397-0.385 0.926-0.601 1.48-0.601s1.083 0.217 1.48 0.601c0.397 0.385 0.638 0.914 0.65 1.48s-0.18 1.131-0.577 1.552c-0.385 0.433-1.071 0.686-1.564 0.698-0.481 0.012-1.155-0.289-1.54-0.71-0.192-0.217-0.349-0.457-0.445-0.722s-0.144-0.541-0.132-0.83c0-0.553 0.253-1.083 0.65-1.468zM35.308 23.471c1.107 0 2.262 0.289 3.164 0.902 0.926 0.626 1.78 1.684 2.202 2.37 0.325 0.517 0.866 0.77 1.395 0.553 0.517-0.204 0.686-0.83 0.553-1.371-0.12-0.541-0.577-1.395-1.059-2.045-0.505-0.662-1.167-1.323-2.25-1.949-1.215-0.71-2.454-1.035-4.018-1.035s-2.803 0.325-4.018 1.035c-1.083 0.626-1.744 1.287-2.25 1.949-0.493 0.65-0.938 1.504-1.059 2.045s0.048 1.167 0.553 1.371c0.541 0.217 1.083-0.036 1.395-0.553 0.421-0.686 1.275-1.756 2.201-2.37s2.081-0.902 3.188-0.902z"></path><path d="M43.898 29.931c-1.768-0.18-3.537-0.253-5.305-0.301-0.89-0.024-1.78-0.024-2.671-0.036l-13.353-0.12-10.683-0.048h-8.782c-0.337 0-0.602-0.265-0.602-0.602 0.012-1.889-0.096-7.122-0.144-10.298-0.060-3.188-0.144-7.916-0.204-10.43-0.072-2.514-0.084-5.029-0.325-7.531-0.024-0.301-0.277-0.541-0.589-0.553-0.325-0.012-0.602 0.253-0.638 0.577-0.265 2.49-0.265 4.992-0.337 7.507-0.060 2.514-0.204 8.469-0.241 11.116-0.036 2.635-0.024 8.974 0.060 11.489 0 0-0.096-3.176 0 0.036 0.024 0.686 0.577 1.215 1.263 1.227 1.78 0.012 3.429 0.012 5.209 0.012h5.341l10.683-0.048 13.353-0.12c0.89-0.012 1.78-0.012 2.671-0.036 1.78-0.048 3.561-0.12 5.341-0.301 0.373-0.036 0.662-0.337 0.686-0.722 0-0.433-0.325-0.77-0.734-0.818z"></path><path d="M6.953 20.547l0.926-1.095 0.938-1.119c0.614-0.758 1.239-1.504 1.853-2.262s1.227-1.504 1.829-2.274c0.313-0.385 0.626-0.782 0.926-1.167 0.072-0.096 0.217-0.096 0.301-0.012l4.439 4.451c0.445 0.421 1.155 0.409 1.588-0.036l0.048-0.060c1.023-1.083 2.033-2.201 3.020-3.308 0.986-1.119 1.961-2.25 2.923-3.392 0.77-0.914 2.37-2.875 3.056-3.717 0.096-0.108 0.277-0.036 0.265 0.108-0.060 0.517-0.144 1.299-0.18 1.72-0.060 0.662-0.156 1.323-0.060 2.021 0.024 0.168 0.144 0.313 0.325 0.361 0.204 0.048 0.397-0.072 0.493-0.253 0.301-0.614 0.421-1.263 0.577-1.901 0.156-0.65 0.301-1.299 0.445-1.949s0.289-1.299 0.421-1.961c0.072-0.325 0.313-1.227 0.385-1.997s-0.361-1.167-0.95-1.059c-0.589 0.108-1.54 0.505-2.165 0.734l-1.877 0.722-1.865 0.734c-0.614 0.265-1.516 0.65-1.72 0.794s-0.313 0.385-0.253 0.589c0.060 0.205 0.217 0.385 0.577 0.349 0.361-0.048 1.191-0.241 1.829-0.409l1.72-0.469c0.144-0.036 0.253 0.132 0.156 0.241-0.734 0.818-2.406 2.719-3.188 3.633-0.962 1.143-1.925 2.286-2.863 3.441-0.614 0.746-1.215 1.504-1.817 2.262-0.12 0.156-0.349 0.168-0.493 0.036l-4.391-4.211c-0.048-0.036-0.084-0.072-0.144-0.108-0.469-0.313-1.095-0.18-1.432 0.277-0.577 0.77-1.143 1.54-1.708 2.322-0.577 0.782-1.143 1.576-1.708 2.358-0.565 0.794-1.131 1.588-1.684 2.382l-0.83 1.203-0.818 1.203c-0.217 0.313-0.144 0.758 0.205 0.974 0.277 0.18 0.674 0.108 0.902-0.156z"></path>
            </svg>
        </template>
        <template #default="{ parentElement }">
            <div v-if="loading" class="fixed h-full w-full z-[-1] flex items-center justify-center">
                <div class="spinner-mini"></div>
            </div>
            <div>
                <div v-if="!fullscreen" class="mx-3 mb-3">
                    <div v-for="item in approvaNeeded" class="mb-4">
                        <p class="my-2 text-sm overflow-hidden whitespace-nowrap text-ellipsis">
                            <span class="text-[11px] rounded-full bg-[var(--bg3)] px-1 py-0.5">{{ item.chip }}</span>
                            {{ item.title }} ({{ item.users.length }})
                        </p>
                        <div class="grid" :class="`grid-cols-${data.col.split('-')[2]}`" >
                            <div class="p-2 cursor-pointer hover:bg-[var(--bg3)] rounded flex items-center text-[13px] overflow-hidden" v-for="member in item.users" :key="member.id">
                                <UserPanel size="25" :user="member" disable-instant/>
                                <div class="ml-2 flex w-full">
                                    <div class="flex gap-1">
                                        <span class="">{{ member.name }}</span>
                                        <span class="ml-1 text-[12px] text-[gray] whitespace-nowrap">({{ member.outcome_goals_count }}件)</span>
                                    </div>
                                    <div class="jump-link ml-auto whitespace-nowrap" @click="{selectedUser = member; emit('toggle', parentElement, data.type)}">対応</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-if="myGoals.length">
                        <p v-if="approvaNeeded.length" class="text-sm mb-2">自分の目標 ({{ myGoals.length }})</p>
                        <v-expansion-panels>
                            <v-expansion-panel hide-actions static :tile="true" class="rm-p" v-for="(goal) in myGoals" :key="goal.id">       
                                <v-expansion-panel-title>
                                    <template v-slot:default="{ expanded }">
                                        <PanelTitle :expanded="expanded">
                                        <div class="flex items-center h-full text-[13px] leading-normal overflow-hidden whitespace-nowrap">
                                            <div class="mr-1 ml-[-5px]" v-if="goal.status === 9">
                                                <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="13" viewBox="0 0 38 32" style="fill: rgb(100, 188, 68);; margin-left: 4px;">
                                                    <path data-v-3c7a9f1f="" d="M36.486 0.324c-0.666-0.515-1.629-0.396-2.204 0.22l-3.039 3.271-3.060 3.328c-2.031 2.23-4.067 4.452-6.086 6.689-2.025 2.234-8.487 9.367-9.743 10.772-0.132 0.15-0.369 0.129-0.486-0.025-1.060-1.399-2.287-3.028-3.468-4.519-1.161-1.465-2.516-3.22-3.271-4.144-0.755-0.927-1.702-2.093-2.191-2.668-0.528-0.625-1.457-0.791-2.182-0.329-0.765 0.489-0.973 1.521-0.518 2.307 0.367 0.636 2.307 3.801 2.307 3.801 0.801 1.27 3.213 5.039 3.699 5.791 0.487 0.751 1.194 1.782 1.879 2.788 0.684 1.004 1.52 2.313 2.429 3.264s2.487 0.627 3.321-0.358c1.932-2.282 9.588-11.527 11.498-13.857 1.916-2.327 3.815-4.668 5.719-7.004l2.842-3.517 2.823-3.535c0.548-0.687 0.451-1.716-0.272-2.276z"></path>
                                                </svg>
                                            </div>
                                            <div v-if="goalIsOverWeek(goal)" class="mx-1">
                                                <span style="position: unset;" :class="['side-notification !w-2 !min-w-2 !h-2', 'custom-heartbeat',  ]"></span>  
                                            </div>
                                            <div class="overflow-hidden whitespace-nowrap text-ellipsis">{{ goal.title || goal.outcome_goal }}</div>
                                            <div class="flex items-center gap-1 ml-2">
                                                <span class="side-notification" style="position: unset;width:15px;z-index: 1;" v-if="badge.goalsBadgeByFilter([{by: 'id', value: goal.id}]).length">
                                                    {{ badge.goalsBadgeByFilter([{by: 'id', value: goal.id}]).length }}
                                                </span>
                                                <span v-if="badge.goalIssueCommentBadgeByFilter([{by: 'project_goal_id', value: goal.id}]).length" class="side-notification bg-[orange] ml-1" style="position: unset;z-index: 1;">
                                                    {{ badge.goalIssueCommentBadgeByFilter([{by: 'project_goal_id', value: goal.id}]).length }}
                                                </span>   
                                            </div>
                                        </div>
                                        </PanelTitle>
                                    </template>
                                </v-expansion-panel-title>
                                <v-expansion-panel-text>
                                    <PanelData>
                                        <MonthlyGoalItemCompact :goal="goal"/>
                                    </PanelData>
                                </v-expansion-panel-text>
                            </v-expansion-panel>
                        </v-expansion-panels>
                    </div>
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
        </template>
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
import PanelTitle from './PanelTitle.vue';
import PanelData from './PanelData.vue';
import UserPanel from '@/components/Global/UserPanel.vue';

const props = defineProps<{
    data: {
        title: string,
        data: any[],
        order?: number,
        type: string
        canResize?: boolean
        canFullscreen?: boolean
        col: string
    },
    fullscreen: boolean
}>()

const emit = defineEmits<{
    resize: [type: string]
    toggle: [el: HTMLElement | null, title: string]
}>()

const badge = useBadgeStore()

const { goals, getGoals, loading, pendingMembers, myGoals, managersGoals, mentorApprovalNeededGoalsWithSalaryIssue, adminApprovalNeededGoalsWithSalaryIssue, adminApprovalNeededGoals } = useGoal()
const auth = useAuthUserStore()
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

const approvaNeeded = computed(() => {
    const items:{
        chip: string,
        title: string,
        users: UserWithGoals[]
    }[] = []
    if(pendingMembers.value.length){
        items.push({
            chip: 'PM',
            title: '承認漏れ【メンバー】',
            users: pendingMembers.value
        })
    }
    if(managersGoals.value.length){
        items.push({
            chip: '役員',
            title: '承認漏れ【PM】',
            users: managersGoals.value
        })
    }
    if(mentorApprovalNeededGoalsWithSalaryIssue.value.length){
        items.push({
            chip: 'メンター',
            title: '承認漏れ【メンティー】',
            users: mentorApprovalNeededGoalsWithSalaryIssue.value
        })
    }
    if(adminApprovalNeededGoalsWithSalaryIssue.value.length){
        items.push({
            chip: '人事',
            title: '承認漏れ【昇給課題】',
            users: adminApprovalNeededGoalsWithSalaryIssue.value    
        })
    }
    if(adminApprovalNeededGoals.value.length){
        items.push({
            chip: '人事',
            title: '承認漏れ【成果目標】',
            users: adminApprovalNeededGoals.value    
        })
    }

    return items

})
const goalIsOverWeek = (goal: ProjectGoal) => {
    if(goal.status === 9) return false
    const now = DateTime.local();
    const deadline = DateTime.fromISO(goal.end_date);
    const diffInDays = now.diff(deadline, 'days').days;
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