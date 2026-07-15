<template>
    <div style="position: relative;">
        <LongInput
            v-if="isEditable"
            :initialValue="material?.answer?.answer ?? answer"
            :placeHolder="`課題への回答`"
            ref="answerComment"
            rules="required"
            name="recordBody"
            label="タイトル"
            v-model="answer"
        />
        <p v-else><strong>課題への回答<br></strong>{{ material?.answer?.answer }}</p>
        <OpenAiReview 
            v-if="materialAiConfig"
            :config-key="materialAiConfig.config_key"
            :lesson-theme-id="selectedTopic?.id"
            :source-text="material?.answer?.ai_review ?? undefined"
            :message="answer"
            :confirm-text="'業務リスク管理の基礎を効果的に理解し、実務で活用できる視点を身につけている。'"
            :answer="true"
            ref="reviewEl"
        />
        <div v-if="isEditable" class="flex justify-center gap-5 flex-wrap mt-6">
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
import { computed, inject, ref, useTemplateRef, watch } from 'vue';
import { useRouter } from 'vue-router';
import LoaderButton from '@/components/Global/LoaderButton.vue';
import { useLearningApi } from '@/composables/learningApi';
import { useDialog } from '@/composables/dialog';
import { LESSON_ANSWER_STATUS } from '@/config/learning';
import type { LearningMaterial, LearningTheme } from '@/types/learning';

const props = defineProps<{
    material: LearningMaterial
    selectedTopic?: LearningTheme | null
}>()
const answer = ref(props.material?.answer?.answer || '')
const processing = ref<Record<number, boolean>>({
    [LESSON_ANSWER_STATUS.DRAFT]: false,
    [LESSON_ANSWER_STATUS.COMPLETED]: false,
})
const reviewEl = useTemplateRef('reviewEl')
const answerComment = useTemplateRef('answerComment')
const router = useRouter()
const getLessons = inject<() => void | Promise<void>>('getLessons')
const learningApi = useLearningApi()
const { ping } = useDialog()
const isEditable = computed(() => !props.material.answer || Number(props.material.answer.status ?? 0) < LESSON_ANSWER_STATUS.COMPLETED)
const materialAiConfig = computed(() => {
    return props.selectedTopic?.ai_configs?.find(config => config.lesson_material_id === props.material.id) ?? null
})
watch(() => props.material.answer?.answer, (value) => {
    answer.value = value ?? ''
}, { immediate: true })
const finish = async(status: number) => {
    if (status === LESSON_ANSWER_STATUS.COMPLETED) {
        if(materialAiConfig.value && !reviewEl.value?.reviewResultRaw){
            ping('AI分析を必須として実施し、完了してください。')
            return
        }
        const aiVal = await reviewEl.value?.validate()
        if (props.material.has_question) {
            const val = await answerComment.value?.validate() || {valid: false}
            if (!val.valid) return
        }
        
        if((materialAiConfig.value && !aiVal)){
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

    await learningApi.saveAnswer(params, {
        toast: '保存しました。'
    })
    processing.value[status] = false
    await getLessons?.()
    

    if(status === LESSON_ANSWER_STATUS.COMPLETED){
        router.push({name : 'basic'})
    }

}
</script>
