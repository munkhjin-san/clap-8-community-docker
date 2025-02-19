<template>
    <div class="work-modal" @mousedown="emit('closeModal')">
        <div class="work-modal-inner" @mousedown.stop>
            <div class="recordFormTitle" style="z-index: 26;">
                <p style="font-size: 18px;">{{ shiftYear }}年{{ shiftMonth+1 }}月の勤怠予定</p>
                <div @click="emit('closeModal')" class="cursor-pointer flex items-center" style="margin: auto 0 auto auto;">
                    <svg class="modalWindowCloseButton" version="1.1" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 32 32">
                        <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                    </svg>
                </div>
            </div>
            <div class="shift-title" style="margin-bottom: 20px;">
                <div class="sub-tab-container gap-5">
                    <div @click="checkLeave = 0" style="padding: 10px 0;" :class="['sub-tab-item', { 'selected-sub-tab': checkLeave == 0}]">予定入力</div>
                    <div v-if="usersData[0].position_id !== 15" @click="checkLeave = 1" style="padding: 10px 0;" :class="['sub-tab-item', { 'selected-sub-tab': checkLeave == 1}]">計画有給確認</div>
                </div>
                <div v-if="selectedShiftType == 3 && checkLeave == 0" style="margin-left:auto;">
                    <MonthPicker 
                        :selectedMonth="shiftMonth"
                        :selectedYear="shiftYear"
                        right='0' 
                        @setDate="setDate"
                    />
                </div>
            </div>
            <div v-if="checkLeave == 0">
                <div class="shift-wrapper">
                    <div class="shift-types">
                        <div class="shift-type_name" v-for="(shift_type, index) in shiftTypes" :key="index">
                            <input type="radio" :disabled="shift_type.id === 3 && notSubmitted || shift_type.id === 16 && odaCheck" :id="shift_type.id" v-model="selectedShiftType" :value="shift_type.id">
                            <label :class="{'planned-date' : notSubmitted && shift_type.id === 3 || shift_type.id === 16 && odaCheck}" :for="shift_type.id">{{ shift_type.name }}</label>
                        </div>
                    </div>
                    <div class="shift-holiday">
                        <p v-if="selectedShiftType == 3">計画有給: {{ remainingDays }}日</p>
                        <p>休日数: {{holidayCount}}日</p>
                    </div>
                    <div class="shift-calendar">
                        <div class="shift-header">
                            <div class="shift-weekdays" v-for="num in 7">
                                <div @click="selectByWeek(num)" :class="{'shift-saturday' : num == 6, 'shift-sunday' : num == 7}">
                                    {{ weekDay(num) }}
                                </div>
                            </div>
                        </div>
                        <div class="shift-inner">
                            <div class="shift-month" v-for="(week, index) in dataLoad" :key="index">                
                                <div class="shift-week" v-for="(day, index) in week" :key="index">
                                    <div @click="selectShift(day, [], index + 1)" :class="{ 'hidden-date': !day.day_short, 'showed-date': day.day_short, 'planned-date': selectedShift(day) && selectedShift(day).id == 3}">
                                        <div>
                                            <div class="shift-day" :class="{'shift-saturday' : index == 5, 'shift-sunday' : index == 6, 'shift-everyholiday' : day.day_holiday}">
                                            {{ day.day_short }}
                                            </div>
                                            <div class="shift-select">{{ selectedShift(day) && selectedShift(day).name }}</div>
                                            <div style="font-size:10px;color:tomato" v-if="!selectedShift(day) && required">
                                                必須です
                                            </div>
                                        </div>
                                    </div>   
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div ref="shiftTime">
                    <div class="shift-title">
                        <p>基本就業時間の入力</p>
                    </div>
                    <div class="shift-workTime">
                        <div>
                            <p v-if="!responsive.mobile">始業時間</p>
                        </div>
                        <div>
                            <ShortInput 
                                customClass="date" 
                                type="time" 
                                v-model="startTime" 
                                name="start_time" 
                                rules="required" 
                                ref="startTimeRef"
                                :customStyle="{colorScheme: theme.dark == true ? 'dark' : '', fontSize: '13px'}"
                            />
                        </div>
                        <div>
                            <p>{{responsive.mobile ? '～' : '終業時間'}}</p>
                        </div>
                        <div>
                            <ShortInput 
                            customClass="date" 
                            type="time" 
                            v-model="endTime" 
                            name="end_time" 
                            rules="required" 
                            ref="endTimeRef"
                            :customStyle="{colorScheme: theme.dark == true ? 'dark' : '', fontSize: '13px'}"
                            />
                        </div>
                    </div>

                    <LoaderButton style="margin-top:30px;" @triggered="shiftAdd" :loading="loading" :content="attendanceFlag ? '勤怠確定後は編集できません' : '申請'"/>
                    
                </div>
            </div>
            <div v-else-if="checkLeave == 1">
                <WorkPaidLeave 
                    :user-id="usersCheckArray[0]"
                />
            </div>
        </div>
    </div>
        
</template>
<script setup>
    import moment from 'moment'
    import LoaderButton from '../Global/LoaderButton.vue'
    import MonthPicker from '../Global/MonthPicker.vue'
    import { computed, onMounted, inject, ref } from 'vue';
    import { useTheme } from '@/store/theme';
    import { useResponsive } from '@/store/responsive';
    import ShortInput from '../Form/ShortInput.vue';
    import holiday_jp from '@holiday-jp/holiday_jp'
    import { getShiftData } from '../../utils/workApi';
    import WorkPaidLeave from './WorkPaidLeave.vue';
    import { useBadgeStore } from '@/store/badge';
    const responsive = useResponsive()
    const theme = useTheme()
    const emit = defineEmits(['closeModal', 'reload', 'viewPaidLeave'])
    const { notify, info } = inject('dialog')
    const props = defineProps([
            'selectedMonth', 
            'selectedYear', 
            'usersData',
            'startDate',
            'notSubmitted',
            'usersCheckArray',
            'chosenId',
            'attendanceFlag'
            ])
    const shiftTime = ref(null)
    const selectedShiftType = ref(0)
    const loading = ref(false)
    const selectedShifts = ref([])
    const holidayCount = ref(0)
    const startTime = ref('')
    const endTime = ref('')
    const shiftMonth = ref(props.selectedMonth)
    const shiftYear = ref(props.selectedYear)
    const required = ref(false)
    const startTimeRef = ref(null)
    const endTimeRef = ref(null)
    const checkLeave = ref(0)
    const remainingDays = ref(0)
    const workTemp = ref([])
    const shiftTypes = ref([])
    const shiftRecords = ref([])
    const odaCheck = ref([])
    const badge = useBadgeStore()
    onMounted(async() => {
        propsCheck()
        await fetchShiftData()
        isShiftRecord()
    })
    const dataLoad = computed(() => {
        const thisMonth = moment([shiftYear.value, shiftMonth.value]);
        const firstDay = thisMonth.clone().startOf("isoWeek")
        const lastDay = thisMonth.clone().endOf("month").endOf("isoWeek");
        const holidays = holiday_jp.between(new Date(shiftYear.value + '-01-01'), new Date(shiftYear.value + '-12-31'));
        
        const calendar = [];
        for (let i = firstDay; i.isBefore(lastDay); i.add(1, "day")) {
            const weekIndex = calendar.length - 1;
            if (weekIndex < 0 || calendar[weekIndex].length === 7) {
                calendar.push([]);
            }
            if (i.month() !== thisMonth.month()) {
                calendar[calendar.length - 1].push({});
            } else {
                const holiday = holidays.find(h => moment(h.date).isSame(i, 'day'));
                calendar[calendar.length - 1].push({ 
                    "day_short" : i.locale("ja").format("D"),
                    "day_full" : i.locale("ja").format("YYYY-MM-DD"),
                    "day_holiday" : holiday ? holiday.name : null,
                    "weekday" : (i.day() + 6) % 7 + 1,
                    "formated_date" : `${i.format('M')} / ${i.format('D')} ${i.locale('ja').format('(ddd)')}`,
                });
            }
        }
        return calendar
    })
    const fetchShiftData = async() => {
        let yearMonth = moment([shiftYear.value, shiftMonth.value]).format('YYYY-MM')
        const work_group = props.chosenId ? [props.chosenId] : props.usersCheckArray
        const tempdate = props.startDate ? moment(props.startDate).format('YYYY-MM-DD') : ''
        try{
            const shiftData = await getShiftData(yearMonth, work_group, tempdate)           
            remainingDays.value = shiftData.remaining_days
            workTemp.value = shiftData.workTemp
            shiftTypes.value = shiftData.shift_type
            shiftRecords.value = shiftData.shift_record
            odaCheck.value = shiftData.odaCheck
        }catch (e){
            notify(e?.message)
        }
    }
    const propsCheck = () => {
        if(props.startDate){
            const newDate = props.startDate;
            shiftYear.value = moment(newDate).year();
            shiftMonth.value = moment(newDate).month();
            selectedShiftType.value = 3;
        }
    }
    const tempStartDate = computed(() => {
        return workTemp.value ? workTemp.value.date : props.startDate
    })
    const tempStartEnd = computed(() => {
        return workTemp.value ? moment(workTemp.value.date).clone().add(1, 'year') : moment(props.startDate).clone().add(1, 'year')
    })
    const between = computed(() => {
        let yearMonth = moment([props.selectedYear, props.selectedMonth]).format('YYYY-MM')
        return (moment(yearMonth).isAfter(moment(tempStartDate.value).format('YYYY-MM')) || moment(yearMonth).isBefore(moment(tempStartEnd.value).format('YYYY-MM'))) && selectedShiftType.value == 3
    }) 
    const isShiftRecord = () => {
        if(shiftRecords.value && shiftRecords.value.length){
            selectedShifts.value = []
            startTime.value = shiftRecords.value[0] ? shiftRecords.value[0].start_time : ''
            endTime.value = shiftRecords.value[0] ? shiftRecords.value[0].end_time : ''
            for(let shift of shiftRecords.value){
                let date = {
                    day_full : shift.shift_day,
                }
                selectShift(date, shift)
            } 
        }else{
            selectedShifts.value = []
            holidayCount.value = 0
            startTime.value = '09:00'
            endTime.value = '18:00'
        }
    }
    const weekDay = (num) => {
        return moment().weekday(num).locale('ja').format("dd")
    }
    const selectShift = (date, record, val) => {
        const status_flag = record ? record?.status_flag : 2
        const type_id = record && record.shift_type ? record?.shift_type?.id : selectedShiftType.value
        let existingShift = selectedShifts.value.find(shift => shift.date === date.day_full)
        if (existingShift) {
            if(existingShift.type == 3 && existingShift.status_flag == 1){
                notify('計画有給を変えることができません。')   
                return
            }
            selectedShifts.value = selectedShifts.value.filter(shift => shift.date !== date.day_full);
            if(val && type_id == 3 && existingShift.type == 3){
                remainingDays.value++
            }
        } else {
            selectedShifts.value.push({date: date.day_full, type: type_id, status_flag: status_flag});
            if(val && type_id == 3){
                remainingDays.value--
            }
            if(type_id == 3){
                const previousPeriodStart = moment(tempStartDate.value).subtract(1, 'year');
                const previousPeriodEnd = moment(tempStartDate.value);
                if (
                    record?.planned_year !== 2023 &&
                    !(
                        (moment(date.day_full).isBetween(previousPeriodStart, previousPeriodEnd, 'day', '[]') && 
                        record?.planned_year === moment().year() - 1) || // Valid in previous year's planning
                        moment(date.day_full).isBetween(moment(tempStartDate.value), moment(tempStartEnd.value), 'day', '[]') // Valid in current planning period
                    )
                ) {
                    selectedShifts.value.pop()
                    remainingDays.value++
                    const content = moment(date.day_full).format('YYYY/MM/DD') + 'は計画期間外です。<br>設定可能な期間は' + '<strong>' + moment(tempStartDate.value).format('YYYY/MM/DD') + '</strong>' + '-' + '<strong>' + tempStartEnd.value.format('YYYY/MM/DD') + '</strong>'

                    notify(content)  
                    return
                }
                if(remainingDays.value < 0){
                    remainingDays.value = 0
                    selectedShifts.value.pop()
                    notify('計画有給日数が足りない又は当年の計画有給は付与されていません。')  
                    return
                }
            }
        }
        holidayCount.value = selectedShifts.value.filter(shift => shift.type === 0).length
        
    }
    const selectedShift = (date) => {
        const record = selectedShifts.value.find(shift => shift.date == date.day_full)
        if(record){
            const shiftType = shiftTypes.value.find(type => type.id == record.type)
            return shiftType
        }
        
    }
    const shiftAdd = async() => {
        if(props.attendanceFlag) return
        const month = shiftMonth.value + 1
        var lastDay = new Date(shiftYear.value, month, 0).getDate();
        var holidayNum;
        if (props.usersData[0].position_id !== 15) {
            if(lastDay > selectedShifts.value.length && selectedShiftType.value !== 3){
                required.value = true 
                return
            }
            
            
        }
        if (month == 12 || month == 1) {
            holidayNum = (props.usersData[0].position_id <= 11) ? ((month == 12) ? 10 : 12) : 9;
        } else {
            holidayNum = (lastDay >= 29) ? 9 : 8;
        }
        if(endTime.value < startTime.value){
            notify('終業時間は始業時間より先にすることができません。')
            return
        }
        if(holidayCount.value >= holidayNum || props.usersData[0].position_id === 15 || between.value){
            const targets = [startTimeRef.value, endTimeRef.value]
            let result = true
            for(const target of targets){            
                const val = await target?.validate() || {valid: false}
                result = result * val.valid
            }
            if(!result) return
            if (loading.value) return
            loading.value = true

            let yearMonth = moment([shiftYear.value, shiftMonth.value]).format('YYYY-MM')

            const params = {
                shiftTimeStart : startTime.value,
                shiftEndStart : endTime.value,
                shift_array : selectedShifts.value,
                year: shiftYear.value,
                month: shiftMonth.value + 1,
                planned_year: tempStartDate.value ? tempStartDate.value.substring(0, 4) : props.selectedYear,
                userId: props.usersData[0].id,
                yearMonth: yearMonth
            }
            try {
                await axios.post('/add_shift', params);
                info('申請しました。')
                emit('closeModal')
                emit('reload')
                badge.getRemindBadge()
            } catch (e) {
                notify(e.response?.data.message || e?.message || 'エラーが発生しました。')   
            } finally {
                loading.value = false
            }
            
        }else{
            notify('今月の休日数は' + holidayNum + '日以上取得が必要です。')
        }
        
    }
    const setDate = async(date) => {
        selectedShifts.value = []
        shiftYear.value = date.year
        shiftMonth.value = date.month - 1
        await fetchShiftData()
        isShiftRecord()
    }
    const selectByWeek = (num) => {
        let selectedDays = []
        for (let week of dataLoad.value) {
            for (let day of week) {
                if (day.weekday === num) {
                    selectedDays.push(day);
                }
            }
        }
        let count = 0
        for(let date of selectedDays){
            let existingShift = selectedShifts.value.find(shift => shift.date === date.day_full);
            if (!existingShift) {
                selectShift(date, [], 1);
            }else{
                count++
                if(count === selectedDays.length){
                    for(let date of selectedDays){
                        selectShift(date, [], 1)
                    }
                }
            }
        }
    }
</script>