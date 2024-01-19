<template>
    <div style="background: inherit;">
        <div v-if="$route.name == 'more'" style="background: inherit;">
            <div class="lessons-topic">
                <div>
                    <span style="font-size: 24px;background-color: #000;color: white;font-weight: 600;padding: 0 20px;">{{ material ? material.title : '' }}</span>
                    <div v-if="material" v-html="material.content_detailed"></div>
                </div>
            </div>
            <div class="post-separetor" style="margin-top: 30px;"></div>
            <div>
                <p><strong>研修内容を理解しましたか</strong></p>
                <div v-for="answer in list" style="display: flex;align-items: center;padding: 5px 0;">
                    <input class="fish-eye" v-model="selectedAnswer" type="radio" :id="answer.value" name="answer" :value="answer.value">
                    <label style="margin-left:10px;cursor:pointer" :for="answer.value">{{answer.content}}</label>  
                </div>
                <span class="form-error" style="font-size: 11px;color:tomato">{{ selectedAnswer != null ? '' : radioError }}</span>
            </div>
            <div v-if="supportPopup" class="overlay">
                <div class="chatCreate" @mousedown.stop style="overflow: hidden auto;">     
                    <div class="recordFormTitle" style="display:flex">
                        <div class="m-close-button" @click="supportPopup = false" style="position:unset; margin:auto 0 auto auto">
                            <svg version="1.1" xmlns="http://www.w3.org/2000/svg" class="modalWindowCloseButton" viewBox="0 0 32 32">
                                <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                            </svg>                        
                        </div> 
                    </div>
                    <div>
                        <div class="topic-title">
                            <p>研修内容に関するサポート案内</p>
                        </div>
                        <div class="flex flex-col gap-30">
                            <p>私たちの研修プログラムでは、皆さんがスムーズに学習を進められるよう、以下の2つのサポート方法を用意しています。</p>
                            <p>1.メッセージでのサポート：研修の内容について疑問や質問があれば、いつでもメッセージを送ってください。<br>チームが迅速に回答し、必要な情報を提供します。</p>
                            <p>2.個別の相談：より詳しい説明や個別のガイダンスが必要な場合、個人的な相談の時間を設定して対応します。<br>この時間を利用して、研修内容の深い理解や具体的なアドバイスを受けることができます。</p>
                            <p>ご希望のサポート方法を選択して、私たちにお知らせください。<br>分からないことがあれば、遠慮なくお問い合わせください。</p>
                            <p><strong>どのサポートを希望しますか？</strong></p>
                        </div>
                    </div>      
                    <div>
                        <div v-for="answer in supportChoices" style="display: flex;align-items: center;padding: 5px 0;">
                            <input class="fish-eye" v-model="selectedRadio" type="radio" :id="answer.value" name="nextanswer" :value="answer.value">
                            <label style="margin-left:10px;cursor:pointer" :for="answer.value">{{answer.content}}</label>  
                        </div>
                        <span class="form-error" style="font-size: 11px;color:tomato">{{ selectedRadio != null ? '' : radioError }}</span>
                    </div>
                
        
                            
                            
                    <div class="si-box">
                        <LoaderButton @triggered="fail" :loading="failProcessing" :content="'保存する'"/>
                    </div>               
                
                </div>
                
            </div>
            
            <div v-if="selectedAnswer == 1" class="si-box" style="margin:0">
                <p style="margin-bottom: 20px;"><strong>研修内容から理解したものやハイライトしたい部分を入力してください。</strong></p>
                <FormLongText
                    :initialValue="comment"   
                    :placeHolder="`基礎知識の内容`"
                    ref="moreDetailContent"
                    rules="required|max:2000"
                    uId="recordBody"
                    name="recordBody"
                    label="タイトル"
                    @setValue="val => comment = val"
                />
            </div>
            
            <div v-if="selectedAnswer !== null" style="display:flex; justify-content: center; gap:20px;flex-wrap: wrap;">
                <div v-if="selectedAnswer == 1" class="si-box" style="display: flex;gap: 20px;">
                    <LoaderButton @triggered="successAndSave()" :loading="saving" :content="'一時保存'"/>
                    <LoaderButton @triggered="successAndNext()" :loading="processing" :content="'完了'"/>
                </div> 
                <div v-if="selectedAnswer == 0">
                    <LoaderButton @triggered="supportPopup = true" :loading="false" content="次へ"/>
                </div>
            </div>  
        </div>
    </div>
</template>
<script setup>
import { onBeforeRouteLeave, useRoute, useRouter } from 'vue-router';
import FormLongText from '../../Global/FormLongText.vue';
import LoaderButton from '../../Global/LoaderButton.vue';
import { ref,inject, onMounted, } from 'vue';
    const route = useRoute()
    const props = defineProps(['material', 'lessonThemeId', 'selectedTopic', 'portfolioId', 'sectionUpdate'])
    const comment = ref("")
    const router = useRouter()
    const processing = ref(false)
    const moreDetailContent = ref(null)
    const list = ref([
        { value: 1, content: '理解しました'},
        { value: 0, content: 'さらなる説明が必要です'}
        
    ])
    const supportChoices = ref([
        { value: 3, content: 'メッセージでのサポートを希望する'},
        { value: 2, content: '個別の相談を希望する'}
        
    ])
    const selectedAnswer = ref(null)
    const selectedRadio = ref(null)
    const supportPopup = ref(false)
    const radioError = ref("")
    const saving = ref(false)
    const supportAccountId = ref(null);

    onBeforeRouteLeave(() => {
        getLessonPortfolios()
        getThemes()
    })

    const getLessonPortfolios = inject('getLessonPortfolios')
    const getThemes = inject('getThemes')
    onMounted(() => {
        if(route.meta.support_user_id){
            supportAccountId.value = route.meta.support_user_id;
        }
    })


    const createChatSupport = async () => {
        try {
            // check if there board between user and support account
            const chat = await axios.get(`/start_private_board?with=${supportAccountId.value}&nodirect=1`);
            const supportType = supportChoices.value.find(ob => ob.value == selectedRadio.value)?.content
            const params = {
                message: `<a href=/app/public/user?id=${supportAccountId.value}>@研修サポート</a>
研修テーマ :【${props.selectedTopic.title}】
セクション :【${props.material.title}】
サポート希望 : ${supportType}

上記の通り、サポート依頼を受付完了しました。
※このメッセージは自動生成されたメッセージです。`,
                record_id: chat.data,
                mentioned_users: [supportAccountId.value],
                override_user_id: supportAccountId.value,
                u_id: '',
                emoji_flag: false
            };      
            // create default message 
            await axios.post('/chat_add_api', params)               
            return chat.data
        } catch (error) {
            if (error.response) errorToast('エラーが発生しました。 ' + error.response.data.message)
            else if (error.request) errorToast('エラーが発生しました。')
            else errorToast('エラーが発生しました。 ' + error.message)    
        }
    }
    const successAndSave = async(status) => {
        const valid = await moreDetailContent.value.$refs.recordBody.validate()
        if(!valid.valid) return
        saving.value = true
        const content = comment.value
        await props.sectionUpdate(status, content)

        const data = {
            text: '保存しました。',
            channel: Math.random().toString(36).substring(5),
            icon: 0,
            view: true
        }
        emitter.emit('setInfo', data)
        saving.value = false
    }
    const failProcessing = ref(false)
    const fail = async (status) => {
        if(!supportAccountId.value){
            errorToast('エラーが発生しました。')
            return
        }
        failProcessing.value = true    
            
        await props.sectionUpdate('save', '')   
        const chatId = await createChatSupport()     

        failProcessing.value = false
        var uniqueChannell = Math.random().toString(36).substring(5);   
        emitter.emit('setToast', {
            active: true,  
            type: 'info', 
            content: `サポート用の<a href="/board/${chatId}" target="_blank">ボード</a>が作成されました。<br>人事部門からの連絡をお待ちください。` ,
            closeButton: false, 
            autoClose: false,
            touchClose: false,
            answers: ['OK'],
            channel: uniqueChannell

        })            
        emitter.on(uniqueChannell, () => { router.push({name: 'basic'}) });
        return     
    }
    const successAndNext = async() => {
        const valid = await moreDetailContent.value.$refs.recordBody.validate()
        if(!valid.valid) return
        await successAndSave('next')
        router.push({name: 'basic'})        
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