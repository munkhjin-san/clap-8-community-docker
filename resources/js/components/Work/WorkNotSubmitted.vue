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
    import { onMounted, ref, inject, computed, provide } from 'vue';
    import moment from 'moment';
    import { useAuthUserStore } from '@/store/auth'
    import { getCustomFields, getWorkGroup } from '../../utils/workApi';
    const auth = useAuthUserStore()
    const { notify } = inject('dialog')
    const props = defineProps(['item'])
    const workGroups = ref([])
    const selectedYear = ref(props.item ? props.item.year : moment().year())
    const selectedMonth = ref(props.item ? props.item.month - 1 : moment().month())
    const reportModal = ref(false)
    const customFieldData = ref([])
    const { notSubmitted, nextMonthShift } = inject('checkWork')
    const shiftModal = ref(false)
    const editData = ref(null)
    onMounted(() => {
        fetchDatas()
    })
    const timeCardAdd = (item) => {
        if(item.day){
            const { value, shiftStartTime, shiftEndTime, shiftOverTimeRequest } = item;
            editData.value = {
                day_full : value,
                user_id : auth.id,
                work_time_day: auth.user.work_time_day,
                shift : {
                    start_time: shiftStartTime,
                    end_time: shiftEndTime,
                    overtime_request: shiftOverTimeRequest
                },
                time_card: {
                    custom_field_data_records: customFieldData.value
                }
            }
            reportModal.value = true
        }else{
            shiftModal.value = true
        }
    }
    const fetchDatas = async() => {
        try{
            workGroups.value = await getWorkGroup()
            customFieldData.value = await getCustomFields()
        }catch (e){
            notify(e?.message || 'エラーが発生しました。') 
        }
    }   
    const reload = () => {
        notSubmitted()
        nextMonthShift()
        reportModal.value = false
    }
    provide('customInfo', customFieldData)
    provide('workGroups', workGroups)
</script>
