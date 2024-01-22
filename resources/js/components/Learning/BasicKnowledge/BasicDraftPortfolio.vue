<template>
    <div class="section-wrapper" style="height: calc(100% - 50px);">
        <div class="section-inner">    
            <div>
                <p><strong>ディスカッション用のポートフォリオを作成してください。</strong></p>
                <div style="font-size: 13px;margin-top: 15px;">
                    <div v-if="selectedTopic && selectedTopic.guidance" v-html="selectedTopic?.guidance"></div>
                </div>
            </div>
            <div class="si-box">
                <FormShortText
                    v-if="portfolio && portfolio.status < 1"
                    :initialValue="portfolio ? portfolio.portfolio_title : portfolio_title"
                    ref="portfolioTitle"
                    placeHolder="ポートフォリオタイトル"
                    uId="portfolioTitle"
                    name="portfolioTitle"
                    rules="required"
                    label="タイトル"
                    @setValue="val => portfolio_title = val"
                />
                <p v-else>{{ portfolio?.portfolio_title }}</p>
            </div>
            <div class="si-box">
                <FormLongText
                    v-if="portfolio && portfolio.status < 1"
                    :initialValue="portfolio ? portfolio.content : content"   
                    :placeHolder="`ポートフォリオ内容`"
                    :key="portfolio ? portfolio.content : 0"
                    ref="portfolioBody"
                    rules="required|max:2000"
                    uId="recordBody"
                    name="recordBody"
                    label="タイトル"
                    @setValue="val => content = val"
                />
                <p v-else>{{ portfolio?.content }}</p>
            </div>
            
            <div v-if="portfolio && portfolio.status < 1" style="display:flex; justify-content: center; gap:20px;flex-wrap: wrap;margin-top: 25px;">
                <div>
                    <LoaderButton @triggered="tempSavePort('save')" :loading="processing_save" :content="'一時保存'"/>
                </div> 
                <div>
                    <LoaderButton @triggered="nextStage" :loading="processing" :content="'基礎知識完了'"/>
                </div>
            </div>

        </div>
    </div>
</template>
<script setup>
    import { useRoute, useRouter } from 'vue-router';
    import FormLongText from '../../Global/FormLongText.vue';
    import LoaderButton from '../../Global/LoaderButton.vue';
    import FormShortText from '../../Global/FormShortText.vue';
    import { ref, inject } from 'vue'
    const props = defineProps(['selectedTopic'])
    const content = ref('')
    const portfolioBody = ref(null)
    const processing = ref(false)
    const processing_save = ref(false)
    const router = useRouter()
    const route = useRoute()
    const lesson = inject('getLessonPortfolios')
    const portfolio = inject('portfolio')
    const portfolio_title = ref('')
    const tempSavePort = async(status) => {
        const result = await portfolioBody.value.$refs.recordBody.validate()
        if(result.valid){
            let portfolioStatus = 0
            if(status == 'next'){
                processing.value = true
                portfolioStatus = 1
            }else{
                processing_save.value = true
            }
            const params = {
                content: content.value ? content.value : portfolio?.content,
                status: portfolioStatus,
                theme_id: route.params.lessonThemeId,
                portfolio_title: portfolio_title.value ? portfolio_title.value : portfolio?.portfolio_title
            }
            axios.post('/save_lesson_portfolio', params).then(response => {
                
                
                if(status == 'next'){

                }else{
                    const data = {
                        text: props.editTarget ? '編集しました。' :'保存しました。',
                        channel: Math.random().toString(36).substring(5),
                        icon: 0,
                        view: true
                    }
                    emitter.emit('setInfo', data)
                    processing_save.value = false
                }
            }).catch(function (error) {
                if (error.response) errorToast('エラーが発生しました。 ' + error.response.data.message)
                else if (error.request) errorToast('エラーが発生しました。')
                else errorToast('エラーが発生しました。 ' + error.message)                       
            });
        }
    }
    const nextStage = async() => {
        
        const uniqueChannell = Math.random().toString(36).substring(5);
        const answers = ['OK', 'キャンセル']
        emitter.emit('setToast', {
            active: true,  
            type: 'info', 
            content: '基礎知識研修を完了にしますか。\n※完了後に、編集するができません。',
            closeButton: false, 
            autoClose: false,
            touchClose: false,
            answers: answers,
            channel: uniqueChannell
        })  
        emitter.on(uniqueChannell, async (data) => {                            
            if(data.answer === answers[0]){
                await tempSavePort('next')
                setTimeout(() => {                    
                    finishBasic()
                }, 1000);
            }
        })  

        
    }

    const finishBasic = () => {
        const uniqueChannell = Math.random().toString(36).substring(5);
        emitter.emit('setToast', {
            active: true,  
            type: 'info', 
            content:'基礎知識研修完了しました。\n\nお疲れ様でした。',
            closeButton: false, 
            autoClose: false,
            touchClose: false,
            answers: ['OK'],
            channel: uniqueChannell
        })  
        emitter.on(uniqueChannell, () => {    
            processing.value = false   
            lesson()                     
            router.push({name: 'top'})
        })
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
</script>