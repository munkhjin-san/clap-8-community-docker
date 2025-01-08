<template>
    <div @click="closeModal" class="overlay" style="z-index: 24;">
        <div id="createModal" class="chatCreate scrollable" ref="createModal" @click.stop>
            <div class="recordFormTitle">
                <h3>新しいボードを作成する</h3>
                <div @click="emit('close')" class="m-close-button" style="position: unset;margin-left: auto;">
                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 32 32">
                        <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                    </svg>
                </div>                
            </div>
            <div class="si-box" style="margin:0">
                <div style="display:flex;gap:15px;font-size: 14px;">
                    <div :class="['ch-selector', {chSelected : chatType == 0}]" @click="chatType = 0" style="font-size: 14px;">グループボード</div>
                    <div :class="['ch-selector', {chSelected : chatType == 1}]" @click="chatType = 1, board_users = []" style="font-size: 14px;">個別ボード</div>                
                </div>               
            </div>
            <div style="background: inherit;">
                <div v-if="chatType == 0" class="si-box">
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
                <div class="si-box" >
                    <MemberSelector 
                        placeHolder="メンバー選択（必須）"
                        rules="required"
                        name="boardMembers"
                        ref="boardMembers"
                        path="board_possible_users"
                        :exclude="[auth.id]"
                        :closeOnSelect="chatType == 1 ? true : false"
                        :limit="chatType == 1 ? 1 : null"
                        :multiple="true"
                        v-model="board_users"
                    />
                </div>
                <div v-if="chatType == 0">
                    <div class="si-box">
                        <div style="display:flex;gap:15px;font-size: 14px;">
                            <div :class="['ch-selector', {chSelected : iconType == 0}]" @click="iconType = 0" style="font-size: 14px;">テキストアイコン</div>
                            <div :class="['ch-selector', {chSelected : iconType == 1}]" @click="iconType = 1" style="font-size: 14px;">画像アイコン</div>                
                        </div>               
                    </div>
                    <div class="si-box" v-if="iconType == 0">
                        <ShortInput 
                            name="boardIconText"
                            place-holder="アイコンテキスト"
                            :initial-value="iconText"
                            custom-class="full"
                            type="text"
                            v-model="iconText"
                        />
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
                            <div v-if="tempImage" style="height: auto;max-height:calc(80vh / 2);background:#efefef;width: 100%;margin: auto;position:relative;">
                                <div @click="destroyCropper" style="position:absolute;right:10px;top:10px;background:#fff;border-radius:50px;min-width:30px;width:30px;height:30px;cursor:pointer;z-index: 5;display: flex;box-shadow: rgba(100, 100, 111, 0.2) 0px 7px 29px 0px;"> 
                                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" style="width:10px;height:10px;margin:auto;" fill="#000" viewBox="0 0 32 32">
                                        <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                                    </svg>
                                </div>
                                <img ref="cropImage" :src="tempImage">                         
                            </div>
                            <div v-else style="display: flex;width: fit-content;margin: 30px auto;">
                                <button class="commentEditButton" style="margin:0">
                                    <label for="boardIcon" class="cursor-pointer" :class="{uploadPassive: upFileArray.length}">アップロード</label>
                                    <input type="file" name="boardIcon" id="boardIcon" v-on:change="iconSelected" style="display: none;" accept="image/*">
                                </button>                                   
                            </div>
                            
                            
                        </div>
                    </div>
                    <div class="si-box">
                        <div v-if="iconType == 0" style="width: fit-content;padding: 15px;margin: auto;">
                            <div id="boardIconPreview" class="iconPreview">
                                <img v-if="iconText" draggable="false" loading="lazy" class="iconPreviewInner" :src="defaultIcon">

                                <svg v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 30 30" class="boardNormalIcon">
                                    <circle cx="15" cy="15" r="15" :fill="iconBg"/>
                                </svg>
                            </div>
                        </div>
                        <div v-else class="flex justify-center">
                            <img v-if="croppedImage" draggable="false" loading="lazy" class="iconPreviewInner" style="width:45px;height:45px" :src="croppedImage">
                            <svg v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 30 30" class="boardNormalIcon">
                                <circle cx="15" cy="15" r="15" :fill="iconBg"/>
                            </svg>
                        </div> 
                    </div>
                    
                </div>               
                                    
            </div>
            
            
            <div style="text-align: center;margin-top: auto;padding-top: 20px;">
                <LoaderButton @triggered="cropComplete" :loading="loader" content="作成する"/>
            </div>
        </div>        
    </div>
</template>

<script setup>
import Cropper from 'cropperjs';
import LoaderButton from '../Global/LoaderButton.vue'
import MemberSelector from '../Form/MemberSelector.vue'
import 'cropperjs/dist/cropper.css';
import ShortInput from '../Form/ShortInput.vue'
import { computed, inject, ref, watch } from 'vue';
import { useAuthUserStore } from '@/store/auth';
import ColorPicker from '../Global/ColorPicker.vue';
    const auth = useAuthUserStore()
    const emit = defineEmits(['close'])
    const chatType = ref(0)
    const iconType = ref(0)
    const tempImage = ref(null)
    const cropperInstance = ref(null)
    const loader = ref(false)
    const upFileArray = ref([])
    const title = ref('')
    const iconId = ref(null)
    const board_users = ref([])
    const cropImage = ref(null)
    const { confirm, notify, info } = inject('dialog');
    const boardTitle = ref(null)
    const boardMembers = ref(null)
    const iconBg = ref('#000')
    const iconText = ref('')
    const croppedImage = ref(null)
    const reload = inject('reload')
    const validateTargets = computed(() => {
        return [
            boardTitle.value,
            boardMembers.value, 
        ]
    }) 
    watch(title, (newValue) => {
        if (newValue !== iconText.value) {
            iconText.value = newValue
        }
    })
    const defaultIcon = computed(() => {
        const color = encodeURIComponent(iconBg.value);
        const noSpace = iconText.value?.replace(/[\s　]/g, '');   
        const basePath = '/board_default_thumbnail'
        return `${basePath}/${noSpace}/45/${color}`; 
    })
    const previewText = computed(() => {
        if (!iconText.value) {
            return ''; 
        }
        const fontSizes = [22, 17, 13, 13, 12, 12];
        let index = Math.min(iconText.value.length, fontSizes.length) - 1;
        let fontSize = fontSizes[index];
        let text = iconText.value;
        if (index === 3) {
            text = `${iconText.value.slice(0, 2)}<br>${iconText.value.slice(2, 4)}`;
        } else if (index >= 4) {
            text = `${iconText.value.slice(0, 3)}<br>${iconText.value.slice(3, 6)}`;
        }
        return `<div style="font-size:${fontSize}px">${text}</div>`;
    })
    
    const destroyCropper = async() => {
        return new Promise((resolve) => {
            if(cropperInstance.value){
                cropperInstance.value.destroy();
                cropperInstance.value = null;
                croppedImage.value = null
            }
            tempImage.value = null 
            resolve()
        });
        
    }
    const iconDelete = () => {
        upFileArray.value = [];
        iconId.value = null;         
    }
    const iconSelected = async() => {          
        await destroyCropper()
        tempImage.value = URL.createObjectURL(event.target.files[0]);
        setTimeout(() => {
            var image = cropImage.value 
            cropperInstance.value = new Cropper(image, {              
                dragMode: 'move',
                preview: '.preview',
                aspectRatio: 1 / 1,                     
                viewMode: 1,
                responsive:true,
                autoCrop: true,
                background: false,
                guides: false,
                zoomable:false,
                crop() {
                    const canvas = cropperInstance.value.getCroppedCanvas()
                    if (canvas) {
                        croppedImage.value = canvas.toDataURL('image/webp', 0.8);
                    }
                }           
            });
        }, 0);
        
                     
    }
    const cropComplete = async() => {
        if(loader.value) return
        if(cropperInstance.value){                              
            cropperInstance.value.getCroppedCanvas().toBlob((blob) => {                        
                const formData = new FormData()
                formData.append('file', blob)            
                axios.post('/icon_up_api', formData)
                .then(response => {
                    if(response.data.icon_id && response.data.set_path){
                        upFileArray.value = []
                        upFileArray.value[0] = response.data.set_path;                        
                        iconId.value = response.data.icon_id;                            
                        boardAdd()
                    }                   
                }).catch(function (error) { 
                    if(error.response.status == 413){
                        notify('アップロードファイルのサイズが大きすぎます。1MB以下のファイルをアップロードしてください')
                        destroyCropper()
                    }else{
                        notify('ファイルアップロード中にエラーが発生しました')  
                    }
                    loader.value = false                          
                });                      
            });
        }else{
            boardAdd() 
        }
    }
    const boardAdd = async() => {     
        const targets = validateTargets.value.filter(ob => ob !== null)
        let result = true
        for(const target of targets){            
            const val = await target?.validate() || {valid: false}
            result = result * val.valid
        }
        if (!result) return
        loader.value = true  
        const noSpace = title.value.replace(/\s/g, '', '　')            
        const titleLen = title.value == null ? 4 : title.value.length            
        const params = {
            private_flag: chatType.value,
            str_len : titleLen,
            icon_text: iconText.value,
            icon_id: iconId.value,
            icon_bg: iconBg.value,
            to_users: board_users.value.map(ob => ob.id),
            title_no_space: noSpace,
            title: title.value,
            file: upFileArray.value,
        }
        loader.value = true
        try{
            const data = await axios.post('/board_create', params).then(res => res.data)
            if(data.message && data.data){
                loader.value = false   
                info(data.message)
                emit('reload', data.data.id)  
                emit('close')                   
            }else if(response.data.message == 'empty' || response.data.message == 'titleNeeded'){
                notify('メンバーを選択してください。')                   
            }   
            loader.value = false 
        } catch (e) { 
            notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
        }  
    }
</script>
