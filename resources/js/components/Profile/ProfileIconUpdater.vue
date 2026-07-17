<template>
    <Modal @close="emit('close', false)">
        <template #title>
            <p>アイコンを編集する</p>
        </template>
        <template #content>                         
            <div>
                <div style="display:flex;gap:15px;font-size: 14px;">
                    <div :class="['ch-selector', {chSelected : iconType == 0}]" @click="iconType = 0" style="font-size: 14px;">デフォルトアイコン</div>
                    <div :class="['ch-selector', {chSelected : iconType == 1}]" @click="iconType = 1" style="font-size: 14px;">画像アイコン</div>                
                </div>               
            </div>
            <div  class="si-box" style="padding: 10px;position:relative;border: solid thin var(--primary-color);">
                <div v-if="iconType == 0">
                    <span class="form-plc smallPlc">アイコンカラー</span>                  
                    <div class="flex justify-center">
                        <div class="si-box">
                            <ColorPicker v-model="iconBg"/>
                        </div>
                                                        
                    </div>
                </div>
                <div v-else>
                    <Cropper ref="cropperInstance"/>                       
                </div>
            </div>
            <div class="si-box">
                <div v-if="iconType == 0" style="width: fit-content;padding: 15px;margin: auto;">
                    <div id="boardIconPreview" class="iconPreview">
                        <UserDefaultIcon :name="userData.name" :bg="iconBg" :size="45" class="iconPreviewInner" />
                    </div>
                </div>
            </div>    
            <div class="si-box">
                <LoaderButton @triggered="sendIcon" :loading="sendLoader" content="保存する"/>
            </div>                    
        </template> 
    </Modal>   
</template>
<script setup lang="ts">
import { ref, useTemplateRef } from 'vue';
import Modal from '../Global/Modal.vue';
import ColorPicker from '../Global/ColorPicker.vue';
import { User } from '@/interface/globalInterface';
import Cropper from '../Global/Cropper.vue';
import LoaderButton from '../Global/LoaderButton.vue';
import UserDefaultIcon from '../Global/UserDefaultIcon.vue';
import { useApi } from '@/composables/api';
const props = defineProps<{
    userData: User
}>()
const emit = defineEmits<{
    close: [boolean]
}>()

const iconType = ref(0)
const iconBg = ref(props.userData?.icon_bg ?? '#000')
const sendLoader = ref(false)
const cropperInstance = useTemplateRef('cropperInstance')
const api = useApi()

const sendIcon = async() => {
    if(iconType.value == 0) {
        iconDeleteConfirm()
    }else if(iconType.value == 1 && cropperInstance.value){
        customIconCreate()
    }

}
const customIconCreate = async() => {
    if(!cropperInstance.value) return;
    sendLoader.value = true;
    const { blob, source } = await cropperInstance.value.complete();
    if(blob && source){
        const formData = new FormData();
        formData.append('croppedImage', blob/*, 'example.png' */);
        formData.append('orgImage', source)    
        await api.post('/user_icon_cropped_up_api', formData)
        emit('close', true);   
    }
}  
const iconDeleteConfirm = async() => {     
    sendLoader.value = true;
    await api.post('/user_icon_create_api', {icon_type: iconType.value, icon_bg: iconBg.value}, {
        ask: 'アイコンを変更しますか？',
        toast: 'アイコンを変更しました。'
    })
    emit('close', true);
    sendLoader.value = false;       
}
</script>