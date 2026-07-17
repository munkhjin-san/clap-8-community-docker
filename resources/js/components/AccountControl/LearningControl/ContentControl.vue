<template>
    <div class="lcontrol">
        <div class="vtabs">
            <div class="vtabs__list">
                <button
                    v-for="version in versions"
                    :key="version.id"
                    type="button"
                    class="vtab"
                    :class="{ 'vtab--active': version.id === selectedVersionId }"
                    @click="selectVersion(version.id)"
                >
                    <span class="vtab__name">バージョン{{ version.version_no }}<template v-if="version.label">・{{ version.label }}</template></span>
                    <span v-if="version.is_default" class="vtab__default"><span class="vtab__dot" />デフォルト</span>
                    <span class="vtab__count">{{ version.materials_count ?? 0 }}</span>
                </button>
                <button type="button" class="vtab vtab--add" @click="addVersionModal = true">＋ 新しいバージョン</button>
            </div>
            <div v-if="selectedVersion" class="vtabs__actions">
                <span v-if="selectedVersion.is_default" class="vtabs__hint">学習者にはこのバージョンが表示されます。</span>
                <button
                    v-else
                    type="button"
                    class="vaction"
                    @click="makeDefault(selectedVersion)"
                >
                    デフォルトに設定
                </button>
                <button
                    v-if="!selectedVersion.is_default && versions.length > 1"
                    type="button"
                    class="vaction vaction--danger"
                    @click="removeVersion(selectedVersion)"
                >
                    このバージョンを削除
                </button>
            </div>
        </div>

        <div class="content-control__list">
            <ExamSummaryCard
                v-if="has_case_study"
                :exam="exam"
                :menu-items="examMenuItems"
            />
            <LearningMaterialCard
                v-for="lesson in lessons"
                :key="lesson.id"
                :lesson="lesson"
                :menu-items="lessonMenuItems(lesson)"
                :priority-label="priorityLabel(lesson.priority)"
                :request-label="requestLabel(lesson)"
                :loading="initialLoader === lesson.id"
                :summary-menu-items="summaryMenuItems"
                :has-ai-config="hasMaterialAiConfig(lesson)"
            />
            <p v-if="!lessons.length" class="content-control__empty">このバージョンには教材がありません。</p>
        </div>
        <div @click="createWindow = true, editTarget = null" class="createBoardButton fileNewButton" title="新規作成" id="boardCreate">
            <svg class="content-control__plus-icon" version="1.1" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 32 32"><path d="M30.044 14.14c-2.402-0.231-4.804-0.341-7.206-0.422-1.535-0.058-3.071-0.079-4.606-0.090-0.326-0.002-0.587-0.265-0.588-0.591-0.004-1.537-0.018-3.074-0.078-4.613-0.092-2.4-0.218-4.802-0.542-7.205-0.084-0.612-0.565-1.119-1.205-1.206-0.769-0.103-1.477 0.437-1.582 1.206-0.324 2.401-0.449 4.804-0.542 7.205-0.059 1.536-0.074 3.071-0.078 4.606-0.001 0.325-0.263 0.59-0.59 0.59-1.534 0.005-3.068 0.020-4.602 0.078-2.404 0.094-4.805 0.219-7.207 0.543-0.612 0.081-1.119 0.564-1.205 1.205-0.103 0.769 0.436 1.477 1.205 1.58 2.402 0.324 4.804 0.449 7.207 0.543 1.536 0.059 3.074 0.073 4.612 0.078 0.325 0.001 0.587 0.262 0.59 0.587 0.011 1.536 0.033 3.070 0.090 4.606 0.080 2.402 0.192 4.805 0.423 7.207 0.066 0.699 0.622 1.278 1.349 1.348 0.823 0.079 1.556-0.524 1.633-1.348 0.231-2.402 0.342-4.805 0.423-7.207 0.057-1.538 0.079-3.077 0.090-4.615 0.002-0.324 0.263-0.583 0.587-0.586 1.538-0.011 3.077-0.034 4.615-0.090 2.402-0.080 4.804-0.193 7.206-0.423 0.7-0.066 1.279-0.622 1.349-1.349 0.076-0.823-0.528-1.557-1.351-1.634z"></path></svg>
        </div>
        <Transition name="modalFade">                              
            <LessonCreate
                v-if="createWindow"
                :editTarget="editTarget"
                :has_case_study="theme.has_case_study"
                :version-id="selectedVersionId"
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

        <Teleport to="body">
            <transition name="modalFade">
                <div v-if="addVersionModal" class="vmodal-overlay" @mousedown="addVersionModal = false">
                    <div class="vmodal" @mousedown.stop>
                        <div class="vmodal__head">
                            <span>新しいバージョンを作成</span>
                            <button type="button" class="vmodal__close" @click="addVersionModal = false">✕</button>
                        </div>
                        <div class="vmodal__body">
                            <button
                                type="button"
                                class="vmodal__choice"
                                :disabled="versionBusy || !selectedVersionId"
                                @click="createVersion(selectedVersionId)"
                            >
                                現在のバージョンからコピー
                                <span class="vmodal__choice-hint">選択中のバージョンの教材を複製します。</span>
                            </button>
                            <button
                                type="button"
                                class="vmodal__choice"
                                :disabled="versionBusy"
                                @click="createVersion(null)"
                            >
                                空で作成
                                <span class="vmodal__choice-hint">教材のない新しいバージョンを作成します。</span>
                            </button>
                        </div>
                    </div>
                </div>
            </transition>
        </Teleport>
    </div>
</template>
<script setup lang="ts">
import { onMounted, ref, computed } from 'vue';
import { useRoute } from 'vue-router';
import LessonCreate from './LessonCreate.vue';
import SummaryCreate from './SummaryCreate.vue';
import ExamCreate from './ExamCreate.vue';
import ExamAttempts from './ExamAttempts.vue';
import ExamSummaryCard from './Authoring/ExamSummaryCard.vue';
import LearningMaterialCard from './Authoring/LearningMaterialCard.vue';
import { useLearningApi } from '@/composables/learningApi';
import { isEnabled } from '@/utils/learningProgress';
import { LESSON_MATERIAL_PRIORITY } from '@/config/learning';
import type { MenuList } from '@/interface/globalInterface';
import type { LearningExam, LearningMaterial, LearningMaterialVersion, LearningSummary, LearningTheme } from '@/types/learning';
const props = defineProps<{
    theme: LearningTheme
}>()
const learningApi = useLearningApi()
const route = useRoute()
const createWindow = ref(false)
const editTarget = ref<LearningMaterial | null>(null)
const initialLoader = ref<number | null>(null)
const createSummary = ref(false)
const materialId = ref<number | null>(null)
const summaryData = ref<LearningSummary | null>(null)
const exam = ref<LearningExam | null>(null)
const examModal = ref(false)
const examAttemptsModal = ref(false)

const themeId = computed(() => Array.isArray(route.params.themeId) ? route.params.themeId[0] : route.params.themeId)

// ---- Content versioning ----
const versions = ref<LearningMaterialVersion[]>([])
const selectedVersionId = ref<number | null>(null)
const addVersionModal = ref(false)
const versionBusy = ref(false)
const selectedVersion = computed(() => versions.value.find(v => v.id === selectedVersionId.value) ?? null)

const loadVersions = async() => {
    if (!themeId.value) return
    const data = await learningApi.getMaterialVersions(themeId.value)
    versions.value = data ?? []
    if (!selectedVersionId.value || !versions.value.some(v => v.id === selectedVersionId.value)) {
        selectedVersionId.value = (versions.value.find(v => v.is_default) ?? versions.value[0])?.id ?? null
    }
}
const selectVersion = (id: number) => {
    if (selectedVersionId.value === id) return
    selectedVersionId.value = id
    getLesson()
}
const makeDefault = async(version: LearningMaterialVersion) => {
    if (!themeId.value) return
    const res = await learningApi.setDefaultMaterialVersion(themeId.value, version.id)
    if (res) await loadVersions()
}
const removeVersion = async(version: LearningMaterialVersion) => {
    if (!themeId.value) return
    const res = await learningApi.deleteMaterialVersion(themeId.value, version.id)
    if (res) {
        if (selectedVersionId.value === version.id) selectedVersionId.value = null
        await loadVersions()
        getLesson()
    }
}
const createVersion = async(copyFrom: number | null) => {
    if (!themeId.value || versionBusy.value) return
    versionBusy.value = true
    try {
        const created = await learningApi.createMaterialVersion(themeId.value, { copy_from: copyFrom })
        if (created) {
            addVersionModal.value = false
            selectedVersionId.value = created.id
            await loadVersions()
            getLesson()
        }
    } finally {
        versionBusy.value = false
    }
}

onMounted(async() => {
    await loadVersions()
    getLesson()
    getExam()
})
const has_case_study = computed(() => {
    return isEnabled(props.theme?.has_case_study)
})
const lessons = ref<LearningMaterial[]>([])
const getLesson = async() => {
    if (!themeId.value) return
    const data = await learningApi.getThemeMaterials(themeId.value, selectedVersionId.value)
    data && (lessons.value = data)
}
const editLesson = (lesson: LearningMaterial) => {
    editTarget.value = lesson
    createWindow.value = true
}

const createFinish = (reload?: boolean) => {
    createWindow.value = false
    createSummary.value = false
    materialId.value = null
    summaryData.value = null
    if(reload){
        getLesson()
        loadVersions()
    }

}
const deleteConfirm = async(id: number) => {
    const data = await learningApi.deleteMaterial(id)
    if (data) {
        getLesson()
        loadVersions()
    }
}
const summary = (id: number) => {
    materialId.value = id
    createSummary.value = true
}
const editSummary = (summary: LearningSummary) => {
    summaryData.value = summary
    createSummary.value = true
}
const deleteSummary = async(id: number) => {
    const data = await learningApi.deleteSummary(id)
    data && getLesson()   
}
const getExam = async() => {
    const themeId = Array.isArray(route.params.themeId) ? route.params.themeId[0] : route.params.themeId
    if (!themeId) return

    const res = await learningApi.getAdminExam(themeId)
    if (res.exists && res.exam) {
        exam.value = res.exam
    } else {
        exam.value = null
    }
}
const openExamModal = () => {
    examModal.value = true
}
const closeExamModal = (refresh?: boolean) => {
    examModal.value = false
    if(refresh){
        getExam()
    }
}
const deleteExam = async() => {
    if(!exam.value?.id) return
    const data = await learningApi.deleteAdminExam(exam.value.id)
    if(data){
        exam.value = null
    }
}
const lessonMenuItems = (lesson: LearningMaterial) => {
    const items: MenuList[] = [
        {title: '編集する', action: () => editLesson(lesson)},
        {title: '削除する', action: () => deleteConfirm(lesson.id)},
    ]
    if(lesson.priority === LESSON_MATERIAL_PRIORITY.SECTION && !isEnabled(lesson.has_question)){
        items.push({title: '理解チェックを追加', action: () => summary(lesson.id)})
    }
    return items
}
const summaryMenuItems = (summary: LearningSummary): MenuList[] => [
    {title: '編集する', action: () => editSummary(summary)},
    {title: '削除する', action: () => deleteSummary(summary.id)},
]
const priorityLabel = (priority: number) => {
    if(priority === LESSON_MATERIAL_PRIORITY.HEADER) return 'ヘッダー'
    if(priority === LESSON_MATERIAL_PRIORITY.SECTION) return 'セクション'
    return '未設定'
}
const requestLabel = (lesson: LearningMaterial) => {
    if(isEnabled(lesson.has_question)) return '質問依頼'
    if(isEnabled(lesson.has_understand)) return '理解依頼'
    return ''
}
const hasMaterialAiConfig = (lesson: LearningMaterial) => {
    if(lesson.material_type !== 'ケーススタディ') return false
    return props.theme.ai_configs?.some(config => config.config_key === `case_study_material_${lesson.id}`) ?? false
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

<style scoped>
.content-control__list{
    display: flex;
    flex-direction: column;
    gap: 20px;
    padding: 0 20px 20px;
}

.content-control__plus-icon{
    fill: rgb(0, 0, 0);
    margin: auto;
}
.content-control__empty{
    font-size: 13px;
    color: var(--third-color);
    text-align: center;
    padding: 20px 0;
}

/* ---- Version tabs ---- */
.vtabs{
    padding: 0 20px;
    margin-bottom: 16px;
}
.vtabs__list{
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    border-bottom: 1px solid var(--calendarBorder);
}
.vtab{
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 14px;
    font-size: 13px;
    color: var(--third-color);
    background: transparent;
    border: 0;
    border-bottom: 2px solid transparent;
    margin-bottom: -1px;
    cursor: pointer;
    white-space: nowrap;
}
.vtab--active{
    color: var(--primary-color);
    border-bottom-color: var(--primary-color);
}
.vtab__count{
    font-size: 11px;
    color: var(--third-color);
    background: var(--bg3);
    border-radius: 999px;
    padding: 1px 8px;
}
.vtab__default{
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 11px;
    color: var(--primary-color);
    background: var(--bg3);
    border-radius: 999px;
    padding: 2px 8px;
}
.vtab__dot{
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #2e9e4f;
}
.vtab--add{
    color: var(--primary-color);
}
.vtabs__actions{
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 0 0;
}
.vaction{
    font-size: 12px;
    padding: 5px 14px;
    border: 1px solid var(--primary-color);
    background: transparent;
    color: var(--primary-color);
    cursor: pointer;
    transition: background-color .15s ease, color .15s ease;
}
.vaction:hover{
    background: var(--primary-button);
    color: #fff;
    border-color: var(--primary-button);
}
.vaction--danger{
    color: #c0392b;
    border-color: #c0392b;
}
.vaction--danger:hover{
    background: #c0392b;
    color: #fff;
}
.vtabs__hint{
    font-size: 11px;
    color: var(--third-color);
}

/* ---- Add-version modal ---- */
.vmodal-overlay{
    position: fixed;
    inset: 0;
    z-index: 50;
    background: var(--overlay);
    display: flex;
    align-items: center;
    justify-content: center;
}
.vmodal{
    width: min(440px, 92vw);
    background: var(--background-color);
    color: var(--primary-color);
    padding: 20px;
}
.vmodal__head{
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 14px;
    margin-bottom: 16px;
}
.vmodal__close{
    background: transparent;
    border: 0;
    cursor: pointer;
    color: var(--primary-color);
    font-size: 14px;
}
.vmodal__body{
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.vmodal__choice{
    display: flex;
    flex-direction: column;
    gap: 4px;
    text-align: left;
    padding: 12px 14px;
    font-size: 13px;
    border: 1px solid var(--calendarBorder);
    background: transparent;
    color: var(--primary-color);
    cursor: pointer;
    transition: border-color .15s ease, background-color .15s ease;
}
.vmodal__choice:hover:not(:disabled){
    border-color: var(--primary-color);
    background: var(--bg3);
}
.vmodal__choice:disabled{
    opacity: .5;
    cursor: not-allowed;
}
.vmodal__choice-hint{
    font-size: 11px;
    color: var(--third-color);
}
</style>
