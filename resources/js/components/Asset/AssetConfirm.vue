<template>
    <Modal @close="emit('close', false)">
        <template #title>物品確認</template>
        <template #content>
            <LongInput 
                name="confirmContent"
                place-holder="メモ"
                v-model="confirmContent"
            />
            <div class="si-box">
                <FileUploader 
                    path="/asset_confirm_files"
                    v-model="files"
                />
            </div>
            <div class="si-box">
                <LoaderButton 
                    title="提出" 
                    :loading="loading"
                    content="保存する"
                    @triggered="submitConfirm"
                />
            </div>            
        </template>
    </Modal>
</template>
<script setup lang="ts">
import { Asset } from '@/interface/assetInterface';
import Modal from '../Global/Modal.vue';
import LongInput from '../Form/LongInput.vue';
import { ref } from 'vue';
import FileUploader from '../Form/FileUploader.vue';
import LoaderButton from '../Global/LoaderButton.vue';
import { useApi } from '@/composables/api';
import { useDialog } from '@/composables/dialog';

const props = defineProps<{
    asset: Asset
}>()

const emit = defineEmits<{
    close: [flag: boolean]
}>()

const confirmContent = ref('')
const loading = ref(false)
const api = useApi()
const files = ref<File[]>([])
const { ask, ping, toast } = useDialog()

const submitConfirm = async () => {
    const confirmed = await ask('物品の確認完了しましたか？',)
    if (!confirmed) return
    await api.post('/confirm_asset', {
        asset_id: props.asset.id,
        content: confirmContent.value,
        file_list: files.value

    }, {
        loadingRef: loading,
    })

    toast('物品の確認を提出しました')
    emit('close', true)
}
</script>