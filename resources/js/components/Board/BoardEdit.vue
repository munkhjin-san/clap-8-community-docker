<template>
    <Modal @close="emit('close', false)">
        <template #title>
            <p>チャットを編集する</p>
        </template>
        <template #content>
            <div style="background: inherit;">
                <div class="si-box">
                    <ShortInput 
                        name="boardTitle" 
                        placeHolder="タイトルを入力（必須）" 
                        :rules="'required'"
                        :initialValue="title"
                        customClass="full"
                        ref="boardTitle"
                        type="text"
                        v-model="title"
                    />
                </div>
                <div class="si-box">
                    <div style="display:flex;gap:15px;font-size: 14px;">
                        <div :class="['ch-selector', {chSelected : iconType == 0}]" @click="iconType = 0" style="font-size: 14px;">テキストアイコン</div>
                        <div :class="['ch-selector', {chSelected : iconType == 1}]" @click="iconType = 1" style="font-size: 14px;">画像アイコン</div>                
                    </div>               
                </div>
                <div class="si-box" v-if="iconType == 0">
                    <ShortInput 
                        name="boardIconText"
                        place-holder="アイコンテクスト"
                        :initial-value="iconText"
                        custom-class="full"
                        type="text"
                        v-model="iconText"
                    />
                </div>
                <div class="si-box">
                    <div style="text-align:center;padding: 10px;position:relative;border:solid thin var(--primary-color)">  
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
                                                                    
                </div> 
                <div class="si-box" >
                    <div v-if="iconType == 0" style="width: fit-content;padding: 15px;margin: auto;">
                        <div id="boardIconPreview" class="iconPreview">
                            <img v-if="iconText" draggable="false" loading="lazy" class="iconPreviewInner" :src="defaultIcon">
                            <svg v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 30 30" class="boardNormalIcon">
                                <circle cx="15" cy="15" r="15" :fill="iconBg"/>
                            </svg>
                        </div>                            
                    </div>
                    <div class="flex flex-col items-center gap-[10px] w-fit m-auto" v-else-if="targetBoard.icon_path">
                        <BoardIcon :item="targetBoard"/>
                        <CommandButton 
                            :buttons="[{title: 'アイコン初期化', action:() => {targetBoard.icon_path = null, iconType = 0}}]"
                        />
                    </div>  
                </div>
                                    
            </div>                
            <div style="text-align: center;margin-top: auto;padding-top: 30px;">
                <LoaderButton @triggered="cropComplete()" :loading="loader" :content="'保存する'"/>
            </div>  
        </template>
    </Modal>      
</template>

<script setup lang="ts">
import LoaderButton from '../Global/LoaderButton.vue'
import ShortInput from '../Form/ShortInput.vue'
import ColorPicker from '../Global/ColorPicker.vue';
import { computed, inject, onMounted, reactive, ref, toRaw, useTemplateRef } from 'vue';  
import Cropper from '../Global/Cropper.vue';  
import BoardIcon from './Mixed/BoardIcon.vue';
import CommandButton from '../Global/CommandButton.vue';
import { useApi } from '@/composables/api';
import { useDialog } from '@/composables/dialog';
import Modal from '../Global/Modal.vue';
    
    const props = defineProps(['editTarget'])
    const emit = defineEmits(['close'])
    const targetBoard = reactive({ ...toRaw(props.editTarget) })
    const title = ref('')
    const cropperInstance = useTemplateRef('cropperInstance')
    const loader = ref(false)
    const boardTitle = useTemplateRef('boardTitle')
    const iconBg = ref(props.editTarget?.icon_bg ?? '#000')
    const iconText = ref(props.editTarget?.icon_text ?? '')
    const iconType = ref(props.editTarget?.icon_path ? 1 : 0)
    const api = useApi()
    const { ping } = useDialog()
    onMounted(() => {         
        title.value = props.editTarget.title             
    })
    const reload = inject<Function>('reload') as Function
    const defaultIcon = computed(() => {
        if (iconText.value) {
            const color = encodeURIComponent(iconBg.value);
            const noSpace = iconText.value?.replace(/[\s　]/g, '');   
            const basePath = '/board_default_thumbnail'
            return `${basePath}/${noSpace}/45/${color}`; 
        }
    })

    const boardEditSend = async () => {
        const result = await boardTitle.value?.validate();
        if(!result?.valid) return
        loader.value = true
        const noSpaceEdit = title.value.replace(/[\s　]/g, '');
        const titleLenEdit = noSpaceEdit.length;
        const params = {
            str_len : titleLenEdit,
            title_no_space: noSpaceEdit,
            id: props.editTarget.id,
            title: title.value,
            icon_path: targetBoard.icon_path,
            icon_bg: iconBg.value,
            icon_text: iconText.value,
        }
        await api.post('/board_edit', params, { toast: '保存しました。'})
        reload()
        emit('close', true)
        loader.value = false
                          
        
    }
    const cropComplete = async() => {
        if(loader.value) return
        if(cropperInstance.value){
            
            const { blob, source } = await cropperInstance.value.complete(); 
            if (!blob || !source) {
                ping('アイコンをアップロードしてください。')
                return;
            }  
                
            loader.value = true
            const formData = new FormData();
            formData.append("file", blob);
            const data = await api.post("/icon_up_api", formData, {}, {
                headers: {
                    "Content-Type": "multipart/form-data",
                },
            })

                                    
            targetBoard.icon_path = data;                            
            boardEditSend()
                
            loader.value = false     
   
            
        }else{            
            boardEditSend()
        }
    }  

</script>
<style lang="scss">
    .form-error{
        color: tomato;
        position: absolute; 
        bottom: -10px;
        font-size: 11px;
        color: inherit;
        left: 0;
    }
</style>
