<template>
    <div @mousedown="emit('close')" class="overlay">            
        <div id="modalContent04" @mousedown.stop style="position: relative;"> 
            <div style="width:100%;display:flex;">
                <p style="padding-bottom: 20px;">メンバー選択</p>
                <svg style="margin:0 0 0 auto;cursor:pointer" @click="emit('close')" version="1.1" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 32 32">
                    <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                </svg>
            </div>                        
            <span style="position: initial;padding-bottom:10px;display:block;font-size:12px;">{{ requestType === 'sign' ? 'サイン依頼するメンバーを選択してください' : '確認依頼するメンバーを選択してください'}}</span>
            
            <div id="checkUserSelecter" style="border: 1px solid #ccc; max-height: 60vh; overflow-y: auto;">
                <div style="padding:5px 15px;display:flex;user-select: none;cursor:pointer;margin-top:10px">                                
                    <label class="check-container" style="align-self: center;margin-bottom: 5px;">
                        <input id="allMemberSelector" @change="selectAllMembers" value="" name="" type="checkbox">
                        <span class="checkmark-mini" style="margin: auto;bottom: 0;"></span>
                        <div class="left-panel-items" style="width: auto;padding:5px 0;margin:0;">                                    
                            <div style="align-self: center;">
                                <p style="line-height: 1.3;font-size: 16px;">すべて選択</p>                                          
                            </div>                                      
                        </div>
                    </label>                      
                </div>
                <div>    
                    <div :key="user.id" v-for="user in targetUsers" style="padding:0 15px;display:flex;">                                
                        <label class="check-container" style="align-self: center;margin-bottom: 5px;">
                            <input @change="validateSelection" v-model="selectedMembers" :value="user.id" name="targetMembers" type="checkbox">
                            <span class="checkmark-mini" style="margin: auto;bottom: 0;"></span>                        
                            <div class="left-panel-items" style="width: auto;padding:5px 0;margin:0;user-select: none;cursor:pointer;">
                                <UserPanel :disableInstant="true" size="30" :title="user.name" :user="user" imgClass="userNormalIcon"/>                      
                                <div style="align-self: center;padding:0 10px;">
                                    <p style="line-height: 1.3;font-size: 16px;">{{user.name}}</p>                                          
                                </div>                                      
                            </div>
                        </label>  
                    </div>
                </div>                          
            </div>

            <div v-if="selectedMembers.length > 1 && requestType == 'sign'" style="margin-top: 15px;">
                <span style="position: initial;padding-bottom:10px;display:block;font-size:12px;">ファイル数を設定してください。</span>
                <div v-for="prepareType in prepareTypes" style="display: flex;align-items: center;padding: 5px 0;">
                    <input class="fish-eye" v-model="prepare" type="radio" :id="`prepare_${prepareType.value}`" name="answer" :value="prepareType.value" >
                    <label style="margin-left:10px;cursor:pointer;font-size: 14px;" :for="`prepare_${prepareType.value}`">{{prepareType.content}}</label>
                </div>

            </div>
            <div class="relative">
                <span id="userSelectError" v-if="required" class="valid-error">必須です</span>
            </div>
            <div class="mt-5" v-if="requestType == 'confirm'">
                <div>
                    <div class="switchLabel">
                        <p class="form-lbl" style="white-space: nowrap;font-size: 14px;">期日指定</p>
                    </div>
                    <div class="selectSwitchArea" style="display: flex;width: 100%;margin-top:10px;">    
                        <input v-model="hasDeadline" type="checkbox" id="hasDeadline">
                        <label for="hasDeadline" style="min-width: 80px;" class="cursor-pointer"><span></span>
                            <div class="switch-toggle"></div>
                        </label>
                        
                    </div>  
                </div>
                
                
            </div>
            <div v-if="hasDeadline" class="mt-5">
                <p style="font-size: 14px;">期日</p>
                <div style="display:flex;margin-top: 10px;position: relative;width:100%;gap: 10px;">                    
                    <ShortInput 
                        name="recordDateStart" 
                        customClass="date"
                        ref="recordDateStart"
                        type="date"
                        v-model="checkRequestDl"
                    />   
                    <select 
                        class="dropDownSelector cursor-pointer"
                        :class="[{'date-color' : theme.dark }]" 
                        name="scheduleHour" 
                        v-model="selectedTime"
                        style="min-height: 40px; font-size: 14px; border: solid 1px var(--primary-color);"
                    >
                        <option :value="option" v-for="option in availableHours" v-html="`${option}時`"></option>
                    </select>                  
                </div> 
            </div>
            <div style="position: relative;display: flex;flex-direction: column;">
                <LoaderButton style="margin: 30px auto 20px auto;" @triggered="checkRequest" :content="'送信する'" :loading="processing"/>
            </div>
      
        </div> 
    </div>
</template>

<script setup lang="ts">
import { computed, inject, ref } from 'vue';
import LoaderButton from '../../Global/LoaderButton.vue'
import UserPanel from '@/components/Global/UserPanel.vue'
import { useAuthUserStore } from '@/store/auth'
import { useApi } from '@/composables/api';
import { useDialog } from '@/composables/dialog';
import { BoardMethodsKey, BoardMethods } from '@/interface/keys';
import { useBoardList } from '@/composables/board';
import { DateTime } from 'luxon';
import ShortInput from '@/components/Form/ShortInput.vue';
import { useTheme } from '@/store/theme';
import { Message } from '@/interface/globalInterface';
    const auth = useAuthUserStore()
    // const props = defineProps(['message', 'requestType', 'file'])
    const props = defineProps<{
        message: Message;
        requestType: string;
        file?: any;
    }>()
    const emit = defineEmits(['close'])
    const processing = ref(false)
    const prepare = ref(0)
    const { openedBoard } = useBoardList()
    const selectedMembers = ref<number[]>([])
    const checkRequestDl = ref<string>(DateTime.now().plus({day: 1}).toISODate())
    const hasDeadline = ref(false)
    const {refreshMessages} = inject(BoardMethodsKey) as BoardMethods
    const required = ref(false)
    const theme = useTheme()
    const selectedTime = ref(DateTime.now().plus({hours: 1}).hour)
    const availableHours = Array.from({ length: 24 }, (_, index) => index + 1);
    const api = useApi()
    const { ask, ping } = useDialog()
    const prepareTypes = [
        {content: '１枚（連名）', value: 0},
        {content: '人数分（個別）', value: 1}
    ]
    const targetUsers = computed(() => {
        let users = openedBoard.value && openedBoard.value.board_to_users ? openedBoard.value.board_to_users.map(ob => ob.user) : []
        if(props.requestType == 'confirm'){
            users = users.filter(ob => ob.id !== auth.activeUser.id && ob.on_leave === 0)
        }
        return users
    })
    const selectAllMembers = (event: Event) => {     
        if((event.target as HTMLInputElement).checked){
            selectedMembers.value = targetUsers.value.map(ob => ob.id)
        }else{
            selectedMembers.value = []
        }
    }
    const validateSelection = () => {
        required.value = selectedMembers.value.length === 0
    }
    const checkRequest = async() => {
        validateSelection()
        if(required.value) {
            ping('メンバーを選択してください。')
            return
        }
        const message = props.requestType == 'sign' ? 'サイン依頼をオンにします。\n選択したメンバーへサイン依頼の通知メールが送信されます。\nよろしいですか。' : '確認依頼をオンにします。\n選択したメンバーへ確認依頼の通知メールが送信されます。\nよろしいですか。'
        const confirmed = await ask(message)
        if(!confirmed.value || processing.value) return
        processing.value = true
        let params: Record<string, any> = {
            users: selectedMembers.value,
            type: props.requestType
        }
        const checkParam = hasDeadline.value ? DateTime.fromISO(checkRequestDl.value).set({hour: selectedTime.value, minute: 0, second: 0}).toFormat('yyyy-MM-dd HH:mm:ss') : null
        if(props.file){
            params['msg_file_id'] = props.file.id
            params['prepare'] = prepare.value
            params['board_id'] = openedBoard.value?.id
            params['msg_id'] = props.message.id
        }else{
            params['check_request_deadline'] = checkParam
            params['msg_id'] = props.message.id
        }

        const data = await api.post('/check_request_api', params, {
            toast: '送信しました。',
        })
        refreshMessages(data)           
        processing.value = false
        emit('close')
 
    }
</script>
