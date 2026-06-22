<template>    
    <Modal @close="emit('close', false)">
        <template #title>
            <p>{{ editTarget ? `規則を編集する` : `規則を作成する`}}</p>
        </template>
        <template #content>
            <div class="si-box">
                <ShortInput
                    ref="regulationTitle"
                    placeHolder="タイトルを入力（必須）"
                    name="regulationTitle"
                    rules="required"
                    label="タイトル"
                    v-model="params.title"
                />
            </div>
            <div class="si-box">                   
                <LongInput
                    ref="regulationContent"
                    :placeHolder="`内容`"
                    name="regulationContent"
                    rules="required"
                    label="内容"
                    v-model="params.content!"
                />                    
            </div>
            <div class="si-box relative">
                <div v-if="params.regulation_files && params.regulation_files.length" class="mb-[20px]">
                    <RegulationFiles 
                        @remove="removeFile" 
                        @update="updateFile"
                        :files="params.regulation_files" 
                        mode="edit"
                    />
                </div>
                <div class="formFileUploadArea" @click="formUploader?.click()" >
                    <div class="form-plc" style="z-index: 6;">
                        <label for="file" class="file-label">
                            <span>ファイル</span>
                        </label>
                        <input type="file" ref="formUploader" name="file" id="file" @change="handleFileUpload" style="display: none;">
                    </div> 
                </div>
                <div class="uploadMask" v-if="uploadingProgress"><div>アップロード中</div><div> {{uploadingProgress }}%</div></div>
            </div>

            <div class="si-box">
                <LoaderButton @triggered="checkConfirm" :loading="processing" content="保存する"/>
            </div> 
        </template>
    </Modal>
</template>

<script setup lang="ts">
import ShortInput from '@/components/Form/ShortInput.vue';
import LongInput from '@/components/Form/LongInput.vue';
import LoaderButton from '@/components/Global/LoaderButton.vue';
import { ref, reactive, useTemplateRef } from 'vue';
import { useApi } from '@/composables/api';
import { Regulation } from '@/interface/regulationInterface';
import Modal from '@/components/Global/Modal.vue';
import RegulationFiles from './RegulationFiles.vue';

interface Props {
    editTarget?: Regulation | null;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    close: [refreshNeeded: boolean];
}>();
const uploadingProgress = ref(0);
const formUploader = useTemplateRef('formUploader');
const params = reactive<Partial<Regulation>>({
    id: props.editTarget?.id ?? undefined,
    title: props.editTarget?.title ?? '',
    content: props.editTarget?.content ?? '',
    regulation_files: props.editTarget?.regulation_files ?? [],
});

const processing = ref(false);
const regulationTitle = ref<InstanceType<typeof ShortInput> | null>(null);
const regulationContent = ref<InstanceType<typeof LongInput> | null>(null);
const api = useApi();

const validation = async (): Promise<boolean> => {                 
    try {                    
        let result = true;
        const checkRef = [regulationTitle.value, regulationContent.value];
        
        for (const check of checkRef) {
            if (check) {
                const exec = await check.validate();
                result = result && (exec?.valid ?? false);
            }
        }            
        
        return result;
    } catch (error) {
        console.error('Error during validation:', error);
        return false;
    }               
};

const checkConfirm = async (): Promise<void> => {
    processing.value = true;
    
    try {
        const valid = await validation();
        if (!valid) {
            processing.value = false;
            return;
        }

        const submitParams = {
            ...params,
        };

        await api.post('/regulation_add_record', submitParams, {
            toast: props.editTarget ? '編集しました。' : '作成しました。',
            loadingRef: processing,
        });
        
        emit('close', true);
    } catch (error) {
        console.error('Error saving regulation:', error);
        processing.value = false;
    }
};

const handleFileUpload = async (event: Event): Promise<void> => {
    const target = event.target as HTMLInputElement;
    if (!target.files || target.files.length === 0 || processing.value) return;

    const file = target.files[0];
    const formData = new FormData();
    formData.append('file', file);

    processing.value = true;
    const uploadedFile = await api.post('/regulation_file_upload', formData, {
        toast: 'ファイルをアップロードしました。',
        loadingRef: processing,
    }, { onUploadProgress: (e) => uploadingProgress.value = Math.floor((e.loaded * 100) / (e.total || e.loaded || 1)) });

    params.regulation_files?.push(uploadedFile);

    processing.value = false;
    uploadingProgress.value = 0;
    target.value = '';

};

const removeFile = (id: number): void => {
    params.regulation_files = params.regulation_files?.filter(file => file.id !== id) || [];
};
const updateFile = (id: number, field: string, value: any): void => {
    const file = params.regulation_files?.find(file => file.id === id);
    if (file) {
        (file as any)[field] = value;
    }
};
</script>

