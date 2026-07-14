<template>
    <Modal @close="emit('close')" persist>
        <template #title>
            <p>新しいチャットを作成する</p>
        </template>
        <template #content>
            <div class="si-box" style="margin:0">
                <div style="display:flex;gap:15px;font-size: 14px;">
                    <div :class="['ch-selector', {chSelected : chatType == 0}]" @click="chatType = 0" style="font-size: 14px;">グループチャット</div>
                    <div :class="['ch-selector', {chSelected : chatType == 1}]" @click="chatType = 1, board_users = []" style="font-size: 14px;">個別チャット</div>                
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
                        :exclude="auth.id ? [auth.id] : []"
                        :closeOnSelect="chatType == 1 ? true : false"
                        :limit="chatType == 1 ? 1 : undefined"
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
                            <Cropper ref="cropperInstance"/>                          
                        </div>
                    </div>
                    <div class="si-box">
                        <div v-if="iconType == 0" style="width: fit-content;padding: 15px;margin: auto;">
                            <div id="boardIconPreview" class="iconPreview">
                                <BoardDefaultIcon v-if="iconText" :text="iconText" :bg="iconBg" :size="45" class="iconPreviewInner" />

                                <svg v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 30 30" class="boardNormalIcon">
                                    <circle cx="15" cy="15" r="15" :fill="iconBg"/>
                                </svg>
                            </div>
                        </div>
                    </div>                    
                </div>             
            </div>           
            
            <div style="text-align: center;margin-top: auto;padding-top: 20px;">
                <LoaderButton @triggered="cropComplete" :loading="loader" content="作成する"/>
            </div>
        </template>
    </Modal>
</template>

<script setup lang="ts">
import LoaderButton from '../Global/LoaderButton.vue'
import MemberSelector from '../Form/MemberSelector.vue'
import ShortInput from '../Form/ShortInput.vue'
import { computed, inject, ref, useTemplateRef, watch } from 'vue';
import { useAuthUserStore } from '@/store/auth';
import ColorPicker from '../Global/ColorPicker.vue';
import Cropper from '../Global/Cropper.vue';
import BoardDefaultIcon from './Mixed/BoardDefaultIcon.vue';
import { useApi } from '@/composables/api';
import { useDialog } from '@/composables/dialog';
import { User } from '@/interface/globalInterface';
import Modal from '../Global/Modal.vue';
    const auth = useAuthUserStore()
    const emit = defineEmits(['close', 'reload'])
    const chatType = ref(0)
    const iconType = ref(0)
    const cropperInstance = useTemplateRef('cropperInstance')
    const loader = ref(false)
    const upFileArray = ref([])
    const title = ref('')
    const icon_path = ref('')
    const board_users = ref<User[]>([])
    const boardTitle = useTemplateRef('boardTitle')
    const boardMembers = useTemplateRef('boardMembers')
    const iconBg = ref('000')
    const iconText = ref('')
    const reload = inject('reload')
    const api = useApi()
    const { ask, toast, ping } = useDialog()
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
    const cropComplete = async() => {
        if(loader.value) return
        if(cropperInstance.value){      
            const { blob, source } = await cropperInstance.value.complete(); 
            if (!blob || !source) {
                ping('エラーが発生しました。')
                return;
            }          
              
            loader.value = true
            const formData = new FormData();
            formData.append("file", blob);
            const data = await api.post("/icon_up_api", formData, {},  {
                headers: {
                    "Content-Type": "multipart/form-data",
                },
            })

            icon_path.value = data                          
            boardAdd()          

            loader.value = false     
           
        }else{
            boardAdd() 
        }
    }
    const boardAdd = async() => {     
        const targets = validateTargets.value.filter(ob => ob !== null)
        let result = true
        for(const target of targets){            
            const val = await target?.validate() || {valid: false}
            result = result && val.valid
        }
        if (!result) return
        loader.value = true  
        const noSpace = title.value.replace(/[\s　]/g, '')            
        const titleLen = title.value == null ? 4 : title.value.length            
        const params = {
            private_flag: chatType.value,
            str_len : titleLen,
            icon_text: iconText.value,
            icon_path: icon_path.value,
            icon_bg: iconBg.value,
            to_users: board_users.value.map(ob => ob.id),
            title_no_space: noSpace,
            title: title.value,
            file: upFileArray.value,
        }
        loader.value = true

            const data = await api.post('/board_create', params)
            if(data.message && data.data){
                loader.value = false   
                toast(data.message)
                emit('reload', data.data.id)  
                emit('close')                   
            }else if(data.message == 'empty' || data.message == 'titleNeeded'){
                ping('メンバーを選択してください。')                   
            }   
            loader.value = false 
 
    }
</script>
