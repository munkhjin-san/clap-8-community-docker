<template>
    <Modal size="medium" disable-scroll custom-class="community-settings-modal" @close="close">
        <template #title>
            <p>コミュニティを作成</p>
        </template>
        <template #content>
            <div class="community-icon-modal">
                <div class="community-settings-fields">
                    <div class="community-settings-preview">
                        <img v-if="iconPreview" :src="iconPreview" :alt="title" loading="lazy">
                        <span v-else>{{ initial }}</span>
                    </div>
                    <label>
                        <span>タイトル</span>
                        <input v-model="title" type="text" maxlength="255" placeholder="コミュニティ名">
                    </label>
                </div>
                <div class="community-settings-image-tools">
                    <Cropper ref="cropper" place-holder="コミュニティ画像をアップロード"/>
                    <div class="community-settings-image-actions">
                        <button type="button" class="community-control-secondary" :disabled="iconUploading" @click="uploadIcon">
                            {{ iconUploading ? 'アップロード中' : '画像を反映' }}
                        </button>
                        <button v-if="iconPath" type="button" class="community-control-secondary" @click="iconPath = null">画像を削除</button>
                    </div>
                </div>
                <div class="community-icon-modal-actions">
                    <button type="button" class="community-control-secondary" @click="close">キャンセル</button>
                    <button type="button" class="community-control-save" :disabled="saving || !title.trim()" @click="create">
                        {{ saving ? '作成中' : '作成' }}
                    </button>
                </div>
            </div>
        </template>
    </Modal>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { useApi } from '@/composables/api';
import { useDialog } from '@/composables/dialog';
import { useAuthUserStore } from '@/store/auth';
import Cropper from '@/components/Global/Cropper.vue';
import Modal from '@/components/Global/Modal.vue';

const emit = defineEmits<{ (e: 'close'): void; (e: 'created'): void }>()

const auth = useAuthUserStore()
const { ping, toast } = useDialog()
const api = useApi()

const title = ref('')
const iconPath = ref<string | null>(null)
const saving = ref(false)
const iconUploading = ref(false)
const cropper = ref<InstanceType<typeof Cropper> | null>(null)

const iconPreview = computed(() => iconPath.value ? `/board_icon_thumbnail/${iconPath.value}/96` : '')
const initial = computed(() => (title.value || 'C').charAt(0).toUpperCase())

const uploadIcon = async () => {
    if(iconUploading.value) return
    const result = await cropper.value?.complete()
    if(!result?.blob || !result.source){
        ping('画像をアップロードしてください。')
        return
    }
    iconUploading.value = true
    try {
        const formData = new FormData()
        formData.append('file', result.blob)
        iconPath.value = await api.post('/icon_up_api', formData, {}, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        })
        cropper.value?.destroy()
    } finally {
        iconUploading.value = false
    }
}

const create = async () => {
    if(saving.value) return
    if(!title.value.trim()){
        ping('コミュニティ名を入力してください。')
        return
    }
    saving.value = true
    try {
        await auth.createCommunity({
            name: title.value.trim(),
            icon_path: iconPath.value,
        })
        toast('コミュニティを作成しました。')
        emit('created')
        close()
    } finally {
        saving.value = false
    }
}

const close = () => {
    cropper.value?.destroy()
    emit('close')
}
</script>

<style scoped>
.community-control-save{
    min-width: 72px;
    height: 34px;
    border: solid thin var(--primary-color);
    border-radius: 6px;
    background: var(--primary-color);
    color: var(--background-color);
    font-size: 13px;
    cursor: pointer;
}
.community-control-save:disabled{
    opacity: 0.45;
    cursor: default;
}
.community-control-secondary{
    min-width: 72px;
    height: 34px;
    border: solid thin var(--formBorder);
    border-radius: 6px;
    background: var(--background-color);
    color: var(--primary-color);
    font-size: 13px;
    cursor: pointer;
    padding: 0 10px;
}
.community-control-secondary:disabled{
    opacity: 0.45;
    cursor: default;
}
.community-icon-modal{
    display: flex;
    flex-direction: column;
    gap: 14px;
    height: 100%;
    min-height: 300px;
}
.community-settings-fields{
    display: grid;
    grid-template-columns: 64px minmax(0, 1fr);
    align-items: center;
    gap: 14px;
}
.community-settings-preview{
    width: 64px;
    height: 64px;
    border-radius: 8px;
    overflow: hidden;
    background: var(--bg3);
    border: solid thin var(--formBorder);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--primary-color);
    font-size: 24px;
    font-weight: 700;
}
.community-settings-preview img{
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.community-settings-fields label{
    display: flex;
    flex-direction: column;
    gap: 5px;
    min-width: 0;
}
.community-settings-fields label span{
    color: gray;
    font-size: 11px;
}
.community-settings-fields input{
    width: 100%;
    height: 36px;
    border: solid thin var(--formBorder);
    border-radius: 6px;
    background: var(--background-color);
    color: var(--primary-color);
    padding: 0 10px;
    font-size: 13px;
    box-sizing: border-box;
}
.community-settings-image-tools{
    display: flex;
    flex-direction: column;
    gap: 10px;
    min-height: 250px;
}
.community-icon-modal .filedrop-area,
.community-icon-modal .cropping-area{
    min-height: 220px;
}
.community-settings-image-actions{
    display: flex;
    justify-content: center;
    gap: 10px;
}
.community-icon-modal-actions{
    display: flex;
    justify-content: center;
    gap: 12px;
    margin-top: auto;
}
:global(.community-settings-modal){
    width: min(520px, calc(100vw - 40px)) !important;
    height: min(500px, calc(100vh - 40px)) !important;
}
</style>
