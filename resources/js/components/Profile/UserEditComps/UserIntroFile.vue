<template>
    <Modal @close="emit('closeModal')">
        <template #title>
            <p style="font-size: 18px;">ファイルアップロード</p>
        </template>
        <template #content>
            <div class="si-box">
                <ShortInput 
                    :initialValue="introTitle"
                    placeHolder="タイトル"
                    uId="introTitle"
                    name="introTitle"
                    v-model="introTitle"
                />
            </div>
            <div class="si-box">
                <TagSelector 
                    placeHolder="タグ選択"
                    :suggestion="tagSuggestionText"
                    :specialTags="['推し']"
                    v-model="tags"
                />
            </div>
            <div class="si-box">
                <UserFileUploader 
                    v-model="userAlbumbs"
                    :path="'/cdn/user_album/' + UserAllData.id"
                />
            </div>
            <div class="si-box" style="margin-top:auto;margin-bottom: 30px;">
                <LoaderButton 
                    @triggered="saveAlbum()" 
                    :loading="processing" 
                    :content="editData ? '編集する' : '保存する'"
                />
            </div>
        </template>
    </Modal>
</template>
<script setup>
import Modal from '../../Global/Modal.vue';
import ShortInput from '../../Form/ShortInput.vue';
import UserFileUploader from './UserFileUploader.vue';
import LoaderButton from '../../Global/LoaderButton.vue';
import TagSelector from '../../Form/TagSelector.vue';
import { ref, computed } from 'vue';
import { useApi } from '@/composables/api';
import { useDialog } from '@/composables/dialog';
    const props = defineProps(['UserAllData', 'editData'])
    const emit = defineEmits(['updateUser', 'closeModal'])
    const introTitle = ref(props.editData && props.editData.title ? props.editData.title : '')
    const userAlbumbs = ref(props.editData ? [props.editData] : [])
    const processing = ref(false)
    const tags = ref(props.editData && props.editData.tags.length ? props.editData.tags : [])
    const api = useApi()
    const { ping } = useDialog()
    const tagSuggestionText = computed(() => {
        const gTitle = introTitle.value ? `${introTitle.value}` : ''
        return `${gTitle}` 
    })
    const saveAlbum = async() => {
        if(processing.value) return
        
        const params = {
            id: props.editData ? props.editData.id : null,
            title: introTitle.value,
            uploadedImages : userAlbumbs.value.length ? userAlbumbs.value : [],
            intro_flag : 1,
            path: '/user_album/' + props.UserAllData.id,
            tags: tags.value.length ? tags.value.map(ob => ob.text) : [],
        }
        if(!userAlbumbs.value && !userAlbumbs.value.length){
            ping('ファイルをアップロードしてください。')
            return
        }
        processing.value = true
        
        await api.post('/save_intro', params, {
            loadingRef: processing,
        })
        emit('closeModal')
        emit('updateUser')      
    }
    
</script>