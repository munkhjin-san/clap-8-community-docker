<template>
    <Modal @close="emit('close', false)">
        <template #title>
            <p>{{ preData.id ? 'タスクを編集する' : '新しいタスクを作成する'}}</p>
        </template>
        <template #content>    
            <div>                   
                <LongInput 
                    ref="taskContent"
                    placeHolder="メモ"
                    rules="required"
                    name="taskContent"
                    v-model="params.remarks"
                />                    
            </div>            
            
            <div class="si-box">
                <div class="switchLabel">
                    <p class="form-lbl" style="white-space: nowrap;font-size: 14px;">全員選択</p>
                </div>
                <div class="selectSwitchArea" style="display: flex;width: 100%;">    
                    <input @change="selectAll" type="checkbox" id="edit_all">
                    <label for="edit_all" style="min-width: 80px;" class="cursor-pointer"><span></span>
                        <div class="switch-toggle"></div>
                    </label>                    
                </div>  
            </div> 
            <div class="si-box">
                <MemberSelector 
                    placeHolder="メンバー選択"
                    rules="required"
                    name="taskMembers"
                    ref="taskMembers"
                    :multiple="true"
                    :closeOnSelect="false"
                    v-model="setExecutor" 
                    :options=" [...project?.manager ?? [], ...project?.members ?? [] ]"               
                />
            </div>     
            
            <div class="si-box">
                <p class="form-lbl" style="white-space: nowrap;font-size: 14px;">期限</p>
                <div style="display:flex;gap: 15px;">
                    <div style="display:flex;margin-top: 10px;position: relative;width:fit-content">                    
                        <ShortInput 
                            name="taskStart" 
                            customClass="date"
                            ref="taskStartRef"
                            type="date"
                            rules="required"
                            v-model="setStartTime"
                        />               

                    </div> 
                    <div style="display:flex;margin-top: 10px;position: relative;width:fit-content">                    
                        <ShortInput 
                            name="taskEnd" 
                            customClass="date"
                            ref="taskEndRef"
                            rules="required"
                            :max="DateTime.fromISO(params.start_at!).plus({years: 5 }).toISODate()?.toString()"
                            :min="DateTime.fromISO(params.start_at!).toISODate()?.toString()"
                            type="date"
                            v-model="params.end_at"
                        />                   
                    </div> 
                </div>
            </div>

            <div class="si-box">
                <SubTaskSection 
                    v-for="(_task, index) in params.sub_tasks" 
                    :key="params.executors.length"  
                    :sub-task-index="index" 
                    :user-options="params.executors"
                    :multiple="true"
                    @remove="(index) => params.sub_tasks.splice(index, 1)"
                    v-model:remarks="params.sub_tasks[index].remarks"
                    v-model:executors="params.sub_tasks[index].pre_executors"
                    v-model:start_at="params.sub_tasks[index].start_at"
                    v-model:end_at="params.sub_tasks[index].end_at"
                />
                <div style="display: flex;align-items: center;gap: 10px;font-size: 12px;cursor: pointer;" @click="params.sub_tasks.push({ remarks: '', comp_flag: 0, end_at: params.end_at, start_at: params.start_at})">
                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 32 32" style="fill:var(--primary-color)">
                        <path d="M30.044 14.14c-2.402-0.231-4.804-0.341-7.206-0.422-1.535-0.058-3.071-0.079-4.606-0.090-0.326-0.002-0.587-0.265-0.588-0.591-0.004-1.537-0.018-3.074-0.078-4.613-0.092-2.4-0.218-4.802-0.542-7.205-0.084-0.612-0.565-1.119-1.205-1.206-0.769-0.103-1.477 0.437-1.582 1.206-0.324 2.401-0.449 4.804-0.542 7.205-0.059 1.536-0.074 3.071-0.078 4.606-0.001 0.325-0.263 0.59-0.59 0.59-1.534 0.005-3.068 0.020-4.602 0.078-2.404 0.094-4.805 0.219-7.207 0.543-0.612 0.081-1.119 0.564-1.205 1.205-0.103 0.769 0.436 1.477 1.205 1.58 2.402 0.324 4.804 0.449 7.207 0.543 1.536 0.059 3.074 0.073 4.612 0.078 0.325 0.001 0.587 0.262 0.59 0.587 0.011 1.536 0.033 3.070 0.090 4.606 0.080 2.402 0.192 4.805 0.423 7.207 0.066 0.699 0.622 1.278 1.349 1.348 0.823 0.079 1.556-0.524 1.633-1.348 0.231-2.402 0.342-4.805 0.423-7.207 0.057-1.538 0.079-3.077 0.090-4.615 0.002-0.324 0.263-0.583 0.587-0.586 1.538-0.011 3.077-0.034 4.615-0.090 2.402-0.080 4.804-0.193 7.206-0.423 0.7-0.066 1.279-0.622 1.349-1.349 0.076-0.823-0.528-1.557-1.351-1.634z"></path>
                    </svg>
                    <span>サブタスク追加</span>
                </div>                
            </div>
            <div class="si-box">
                <LoaderButton @triggered="taskCreate" :loading="loading" content="保存する"/>
            </div> 
    </template>
</Modal> 
</template>
<script setup lang="ts">
import LoaderButton from '@/components/Global/LoaderButton.vue';
import LongInput from '@/components/Form/LongInput.vue';
import MemberSelector from '@/components/Form/MemberSelector.vue';
import ShortInput from '@/components/Form/ShortInput.vue';
import { computed, inject, onMounted, ref, reactive, useTemplateRef } from 'vue';
import { useAuthUserStore } from '@/store/auth'
import { DialogKey, DialogMethods, GanttProjectMethods, GanttProjectMethodsKey } from '@/interface/keys';
import axios from 'axios';
import { Dialog, Task, User } from '@/interface/globalInterface';
import { Project } from '@/interface/projectInterface';
import type { ComponentExposed } from 'vue-component-type-helpers'
import { DateTime } from 'luxon';
import SubTaskSection from './SubTaskSection.vue';
import { useBadgeStore } from '@/store/badge';
import Modal from '../Global/Modal.vue';
    const auth = useAuthUserStore()
    const props = defineProps<{
        preData: Partial<Task>
        project: Project
    }>()
    const {refreshProject} = inject(GanttProjectMethodsKey) as GanttProjectMethods
    const params = reactive({
        id: props.preData?.id ?? null,
        board_id: props.project.board_id,
        remarks: props.preData?.remarks ?? '',
        executors: props.preData?.executors ?? [],
        project_id: props.project.id,
        supervisors: props.preData?.supervisors?.map(ob => ob.id) ?? [],
        start_at: props.preData?.start_at ? DateTime.fromISO(props.preData?.start_at!).toISODate()?.toString() : DateTime.now().toISODate(),
        end_at: props.preData?.end_at ? DateTime.fromISO(props.preData?.end_at!).toISODate()?.toString() : DateTime.now().plus({day: 1}).toISODate(),
        sub_tasks: <Partial<Task>[]>[],
    })
    const badge = useBadgeStore()
    onMounted(async() => {
       
        
        if(!props.preData.id){
            taskMembers.value?.selectBy([auth.activeUser as User])
        }
        if(props.preData.id){
            params.sub_tasks = (props.preData?.sub_tasks ?? []).map(task => ({ ...task }));
            params.sub_tasks.map( subTask => {
                const sub = subTask.executors ??  []
                subTask.pre_executors = sub
            })            
        }
        console.log(params)
    })


    const emit = defineEmits(['close'])
    const loading = ref(false)
    const taskMembers = ref<InstanceType<typeof MemberSelector> | null>(null)
    const taskStartRef = ref<InstanceType<typeof ShortInput> | null>(null)
    const taskEndRef = ref<InstanceType<typeof ShortInput> | null>(null)
    const taskContent = ref<InstanceType<typeof LongInput> | null>(null)
    const subTaskMembers = useTemplateRef<ComponentExposed<typeof MemberSelector>[]>('subTaskMembersRef')
    const subTaskContentRef = useTemplateRef<ComponentExposed<typeof ShortInput>[]>('subTaskContentRef')
    const subTaskStartRef = useTemplateRef<ComponentExposed<typeof ShortInput>[]>('subTaskStartRef')
    const subTaskEndRef = useTemplateRef<ComponentExposed<typeof ShortInput>[]>('subTaskEndRef')
    const { notify, info } = inject<Dialog>('dialog') as DialogMethods;

    const setStartTime = computed({
        get(){
            return params.start_at
        },
        set(value){
            params.start_at = value
            if(DateTime.fromISO(params.end_at!) < DateTime.fromISO(value!)){
                params.end_at = value
            }
        }
    })
    const setExecutor = computed({
        get(){
            return params.executors
        },
        set(value){
            console.log(value)
            params.executors = value
            params.supervisors = params.supervisors.filter((member: any) => 
                !value.some((user: any) => user === member)
            )
            if(params.sub_tasks.length){
                params.sub_tasks = params.sub_tasks.map(subTask => ({
                    ...subTask,
                    pre_executors: subTask.pre_executors?.filter(user => value.some(val => val.id === user.id)) ?? [],
                }));

            }
        }
    })
    const valid = async() => {
      
        const allTargets = [taskMembers.value, taskStartRef.value, taskEndRef.value, taskContent.value, ];
        const m = allTargets.concat(subTaskMembers.value, subTaskContentRef.value, subTaskStartRef.value, subTaskEndRef.value)
        const possibleTargets = m.filter( r => r !== null)
        let result = true
        for(const target of possibleTargets){            
            const val = await target?.validate() || {valid:false}
            result = result && val.valid
        }
        return result
      
    }
    const taskCreate = async() => {
       
        if(loading.value) return
        const validation = await valid();
        if (!validation) return
        const validDate = DateTime.fromISO(params.end_at!) >=  DateTime.fromISO(params.start_at!)
        if(!validDate){
            notify('終了日は開始日より先にすることができません。')
            return 
        }
        try{
            loading.value = true
            const modifiedParams = {
                ...params,
                executors: params.executors.map((executor: { id: number }) => executor.id),
                sub_tasks: params.sub_tasks.map(subTask => ({
                    ...subTask,
                    pre_executors: subTask.pre_executors?.map((executor: {id: number}) => executor.id)
                }))
            };
            await axios.put('/task_item', modifiedParams)            
            info('保存しました。')
            refreshProject({})
            loading.value = false
            emit('close', true)           
            badge.getTaskBadge()
        }catch (e) {
            notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
            loading.value = false
        }

    }
    const selectAll = (event:Event) => {
        const target = event.target as HTMLInputElement
        if(taskMembers.value){
            taskMembers.value.selectAll(target.checked)
        }
    } 
</script>