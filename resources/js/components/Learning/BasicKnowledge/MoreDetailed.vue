<template>
    <div>
        <div v-if="$route.name == 'more'" style="background: var(--background-color);padding: 30px;word-wrap: break-word;white-space: break-spaces;line-height: 1.8;display: flex;flex-direction: column;gap: 30px;margin: 0 20px;">
            <!-- <div class="lessons-topic" v-for="topic in lessons">
                <div>
                    <div style="padding: 20px;border: solid thin var(--calendarBorder);">
                        <div @click="toggleView(topic.id)" style="cursor: pointer;">
                            <span style="margin-right: 15px;"> 
                                <svg version="1.1" width="13" height="13" :style="{transform: `rotate(${viewId.includes(topic.id) ? 270 : 180}deg)`, transition: 'transform 0.2s ease'}" viewBox="0 0 20 32" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M0.775 17.789c1.305 1.166 2.612 2.332 3.927 3.486 1.311 1.161 2.634 2.308 3.953 3.46 1.316 1.156 2.646 2.296 3.973 3.439 1.33 1.139 2.667 2.273 4.015 3.394 0.662 0.551 1.647 0.52 2.272-0.107 0.65-0.654 0.619-1.725-0.020-2.393-1.198-1.253-2.407-2.495-3.621-3.729-1.232-1.245-2.462-2.492-3.704-3.725-0.902-0.9-1.803-1.802-2.707-2.699-0.033-0.032-0.055-0.069-0.072-0.106-0.045-0.036-0.082-0.080-0.111-0.129-0.069-0.047-0.129-0.117-0.176-0.216-0.021-0.047-0.044-0.092-0.066-0.136-0.12-0.062-0.214-0.168-0.246-0.325-0.001-0.005-0.002-0.009-0.003-0.014-0.104-0.157-0.187-0.327-0.254-0.505-0.109-0.185-0.182-0.388-0.226-0.601-0.002-0.012-0.005-0.024-0.007-0.036-0.016-0.085-0.028-0.172-0.036-0.259-0.195-0.593-0.26-1.183 0.030-1.653 0.006-0.157 0.067-0.277 0.157-0.361 0.019-0.050 0.039-0.099 0.063-0.149 0.040-0.084 0.1-0.145 0.17-0.188 0.008-0.015 0.019-0.028 0.028-0.042 0.032-0.13 0.106-0.228 0.202-0.293 0.072-0.145 0.157-0.287 0.26-0.43 0.046-0.063 0.101-0.113 0.163-0.151 0.018-0.020 0.037-0.038 0.059-0.054 0.014-0.059 0.044-0.116 0.094-0.165 0.9-0.888 1.797-1.782 2.699-2.672 1.244-1.231 2.476-2.475 3.714-3.717l1.843-1.871 1.832-1.885c0.655-0.681 0.669-1.793-0.044-2.48-0.652-0.631-1.693-0.624-2.385-0.038l-1.964 1.66-1.995 1.71c-1.32 1.149-2.648 2.293-3.962 3.45s-2.636 2.308-3.943 3.474c-1.311 1.159-3.284 2.806-4.106 3.689s-0.792 2.492 0.191 3.369z"></path>
                                </svg>
                            </span>
                            <span>{{ topic.title }}</span>
                        </div>
                        
                        <div v-if="viewId.includes(topic.id)" style="margin-top: 15px;">
                            <div v-html="topic.content_detailed"></div>
                        </div>
                    </div>                    
                </div>
            </div> -->
            <div class="flex flex-col gap-30">
                <div>
                    <div class="topic-title">
                        <p>研修プログラムについて</p>
                    </div>
                    <div class="flex flex-col gap-30">
                        <p>この研修プログラムは、社会活動で必要とされる9つの職能を学ぶことを目的としています。<br>以下に、プログラムの流れについて詳しく説明します。</p>
                        <p>1.CLAPラーニングアプリによる基礎学習：<br>
                        -参加者はまず、CLAPのラーニングアプリを使用して基礎学習を行います。<br>
                        -この段階では、9つの職能に関連する基本的な知識やスキルを学びます。
                        </p>
                        <p>2.サポートとフォローアップ：<br>
                        -もし基礎学習の内容が理解しにくい場合、補足資料を参照したり、フォローアップ面談を通じてサポートを受けることができます。<br>
                        -これにより、参加者は自分のペースで学習を進めることができます。
                        </p>
                        <p>3.ポートフォリオの作成：<br>
                        -基礎学習を理解した参加者は、グループディスカッションの準備のためにポートフォリオを作成します。<br>
                        -ポートフォリオには、学んだことの要約や、自身の考えや経験を反映させます。
                        </p>
                        <p>4.グループディスカッションとフィードバック：<br>
                        -ディスカッションでは、ポートフォリオを発表し、他の参加者や指導者からのポジティブ（肯定的）およびネガティブ（否定的）なフィードバックを受けます。<br>
                        -このフィードバックをもとに、ポートフォリオをさらに磨き上げます。
                        </p>
                        <p>5.ポートフォリオの公開と履修完了：<br>
                        -完成したポートフォリオは、各参加者のマイページのプロフィール欄に自動的に掲載されます。<br>
                        -基礎学習を完了し、ポートフォリオが完成した参加者は、1つのテーマの履修が完了したとみなされます。
                        </p>
                        <p>このプログラムは、職能を体系的に学び、実践的なスキルを身につけることを目指しています。<br>参加者は自己の学びを深め、自己表現のスキルを磨くことができます。</p>
                    </div>
                </div>
                <div>
                    <div class="topic-title">
                        <p>ポートフォリオとは</p>
                    </div>
                    <div class="flex flex-col gap-30">
                        <p>ポートフォリオとは、自身の学びや経験を体系的にまとめた文書です。<br>これは、ご自身の能力と成長を記録し、可視化するための重要なツールです。ポートフォリオの作成には以下のステップが含まれます。</p>
                        <p>1.学習内容の整理：まず、研修プログラムや業務で学んだ知識やスキルを整理します。<br>どのような新しいことを学んだのか、どのような理解を深めたのかを明記します。</p>
                        <p>2.過去の経験の反映：以前に経験したプロジェクトや業務での成功や挑戦、そこから得た教訓を記載します。<br>これにより、ご自身の経験がどのように現在の職務に役立っているかを示すことができます。</p>
                        <p>3.個人の特性と主張：自己分析を通じて自覚した自身の強みや特性、個人的な見解や考え方を明確にします。<br>これは、個人としてのユニークな価値や視点を表現するために重要です。</p>
                        <p>4.フィードバックの統合：研修中や業務中に受けた指導や助言をポートフォリオに取り入れ、自己改善の過程を示します。</p>
                        <p>ポートフォリオを作成することは、自己の成長を振り返り、今後のキャリアパスを考える上で非常に有効です。<br>また、自分自身の能力や業績を上司や同僚に伝える際にも役立ちます。<br>このプロセスを通じて、皆様は自己の能力をより深く理解し、自信を持って業務に取り組むことができるようになるでしょう。</p>
                    </div>
                </div>
            </div>
            <div>
                <p><strong>研修内容を理解しましたか</strong></p>
                <div v-for="answer in list" style="display: flex;align-items: center;padding: 5px 0;">
                    <input class="fish-eye" v-model="selectedAnswer" type="radio" :id="answer.value" name="answer" :value="answer.value">
                    <label style="margin-left:10px;cursor:pointer" :for="answer.value">{{answer.content}}</label>  
                </div>
                <span class="form-error" style="font-size: 11px;color:tomato">{{ selectedAnswer != null ? '' : radioError }}</span>
            </div>
            <div v-if="popup" class="overlay">
                <div class="chatCreate" @mousedown.stop style="overflow: hidden auto;">     
                    <div class="recordFormTitle" style="display:flex">
                        <div class="m-close-button" @click="popup = false" style="position:unset; margin:auto 0 auto auto">
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
                        <div v-for="answer in list1" style="display: flex;align-items: center;padding: 5px 0;">
                            <input class="fish-eye" v-model="selectedRadio" type="radio" :id="answer.value" name="nextanswer" :value="answer.value">
                            <label style="margin-left:10px;cursor:pointer" :for="answer.value">{{answer.content}}</label>  
                        </div>
                        <span class="form-error" style="font-size: 11px;color:tomato">{{ selectedRadio != null ? '' : radioError }}</span>
                    </div>
                
        
                            
                            
                    <div class="si-box">
                        <LoaderButton @triggered="fail" :loading="failProcessing" :content="'送信する'"/>
                    </div>               
                
                </div>
                
            </div>
            
            <div v-if="selectedAnswer == 1" class="si-box" style="margin:0">
                <p style="margin-bottom: 20px;"><strong>研修内容から理解したものやハイライトしたい部分を入力してください。</strong></p>
                <FormLongText
                    :initialValue="comment"   
                    :placeHolder="`基礎知識の内容`"
                    ref="lessonBody"
                    rules="required|max:2000"
                    uId="recordBody"
                    name="recordBody"
                    label="タイトル"
                    @setValue="val => comment = val"
                />
            </div>
            
            <div style="display:flex; justify-content: center; gap:20px;flex-wrap: wrap;">
                <div v-if="selectedAnswer == 1">
                    <LoaderButton @triggered="success('save')" :loading="processing_save" :content="'保存する'"/>
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
import { ref,inject, onMounted } from 'vue';
import axios from 'axios';
import { useStore } from 'vuex';
    const route = useRoute()
    const store = useStore()
    const props = defineProps(['lessons', 'topicId', 'selectedTopic', 'portfolioId', 'not_content'])
    const uncomment = ref(props.not_content || '')
    const comment = ref("")
    const router = useRouter()
    const processing = ref(false)
    const lessonBody = ref(null)
    const theme = inject('getThemes')
    const list = ref([
               { value: 1, content: '理解しました'},
               { value: 0, content: 'さらなる説明が必要です'}
               
            ])
    const list1 = ref([
        { value: 3, content: 'メッセージでのサポートを希望する'},
        { value: 2, content: '個別の相談を希望する'}
        
    ])
    const selectedAnswer = ref(null)
    const selectedRadio = ref(null)
    const popup = ref(false)
    const radioError = ref("")
    const viewId = ref([])
    const processing_save = ref(false)
    const lesson_portfolio = inject('getLessonPortfolios')
    const supportAccountId = ref(null);
    onMounted(() => {
        if(route.meta.data){
            supportAccountId.value = route.meta.data;
        }
    })
    const toggleView = (id) => {
        if(viewId.value.includes(id)){
            const index = viewId.value.indexOf(id)
            viewId.value.splice(index, 1)
        }else{
            viewId.value.push(id)
        }
    }

    const createChatSupport = async () => {
        try {
            // check if there board between user and support account
            const chat = await axios.get(`/start_private_board?with=${supportAccountId.value}&nodirect=1`);
            const supportType = list1.value.find(ob => ob.value == selectedRadio.value)?.content
            const params = {
                message: `<a href=/app/public/user?id=${supportAccountId.value}>@研修サポート</a>\n研修テーマ :【${props.selectedTopic.title}】\nサポート希望 : ${supportType}\n※このメッセージは自動生成されたメッセージです。`,
                record_id: chat.data,
                mentioned_users: [supportAccountId.value],
                user_id: store.state.user.id,
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
    const savePortfolio = async () => {
        try {
            const params = {
                board_create: selectedRadio.value,
                basic_knowledge: comment.value,
                understand: selectedAnswer.value,
                status: 0,
                topic_id: props.topicId,
                title: props.selectedTopic.title,
                portfolio_id: props.portfolioId ? props.portfolioId : null,
            }
            axios.post('/save_lesson_portfolio', params).then(response => {
                theme()
                return true
            }).catch(function (error) {
                if (error.response) errorToast('エラーが発生しました。 ' + error.response.data.message)
                else if (error.request) errorToast('エラーが発生しました。')
                else errorToast('エラーが発生しました。 ' + error.message)                       
            });
        } catch (error) {
            if (error.response) errorToast('エラーが発生しました。 ' + error.response.data.message)
            else if (error.request) errorToast('エラーが発生しました。')
            else errorToast('エラーが発生しました。 ' + error.message)    
        }
    }
    const success = async(status) => {
        if(status == 'next'){
            processing.value = true
        }else{
            processing_save.value = true
        }
        const save = await savePortfolio()
        if(status == 'next'){            
            if(selectedAnswer.value == 1){
                router.push({name: 'portfoliodraft'})
            }else{
                router.push({name: 'learning'})                
            }
            
        }else{
            const data = {
                text: props.editTarget ? '編集しました。' :'保存しました。',
                channel: Math.random().toString(36).substring(5),
                icon: 0,
                view: true
            }
            emitter.emit('setInfo', data)            
        }
        lesson_portfolio()
        processing.value = false
        processing_save.value = false
    }
    const failProcessing = ref(false)
    const fail = async (status) => {
        if(!supportAccountId.value){
            errorToast('エラーが発生しました。')
            return
        }
        failProcessing.value = true        
        await savePortfolio()   
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
        emitter.on(uniqueChannell, () => { router.push({name: 'learning'}) });
        return     
    }
    const nextStage = () => {
        if(selectedAnswer.value == 1){
            success('next')
        }else if(selectedAnswer.value == 0){
            popup.value = true
            radioError.value = ''
        }else{
            radioError.value="必須です"
            return
        }
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