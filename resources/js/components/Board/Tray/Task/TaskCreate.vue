<template>
    <div @mousedown="emit('close', false)" class="overlay" style="z-index:24">        
        <div class="chatCreate scrollable" style="position:relative" @mousedown.stop>           
            <div class="recordFormTitle" style="display:flex;">
                <p>{{ editTaskData ? 'タスクを編集する' : '新しいタスクを作成する'}}</p>
                <div style="margin-left:auto;"> 
                    <div @click="emit('close', false)" class="cursor-pointer" style="position:unset;">
                        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 32 32">
                            <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                        </svg>
                    </div>                                         
                </div>
            </div>
            <div class="si-box">                   
                <LongInput
                    :initialValue="content"  
                    ref="taskContent"
                    placeHolder="メモ"
                    name="taskContent"
                    v-model="content"
                />                    
            </div>

            <div class="si-box">
                <div class="switchLabel">
                    <p class="form-lbl" style="white-space: nowrap;font-size: 14px;">全員選択</p>
                </div>
                <div class="selectSwitchArea" style="display: flex;width: 100%;">    
                    <input @change="selectAll" :checked="allSelected" type="checkbox" id="edit_all">
                    <label for="edit_all" style="min-width: 80px;" class="cursor-pointer"><span></span>
                        <div class="switch-toggle"></div>
                    </label>
                    
                </div>  
            </div> 

            <div class="si-box">
                <MemberSelector 
                    placeHolder="メンバー選択（必須）"
                    rules="required"
                    name="taskMembers"
                    ref="taskMembers"
                    :closeOnSelect="false"
                    v-model="qualified_users"  
                    :options="boardMembers"                  
                />
            </div>

            <div class="si-box">
                <p class="form-lbl" style="font-size: 14px;">期限</p>
                <div style="display:flex;margin-top: 10px;position: relative;width:100%">                    
                    <ShortInput 
                        name="recordDateStart" 
                        :initialValue="once_date"
                        customClass="date"
                        ref="recordDateStart"
                        type="date"
                        v-model="taskEndDate"
                    />                   
                </div>                           
            </div>          
            

            <div class="si-box">
                <LoaderButton @triggered="taskCreate" :loading="loading" content="保存する"/>
            </div> 
        </div>
    </div>   
</template>
<script setup>

import moment from 'moment'
import LoaderButton from '../../../Global/LoaderButton.vue';
import LongInput from '../../../Form/LongInput.vue';
import MemberSelector from '../../../Form/MemberSelector.vue';
import ShortInput from '../../../Form/ShortInput.vue';
import { computed, inject, onMounted, ref } from 'vue';
import { useAuthUserStore } from '@/store/auth'
import { useSharingDataStore } from '@/store/sharingData'
    const sharingData = useSharingDataStore()
    const auth = useAuthUserStore()

    const props = defineProps(['editTaskData'])
    const emit = defineEmits(['close'])
    const content = ref(props.editTaskData ? props.editTaskData.remarks : '')
    const qualified_users = ref(props.editTaskData ? props.editTaskData.to_users : [auth.activeUser]) 
    const loading = ref(false)
    const taskEndDate = ref(props.editTaskData && props.editTaskData.end_at?  moment(props.editTaskData.end_at).format('YYYY-MM-DD') : '')
      
    const taskMembers = ref(null)
    const board = inject('openedBoard')
    const {notify, info} = inject('dialog')

    onMounted(() => {
        if(!props.editTaskData && sharingData.active){
            content.value = sharingData?.message?.message_text
            const shareData = {
                active: false,
                title: '',
                text: '',
                files: [],
                from: '',
                to: '',
                drag: false,
                instruction: ''
            }
            sharingData.setSharingData(shareData)
        }
    })
    const boardMembers = computed(() => {
        if(board.value){
            return board.value.board_to_users.map(ob => ob.user)
        }
        return []
    })
    const allSelected = computed(() => {
        return boardMembers.value.length == qualified_users.value.length
    }) 
    const taskCreate = async() => {   
        const valid = await taskMembers.value.validate()
        if(!valid.valid) return
        const params = {            
            qualified_users: qualified_users.value.map(ob => ob.id),
            remarks: content.value,
            task_end_date: taskEndDate.value,
            board_id: board.value.id,
            edit_id: props.editTaskData ? props.editTaskData.id : null
        };
        try{
            loading.value = true
            await axios.post('/add_task_api', params )            
            info('作成しました。')
            emit('close', true)
            loading.value = false
        }catch (e) {
            notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
            loading.value = false
        }
        
    }
    const selectAll = (event) => {
        if(taskMembers.value){
            taskMembers.value.selectAll(event.target.checked)
        }
    }
</script>