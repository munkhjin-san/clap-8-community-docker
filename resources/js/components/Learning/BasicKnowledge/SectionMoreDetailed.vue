<template>
    <div style="background: inherit;">
        <div v-if="isSupportRoute" style="background: inherit;">
            <Modal @close="closeSupportModal">
                <template #title>
                    <p>研修内容に関するサポート案内</p>
                </template>
                <template #content>
                    <div>
                        <div class="flex flex-col gap-[30px]">
                            <p>私たちの研修プログラムでは、皆さんがスムーズに学習を進められるよう、以下の2つのサポート方法を用意しています。</p>
                            <p>1.メッセージでのサポート：研修の内容について疑問や質問があれば、いつでもメッセージを送ってください。<br>チームが迅速に回答し、必要な情報を提供します。</p>
                            <p>2.個別の相談：より詳しい説明や個別のガイダンスが必要な場合、個人的な相談の時間を設定して対応します。<br>この時間を利用して、研修内容の深い理解や具体的なアドバイスを受けることができます。</p>
                            <p>ご希望のサポート方法を選択して、私たちにお知らせください。<br>分からないことがあれば、遠慮なくお問い合わせください。</p>
                            <p><strong>どのサポートを希望しますか？</strong></p>
                        </div>
                    </div>      
                    <div>
                        <div v-for="answer in supportChoices" style="display: flex;align-items: center;padding: 5px 0;">
                            <input class="fish-eye" v-model="selectedRadio" type="radio" :id="String(answer.value)" name="nextanswer" :value="answer.value">
                            <label style="margin-left:10px;cursor:pointer" :for="String(answer.value)">{{answer.content}}</label>
                        </div>
                        <span class="form-error" style="font-size: 11px;color:tomato">{{ selectedRadio != null ? '' : radioError }}</span>
                    </div>
                    <div class="si-box">                       
                        <LongInput
                            :initialValue="notUnderstandContent"   
                            :placeHolder="`理解できなかった理由`"
                            ref="moreDetailContent"
                            rules="required"
                            name="recordBody"
                            label="タイトル"
                            v-model="notUnderstandContent"
                        />
                    </div>
        
                            
                            
                    <div class="si-box">
                        <LoaderButton @triggered="fail" :loading="failProcessing" :content="'保存する'"/>
                </div>
            </template>
        </Modal>
            <LearningContentRenderer
                v-if="!isPersonalMaterialSupport && filteredContentSource"
                :content="filteredContentSource"
            />
            
            <div v-if="!isPersonalMaterialSupport && selectedAnswer == 1" class="si-box" style="margin:0">
                <p style="margin-bottom: 20px;"><strong>研修内容から理解したものやハイライトしたい部分を入力してください。</strong></p>
                <LongInput
                    :initialValue="comment"   
                    :placeHolder="`知識研修の内容`"
                    ref="moreDetailContent"
                    rules="required"
                    name="recordBody"
                    label="タイトル"
                    v-model="comment"
                />
            </div>
            
            <div v-if="!isPersonalMaterialSupport && selectedAnswer !== null" style="display:flex; justify-content: center; gap:20px;flex-wrap: wrap;">
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
<script setup lang="ts">
import { onBeforeRouteLeave, useRoute, useRouter } from 'vue-router';
import LongInput from '../../Form/LongInput.vue';
import LoaderButton from '../../Global/LoaderButton.vue';
import { ref, inject, onMounted, computed } from 'vue';
import { useAuthUserStore } from '@/store/auth'
import { useApi } from '@/composables/api';
import { useLearningApi } from '@/composables/learningApi';
import { useDialog } from '@/composables/dialog';
import LearningContentRenderer from '@/components/Learning/shared/LearningContentRenderer.vue';
import type { LearningMaterial, LearningTheme } from '@/types/learning';
import Modal from '@/components/Global/Modal.vue';
type SectionUpdate = (status: 'save' | 'next', content: string) => Promise<unknown>
interface SupportChoice {
    value: number
    content: string
}
    const auth = useAuthUserStore()
    const route = useRoute()
    const props = defineProps<{
        material?: LearningMaterial
        lessonThemeId?: number | string
        selectedTopic: LearningTheme
        portfolioId?: number | null
        sectionUpdate?: SectionUpdate
        sectionStatus?: number | null
    }>()
    const comment = ref("")
    const notUnderstandContent = ref("")
    const router = useRouter()
    const processing = ref(false)
    const moreDetailContent = ref<any>(null)
    const supportChoices = ref<SupportChoice[]>([
        { value: 3, content: 'メッセージでのサポートを希望する'},
        { value: 2, content: '個別の相談を希望する'}
        
    ])
    const selectedAnswer = ref<number | null>(null)
    const selectedRadio = ref<number | null>(null)
    const supportPopup = ref(true)
    const radioError = ref("")
    const saving = ref(false)
    const supportAccountId = ref<number | string | null>(null);
    const api = useApi()
    const learningApi = useLearningApi()
    const { ask, ping, toast } = useDialog()
    const getLessonPortfolios = inject<() => void | Promise<void>>('getLessonPortfolios')
    const getThemes = inject<() => void | Promise<void>>('getThemes')
    const isSupportRoute = computed(() => route.name === 'more' || route.name === 'personal_material_more')
    const isPersonalMaterialSupport = computed(() => route.name === 'personal_material_more')
    const supportSectionTitle = computed(() => {
        return isPersonalMaterialSupport.value ? '個人専用研修資料' : props.material?.title ?? ''
    })
    const closeSupportModal = () => {
        router.push({ name: isPersonalMaterialSupport.value ? 'basic' : 'material' })
    }
    onBeforeRouteLeave(() => {
        getLessonPortfolios?.()
        getThemes?.()
    })
    onMounted(() => {
        loadSupportAccount()
    })
    const loadSupportAccount = async() => {
        supportAccountId.value = await learningApi.getSupportAccountId()
    }
    const filteredContentSource = computed(() => props.material?.content_detailed ?? '')

    const createChatSupport = async () => {

            // check if there board between user and support account
            const chat = await api.get(`/start_private_board?with=${supportAccountId.value}&nodirect=1`);
            const supportType = supportChoices.value.find(ob => ob.value == selectedRadio.value)?.content
            const guide = selectedRadio.value == 3 ? `研修サポート担当より、理解できなかった点に関するフォローアップメッセージをお送りします。
内容によっては、返信に時間がかかることがございますので、予めご了承ください` : `職能研修機関の研修講師とオンラインビデオ面談の日程調整を進めます。
研修サポート担当より、日程調整に関するメッセージをお送りいたします。
日程調整には時間がかかることがございますので、予めご了承ください。
`
            const params = {
                message: `[To:研修サポート:]
[To:${auth.name}:]
※このメッセージは自動生成されたメッセージです。
下記の通りサポート依頼を受付しました。

研修テーマ :【${props.selectedTopic.title}】
セクション :【${supportSectionTitle.value}】
サポート希望 : ${supportType}
理解できなかった理由 : ${notUnderstandContent.value}

${guide}
`,
                record_id: chat,
                override_user_id: supportAccountId.value,
                u_id: '',
                emoji_flag: false
            };      
            // create default message 
            await api.post('/chat_add_api', params)               
            return chat

    }
    const successAndSave = async(status: 'save' | 'next' = 'save') => {
        const valid = await moreDetailContent.value.validate()
        if(!valid.valid) return
        saving.value = true
        const content = comment.value
        await props.sectionUpdate?.(status, content)
        toast('保存しました。')
        saving.value = false
    }
    const failProcessing = ref(false)
    const fail = async () => {
        if(!supportAccountId.value){
            ping('エラーが発生しました。')
            return
        }
        if (selectedRadio.value === null) {
            radioError.value = '必須です'
            return
        }

        const valid = await moreDetailContent.value.validate()
        if(!valid.valid) return

        radioError.value = ''
        failProcessing.value = true    

        if (isPersonalMaterialSupport.value) {
            await learningApi.savePersonalMaterialFeedback(props.selectedTopic.id, {
                understand: false,
                important_point: null,
            })
        } else {
            await props.sectionUpdate?.('save', '')
        }

        const chatId = await createChatSupport()     

        failProcessing.value = false
        const options = {
            answers: [{label: 'OK', value:true}]
        }
        const answer = await ask(`サポート用の<a href="/board/${chatId}" target="_blank">チャット</a>が作成されました。<br>研修サポート担当からからの連絡をお待ちください。`, options)
        if(answer.value){
            router.push({name: 'basic'})  

        }
        return     
    }
    const successAndNext = async() => {
        const valid = await moreDetailContent.value.validate()
        if(!valid.valid) return
        await successAndSave('next')
        router.push({name: 'basic'})        
    }
</script>
