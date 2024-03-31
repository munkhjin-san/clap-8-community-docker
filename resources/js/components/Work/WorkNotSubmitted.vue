<template>
    <div>
        <div style="display: flex;align-items: center;justify-content: space-between;">
            <template v-if="item.day">
                <p>{{ item.month }}月{{ item.day }}日の日報を申請する</p>
            </template>
            <template v-else>
                <p>今月の勤怠予定</p>
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
            :shiftTypes="shiftTypes"
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
    import holiday_jp from '@holiday-jp/holiday_jp'
    import { useAuthUserStore } from '@/store/auth'
    const auth = useAuthUserStore()
    const { notify } = inject('dialog')
    const selectedYear = ref(moment().year())
    const selectedMonth = ref(moment().month())
    const props = defineProps(['item'])
    const todayStartTime = ref(null)
    const todayEndTime = ref(null)
    const chosenDate = ref(null)
    const reportModal = ref(false)
    const shiftTypes = ref([])
    const customFieldData = ref([])
    const planned_record = ref([])
    const getNotSubmitted = inject('getNotSubmitted')
    const shiftModal = ref(false)
    const overTimeRequest = ref([])
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
            shiftTypes.value = response.data.shift_type
            planned_record.value = response.data.planned_record
        }catch (e){
            notify(e.response?.data.message || e?.message || 'エラーが発生しました。') 
        }
    }   
    const reload = () => {
        getNotSubmitted()
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
    const formatTime = (time, val) => {
        if(!time) return '--'
        
        if(/^([01]\d|2[0-3]):[0-5]\d:[0-5]\d$/.test(time)){
            var date = new Date("2000-01-01T" + time);
            var minutes = date.getMinutes();
            // if(val == 'start'){
            //     var rounded = Math.ceil(minutes / 15) * 15;
            // }else if(val == 'end'){
            //     var rounded = Math.floor(minutes / 15) * 15;
            // }
            date.setMinutes(minutes);
            date.setSeconds(0);
            var hours = date.getHours();
            var minutes = date.getMinutes();

            hours = hours < 10 ? '0' + hours : hours;
            minutes = minutes < 10 ? '0' + minutes : minutes;
            let roundedTime = hours + ':' + minutes
            return roundedTime

        }else if(time === '打刻なし'){
            return time
        }
    }
    provide('customInfo', customFieldData)
</script>
