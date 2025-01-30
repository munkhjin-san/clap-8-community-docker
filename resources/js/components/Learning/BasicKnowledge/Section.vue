<template>
    <DraftLayout v-if="material">
        <template #main>        
            <div style="background:inherit">
                <div>
                    <div style="position:absolute; right: 50px; display: flex; gap: 10px;" v-if="ttsStore.active && ttsStore.id == material.id">
                        <div style="position: static" class="lesson-play" @click="stopPlay(material.id)">{{ ttsStore.play ? '一時停止' : '再開する' }}</div>
                        <div style="position: static" class="lesson-play" @click="endPlay">ストップ</div>
                    </div>
                    <div class="lesson-play" v-else @click="convertToSpeech(getTextContent(filteredContent), material.id)">読み上げる</div>
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
    import { ref, computed, inject, useTemplateRef, watchEffect } from 'vue'
    import DraftLayout from './DraftLayout.vue';
    import axios from 'axios';
    import { convertToSpeech, endPlay, stopPlay } from '@/utils/tts';
    import { useTtsStore } from '@/store/ttsStore';
    import EasySummary from './EasySummary.vue';
    import HasQuestion from './HasQuestion.vue';
    import SummaryQuestions from './SummaryQuestions.vue';
    const router = useRouter()
    const route = useRoute()
    const ttsStore = useTtsStore()
    const { notify, info, confirm } = inject('dialog')
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
        try{
            const response = await axios.post('/section_update', params)
            if(status == 'save'){
                info(props.editTarget ? '編集しました。' :'保存しました。')
                processing_save.value = false
            }
            await getLessonPortfolios() 
            radioError.value = ''
            processing.value = false
            return response.status
        }catch(error){
            if (error.response) notify('エラーが発生しました。 ' + error.response.data.message)
            else if (error.request) notify('エラーが発生しました。')
            else notify('エラーが発生しました。 ' + error.message)        
        }
                
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
                const answer = await confirm('理解度チェックの結果、「実務での応用に不安がある」または「理解できていない」を選択された方に向けて、研修内容を分かりやすくまとめた要約を表示します。要約をご覧いただき、理解を深めてください。', options)
                if (answer) {
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
        try {
            await axios.post('/save_summary_answers', {answers: summaryAnswers.value})
        } catch (e) {
            notify(e)
        }
    }
    const updateAnswerStatus = async(status, joined, reason_dnt_und) => {
        try {
            const params = {
                id: material?.value?.answer?.id,
                params: {
                    material_id: material.value?.id,
                    status: status || 2,
                    cant_understand: joined || '',
                    reason_dnt_und: reason_dnt_und || ''
                },
            }
            await axios.post('/update_lesson_answer', params)
            
            router.push({name: 'basic'})
            
            info('研修は終了致しました。有難うございます。')
            saveSummaryAnswers()
            getLessons()
        } catch (e) {

        }
    }
    const getTextContent = (html) => {
        // Create a temporary DOM element to extract text from HTML content
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = html;
        return tempDiv.textContent || tempDiv.innerText; // Get plain text from HTML
    };
    
</script>