<template>
    <BaseLayout
        :title="'【管理者】人事考課'" 
        :count="0"
        :fullscreen="fullscreen" 
        :type="data.type" 
        :can-resize="data.canResize"
        :can-fullscreen="data.canFullscreen"
        @toggle="(el, title) =>emit('toggle', el, data.type)" 
        @resize="emit('resize', data.type)"
    >
        <div>
            <div v-if="data.data.pendingEvaluations.length == 0" class="p-5 text-center">
                対象の人事評価はありません
            </div>
            <div v-else class="p-3 flex flex-col gap-2">  
                <p class="text-sm mb-3">人事承認待ち{{ data.data.pendingEvaluations.length }}件</p>          
                <div v-for="(evaluation, index) in data.data.pendingEvaluations" :key="index">
                    <div class="flex items-center gap-3 mb-2">
                        <UserPanel :user="evaluation.user" size="30" with-name disable-instant>
                            <template #details>
                                <div class="ml-3 mt-1 text-[11px] text-[gray]">メンター：{{ evaluation?.mentor?.name }}</div>
                            </template>
                        </UserPanel>
                        <CommandButton
                            :buttons="[
                                {title: '対応', action: () => setDetail(evaluation)}
                            ]"
                        />
                    </div>
                </div>
            </div>

        </div>
        <Modal v-if="detailedData" @close="detailedData = null">
             <template #title>
                <p>{{ `${detailedData?.memberData?.name} ~ ${detailedData?.date?.short_name}` }}</p>
            </template>
            <template #content>
                <EvaluationDetail 
                    :member-data-remind="detailedData?.memberData" 
                    :date="detailedData?.date" 
                    @reload="{emit('refreshData', data.type); detailedData = null}"
                />
            </template> 
        </Modal>
    </BaseLayout>
</template>
<script setup lang="ts">
import CommandButton from '@/components/Global/CommandButton.vue';
import BaseLayout from '../BaseLayout.vue';
import UserPanel from '@/components/Global/UserPanel.vue';
import { EvaluationRecord } from '@/interface/evaluationInterface';
import { ref } from 'vue';
import { User } from '@/interface/globalInterface';
import { detailedDateOptions } from '@/utils/tools';
import Modal from '@/components/Global/Modal.vue';
import EvaluationDetail from '@/components/Project/PersonnelEvaluation/EvaluationDetail.vue';

const props = defineProps<{
    data: {
        title: string,
        data: {
            pendingEvaluations: EvaluationRecord[]
        }
        order?: number,
        type: string
        canResize?: boolean
        canFullscreen?: boolean
    }
    fullscreen: boolean
}>()

const emit = defineEmits<{
    resize: [type: string]
    toggle: [el: HTMLElement | null, title: string]
    refreshData: [key: string]
}>()
const targetDates = detailedDateOptions()

const detailedData = ref<{
    evaluation: EvaluationRecord
    date: typeof targetDates[0]
    memberData: User
} | null>(null)

const setDetail = (evaluation: EvaluationRecord) => {
    const memberData = evaluation.user
    const dateOptions = detailedDateOptions()
    const date = dateOptions.find(option => option.year == evaluation.year && option.which_half == evaluation.which_half)
    if(memberData && date){
        detailedData.value = {
            evaluation,
            date,
            memberData
        }
    }
}

defineExpose({
    cardType: props.data.type,
})
</script>