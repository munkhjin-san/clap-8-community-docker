<template>
    <div class="section-wrapper">
        <div v-if="route.name == 'portfoliodraft'" class="section-inner">    
            <div>
                <div>
                    <div v-if="selectedTopic && selectedTopic.guidance" v-html="selectedTopic?.guidance"></div>
                </div>
                
                <div style="margin-top: 30px;" class="section-contents" >
                    <p style="margin-bottom: 10px;"><strong>重要だと理解した点</strong></p>
                    <div v-for="section in portfolio?.lesson_sections">
                        <p>{{ section?.lesson_material?.title }}</p>
                        <p>{{ section?.content }}</p>
                    </div>
                    
                </div>
            </div>
            <div class="si-box" style="margin:45px 0">
                <LoaderButton :loading="false" content="ポートフォリオ作成例を確認する" @triggered="router.push({ name: 'portfolioview'})"/>
            </div>
            <div class="si-box">
                <p v-if="portfolio?.status < 1" :style="{marginBottom: portfolio?.status < 1 ? '20px' : '0'}"><strong>ディスカッション用のポートフォリオを作成してください</strong></p>
                <ShortInput
                    v-if="portfolio?.status < 1"
                    :initialValue="portfolio ? portfolio.portfolio_title : portfolio_title"
                    :key="`p_key_${portfolio && portfolio.portfolio_title ? portfolio.portfolio_title : 0}`"
                    ref="portfolioTitle"
                    placeHolder="ディスカッション用ポートフォリオタイトル"
                    name="portfolioTitle"
                    rules="required"
                    label="タイトル"
                    v-model="portfolio_title"
                />
                <p v-else><strong>ディスカッション用ポートフォリオタイトル<br></strong>{{ portfolio?.portfolio_title }}</p>
            </div>
            
            <div class="si-box">
                <LongInput
                    v-if="portfolio?.status < 1"
                    :initialValue="portfolio ? portfolio.content : content"   
                    :placeHolder="`ディスカッション用ポートフォリオ内容`"
                    :key="`${portfolio ? portfolio.content : 0}_${route.fullPath}_${portfolio.updated_at}`"
                    ref="portfolioBody"
                    rules="required"
                    name="recordBody"
                    label="タイトル"
                    v-model="content"
                />
                <p v-else><strong>ディスカッション用ポートフォリオ内容<br></strong>{{ portfolio?.content }}</p>
            </div>
            <OpenAiReview 
                v-if="selectedTopic && portfolio && selectedTopic.assistant_id" 
                :assistand-id="selectedTopic.assistant_id" 
                :soure-text="portfolio?.ai_review_pre" 
                :message="content"
                :confirm-text="'発表用ポートフォリオは、研修テーマに沿った内容であり、発表時間が５分程度の内容にまとめられている。'"
                ref="reviewEl"
            />
            <div v-if="portfolio && portfolio.status < 1" style="display:flex; justify-content: center; gap:20px;flex-wrap: wrap;margin-top: 25px;">              
                <LoaderButton style="margin: 0" @triggered="savePortfolio(0)" :loading="loading[0]" :content="'一時保存'"/>
                <LoaderButton style="margin: 0" @triggered="finishPortfolio" :loading="loading[1]" :content="'作成完了'"/>               
            </div>

        </div>
        <router-view></router-view>
    </div>
</template>
<script setup>
    import { useRoute, useRouter } from 'vue-router';
    import LoaderButton from '../../Global/LoaderButton.vue';
    import LongInput from '../../Form/LongInput.vue';
    import ShortInput from '../../Form/ShortInput.vue';
    import { ref, inject } from 'vue'
    import OpenAiReview from '../../Global/OpenAiReview.vue'
    const props = defineProps(['selectedTopic', 'materials'])
    const content = ref('')
    const portfolioBody = ref(null)
    const loading = ref([false, false])
    const router = useRouter()
    const route = useRoute()
    const lesson = inject('getLessonPortfolios')
    const portfolio = inject('portfolio')
    const portfolio_title = ref('')
    const { notify, info, confirm } = inject('dialog')
    const reviewEl = ref(null)
    const savePortfolio = async(status) => {
        const result = await portfolioBody.value.validate()
        if(result.valid){
            loading.value[status] = true
            const params = {                
                theme_id: route.params.lessonThemeId,
                params: {
                    status: status,
                    content: content.value ? content.value : portfolio.value?.content,
                    portfolio_title: portfolio_title.value ? portfolio_title.value : portfolio.value?.portfolio_title,
                    ai_review_pre: reviewEl.value?.reviewResultRaw,
                }
            }
            try{
                await axios.post('/save_lesson_portfolio', params)
                if(status == 0){
                    info(props.editTarget ? '編集しました。' :'保存しました。')
                    loading.value[status] = false
                }
                lesson()
            }catch (error){
                if (error.response) notify('エラーが発生しました。 ' + error.response.data.message)
                else if (error.request) notify('エラーが発生しました。')
                else notify('エラーが発生しました。 ' + error.message)   
            }
        }
    }

    const finishPortfolio = async() => {
        if(props.selectedTopic.assistant_id && !reviewEl.value?.reviewResultRaw){
            notify('基礎知識研修を完了する前、AI分析してください。')
            return
        }
        const valid = await reviewEl.value?.validate()
        if(props.selectedTopic.assistant_id && !valid){
            return
        }
        const answer = await confirm('基礎知識研修を完了にしますか。\n完了後は編集ができません。')
        if(!answer) return  
        await savePortfolio(1)
        setTimeout(() => {                    
            finishBasic()
        }, 1000); 
    }

    const finishBasic = async() => {
        const options = {
            answers: [{label: 'OK', value: true}]
        }
        const answer = await confirm('基礎知識研修完了しました。\nお疲れ様でした。', options)
        if(answer){
            loading.value[1] = false
            await lesson()                     
            router.push({name: 'top'})
        }        
    } 
</script>

<style lang="scss">
.response-container {
  line-height: 1.6;
  color: var(--primary-color);
  font-size: 14px;
}
.response-container ol{
    list-style: decimal!important;
    padding: revert-layer !important;
    line-height: 1 !important;
}
.response-container ul{
    list-style: disc !important;
    padding: revert-layer !important;
    line-height: 1 !important;
}
.response-container li {
    line-height: 2 !important;
    white-space: normal !important;
}
.response-container h1,
.response-container h2,
.response-container h3,
.response-container h4,
.response-container h5,
.response-container h6 {
  font-weight: bold;
}


</style>