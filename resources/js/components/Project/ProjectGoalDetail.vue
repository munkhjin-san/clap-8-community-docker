<template>
        
        <div class="goals-wrap">
            <div style="overflow: hidden; position: relative;height:100%;">
                <div class="goals-inner" style="height: calc(100% - 20px); padding: 20px;">
                    <div v-if="projectGoals.length" v-for="goal in projectGoals" style="position: relative">                        
                        <div class="goal-detail cursor-pointer" @click="router.push({name: 'goal-more', params: { goalId: goal?.id}})" style="position: relative;gap:10px;margin-bottom: 20px;">
                     
                            <div>
                                <div>該当部門</div>
                                <div class="kadai-content flex items-center">
                                    {{ goal?.project?.name }}
                                    
                                </div>                                
                            </div>
                            <div>
                                <div>期間</div>
                                <div class="kadai-content">{{ `${DateTime.fromISO(goal.start_date).toLocaleString()} ~ ${DateTime.fromISO(goal.end_date).toLocaleString()}` }}</div>
                            </div>
                      
                            <!-- <div>
                                <div>成果目標ステータス</div>
                                <div class="kadai-content flex items-center" :style="{color: badge.goalsBadgeByFilter([{by: 'id', value: goal.id}, {by: 'project_id', value: Number(route.params.projectId)}]).length ? 'tomato' : 'var(--primary-color)'}">{{ statuses[goal?.status] }}
                                    <span class="side-notification" style="position: unset;width:15px" v-if="badge.goalsBadgeByFilter([{by: 'id', value: goal.id}, {by: 'project_id', value: Number(route.params.projectId)}]).length">{{ badge.goalsBadgeByFilter([{by: 'id', value: goal.id}, {by: 'project_id', value: Number(route.params.projectId)}]).length }}</span>
                                </div>
                            </div> -->
                            <div v-if="goal?.outcome_goal">
                                <div>成果目標</div>
                                <div class="kadai-content">{{ sliceGoal(goal?.outcome_goal) }}</div>
                            </div>     
                            <div v-if="goal?.title">
                                <div>タイトル</div>
                                <div class="kadai-content">{{ goal?.title }}</div>
                            </div>                
                            <div v-if="goal?.miso">
                                <div>MISO</div>
                                <div class="kadai-content">{{ sliceGoal(goal?.miso) }}</div>
                            </div>                           
                            <div v-if="goal?.kgi">
                                <div>KGI</div>
                                <div class="kadai-content">{{ sliceGoal(goal?.kgi) }}</div>
                            </div>                           

                            <div>
                                <div>達成率</div>
                                <div class="flex gap-[20px] kadai-content" v-if="goal.steps && goal.steps.length">
                                    <div>KGI {{ `${goal.achievement_rate}%` }}</div>
                                    <div>KPI {{ `${kpiCalculation(goal.steps)}%` }}</div>                                    
                                    <div>評価点 {{ `${overallScore(goal)}点` }}</div>
                                </div>
                                <div v-else-if="goal?.achievement_rate !== null" class="kadai-content">{{ goal?.achievement_rate }}%</div>                                
                            </div>
                            <div v-if="goal?.salary_issue" class="mt-[5px]">
                                <div class="w-full h-[1px] bg-[var(--calendarBorder)] mb-[15px]"></div>
                                <div class="mb-[10px]">
                                    <div>昇給課題</div>
                                    <div class="kadai-content">{{ goal?.salary_issue?.title }}</div>
                                </div>
                                <div>
                                    <div>昇給課題ステータス</div>
                                    <div class="kadai-content flex items-center" :style="{color: badge.salaryIssueByFilter([{by: 'goal_id', value: goal.id}, {by: 'project_id', value: Number(route.params.projectId)}]).length ? 'tomato' : 'var(--primary-color)'}">{{ salaryIssueStatus[goal?.salary_issue?.status] }}
                                        <span class="side-notification" style="position: unset;width:15px" v-if="badge.salaryIssueByFilter([{by: 'goal_id', value: goal.id}, {by: 'project_id', value: Number(route.params.projectId)}]).length">{{ badge.salaryIssueByFilter([{by: 'goal_id', value: goal.id}, {by: 'project_id', value: Number(route.params.projectId)}]).length }}</span>
                                    </div>
                                </div>
                            </div>

                            
                            <div class="absolute right-[10px] top-[10px] flex items-center gap-2">   
                                <div>
                                    <div class="p-[5px] bg-[var(--background-color)] text-[var(--primary-color)] text-[12px]">
                                        <div class="flex items-center" :style="{color: badge.goalsBadgeByFilter([{by: 'id', value: goal.id}, {by: 'project_id', value: Number(route.params.projectId)}]).length ? 'tomato' : 'var(--primary-color)'}">{{ statuses[goal?.status] }}
                                            <span class="side-notification" style="position: unset;width:15px" v-if="badge.goalsBadgeByFilter([{by: 'id', value: goal.id}, {by: 'project_id', value: Number(route.params.projectId)}]).length">{{ badge.goalsBadgeByFilter([{by: 'id', value: goal.id}, {by: 'project_id', value: Number(route.params.projectId)}]).length }}</span>
                                        </div>
                                    </div>
                                </div>                                         
                                <ItemMenu 
                                    v-if="memberData && (auth.id === memberData.id || isManagerOrMember || auth.activeUser.id === 610 || auth.activeUser.id === 608) && goal?.status < 2"
                                    :items="[
                                        {title: '編集する', action: () => editGoal(goal)},
                                        {title: '削除する', action: () => deleteGoal(goal)}
                                    ]"
                                /> 
                                <ItemMenu 
                                    v-else-if="memberData && auth.id === memberData.id && goal?.status >= 2 && goal?.status < 7 && goal?.status != 4" 
                                    :items="[{title: '変更申請', action: () => applyEdit(goal)}]"
                                /> 
                            </div>                            
                        </div>
                                
                    
                
                        
                    </div>
                    <div v-else class="no-comment-text">
                        現在レコードはありません。
                    </div>
                </div>
                <div v-if="memberData && (auth.id === memberData.id)" title="新規作成" id="boardCreate" class="createBoardButton fileNewButton" @click="createOutcomeGoal = true" :style="{zIndex: 7}">
                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 32 32" style="fill:#000;margin:auto;">
                        <path d="M30.044 14.14c-2.402-0.231-4.804-0.341-7.206-0.422-1.535-0.058-3.071-0.079-4.606-0.090-0.326-0.002-0.587-0.265-0.588-0.591-0.004-1.537-0.018-3.074-0.078-4.613-0.092-2.4-0.218-4.802-0.542-7.205-0.084-0.612-0.565-1.119-1.205-1.206-0.769-0.103-1.477 0.437-1.582 1.206-0.324 2.401-0.449 4.804-0.542 7.205-0.059 1.536-0.074 3.071-0.078 4.606-0.001 0.325-0.263 0.59-0.59 0.59-1.534 0.005-3.068 0.020-4.602 0.078-2.404 0.094-4.805 0.219-7.207 0.543-0.612 0.081-1.119 0.564-1.205 1.205-0.103 0.769 0.436 1.477 1.205 1.58 2.402 0.324 4.804 0.449 7.207 0.543 1.536 0.059 3.074 0.073 4.612 0.078 0.325 0.001 0.587 0.262 0.59 0.587 0.011 1.536 0.033 3.070 0.090 4.606 0.080 2.402 0.192 4.805 0.423 7.207 0.066 0.699 0.622 1.278 1.349 1.348 0.823 0.079 1.556-0.524 1.633-1.348 0.231-2.402 0.342-4.805 0.423-7.207 0.057-1.538 0.079-3.077 0.090-4.615 0.002-0.324 0.263-0.583 0.587-0.586 1.538-0.011 3.077-0.034 4.615-0.090 2.402-0.080 4.804-0.193 7.206-0.423 0.7-0.066 1.279-0.622 1.349-1.349 0.076-0.823-0.528-1.557-1.351-1.634z"></path>
                    </svg>
                </div>
                    
            </div>
            
            <router-view v-slot="{ Component }">
                <component
                    :is="Component" 
                    :goal="chosenGoal"
                    :themeRecords="themeRecords"
                    :selectedDate="selectedDate"
                    :statuses="statuses"
                    :salaryIssueStatus="salaryIssueStatus"
                    :evaluationData="evaluationData"
                />                    
            </router-view>
            <Transition name="modalFade">
                <ProjectGoalCreation 
                    v-if="createOutcomeGoal"
                    :selectedDate="selectedDate"
                    :editGoalData="editGoalData"
                    @close="createOutcomeGoal = false, editGoalData = null"
                />
            </Transition>
        </div>
            
        
</template>
<script setup lang="ts">
import { useRoute, useRouter } from 'vue-router';
import { computed, ref, onMounted, inject, watch, provide } from 'vue';
import ItemMenu from '../Global/ItemMenu.vue';
import axios from 'axios';
import { useAuthUserStore } from '@/store/auth';
import { Dialog } from '@/interface/globalInterface';
import ProjectGoalCreation from './ProjectGoalCreation.vue';
import { ProjectGoal } from '@/interface/projectInterface';
import { detailedDateOptions } from '@/utils/tools'
import { useBadgeStore } from '@/store/badge';
import { EvaluationRecord } from '@/interface/evaluationInterface';
import { useProject } from '@/composables/project';
import { DateTime } from 'luxon';

interface Theme {
    issues: any;
    level: any;
}
interface Date {
    value: any;
}
const auth = useAuthUserStore()
const route = useRoute()
const router = useRouter()
const initialLoader = defineModel()
const createOutcomeGoal = ref(false)
const themeRecords = ref<Theme[]>([])
const editGoalData = ref<ProjectGoal | null>(null)
const goalDate = ref('')
const projectGoals = ref<ProjectGoal[]>([])
const evaluationData = ref<EvaluationRecord | null>(null)
const badge = useBadgeStore()
const { notify, info, confirm } = inject<Dialog>('dialog')!;
const { memberData, isManagerOrMember } = useProject()
const statuses = [
    '作成中（本人対応中）', 
    '目標を差戻中（本人対応中）', 
    '目標を上席者に申請中（上席者対応中）', 
    '目標を人事に申請中（人事対応中）', 
    '目標の変更申請中（人事対応中）', 
    '目標承認済み（本人対応中）', 
    '結果入力中（本人対応中）',
    '結果を上席者に申請中（上席者対応中）',
    '報告を差戻中（本人対応中）', 
    '結果を上席者承認済み（完了）'
]
const salaryIssueStatus = [
    '作成中（本人対応中）', 
    '課題を差戻中（本人対応中）', 
    '課題をメンターに申請中（メンター対応中）', 
    '課題を人事に申請中（人事対応中）', 
    '課題の変更申請中（人事対応中）', 
    '課題承認済み（本人対応中）', 
    '結果入力中（本人対応中）',
    '結果をメンターに申請中（メンター対応中）',
    '結果を差戻中（本人対応中）',
    '結果を人事に申請中（人事対応中）', 
    '昇給達成（完了）or 未達成（完了）'
]
watch(goalDate, async(newValue) => {
    // if(newValue){
    //     initialLoader.value = true
    //     await fetchMemberData()
    // }
})

onMounted(async() => {
    // setInitialDates()
    await fetchMemberData()
    await getThemes()
})
const chosenGoal = computed(() => {
    return route.params && route.params.goalId ? projectGoals.value.find(ob => ob.id == Number(route.params.goalId)) : null
})
const sliceGoal = (content: string) => {
    if (!content) return ''
    const truncatedGoal = content.length > 100 
    ? content.slice(0, 100) + '...' 
    : content;
    return truncatedGoal
}
const selectedDate = computed(() => {
    const options = detailedDateOptions()
    const span = route.params.span as string
    const [year, which_half] = span.split('-')
    const goalDate = options.find(option => option.year === year && option.which_half === which_half)
    return goalDate
})

const fetchMemberData = async () => {
    if (memberData.value) {
        try {
            const span = route.params.span as string
            const [year, which_half] = span.split('-')
            const params = {
                year: year,
                which_half: which_half,
                user_id: memberData.value?.id
            }
            const data = await axios.post('/get_outcome_goals', params).then(res => res.data)
            projectGoals.value = data.project_goals
            evaluationData.value = data.evaluation
            setTimeout(() => {
                initialLoader.value = false
            }, 300)
        } catch (e) {
            notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
        }
    }
    
}
const editGoal = async (goal: any) => {
    editGoalData.value = goal
    createOutcomeGoal.value = true
}
const deleteGoal = async (goal: ProjectGoal) => {
    const answer = await confirm('成果目標の削除は、昇給課題と一緒に削除される場合があります。よろしいですか？')
    if(!answer.value) return
    try {
        await axios.delete(`/delete_project_goal?id=${goal.id}`)
        fetchMemberData()
        info('削除しました。')
    } catch (e) {
        notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
    }
} 

const getThemes = async() => {
    try{
        const res = await axios.get('/get_kadai_themes')
        if(res.data){
            themeRecords.value = res.data
        }
    } catch (e) {
        notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
    }
}
const applyEdit = async (goal: ProjectGoal) => {
    try {
        await axios.put('/approve_outcome_goal', {id: goal.id, status: 4})
        fetchMemberData()
        info('変更申請しました。')
    } catch (e) {
        notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
    }
}
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
provide('refresh', fetchMemberData)
</script>
<style>
    .kadaiSwitch{
        padding: 10px;
        background: var(--background-color);
        cursor: pointer;
        font-size: 13px;
    }
    .kadai-content{
        white-space: break-spaces;
        line-height: 2;
    }
    .kadai-active{
        background: var(--bg3);
    }
    .member-wrap{
        display: flex;
        gap: 10px;
        align-items: center;
        width: fit-content;
        padding: 10px;
    }
    .goals-wrap{
        /* display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px; */
        height: 100%;
    }
    .goals-inner {
        overflow: hidden auto;
        height: 100%;
        background: var(--background-color);
        padding: 0 20px;
    }
    /* .goal-detail{
        background-color: var(--bg3);
        line-height: 1.5;
        word-break: break-word;
        white-space: break-spaces;
        padding: 10px;
        margin-bottom: 30px;
        display: flex;
        flex-direction: column;
        gap: 30px;
    } */
    /* .kadaiCreate{
        width: 90% !important;
        height: 90%!important;
    } */
    .kadai-root{
        width: 100%;
        height: auto;
        left: 0;
        top: 0;
        font-size: 14px;
        line-height: 1.5;
    }
.range-input {
  -webkit-appearance: none;
  appearance: none; 
  width: 50%;
  cursor: pointer;
  outline: none;
  border-radius: 15px;
  height: 6px;
  background: #ccc;
}

input[type="range"]::-webkit-slider-thumb {
  -webkit-appearance: none;
  appearance: none; 
  height: 15px;
  width: 15px;
  background-color: var(--primary-color);
  border-radius: 50%;
  border: none;
  transition: .2s ease-in-out;
}

input[type="range"]::-moz-range-thumb {
  height: 15px;
  width: 15px;
  background-color: var(--primary-color);
  border-radius: 50%;
  border: none;
  transition: .2s ease-in-out;
}


</style>