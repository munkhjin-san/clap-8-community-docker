<template>
    <div style="background: var(--background-color);padding: 30px;word-wrap: break-word;white-space: break-spaces;line-height: 1.8;display: flex;flex-direction: column;gap: 30px;margin: 0 20px;">
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
            <FormLongText
                v-if="currentStatus"
                :initialValue="temp_content"   
                :placeHolder="`ポートフォリオの内容`"
                ref="portfolioBody"
                :key="temp_content"
                rules="required|max:2000"
                uId="recordBody"
                name="recordBody"
                label="タイトル"
                @setValue="val => portfolio = val"
            />
            <p v-else>{{ temp_content }}</p>
        </div>
        <div v-if="currentStatus" style="display:flex; justify-content: center; gap:20px;flex-wrap: wrap;">
            <div>
                <LoaderButton @triggered="savePortfolio(1)" :loading="processing_save" :content="'保存する'"/>
            </div>
            <div>
                <LoaderButton @triggered="nextStage" :loading="processing" :content="'次へ'"/>
            </div>
        </div>
    </div>
</template>
<script setup>
    import { useRouter } from 'vue-router';
    import FormLongText from '../Global/FormLongText.vue';
    import LoaderButton from '../Global/LoaderButton.vue';
    import { inject, ref } from 'vue'
    const props = defineProps(['lesson_n_feedBack','lesson_p_feedBack', 'temp_content', 'portfolioId', 'currentStatus'])
    const portfolio = ref(props.temp_content || '')
    const portfolioBody = ref(null)
    const processing = ref(false)
    const router = useRouter()
    const processing_save = ref(false)
    const savePortfolio = async(status) => {
        const result = await portfolioBody.value.$refs.recordBody.validate()
        if(result.valid){
            if(status == 2){
                processing.value = true
            }else{
                processing_save.value = true
            }
            const params = {
                content: portfolio.value ? portfolio.value : props.temp_content,
                portfolio_id: props.portfolioId ? props.portfolioId : null,
                status: status,
            }
            axios.post('/save_lesson_portfolio', params).then(response => {
                if(status == 2){
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
        savePortfolio(2)
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