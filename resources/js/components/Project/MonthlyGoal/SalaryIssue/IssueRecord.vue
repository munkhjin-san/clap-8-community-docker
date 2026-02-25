<template>
    <div class="flex flex-col gap-[30px] relative" v-if="issue">

        <ItemStatusDetail
            :type="'salary_issue'"
            :status="issue?.status"
            :logs="issue?.status_logs"
        />
        <div 
            v-if="auth.isAdmin" 
            class="flex flex-wrap items-center gap-[10px] bg-[var(--bg3)] px-[10px] py-[8px] mt-10"
        >
            <div class="text-[12px] font-semibold whitespace-nowrap">人事専用ステータス変更</div>
            <select 
                v-model.number="selectedSalaryIssueStatus" 
                class="py-[6px] px-[10px] text-[13px] text-[var(--primary-color)] bg-[var(--background-color)] border border-solid border-[var(--formBorder)] min-w-[180px]"
            >
                <option v-for="(label, index) in salaryIssueStatuses" :key="index" :value="index">
                    {{ label }}
                </option>
            </select>
            <LoaderButton style="margin: 0;" :content="'更新'" @click="updateSalaryIssueStatusDirectly" />
        </div>
        <div>
            <div class="text-[13px] font-semibold">テーマ</div>
            <div>{{ issue.theme }}</div>
        </div>
        <div>
            <div class="text-[13px] font-semibold">メンター</div>
            <div class="kadai-content">{{ evaluationData?.mentor?.name ?? '未設定' }}</div>
        </div>
        <div>
            <div class="text-[13px] font-semibold">タイトル</div>
            <div class="kadai-content">{{ issue.title }}</div>
        </div>
        <div v-if="issue.content">
            <div class="text-[13px] font-semibold">内容・詳細</div>
            <div class="kadai-content">{{ issue.content }}</div>
        </div>
        <div>
            <div class="text-[13px] font-semibold">開発能力</div>
            <div class="kadai-content">{{ issue.ability }}</div>
        </div>      
        <div>
            <div class="text-[13px] font-semibold mb-[10px]">修得要件</div>
            <div v-if="issue.actions" class="flex flex-col gap-[15px]">
                <table class="issue-table">
                    <thead>
                        <tr>
                            <th class="w-[80px]">修得状況</th>
                            <th>修得要件</th>
                            <th>ガイドライン</th>
                        </tr>
                    </thead>
                    <tbody>                                    
                        <tr v-for="action in issue.actions">                                        
                            <td class="w-[80px] max-w-[110px] text-center">
                                <select 
                                    :disabled="!salaryIssueReport" 
                                    :value="action.status" 
                                    @change="salaryIssueActionComplete(action)" 
                                    class="py-[5px] px-[10px]"
                                    :class="{'!cursor-not-allowed appearance-none': !salaryIssueReport}"
                                    :style="{ background: action.status == 1 ? '#64bc44' : 'var(--bg3)', color: action.status == 1 ? 'white' : 'var(--primary-color)' }"
                                >
                                    <option :value="1">修得済み</option>
                                    <option :value="0">未修得</option>
                                </select>
                            </td>
                            <td>
                                <div class="leading-normal text-[13px]">{{ action.content }}</div>
                            </td>
                            <td>
                                <div class="leading-normal text-[13px]">{{ action.learning_content }}</div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>                                  
        <div v-if="issue.comment">
            <div class="text-[13px] font-semibold">コメント</div>
            <div class="kadai-content">{{ issue.comment }}</div>
        </div>
        <div v-if="issue.review">
            <div class="text-[13px] font-semibold">AI添削結果</div>
            <div class="kadai-content">{{ issue.review }}</div>
        </div>
        <div v-if="issue?.status >= 6">
            <div class="post-separetor mt-[10px]"></div>
            <div class="mb-[10px] font-semibold text-[13px]">開発能力検証報告</div>
            <div class="kadai-content">{{ issue.result }}</div>                        
            <Files style="margin-top: 15px;" v-if="issue?.files?.length" :items="issue?.files" :path="'project_files'"/>
        </div>
        <MessageArea :passing-data="passingData" which="salary_issue" :item="issue" :key="`message-area-goal-${goal.id}`" @refresh="() => refresh()"/>
        <div v-if="issue.status < 2 && (auth.id === goal.user_id || evaluationData?.mentor_id === auth.id)" class="flex gap-5 mb-3 justify-center">
            <LoaderButton style="margin: 0;" @click="emit('edit', issue)" content="編集"/>
            <LoaderButton style="margin: 0;" @click="deleteIssue(issue)" :content="'削除'"/>
        </div>
        <div v-if="evaluationData?.mentor_id === auth.id && issue?.status == 2" class="flex gap-5 mb-3 justify-center">
            <LoaderButton style="margin: 0;" @click="updateSalaryIssueStatus(1, '差戻')" :content="'差戻'"/>
            <LoaderButton style="margin: 0;" @click="updateSalaryIssueStatus(3, '承認')" :content="'承認'"/>
        </div>
        <div v-if="auth.isAdmin && issue?.status == 3" class="flex gap-5 mb-3 justify-center">
            <LoaderButton style="margin: 0;" @click="updateSalaryIssueStatus(1, '人事差戻')" :content="'人事差戻'"/>
            <LoaderButton style="margin: 0;" @click="updateSalaryIssueStatus(5, '人事承認')" :content="'人事承認'"/>
        </div>
        <div v-if="auth.isAdmin && issue?.status == 9" class="flex gap-5 mb-3 justify-center">
            <LoaderButton style="margin: 0;" @click="updateSalaryIssueStatus(6, '結果人事差戻')" :content="'結果人事差戻'"/>
            <LoaderButton style="margin: 0;" @click="updateSalaryIssueStatus(10, '結果人事承認')" :content="'結果人事承認'"/>
        </div>
        <div v-if="(auth.isAdmin || evaluationData?.mentor_id === auth.id) && issue?.status === 7" class="si-box" style="display: flex; gap: 20px; justify-content: center;">
            <LoaderButton style="margin: 0;" content="報告差戻" @triggered="updateSalaryIssueStatus(8, '報告差戻')"/>
            <LoaderButton style="margin: 0;" content="報告承認" @triggered="updateSalaryIssueStatus(9, '報告承認')"/>
        </div>
        <div v-if="auth.isAdmin && issue?.status == 5" class="flex gap-5 mb-3 justify-center">
            <LoaderButton style="margin: 0;" @click="updateSalaryIssueStatus(3, '人事承認取消')" :content="'人事承認取消'"/>
        </div>
        <div class="flex gap-5 mb-3 justify-center">
            <LoaderButton v-if="salaryIssueReport" style="margin: 0;" :content="'開発能力検証報告'" @click="reportWindow = true"/>
            <!-- <LoaderButton style="margin: 0;" v-if="evaluationData?.mentor_id === auth.id && issue?.status === 7" :content="'進捗報告承認'" @click="reportWindow"/> -->
        </div>
        <Teleport to="body">
            <Report
                v-if="reportWindow"
                :chosenIssue="issue"
                @close="reportWindow = false"
                @reload="reportWindow = false, refresh()"
            />
        </Teleport>
    </div>
</template>
<script setup lang="ts">
import { useApi } from '@/composables/api';
import { useDashboardGoalsStore } from '@/store/dashboardGoals';
import { storeToRefs } from 'pinia';
import { ProjectGoal, SalaryIssue } from '@/interface/projectInterface';
import { useAuthUserStore } from '@/store/auth';
import { computed, ref } from 'vue';
import MessageArea from '../../MessageArea.vue';
import LoaderButton from '@/components/Global/LoaderButton.vue';
import Files from '@/components/Global/Files.vue';
import { useBadgeStore } from '@/store/badge';
import Report from './Report.vue';
import ItemStatusDetail from '../ItemStatusDetail.vue';

const props = defineProps<{
    issue: SalaryIssue
    goal: ProjectGoal
}>()
const emit = defineEmits<{
    refresh: []
    edit: [issue: SalaryIssue]
}>()
const passingData = {
    path: '/project_goal_comment_create',
    title: '進捗報告・メッセージ',
    file_path: 'project_goal_report_files'
}
const auth = useAuthUserStore()
const goalsStore = useDashboardGoalsStore()
const { salaryIssueStatuses, evaluationData } = storeToRefs(goalsStore)
const { salaryIssueStatus } = goalsStore

const selectedSalaryIssueStatus = ref<number | null>(null)

const api = useApi()
const badge = useBadgeStore()

const reportWindow = ref(false)

const salaryIssueReport = computed(() => {
    return (props.issue.status >= 5 && props.issue.status < 9 && props.issue.status !== 7) 
        && (auth.id === props.goal.user_id || evaluationData.value?.mentor_id === auth.id)
})

const updateSalaryIssueStatusDirectly = async () => {
    if (selectedSalaryIssueStatus.value === null) return
    await api.put('/approve_salary_issue', { id: props.issue.id, status: selectedSalaryIssueStatus.value, comment: null }, {
        ask: 'この昇給課題のステータスを変更しますか？',
        toast: 'ステータスを更新しました。',
    })

    refresh()
    
}

const salaryIssueActionComplete = async(record) => {
    const status = record.status
    const confirmMessage = status == 1 ? '修得要件を未修得にします。よろしいですか？' : '修得要件を修得済みにします。よろしいですか？'
    const successMessage = status == 1 ? '未修得にしました。' : '修得済みしました。'
    await api.post('/salary_issue_action_complete', { action_id: record.id, issue_id: record.salary_issue_id }, {
        ask: confirmMessage,
        toast: successMessage,
    })
    refresh()
    
}

const refresh = async() => {
    emit('refresh')
}
const deleteIssue = async(issue: SalaryIssue) => {
    api.del(`/delete_issue`, {
        id: issue.id,
    }, {
        ask: '昇給課題を削除します。よろしいですか？',
        toast: '削除しました。',
    })
    refresh()  

}

const updateSalaryIssueStatus = async(status: number, message: string) => {
 

    const res = await api.put('/approve_salary_issue', { id: props.issue.id, status: status, comment: null }, {
        ask: `この昇給課題を${message}しますか？`,
        toast: `${message}しました。`,
    })
    if(!res) return
    refresh()

} 
</script>
<style scoped>
.issue-table {
    width: 100%;
    border-collapse: collapse;
}
.issue-table th, .issue-table td {
    border: 1px solid var(--formBorder);
    padding: 8px;
    text-align: left;
}
.issue-table th {
    background-color: var(--bg3);
}
</style>