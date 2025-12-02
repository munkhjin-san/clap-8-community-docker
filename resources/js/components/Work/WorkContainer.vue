<template>
    <div class="work-root">
        
        <div class="work-header" ref="headerEl">
            <WorkHeader
                :workGroups="workGroups"
                :selectedMonth="selectedMonth"
                v-model:users="usersCheckArray"
                v-model:vehicles="selectedVehicles"
                @selectShift="selectShift"
                @confirmAttendance="confirmAttendance"
                @todayScroll="todayScroll"
                @toBottomScroll="toBottomScroll"
                @approveShift="approvalModal = true"
            />
            <div class="work-monthpicker">
                <div @click="shiftMonth(-1)" class="work-prevmonth">
                    <svg version="1.1" width="13" height="13" viewBox="0 0 20 32" xmlns="http://www.w3.org/2000/svg">
                        <path d="M0.775 17.789c1.305 1.166 2.612 2.332 3.927 3.486 1.311 1.161 2.634 2.308 3.953 3.46 1.316 1.156 2.646 2.296 3.973 3.439 1.33 1.139 2.667 2.273 4.015 3.394 0.662 0.551 1.647 0.52 2.272-0.107 0.65-0.654 0.619-1.725-0.020-2.393-1.198-1.253-2.407-2.495-3.621-3.729-1.232-1.245-2.462-2.492-3.704-3.725-0.902-0.9-1.803-1.802-2.707-2.699-0.033-0.032-0.055-0.069-0.072-0.106-0.045-0.036-0.082-0.080-0.111-0.129-0.069-0.047-0.129-0.117-0.176-0.216-0.021-0.047-0.044-0.092-0.066-0.136-0.12-0.062-0.214-0.168-0.246-0.325-0.001-0.005-0.002-0.009-0.003-0.014-0.104-0.157-0.187-0.327-0.254-0.505-0.109-0.185-0.182-0.388-0.226-0.601-0.002-0.012-0.005-0.024-0.007-0.036-0.016-0.085-0.028-0.172-0.036-0.259-0.195-0.593-0.26-1.183 0.030-1.653 0.006-0.157 0.067-0.277 0.157-0.361 0.019-0.050 0.039-0.099 0.063-0.149 0.040-0.084 0.1-0.145 0.17-0.188 0.008-0.015 0.019-0.028 0.028-0.042 0.032-0.13 0.106-0.228 0.202-0.293 0.072-0.145 0.157-0.287 0.26-0.43 0.046-0.063 0.101-0.113 0.163-0.151 0.018-0.020 0.037-0.038 0.059-0.054 0.014-0.059 0.044-0.116 0.094-0.165 0.9-0.888 1.797-1.782 2.699-2.672 1.244-1.231 2.476-2.475 3.714-3.717l1.843-1.871 1.832-1.885c0.655-0.681 0.669-1.793-0.044-2.48-0.652-0.631-1.693-0.624-2.385-0.038l-1.964 1.66-1.995 1.71c-1.32 1.149-2.648 2.293-3.962 3.45s-2.636 2.308-3.943 3.474c-1.311 1.159-3.284 2.806-4.106 3.689s-0.792 2.492 0.191 3.369z"></path>
                    </svg>
                </div>
                <MonthPickerNew                   
                    v-model:month="selectedMonth"
                    v-model:year="selectedYear"
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
                :usersData="relocateUsers"
                :monthAverage="monthAverage"
                :selectedMonth="selectedMonth"
                :selectedYear="selectedYear"
                :records="recordsArray"
                :loading="loading"
                :headerHeight="headerHeight"
                :workGroups="workGroups"
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
                    :attendanceFlag="attendanceFlag"
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
                    @reload="reload"
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
            <Transition name="modalFade">
                <ShiftApproval 
                    v-if="approvalModal"
                    :selectedMonth="selectedMonth"
                    :selectedYear="selectedYear"
                    :workGroups="workGroups"
                    :usersCheckArray="usersCheckArray"
                    @closeModal="approvalModal = false, fetchShiftDataTable()"
                />
            </Transition>
            <Transition name="modalFade">
                <DepartmentField 
                    v-if="shiftForDepartment"
                    :shiftForDepartment="shiftForDepartment"
                    @close="shiftForDepartment = null, fetchShiftDataTable()"
                />
            </Transition>
    </div>
</template>

<script setup>
import WorkHeader from './WorkHeader.vue'
import WorkRecords from './WorkRecords.vue'
import WorkShifts from './WorkShifts.vue'
import WorkAttendance from './WorkAttendance.vue'
import WorkReport from './WorkReport.vue'
import ShiftApproval from './ShiftApproval.vue'
import { computed, onMounted, ref, provide, inject, watch, nextTick } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthUserStore } from '@/store/auth'
import { useElementSize } from '@vueuse/core'
import { getWorkGroup, getCustomFields, getWorkData, getShiftDataTable } from '../../utils/workApi'
import { useBreakTime } from '@/store/breakTime'
import DepartmentField from './DepartmentField.vue'
import { DateTime } from 'luxon'
import MonthPickerNew from '../Global/MonthPickerNew.vue'
import { useApi } from '@/composables/api'
import { useDialog } from '@/composables/dialog'
    const firstUser = computed(() => {
        return auth.id == 608 || auth.id == 610 ? [] : [Number(auth.id)]
    })
    const auth = useAuthUserStore()
    const route = useRoute()
    const selectedYear = ref(DateTime.now().year)
    const selectedMonth = ref(DateTime.now().month)
    const currentDay = ref(DateTime.now().toISODate())
    const usersCheckArray =  ref(firstUser.value)
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
    const recordsArray = ref([])
    const headerEl = ref(null)
    const editData = ref(null)
    const attendanceFlag = ref(false)
    const approvalModal = ref(false)
    const breakTimeStore = useBreakTime()
    const shiftForDepartment = ref(null)
    const selectedVehicles = ref([])
    const api = useApi()
    const { ask, ping, toast } = useDialog() 
    onMounted(async() => {
        const query = route.query
        if(query.user_id){
            usersCheckArray.value = [Number(query.user_id)]
        }
        fetchDatas()
        await fetchWorkData()
        fetchShiftDataTable(0)
        if(query.startDate){
            startDate.value = query.startDate
            selectShift()
        }
    })
    let isClearing = false
    watch(() => usersCheckArray.value, (newValue) => {
        if (isClearing) return
        if (newValue.length && selectedVehicles.value.length) {
            selectedVehicles.value = []
            isClearing = true
        }
        handleWatch()
    })

    watch(() => selectedVehicles.value, (newValue) => {
        if (isClearing) return
        if (newValue.length && usersCheckArray.value.length) {
            usersCheckArray.value = []
            isClearing = true
        }

        handleWatch()
    })
    const handleWatch = () => {
        const dataTable = document.querySelector('.v-table__wrapper')
        dataTable ? dataTable.scrollTop = 0 : ''
        fetchShiftDataTable()
        fetchWorkData()
        setTimeout(() => (isClearing = false), 0);
    }
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
    const addDepartmentOnly = async(data) => {
        shiftForDepartment.value = data
    }
    const timeStampStart = async(data) => {
        const month = selectedMonth.value
        if(data || data.position_id === 15 || data.position_id < 6){
            if(data?.shift?.shift_type.id == 3){
                ping('計画有給設定しているため日報作成ができません。')
            } else if(data?.shift?.shift_type?.id == 2){
                ping('休業日のため日報作成ができません。')
            } else if (data.shift?.status_flag == 2) {
                ping('勤怠予定は承認されていません。') 
            } else {
                let date = DateTime.now();
                let minutes = date.minute;
                let quarterHours = Math.ceil(minutes / 15);
                date = date.set({ minute: quarterHours * 15, second: 0 });

                let time = date.toFormat('HH:mm:ss');
                todayStartTime.value = time
                const params = {
                    start_time : time,
                    day : currentDay.value,
                }
               
                await api.post('/daily_report_add', params)
                reload()
                
            }
        }else{
            ping(month + '月の勤怠予定を入力してください。') 
        }
    }
    const timeStampEnd = async() => {
        let date = DateTime.now();
        let minutes = date.minute;
        let quarterHours = Math.floor(minutes / 15);
        date = date.set({ minute: quarterHours * 15, second: 0 });
        
        let time = date.toFormat('HH:mm:ss');
        todayEndTime.value = time
        const params = {
            end_time : time,
            day : currentDay.value,
        }
        const answer = await ask('本日の勤務を終業しますか。')
        if(!answer.value) return
     
        const response = await api.post('/daily_report_add', params)
        await fetchShiftDataTable()
        const record = recordsArray.value.find(ob => ob.user_id == response.user_id && ob.day_full == response.day)
        if(record){
            timeStampEdit(record)
        }
        
    }
    const timeStampBreak = async(data) => {
        const params = {
            break_start : DateTime.now().toFormat('HH:mm:ss'),
            record : data.time_card
        }
  
        await api.post('/daily_report_break', params)
        breakTimeStore.checkBreakTime()
        await fetchShiftDataTable()
        
    }
    const timeStampEdit = (data) => {
        const month = selectedMonth.value
        if(data?.shift || data.position_id === 15 || data.position_id < 6){
            if(data?.shift?.shift_type?.id == 3){
                ping('計画有給設定しているため日報作成ができません。')
            } else if(data?.shift?.shift_type?.id == 2){
                ping('休業日のため日報作成ができません。')
            } else if (data.shift?.status_flag == 2) {
                ping('勤怠予定は承認されていません。') 
            } else {
                editData.value = data
                reportModal.value = true
            }
        }else{
            ping(month + '月の勤怠予定を入力してください。') 
        }
        
    }
    const timeStampDelete = async(data) => {
        const params = {
            date : data.day_full,
            userId: data.user_id,
        }
        await api.post('/delete_time_card', params, {
            ask: `${data.day_full}の日報を削除しますか。`,
            toast: '日報を削除しました。'
        })
        reload()
    }
    const reload = async() => {
        if(reportModal.value){
            closeModal()
        }
        if(usersCheckArray.value.length > 0 || selectedVehicles.value.length > 0){
            await fetchWorkData()
            await fetchShiftDataTable()            
        }else{
            ping('メンバーを選択してください。')
        }       
    }
    const setDate = (date) => {
        selectedYear.value = date.year
        selectedMonth.value = date.month
        reload()
    }
    const fetchDatas = async () => {
        try{
            workGroups.value = await getWorkGroup()
            
        } catch (e){
            ping(e?.message || 'エラーが発生しました。') 
        }
    }
    const fetchWorkData = async () => {
        const yearMonth = DateTime.fromObject({year: selectedYear.value, month: selectedMonth.value}).toFormat('yyyy-MM')

        try {
            const workData = await getWorkData(yearMonth, usersCheckArray.value)
            usersData.value = workData.user_data
            monthAverage.value = workData.month_average
            attendanceFlag.value = workData.attendance_flag
        } catch (e){
            ping(e?.message || 'エラーが発生しました。') 
        }
    }
    const fetchShiftDataTable = async(init) => {
        const yearMonth = DateTime.fromObject({year: selectedYear.value, month: selectedMonth.value}).toFormat('yyyy-MM')

        recordsArray.value = await getShiftDataTable(yearMonth, usersCheckArray.value, selectedVehicles.value)
        if(init == 0){
            loading.value ++
            setTimeout(() => {
                todayScroll()
            }, 50);
        }else{
            loading.value ++
        }       
    }
    const modalSelect = computed(() => {
        return (usersCheckArray.value[0] == auth.activeUser.id || auth.activeUser.id == 608 || auth.activeUser.id == 610 || isAnyChecked15.value) && usersCheckArray.value.length == 1
    })
    const selectShift = async() => {
        if(usersCheckArray.value.length > 1){
            ping('メンバーが複数選択されています。勤怠予定はメンバーを1人のみ選択してください。') 
        } else if (modalSelect.value){
            shiftModal.value = true
        } else if (usersCheckArray.value.length == 0) {
            ping('メンバーを選択してください。')
        }
    }
    const confirmAttendance = async() => {
        
        if(usersCheckArray.value.length > 1){
            ping('メンバーが複数選択されています。勤怠確定はメンバーを1人のみ選択してください。')
        } else if (modalSelect.value){
            shiftAttendance.value = true
        } else if (usersCheckArray.value.length == 0) {
            ping('メンバーを選択してください。')
        }
    }    
    const inChargeProjects = computed(() => {
        const groups = workGroups.value ?? [];
        const userId = auth.activeUser?.id;
        if (!userId) return [];
        const idOf = (m) => (typeof m === 'object' && m && 'id' in (m)) ? (m).id : m;
        return groups.filter(p => Array.isArray(p.manager) && p.manager.some(m => idOf(m) === userId));
    });
    const toId = (x) => (x && typeof x === 'object' && 'id' in x ? x.id : x);

    const registeredIds = computed(() => {
        const projects = inChargeProjects.value ?? [];
        const members = projects.flatMap(p => Array.isArray(p.members) ? p.members : []);
        return new Set(
            members
            .filter(m => m && Number(m.position_id) === 15)
            .map(m => m.id)
        );
    });

    const selectedIds = computed(() =>
        (usersCheckArray.value ?? []).map(toId)
    );
    const isAnyChecked15 = computed(() =>
        selectedIds.value.some(id => registeredIds.value.has(id))
    );
    const shiftMonth = (val) => {
        const current = DateTime.fromObject({year: selectedYear.value, month: selectedMonth.value})
        const newDate = current.plus({months: val})
        selectedMonth.value = newDate.month
        selectedYear.value = newDate.year
        reload()
    }
    const loading = ref(0)
    
    const todayScroll = async() => {
        if(!DateTime.fromObject({year: selectedYear.value, month: selectedMonth.value}).hasSame(DateTime.now(), 'month')){
            selectedMonth.value = DateTime.now().month
            selectedYear.value = DateTime.now().year
            await reload()
        }
        await nextTick()
        let scrollPosition = document.querySelector('.today');
        
        if (scrollPosition) {
            scrollPosition.scrollIntoView({ behavior: 'instant', block: 'center' });
        }
        
    }
    const toBottomScroll = () => {
        let scrollInto = document.getElementById('bottomTotal');
        scrollInto.scrollIntoView({ behavior: 'instant', block: 'start' });
    }
    provide('fetchShiftDataTable', fetchShiftDataTable)
    provide('stamps', {
        edit: (item) => timeStampEdit(item),
        start: (item) => timeStampStart(item),
        stampDelete: (item) => timeStampDelete(item),
        end: (item) => timeStampEnd(item),
        takeBreak: (item) => timeStampBreak(item),
        addDepartmentOnly: (item) => addDepartmentOnly(item)
    })
</script>