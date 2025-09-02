<template>
<Modal @close="emit('close', false)" persist>
    <template #title>
        <p>グラリンピックエントリー</p>
    </template>
    <template #content>
        <div>
            <ShortInput 
                type="number" 
                v-model="params.calories"
                place-holder="カロリー(kcal)"
                :rules="'required'"
                ref="caloryRef"
            />
        </div>
        <div class="si-box">
            <LongInput 
                place-holder="メッセージ"
                v-model="params.comment"
            />

        </div>
        <div class="si-box">
            <FileUploader
                v-model="params.files"
                path="/post_entry_files"
                custom-place-holder="消費カロリー画面をアップロード"
                accept="image/*"
            />
        </div>
        <div class="si-box">
            <FileUploader
                v-model="params.photos"
                path="/post_entry_photos"
                custom-place-holder="運動している写真（任意）"
                accept="image/*"
            />
        </div>
        <div class="si-box">
            <LoaderButton @triggered="save" content="保存する" :loading="loading"/>
        </div>
        
    </template>

</Modal>
</template>
<script setup lang="ts">
import { Post, PostEntry } from '@/interface/postInterface';
import Modal from '../Global/Modal.vue';
import LongInput from '../Form/LongInput.vue';
import { reactive, ref, useTemplateRef } from 'vue';
import ShortInput from '../Form/ShortInput.vue';
import FileUploader from '../Form/FileUploader.vue';
import LoaderButton from '../Global/LoaderButton.vue';
import { useDialog } from '@/composables/dialog';
import { useApi } from '@/composables/api';

const loading = ref(false);
const props = defineProps<{
    record: Post
    editData: PostEntry | null
}>()
const { ping } = useDialog()
const params = reactive({
    id: props.editData?.id || undefined,
    comment: props.editData?.comment || '',
    calories: props.editData?.calories || '',
    files: props.editData?.files || [],
    photos: props.editData?.photos || []
})
const emit = defineEmits<{
    'close' : [flag: boolean, id?: number | undefined]
}>()
const caloryRef = useTemplateRef('caloryRef');
const api = useApi()
const save = async() => {
    const validate = await caloryRef.value?.validate();
    if (!validate || !validate.valid) {
        ping('カロリーは必須項目です。');
        return;
    }
    const data = {
        ...params,
        file_ids: params.files.map(file => file.id),
        photo_ids: params.photos.map(photo => photo.id),
        record_id: props.record.id,
    }

    await api.post('/post_entries', data, {
        loadingRef: loading,
        toast: 'エントリを保存しました。',

    });
    emit('close', true, props.record.id);

}
</script>