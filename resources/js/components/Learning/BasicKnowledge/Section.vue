<template>
    <DraftLayout v-if="material">
        <template #main>        
            <div style="background:inherit">
                <div>
                    <div style="position:absolute; right: 50px; display: flex; gap: 10px;" v-if="ttsStore.active && ttsStore.id == selectedTopic.id">
                        <div style="position: static" class="lesson-play" @click="stopPlay(selectedTopic.id)">{{ ttsStore.play ? '一時停止' : '再開する' }}</div>
                        <div style="position: static" class="lesson-play" @click="endPlay">ストップ</div>
                    </div>
                    <div class="lesson-play" v-else @click="convertToSpeech(getTextContent(filteredContent), material.id)">読み上げる</div>
                    <p v-html="filteredContent"></p>
                </div>
                <div class="post-separetor"></div>
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
                        rules="required|max:2000"
                        name="recordBody"
                        label="タイトル"
                        v-model="comment"
                    />
                    <div v-else>
                        <p >{{ sectionContent ? sectionContent : "" }}</p>
                        <router-link :to="{name: 'more'}">もっと詳しく知りたい</router-link>
                    </div>
                    
                </div>
                <div v-if="material.has_question" style="position: relative;">
                    <LongInput 
                        v-if="(material.answer && material?.answer?.status < 2) || !material.answer" 
                        :initialValue="material?.answer?.answer ? material?.answer?.answer : answer" 
                        :placeHolder="`質問に関する答え`"
                        ref="answerComment"
                        rules="required|max:2000"
                        name="recordBody"
                        label="タイトル"
                        v-model="answer"
                    />
                    <p v-else><strong>質問に関する回答内容<br></strong>{{ material?.answer?.answer }}</p>
                    <OpenAiReview 
                        :assistand-id="selectedTopic?.assistant_id" 
                        :soure-text="material?.answer?.ai_review" 
                        :message="answer"
                        :confirm-text="'業務リスク管理の基礎を効果的に理解し、実務で活用できる視点を身につけている。'"
                        :answer="true"
                        ref="reviewEl"
                    />
                </div>
                <div v-if="sectionStatus != 2 && material.has_understand" style="display:flex; justify-content: center; gap:20px;flex-wrap: wrap;margin-top: 25px;">
                    <div v-if="selectedAnswer == 1">
                        <LoaderButton @triggered="validate('save')" :loading="processing_save" :content="'一時保存'"/>
                    </div> 
                    <div>
                        <LoaderButton @triggered="nextStage" :loading="processing" :content="selectedAnswer == 0  ? '次へ' : '完了'"/>
                    </div>
                </div>
                <div v-else-if="material.has_question" style="display:flex; justify-content: center; gap:20px;flex-wrap: wrap;margin-top: 25px;">
                    <div>
                        <LoaderButton @triggered="finish(1, material)" :loading="questionsave[1]" :content="'一時保存'"/>
                    </div>
                    <div>
                        <LoaderButton @triggered="finish(2, material)" :loading="questionsave[2]" :content="'完了'"/>
                    </div>
                </div>
                <div v-else style="display:flex; justify-content: center; gap:20px;flex-wrap: wrap;margin-top: 25px;">
                    <LoaderButton @triggered="nextStage" :loading="processing" :content="'完了'"/>
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
    import { ref, computed, inject, useTemplateRef } from 'vue'
    import DraftLayout from './DraftLayout.vue';
    import axios from 'axios';
    import OpenAI from 'openai';
    import OpenAiReview from '@/components/Global/OpenAiReview.vue';
    import { convertToSpeech, endPlay, stopPlay } from '@/utils/tts';
    import { useTtsStore } from '@/store/ttsStore';
    const router = useRouter()
    const route = useRoute()
    const ttsStore = useTtsStore()
    const { notify, info } = inject('dialog')
    const props = defineProps(['selectedTopic', 'filteredMaterials', 'sections_status'])
    const getLessonPortfolios = inject('getLessonPortfolios')
    const reviewEl = useTemplateRef('reviewEl')
    const { confirm } = inject('dialog')
    const filteredContent = computed(() => {
        
        return material.value.content.replace(/\[\[learning_video src="(.*?)" learning_video\]\]/g, (match, videoSrc) => {
            return `<video class="ls-video"  controls="controls"><source src="${videoSrc}"></video>`;
        });
    })
    const material = computed(() => {
        return props.filteredMaterials ? props.filteredMaterials.find(val => val.id == route.params.materialId) : null
    })
    const sectionStatus = computed(() => {
        return props.sections_status && props.sections_status.length ? props.sections_status.find(val => val.material_id === material.value?.id)?.status : 0
    })
    const sectionContent = computed(() => {
        return props.sections_status && props.sections_status.length ? props.sections_status.find(val => val.material_id === material.value?.id)?.content : ''
    })
    const understandComment = ref(null)
    const answer = ref("")
    const answerComment = ref(null)
    const comment = ref("")
    const processing = ref(false)
    const list = [
        { value: 1, content: '理解した'},
        { value: 0, content: '理解できなかった'}        
    ]
    const selectedAnswer = ref(null)
    
    const radioError = ref("")
    const processing_save = ref(false) 
    const questionsave = ref(['', false, false])
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
            updateAnswerStatus()
        } else {
            router.push({name: 'basic'})
        }
        
        
    }
    const updateAnswerStatus = async() => {
        try {
            const params = {
                id: material?.value?.answer?.id,
                params: {
                    material_id: material.value?.id,
                    status: 2
                },
            }
            await axios.post('/update_lesson_answer', params)
            router.push({name: 'basic'})
        } catch (e) {

        }
    }
    const getTextContent = (html) => {
        // Create a temporary DOM element to extract text from HTML content
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = html;
        return tempDiv.textContent || tempDiv.innerText; // Get plain text from HTML
    };
       
    const finish = async(status, material) => {
        if (status === 2) {
            if(props.selectedTopic.assistant_id && !reviewEl.value?.reviewResultRaw){
                notify('基礎知識研修を完了する前、AI分析してください。')
                return
            }
            
        }        
        const aiVal = await reviewEl.value?.validate()
        
        const val = await answerComment.value?.validate() || {valid: false}
        
        if((props.selectedTopic.assistant_id && !aiVal) || !val.valid){
            return
        }
        
        questionsave.value[status] = true
        const materialId = material?.id
        const answerId = material?.answer?.id
        const params = {     
            id: answerId,           
            params: {
                status: status,
                answer: answer.value,
                ai_review: reviewEl.value?.reviewResultRaw,
                material_id: materialId
            }
        }
        // let decision
        // if (status == 2) {
        //     decision = await checkList()
        // }
        try {
            axios.post('/update_lesson_answer', params)
            info('保存しました。')
            questionsave.value[status] = false
            // if (decision) {
            //     window.open(
            //     'https://docs.google.com/forms/d/e/1FAIpQLSclZ50A5MBYcx-Y_8_hLV3ARWgkJMX8Z6QRKdkl0XLKGDzhSg/viewform',
            //     '_blank'
            //     );
            // }
            
        } catch (e) {
            notify(e)
        } finally {
            router.push({name : 'basic'})
        }
    }
    const checkList = async() => {
        const options = {
            answers: [{ label: 'OK', value: true }]
        };
        const answer = await confirm("最後に業務リスク研修チェックリストの実施をお願い致します。", options);
        return answer
    }
    
</script>