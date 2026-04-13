<template>
<!-- プロジェクトマネジャー確認項目 -->
    <div v-if="assignData.questions?.length" class="pt-4 mt-4">
        <div class="post-separetor"><div>PM対応</div></div>
        <div class="flex justify-between flex-wrap">
            <h4 class="font-medium mb-3">確認項目</h4>
            <p class="p-3 bg-[var(--bg3)] text-[12px] text-[gray] w-fit mb-3">対応内容は本人に共有されます。</p>
        </div>

        <fieldset :disabled="assignData.status !== '作成中'" class="space-y-4 border-none p-0 m-0">
            <div v-for="(item, index) in assignData.questions" :key="item.id" class="p-3">
                <SurveyBlock
                    ref="blocks"
                    class="text-[14px]"
                    :block="item"
                    :answer="item.answers?.[0] ?? null"
                />
            </div>
        </fieldset>

        <div class="flex justify-center gap-5 flex-wrap mt-5">
            <LoaderButton v-if="assignData.status === '作成中'" style="margin:0" @triggered="applyToHR" :loading="savingAssignData" content="人事へ申請する" />
        </div>
    </div>
</template>
<script setup lang="ts">
import LoaderButton from '@/components/Global/LoaderButton.vue';
import SurveyBlock from '@/components/Survey/SurveyBlock.vue';
import { useApi } from '@/composables/api';
import { useDialog } from '@/composables/dialog';
import { ProjectAssignRecord } from '@/interface/projectInterface';
import { ref, useTemplateRef } from 'vue';
import type { ComponentExposed } from 'vue-component-type-helpers';

const props = defineProps<{
    assignData: ProjectAssignRecord;
}>();
const emit = defineEmits<{
    update: []
}>();

const api = useApi()
const { ping } = useDialog()
const savingAssignData = ref(false);
const blocks = useTemplateRef<ComponentExposed<typeof SurveyBlock>[]>('blocks')

const applyToHR = async () => {
    if (!props.assignData) return;

    if (props.assignData.status === '作成中') {
        const targets = blocks.value?.filter(Boolean) ?? []
        let valid = true
        for (const block of targets) {
            valid = block.isValid() && valid
        }
        if (!valid) {
            ping('すべての確認項目に回答してください。')
            return
        }
    }

    savingAssignData.value = true;
    const targets = blocks.value?.filter(Boolean) ?? []
    const blockAnswers = targets.map(b => b.extractedData)

    const res = await api.post('/apply_assign_data_to_hr', {
        assign_record_id: props.assignData.id,
        block_answers: blockAnswers,
    }, {
        toast: '人事へ申請しました。'
    })
    if (res !== null) {
        emit('update');
    }
    savingAssignData.value = false;
};
</script>
<style>
fieldset:disabled input, fieldset:disabled select, fieldset:disabled textarea {
    background-color: var(--bg3);
    cursor: not-allowed !important;
    
}
</style>