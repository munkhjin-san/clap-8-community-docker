<template>
<div style="position:absolute;left: 0;top: 0;height: 100%;width: 100%;z-index: 6;background: var(--bg2);color:var(--primary-color);">
    <div style="height: 100%;width: 100%;overflow: hidden auto;">
        <div style="display: flex;align-items: center;height: 50px;position: sticky;top: 0;background: var(--bg2);z-index: 3;">
            <div style="height: 50px;width: 50px;cursor: pointer;display: flex;justify-content: center;align-items: center;fill:var(--primary-color)" @click="$router.go(-1)">
                <svg version="1.1" width="15" height="15" viewBox="0 0 20 32" xmlns="http://www.w3.org/2000/svg">
                    <path d="M0.775 17.789c1.305 1.166 2.612 2.332 3.927 3.486 1.311 1.161 2.634 2.308 3.953 3.46 1.316 1.156 2.646 2.296 3.973 3.439 1.33 1.139 2.667 2.273 4.015 3.394 0.662 0.551 1.647 0.52 2.272-0.107 0.65-0.654 0.619-1.725-0.020-2.393-1.198-1.253-2.407-2.495-3.621-3.729-1.232-1.245-2.462-2.492-3.704-3.725-0.902-0.9-1.803-1.802-2.707-2.699-0.033-0.032-0.055-0.069-0.072-0.106-0.045-0.036-0.082-0.080-0.111-0.129-0.069-0.047-0.129-0.117-0.176-0.216-0.021-0.047-0.044-0.092-0.066-0.136-0.12-0.062-0.214-0.168-0.246-0.325-0.001-0.005-0.002-0.009-0.003-0.014-0.104-0.157-0.187-0.327-0.254-0.505-0.109-0.185-0.182-0.388-0.226-0.601-0.002-0.012-0.005-0.024-0.007-0.036-0.016-0.085-0.028-0.172-0.036-0.259-0.195-0.593-0.26-1.183 0.030-1.653 0.006-0.157 0.067-0.277 0.157-0.361 0.019-0.050 0.039-0.099 0.063-0.149 0.040-0.084 0.1-0.145 0.17-0.188 0.008-0.015 0.019-0.028 0.028-0.042 0.032-0.13 0.106-0.228 0.202-0.293 0.072-0.145 0.157-0.287 0.26-0.43 0.046-0.063 0.101-0.113 0.163-0.151 0.018-0.020 0.037-0.038 0.059-0.054 0.014-0.059 0.044-0.116 0.094-0.165 0.9-0.888 1.797-1.782 2.699-2.672 1.244-1.231 2.476-2.475 3.714-3.717l1.843-1.871 1.832-1.885c0.655-0.681 0.669-1.793-0.044-2.48-0.652-0.631-1.693-0.624-2.385-0.038l-1.964 1.66-1.995 1.71c-1.32 1.149-2.648 2.293-3.962 3.45s-2.636 2.308-3.943 3.474c-1.311 1.159-3.284 2.806-4.106 3.689s-0.792 2.492 0.191 3.369z"></path>
                </svg> 
            </div>
            <div>{{ title ? title : '' }}</div>        
        </div>
        <div v-if="noData" style="line-height: 1.8;height:calc(100% - 110px);display: flex;justify-content: center;align-items: center;">
            <p>現在データはありません。</p>
        </div>
        <div v-else-if="lessonExists && $route.name == 'lesson' && selectedTopic && selectedTopic.active == 1" style="background: var(--background-color);padding: 30px;word-wrap: break-word;white-space: break-spaces;line-height: 1.8;display: flex;flex-direction: column;gap: 30px;margin: 0 20px;">
            <div class="lessons-topic" v-for="topic in lessons">
                <p v-html="topic.content"></p>
            </div>
            <div class="post-separetor" style="margin-bottom: 0;"></div>
            <div v-if="currentStatus">
                <p><strong>研修内容を理解しましたか</strong></p>
                <div v-for="answer in list" style="display: flex;align-items: center;padding: 5px 0;">
                    <input class="fish-eye" v-model="selectedAnswer" type="radio" :id="answer.value" name="answer" :value="answer.value" >
                    <label style="margin-left:10px;cursor:pointer" :for="answer.value">{{answer.content}}</label>
                </div>
                <span class="form-error" style="font-size: 11px;color:tomato">{{ selectedAnswer != null ? '' : radioError }}</span>
            </div>
            
            <div v-if="selectedAnswer == 1" class="si-box" style="margin:0">
                <p :style="{marginBottom: currentStatus ? '20px' : '0'}"><strong>{{ currentStatus ? '研修内容から理解したものやハイライトしたい部分を入力してください。' : '研修内容ハイライト。'}}</strong></p>
                <FormLongText
                    v-if="currentStatus"
                    :initialValue="comment"   
                    :placeHolder="`ハイライト内容`"
                    :key="formKey"
                    ref="lessonBody"
                    rules="required|max:2000"
                    uId="recordBody"
                    name="recordBody"
                    label="タイトル"
                    @setValue="val => comment = val"
                />
                <p v-else>{{ comment }}</p>
            </div>
            
            <div style="display:flex; justify-content: center; gap:20px;flex-wrap: wrap;">
                <div v-if="selectedAnswer == 1 && currentStatus">
                    <LoaderButton @triggered="saveContent('save')" :loading="processing_save" :content="'保存する'"/>
                </div> 
                <div>
                    <LoaderButton @triggered="nextStage" :loading="processing" :content="'次へ'"/>
                </div>
            </div>
               
        </div>
        
        <router-view 
            :comment="comment"
            :topicId="topicId"
            :portfolioId="portfolioId"
            :temp_content="temp_content"
            :selectedTopic="selectedTopic"
            :lesson_n_feedBack="lesson_n_feedBack"
            :lesson_p_feedBack="lesson_p_feedBack"
            :currentStatus="currentStatus"
            :lessons="lessons"
            :not_content="not_content"
            :selectedAnswer="selectedAnswer"
            >
        </router-view>
    </div>
    

</div>
</template>
<script setup>
import { onBeforeRouteLeave, useRoute, useRouter } from 'vue-router';
import FormLongText from '../Global/FormLongText.vue';
import LoaderButton from '../Global/LoaderButton.vue'
import { ref, computed, onMounted, provide, inject } from 'vue'
import { useStore } from 'vuex';
import axios from 'axios';

    const props = defineProps(['selectedTopic'])
    const router = useRouter()
    const lessonBody = ref(null)
    const route = useRoute()
    const lessons = ref([])
    const store = useStore()
    const comment = ref("")
    const processing = ref(false)
    const theme = inject('getThemes')
    const formKey = ref(0)
    const list = ref([
               { value: 1, content: '理解しました'},
               { value: 0, content: '理解できませんでした'}
               
            ])
    const selectedAnswer = ref(null)
    const portfolioId = ref(null)
    const temp_content = ref("")
    const lesson_n_feedBack = ref("")
    const lesson_p_feedBack = ref("")
    const not_content = ref("")
    const radioError = ref("")
    const processing_save = ref(false)

    onBeforeRouteLeave((to, from, next) => {
        theme()
        next();
    })
    onMounted(() => {
        if(route.meta.data && Object.keys(route.meta.data).length){
            lessons.value = route.meta.data;
            getLessonPortfolios()
        }
    })
    const title = computed(() => {
        const prefix = getTitlePrefix(route.name);
        const topicTitle = props.selectedTopic ? props.selectedTopic.title : '';
        return `${prefix}${topicTitle}】`;
    })
    const getTitlePrefix = (name) => {
        const titleMappings = {
            lesson: '基礎知識【',
            more: '基礎知識【',
            portfoliodraft: 'ポートフォリオ作成【',
            discussion: 'グループディスカッション【',
            portfolio: 'ポートフォリオ完成【',
            form: 'アンケート【',
            finish: '',
        };

        return titleMappings[name] || '';
    }
    const currentStatus = computed(() => {
        if(props.selectedTopic){
            return props.selectedTopic.lesson_portfolio && props.selectedTopic.lesson_portfolio.status != 3 || !props.selectedTopic.lesson_portfolio
        }else{
            return false
        }
    })
    const noData = computed(() => {
        return props.selectedTopic && props.selectedTopic.active == 0 || !lessonExists.value
    })
    const lessonExists = computed(() => {
        return lessons.value.length
    })
    const topicId = computed(() => {
        return route.params.topicId
    })
    const getLessonPortfolios = () => {
        axios.post('/get_lesson_portfolio', {topic_id: topicId.value}).then(response => {
            if(response.data){
                comment.value = response.data.basic_knowledge ? response.data.basic_knowledge : ''
                portfolioId.value = response.data.id
                selectedAnswer.value = response.data.understand ? response.data.understand : null
                temp_content.value = response.data.content ? response.data.content : ''
                not_content.value = response.data.not_understand_content ? response.data.not_understand_content : ''
                lesson_n_feedBack.value = response.data.negative_feedback ? response.data.negative_feedback : ''
                lesson_p_feedBack.value = response.data.positive_feedback ? response.data.positive_feedback : ''
            }
        })
    }
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
                topic_id: topicId.value,
                title: props.selectedTopic.title,
                portfolio_id: portfolioId.value ? portfolioId.value : null,
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
                getLessonPortfolios()
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
            if(currentStatus.value){
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
    provide('getLessonPortfolios', getLessonPortfolios)
</script>