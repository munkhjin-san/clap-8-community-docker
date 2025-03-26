<template>
    <div class="section-wrapper">
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
                <ShortInput
                    v-if="portfolio && portfolio.status == 2"
                    :initialValue="portfolio.public_title ? portfolio.public_title : ''"
                    ref="portfolioTitle"
                    placeHolder="ポートフォリオタイトル"
                    name="portfolioTitle"
                    rules="required"
                    label="タイトル"
                    v-model="portfolio_title"
                />
                <p v-else>{{ portfolio?.public_title }}</p>
            </div>
            <div class="si-box">
                <LongInput
                    v-if="portfolio && portfolio.status == 2"
                    :initialValue="portfolio.public_content ? portfolio.public_content : ''"
                    :placeHolder="`ポートフォリオの内容`"
                    ref="portfolioBody"
                    :key="portfolio.public_content ? portfolio.public_content : 0"
                    rules="required"
                    name="recordBody"
                    label="タイトル"
                    v-model="portfolioContent"
                />
                <p v-else>{{ portfolio?.public_content }}</p>
            </div>
            <OpenAiReview 
                v-if="selectedTopic && portfolio" 
                assistand-id="asst_NnPHXCXimhJ09GNZwOfg107Y" 
                :soure-text="portfolio?.ai_review_final" 
                :message="portfolioContent"
                confirm-text="ポジティブ・ネガティブフィードバックから得た発見と成長がポートフォリオに反映されている。"
                ref="reviewElFinal"
            />
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
    import LongInput from '../../Form/LongInput.vue';
    import ShortInput from '../../Form/ShortInput.vue';
    import LoaderButton from '../../Global/LoaderButton.vue';
    import { ref, onBeforeMount, inject } from 'vue'
    import OpenAiReview from '../../Global/OpenAiReview.vue'
    const { confirm, info, notify } = inject('dialog')
    const props = defineProps(['selectedTopic', 'available'])
    const portfolio = inject('portfolio')
    const portfolioContent = ref(portfolio ? portfolio.public_content : '')
    const portfolioBody = ref(null)
    const portfolioTitle = ref(null)
    const processing = ref(false)
    const router = useRouter()
    const processing_save = ref(false)
    const portfolio_title = ref(portfolio ? portfolio.public_title : '')
    const route = useRoute()
    const reviewElFinal = ref(null)
    onBeforeMount(() => {
        setTimeout(() => {
            if(props.selectedTopic?.lesson_portfolio?.status < 2 || !props.selectedTopic.lesson_portfolio){
                backToast()
            }
        }, 500)
        
    })
    const savePortfolio = async(status) => {
        let portfolioStatus = 2
        if(status == 'next'){
            
            processing.value = true
            // portfolioStatus = 3
        }else{
            processing_save.value = true
        }
        const params = {
            theme_id: route.params.lessonThemeId,
            params: {
                portfolio_title: portfolio.value.portfolio_title,
                content: portfolio.value.content,                
                public_title: portfolio_title.value,
                public_content: portfolioContent.value,
                status: portfolioStatus,
                ai_review_final: reviewElFinal.value?.reviewResultRaw,
            }  

        }
        axios.post('/save_lesson_portfolio', params).then(response => {
            if(status == 'next'){

            }else{
                info(props.editTarget ? '編集しました。' :'保存しました。')
                processing_save.value = false
            }
        }).catch(function (error) {
            if (error.response) notify('エラーが発生しました。 ' + error.response.data.message)
            else if (error.request) notify('エラーが発生しました。')
            else notify('エラーが発生しました。 ' + error.message)                       
        });
    }
    const nextStage = async() => {
        const result = await portfolioBody.value.validate()
        const title_result = await portfolioTitle.value.validate()
        const valid = await reviewElFinal.value?.validate()
        if(result.valid && title_result.valid && valid){
            const answer = await confirm('ポートフォリオを完了にしますか。\n完了後は編集ができません。')
                                      
            if(!answer.value) return
            await savePortfolio('next')
            setTimeout(() => {                    
                processing.value = false
                router.push({name: 'form'})
            }, 1000);
               
        }
        
    }
    const backToast = async() => {
        const options = {
            answers: [{label: '戻る', value: true}]
        }
        const answer = await confirm('グループディスカッションを完了してください。', options)                       
        if(answer.value){
            router.go(-1)
        }
    }
</script>