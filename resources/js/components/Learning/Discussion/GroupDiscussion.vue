<template>
    <div class="section-wrapper" style="height: calc(100% - 50px);">
        <div class="section-inner" v-if="selectedTopic && selectedTopic.active == 1">  
        
            <div>
                <p><strong>ポートフォリオ</strong></p>
                <p>{{ portfolio ? portfolio.content : '' }}</p>
            </div>
            <div>
                <p><strong>どのようなフィードバックをもらいましたか。</strong></p>
                
            </div>
            <div class="si-box">
                <FormLongText
                    v-if="portfolio && portfolio.status == 1"
                    :initialValue="portfolio ? portfolio.positive_feedback : p_feedBack"   
                    :placeHolder="`ポジティブフィードバックの内容と気付き`"
                    :key="portfolio.positive_feedback"
                    ref="p_feedbackBody"
                    uId="recordBody"
                    name="recordBody"
                    label="タイトル"
                    @setValue="val => p_feedBack = val"
                />
                <div v-else>
                    <p>ポジティブフィードバック</p>
                    <p>{{ portfolio.positive_feedback }}</p>
                </div>
            </div>
            <div class="si-box">
                <FormLongText
                    v-if="portfolio && portfolio.status == 1 "
                    :initialValue="portfolio ? portfolio.negative_feedback : n_feedBack"   
                    :placeHolder="`ネガティブフィードバックの内容と気付き`"
                    :key="portfolio.negative_feedback"
                    ref="n_feedbackBody"
                    uId="recordBody"
                    name="recordBody"
                    label="タイトル"
                    @setValue="val => n_feedBack = val"
                />
                <div v-else>
                    <p>ネガティブフィードバック</p>
                    <p>{{ portfolio.negative_feedback }}</p>
                </div>
            </div>
            <div v-if="portfolio && portfolio.status == 1 " style="display:flex; justify-content: center; gap:20px;flex-wrap: wrap;margin-top: 25px;">
                <div>
                    <LoaderButton @triggered="saveContent('save')" :loading="processing_save" :content="'一時保存'"/>
                </div>
                <div>
                    <LoaderButton @triggered="nextStage" :loading="processing" :content="'ディスカッション完了'"/>
                </div>
            </div>
            
            
        </div>
    </div>
    
</template>
<script setup>
    import axios from 'axios';
    import FormLongText from '../../Global/FormLongText.vue';
    import LoaderButton from '../../Global/LoaderButton.vue';
    import { ref, inject, computed, onBeforeMount } from 'vue'
    import { useRoute, useRouter } from 'vue-router';
    const props = defineProps([
        'selectedTopic',
    ]);
    const route = useRoute()
    const p_feedBack = ref(portfolio ? portfolio.positive_feedback : "")
    const n_feedBack = ref(portfolio ? portfolio.negative_feedback : "")
    const p_feedbackBody = ref(null)
    const n_feedbackBody = ref(null)
    const processing = ref(false)
    const router = useRouter()
    const lesson = inject('getLessonPortfolios')
    const processing_save = ref(false)
    const discussionDay = computed(() => {
        if(props.selectedTopic && props.selectedTopic.discussion_date){
            const currentDate = new Date();
            const discussionDate = new Date(props.selectedTopic.discussion_date)
            return currentDate >= discussionDate
        }else{
            return false
        }
    })
    const portfolio = inject('portfolio')
    const group_available = computed(() =>{
        if(props.selectedTopic && props.selectedTopic.lesson_portfolio){
            if(props.selectedTopic.lesson_portfolio.status < 1){
                return true
            }
            return false
        }
        return true
    })
    onBeforeMount(() => {
        setTimeout(() => {
            if(!discussionDay.value || group_available.value){
                errorToast()
            }
        }, 500);
        
    })
    const saveContent = async(status) => {
        let portfolioStatus = 1
        if(status == 'next'){
            processing.value = true
            portfolioStatus = 2
        }else{
            processing_save.value = true
        }
        
        const params = {
            p_feedback: p_feedBack.value ? p_feedBack.value : portfolio.positive_feedback,
            n_feedback: n_feedBack.value ? n_feedBack.value : portfolio.negative_feedback,
            theme_id: route.params.lessonThemeId,
            status: portfolioStatus,
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
                setTimeout(() => {
                    processing_save.value = false
                }, 500);
                
            }
            
        }).catch(function (error) {
            if (error.response) errorToast('エラーが発生しました。 ' + error.response.data.message)
            else if (error.request) errorToast('エラーが発生しました。')
            else errorToast('エラーが発生しました。 ' + error.message)                       
        });
        
    }
    
    const nextStage = () => {

        const uniqueChannell = Math.random().toString(36).substring(5);
        const answers = ['OK', 'キャンセル']
        emitter.emit('setToast', {
            active: true,  
            type: 'info', 
            content: 'グループディスカッションを完了にしますか。\n※完了後に、編集するができません。',
            closeButton: false, 
            autoClose: false,
            touchClose: false,
            answers: answers,
            channel: uniqueChannell
        })  
        emitter.on(uniqueChannell, async (data) => {                            
            if(data.answer === answers[0]){
                await saveContent('next')
                setTimeout(() => {                    
                    finishDiscussion()
                    processing_save.value = false
                }, 1000);
                // router.go(-1)
            }
        })       
    }
    const finishDiscussion = () => {
        const uniqueChannell = Math.random().toString(36).substring(5);
        const answers = ['OK']
        emitter.emit('setToast', {
            active: true,  
            type: 'info', 
            content: 'グループディスカッションを完了にしまた。\n\nお疲れ様でした。',
            closeButton: false, 
            autoClose: false,
            touchClose: false,
            answers: answers,
            channel: uniqueChannell
        })  
        emitter.on(uniqueChannell, () => {    
            lesson()                        
            router.push({name: 'top'})
        })   
    }
    const errorToast = () => {
            const uniqueChannell = Math.random().toString(36).substring(5);

            emitter.emit('setToast', {
                active: true,  
                type: 'info', 
                content: group_available.value ? '基礎知識を完了してください。' : 'グループディスカッションの日常調整連絡お待ちください。',
                closeButton: false, 
                autoClose: false,
                touchClose: false,
                answers: ['戻る'],
                channel: uniqueChannell
            })  
            emitter.on(uniqueChannell, (data) => {                            
                if(data.answer === '戻る'){
                    router.go(-1)
                }
            })
        }
    
</script>
<style>
    .inactive-overlay{
        color: white;
        font-size: 20px;
        text-align: center;
        padding: 20px;
    }
</style>