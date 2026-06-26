<template>
    <section v-if="auth.activeCommunity" class="community-control-panel">
        <div class="community-control-preview">
            <img v-if="communityIconPreview" :src="communityIconPreview" :alt="communityTitle" loading="lazy">
            <span v-else>{{ communityInitial }}</span>
        </div>
        <div class="community-control-main">
            <div class="community-control-title">
                <p>{{ auth.activeCommunity?.name }}</p>
                <span>{{ auth.activeCommunity?.slug }}</span>
            </div>
        </div>
        <div class="community-control-actions" v-if="canManageCommunity">
            <button type="button" class="community-edit-trigger" title="コミュニティ設定" @click="openCommunitySettings">
                <EditIcon size="16"/>
            </button>
        </div>
    </section>
    <Modal v-if="settingsModalOpen" size="medium" disable-scroll custom-class="community-settings-modal" @close="closeCommunitySettings">
        <template #title>
            <p>コミュニティ設定</p>
        </template>
        <template #content>
            <div class="community-icon-modal">
                <div class="community-settings-fields">
                    <div class="community-settings-preview">
                        <img v-if="communityIconPreview" :src="communityIconPreview" :alt="communityTitle" loading="lazy">
                        <span v-else>{{ communityInitial }}</span>
                    </div>
                    <label>
                        <span>タイトル</span>
                        <input v-model="communityTitle" type="text" maxlength="255">
                    </label>
                </div>
                <div class="community-settings-image-tools">
                    <Cropper ref="communityCropper" place-holder="コミュニティ画像をアップロード"/>
                    <div class="community-settings-image-actions">
                        <button type="button" class="community-control-secondary" :disabled="iconUploading" @click="uploadCommunityIcon">
                            {{ iconUploading ? 'アップロード中' : '画像を反映' }}
                        </button>
                        <button v-if="communityIconPath" type="button" class="community-control-secondary" @click="clearCommunityIcon">画像を削除</button>
                    </div>
                </div>
                <div class="community-icon-modal-actions">
                    <button type="button" class="community-control-secondary" @click="closeCommunitySettings">キャンセル</button>
                    <button type="button" class="community-control-save" :disabled="communitySaving || !hasCommunityChanges" @click="saveCommunity">
                        {{ communitySaving ? '保存中' : '保存' }}
                    </button>
                </div>
            </div>
        </template>
    </Modal>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { useApi } from '@/composables/api';
import { useDialog } from '@/composables/dialog';
import { useAuthUserStore } from '@/store/auth';
import Cropper from '@/components/Global/Cropper.vue';
import Modal from '@/components/Global/Modal.vue';
import EditIcon from '@/components/Icons/Edit.vue';

const auth = useAuthUserStore()
const { ping, toast } = useDialog()
const api = useApi()
const communityTitle = ref('')
const communityIconPath = ref<string | null>(null)
const communitySaving = ref(false)
const settingsModalOpen = ref(false)
const iconUploading = ref(false)
const communityCropper = ref<InstanceType<typeof Cropper> | null>(null)

const syncCommunityForm = () => {
    communityTitle.value = auth.activeCommunity?.name ?? ''
    communityIconPath.value = auth.activeCommunity?.config?.icon_path ?? null
}

watch(() => auth.activeCommunity, syncCommunityForm, { immediate: true, deep: true })

const canManageCommunity = computed(() => auth.hasCapability('community.manage'))
const communityIconPreview = computed(() => communityIconPath.value ? `/board_icon_thumbnail/${communityIconPath.value}/96` : '')
const communityInitial = computed(() => (communityTitle.value || auth.activeCommunity?.name || 'C').charAt(0).toUpperCase())
const hasCommunityChanges = computed(() => {
    return communityTitle.value !== (auth.activeCommunity?.name ?? '')
        || communityIconPath.value !== (auth.activeCommunity?.config?.icon_path ?? null)
})

const saveCommunity = async () => {
    if(communitySaving.value) return
    if(!communityTitle.value.trim()){
        ping('コミュニティタイトルを入力してください。')
        return
    }
    communitySaving.value = true
    try {
        await auth.updateActiveCommunity({
            name: communityTitle.value.trim(),
            icon_path: communityIconPath.value,
        })
        toast('コミュニティ設定を保存しました。')
        closeCommunitySettings()
    } finally {
        communitySaving.value = false
    }
}

const uploadCommunityIcon = async () => {
    if(iconUploading.value) return
    const result = await communityCropper.value?.complete()
    if(!result?.blob || !result.source){
        ping('画像をアップロードしてください。')
        return
    }
    iconUploading.value = true
    try {
        const formData = new FormData()
        formData.append('file', result.blob)
        communityIconPath.value = await api.post('/icon_up_api', formData, {}, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        })
        communityCropper.value?.destroy()
    } finally {
        iconUploading.value = false
    }
}

const clearCommunityIcon = () => {
    communityIconPath.value = null
}

const openCommunitySettings = () => {
    syncCommunityForm()
    settingsModalOpen.value = true
}

const closeCommunitySettings = () => {
    communityCropper.value?.destroy()
    settingsModalOpen.value = false
}
</script>

<style scoped>
.community-control-panel{
    display: grid;
    grid-template-columns: 52px minmax(220px, 1fr) auto;
    align-items: center;
    gap: 14px;
    padding: 10px 18px;
    border-bottom: solid thin var(--formBorder);
    background: var(--bg3);
}
.community-control-preview{
    width: 52px;
    height: 52px;
    border-radius: 8px;
    overflow: hidden;
    background: var(--background-color);
    border: solid thin var(--formBorder);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--primary-color);
    font-size: 22px;
    font-weight: 700;
}
.community-control-preview img{
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.community-control-main{
    display: flex;
    align-items: center;
    min-width: 0;
}
.community-control-title{
    display: flex;
    flex-direction: column;
    gap: 3px;
    min-width: 0;
}
.community-control-title p{
    margin: 0;
    font-size: 15px;
    font-weight: 700;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.community-control-title span{
    color: gray;
    font-size: 11px;
    overflow: hidden;
    text-overflow: ellipsis;
}
.community-control-actions{
    display: flex;
    align-items: center;
    gap: 8px;
    justify-content: flex-end;
    min-width: 34px;
}
.community-edit-trigger{
    width: 34px;
    height: 34px;
    border: solid thin var(--formBorder);
    border-radius: 6px;
    background: var(--background-color);
    color: var(--primary-color);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
}
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

@media screen and (max-width: 959px) {
    .community-control-panel{
        grid-template-columns: 44px minmax(0, 1fr) 34px;
        align-items: center;
        gap: 10px;
        padding: 12px;
    }
    .community-control-preview{
        width: 44px;
        height: 44px;
        font-size: 18px;
    }
    .community-control-main{
        min-width: 0;
    }
    .community-control-actions{
        min-width: 0;
        justify-content: flex-end;
    }
}
</style>
