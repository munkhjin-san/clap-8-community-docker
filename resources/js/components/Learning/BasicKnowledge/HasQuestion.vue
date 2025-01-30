<template>
    <div style="position: relative;">
        <LongInput 
            v-if="(material.answer && material?.answer?.status < 2) || !material.answer" 
            :initialValue="material?.answer?.answer ? material?.answer?.answer : answer" 
            :placeHolder="`課題への回答`"
            ref="answerComment"
            rules="required"
            name="recordBody"
            label="タイトル"
            v-model="answer"
        />
        <p v-else><strong>課題への回答<br></strong>{{ material?.answer?.answer }}</p>
        <OpenAiReview 
            :assistand-id="material?.assistant_id" 
            :soure-text="material?.answer?.ai_review" 
            :message="answer"
            :confirm-text="'業務リスク管理の基礎を効果的に理解し、実務で活用できる視点を身につけている。'"
            :answer="true"
            ref="reviewEl"
        />
        <div v-if="(material && (!material.answer || material.answer.status < 2))" style="display:flex; justify-content: center; gap:20px;flex-wrap: wrap;margin-top: 25px;">
            <div>
                <LoaderButton @triggered="finish(1)" :loading="processing[1]" :content="'一時保存'"/>
            </div>
            <div>
                <LoaderButton @triggered="finish(2)" :loading="processing[2]" :content="'完了'"/>
            </div>
        </div>
        
    </div>
</template>
<script lang="ts" setup>
import OpenAiReview from '@/components/Global/OpenAiReview.vue';
import LongInput from '@/components/Form/LongInput.vue';
import { inject, onMounted, ref, useTemplateRef } from 'vue';
import { Dialog } from '@/interface/globalInterface';
import axios from 'axios';
import { useRouter } from 'vue-router';
import LoaderButton from '@/components/Global/LoaderButton.vue';
const props = defineProps(['material'])
const answer = ref(props.material?.answer?.answer || '')
const processing = ref(['', false, false])
const reviewEl = useTemplateRef('reviewEl')
const { notify, info } = inject<Dialog>('dialog')!
const answerComment = useTemplateRef('answerComment')
const router = useRouter()
const getLessons = inject('getLessons') as Function
const providedMaterial = inject('providedMaterial')
onMounted(() => {
    console.log('yay',providedMaterial)
    if (props.material.answer) {
        answer.value = props.material.answer.answer
    }
})
const finish = async(status: number) => {
    if (status === 2) {
        if(props.material.assistant_id && !reviewEl.value?.reviewResultRaw){
            notify('AI分析を必須として実施し、完了してください。')
            return
        }
        const aiVal = await reviewEl.value?.validate()
        if (props.material.has_question) {
            const val = await answerComment.value?.validate() || {valid: false}
            if (!val.valid) return
        }
        
        if((props.material.assistant_id && !aiVal)){
            return
        }
    }        

    processing.value[status] = true
    const materialId = props.material?.id
    const answerId = props.material?.answer?.id
    const params = {     
        id: answerId,           
        params: {
            status: status,
            answer: answer.value,
            ai_review: reviewEl.value?.reviewResultRaw,
            material_id: materialId
        }
    }
    try {
        axios.post('/update_lesson_answer', params)
        info('保存しました。')
        processing.value[status] = false
        getLessons()
        
    } catch (e) {
        notify(e)
    } finally {
        if(status === 2){
            router.push({name : 'basic'})
        }
    }
}
</script>