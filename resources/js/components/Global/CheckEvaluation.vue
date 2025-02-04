<template>
<Modal @close="router.back()">
    <template #title>
        <p>{{ `${memberData?.name} ~ ${date?.short_name}` }}</p>
    </template>
    <template #content>
        <EvaluationDetail :member-data="memberData" :date="date"/>
    </template>
</Modal>
</template>
<script setup lang="ts">
import { EvaluationRecord } from '@/interface/evaluationInterface';
import Modal from './Modal.vue';
import { useRoute, useRouter } from 'vue-router';
import { computed } from 'vue';
import { detailedDateOptions } from '@/utils/tools';
import EvaluationDetail from '../Project/PersonnelEvaluation/EvaluationDetail.vue';
const props = defineProps<{
    evaluations: EvaluationRecord[]
}>()
const route = useRoute()
const router = useRouter()
const memberData = computed(() => {
    const memberEvaluations = props.evaluations.find(evaluation => evaluation.user_id.toString() == route.params.memberId)
    return memberEvaluations && memberEvaluations.user ? memberEvaluations.user : null
})

const dateOptions = detailedDateOptions()
const date = computed(() => {
    const span = route.params.span as string
    if(span){
        const option = dateOptions.find(option => option.year + '-' + option.which_half == span)
        if(option){
            return option
        }
    }
    return null
})
</script>