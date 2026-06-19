<template>
    <div>
        <div style="display: flex;align-items: center;justify-content: space-between;">
            <template v-if="item.day">
                <p>{{ item.month }}月{{ item.day }}日の日報を申請する</p>
            </template>
            <template v-else>
                <p>{{ item.value ? '今月の勤怠予定' : '来月の勤怠予定'}}</p>
            </template>
            
            <div>
                <button class="shift-button" v-on:click="timeCardAdd(item)">作成</button>
            </div>
        </div>
        
        <WorkReport
            v-if="reportModal"
            @closeModal="reportModal = false"
            @reload="reload"
            :item="editData"
            
        />
        <WorkShifts 
            v-if="shiftModal"
            :selectedMonth="selectedMonth"
            :selectedYear="selectedYear"
            :usersData="[auth.user]"
            :notSubmitted="true"
            :chosenId="auth.id"
            @closeModal="shiftModal = false"
            @reload="reload"
        />
    </div>
</template>



<script setup>
import WorkReport from './WorkReport.vue'
import WorkShifts from './WorkShifts.vue';
import { ref, inject } from 'vue';
import { useAuthUserStore } from '@/store/auth'
import { DateTime } from 'luxon';   
import { useDialog } from '@/composables/dialog';
    const auth = useAuthUserStore()
    const props = defineProps(['item'])
    const { ping } = useDialog()
    const customFieldData = ref([])
    const workGroups = ref([])
    const selectedYear = ref(props.item ? props.item.year : DateTime.now().year)
    const selectedMonth = ref(props.item ? props.item.month : DateTime.now().month)
    const reportModal = ref(false)
    
    const { notSubmitted, nextMonthShift } = inject('checkWork')
    const shiftModal = ref(false)
    const editData = ref(null)
    const timeCardAdd = async(item) => {
        if(item.day){
            const { 
                value, 
                shiftStartTime, 
                shiftEndTime, 
                shiftDepartmentId,
                shiftType,
                shiftOverTimeRequest, 
                shiftStatus,
                customData,
                costs,
                work_group_id,
                user_id,
                timecard_id,
                timecard_status
            } = item;
            if(shiftStatus === 2) {
                ping('勤怠予定は承認されていません。') 
                return
            }
            const hasExistingTimeCard = Boolean(timecard_id)
            editData.value = {
                day_full : value,
                user_id : user_id ?? auth.id,
                work_time_day: auth.user.work_time_day,
                user_code: auth.user.user_code,
                work_type: auth.user.work_type,
                shift : {
                    start_time: shiftStartTime,
                    end_time: shiftEndTime,
                    department_id: shiftDepartmentId ?? work_group_id ?? null,
                    shift_type: shiftType ? { id: shiftType } : null,
                    overtime_request: shiftOverTimeRequest
                },
                time_card: hasExistingTimeCard ? {
                    id: timecard_id,
                    status_flag: timecard_status,
                    custom_field_data_records: customData ?? [],
                    work_group_id: work_group_id,
                    timecard_costs: costs,
                    user_id: user_id ?? auth.id,
                    project_segments: item.project_segments ?? []
                } : null,
                total_break_time: 0,
                ability: {
                    daily_report_create: !hasExistingTimeCard,
                    daily_report_modify: hasExistingTimeCard,
                }
            }
            reportModal.value = true

        }else{
            shiftModal.value = true
        }
    }
      
    const reload = () => {
        notSubmitted()
        nextMonthShift()
        reportModal.value = false
    }
</script>
