<template>
    <div class="section-wrapper">
        <div class="section-inner" v-if="selectedTopic && selectedTopic.active == 1"> 
            <p>研修はまだ完了しておりません。アンケートの回答にご協力をお願い致します。</p>
            <p style="font-size: 18px;"><strong>研修に関するアンケート</strong></p>
            <div style="height: 20px;"></div>         
            <QuestionRadio
                questionId="question1"
                :question=faq1
                :answers=answers1
                :errorMessage="errorMessage"
                :key="errorMessage"
                v-model="question1"
                @setValue = "val => question1 = val"
            />
            <div style="height: 20px;"></div>
            <QuestionRadio
                questionId="question2"
                :question=faq2
                :answers=answers2
                :errorMessage="errorMessage"
                :key="errorMessage"
                v-model="question2"
                @setValue = "val => question2 = val"
            />
            <div style="height: 20px;"></div>
            <QuestionRadio
                questionId="question3"
                :question=faq3
                :key="errorMessage"
                :errorMessage="errorMessage"
                :answers=answers3
                v-model="question3"
                @setValue = "val => question3 = val"
            />
            <div class="si-box">
                <p style="margin-bottom: 20px;"><strong>その他ご意見をお聞かせください。</strong></p>
                <LongInput 
                    :initialValue="content"   
                    :placeHolder="`ご意見`"
                    ref="portfolioBody"
                    rules="required"
                    name="recordBody"
                    label="タイトル"
                    v-model="content"
                />
            </div>
            
            <div class="si-box">
                <LoaderButton @triggered="saveConfirm" :loading="processing" :content="'研修完了'"/>
            </div>
        </div>
    </div>
</template>
<script setup>
    import { useRoute, useRouter } from 'vue-router';
    import LoaderButton from '../../Global/LoaderButton.vue';
    import QuestionRadio from './QuestionRadio.vue';
    import { ref, inject, onBeforeMount } from 'vue';
    import LongInput from '../../Form/LongInput.vue';
    const router = useRouter()
    const { confirm, notify } = inject('dialog')
    const route = useRoute()
    const props = defineProps(['selectedTopic', 'available'])
    const question1 = ref(null)
    const question2 = ref(null)
    const question3 = ref(null)
    const processing = ref(false)
    const answers1 = ref(['反映できると強く感じる', 'だいたいの内容を反映できると思う', '一部の内容は反映できると感じる', 'ほんの少し反映できるかもしれない', '残念ながら、反映できるとは感じない'])
    const answers2 = ref(['完全に理解し、能動的に参加した', 'ほとんど理解し、主に能動的に参加した', 'ある程度は理解し、能動的に参加した', 'あまり理解できなかったが、少しは能動的に参加した。', '理解できず、能動的に参加することもできなかった'])
    const answers3 = ref(['向上したと強く感じる', '大部分で向上したと感じる', '一部分で向上したと感じる', 'ほんの少し向上したかもしれない', '向上したとは感じない'])
    const faq1 = ref('今回の研修内容は、今後の業務や活動にどれくらい反映できると感じますか？')
    const faq2 = ref('今回の研修の目的を正しく理解し、能動的に参加することができていましたか？')
    const faq3 = ref('今回の研修を受けたことで、意識や態度、能力の向上に繋がったと感じますか？')
    const errorMessage = ref('')
    const portfolio = inject('portfolio')
    const content = ref('')
    onBeforeMount(() => {
        setTimeout(() => {
            if(portfolio?.status < 2){
                backToast()
            }
        }, 500)
    })
    const saveConfirm = async() => {        
        await saveForm()
        setTimeout(() => {                    
            processing.value = false
            router.push({name: 'finish'})
        }, 1000);         
    }
    const saveForm = async() => {
        if(question1.value != null && question2.value != null && question3.value != null){
            processing.value = true
            const params = {
                question1: faq1.value,
                question2: faq2.value,
                question3: faq3.value,
                answer1: answers1.value[question1.value],
                answer2: answers2.value[question2.value],
                answer3: answers3.value[question3.value],
                lesson_theme_id: route.params.lessonThemeId,
                status: 3,
                content: content.value ? content.value : ''
            }
            try{
                const response = await axios.post('/save_lesson_form', params)
                return response.status
            }
            catch(error){
                if (error.response) notify('エラーが発生しました。 ' + error.response.data.message)
                else if (error.request) notify('エラーが発生しました。')
                else notify('エラーが発生しました。 ' + error.message)     
            }
            
        }else{
            errorMessage.value = '必須です'
        }
    }
    const backToast = async() => {
        const options = {
            answers : [{label: '戻る', value: true}]
        }
        const answer = await confirm('グループディスカッションを完了してください。', options)
        if(answer){
            router.go(-1)
        }
    }
</script>