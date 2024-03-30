<template>
    <div class="work-root">
        
        <div class="work-header" ref="headerEl">
            <WorkHeader
                :workGroups="workGroups"
                :usersCheckArray="usersCheckArray"
                v-model="usersCheckArray"
                @selectShift="selectShift"
                @confirmAttendance="confirmAttendance"
                @todayScroll="todayScroll"
                @toBottomScroll="toBottomScroll"
            />
            <div class="work-monthpicker">
                <div @click="shiftMonth(-1)" class="work-prevmonth">
                    <svg version="1.1" width="13" height="13" viewBox="0 0 20 32" xmlns="http://www.w3.org/2000/svg">
                        <path d="M0.775 17.789c1.305 1.166 2.612 2.332 3.927 3.486 1.311 1.161 2.634 2.308 3.953 3.46 1.316 1.156 2.646 2.296 3.973 3.439 1.33 1.139 2.667 2.273 4.015 3.394 0.662 0.551 1.647 0.52 2.272-0.107 0.65-0.654 0.619-1.725-0.020-2.393-1.198-1.253-2.407-2.495-3.621-3.729-1.232-1.245-2.462-2.492-3.704-3.725-0.902-0.9-1.803-1.802-2.707-2.699-0.033-0.032-0.055-0.069-0.072-0.106-0.045-0.036-0.082-0.080-0.111-0.129-0.069-0.047-0.129-0.117-0.176-0.216-0.021-0.047-0.044-0.092-0.066-0.136-0.12-0.062-0.214-0.168-0.246-0.325-0.001-0.005-0.002-0.009-0.003-0.014-0.104-0.157-0.187-0.327-0.254-0.505-0.109-0.185-0.182-0.388-0.226-0.601-0.002-0.012-0.005-0.024-0.007-0.036-0.016-0.085-0.028-0.172-0.036-0.259-0.195-0.593-0.26-1.183 0.030-1.653 0.006-0.157 0.067-0.277 0.157-0.361 0.019-0.050 0.039-0.099 0.063-0.149 0.040-0.084 0.1-0.145 0.17-0.188 0.008-0.015 0.019-0.028 0.028-0.042 0.032-0.13 0.106-0.228 0.202-0.293 0.072-0.145 0.157-0.287 0.26-0.43 0.046-0.063 0.101-0.113 0.163-0.151 0.018-0.020 0.037-0.038 0.059-0.054 0.014-0.059 0.044-0.116 0.094-0.165 0.9-0.888 1.797-1.782 2.699-2.672 1.244-1.231 2.476-2.475 3.714-3.717l1.843-1.871 1.832-1.885c0.655-0.681 0.669-1.793-0.044-2.48-0.652-0.631-1.693-0.624-2.385-0.038l-1.964 1.66-1.995 1.71c-1.32 1.149-2.648 2.293-3.962 3.45s-2.636 2.308-3.943 3.474c-1.311 1.159-3.284 2.806-4.106 3.689s-0.792 2.492 0.191 3.369z"></path>
                    </svg>
                </div>
                <MonthPicker
                    :selectedMonth="selectedMonth"
                    :selectedYear="selectedYear"
                    :right="windowWidth < 425 ? 'auto' : '0'" 
                    @setDate="setDate"
                />
                <div @click="shiftMonth(1)" class="work-nextmonth">
                    <svg version="1.1" width="13" height="13" viewBox="0 0 20 32" xmlns="http://www.w3.org/2000/svg" style="transform: rotate(180deg);">
                        <path d="M0.775 17.789c1.305 1.166 2.612 2.332 3.927 3.486 1.311 1.161 2.634 2.308 3.953 3.46 1.316 1.156 2.646 2.296 3.973 3.439 1.33 1.139 2.667 2.273 4.015 3.394 0.662 0.551 1.647 0.52 2.272-0.107 0.65-0.654 0.619-1.725-0.020-2.393-1.198-1.253-2.407-2.495-3.621-3.729-1.232-1.245-2.462-2.492-3.704-3.725-0.902-0.9-1.803-1.802-2.707-2.699-0.033-0.032-0.055-0.069-0.072-0.106-0.045-0.036-0.082-0.080-0.111-0.129-0.069-0.047-0.129-0.117-0.176-0.216-0.021-0.047-0.044-0.092-0.066-0.136-0.12-0.062-0.214-0.168-0.246-0.325-0.001-0.005-0.002-0.009-0.003-0.014-0.104-0.157-0.187-0.327-0.254-0.505-0.109-0.185-0.182-0.388-0.226-0.601-0.002-0.012-0.005-0.024-0.007-0.036-0.016-0.085-0.028-0.172-0.036-0.259-0.195-0.593-0.26-1.183 0.030-1.653 0.006-0.157 0.067-0.277 0.157-0.361 0.019-0.050 0.039-0.099 0.063-0.149 0.040-0.084 0.1-0.145 0.17-0.188 0.008-0.015 0.019-0.028 0.028-0.042 0.032-0.13 0.106-0.228 0.202-0.293 0.072-0.145 0.157-0.287 0.26-0.43 0.046-0.063 0.101-0.113 0.163-0.151 0.018-0.020 0.037-0.038 0.059-0.054 0.014-0.059 0.044-0.116 0.094-0.165 0.9-0.888 1.797-1.782 2.699-2.672 1.244-1.231 2.476-2.475 3.714-3.717l1.843-1.871 1.832-1.885c0.655-0.681 0.669-1.793-0.044-2.48-0.652-0.631-1.693-0.624-2.385-0.038l-1.964 1.66-1.995 1.71c-1.32 1.149-2.648 2.293-3.962 3.45s-2.636 2.308-3.943 3.474c-1.311 1.159-3.284 2.806-4.106 3.689s-0.792 2.492 0.191 3.369z"></path>
                    </svg>
                </div>
            </div>
        </div>
            <WorkRecords 
                :currentDay="currentDay"
                :usersData="relocateUsers"
                :monthAverage="monthAverage"
                :selectedMonth="selectedMonth"
                :selectedYear="selectedYear"
                :records="recordsArray"
                :loading="loading"
                :headerHeight="headerHeight"
                @timeStampStart="timeStampStart"
                @timeStampEnd="timeStampEnd"
                @timeStampEdit="timeStampEdit"
                @timeStampDelete="timeStampDelete"
                @reload="reload"
            />
        
            <Transition name="modalFade"> 
                <WorkShifts 
                    v-if="shiftModal"
                    :selectedMonth="selectedMonth"
                    :selectedYear="selectedYear"
                    :usersCheckArray="usersCheckArray"
                    :usersData="usersData"
                    :startDate="startDate"
                    @closeModal="shiftModal = false"
                    @reload="reload"
                />
            </Transition>
            <Transition name="modalFade"> 
                <WorkAttendance
                    v-if="shiftAttendance"
                    :selectedYear="selectedYear"
                    :selectedMonth="selectedMonth"
                    :usersCheckArray="usersCheckArray"
                    @closeModal="shiftAttendance = false"
                />
            </Transition>
            <Transition name="modalFade"> 
                <WorkReport
                    v-if="reportModal"
                    @reload="reload"
                    @closeModal="closeModal"
                    :item="editData"
                />
            </Transition>
    </div>
</template>

<script setup>
    import WorkHeader from './WorkHeader.vue'
    import MonthPicker from '../Global/MonthPicker.vue'
    import WorkRecords from './WorkRecords.vue'
    import WorkShifts from './WorkShifts.vue'
    import WorkAttendance from './WorkAttendance.vue'
    import WorkReport from './WorkReport.vue'
    import moment from 'moment'
    import { computed, onMounted, ref, provide, inject, watch } from 'vue'
    import { useRoute } from 'vue-router'
    import { useAuthUserStore } from '@/store/auth'
    import { useElementSize } from '@vueuse/core'
    const auth = useAuthUserStore()
    const { confirm, notify } = inject('dialog')
    const route = useRoute()
    const selectedYear = ref(moment().year())
    const selectedMonth = ref(moment().month())
    const currentDay = ref(moment().format('YYYY-MM-DD'))
    const usersCheckArray =  ref([Number(auth.id)])
    const shiftModal = ref(false)
    const shiftAttendance = ref(false)
    const workGroups = ref([])
    const usersData = ref([])
    const monthAverage = ref([])
    const windowWidth = ref(window.innerWidth)
    const startDate = ref('')
    const reportModal = ref(false)
    const todayStartTime = ref(null)
    const todayEndTime = ref(null)
    const customFieldData = ref([])
    const customInfo = ref([])
    const recordsArray = ref([])
    const headerEl = ref(null)
    const editData = ref(null)
    onMounted(async() => {
        const query = route.query
        if(query.user_id){
            usersCheckArray.value = [Number(query.user_id)]
        }
        
        getWorkData()
        // await getShiftData()
        getWorkGroup()
        getCustomFields()
        getUsersRecords(0)
        if(query.startDate){
            startDate.value = query.startDate
            selectShift()
        }
    })

    watch(() => usersCheckArray.value,  () => {
        getUsersRecords()
        getWorkData()
    })
    const headerHeight = computed(() => {
        const { height } = useElementSize(headerEl)
        return height
    })
    const relocateUsers = computed(() => {
        const authUserId = auth.id;
        const checkedUserArray = usersCheckArray.value;
        const slicedUsersData = usersData.value.slice(); 
        if (checkedUserArray.includes(authUserId)) {
            checkedUserArray.unshift(checkedUserArray.splice(checkedUserArray.indexOf(authUserId), 1)[0]);
        }
        slicedUsersData.sort((a, b) => checkedUserArray.indexOf(a.id) - checkedUserArray.indexOf(b.id));

        return slicedUsersData;
    })
    
    const closeModal = () =>{
        reportModal.value = false
        customFieldData.value = []
    }
    
    const getCustomFields = async() => {
        const params = {
            app_name : 'work'
        };

        try{
            const response = await axios.post('/custom_field_data', params)
            customInfo.value = response.data
        }catch (e){
            notify(e.response?.data.message || e?.message || 'エラーが発生しました。') 
        }
    }
    const timeStampStart = async(data) => {
        const month = selectedMonth.value + 1
        if(data){
            if(data?.shift?.shift_type.id == 3){
                notify('計画有給設定しているため日報作成ができません。')
            }else{
                var date = new Date(); 
                var minutes = date.getMinutes();
                var quarterHours = Math.ceil(minutes / 15);
                date.setMinutes(quarterHours * 15);
                date.setSeconds(0);
                var hours = date.getHours();
                var minutes = date.getMinutes();
                hours = hours < 10 ? '0' + hours : hours;
                minutes = minutes < 10 ? '0' + minutes : minutes;
                let time = hours + ':' + minutes + ':00'
                todayStartTime.value = time
                const params = {
                    start_time : time,
                    day : currentDay.value,
                }
                try{
                    await axios.post('/daily_report_add', params)
                    reload()
                }catch (e){
                    notify(e.response?.data.message || e?.message || 'エラーが発生しました。') 
                }
            }
        }else{
            notify(month + '月の勤怠予定を入力してください。') 
        }
    }
    const timeStampEnd = async() => {
        var date = new Date();
        var minutes = date.getMinutes();
        var rounded = Math.floor(minutes / 15) * 15;
        date.setMinutes(rounded);
        date.setSeconds(0);
        var hours = date.getHours();
        var minutes = date.getMinutes();

        hours = hours < 10 ? '0' + hours : hours;
        minutes = minutes < 10 ? '0' + minutes : minutes;
        let time = hours + ':' + minutes + ':00'
        todayEndTime.value = time
        const params = {
            end_time : time,
            day : currentDay.value,
        }
        const answer = await confirm('本日の勤務を終業しますか。')
        if(!answer) return
        try{
            const response = await axios.post('/daily_report_add', params)
            await getUsersRecords()
            const record = recordsArray.value.find(ob => ob.user_id == response.data.user_id && ob.day_full == response.data.day)
            if(record){
                timeStampEdit(record)
            }
        }catch (e){
            notify(e.response?.data.message || e?.message || 'エラーが発生しました。') 
        } 
    }
    const timeStampEdit = (data) => {
        const month = selectedMonth.value + 1
        if(data?.shift){
            if(data?.shift?.shift_type?.id == 3){
                notify('計画有給設定しているため日報作成ができません。')
            }else{
                editData.value = data
                reportModal.value = true
            }
        }else{
            notify(month + '月の勤怠予定を入力してください。') 
        }
        
    }
    const timeStampDelete = async(data) => {
        const answer = await confirm(`${data.day_full}の日報を削除しますか。`)
        if(!answer) return
        const params = {
            date : data.day_full,
            userId: data.user_id,
        }
        try{
            await axios.post('/delete_time_card', params)
            reload()
        }catch (e){
            notify(e.response?.data.message || e?.message || 'エラーが発生しました。')     
        } 
    }
    const reload = () => {
        if(reportModal.value){
            closeModal()
        }
        if(usersCheckArray.value.length > 0){
            getWorkData()
            getUsersRecords()            
            // getShiftData()
        }else{
            notify('メンバーを選択してください。')
        }       
    }
    const setDate = (date) => {
        selectedYear.value = date.year
        selectedMonth.value = date.month - 1
        reload()
    }
    const getWorkGroup = async() => {
        try{
            const response = await axios.post('/get_work_group', {id: auth.activeUser.id})
            workGroups.value = response.data
        }catch (e){
            notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
        }
        
    }
    const getWorkData = async() => {
        let yearMonth = moment([selectedYear.value, selectedMonth.value]).format('YYYY-MM')
        const params = {
            current_date : yearMonth,
            work_group : usersCheckArray.value,
        }
        try{
            const response = await axios.post('/get_work_data', params)
            usersData.value = response.data.user_data
            monthAverage.value = response.data.month_average
        }catch (e){
            notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
        }
    }
    // const getShiftData = async() => {
    //     let yearMonth = moment([selectedYear.value, selectedMonth.value]).format('YYYY-MM')
    //     const params = {
    //         current_date : yearMonth,
    //         work_group : usersCheckArray.value
    //     }
    //     try{
    //         const response = await axios.post('/get_shift_data', params)
            
    //             remainingDays.value = response.data.remaining_days
    //             workTemp.value = response.data.workTemp
    //             shiftTypes.value = response.data.shift_type
    //             shiftRecords.value = response.data.shift_record
    //             kintone_data.value = response.data.kintone_data
            
    //     }catch (e){
    //         notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
    //     }
    // }
    const selectShift = async() => {
        if(usersCheckArray.value.length > 1){
            notify('メンバーが複数選択されています。勤怠予定はメンバーを1人のみ選択してください。') 
        }else if(usersCheckArray.value[0] == auth.activeUser.id || auth.activeUser.id == 608 || auth.activeUser.id == 610){
            shiftModal.value = true
        }
    }
    const confirmAttendance = async() => {
        
        if(usersCheckArray.value.length > 1){
            notify('メンバーが複数選択されています。勤怠確定はメンバーを1人のみ選択してください。')
        }else if(usersCheckArray.value[0] == auth.activeUser.id || auth.activeUser.id == 608 || auth.activeUser.id == 610){
            shiftAttendance.value = true
        }
    }    
    const shiftMonth = (val) => {
        const current = moment([selectedYear.value, selectedMonth.value])
        current.add(val, 'month')
        selectedMonth.value = current.month()
        selectedYear.value = current.year()
        reload()
    }
    const loading = ref(0)
    const getUsersRecords = async(init) => {
        let yearMonth = moment([selectedYear.value, selectedMonth.value]).format('YYYY-MM')
        const params = {
            current_date : yearMonth,
            work_group : usersCheckArray.value
        }
        recordsArray.value = await axios.post('get_shift_data_table', params).then(res => res.data)      
        
        if(init == 0){
            loading.value ++
            setTimeout(() => {
                todayScroll()
            }, 50);
        }else{
            loading.value ++
        }       
        

    }
    const todayScroll = () => {
        let scrollPosition = document.querySelector('.today');
        if (scrollPosition) {
            scrollPosition.scrollIntoView({ behavior: 'instant', block: 'start' });
        } 
    }
    const toBottomScroll = () => {
        let scrollInto = document.getElementById('bottomTotal');
        scrollInto.scrollIntoView({ behavior: 'instant', block: 'start' });
    }
    provide('customInfo', customInfo)
    provide('getUsersRecords', getUsersRecords)
</script>