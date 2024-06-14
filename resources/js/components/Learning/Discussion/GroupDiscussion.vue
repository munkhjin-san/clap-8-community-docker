<template>
    <div class="section-wrapper">
        <div class="section-inner" v-if="selectedTopic && selectedTopic.active == 1">  
        
            <div>
                <p><strong>ポートフォリオ</strong></p>
                <p>{{ portfolio ? portfolio.content : '' }}</p>
            </div>
            <div class="si-box">
                <p :style="{marginBottom: portfolio && portfolio.status == 1 ? '20px' : '0'}"><strong>どのようなフィードバックをもらいましたか。</strong></p>
                <LongInput
                    v-if="portfolio && portfolio.status == 1"
                    :initialValue="portfolio ? portfolio.positive_feedback : p_feedBack"   
                    :placeHolder="`ポジティブフィードバックの内容`"
                    :key="portfolio ? portfolio.positive_feedback : 0"
                    ref="p_feedbackBody"
                    name="recordBody"
                    label="タイトル"
                    v-model="p_feedBack"
                />
                <div v-else>
                    <p>ポジティブフィードバック</p>
                    <p>{{ portfolio?.positive_feedback }}</p>
                </div>
            </div>
            <div class="si-box">
                <LongInput
                    v-if="portfolio && portfolio.status == 1 "
                    :initialValue="portfolio ? portfolio.negative_feedback : n_feedBack"   
                    :placeHolder="`ネガティブフィードバックの内容`"
                    :key="portfolio.negative_feedback"
                    ref="n_feedbackBody"
                    name="recordBody"
                    label="タイトル"
                    v-model="n_feedBack"
                />
                <div v-else>
                    <p>ネガティブフィードバック</p>
                    <p>{{ portfolio?.negative_feedback }}</p>
                </div>
            </div>
            <div class="si-box">
                <p :style="{marginBottom: portfolio && portfolio.status == 1 ? '20px' : '0'}"><strong>フィードバックから得た発見と成長</strong></p>
                <LongInput
                    v-if="portfolio && portfolio.status == 1 "
                    :initialValue="portfolio ? portfolio.noticed : noticed"   
                    :placeHolder="`発見と成長の内容`"
                    :key="portfolio.noticed"
                    ref="noticedBody"
                    name="recordBody"
                    label="タイトル"
                    v-model="noticed"
                />
                <div v-else>
                    <p>{{ portfolio?.noticed }}</p>
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
    import LongInput from '../../Form/LongInput.vue';
    import LoaderButton from '../../Global/LoaderButton.vue';
    import { ref, inject, computed, onBeforeMount, onMounted } from 'vue'
    import { useRoute, useRouter } from 'vue-router';
    const props = defineProps([
        'selectedTopic',
    ]);
    const portfolio = inject('portfolio')
    const route = useRoute()
    const p_feedBack = ref(portfolio ? portfolio.positive_feedback : "")
    const n_feedBack = ref(portfolio ? portfolio.negative_feedback : "")
    const p_feedbackBody = ref(null)
    const n_feedbackBody = ref(null)
    const noticed = ref(portfolio ? portfolio.noticed : "")
    const processing = ref(false)
    const router = useRouter()
    const lesson = inject('getLessonPortfolios')
    const processing_save = ref(false)
    const { confirm, notify, info } = inject('dialog')
   
    const saveContent = async(status) => {
        let portfolioStatus = 1
        if(status == 'next'){
            processing.value = true
            portfolioStatus = 2
        }else{
            processing_save.value = true
        }
        
        const params = {
            theme_id: route.params.lessonThemeId,
            params:{
                positive_feedback: p_feedBack.value ? p_feedBack.value : portfolio.positive_feedback,
                negative_feedback: n_feedBack.value ? n_feedBack.value : portfolio.negative_feedback,
                noticed: noticed.value ? noticed.value : portfolio.noticed,                
                status: portfolioStatus,
            }

        }
        axios.post('/save_lesson_portfolio', params).then(response => {
            if(status == 'save'){
                info(props.editTarget ? '編集しました。' :'保存しました。')
                setTimeout(() => {
                    processing_save.value = false
                }, 500);
                
            }
            
        }).catch(function (error) {
            if (error.response) notify('エラーが発生しました。 ' + error.response.data.message)
            else if (error.request) notify('エラーが発生しました。')
            else notify('エラーが発生しました。 ' + error.message)                       
        });
        
    }
    
    const nextStage = async() => {
        const answer = await confirm('グループディスカッションを完了にしますか。\n完了後は編集ができません。')
        if(!answer) return
        await saveContent('next')
        setTimeout(() => {                    
            finishDiscussion()
            processing_save.value = false
        }, 1000);      
    }
    const finishDiscussion = async() => {
        const options = {
            answers: [{label: 'OK', value: true}]
        }
        const answer = await confirm('グループディスカッションを完了にしまた。\nお疲れ様でした。', options)
        if(answer){
            lesson()                        
            router.push({name: 'top'})
        }
            
         
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