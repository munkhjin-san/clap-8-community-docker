<template>
    <div v-if="selectedTopic && selectedTopic.active == 1" style="background: var(--background-color);padding: 30px;word-wrap: break-word;white-space: break-spaces;line-height: 1.8;display: flex;flex-direction: column;gap: 30px;margin: 0 20px;">
        <div v-if="selectedTopic && selectedTopic.guidance" v-html="selectedTopic?.guidance">
        </div>
        
        <div v-if="lesson_p_feedBack">
            <p><strong>ポジティブフィードバック</strong></p>
            <p>{{ lesson_p_feedBack }}</p>
        </div>
        <div v-if="lesson_n_feedBack">
            <p><strong>ネガティブフィードバック</strong></p>
            <p>{{ lesson_n_feedBack }}</p>
        </div>
        <div class="si-box" style="margin:0">
            <p :style="{marginBottom: currentStatus ? '20px' : '0'}"><strong>{{currentStatus ? 'ポートフォリオを完成してください。' : 'ポートフォリオ'}}</strong></p>
            <FormShortText
                v-if="currentStatus"
                :initialValue="title_data"
                ref="portfolioTitle"
                placeHolder="ポートフォリオタイトル"
                uId="portfolioTitle"
                name="portfolioTitle"
                rules="required"
                label="タイトル"
                @setValue="val => portfolio_title = val"
            />
            <p v-else>{{ title_data }}</p>
        </div>
        <div class="si-box" style="margin:0">
            <FormLongText
                v-if="currentStatus"
                :initialValue="temp_content"   
                :placeHolder="`ポートフォリオの内容`"
                ref="portfolioBody"
                :key="temp_content"
                rules="required"
                uId="recordBody"
                name="recordBody"
                label="タイトル"
                @setValue="val => portfolio = val"
            />
            <p v-else>{{ temp_content }}</p>
        </div>
        <div v-if="currentStatus" style="display:flex; justify-content: center; gap:20px;flex-wrap: wrap;">
            <div>
                <LoaderButton @triggered="savePortfolio('save')" :loading="processing_save" :content="'保存する'"/>
            </div>
            <div>
                <LoaderButton @triggered="nextStage" :loading="processing" :content="'次へ'"/>
            </div>
        </div>
    </div>
</template>
<script setup>
    import { useRouter } from 'vue-router';
    import FormLongText from '../../Global/FormLongText.vue';
    import LoaderButton from '../../Global/LoaderButton.vue';
    import FormShortText from '../../Global/FormShortText.vue';
    import { ref, onBeforeMount } from 'vue'
    const props = defineProps(['lesson_n_feedBack','lesson_p_feedBack', 'temp_content', 'portfolioId', 'currentStatus', 'selectedTopic', 'available', 'title_data'])
    const portfolio = ref(props.temp_content || '')
    const portfolioBody = ref(null)
    const portfolioTitle = ref(null)
    const processing = ref(false)
    const router = useRouter()
    const processing_save = ref(false)
    const portfolio_title = ref(props.title_data || '')
    onBeforeMount(() => {
        setTimeout(() => {
            if(props.available){
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
                portfolio_title: portfolio_title.value ? portfolio_title.value : props.title_data,
                content: portfolio.value ? portfolio.value : props.temp_content,
                portfolio_id: props.portfolioId ? props.portfolioId : null,
                status: 2,
            }
            axios.post('/save_lesson_portfolio', params).then(response => {
                if(status == 'next'){
                    processing.value = false
                    router.push({name: 'form'})
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
        savePortfolio('next')
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