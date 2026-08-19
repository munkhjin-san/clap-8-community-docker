<template>
    <div class="w-full h-full relative overflow-hidden">
        <div class="w-full h-full absolute top-0 left-0 bg-[var(--bg2)] z-10 flex items-center justify-center" v-if="mainLoader">
            <div class="spinner-micro"></div>
        </div>
        <div v-if="selectedTopic && isEnabled(selectedTopic.active) && route.name == 'basic'" :style="{height: route.name == 'basic'  ? '100%' : '0'}">
            <div class="h-full overflow-y-auto overflow-x-hidden">
                <div
                    v-if="headerMaterials.length"
                    class="m-[20px] border border-solid border-[var(--formBorder)]"
                >
                    <!-- Collapsible basic knowledge for every learner. First-timers
                         open by default; repeaters / salary challengers (who have
                         already learned it) start collapsed. The toggle is set off by
                         its own background with a single line above the content. -->
                    <button
                        type="button"
                        class="w-full flex items-center gap-[10px] px-[15px] py-[20px] border-0 cursor-pointer text-[var(--primary-color)]"
                        @click="showBasic = !showBasic"
                    >
                        <Back size="11" class="transition-transform duration-200" :class="showBasic ? 'rotate-[270deg]' : 'rotate-180'" />
                        <span>基礎知識内容</span>
                        <div @click.stop class="w-fit bg-[var(--primary-button)] text-[#fff] px-[10px] h-[30px] ml-auto" v-show="showBasic">
                            <TTSPlayer
                                :text="getTextContent(getAllContent())"
                                :color="'#fff'"
                            />
                        </div>
                    </button>
                    <div
                        v-show="showBasic"
                        class="bg-[var(--background-color)] leading-[1.8] flex flex-col gap-[20px] relative break-words whitespace-break-spaces p-5"
                    >
                        
                        <div class="lessons-topic" v-for="topic in headerMaterials">
                            <LearningContentRenderer :content="topic.content" />
                        </div>
                    </div>
                </div>
                <LearningPreviousExperiencePanel
                    v-if="previousExperience?.has_experience && previousExperience.portfolio"
                    :theme-title="previousExperience.theme?.title"
                    :portfolio="previousExperience.portfolio"
                    :theme="selectedTopic"
                    :personal-material="previousExperience.personal_material"
                    :can-generate-personal-material="previousExperience.can_generate_personal_material"
                    :is-salary-challenge="previousExperience.is_salary_challenge"
                    :refresh-lesson-view="refreshPreviousExperience"
                />
                <LearningTopicMenu
                    v-else
                    class="learning-basic-menu"
                    :items="basicMenuItems"
                    @select="selectMenuItem"
                />
            </div>
        </div>
        
      
        <LearningPledgeSigner
            v-if="pledgeOpen && pledgeSource"
            :theme-id="selectedTopic.id"
            :source="pledgeSource"
            :document-name="selectedTopic?.pledge_file_path ?? null"
            :signed-at="progress?.pledge?.signed_at ?? null"
            @close="pledgeOpen = false"
            @signed="refreshLessonView?.()"
        />
        <router-view v-slot="{ Component }">
            <transition name="modalFade">
                <component :is="Component" 
                    :selectedTopic="selectedTopic"
                    :filteredMaterials="filteredMaterials"
                    :sections_status="sections_status"
                />
            </transition>
        </router-view>
    
    </div>  
</template>
<script setup lang="ts">
import { computed, ref, inject, provide, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import HasQuestion from './HasQuestion.vue';
import { DateTime } from 'luxon';
import { useLearningApi, type LearningPortfolioSaveRequest } from '@/composables/learningApi';
import TTSPlayer from '@/components/Global/TTSPlayer.vue';
import LearningTopicMenu, { type LearningTopicMenuItem } from '@/components/Learning/shared/LearningTopicMenu.vue';
import LearningPreviousExperiencePanel from '@/components/Learning/shared/LearningPreviousExperiencePanel.vue';
import LearningContentRenderer from '@/components/Learning/shared/LearningContentRenderer.vue';
import LearningPledgeSigner from '@/components/Learning/Pledge/LearningPledgeSigner.vue';
import Back from '@/components/Icons/Back.vue';
import { LEARNING_MATERIAL_TYPES, LESSON_ANSWER_STATUS, LESSON_MATERIAL_PRIORITY, LESSON_PORTFOLIO_STATUS, LESSON_SECTION_STATUS } from '@/config/learning';
import { isEnabled } from '@/utils/learningProgress';
import type { LearningExam, LearningExamAttempt, LearningMaterial, LearningPreviousExperiencePayload, LearningSection, LearningTheme } from '@/types/learning';
import type { LearningBasicItemContext, LearningValidatableRef } from '@/composables/learningDraftContext';
    const router = useRouter()
    const props = defineProps<{
        selectedTopic: LearningTheme
        materials: LearningMaterial[]
        sections_status: LearningSection[]
        filteredMaterials: LearningMaterial[]
        sectionsCompleted: boolean
        examData?: LearningExam | null
        examAttempts?: LearningExamAttempt[]
        examRemaining?: number
        examLoading?: boolean
        refreshLessonView?: () => Promise<void>
    }>()
    const loading = ref<boolean[]>([false, false])
    const lesson = inject<() => void | Promise<void>>('getLessonPortfolios')
    const learningApi = useLearningApi()
    const route = useRoute()
    const mainLoader = ref(true)
    const previousExperience = ref<LearningPreviousExperiencePayload | null>(null)
    // First-timers (no previous experience) see the basic knowledge open; any
    // repeater / salary challenger who already learned it starts collapsed.
    const showBasic = ref(true)

    onMounted(async () => {
        await refreshPreviousExperience()
        showBasic.value = !(previousExperience.value?.has_experience ?? false)
        mainLoader.value = false
    })
    // The backend decides whether this is a repeater (path 2) attempt.
    const refreshPreviousExperience = async() => {
        if (!props.selectedTopic?.id) return

        previousExperience.value = await learningApi.getPreviousExperience(props.selectedTopic.id)
        await props.refreshLessonView?.()
    }
    const progress = computed(() => props.selectedTopic?.progress ?? null)
    const examRemaining = computed(() => progress.value?.exam.remaining_attempts ?? props.examRemaining ?? 0)
    const examLoading = computed(() => props.examLoading ?? false)
    const headerMaterials = computed(() => {
        return props.materials?.filter(ob => ob.priority === LESSON_MATERIAL_PRIORITY.HEADER) ?? []
    })
    
    const portfolioStatus = computed(() => {
        return progress.value?.portfolio.status ?? Number(props.selectedTopic?.lesson_portfolio?.status ?? 0)
    })

    const sectionStatus = (id: number) => {
        return props.sections_status.find(ob => ob.material_id === id)?.status ?? null
    }    
    const getAllContent = () => {
        let contents = ''
        headerMaterials.value.forEach(element => {
            contents += element.content ?? ''
        });
        return contents
    }
    const getTextContent = (html: string) => {
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = html;
        return tempDiv.textContent || tempDiv.innerText;
    }
    const saveItems = async(routeName: string, status: number, refs: LearningValidatableRef[], params: LearningPortfolioSaveRequest) => {
        let result = true
        for(const ref of refs){
            const val = ref.validate ? await ref.validate() : {valid: false}
            result = result && val.valid
        }
        if(!result) return
        loading.value[status] = true
        
    
        await learningApi.savePortfolio(params)
        loading.value[status] = false
        if(status == 1){
            router.push({name: routeName})
        }
        await lesson?.()

    }
    const viewPortfolios = async() => {
        if (!props.selectedTopic) return
        const url = `/learning/${props.selectedTopic.id}/portfolioview`
        window.open(url, '_blank')?.focus();
    } 
    const basicItemContext: LearningBasicItemContext = {
        saveItems: (route: string, status: number, refs: LearningValidatableRef[], params: LearningPortfolioSaveRequest) => saveItems(route, status, refs, params),
        viewPortfolios: () => viewPortfolios(),
        loading: loading,
    }
    provide('basicItem', basicItemContext)
    provide('previousExperience', previousExperience)
    const basicStatus = computed(() => {
        return progress.value?.basic_completed ?? false
    })
    const canAccessExam = computed(() => {
        return Boolean(progress.value?.basic_completed && progress.value?.case_completed)
    })
    const examAvailable = computed(() => progress.value?.exam.available ?? false)
    const examPassed = computed(() => {
        return progress.value?.exam.passed ?? false
    })
    // Advancement needs the exam taken, not passed.
    const examTaken = computed(() => (progress.value?.exam.attempts_count ?? 0) > 0)
    const pledgeRequired = computed(() => progress.value?.pledge?.required ?? false)
    const pledgeSigned = computed(() => progress.value?.pledge?.signed ?? false)
    // Once signed, show the learner their own signed copy rather than the blank
    // original (served auth-scoped; only the owner or an admin can fetch it).
    const pledgeSource = computed(() => {
        const pledge = progress.value?.pledge
        if (pledge?.signed && pledge.signature_id) {
            return `/lesson_pledge_file/${pledge.signature_id}`
        }
        return props.selectedTopic?.pledge_file_path ?? null
    })
    const pledgeOpen = ref(false)
    const surveyAvailable = computed(() => {
        return progress.value?.survey.available ?? false
    })
    const goExam = () => {
        if(!examAvailable.value || !canAccessExam.value || !props.selectedTopic){
            return
        }
        router.push({name: 'exam', params: {lessonThemeId: props.selectedTopic.id}})
    }
    const goSurvey = () => {
        if(!surveyAvailable.value) return
        // Tag the origin so the form's completion screen offers a way back to
        // this theme instead of "create another answer".
        router.push({
            path: `/survey/${props.selectedTopic?.custom_form_id}`,
            query: { lessonThemeId: String(props.selectedTopic?.id) },
        })
    }
    const dateFormat = (date: string | null | undefined) => {
        if (!date) return ''
        return DateTime.fromISO(date).toISODate()
    }
    const materialTone = (material: LearningMaterial) => {
        if (sectionStatus(material.id) === LESSON_SECTION_STATUS.COMPLETED || material.answer?.status === LESSON_ANSWER_STATUS.COMPLETED) return 'complete'
        if (sectionStatus(material.id) === LESSON_SECTION_STATUS.DRAFT || Number(material.answer?.status ?? 0) < 0) return 'warning'
        return undefined
    }
    // Per-section exam status lines (残り受験回数 / 前回スコア). These belong to
    // the section's own exam — the theme exam is shown separately below.
    const materialExamMeta = (materialId: number): string[] => {
        const exam = progress.value?.material_exams?.[materialId]
        if (!exam?.available) return []
        if (exam.passed) return ['試験：合格済み']

        const lines = [`試験 残り受験回数：${exam.remaining_attempts}回`]
        if (exam.latest_status) {
            lines.push(`前回 ${exam.latest_score}% / ${exam.latest_status === 'passed' ? '合格' : '不合格'}`)
        }
        return lines
    }
    const basicMenuItems = computed<LearningTopicMenuItem[]>(() => {
        const items: LearningTopicMenuItem[] = props.filteredMaterials.map((section) => ({
            id: `material-${section.id}`,
            title: section.title ?? '',
            disabled: !basicStatus.value && section.material_type === LEARNING_MATERIAL_TYPES.CASE_STUDY,
            completed: materialTone(section) === 'complete',
            tone: materialTone(section) === 'warning' ? 'warning' : undefined,
            meta: [
                ...(section.answer?.status === LESSON_ANSWER_STATUS.COMPLETED && section.answer.updated_at
                    ? [`完了日:${dateFormat(section.answer.updated_at)}`]
                    : []),
                ...materialExamMeta(section.id),
            ],
        }))

        if (examAvailable.value) {
            items.push({
                id: 'exam',
                title: '試験',
                disabled: !canAccessExam.value && !examTaken.value,
                completed: examTaken.value,
                tone: !examTaken.value && canAccessExam.value ? 'warning' : undefined,
                meta: examMeta.value,
            })
        }

        if (isEnabled(props.selectedTopic?.portfolio)) {
            items.push({
                id: 'portfolio',
                title: 'ポートフォリオ作成',
                disabled: !props.sectionsCompleted,
                completed: portfolioStatus.value >= LESSON_PORTFOLIO_STATUS.DISCUSSION_DRAFT_READY,
                tone: portfolioStatus.value < LESSON_PORTFOLIO_STATUS.DISCUSSION_DRAFT_READY && props.sectionsCompleted ? 'warning' : undefined,
            })
        }

        if (props.selectedTopic?.custom_form_id) {
            const meta: string[] = []
            if (examAvailable.value && !examTaken.value) meta.push('試験の受験後に回答できます。')
            const surveyDate = progress.value?.survey.completed_at ?? props.selectedTopic.survey_date
            if (surveyDate) meta.push(`完了日:${dateFormat(surveyDate)}`)

            items.push({
                id: 'survey',
                title: 'チェックリスト',
                disabled: !surveyAvailable.value,
                completed: progress.value?.survey.completed ?? Boolean(props.selectedTopic.survey_completed),
                meta,
            })
        }

        // 誓約書: signing is required before the theme can finish.
        if (pledgeRequired.value) {
            items.push({
                id: 'pledge',
                title: '誓約書',
                completed: pledgeSigned.value,
                tone: pledgeSigned.value ? undefined : 'warning',
                meta: pledgeSigned.value
                    ? [`署名日:${dateFormat(progress.value?.pledge.signed_at)}`]
                    : ['署名するとテーマを修了できます。'],
            })
        }

        return items
    })
    const examMeta = computed(() => {
        if (examLoading.value) return ['試験情報を読み込み中です...']
        if (examPassed.value) return ['ステータス：合格済み']
        const lines = [`残り受験回数：${examRemaining.value}回`]
        if (progress.value?.exam.latest_status) {
            lines.push(`前回 ${progress.value.exam.latest_score}% / ${progress.value.exam.latest_status === 'passed' ? '合格' : '不合格'}`)
        }
        return lines
    })
    const selectMenuItem = (item: LearningTopicMenuItem) => {
        if (item.disabled) return

        if (String(item.id).startsWith('material-')) {
            router.push({name: 'material', params: { materialId: String(item.id).replace('material-', '')}})
        } else if (item.id === 'exam') {
            goExam()
        } else if (item.id === 'portfolio') {
            router.push({name: portfolioStatus.value < LESSON_PORTFOLIO_STATUS.DISCUSSION_DRAFT_READY ? 'story' : 'summary'})
        } else if (item.id === 'survey') {
            goSurvey()
        } else if (item.id === 'pledge') {
            pledgeOpen.value = true
        }
    }
</script>
