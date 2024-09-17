<template>
    <div class="goals-wrap">
        <div style="overflow: hidden; position: relative;height:100%;">
            <div class="goals-inner">
                <div style="display: flex;justify-content: flex-end;padding: 20px 0;background-color:var(--background-color);position:sticky;top:0;z-index: 1;">
                    <!-- <div>人事考課</div> -->
                    <div class="locale-selector" style="width: auto;">
                        <select name="locales" v-model="evaluationDate" class="dropDownSelector cursor-pointer" style="width: fit-content;">
                            <option :value="date.evaluationDate" v-for="date in evaluationOptions">{{ parseDate(date.evaluationDate) }}</option>
                        </select>
                    </div>
                </div>
                <div v-if="projectEvaluations" style="position: relative">
                    <div style="display: flex; gap: 20px;">
                        <div>
                            <div style="margin-bottom: 10px;">雇用形態</div>
                            <div>{{ projectEvaluations?.evaluation?.employment_type }}</div>
                        </div>
                        <div>
                            <div style="margin-bottom: 10px;">等級</div>
                            <div>{{ projectEvaluations?.evaluation?.grade }}</div>
                        </div>
                        <div>
                            <div style="margin-bottom: 10px;">職階</div>
                            <div>{{ projectEvaluations?.evaluation?.general_position }}</div>
                        </div>
                        <div>
                            <div style="margin-bottom: 10px;">職務</div>
                            <div>{{ projectEvaluations?.evaluation?.current_level }}</div>
                        </div>
                        
                    </div>
                    
                    <div class="si-box" style="display: flex; gap: 20px;" v-if="(auth.id === memberData.id || auth.id === memberData?.evaluation?.mentor?.id)">
                        <!-- <div>
                            <div style="margin-bottom: 10px;">等級</div>
                            <div>{{ projectEvaluations?.evaluation?.grade }}</div>
                        </div> -->
                        <div>
                            <div style="margin-bottom: 10px;">給料（非公開）</div>
                            <div>{{ projectEvaluations?.evaluation?.current_salary_rank }}</div>
                        </div>
                        <div v-if="currentPosition?.value">
                            <div style="margin-bottom: 10px;">役職手当（非公開）</div>
                            <div>{{ currentPosition?.value }}</div>
                        </div>
                        
                    </div>
                    
                    <div class="goal-detail" style="margin-top: 20px;gap: 10px;position:relative;">
                        <div>{{selectedDate.lastname}}</div>
                        <!-- <div style="display: flex; gap: 20px;">
                            <div>
                                <div style="margin-bottom: 10px;">達成された成果目標</div>
                                <div>{{ projectEvaluations?.outcome_goals?.length }}／6</div>
                            </div>
                            
                            
                        </div> -->
                        <div style="display: flex; gap: 20px;">
                            <div>
                                <div style="margin-bottom: 10px;">成果目標達成率</div>
                                <div>{{ goalPercentage }}%／600%</div>
                            </div>
                            <div>
                                <div style="margin-bottom: 10px;">昇給課題設定数</div>
                                <div>{{ projectEvaluations?.last_set }}</div>
                            </div>
                            <div>
                                <div style="margin-bottom: 10px;">昇給課題達成数</div>
                                <div>{{ projectEvaluations?.last_achieved }}</div>
                            </div>
                            <!-- <div>
                                <div style="margin-bottom: 10px;">雇用条件変更有無</div>
                                <div>{{ employeChange[projectEvaluations?.change_in_position] }}</div>
                            </div> -->
                            <!-- <div> 
                                <div style="margin-bottom: 10px;">人事確定ー人事会議使用欄ー</div>
                                <div>{{ confirmOptions[projectEvaluations?.position_approved] }}</div>
                            </div> -->
                            <div>
                                <div style="margin-bottom: 10px;">人事結果</div>
                                <div v-for="candidate in projectEvaluations?.candidate">{{ candidate.last_candidate }}</div>
                            </div>
                            
                        </div>
                        <div style="display: flex; gap: 20px;" v-if="(auth.id === memberData.id || auth.id === memberData?.evaluation?.mentor?.id)">
                            <div>
                                <div style="margin-bottom: 10px;">新給料（非公開）</div>
                                <div>{{ projectEvaluations?.evaluation?.after_salary_rank }}</div>
                            </div>
                            <div v-if="newPosition?.value">
                                <div style="margin-bottom: 10px;">新役職手当（非公開）</div>
                                <div>{{ newPosition?.value }}</div>
                            </div>
                            <div v-if="newPosition">
                                <div style="margin-bottom: 10px;">次期職階</div>
                                <div>{{ newPosition?.name }}</div>
                            </div>
                        </div>
                        <div v-if="memberData && (auth.id === memberData.id || auth.id === memberData?.evaluation?.mentor?.id || auth.id === 612)" style="position: absolute;right: 10px;top: 10px;">                                            
                            <ItemMenu :items="[
                                {title: '編集する', action: () => handleClick(0)},
                                {title: '削除する', action: () => deleteEvaluation()}
                            ]"/> 
                        </div>
                    </div>
                    <div class="goal-detail" style="margin-top: 20px;gap: 10px;position: relative;">
                        <div>{{ selectedDate.name }}</div>
                        <div style="display: flex; gap: 20px;">
                            <div>
                                <div style="margin-bottom: 10px;">昇給課題設定可能数</div>
                                <div>{{ possibleSetIssue }}／4</div>
                            </div>
                            <div>
                                <div style="margin-bottom: 10px;">昇給課題設定数</div>
                                <div>{{ projectEvaluations?.salary_issues?.length }}／{{ possibleSetIssue }}</div>
                            </div>
                        </div>
                        
                        <div>
                            <div style="margin-bottom: 10px;">保有能力</div>
                            <div v-for="checked in projectEvaluations?.checklist">{{ checked.content }}</div>
                        </div>
                        <div style="display: flex; gap: 20px;">
                            
                            <div>
                                <div style="margin-bottom: 10px;">能力保有数</div>
                                <div>{{ projectEvaluations?.checklist?.length }}／{{ criteriaMaster?.[0]?.standards.length }}</div>
                            </div>
                            <!-- <div>
                                <div style="margin-bottom: 10px;">保有数</div>
                                <div></div>
                            </div> -->
                            <div>
                                <div style="margin-bottom: 10px;">能力保有率</div>
                                <div>{{ Math.round(projectEvaluations?.checklist?.length / criteriaMaster?.[0]?.standards.length * 100) }}%</div>
                            </div>
                            <div> 
                                <div style="margin-bottom: 10px;">人事計画</div>
                                <div v-for="candidate in projectEvaluations?.candidate">{{ candidate.next_candidate }}</div>
                            </div>
                        </div>
                        <div>
                            <div style="margin-bottom: 10px;">昇格後のビジョン</div>
                            <div>{{ projectEvaluations?.reason }}</div>
                        </div>
                        
                        <div>
                            <div style="margin-bottom: 10px;">メンター記入欄</div>
                            <div>{{ projectEvaluations?.mentor_entry }}</div>
                        </div>
                        <div v-if="memberData && (auth.id === memberData.id || auth.id === memberData?.evaluation?.mentor?.id || auth.id === 612)" style="position: absolute;right: 10px;top: 10px;">                                            
                            <ItemMenu :items="[
                                {title: '編集する', action: () => handleClick(1)},
                                {title: '削除する', action: () => deleteEvaluation()}
                            ]"/> 
                        </div>
                    </div>
                    <!-- <div class="goal-detail" style="margin-top: 20px;gap: 10px;"> -->
                        <!-- <div>人事成果</div> -->
                        <!-- <div style="display: flex; gap: 20px;"> -->
                            <!-- <div>
                                <div style="margin-bottom: 10px;">達成された成果目標</div>
                                <div>{{ projectEvaluations?.outcome_goals?.length }}／6</div>
                            </div>
                            <div>
                                <div style="margin-bottom: 10px;">成果目標達成率合計</div>
                                <div>{{ goalPercentage }}%／600%</div>
                            </div> -->
                            <!-- <div>
                                <div style="margin-bottom: 10px;">次期課題設定可能数</div>
                                <div>{{ possibleSetIssue }}／4</div>
                            </div>
                            <div>
                                <div style="margin-bottom: 10px;">次期昇給課題設定数</div>
                                <div>{{ projectEvaluations?.salary_issues?.length }}／{{ possibleSetIssue }}</div>
                            </div> -->
                        <!-- </div> -->
                    <!-- </div> -->
                    
                </div>
                <div v-else class="no-comment-text">
                    現在レコードはありません。
                </div>
            </div>
            <div v-if="memberData && ((auth.id === memberData.id && auth.user?.position_id !== 13 )|| auth.id === memberData?.evaluation?.mentor?.id || auth.id === 612) && !projectEvaluations" title="新規作成" id="boardCreate" class="createBoardButton fileNewButton" @click="handleClick(0)" :style="{zIndex: 7}">
                <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 32 32" style="fill:#000;margin:auto;">
                    <path d="M30.044 14.14c-2.402-0.231-4.804-0.341-7.206-0.422-1.535-0.058-3.071-0.079-4.606-0.090-0.326-0.002-0.587-0.265-0.588-0.591-0.004-1.537-0.018-3.074-0.078-4.613-0.092-2.4-0.218-4.802-0.542-7.205-0.084-0.612-0.565-1.119-1.205-1.206-0.769-0.103-1.477 0.437-1.582 1.206-0.324 2.401-0.449 4.804-0.542 7.205-0.059 1.536-0.074 3.071-0.078 4.606-0.001 0.325-0.263 0.59-0.59 0.59-1.534 0.005-3.068 0.020-4.602 0.078-2.404 0.094-4.805 0.219-7.207 0.543-0.612 0.081-1.119 0.564-1.205 1.205-0.103 0.769 0.436 1.477 1.205 1.58 2.402 0.324 4.804 0.449 7.207 0.543 1.536 0.059 3.074 0.073 4.612 0.078 0.325 0.001 0.587 0.262 0.59 0.587 0.011 1.536 0.033 3.070 0.090 4.606 0.080 2.402 0.192 4.805 0.423 7.207 0.066 0.699 0.622 1.278 1.349 1.348 0.823 0.079 1.556-0.524 1.633-1.348 0.231-2.402 0.342-4.805 0.423-7.207 0.057-1.538 0.079-3.077 0.090-4.615 0.002-0.324 0.263-0.583 0.587-0.586 1.538-0.011 3.077-0.034 4.615-0.090 2.402-0.080 4.804-0.193 7.206-0.423 0.7-0.066 1.279-0.622 1.349-1.349 0.076-0.823-0.528-1.557-1.351-1.634z"></path>
                </svg>
            </div>
                
        </div>
        <Transition name="modalFade">
            <EvaluationCreation 
                v-if="createWindow"
                :criteriaMaster="criteriaMaster"
                :memberData="memberData"
                :evaluationDate="evaluationDate"
                :selectedDate="selectedDate"
                :edit-data="projectEvaluations"
                :step="step"
                @reload="reload"
                @search="search"
                @close="createWindow = false"
                @next="step = 1"
            />
        </Transition>
    </div>
       
    
</template>
<script setup lang="ts">
import { generalPositions, debounce, evaluationDateOptions, parseDate, detailedDateOptions } from '@/utils/tools';
import { computed, inject, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import UserIcon from '@/components/Board/Mixed/UserIcon.vue';
import { useAuthUserStore } from '@/store/auth';
import axios from 'axios';
import { Increase } from '@/interface/projectInterface';
import EvaluationCreation from './EvaluationCreation.vue';
import ItemMenu from '@/components/Global/ItemMenu.vue';
import moment from 'moment';
import { Dialog } from '@/interface/globalInterface';
const props = defineProps([
    'selectedProject',
    'memberData'
])
const initialLoader = defineModel()
const positions = generalPositions()
const router = useRouter()
const auth = useAuthUserStore()
const projectEvaluations = ref<Increase | null>(null)
const evaluationOptions = detailedDateOptions()
interface Date {
    value: any;
    lastname: string;
    name: string;
}
const selectedDate = inject('selectedDate') as Date
const evaluationDate = inject('evaluationDate') as Date
const metricDate = inject('metricDate') as Date
const criteriaMaster = ref<any>([])
const route = useRoute()
const createWindow = ref(false)
const step = ref(0)
const getProjects = inject('getProjects') as Function
const setDates = inject('setDates') as Function
const { notify, confirm } = inject<Dialog>('dialog')!
const employeChange = [
    '変更なし',
    '変更あり'
]
const confirmOptions = [
    '未確定',
    '確定'
]
watch(() => evaluationDate.value, async(newValue) => {
    if(newValue) {
        initialLoader.value = true
        await getProjects()
        reload()
    }
})
onMounted(async() => {
    reload()
    setDates()
    
})
const currentPosition = computed(() => {
    return positions.find(ob => ob.name === projectEvaluations.value?.evaluation?.general_position)
})
const newPosition = computed(() => {
    return positions.find(ob => ob.name === projectEvaluations.value?.evaluation?.new_position)
})
const handleClick = (num: number) => {
    // if (auth.id === memberData.value.id) {
    //     step.value = 1
    // }
    step.value = num
    createWindow.value = true
}
const reload = async() => {
    await getEvaluations()
    firstFetch()
}
const goalPercentage = computed(() => {
    return projectEvaluations.value?.outcome_goals?.reduce((acc, element) => acc + element.achievement_rate, 0)
})
const possibleSetIssue = computed(() => {
    const percentage = goalPercentage.value;
    if (percentage === 600) {
        return 4;
    } else if (percentage >= 500) {
        return 3;
    } else if (percentage >= 400) {
        return 2;
    } else if (percentage >= 300) {
        return 1;
    } else {
        return 0;
    }
})
const firstFetch = async() => {
    try {
        criteriaMaster.value = await axios.post('/get_project_criteria', {keywords: projectEvaluations.value?.evaluation?.current_level, first: false }).then(res => res.data)
    } catch (e) {
        notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
    }
}
const search = debounce(async(key: string) => {
    try {
        criteriaMaster.value = await axios.post('/get_project_criteria', {keywords: key, first: false}).then(res => res.data)
    } catch (e) {
        notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
    }
    
}, 350)
const getEvaluations = async() => {
    initialLoader.value = true
    try {
        const params = {
            date: evaluationDate.value,
            user_id: props.memberData.id
        }
        const response = await axios.post('/get_set_increase', params)
        projectEvaluations.value = response.data
        setTimeout(() => {
            initialLoader.value = false
        }, 300);
    } catch (e) {
        notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
    }
}

const deleteEvaluation = async() => {
    const id = projectEvaluations.value?.id
    const answer = await confirm('この人事考課を削除してもよろしいですか?')
    if(!answer) return
    try {
        await axios.delete(`/delete_evaluation?id=${id}`)
        getEvaluations()
    } catch (e) {
        notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
    }
}
</script>