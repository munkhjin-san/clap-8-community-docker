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
                    :multiple="true"
                    v-model="setExecutor"  
                    :options="boardMembers"
                />
            </div>
            <div class="si-box">
                <div class="switchLabel">
                    <p class="form-lbl" style="white-space: nowrap;font-size: 14px;">日時指定</p>
                </div>
                <div class="selectSwitchArea" style="display: flex;width: 100%;">    
                    <input v-model="isTask" type="checkbox" id="isTask">
                    <label for="isTask" style="min-width: 80px;" class="cursor-pointer"><span></span>
                        <div class="switch-toggle"></div>
                    </label>
                    
                </div>  
                
            </div>
            <div class="si-box" v-if="isTask">
                <p class="form-lbl" style="white-space: nowrap;font-size: 14px;">期限</p>
                <div style="display:flex;margin-top: 10px;position: relative;width:100%">                    
                    <ShortInput 
                        name="recordDateStart" 
                        :initialValue="taskEndDate"
                        customClass="date"
                        ref="recordDateStart"
                        :max="DateTime.now().plus({ years: 5 }).toISODate()"
                        :min="DateTime.now().toISODate()"
                        type="date"
                        v-model="taskEndDate"
                    />                   
                </div> 
            </div>
            <div class="si-box" v-if="isTask" style="width: fit-content;">
                <p class="form-lbl" style="font-size: 14px;">対応時間</p>
                <div style="display: flex;gap: 10px;margin-top: 10px;">
                    <OptionSelector 
                        :options="avialAbleHours"
                        unit="時間"
                        ref="taskTimeHour"
                        name="taskTimeHour"
                        rules="required"
                        v-model="tasktime.hours"
                    />
                    <OptionSelector
                        :options="avialAbleMinutes"
                        unit="分"
                        ref="taskTimeMinute"
                        name="taskTimeMinute"
                        rules="required"
                        v-model="tasktime.minutes"
                    />
                </div>
            </div>
            <div class="si-box" v-if="isTask && ((auth.user && auth.user.position_id && auth.user.position_id <= 6 )|| auth.activeUser.id === 610)">
                <div class="switchLabel">
                    <p class="form-lbl" style="white-space: nowrap;font-size: 14px;">グラウドナイン</p>
                </div>
                <div class="selectSwitchArea" style="display: flex;width: 100%;">    
                    <input v-model="glowdNine" type="checkbox" id="glowdNine">
                    <label for="glowdNine" style="min-width: 80px;" class="cursor-pointer"><span></span>
                        <div class="switch-toggle"></div>
                    </label>
                    
                </div>  
            </div> 
            <div class="si-box" v-if="glowdNine">
                <MemberSelector 
                    placeHolder="グラウドナインメンバー"
                    name="nineMembers"
                    :closeOnSelect="false"
                    :multiple="true"
                    v-model="glowdNineUsers"  
                    :options="filterUsers"
                    :key="filterUsers.length"
                />
            </div> 
            <div class="si-box" v-if="isTask">
                <div class="switchLabel">
                    <p class="form-lbl" style="white-space: nowrap;font-size: 14px;">スケジュールで表示させますか</p>
                </div>
                <div class="selectSwitchArea" style="display: flex;width: 100%;">    
                    <input v-model="syncToSchedule" @change="setTaskTitle" type="checkbox" id="sync_to_schedule">
                    <label for="sync_to_schedule" style="min-width: 80px;" class="cursor-pointer"><span></span>
                        <div class="switch-toggle"></div>
                    </label>
                    
                </div>  
            </div>
            <div class="si-box" v-if="syncToSchedule">
                <ShortInput 
                    name="taskTitle" 
                    placeHolder="タイトルを入力（必須）" 
                    :rules="'required|max:50'"
                    :initialValue="taskTitle"
                    customClass="full"
                    ref="taskTitleRef"
                    type="text"
                    v-model="taskTitle"
                />
            </div>
            
            <div class="si-box" v-if="isTask">
                <div class="switchLabel">
                    <p class="form-lbl" style="white-space: nowrap;font-size: 14px;">承認設定</p>
                </div>
                <div class="selectSwitchArea" style="display: flex;width: 100%;">    
                    <input v-model="supervisorSelected" type="checkbox" id="approver_select">
                    <label for="approver_select" style="min-width: 80px;" class="cursor-pointer"><span></span>
                        <div class="switch-toggle"></div>
                    </label>
                    
                </div>  
            </div> 
            <div class="si-box" v-if="supervisorSelected">
                <MemberSelector 
                    placeHolder="承認者ー選択"
                    name="taskApprover"
                    ref="taskApprover"
                    :closeOnSelect="false"
                    :multiple="true"
                    v-model="setSupervisor"  
                    :options="boardMembers"                  
                />
            </div>
            
            

            <div class="si-box">
                <LoaderButton @triggered="taskCreate" :loading="loading" content="保存する"/>
            </div> 
        </div>
    </div>   
</template>
<script setup lang="ts">

import LoaderButton from '@/components/Global/LoaderButton.vue';
import LongInput from '@/components/Form/LongInput.vue';
import MemberSelector from '@/components/Form/MemberSelector.vue';
import ShortInput from '@/components/Form/ShortInput.vue';
import { computed, onMounted, ref, useTemplateRef, watch } from 'vue';
import { useAuthUserStore } from '@/store/auth'
import { useSharingDataStore } from '@/store/sharingData'
import OptionSelector from '@/components/Form/OptionSelector.vue';
import { DateTime } from 'luxon';
import { useApi } from '@/composables/api';
import { useBoardList } from '@/composables/board';
import { TaskUser, User } from '@/interface/globalInterface';
    const sharingData = useSharingDataStore()
    const auth = useAuthUserStore()

    const props = defineProps(['editTaskData'])
    const emit = defineEmits(['close', 'getBoardTasks'])
    const content = ref(props.editTaskData ? props.editTaskData.remarks : '')
    const qualified_users = ref(props.editTaskData ? props.editTaskData.executors : [auth.activeUser]) 
    const supervisors = ref(props.editTaskData && props.editTaskData.supervisors ? props.editTaskData.supervisors : [])
    const loading = ref(false)
    const endDateComp = computed(() => {
        return props.editTaskData && props.editTaskData.end_at ? props.editTaskData.end_at : DateTime.now().toISODate()
    }) 
    const taskEndDate = ref(endDateComp.value)
    const supervisorSelected = ref(props.editTaskData && props.editTaskData.supervisors.length ? true : false)
    const taskMembers = useTemplateRef('taskMembers')
    const taskApprover = ref(null)
    const glowdNine = ref(props.editTaskData?.glowd_nine ? true : false)
    const syncToSchedule = ref(props.editTaskData?.sync_to_schedule ? true : false)
    const isTask = ref(props.editTaskData && props.editTaskData.end_at ? true : false)
    const taskTitle = ref(props.editTaskData?.title ? props.editTaskData.title : '')
    const taskTitleRef = useTemplateRef('taskTitleRef')
    
    const { openedBoard } = useBoardList()
    const api = useApi()
    const dateErrors = ref([])
    const tasktime = ref({
        hours: props.editTaskData && props.editTaskData.response_time ? Math.floor(props.editTaskData.response_time / 60) : 1,
        minutes: props.editTaskData && props.editTaskData.response_time ? props.editTaskData.response_time % 60 : 0
    })
    onMounted(() => {
        if(!props.editTaskData && sharingData.active){
            content.value = sharingData?.message?.message
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
    const avialAbleHours =  Array.from({ length: 11 }, (_, index) => index)
    const avialAbleMinutes = [0, 15, 30, 45]
    
    const glowdNineUsers = ref(props.editTaskData?.executors?.filter((member: TaskUser) => member.pivot.glowd_nine === 1) ?? [])
    const setTaskTitle = () => {
        syncToSchedule.value ? taskTitle.value = content.value.slice(0, 10) : taskTitle.value = ''
    }
    
    const setExecutor = computed({
        get(){
            return qualified_users.value
        },
        set(value){
            qualified_users.value = value
            supervisors.value = supervisors.value.filter((member: TaskUser) => 
                !value.some((user: TaskUser) => user.id === member.id)
            )
        }
    })
    const setSupervisor = computed({
        get(){
            return supervisors.value
        },
        set(value){
            supervisors.value = value
            qualified_users.value = qualified_users.value.filter((member: TaskUser) => 
                !value.some((user: TaskUser) => user.id === member.id)
            )
        }
    })
    const boardMembers = computed(() => {
        if(openedBoard.value){
            
            return openedBoard.value.board_to_users.map(ob => ob.user).filter(user => user.on_leave == 0)
            
        }
        return []
    })
    const filterUsers = computed(() => {
       return qualified_users.value.filter((user: TaskUser) => ((user.position_id &&  user.position_id < 13 )|| user.position_id === 16) 
       && user.id !== auth.activeUser.id) 
    })

    watch(qualified_users, (newVal, oldVal) => {
        const updatedUsers = filterUsers.value;
        glowdNineUsers.value = glowdNineUsers.value.filter((user: TaskUser) => updatedUsers.some((updatedUser: TaskUser) => updatedUser.id === user.id));
    });
    watch(glowdNine, (newVal) => {
        if(newVal){
            glowdNineUsers.value = filterUsers.value;
        } else {
            glowdNineUsers.value = []
        }
    })
    const allSelected = computed(() => {
        return boardMembers.value.length == qualified_users.value.length
    }) 
    const taskCreate = async() => {
        if(!openedBoard.value) return
        const val = await taskMembers.value?.validate() || {valid: false}
        let result = true
        if(syncToSchedule.value){
            const titleVal = await taskTitleRef.value?.validate() || {valid: false}
            result = result && titleVal.valid
        }
        if((!val.valid || dateErrors.value.length) && result) return
        const params = {            
            qualified_users: qualified_users.value.map((ob: TaskUser) => ob.id),
            remarks: content.value,
            task_end_date: isTask.value ? taskEndDate.value : '',
            board_id: openedBoard.value.id,
            edit_id: props.editTaskData ? props.editTaskData.id : null,
            supervisors: supervisorSelected.value ? supervisors.value.map((ob: TaskUser) => ob.id) : [],
            response_time: tasktime.value,
            sync_to_schedule: syncToSchedule.value,
            title: taskTitle.value,
            glowd_nine: glowdNine.value,
            glowd_nine_users: glowdNineUsers.value ? glowdNineUsers.value.map((ob: TaskUser) => ob.id) : [],
        };

        await api.post('/add_board_task', params, {
            toast: '作成しました。',
            loadingRef: loading,
        })            
        emit('close', true)
        
    }
    const selectAll = (event: Event) => {
        if(taskMembers.value){
            taskMembers.value.selectAll((event.target as HTMLInputElement).checked)
        }
    }
</script>
<style scoped>
.checkbox-container {
  display: inline-block;
}

.checkbox-container input[type="checkbox"] {
  display: none;
}

.checkbox-container label {
  display: inline-block;
  width: 40px;
  height: 40px;
  line-height: 40px;
  text-align: center;
  background-color: var(--background-color);
  color: var(--primary-color);
  cursor: pointer;
  transition: all 0.1s ease;
  font-size: 14px;
}

.checkbox-container label.active {
    color: var(--background-color);
    background-color: var(--primary-color);
}

.t-date{
    margin: 0 auto;
    padding: 0 15px;
    border: 1px solid var(--primary-color);
    color: inherit;
    font-size: 16px;
    line-height: 1.6;
    transition: border 0.3s ease;
    min-height: 40px;
    text-align: center;
    font-size: 14px;
    width: fit-content;
}

</style>