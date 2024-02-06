<template>
    <div class="section-wrapper" style="height: calc(100% - 50px);">
        <div v-if="selectedTopic && selectedTopic.active == 1"  class="section-inner">  
    
            <!-- <div v-if="selectedTopic && selectedTopic.guidance" v-html="selectedTopic?.guidance"></div> -->
            
            <div v-if="portfolio && portfolio.positive_feedback">
                <p><strong>ポジティブフィードバック</strong></p>
                <p>{{ portfolio.positive_feedback }}</p>
            </div>
            <div class="si-box" v-if="portfolio && portfolio.negative_feedback">
                <p><strong>ネガティブフィードバック</strong></p>
                <p>{{ portfolio.negative_feedback }}</p>
            </div>
            <div class="si-box" v-if="portfolio && portfolio.noticed">
                <p><strong>フィードバックから得た発見と成長</strong></p>
                <p>{{ portfolio.noticed }}</p>
            </div>
            <div class="si-box" v-if="portfolio && portfolio.content">
                <p><strong>ディスカッション用ポートフォリオタイトル</strong></p>
                <p>{{ portfolio.portfolio_title }}</p>
                <p><strong>ディスカッション用ポートフォリオ内容</strong></p>
                <p>{{ portfolio.content }}</p>
            </div>
            <div class="si-box">
                <p :style="{marginBottom: portfolio && portfolio.status == 2 ? '20px' : '0'}"><strong>{{portfolio && portfolio.status == 2 ? 'フィードバックによる発見と成長を反映し、ポートフォリオを完成させてください。' : 'ポートフォリオ'}}</strong></p>
                <FormShortText
                    v-if="portfolio && portfolio.status == 2"
                    :initialValue="portfolio ? portfolio.portfolio_title : portfolio_title"
                    ref="portfolioTitle"
                    placeHolder="ポートフォリオタイトル"
                    uId="portfolioTitle"
                    name="portfolioTitle"
                    rules="required"
                    label="タイトル"
                    @setValue="val => portfolio_title = val"
                />
                <p v-else>{{ portfolio?.public_title }}</p>
            </div>
            <div class="si-box">
                <FormLongText
                    v-if="portfolio && portfolio.status == 2"
                    :initialValue="portfolio ? portfolio.content : portfolioContent"   
                    :placeHolder="`ポートフォリオの内容`"
                    ref="portfolioBody"
                    :key="portfolio ? portfolio.content : portfolioContent"
                    rules="required"
                    uId="recordBody"
                    name="recordBody"
                    label="タイトル"
                    @setValue="val => portfolioContent = val"
                />
                <p v-else>{{ portfolio?.public_content }}</p>
            </div>
            <div v-if="portfolio && portfolio.status == 2" style="display:flex; justify-content: center; gap:20px;flex-wrap: wrap;margin-top: 25px;">
                <div>
                    <LoaderButton @triggered="savePortfolio('save')" :loading="processing_save" :content="'一時保存'"/>
                </div>
                <div>
                    <LoaderButton @triggered="nextStage" :loading="processing" :content="'次へ'"/>
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
    import { ref, onBeforeMount, inject } from 'vue'
    const props = defineProps(['selectedTopic', 'available'])
    const portfolioContent = ref(portfolio ? portfolio.content : '')
    const portfolioBody = ref(null)
    const portfolioTitle = ref(null)
    const processing = ref(false)
    const router = useRouter()
    const processing_save = ref(false)
    const portfolio_title = ref(portfolio ? portfolio.portfolio_title : '')
    const portfolio = inject('portfolio')
    const route = useRoute()
    onBeforeMount(() => {
        setTimeout(() => {
            if(props.selectedTopic?.lesson_portfolio?.status < 2 || !props.selectedTopic.lesson_portfolio){
                backToast()
            }
        }, 500)
        
    })
    const savePortfolio = async(status) => {
        const result = await portfolioBody.value.$refs.recordBody.validate()
        const title_result = await portfolioTitle.value.$refs.portfolioTitle.validate()
        if(result.valid && title_result.valid){
            if(status == 'next'){
                processing.value = true
            }else{
                processing_save.value = true
            }
            const params = {
                portfolio_title: portfolio.value.portfolio_title,
                content: portfolio.value.content,
                theme_id: route.params.lessonThemeId,
                public_title: portfolio_title.value ? portfolio_title.value : portfolio.value.portfolio_title,
                public_content: portfolioContent.value ? portfolioContent.value : portfolio.value.content,
                status: 3,
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
    const nextStage = () => {
        const uniqueChannell = Math.random().toString(36).substring(5);
        const answers = ['OK', 'キャンセル']
        emitter.emit('setToast', {
            active: true,  
            type: 'info', 
            content: 'ポートフォリオを完了にしますか。\n※完了後に、編集するができません。',
            closeButton: false, 
            autoClose: false,
            touchClose: false,
            answers: answers,
            channel: uniqueChannell
        })  
        emitter.on(uniqueChannell, async (data) => {                            
            if(data.answer === answers[0]){
                await savePortfolio('next')
                setTimeout(() => {                    
                    processing.value = false
                    router.push({name: 'form'})
                }, 1000);
            }
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
    const backToast = () => {
        const uniqueChannell = Math.random().toString(36).substring(5);

        emitter.emit('setToast', {
            active: true,  
            type: 'info', 
            content: 'グループディスカッションを完了してください。',
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