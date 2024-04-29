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
            :filteredRecord="filteredRecord"
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
    const auth = useAuthUserStore()
    const { notify } = inject('dialog')
    const props = defineProps(['item'])

    const selectedYear = ref(props.item ? props.item.year : moment().year())
    const selectedMonth = ref(props.item ? props.item.month - 1 : moment().month())
    const reportModal = ref(false)
    const customFieldData = ref([])
    const planned_record = ref([])
    const { notSubmitted, nextMonthShift } = inject('checkWork')
    const shiftModal = ref(false)
    const editData = ref(null)
    onMounted(() => {
        getCustomFields()
        getShiftTypes()
    })
    
    const filteredRecord = computed(() => {
        let yearMonth = moment([selectedYear.value, selectedMonth.value]);
        return planned_record.value.filter(record => {
            let recordDate = moment(record.date);
            return recordDate.year() === yearMonth.year() && recordDate.month() === yearMonth.month();
        })
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
    const getShiftTypes = async() => {
        try{
            const response = await axios.get('/get_shift_types')
            planned_record.value = response.data.planned_record
        }catch (e){
            notify(e.response?.data.message || e?.message || 'エラーが発生しました。') 
        }
    }   
    const reload = () => {
        notSubmitted()
        nextMonthShift()
        reportModal.value = false
    }
    const getCustomFields = async() => {
        const params = {
            app_name : 'work'
        };

        try{
            const response = await axios.post('/custom_field_data', params)
            customFieldData.value = response.data
        }catch (e){
            notify(e.response?.data.message || e?.message || 'エラーが発生しました。') 
        }
    }
    provide('customInfo', customFieldData)
</script>
