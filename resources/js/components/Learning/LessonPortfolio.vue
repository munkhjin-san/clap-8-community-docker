<template>
    <div style="background: var(--background-color);padding: 30px;word-wrap: break-word;white-space: break-spaces;line-height: 1.8;display: flex;flex-direction: column;gap: 30px;margin: 0 20px;">
        <!-- <div>{{ comment }}</div>
        <div class="post-separetor" style="margin-bottom: 0;"></div> -->
        <div>
            <p><strong>ディスカッション用のポートフォリオを作成してください。</strong></p>
            <p style="font-size: 13px;color: gray;">ポートフォリオとは<br>ポートフォリオは、個人やプロフェッショナルが自身のスキル、経験、および達成をまとめ、共有するための重要なツールです。<br>これは、仕事や学術的な成果、プロジェクト参加経験などを包括的に紹介し、他者に対して自己プレゼンテーションを行うための手段となります。</p>
        </div>
        <div class="si-box" style="margin:0">
            <FormLongText
                :initialValue="temp_content"   
                :placeHolder="`ポートフォリオ内容`"
                :key="temp_content"
                ref="portfolioBody"
                rules="required|max:2000"
                uId="recordBody"
                name="recordBody"
                label="タイトル"
                @setValue="val => content = val"
            />
        </div>
        
        <div style="display:flex; justify-content: center; gap:20px;flex-wrap: wrap;">
            <div>
                <LoaderButton @triggered="tempSavePort('save')" :loading="processing_save" :content="'保存する'"/>
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
    import { ref, inject } from 'vue'
    const props = defineProps(['comment', 'temp_content', 'portfolioId', 'selectedTopic'])
    const content = ref(props.temp_content || '')
    const portfolioBody = ref(null)
    const processing = ref(false)
    const processing_save = ref(false)
    const router = useRouter()
    const theme = inject('getThemes')
    const lesson = inject('getLessonPortfolios')
    const tempSavePort = async(status) => {
        const result = await portfolioBody.value.$refs.recordBody.validate()
        if(result.valid){
            if(status == 'next'){
                processing.value = true
            }else{
                processing_save.value = true
            }
            const params = {
                content: content.value ? content.value : props.temp_content,
                portfolio_id: props.portfolioId ? props.portfolioId : null,
                status: props.selectedTopic && props.selectedTopic.status ? props.selectedTopic.status : 1
            }
            axios.post('/save_lesson_portfolio', params).then(response => {
                lesson()
                
                if(status == 'next'){
                    router.push({name: 'discussion'})
                    processing.value = false
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
        tempSavePort('next')
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