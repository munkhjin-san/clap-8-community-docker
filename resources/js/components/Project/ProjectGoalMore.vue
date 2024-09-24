<template>
    <div class="overlay">
        <div class="chatCreate kadaiCreate scrollable">
            <div class="recordFormTitle" style="display:flex;"> 
                <div class="admin-command-bar">            
                    <div class="sub-tab-container">
                        <div @click="sub_tab = 0" :class="['sub-tab-item', { 'selected-sub-tab': sub_tab == 0 }]">成果目標</div>
                        <div @click="sub_tab = 1" :class="['sub-tab-item', { 'selected-sub-tab': sub_tab == 1 }]">昇給課題</div>
                    </div>       
                </div>
                <div class="cursor-pointer" @click="emit('close')" style="position:unset; margin:auto 0 auto auto">
                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" class="modalWindowCloseButton" viewBox="0 0 32 32">
                        <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                    </svg>                        
                </div> 
            </div>
            <div class="kadai-root">
                
                <div v-if="sub_tab === 0" style="display:flex; gap: 20px; flex-direction: column;">
                    <div>
                        <div>該当部門</div>
                        <div class="kadai-content">{{ goal?.project?.name }}</div>
                    </div>
                    <div>
                        <div>成果目標</div>
                        <div class="kadai-content">{{ goal?.outcome_goal }}</div>
                    </div>
                    <div>
                        <div>期間</div>
                        <div class="kadai-content">{{ goal?.start_date }} ～ {{ goal?.end_date }}</div>
                    </div>
                    
                    <div>
                        <div>現状分析</div>
                        <div class="kadai-content">{{ goal?.situation_analysis }}</div>
                    </div>
                    
                    <div>
                        <div>行動計画</div>
                        <div class="kadai-content">{{ goal?.action_plan }}</div>
                    </div>
                    <div>
                        <div>ステータス</div>
                        <div class="kadai-content">{{ statuses[goal?.status] }}</div>
                    </div>
                    <div>
                        <div>期待される効果</div>
                        <div class="kadai-content">{{ goal?.expected_effect }}</div>
                    </div>
                    <div>
                        <div>AI判定とフィードバック</div>
                        <div class="kadai-content">{{ goal?.ai_review }}</div>
                    </div>
                    <div v-if="goal?.achievement_rate">
                        <div>達成率</div>
                        <div class="kadai-content">{{ goal?.achievement_rate }}%</div>
                    </div>
                    <div v-if="goal?.report">
                        <div>成果報告</div>
                        <div class="kadai-content">{{ goal?.report }}</div>
                    </div>
                    <div v-if="goal?.result">
                        <div>成果結果</div>
                        <div class="kadai-content">{{ goal?.result }}</div>
                    </div>
                    <div v-if="memberData && auth.id === memberData.id && goal?.status == 5" style="display: flex; gap: 20px;margin-bottom: 10px;">
                        <LoaderButton @click="openReport = true" style="margin: 0;" content="成果報告"/>
                    </div>
                    <div v-if="(selectedProject?.id === goal?.project?.id && isManagerOrMember || ( auth.user?.position_id && auth.user?.position_id < 6)) && goal?.status == 6" style="display: flex; gap: 20px;margin-bottom: 10px;">
                        <LoaderButton @click="openReport = true" style="margin: 0;" content="成果報告レビュー"/>
                    </div>

                    <div v-if="(selectedProject?.id === goal?.project?.id && isManagerOrMember || ( (auth.user?.position_id && auth.user?.position_id < 6) || (auth.activeUser.id === 610 || auth.activeUser.id === 608))) && goal?.status == 2" style="display: flex; gap: 20px;margin-bottom: 10px;">
                        <LoaderButton style="margin: 0;" @click="approveOutComeGoal(1)" :content="'成果目標差戻'"/>
                        <LoaderButton style="margin: 0;" @click="approveOutComeGoal(3)" :content="'成果目標承認'"/>
                    </div>
                    <div v-if="631 === auth.id && goal?.status == 3" style="display: flex; gap: 20px;margin-bottom: 10px;">
                        <LoaderButton style="margin: 0;" @click="approveOutComeGoal(1)" :content="'人事差戻'"/>
                        <LoaderButton style="margin: 0;" @click="approveOutComeGoal(5)" :content="'人事承認'"/>
                    </div>
                </div>
                <div style="display:flex; gap: 20px; flex-direction: column;" v-if="goal?.salary_issue && sub_tab === 1">
                    <div>
                        <div>評価課題</div>
                        <div>{{ goal?.salary_issue.theme }}</div>
                    </div>
                    <div>
                        <div>タイトル</div>
                        <div class="kadai-content">{{ goal?.salary_issue.title }}</div>
                    </div>
                    <div>
                        <div>内容・詳細</div>
                        <div class="kadai-content">{{ goal?.salary_issue.content }}</div>
                    </div>
                    <div>
                        <div>課題達成による取得能力</div>
                        <div class="kadai-content">{{ goal?.salary_issue.ability }}</div>
                    </div>
                    <div>
                        <div>ステータス</div>
                        <div class="kadai-content">{{ statuses[goal?.salary_issue.status] }}</div>
                    </div>
                    <div>
                        <div>AI添削結果</div>
                        <div class="kadai-content">{{ goal?.salary_issue.review }}</div>
                    </div>
                    <div v-if="goal?.salary_issue?.status == 8">
                        <div>昇給課題結果</div>
                        <div class="kadai-content">{{ goal?.salary_issue.result }}</div>
                        <Files style="margin-top: 15px;" v-if="goal?.salary_issue?.files.length" :items="goal?.salary_issue?.files" :path="'project_files'"/>
                    </div>
                    <div v-if="goal?.salary_issue.status < 2 && (auth.id === memberData?.id || memberData?.evaluation?.mentor.id === auth.id)">
                        <LoaderButton style="margin: 0;" @click="editIssue(goal.salary_issue)" :content="'昇給課題変更'"/>
                    </div>
                    <div v-if="memberData?.evaluation?.mentor.id === auth.id && goal?.salary_issue?.status == 2" style="display: flex; gap: 20px;margin-bottom: 10px;">
                        <LoaderButton style="margin: 0;" @click="approveSalaryIssue(goal?.salary_issue, 1)" :content="'昇給課題差戻'"/>
                        <LoaderButton style="margin: 0;" @click="approveSalaryIssue(goal?.salary_issue, 3)" :content="'昇給課題承認'"/>
                    </div>
                    <div v-if="631 === auth.id && goal?.salary_issue?.status == 3" style="display: flex; gap: 20px;margin-bottom: 10px;">
                        <LoaderButton style="margin: 0;" @click="approveSalaryIssue(goal?.salary_issue, 1)" :content="'人事差戻'"/>
                        <LoaderButton style="margin: 0;" @click="approveSalaryIssue(goal?.salary_issue, 5)" :content="'人事承認'"/>
                    </div>
                    <div v-if="goal?.salary_issue?.status == 5 && (auth.id === memberData?.id || memberData?.evaluation?.mentor.id === auth.id)">
                        <LoaderButton style="margin: 0;" :content="'結果報告'" @click="issueReport = goal?.salary_issue"/>
                    </div>
                    <div v-if="goal?.salary_issue?.status == 6 && (memberData?.evaluation?.mentor.id === auth.id)">
                        <LoaderButton style="margin: 0;" :content="'成果報告レビュー'" @click="issueReport = goal?.salary_issue"/>
                    </div>
                </div>
                <div v-else-if="canCreateIssue && sub_tab === 1">
                    <div v-if="(auth.id === memberData?.id || memberData?.evaluation?.mentor.id === auth.id)">
                        <LoaderButton style="margin: 0;" @click="salaryIssue = true" :content="'昇給課題作成'"/>
                    </div>
                    <div v-else>
                        権限がありません。
                    </div>
                </div>
                 
                <div v-else-if="sub_tab === 1">
                    選択された成果目標は昇給課題を引き起こす資格がありません 
                </div>                 
            </div>
        </div>
        <Transition name="modalFade">
            <ProjectReport 
                v-if="openReport"
                :chosenGoal="goal"
                :memberData="memberData"
                :selectedProject="selectedProject"
                :isManagerOrMember="isManagerOrMember"
                @close="openReport = false"
                @reload="openReport = false, emit('close')"
            />
        </Transition>
        <Transition name="modalFade">
            <ProjectSalaryIssueCreation
                v-if="salaryIssue" 
                @close="salaryIssue = false, emit('close')"
                @selectThemeConfirm="selectThemeConfirm"
                :getIssues="getIssues"
                @goback="selectedTheme = null"
                :selectedTheme="selectedTheme"
                :memberData="memberData"
                :selectedDate="selectedDate"
                :editData="editData"
                :chosenGoal="goal"
                :evaluation="memberData.evaluation"
            />
        </Transition>
        <Transition name="modalFade">
            <Report 
                v-if="issueReport"
                :chosenIssue="issueReport"
                :member-data="memberData"
                :is-manager-or-member="isManagerOrMember"
                @close="issueReport = null"
                @reload="issueReport = null, emit('close')"
            />
        </Transition>
    </div>
</template>
<script setup lang="ts">
import { useAuthUserStore } from '@/store/auth';
import { computed, inject, ref } from 'vue';
import moment from 'moment';
import LoaderButton from '../Global/LoaderButton.vue';
import ProjectReport from './ProjectReport.vue';
import ProjectSalaryIssueCreation from './ProjectSalaryIssueCreation.vue';
import PostFiles from '../Post/PostFiles.vue';
import Files from '../Global/Files.vue';
import Report from './SalaryIssue/Report.vue';
import { SalaryIssue } from '@/interface/projectInterface';
import axios from 'axios';
import { Dialog } from '@/interface/globalInterface';
const props = defineProps([
    'goal', 
    'memberData', 
    'selectedProject', 
    'isManagerOrMember', 
    'themeRecords',
    'selectedDate',
    'statuses'
])
const emit = defineEmits(['close'])
const auth = useAuthUserStore()
const openReport = ref(false)
const sub_tab = ref(0)
const salaryIssue = ref(false)
const selectedTheme = ref(null)
const editData = ref({})
const { confirm, notify } = inject<Dialog>('dialog')!
const refresh = inject('refresh') as Function
const issueReport = ref(null)
const canCreateIssue = computed(() => {
    const start = props.goal?.start_date ? moment(props.goal.start_date) : null;
    const end = props.goal?.end_date ? moment(props.goal.end_date) : null
    if (start && end) {
        const differenceInDays = end.diff(start, 'days')
        if (differenceInDays >= 90) {
            return true
        }
    }
    return false
})
const selectThemeConfirm = (level, theme) => {
    selectedTheme.value = getIssues(level, theme)[0]
}
const evalutionsValues = computed(() => {
    return props.themeRecords
})
const approveOutComeGoal = async(status: number) => {
    let content = ''
    switch (status) {
        case 1: 
            content = 'この成果目標を差し戻してもよろしいですか'
            break
        case 3: 
            content = 'この成果目標を承認してもよろしいですか？'
            break
        case 5: 
            content = 'この成果目標を人事承認でよろしいですか？'
            break
        case 6: 
            content = 'この昇給課題は達成でよろしいですか？'
            break
        default:
            content = 'エラーが発生しました'
            break
    }
    const answer = await confirm(content)
    if(!answer) return
    try {
        await axios.put('/approve_outcome_goal', {id: props.goal.id, status: status})
        refresh()
        emit('close')
    } catch (e) {
        notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
    }
}
const approveSalaryIssue = async(issue: SalaryIssue, status: number) => {
    let content = ''
    switch (status) {
        case 1: 
            content = 'この昇給課題を差し戻してもよろしいですか'
            break
        case 3: 
            content = 'この昇給課題を承認してもよろしいですか？'
            break
        case 5: 
            content = 'この昇給課題は人事承認でよろしいですか？'
            break
        case 6: 
            content = 'この昇給課題は達成でよろしいですか？'
            break
        default:
            content = 'エラーが発生しました'
            break
    }
    if(!issue) return
    const answer = await confirm(content)
    if(!answer) return
    try {
        await axios.put('/approve_salary_issue', { id: issue.id, status: status})
        refresh()
        emit('close')
    } catch (e) {
        notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
    }
} 
const getIssues = (level, theme) => {
    if(evalutionsValues.value){
        const foundItem = evalutionsValues.value.find((item) => item.level === level);
        if (foundItem) {
            return foundItem.issues.filter((issue) => issue.theme === theme);
        }
    }
    return []
}

const editIssue = (issue: SalaryIssue) => {
    if(issue) {
        let template = issue
        let theme = null;
        for (const item of evalutionsValues.value) {
            const issue = item.issues.find((issue) => issue.title_full === template.theme);
            if (issue) {
                theme = issue;
            }
        }
        if(theme){
            selectedTheme.value = theme
            editData.value = template
        }
    }
    salaryIssue.value = true    
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