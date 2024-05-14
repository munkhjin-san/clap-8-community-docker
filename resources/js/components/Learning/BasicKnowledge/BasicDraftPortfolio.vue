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
                    rules="required|max:2000"
                    name="recordBody"
                    label="タイトル"
                    v-model="content"
                />
                <p v-else><strong>ディスカッション用ポートフォリオ内容<br></strong>{{ portfolio?.content }}</p>
            </div>
            
            <div v-if="portfolio && portfolio.status < 1" style="display:flex; justify-content: center; gap:20px;flex-wrap: wrap;margin-top: 25px;">
                <div>
                    <LoaderButton @triggered="tempSavePort('save')" :loading="processing_save" :content="'一時保存'"/>
                </div> 
                <div>
                    <LoaderButton @triggered="nextStage" :loading="processing" :content="'作成完了'"/>
                </div>
            </div>

        </div>
        <router-view>
            
        </router-view>
    </div>
</template>
<script setup>
    import { useRoute, useRouter } from 'vue-router';
    import LoaderButton from '../../Global/LoaderButton.vue';
    import LongInput from '../../Form/LongInput.vue';
    import ShortInput from '../../Form/ShortInput.vue';
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
    const { notify, info, confirm } = inject('dialog')
    const tempSavePort = async(status) => {
        const result = await portfolioBody.value.validate()
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
            try{
                await axios.post('/save_lesson_portfolio', params)
                if(status == 'next'){

                }else{
                    info(props.editTarget ? '編集しました。' :'保存しました。')
                    processing_save.value = false
                }
                lesson()
            }catch (error){
                if (error.response) notify('エラーが発生しました。 ' + error.response.data.message)
                else if (error.request) notify('エラーが発生しました。')
                else notify('エラーが発生しました。 ' + error.message)   
            }
        }
    }
    const nextStage = async() => {
        const answer = await confirm('基礎知識研修を完了にしますか。\n※完了後に、編集するができません。')
        if(!answer) return  
        await tempSavePort('next')
        setTimeout(() => {                    
            finishBasic()
        }, 1000); 
    }

    const finishBasic = async() => {
        const options = {
            answers: [{label: 'OK', value: true}]
        }
        const answer = await confirm('基礎知識研修完了しました。\n\nお疲れ様でした。', options)
        if(answer){
            processing.value = false   
            await lesson()                     
            router.push({name: 'top'})
        }        
    } 
</script>