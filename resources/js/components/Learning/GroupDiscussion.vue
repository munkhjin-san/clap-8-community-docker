<template>
    <div>
        <div v-if="$route.name == 'discussion'" style="background: var(--background-color);padding: 30px;word-wrap: break-word;white-space: break-spaces;line-height: 1.8;display: flex;flex-direction: column;gap: 30px;margin: 0 20px;">
            <div>
                <p><strong>ポートフォリオ</strong></p>
                <p>{{ temp_content }}</p>
            </div>
            <div>
                <p><strong>どのようなフィードバックをもらいましたか。</strong></p>
                
            </div>
            <div class="si-box" style="margin:0">
                <FormLongText
                    :initialValue="lesson_p_feedBack"   
                    :placeHolder="`ポジティブフィードバックの内容`"
                    :key="lesson_p_feedBack"
                    ref="p_feedbackBody"
                    uId="recordBody"
                    name="recordBody"
                    label="タイトル"
                    @setValue="val => p_feedBack = val"
                />
            </div>
            <div class="si-box" style="margin:0">
                <FormLongText
                    :initialValue="lesson_n_feedBack"   
                    :placeHolder="`ネガティブフィードバックの内容`"
                    :key="lesson_n_feedBack"
                    ref="n_feedbackBody"
                    uId="recordBody"
                    name="recordBody"
                    label="タイトル"
                    @setValue="val => n_feedBack = val"
                />
            </div>
            <div style="display:flex; justify-content: center; gap:20px;flex-wrap: wrap;">
                <div>
                    <LoaderButton @triggered="saveContent('save')" :loading="processing_save" :content="'保存する'"/>
                </div>
                <div>
                    <LoaderButton @triggered="nextStage" :loading="processing" :content="'次へ'"/>
                </div>
            </div>
            
            
        </div>
    </div>
    
</template>
<script setup>
    import axios from 'axios';
    import FormLongText from '../Global/FormLongText.vue';
    import LoaderButton from '../Global/LoaderButton.vue';
    import { ref, inject, computed, onMounted } from 'vue'
    import { useRouter } from 'vue-router';
    const props = defineProps([
        'comment', 
        'topicId', 
        'temp_content', 
        'portfolioId', 
        'lesson_n_feedBack',
        'lesson_p_feedBack',
        'selectedTopic'
    ]);
    const p_feedBack = ref(props.lesson_p_feedBack || "")
    const n_feedBack = ref(props.lesson_n_feedBack || "")
    const p_feedbackBody = ref(null)
    const n_feedbackBody = ref(null)
    const processing = ref(false)
    const router = useRouter()
    const theme = inject('getThemes')
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
    onMounted(() => {
        setTimeout(() => {
            if(!discussionDay.value){
                errorToast()
            }
        }, 500)
            
    })
    const saveContent = async(status) => {
            if(status == 'next'){
                processing.value = true
            }else{
                processing_save.value = true
            }
            
            const params = {
                p_feedback: p_feedBack.value ? p_feedBack.value : props.lesson_p_feedBack,
                n_feedback: n_feedBack.value ? n_feedBack.value : props.lesson_n_feedBack,
                portfolio_id: props.portfolioId,
                status: 2,
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
                if(status == 'next'){
                    processing.value = false
                    router.push({name : 'portfolio'})
                }
            }).catch(function (error) {
                if (error.response) errorToast('エラーが発生しました。 ' + error.response.data.message)
                else if (error.request) errorToast('エラーが発生しました。')
                else errorToast('エラーが発生しました。 ' + error.message)                       
            });
        
    }
    
    const nextStage = () => {
        saveContent('next')
        
    }
    const errorToast = () => {
            const uniqueChannell = Math.random().toString(36).substring(5);

            emitter.emit('setToast', {
                active: true,  
                type: 'info', 
                content: 'グループディスカッションの日常調整連絡お待ちください。',
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