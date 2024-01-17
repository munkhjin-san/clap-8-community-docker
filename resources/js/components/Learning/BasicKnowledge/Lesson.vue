<template>
<div>
    <div style="height: 100%;width: 100%;overflow: hidden auto;">
        
        <div v-if="lessonExists && $route.name == 'basicknowledge' && selectedTopic && selectedTopic.active == 1" style="background: var(--background-color);padding: 30px;word-wrap: break-word;white-space: break-spaces;line-height: 1.8;display: flex;flex-direction: column;gap: 30px;margin: 0 20px;">
            <div class="lessons-topic" v-for="topic in lessons">
                <p v-html="topic.content"></p>
            </div>
            <div class="post-separetor" style="margin-bottom: 0;"></div>
            <div v-if="currentStatus">
                <p><strong>研修内容を理解しましたか</strong></p>
                <div v-for="answer in list" style="display: flex;align-items: center;padding: 5px 0;">
                    <input class="fish-eye" :key="understand" v-model="selectedAnswer" type="radio" :id="answer.value" name="answer" :value="answer.value" >
                    <label style="margin-left:10px;cursor:pointer" :for="answer.value">{{answer.content}}</label>
                </div>
                <span class="form-error" style="font-size: 11px;color:tomato">{{ selectedAnswer != null ? '' : radioError }}</span>
            </div>
            
            <div v-if="selectedAnswer == 1" class="si-box" style="margin:0">
                <p :style="{marginBottom: currentStatus ? '20px' : '0'}"><strong>{{ currentStatus ? '研修内容から理解したものやハイライトしたい部分を入力してください。' : '研修内容ハイライト。'}}</strong></p>
                <FormLongText
                    v-if="currentStatus"
                    :initialValue="content"   
                    :placeHolder="`ハイライト内容`"
                    :key="formKey"
                    ref="lessonBody"
                    rules="required|max:2000"
                    uId="recordBody"
                    name="recordBody"
                    label="タイトル"
                    @setValue="val => comment = val"
                />
                <p v-else>{{ content }}</p>
            </div>
            
            <div style="display:flex; justify-content: center; gap:20px;flex-wrap: wrap;">
                <div v-if="selectedAnswer == 1 && currentStatus">
                    <LoaderButton @triggered="saveContent('save')" :loading="processing_save" :content="'保存する'"/>
                </div> 
                <div v-if="currentStatus">
                    <LoaderButton @triggered="nextStage" :loading="processing" :content="'次へ'"/>
                </div>
            </div>
               
        </div>
        
    </div>
    

</div>
</template>
<script setup>
import { useRouter } from 'vue-router';
import FormLongText from '../../Global/FormLongText.vue';
import LoaderButton from '../../Global/LoaderButton.vue'
import { ref, computed, inject, watch } from 'vue'
import axios from 'axios';

    const props = defineProps(['selectedTopic', 'lessons', 'currentStatus', 'topicId', 'portfolioId', 'content', 'understand'])
    const router = useRouter()
    const lessonBody = ref(null)
    
    const comment = ref(props.content || "")
    const processing = ref(false)
    
    const formKey = ref(0)
    const list = ref([
               { value: 1, content: '理解しました'},
               { value: 0, content: 'もっと詳しく知りたい'}
               
            ])
    const selectedAnswer = ref(props.understand || '')
    
 
    const radioError = ref("")
    const processing_save = ref(false)

    const lesson = inject('getLessonPortfolios')
    
    
    
    const lessonExists = computed(() => {
        return props.lessons.length
    })
    watch(() => props.understand, (newVal) => {
        selectedAnswer.value = newVal
    })
    
    const saveContent = async(status) => {
        const result = await lessonBody.value.$refs.recordBody.validate()
        if(result.valid){
            if(status == 'next'){
                processing.value = true
            }else{
                processing_save.value = true
            }

            const params = {
                basic_knowledge: comment.value,
                topic_id: props.topicId,
                title: props.selectedTopic.title,
                portfolio_id: props.portfolioId ? props.portfolioId : null,
                understand: selectedAnswer.value,
                status: props.selectedTopic && props.selectedTopic.status ? props.selectedTopic.status : 0,
            }
            axios.post('/save_lesson_portfolio', params).then(response => {
                if(status == 'save'){
                    const data = {
                        text: props.editTarget ? '編集しました。' :'保存しました。',
                        channel: Math.random().toString(36).substring(5),
                        icon: 0,
                        view: true
                    }
                    emitter.emit('setInfo', data)
                    processing_save.value = false

                }
                lesson()
                radioError.value = ''
                if(status == 'next'){
                    processing.value = false

                    router.push({name: 'portfoliodraft'})
                    
                }
            }).catch(function (error) {
                if (error.response) errorToast('エラーが発生しました。 ' + error.response.data.message)
                else if (error.request) errorToast('エラーが発生しました。')
                else errorToast('エラーが発生しました。 ' + error.message)                       
            });
        }
    }
    const errorToast = (message) => {
            emitter.emit('setToast', {
                active: true,  
                type: 'info', 
                content: message,
                closeButton: false, 
                autoClose: false,
                answers: ['OK']

            })  
            processing.value = false
            
        }
    const nextStage = () => {
        if(selectedAnswer.value == 1){
            if(props.currentStatus){
                saveContent('next')
                
            }else{
                router.push({name: 'portfolio'})
            }
            
        }else if(selectedAnswer.value == 0){
            router.push({name: 'more'})
        }else{
            radioError.value = '必須です'
            return
        }
        
    }
</script>