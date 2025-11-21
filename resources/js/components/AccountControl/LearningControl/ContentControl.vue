<template>
    <div class="lcontrol">        
        <div style="padding: 0 20px 20px;display:flex;flex-direction:column;gap:20px;">
            <div v-if="has_case_study" class="lesson-preview exam-card p-5">
                <div style="display:flex;flex-direction:column;gap:8px;">
                    <div style="font-size:18px;">試験</div>
                    <div v-if="exam" style="font-size:12px;color:gray;display:flex;flex-direction:column;gap:4px;">
                        <span>タイトル：{{ exam.title || '未設定' }}</span>
                        <span>合格基準：{{ exam.passing_score }}%</span>
                        <span>最大受験回数：{{ exam.max_attempts }}回</span>
                        <span>設問数：{{ exam.questions?.length || 0 }}問</span>
                    </div>
                    <div v-else style="font-size:12px;color:gray;">
                        試験はまだ設定されていません。
                    </div>
                </div>
                <div style="position:absolute;right:10px;top:10px;">
                    <ItemMenu :items="examMenuItems"/>
                </div>
            </div>
            <div v-for="lesson in lessons" class="lesson-preview">
                <div style="margin: 20px 40px 20px 20px;height: calc(100% - 40px);position: relative;" >
                    <div class="mb-2" style="display:flex;flex-direction:column;gap:8px;">
                        <div v-html="lesson.title"></div>
                        <div style="display:flex;gap:6px;flex-wrap:wrap;font-size:11px;">
                            <span class="lesson-chip" style="background: var(--hover-border);color:#fff;padding:2px 8px;">{{ priorityLabel(lesson.priority) }}</span>
                            <span class="lesson-chip" v-if="lesson.material_type" style="background: var(--bg2);color: var(--text-color);padding:2px 8px;">{{ lesson.material_type }}</span>
                            <span class="lesson-chip" v-if="requestLabel(lesson)" style="background: #ffe8cc;color:#b15c00;padding:2px 8px;">{{ requestLabel(lesson) }}</span>
                            <span class="lesson-chip" v-if="lesson.has_question && lesson.material_type === 'ケーススタディ'" style="background:#dbeafe;color:#1d4ed8;padding:2px 8px;">QA ケース</span>
                        </div>
                    </div>
                    <div class="flex flex-col" style="font-size: 12px;color: gray;">
                        <span>プロンプトID：{{ lesson.prompt_id }}</span>
                        <div v-if="lesson.priority === 1 && lesson.summaries.length" class="mt-[10px] text-sm" style="color: var(--primary-color);">理解チェック</div>
                        <div v-if="lesson.priority === 1" v-for="summary in lesson.summaries" :key="summary.id" class="flex justify-between items-center">
                            <span>{{ summary.title }}</span>
                            <ItemMenu :items="[
                                {title: '編集する', action: () => editSummary(summary)},
                                {title: '削除する', action: () => deleteSummary(summary.id)},
                            ]"/>
                        </div>
                    </div>
                    <Transition name="modalFade">
                        <div class="cal-month-loader" v-if="initialLoader == lesson.id" style="top: 50%;">
                            <div id="loaderMini">
                                <div class="spinner-mini" style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
                            </div>
                        </div>
                    </Transition>
                </div>
                
                <div style="position: absolute;right: 10px;top: 10px;">                                            
                    <ItemMenu :items="lessonMenuItems(lesson)"/> 
                </div>  
            </div>
        </div>
        <div @click="createWindow = true, editTarget = null" class="createBoardButton fileNewButton" title="新規作成" id="boardCreate">
            <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 32 32" style="fill: rgb(0, 0, 0); margin: auto;"><path d="M30.044 14.14c-2.402-0.231-4.804-0.341-7.206-0.422-1.535-0.058-3.071-0.079-4.606-0.090-0.326-0.002-0.587-0.265-0.588-0.591-0.004-1.537-0.018-3.074-0.078-4.613-0.092-2.4-0.218-4.802-0.542-7.205-0.084-0.612-0.565-1.119-1.205-1.206-0.769-0.103-1.477 0.437-1.582 1.206-0.324 2.401-0.449 4.804-0.542 7.205-0.059 1.536-0.074 3.071-0.078 4.606-0.001 0.325-0.263 0.59-0.59 0.59-1.534 0.005-3.068 0.020-4.602 0.078-2.404 0.094-4.805 0.219-7.207 0.543-0.612 0.081-1.119 0.564-1.205 1.205-0.103 0.769 0.436 1.477 1.205 1.58 2.402 0.324 4.804 0.449 7.207 0.543 1.536 0.059 3.074 0.073 4.612 0.078 0.325 0.001 0.587 0.262 0.59 0.587 0.011 1.536 0.033 3.070 0.090 4.606 0.080 2.402 0.192 4.805 0.423 7.207 0.066 0.699 0.622 1.278 1.349 1.348 0.823 0.079 1.556-0.524 1.633-1.348 0.231-2.402 0.342-4.805 0.423-7.207 0.057-1.538 0.079-3.077 0.090-4.615 0.002-0.324 0.263-0.583 0.587-0.586 1.538-0.011 3.077-0.034 4.615-0.090 2.402-0.080 4.804-0.193 7.206-0.423 0.7-0.066 1.279-0.622 1.349-1.349 0.076-0.823-0.528-1.557-1.351-1.634z"></path></svg>
        </div>
        <Transition name="modalFade">                              
            <LessonCreate 
                v-if="createWindow"
                :editTarget="editTarget"
                :has_case_study="theme.has_case_study"
                @createFinish="createFinish"           
            />
            
        </Transition>
        <Transition name="modalFade">
            <SummaryCreate 
                v-if="createSummary"
                :materialId="materialId"
                :summaryData="summaryData"
                @createFinish="createFinish"
            />
        </Transition>
        <Transition name="modalFade">
            <ExamCreate
                v-if="examModal"
                :themeId="Number(route.params.themeId)"
                :examData="exam"
                @close="closeExamModal"
            />
        </Transition>
        <Transition name="modalFade">
            <ExamAttempts 
                v-if="examAttemptsModal"
                :themeId="Number(route.params.themeId)"
                @close="examAttemptsModal = false"
            />
        </Transition>
    </div>  
</template>
<script setup>
import { onMounted, ref, computed } from 'vue';
import { useRoute } from 'vue-router';
import LessonCreate from './LessonCreate.vue';
import ItemMenu from '@/components/Global/ItemMenu.vue'
import SummaryCreate from './SummaryCreate.vue';
import ExamCreate from './ExamCreate.vue';
import ExamAttempts from './ExamAttempts.vue';
import { useApi } from '@/composables/api';
import { useDialog } from '@/composables/dialog';
const props = defineProps(['theme'])
const api = useApi()
const { ask, ping, toast } = useDialog()
const route = useRoute()
const createWindow = ref(false)
const createAssistantWindow = ref(false)
const editTarget = ref(null)
const editAssistantTarget = ref(null)
const initialLoader = ref(null)
const createSummary = ref(false)
const materialId = ref(null)
const summaryData = ref(null)
const exam = ref(null)
const examModal = ref(false)
const examAttemptsModal = ref(false)
onMounted(() => {
    getLesson()
    getExam()
})
const has_case_study = computed(() => {
    return props.theme?.has_case_study || false
})
const lessons = ref([])
const getLesson = async() => {
    const data = await api.get('/get_lessons', {   
        lesson_theme_id: route.params.themeId
    })
    data && (lessons.value = data)
}
const editLesson = (lesson) => {
    editTarget.value = lesson
    createWindow.value = true
}

const createFinish = (reload) => {
    createWindow.value = false
    createAssistantWindow.value = false
    createSummary.value = false
    materialId.value = null
    summaryData.value = null
    if(reload){
        getLesson()
    }
    
}
const deleteConfirm = async(id) => {     
    const data = await api.del('/lesson_remove_record', {id: id}, {
        ask: '削除しますか？',
        toast: '削除しました。'
    })            
    data && getLesson()
}
const summary = (id) => {
    materialId.value = id
    createSummary.value = true
}
const editSummary = (summary) => {
    summaryData.value = summary
    createSummary.value = true
}
const deleteSummary = async(id) => {     
    const data = await api.del('/lesson_remove_summary', {id: id}, {
        ask: '削除しますか？',
        toast: '削除しました。'
    })
    data && getLesson()   
}
const getExam = async() => {
    const res = await api.get('/lesson_exam', {
        lesson_theme_id: route.params.themeId
    })

    if (res.exists && res.exam) {
        exam.value = res.exam
    }
}
const openExamModal = () => {
    examModal.value = true
}
const closeExamModal = (refresh) => {
    examModal.value = false
    if(refresh){
        getExam()
    }
}
const deleteExam = async() => {
    if(!exam.value?.id) return
    const data = await api.del('/lesson_exam', {exam_id: exam.value.id}, {
        ask: '試験を削除しますか？',
        toast: '試験を削除しました。'
    })
    if(data){
        exam.value = null
    }
}
const lessonMenuItems = (lesson) => {
    const items = [
        {title: '編集する', action: () => editLesson(lesson)},
        {title: '削除する', action: () => deleteConfirm(lesson.id)},
    ]
    if(lesson.priority === 1 && !lesson.has_question){
        items.push({title: '理解チェックを追加', action: () => summary(lesson.id)})
    }
    return items
}
const priorityLabel = (priority) => {
    if(priority === 0) return 'ヘッダー'
    if(priority === 1) return 'セクション'
    return '未設定'
}
const requestLabel = (lesson) => {
    if(lesson.has_question) return '質問依頼'
    if(lesson.has_understand) return '理解依頼'
    return ''
}
const examMenuItems = computed(() => {
    const items = [
        {title: exam.value ? '試験を編集する' : '試験を作成する', action: () => openExamModal()}
    ]
    if(exam.value){
        // items.push({title: '試験結果を見る', action: () => examAttemptsModal.value = true})
        items.push({title: '試験を削除する', action: () => deleteExam()})
    }
    return items
})
</script>

