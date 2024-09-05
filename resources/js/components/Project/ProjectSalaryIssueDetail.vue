<template>
    <div class="routeposition">
        <Transition name="modalFade">
            <div class="cal-month-loader" style="height: calc(100% - 60px); top: 60px;" v-if="initialLoader">
                <div id="loaderMini">
                    <div class="spinner-mini" style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
                </div>
            </div>
        </Transition>
        <div class="post-header">
            <div class="project-search-wrap cursor-pointer" @click="router.go(-1)" style="display:flex; align-items: center;">
                <svg version="1.1" width="15" height="15" viewBox="0 0 20 32" xmlns="http://www.w3.org/2000/svg">
                    <path d="M0.775 17.789c1.305 1.166 2.612 2.332 3.927 3.486 1.311 1.161 2.634 2.308 3.953 3.46 1.316 1.156 2.646 2.296 3.973 3.439 1.33 1.139 2.667 2.273 4.015 3.394 0.662 0.551 1.647 0.52 2.272-0.107 0.65-0.654 0.619-1.725-0.020-2.393-1.198-1.253-2.407-2.495-3.621-3.729-1.232-1.245-2.462-2.492-3.704-3.725-0.902-0.9-1.803-1.802-2.707-2.699-0.033-0.032-0.055-0.069-0.072-0.106-0.045-0.036-0.082-0.080-0.111-0.129-0.069-0.047-0.129-0.117-0.176-0.216-0.021-0.047-0.044-0.092-0.066-0.136-0.12-0.062-0.214-0.168-0.246-0.325-0.001-0.005-0.002-0.009-0.003-0.014-0.104-0.157-0.187-0.327-0.254-0.505-0.109-0.185-0.182-0.388-0.226-0.601-0.002-0.012-0.005-0.024-0.007-0.036-0.016-0.085-0.028-0.172-0.036-0.259-0.195-0.593-0.26-1.183 0.030-1.653 0.006-0.157 0.067-0.277 0.157-0.361 0.019-0.050 0.039-0.099 0.063-0.149 0.040-0.084 0.1-0.145 0.17-0.188 0.008-0.015 0.019-0.028 0.028-0.042 0.032-0.13 0.106-0.228 0.202-0.293 0.072-0.145 0.157-0.287 0.26-0.43 0.046-0.063 0.101-0.113 0.163-0.151 0.018-0.020 0.037-0.038 0.059-0.054 0.014-0.059 0.044-0.116 0.094-0.165 0.9-0.888 1.797-1.782 2.699-2.672 1.244-1.231 2.476-2.475 3.714-3.717l1.843-1.871 1.832-1.885c0.655-0.681 0.669-1.793-0.044-2.48-0.652-0.631-1.693-0.624-2.385-0.038l-1.964 1.66-1.995 1.71c-1.32 1.149-2.648 2.293-3.962 3.45s-2.636 2.308-3.943 3.474c-1.311 1.159-3.284 2.806-4.106 3.689s-0.792 2.492 0.191 3.369z"></path>
                </svg>
                <div class="member-wrap">
                    <UserIcon :user="memberData" :disable-instant="true" size="25" imgClass="userMidIcon"/>
                    {{ memberData?.name }}
                </div>
            </div>  
        </div>
        <div class="project-wrapper">    
            <div class="goals-wrap">
                <div style="overflow: hidden; position: relative;height:100%;">
                    <div class="goals-inner">
                        <div style="display: flex;justify-content: space-between;padding: 20px 0;background-color:var(--background-color);position:sticky;top:0;z-index: 1;">
                            <h2>昇給課題</h2>
                            <div class="locale-selector" style="width: auto;">
                                <select name="locales" v-model="value" class="dropDownSelector cursor-pointer" style="width: fit-content;">
                                    <option :value="date" v-for="date in seikaOptions">{{date}}</option>
                                </select>
                            </div>
                        </div>
                        <div v-if="salaryIssues.length" v-for="issue in salaryIssues" style="position: relative">
                            <div class="goal-detail">
                                <div>
                                    <div>評価課題</div>
                                    <div>{{ issue?.theme }}</div>
                                </div>
                                <div>
                                    <div>昇給課題ステータス</div>
                                    <div class="kadai-content">{{ statuses[issue.status] }}</div>
                                </div>
                                <div>
                                    <div>昇給課題タイトル</div>
                                    <div class="kadai-content">{{ issue.title }}</div>
                                </div>
                                <div>
                                    <div>昇給課題内容・詳細</div>
                                    <div class="kadai-content">{{ issue.content }}</div>
                                </div>
                                <div>
                                    <div>課題達成による取得能力</div>
                                    <div class="kadai-content">{{ issue.ability }}</div>
                                </div>
                                <div>
                                    <div>AI添削結果</div>
                                    <div class="kadai-content">{{ issue.review }}</div>
                                </div>
                            </div>

                            
                            
                            <div v-if="memberData && auth.id === memberData.id && issue?.status < 2" style="position: absolute;right: 10px;top: 10px;">                                            
                                <ItemMenu :items="[
                                    {title: '編集する', action: () => editIssue(issue)},
                                    {title: '削除する', action: () => deleteIssue(issue)}
                                ]"/> 
                            </div>
                            
                        </div>
                        <div v-else class="no-comment-text">
                            現在レコードはありません。
                        </div> 
                    </div>
                    
                    <div v-if="memberData && auth.id === memberData.id" title="新規作成" id="boardCreate" class="createBoardButton fileNewButton" @click="createWindow = true" :style="{zIndex: 7}">
                        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 32 32" style="fill:#000;margin:auto;">
                            <path d="M30.044 14.14c-2.402-0.231-4.804-0.341-7.206-0.422-1.535-0.058-3.071-0.079-4.606-0.090-0.326-0.002-0.587-0.265-0.588-0.591-0.004-1.537-0.018-3.074-0.078-4.613-0.092-2.4-0.218-4.802-0.542-7.205-0.084-0.612-0.565-1.119-1.205-1.206-0.769-0.103-1.477 0.437-1.582 1.206-0.324 2.401-0.449 4.804-0.542 7.205-0.059 1.536-0.074 3.071-0.078 4.606-0.001 0.325-0.263 0.59-0.59 0.59-1.534 0.005-3.068 0.020-4.602 0.078-2.404 0.094-4.805 0.219-7.207 0.543-0.612 0.081-1.119 0.564-1.205 1.205-0.103 0.769 0.436 1.477 1.205 1.58 2.402 0.324 4.804 0.449 7.207 0.543 1.536 0.059 3.074 0.073 4.612 0.078 0.325 0.001 0.587 0.262 0.59 0.587 0.011 1.536 0.033 3.070 0.090 4.606 0.080 2.402 0.192 4.805 0.423 7.207 0.066 0.699 0.622 1.278 1.349 1.348 0.823 0.079 1.556-0.524 1.633-1.348 0.231-2.402 0.342-4.805 0.423-7.207 0.057-1.538 0.079-3.077 0.090-4.615 0.002-0.324 0.263-0.583 0.587-0.586 1.538-0.011 3.077-0.034 4.615-0.090 2.402-0.080 4.804-0.193 7.206-0.423 0.7-0.066 1.279-0.622 1.349-1.349 0.076-0.823-0.528-1.557-1.351-1.634z"></path>
                        </svg>
                    </div>
                        
                </div>
            </div>
        
        </div>
        <Transition name="modalFade">
            <ProjectSalaryIssueCreation
                v-if="createWindow" 
                @close="createWindow = false"
                @selectThemeConfirm="selectThemeConfirm"
                :getIssues="getIssues"
                @getTemplates="getSalaryIssues"
                @goback="selectedTheme = null"
                :selectedTheme="selectedTheme"
                :editTemplateId="editTemplateId"
                :periodString="value"
                :editData="editData"
            />
        </Transition>
    </div>
</template>
<script setup lang="ts">
import { inject, onMounted, ref, computed, watch } from 'vue';
import axios from 'axios';
import { Dialog } from '@/interface/globalInterface';
import { useRoute, useRouter } from 'vue-router';
import UserIcon from '../Board/Mixed/UserIcon.vue';
import { useAuthUserStore } from '@/store/auth';
import { SalaryIssue } from '@/interface/projectInterface';
import ProjectSalaryIssueCreation from './ProjectSalaryIssueCreation.vue';
import ItemMenu from '../Global/ItemMenu.vue';
const route = useRoute()
const props = defineProps(['selectedProject', 'seikaOptions'])
const router = useRouter()
const createWindow = ref(false)
const auth = useAuthUserStore()
const initialLoader = ref(false)
const themeRecords = ref<Theme[]>([])
const value = defineModel<string>()
const salaryIssues = ref<SalaryIssue[]>([])
const selectedTheme = ref(null)
const editIssueId = ref(null)
const editTemplateId = ref<number | null>(null)
const editData = ref({})
interface Theme {
    issues: any;
    level: any;
}
const { notify, confirm } = inject<Dialog>('dialog')!
const statuses = ['作成中', '申請中', '未達成', '達成']
onMounted(() => {
    getThemes()
    getSalaryIssues()
})
watch(value, (newValue) => {
    if(newValue){
        initialLoader.value = true
        getSalaryIssues()
    }
})
const getThemes = async() => {
    console.log('why two')
    try{
        const res = await axios.get('/get_kadai_themes')
        if(res.data){
            themeRecords.value = res.data
        }
    } catch (e) {
        notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
    }
}
const memberData = computed(() => {
    const memberId = route.params.memberId
    return props.selectedProject?.members.find(ob => ob.id == memberId) || props.selectedProject?.manager.find(ob => ob.id == memberId)
})
const getSalaryIssues = async() => {
    if (!value.value) return
    const dates = value.value.match(/\d{4}\.\d{1,2}\.\d{1,2}/g)
    if(dates){
        const formattedDates = dates.map(date => {
            const [year, month, day] = date.split('.');
            return `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        });
        try {
            salaryIssues.value = await axios.post('/get_salary_issues', {date: formattedDates[0]}).then(res => res.data)
            console.log(salaryIssues.value)
            initialLoader.value = false
        } catch (e) {

        }
    }
    
}

const evalutionsValues = computed(() => {
    return themeRecords.value
})
const selectThemeConfirm = (level, theme) => {
    selectedTheme.value = getIssues(level, theme)[0]
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
        editIssueId.value = null
        for (const item of evalutionsValues.value) {
            const issue = item.issues.find((issue) => issue.title_full === template.theme);
            if (issue) {
                theme = issue;
            }
        }
        if(theme){
            editTemplateId.value = template.id
            selectedTheme.value = theme
            editData.value = template
        }
    }
    createWindow.value = true    
}
const deleteIssue = (issue: SalaryIssue) => {

}
</script>