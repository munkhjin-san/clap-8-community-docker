<template>
    <Modal @close="emit('close', false)" persist>
        <template #title>
            {{ editData ? '役割編集' : '役割作成' }}
        </template>
        <template #content>
            <div>
                <ShortInput
                    v-model="params.title"
                    :place-holder="'役割名'"
                    ref="title"
                    rules="required"
                />
            </div>
            <div class="si-box">
                <LongInput 
                    v-model="params.description"
                    :place-holder="'役割の説明（業務内容など）'"
                />
            </div>
            <!-- <div class="si-box">
                <ShortInput
                    v-model="params.member_limit"
                    :place-holder="'メンバー上限'"                    
                    type="number"
                />
            </div> -->
            <div class="si-box">
                <div class="mb-4">勤務条件</div>
                <div class="flex flex-wrap gap-4">
                    <template v-for="condition in workConditions" :key="condition" >
                        <label class="inline-flex items-center space-x-2 cursor-pointer">
                            <input 
                                type="checkbox" 
                                class="custom-f-checkbox"
                                :value="condition"
                                v-model="params.work_conditions"
                            />
                            <span class="text-[12px]">{{ condition }}</span>
                        </label>
                    </template>
                </div>
            </div>
            <div class="si-box">
                <LoaderButton :loading="loading" @triggered="save" content="保存"/>
            </div>
            
        </template>
    </Modal>
</template>
<script setup lang="ts">
import LongInput from '@/components/Form/LongInput.vue';
import ShortInput from '@/components/Form/ShortInput.vue';
import LoaderButton from '@/components/Global/LoaderButton.vue';
import Modal from '@/components/Global/Modal.vue';
import { useApi } from '@/composables/api';
import { useDialog } from '@/composables/dialog';
import { useProject } from '@/composables/project';
import { MemberRole } from '@/interface/projectInterface';
import { reactive, ref, useTemplateRef } from 'vue';
import 'styles/customForm.css'
const emit = defineEmits<{
    close: [flag:boolean]
    saved: [role: MemberRole]
}>();

const props = defineProps<{
    editData: MemberRole | null;
}>();

const api = useApi()
const { toast, ping } = useDialog()
const loading = ref(false);
const title = useTemplateRef('title');
const { selectedProject } = useProject()
const workConditions = [
    'フルタイム',
    'シフト制',
    '早朝・深夜勤務',
    '土日祝勤務',
    '時短勤務',
    '日帰りの出張',
    '宿泊を伴う出張',
    '車両通勤',
    '片道2時間以上の移動',
    'アウトバウンド（発信）テレマ',
    'インバウンド（受信）テレマ',
    'クレーム対応',
    '対面での接客',
    '訪問業務',
    '屋内作業業務',
    '屋外作業業務（設備・通信系）',
    '高所作業（設備・通信系）',
    '重量物の取り扱い（10kgほど）',
    '長時間（4時間以上）のPC作業',
    'マルチタスク（複数画面や複数処理）'

]
const params = reactive<Partial<MemberRole>>(props.editData ? { ...props.editData } : {
    project_record_id: selectedProject.value?.id,
    work_conditions: []
});

const save = async () => {
    const validateTargets = [title.value].filter(item => item !== null);
    for(const target of validateTargets){
        const res = await target.validate();
        if(!res?.valid){
            ping('入力内容を確認してください。');
            return;
        }
    }
    loading.value = true;
    const saved = await api.post('/project_create_member_role', {
        project_id: params.project_record_id,
        data: params
    }, {
        toast: '役割を保存しました。',
        loadingRef: loading
    })

    if (saved) {
        emit('saved', saved)
        emit('close', true)
    }

}
</script>