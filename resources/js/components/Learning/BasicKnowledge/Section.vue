<template>
    <DraftLayout v-if="material">
        <template #main>        
            <div style="background:inherit">
                <div>
                    <div v-if="ttsStore.active && ttsStore.id == material.id" class="absolute bg-[var(--bg3)] shadow-me rounded-md top-0 left-auto right-[25px] w-min" >
                        <div class="flex items-center gap-[5px]">
                            <div v-if="ttsStore.play" class="h-[35px] w-[35px] min-w-[35px] flex items-center justify-center cursor-pointer" @click="stopPlay(material.id)">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 50 50" height="18" width="18" fill="var(--primary-color)">
                                    <rect height="40" width="10" y="5" x="10"></rect>
                                    <rect height="40" width="10" y="5" x="30"></rect>
                                </svg>
                            </div>
                            <div v-else class="h-[35px] w-[35px] min-w-[35px] flex items-center justify-center cursor-pointer" @click="stopPlay(material.id)">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 50 50" height="18" width="18" fill="var(--primary-color)">
                                    <polygon points="10,5 40,25 10,45"></polygon>
                                </svg>
                            </div>
                            <div @click="endPlay" class="h-[35px] w-[35px] min-w-[35px] flex items-center justify-center cursor-pointer">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 50 50" height="18" width="18" fill="tomato">
                                    <rect height="30" width="30" y="10" x="10"></rect>
                                </svg>
                            </div>
                        </div>
                    </div>
                    <div class="lesson-play" v-else @click="convertToSpeech(getTextContent(filteredContent), material.id)">
                        <svg fill="#fff" class="m-auto" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 32.57 26.53">
                            <path class="cls-1" d="M12.49,1.31l-3.5.03h-.03c-.4,0-.78.2-1.01.56l-1.71,2.6c-.46.7-.92,1.39-1.37,2.09-1.12.02-2.69.06-3.56.07-.48.03-.79.28-.95.59-.11.13-.18.29-.2.5-.1,1.79-.14,3.59-.14,5.38.01,1.76.03,3.58.17,5.33,0,.01,0,.02,0,.03,0,.63.49,1.15,1.12,1.16,1.18.02,2.37.04,3.55.05.56.84,3.09,4.67,3.09,4.67.22.33.6.55,1.03.56.02,0,3.5.02,3.52.03.72,0,1.3-.58,1.29-1.29,0-1.76.03-3.51.03-5.27.01-4.9.02-10.95-.03-15.83,0-.71-.58-1.28-1.29-1.27ZM6.48,17.82c-.22-.31-.57-.51-.98-.5-.87,0-2.35-.05-3.09-.09-.25-.01-.44-.16-.45-.48-.05-1.31-.08-5.53-.02-7.13.02-.42.31-.64.7-.66l2.86-.03c.38,0,.76-.18.98-.52.86-1.22,2-2.86,2.96-4.24.13-.19.35-.3.58-.3.25,0,.51,0,.87,0,.16,0,.29.13.29.3,0,1.23-.02,2.46-.02,3.69-.01,4.36-.01,9.63.01,14.17,0,.19-.16.35-.35.35h-.78c-.25,0-.48-.11-.62-.32-1.02-1.46-2.94-4.23-2.95-4.25Z"/>
                            <path class="cls-1" d="M30.96,5.82c-.65-1.41-1.51-2.73-2.5-3.93-.6-.71-1.23-1.41-2.08-1.83-.53-.27-1.11.31-.84.84.34.76.9,1.47,1.36,2.15.74,1.15,1.37,2.41,1.82,3.69,1.78,5.13,1.38,11.03-1.41,15.73-.57.95-1.25,1.82-1.93,2.72-.23.29-.24.71,0,1.01.28.36.8.43,1.16.15.98-.76,1.79-1.7,2.53-2.7,3.81-5.02,4.64-12.13,1.9-17.83Z"/>
                            <path class="cls-1" d="M25.26,8.18c-.46-.95-1.07-1.82-1.76-2.61-.42-.48-.86-.94-1.49-1.14-.51-.18-1.02.34-.84.84.13.54.48,1.01.77,1.46l.26.46c.98,1.82,1.42,3.9,1.42,5.94-.02,2.03-.43,4.12-1.45,5.92-.35.62-.78,1.18-1.17,1.8-.48.75.42,1.64,1.17,1.14.69-.47,1.25-1.1,1.76-1.77.51-.67.96-1.38,1.34-2.15,1.54-3.05,1.52-6.85,0-9.9Z"/>
                            <path class="cls-1" d="M17.66,8.79c-.41,0-.76.3-.82.71-.03.15-.04.34-.02.46.02.16.11.32.17.45.58,1.39.56,2.92.32,4.38-.1.68-.47,1.14-.69,1.78-.24.7.51,1.38,1.19,1.06.4-.19.7-.5.99-.83.28-.33.55-.69.77-1.08,1.17-1.96.79-4.63-.79-6.25-.33-.32-.59-.7-1.12-.69Z"/>
                        </svg>                        
                    </div>
                    <p v-html="filteredContent"></p>
                </div>
                <div class="post-separetor"></div>
                <SummaryQuestions 
                    v-if="hasQuestions"
                    :material="material"
                    v-model:answers="summaryAnswers"
                    v-model:errors="validationErrors"    
                />
                <EasySummary 
                    v-if="showSummary"
                    :summaries="filteredSummaries"
                    :material="material"
                    @updateAnswerStatus="updateAnswerStatus"
                    @close="showSummary = false"
                />
                <div v-if="sectionStatus != 2 && material.has_understand">
                    <p><strong>基礎知識の内容を理解しましたか？</strong></p>
                    <div v-for="answer in list" style="display: flex;align-items: center;padding: 5px 0;">
                        <input class="fish-eye" v-model="selectedAnswer" type="radio" :id="answer.value" name="answer" :value="answer.value" >
                        <label style="margin-left:10px;cursor:pointer" :for="answer.value">{{answer.content}}</label>
                    </div>
                    <span class="form-error" style="font-size: 11px;color:tomato">{{ selectedAnswer != null ? '' : radioError }}</span>
                </div>
                
                <div v-if="selectedAnswer == 1 || sectionStatus == 2" class="si-box" style="margin:0">
                    <p :style="{marginBottom: sectionStatus != 2 ? '20px' : '0'}"><strong>{{ sectionStatus != 2 ? '基礎知識の内容で特に重要だと理解した点を入力してください' : '基礎知識の内容で特に重要だと理解した点'}}</strong></p>
                    <LongInput
                        v-if="sectionStatus != 2"
                        :initialValue="sectionContent ? sectionContent : comment"   
                        :placeHolder="`理解した点`"
                        :key="sectionContent ? sectionContent : 0"
                        ref="understandComment"
                        rules="required"
                        name="recordBody"
                        label="タイトル"
                        v-model="comment"
                    />
                    <div v-else>
                        <p >{{ sectionContent ? sectionContent : "" }}</p>
                        <router-link :to="{name: 'more'}">もっと詳しく知りたい</router-link>
                    </div>
                    
                </div>
                <HasQuestion 
                    v-if="material.has_question" 
                    :material="material" 
                />
                <div v-if="sectionStatus != 2 && material.has_understand" style="display:flex; justify-content: center; gap:20px;flex-wrap: wrap;margin-top: 25px;">
                    <div v-if="selectedAnswer == 1">
                        <LoaderButton @triggered="validate('save')" :loading="processing_save" :content="'一時保存'"/>
                    </div> 
                    <div>
                        <LoaderButton @triggered="nextStage" :loading="processing" :content="selectedAnswer == 0  ? '次へ' : '完了'"/>
                    </div>
                </div>
                <div v-else-if="!material.has_question && (!material.answer || material?.answer?.status < 2)" style="display:flex; justify-content: center; gap:20px;flex-wrap: wrap;margin-top: 25px;">
                    <LoaderButton @triggered="nextStage" :loading="processing" :content="filteredSummaries.length > 0 ? '次へ' : '完了'"/>
                </div>
            </div>
            <router-view
                :material="material"
                :selectedTopic="selectedTopic"
                :sectionUpdate="sectionUpdate"
                :sectionStatus="sectionStatus"
            >
            </router-view>
        </template>
    </DraftLayout>
</template>
<script setup>
import { useRoute, useRouter } from 'vue-router';
import LongInput from '../../Form/LongInput.vue';
import LoaderButton from '../../Global/LoaderButton.vue'
import { ref, computed, inject, watchEffect } from 'vue'
import DraftLayout from './DraftLayout.vue';
import { convertToSpeech, endPlay, stopPlay } from '@/utils/tts';
import { useTtsStore } from '@/store/ttsStore';
import EasySummary from './EasySummary.vue';
import HasQuestion from './HasQuestion.vue';
import SummaryQuestions from './SummaryQuestions.vue';
import { useApi } from '@/composables/api';
import { useDialog } from '@/composables/dialog';
    const router = useRouter()
    const route = useRoute()
    const ttsStore = useTtsStore()
    const api = useApi()
    const { ping, toast } = useDialog()
    const props = defineProps(['selectedTopic', 'filteredMaterials', 'sections_status'])

    const getLessonPortfolios = inject('getLessonPortfolios')
    const filteredContent = computed(() => {
        
        return material.value.content.replace(/\[\[learning_video src="(.*?)" learning_video\]\]/g, (match, videoSrc) => {
            return `<video class="ls-video"  controls="controls"><source src="${videoSrc}"></video>`;
        });
    })
    const material = computed(() => {
        return route.meta.material ? route.meta.material : null
    })
    const sectionStatus = computed(() => {
        return props.sections_status && props.sections_status.length ? props.sections_status.find(val => val.material_id === material.value?.id)?.status : 0
    })
    const sectionContent = computed(() => {
        return props.sections_status && props.sections_status.length ? props.sections_status.find(val => val.material_id === material.value?.id)?.content : ''
    })
    const hasQuestions = computed(() => {
        return material.value?.summaries.length && material.value?.summaries.some(ob => ob.questions?.length > 0) && (material.value?.answer?.status < 2 || !material.value?.answer)
    })
    const answers = computed(() => {
        return material.value?.summaries.map(summary => summary.answers).flat()
    })
    const understandComment = ref(null)
    const comment = ref("")
    const processing = ref(false)
    const list = [
        { value: 1, content: '理解した'},
        { value: 0, content: '理解できなかった'}        
    ]
    const selectedAnswer = ref(null)
    
    const radioError = ref("")
    const processing_save = ref(false) 
    const getLessons = inject('getLessons')
    const summaryAnswers = ref([])
    const validationErrors = ref({});
    watchEffect(() => {
        summaryAnswers.value = answers.value ?? []
    })
    const showSummary = ref(false)
    const validate = async(status) => {
        const valid = await understandComment.value.validate()
        if(valid.valid){
            const content = comment.value ? comment.value : sectionContent.value
            return await sectionUpdate(status, content)
        }
    } 
    const sectionUpdate = async(status, content) => {
    
        let section_status = 1
        if(status == 'next'){
            processing.value = true
            section_status = 2

        }else{
            processing_save.value = true
        }

        const params = {
            update_content: content,
            lesson_theme_id: route.params.lessonThemeId,
            title: props.selectedTopic.title,
            material_id: route.params.materialId,
            section_status: section_status,
        }

        const response = await api.post('/section_update', params)
        if(status == 'save'){
            toast(props.editTarget ? '編集しました。' :'保存しました。')
            processing_save.value = false
        }
        await getLessonPortfolios() 
        radioError.value = ''
        processing.value = false
        return response.status

                
    }
    const filteredSummaries = computed(() => {
        return material.value.summaries.filter((summary) => {
            return summaryAnswers.value.some(answer => 
                answer.lesson_summary_id === summary.id && (answer.answer_val === 1 || answer.answer_val === 2)
            );
        });
    })
    const nextStage = async() => {
        if (material.value.has_understand) {
            if(selectedAnswer.value == 1){
                const checkValidate = await validate('next')
                if(checkValidate){
                    router.push({name: 'basic'})
                }
            }else if(selectedAnswer.value == 0){
                router.push({name: 'more'})
            }else{
                radioError.value = '必須です'
                return
            }
        } else if (!material.value.has_question){
            
            let hasError = false;

            material.value?.summaries.forEach(summary => {
                summary.questions.forEach(question => {
                    const isAnswered = summaryAnswers.value.some(
                        answer => answer.lesson_summary_question_id === question.id
                    );

                    if (!isAnswered) {
                        validationErrors.value[question.id] = true;
                        hasError = true;
                    } else {
                        validationErrors.value[question.id] = false;
                    }
                });
            });
            if (hasError) {
                return;
            }
            if (filteredSummaries.value.length > 0) {
                const options = {
                    answers: [{label: 'OK', value: true}]
                }
                const answer = await ping('理解度チェックの結果、「実務での応用に不安がある」または「理解できていない」を選択された方に向けて、研修内容を分かりやすくまとめた要約を表示します。要約をご覧いただき、理解を深めてください。', options)
                if (answer.value) {
                    showSummary.value = true
                    return
                }
            }
            
            
            updateAnswerStatus()
        } else {
            router.push({name: 'basic'})
        }
        
        
    }
    const saveSummaryAnswers = async() => {
        if(!summaryAnswers.value.length) return
        await api.post('/save_summary_answers', {answers: summaryAnswers.value})

    }
    const updateAnswerStatus = async(status, joined, reason_dnt_und) => {
        const params = {
            id: material?.value?.answer?.id,
            params: {
                material_id: material.value?.id,
                status: status || 2,
                cant_understand: joined || '',
                reason_dnt_und: reason_dnt_und || ''
            },
        }
        await api.post('/update_lesson_answer', params)
        
        router.push({name: 'basic'})
        
        toast('研修は終了致しました。有難うございます。')
        saveSummaryAnswers()
        getLessons()

    }
    const getTextContent = (html) => {
        // Create a temporary DOM element to extract text from HTML content
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = html;
        return tempDiv.textContent || tempDiv.innerText; // Get plain text from HTML
    };
    
</script>