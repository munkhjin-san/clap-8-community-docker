<template>
    <div @mousedown="closeModal" class="overlay" style="z-index: 27;font-size:14px">
        <div style="width: 100%;height: 100%;display: flex;align-items: center;justify-content: center;">  
            <div id="editModal" class="chatCreate scrollable" ref="editModal" @mousedown.stop>
                <div class="recordFormTitle">
                    <p>ボードを編集する</p>
                    <div @click="emit('close')" class="m-close-button" style="position: unset;margin-left: auto;">
                        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 32 32">
                            <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                        </svg>
                    </div>                
                </div>
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
                            <!-- <div @click="targetBoard.icon_path = null" style="position:absolute;cursor: pointer; top:0;right:0;border-radius:50%;background:rgb(181 181 181);">
                                <svg version="1.1" xmlns="http://www.w3.org/2000/svg" style="padding:4px;fill:#fff;" width="10" height="10" viewBox="0 0 32 32">
                                    <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                                </svg>
                            </div>  -->
                        </div>  
                    </div>
                                      
                </div>                
                <div style="text-align: center;margin-top: auto;padding-top: 30px;">
                    <LoaderButton @triggered="cropComplete()" :loading="loader" :content="'保存する'"/>
                </div>        
            </div>
        </div>
    </div>
</template>

<script setup>
import LoaderButton from '../Global/LoaderButton.vue'
import ShortInput from '../Form/ShortInput.vue'
import ColorPicker from '../Global/ColorPicker.vue';
import { computed, inject, onMounted, reactive, ref, toRaw } from 'vue';  
import Cropper from '../Global/Cropper.vue';  
import BoardIcon from './Mixed/BoardIcon.vue';
import CommandButton from '../Global/CommandButton.vue';
    
    const props = defineProps(['editTarget'])
    const emit = defineEmits(['close'])
    const targetBoard = reactive({ ...toRaw(props.editTarget) })
    const title = ref('')
    const cropperInstance = ref(null)
    const loader = ref(false)
    const editModal = ref(null)
    const boardTitle = ref(null)
    const newIcon = ref(null)
    const iconBg = ref(props.editTarget?.icon_bg ?? '#000')
    const iconText = ref(props.editTarget?.icon_text ?? '')
    const iconType = ref(props.editTarget?.icon_path ? 1 : 0)
    onMounted(() => {         
        title.value = props.editTarget.title             
    })
    const { notify, info } = inject('dialog')
    const reload = inject('reload')
    const defaultIcon = computed(() => {
        if (iconText.value) {
            const color = encodeURIComponent(iconBg.value);
            const noSpace = iconText.value?.replace(/[\s　]/g, '');   
            const basePath = '/board_default_thumbnail'
            return `${basePath}/${noSpace}/45/${color}`; 
        }
    })

    const closeModal = (event) => {
        if (!editModal.value.contains(event.target)) {
            emit('close', false)
        }
    }
    const boardEditSend = async () => {
        const result = await boardTitle.value.validate();
        if(!result.valid) return
        loader.value = true
        const noSpaceEdit = title.value.replace(/\s/g, '', '　')
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
        try{
            await axios.post('/board_edit', params)
            reload()
            info('編集しました。')
            emit('close', true)
        }catch (e) {
            notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
        } finally {
            loader.value = false
        }                      
        
    }
    const cropComplete = async() => {
        if(loader.value) return
        if(cropperInstance.value){
            
            const { blob, source } = await cropperInstance.value.complete(); 
            if (!blob || !source) {
                notify('アイコンをアップロードしてください。')
                return;
            }  
            try{         
                loader.value = true
                const formData = new FormData();
                formData.append("file", blob);
                const data = await axios.post("/icon_up_api", formData, {
                    headers: {
                        "Content-Type": "multipart/form-data",
                    },
                }).then(res => res.data);

                                    
                    targetBoard.icon_path = data;                            
                    boardEditSend()
                
            }catch(e){
                notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
                loader.value = false     
   
            } 
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
