<template>
    <Transition name="modalFade">
    <Modal size="large" persist @close="back">
        <template #title>
            <div class="admin-command-bar">            
                <div class="sub-tab-container">
                    <div @click="sub_tab = 0" :class="['sub-tab-item flex gap-[3px]', { 'selected-sub-tab': sub_tab == 0 }]">成果目標
                        <span 
                            class="side-notification" 
                            style="position: unset;width:15px" 
                            v-if="badge.goalsBadgeByFilter([{by: 'id', value: goal.id}]).length + badge.goalIssueCommentBadgeByFilter([{by: 'project_goal_id', value: goal.id}]).length"
                            :class="{
                                'side-notification--comment-only': !badge.goalsBadgeByFilter([{by: 'id', value: goal.id}]).length && badge.goalIssueCommentBadgeByFilter([{by: 'project_goal_id', value: goal.id}]).length
                            }"
                        >
                            {{ badge.goalsBadgeByFilter([{by: 'id', value: goal.id}]).length + badge.goalIssueCommentBadgeByFilter([{by: 'project_goal_id', value: goal.id}]).length }}
                        </span>
                    </div>
                    <div @click="sub_tab = 1, badge.clearGoalIssue({column: 'salary_issue_id', value: goal?.salary_issue?.id})" :class="['sub-tab-item flex gap-[3px]', { 'selected-sub-tab': sub_tab == 1 }]">昇給課題
                        <span 
                            class="side-notification" 
                            style="position: unset;width:15px" 
                            v-if="badge.salaryIssueByFilter([{by: 'goal_id', value: goal.id}]).length + badge.goalIssueCommentBadgeByFilter([{by: 'salary_issue_id', value: goal?.salary_issue?.id}]).length"
                            :class="{
                                'side-notification--comment-only': !badge.salaryIssueByFilter([{by: 'goal_id', value: goal.id}]).length && badge.goalIssueCommentBadgeByFilter([{by: 'salary_issue_id', value: goal?.salary_issue?.id}]).length
                            }"
                        >
                            {{ badge.salaryIssueByFilter([{by: 'goal_id', value: goal.id}]).length + badge.goalIssueCommentBadgeByFilter([{by: 'salary_issue_id', value: goal?.salary_issue?.id}]).length }}
                        </span>
                    </div>
                </div>       
            </div>
        </template>
        <template #content>
            <div class="kadai-root !w-[calc(100%-2px)] ml-[1px]">                
                <div v-if="sub_tab === 0" class="flex flex-col gap-[30px] relative">

                    <div>
                        <div class="text-[13px] font-semibold">該当部門 ／ 職能レベル ／ 担当者</div>
                        <div class="kadai-content">{{ goal?.project?.name }} ／ {{ evaluationData?.current_level ?? '未設定' }} ／ {{ goal?.user?.name }}</div>
                    </div>
                    <!-- <div class="w-fit">
                        <div class="px-[10px] py-[5px] bg-[var(--bg3)] text-[12px]">{{ goalStatus(goal?.status) }}</div>
                    </div> -->
                    <ItemStatusDetail
                        :type="'project_goal'"
                        :status="goal?.status"
                        :logs="goal?.status_logs"
                    />
                    <div 
                        v-if="auth.isAdmin" 
                        class="flex flex-wrap items-center gap-[10px] bg-[var(--bg3)] px-[10px] py-[8px]"
                    >
                        <div class="text-[12px] font-semibold whitespace-nowrap">人事専用ステータス変更</div>
                        <select 
                            v-model.number="selectedGoalStatus" 
                            class="py-[6px] text-[var(--primary-color)] px-[10px] text-[13px] bg-[var(--background-color)] border border-solid border-[var(--formBorder)] min-w-[180px]"
                        >
                            <option v-for="(label, index) in goalStatuses" :key="index" :value="index">
                                {{ label }}
                            </option>
                        </select>
                        <LoaderButton style="margin: 0;" :content="'更新'" @click="updateGoalStatusDirectly" />
                    </div>
                    <div>
                        <div class="text-[13px] font-semibold">タイトル</div>
                        <div class="kadai-content">{{ goal?.title }}</div>
                    </div>
                    <div v-if="goal?.stakeholder_name">
                        <div class="text-[13px] font-semibold">主なステークホルダー（価値提供先）</div>
                        <div class="kadai-content">{{ goal?.stakeholder_name }}</div>
                    </div>
                    <div>
                        <div class="text-[13px] font-semibold">期間</div>
                        <div class="kadai-content">{{ `${DateTime.fromISO(goal.start_date).toLocaleString()} ~ ${DateTime.fromISO(goal.end_date).toLocaleString()}` }}</div>
                    </div>
                    <div v-if="goal?.outcome_goal">
                        <div class="text-[13px] font-semibold">成果目標</div>
                        <div class="kadai-content">{{ goal?.outcome_goal }}</div>
                    </div>
                    <div v-if="goal?.private_memo">
                        <div class="text-[13px] font-semibold">メモ</div>
                        <div class="kadai-content">{{ goal?.private_memo }}</div>
                    </div>                    
                    <div v-if="goal?.miso">
                        <div class="text-[13px] font-semibold">MISO</div>
                        <div class="kadai-content">{{ goal?.miso }}</div>
                    </div>                    
                    <div v-if="goal?.situation_analysis">
                        <div class="text-[13px] font-semibold">現状分析</div>
                        <div class="kadai-content">{{ goal?.situation_analysis }}</div>
                    </div>                    
                    <div v-if="goal?.action_plan">
                        <div class="text-[13px] font-semibold">行動計画</div>
                        <div class="kadai-content">{{ goal?.action_plan }}</div>
                    </div>
                    <div v-if="goal?.expected_effect">
                        <div class="text-[13px] font-semibold">期待される効果</div>
                        <div class="kadai-content">{{ goal?.expected_effect }}</div>
                    </div>
                    <div v-if="goal?.ai_review">
                        <div class="text-[13px] font-semibold">AI判定とフィードバック</div>
                        <div class="kadai-content">{{ goal?.ai_review }}</div>
                    </div>
                    <div v-if="goal.steps && goal.steps.length">
                        <div class="text-[13px] font-semibold mb-[10px]">成果指標</div>
                        <table>
                            <tbody>
                                <tr>
                                    <td>KGI</td>
                                    <td>{{ goal?.kgi }}</td>
                                    <td>
                                        <ProgressSlider :goal-id="goal.id" :disabled="!reviewReport" :progress="goal.achievement_rate" type="kgi"/>
                                    </td>
                                </tr>                          
                                <tr v-for="(step, index) in goal.steps" :key="index">
                                    <td v-if="index == 0" :rowspan="goal.steps.length">KPI</td>
                                    <td>{{ step.content }}</td>
                                    <td>
                                        <ProgressSlider :goal-id="goal.id" :disabled="!reviewReport" :progress="step.progress" type="kpi" :step-id="step.id"/>
                                    </td>                                    
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div>
                        <div class="text-[13px] font-semibold">評価点</div>
                        <div class="kadai-content">{{ overallScore }}点</div>
                    </div>
                    <div v-if="goal.report || goal.result" class="p-4 border border-solid border-[var(--formBorder)] ]">
                        <p>結果</p>
                        <div v-if="goal?.report">
                            <div class="kadai-content">{{ goal?.report }}</div>
                            <Files style="margin-top: 15px;" v-if="goal?.files?.length" :items="goal?.files" :path="'project_files'"/>
                        </div>
                        <div v-if="goal?.result">
                            <div class="kadai-content">{{ goal?.result }}</div>
                        </div>
                        <div class="mt-3" v-if="goal?.stakeholder_review">
                            <div class="text-[13px] font-semibold">ステークホルダーからの反応 : </div>
                            <div v-if="goal?.stakeholder_point" class="">{{ scoreMap[goal.stakeholder_point] }}</div>
                            <div class="text-[13px] font-semibold mt-3">反応の根拠事例</div>
                            <div class="kadai-content">{{ goal?.stakeholder_review }}</div>
                        </div>
                        <div v-if="canConfirmOrDeny && goal?.status === 7" class="si-box flex gap-5 mb-3 justify-center">
                            <LoaderButton style="margin: 0;" content="結果差戻" @triggered="updateGoalStatus(8, '結果差戻')" :loading="loaderBank[8]"/>
                            <LoaderButton style="margin: 0;" content="結果承認" @triggered="updateGoalStatus(9, '結果承認')" :loading="loaderBank[9]"/>
                        </div>
                    </div>
                    
                    <MessageArea which="goal" :passing-data="passingData" :item="goal" :key="`message-area-goal-${goal.id}`" @refresh="() => refresh()"/>
                    <div v-if="(631 === auth.id || auth.isAdmin) && goal?.status == 3" class="flex gap-5 mb-3 justify-center">
                        <LoaderButton style="margin: 0;" @click="updateGoalStatus(1, '人事差戻')" :content="'人事差戻'"/>
                        <LoaderButton style="margin: 0;" @click="updateGoalStatus(5, '人事承認')" :content="'人事承認'"/>
                    </div>
                    <div v-if="canConfirmOrDeny && (goal?.status == 2)" class="flex gap-5 mb-3 justify-center">
                        <LoaderButton style="margin: 0;" @click="updateGoalStatus(1, '目標差戻')" :content="'目標差戻'"/>
                        <LoaderButton v-if="goal?.status == 2" style="margin: 0;" @click="updateGoalStatus(3, '目標承認')" :content="'目標承認'"/>
                    </div>
                    <div v-if="goal?.status == 4 && auth.isAdmin" class="flex gap-5 mb-3 justify-center">
                        <LoaderButton style="margin: 0;" @click="updateGoalStatus(1, '変更依頼を承認')" content="変更依頼を承認"/>
                    </div>
                    <div v-if="reviewReport" class="flex gap-5 mb-3 justify-center">
                        <LoaderButton v-if="reviewReport" @click="openReport = true" style="margin: 0;" :content="'結果申請'"/>
                    </div>
                </div>
                <SalaryIssueSection 
                    v-if="sub_tab === 1" :goal="goal" 
                    :selectedDate="selectedDate"
                    @refresh="refresh"
                />
            </div>
        <Transition name="modalFade">
            <ProjectGoalResult 
                v-if="openReport"
                :chosenGoal="goal"
                :reviewing="reviewing"
                @close="openReport = false"
                @reload="openReport = false, refresh()"
            />
        </Transition>
        </template>
    </Modal>
    </Transition>
</template>
<script setup lang="ts">
import { useAuthUserStore } from '@/store/auth';
import { computed, onMounted, ref, watch } from 'vue';
import LoaderButton from '@/components/Global/LoaderButton.vue';
import ProjectGoalResult from '../ProjectGoalResult.vue';
import Files from '@/components/Global/Files.vue';
import { ProjectGoal } from '@/interface/projectInterface';
import { useBadgeStore } from '@/store/badge'
import { useRouter } from 'vue-router';
import Modal from '@/components/Global/Modal.vue';
import { DateTime } from 'luxon';
import ProgressSlider from '../ProgressSlider.vue';
import { useApi } from '@/composables/api';
import MessageArea from '../MessageArea.vue';
import { useDashboardGoalsStore, issueThemes } from '@/store/dashboardGoals';
import { storeToRefs } from 'pinia';
import SalaryIssueSection from './SalaryIssueSection.vue';
import ItemStatusDetail from './ItemStatusDetail.vue';


const props = defineProps<{
    goal: ProjectGoal,
    themeRecords: any[],
    selectedDate: string,
}>()
const emit = defineEmits(['close'])
const auth = useAuthUserStore()
const goalsStore = useDashboardGoalsStore()
const { goalStatuses, evaluationData } = storeToRefs(goalsStore)
const { goalStatus, salaryIssueStatus, getGoals, invalidateCache } = goalsStore
const openReport = ref(false)
const sub_tab = ref(0)
const reviewing = ref(false)
const api = useApi()
const selectedGoalStatus = ref<number | null>(props.goal?.status ?? null)
const selectedSalaryIssueStatus = ref<number | null>(props.goal?.salary_issue?.status ?? null)


const badge = useBadgeStore()
const router = useRouter()   
const scoreMap = {
    1: '明確に悪化',
    2: '悪化傾向',
    3: '変化なし・未確認',
    4: '好転傾向',
    5: '明確に好転'
} 
const passingData = {
    path: '/project_goal_comment_create',
    title: '進捗報告・メッセージ',
    file_path: 'project_goal_report_files'
}
onMounted(async () => {
    setTimeout(() => {
        badge.clearGoalIssue({column: 'project_goal_id', value: props.goal?.id})
    }, 3000);
})

const canConfirmOrDeny = computed(() => {
    if(auth.isBoss || auth.isAdmin) return true
    if(isManager.value && props.goal.user_id !== auth.activeUser.id) return true
    return false
})

const isManager = computed(() => {
    return props.goal.project?.is_manager
})

watch(
    () => props.goal?.status,
    (value) => {
        selectedGoalStatus.value = typeof value === 'number' ? value : null
    },
    { immediate: true }
)
watch(
    () => props.goal?.salary_issue?.status,
    (value) => {
        selectedSalaryIssueStatus.value = typeof value === 'number' ? value : null
    },
    { immediate: true }
)
const reviewReport = computed(() => {
    return (auth.id === props.goal.user_id  || managerOrDirector.value) && ([5,6,8].includes(props.goal.status) )
})
const managerOrDirector = computed(() => {
    return (auth.user?.position_id && auth.user?.position_id < 6) || isManager.value || auth.isAdmin
})

const updateGoalStatusDirectly = async () => {
    if (selectedGoalStatus.value === null || !props.goal?.id) return
    await api.put('/approve_outcome_goal', { id: props.goal.id, status: selectedGoalStatus.value, comment: '' }, {
        ask: 'この成果目標のステータスを変更しますか？',
        toast: 'ステータスを更新しました。',
    })
    if (typeof refresh === 'function') {
        refresh()
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
const overallScore = computed(() => {
    if(!props.goal.steps || props.goal.steps.length === 0) {
        return props.goal.achievement_rate
    }
    const kpi = kpiCalculation(props.goal.steps)
    const kgi = props.goal.achievement_rate
    const sum = kpi + kgi
    return Math.round(sum / 2)
})

const refresh = () => {
    invalidateCache()
    getGoals(props.goal.user_id, props.goal.year, props.goal.which_half)
}

const back = () => {
    router.back()
}
const loaderBank = ref<any>({})

const updateGoalStatus = async(status: number, action: string) => {
    
    loaderBank[status] = true
    const params = {
        id: props.goal.id,
        params: {
            status: status,
        },
    }
    const result = await api.put('/update_project_progress', params, {
        toast: `${action}しました。`,
        ask: `${action}してもよろしいですか？`,
        
    })
    loaderBank[status] = false
    if(!result) return
    refresh()

    if(auth.user && auth.user?.position_id && auth.user?.position_id < 6){
        badge.getManagersGoalsBadge()
    }
    badge.getMembersGoalsBadge()

}
</script>
<style>
.admin-command-bar{
    width: fit-content;
    min-width: 300px;      
}
.sub-tab-item{
    padding: 10px 15px;
    font-size: 14px;
    border-bottom: solid thin transparent;
    box-sizing: border-box;
    cursor: pointer;
}
.selected-sub-tab{
    border-bottom: solid thin var(--primary-color);
}
.sub-tab-container{
    display: flex;
}
</style>
<style scoped>
table{
    width: 100%;
    border-collapse: collapse;
}
td, th{
    padding: 10px;
    border: solid thin var(--formBorder);
}
th {
    font-weight: normal;
}
</style>
