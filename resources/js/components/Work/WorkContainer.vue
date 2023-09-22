<template>
    <div class="work-root">
        
        <div class="work-header">
            <HamBurger/>
            <WorkButtons
                @selectShift="selectShift"
                @confirmAttendance="confirmAttendance"
                @todayScroll="todayScroll"
                @toBottomScroll="toBottomScroll"
                @selectMember="selectMember"
            />
            <div class="work-monthpicker">
            <div @click="prevMonth" class="work-prevmonth">
                <svg version="1.1" width="13" height="13" viewBox="0 0 20 32" xmlns="http://www.w3.org/2000/svg">
                    <path d="M0.775 17.789c1.305 1.166 2.612 2.332 3.927 3.486 1.311 1.161 2.634 2.308 3.953 3.46 1.316 1.156 2.646 2.296 3.973 3.439 1.33 1.139 2.667 2.273 4.015 3.394 0.662 0.551 1.647 0.52 2.272-0.107 0.65-0.654 0.619-1.725-0.020-2.393-1.198-1.253-2.407-2.495-3.621-3.729-1.232-1.245-2.462-2.492-3.704-3.725-0.902-0.9-1.803-1.802-2.707-2.699-0.033-0.032-0.055-0.069-0.072-0.106-0.045-0.036-0.082-0.080-0.111-0.129-0.069-0.047-0.129-0.117-0.176-0.216-0.021-0.047-0.044-0.092-0.066-0.136-0.12-0.062-0.214-0.168-0.246-0.325-0.001-0.005-0.002-0.009-0.003-0.014-0.104-0.157-0.187-0.327-0.254-0.505-0.109-0.185-0.182-0.388-0.226-0.601-0.002-0.012-0.005-0.024-0.007-0.036-0.016-0.085-0.028-0.172-0.036-0.259-0.195-0.593-0.26-1.183 0.030-1.653 0.006-0.157 0.067-0.277 0.157-0.361 0.019-0.050 0.039-0.099 0.063-0.149 0.040-0.084 0.1-0.145 0.17-0.188 0.008-0.015 0.019-0.028 0.028-0.042 0.032-0.13 0.106-0.228 0.202-0.293 0.072-0.145 0.157-0.287 0.26-0.43 0.046-0.063 0.101-0.113 0.163-0.151 0.018-0.020 0.037-0.038 0.059-0.054 0.014-0.059 0.044-0.116 0.094-0.165 0.9-0.888 1.797-1.782 2.699-2.672 1.244-1.231 2.476-2.475 3.714-3.717l1.843-1.871 1.832-1.885c0.655-0.681 0.669-1.793-0.044-2.48-0.652-0.631-1.693-0.624-2.385-0.038l-1.964 1.66-1.995 1.71c-1.32 1.149-2.648 2.293-3.962 3.45s-2.636 2.308-3.943 3.474c-1.311 1.159-3.284 2.806-4.106 3.689s-0.792 2.492 0.191 3.369z"></path>
                </svg>
            </div>
            <MonthPicker
                :selectedMonth="selectedMonth"
                :selectedYear="selectedYear"
                right="0" 
                @setDate="setDate"
                ref="monthpicker"
            />
            <div @click="nextMonth" class="work-nextmonth">
                <svg version="1.1" width="13" height="13" viewBox="0 0 20 32" xmlns="http://www.w3.org/2000/svg" style="transform: rotate(180deg);">
                    <path d="M0.775 17.789c1.305 1.166 2.612 2.332 3.927 3.486 1.311 1.161 2.634 2.308 3.953 3.46 1.316 1.156 2.646 2.296 3.973 3.439 1.33 1.139 2.667 2.273 4.015 3.394 0.662 0.551 1.647 0.52 2.272-0.107 0.65-0.654 0.619-1.725-0.020-2.393-1.198-1.253-2.407-2.495-3.621-3.729-1.232-1.245-2.462-2.492-3.704-3.725-0.902-0.9-1.803-1.802-2.707-2.699-0.033-0.032-0.055-0.069-0.072-0.106-0.045-0.036-0.082-0.080-0.111-0.129-0.069-0.047-0.129-0.117-0.176-0.216-0.021-0.047-0.044-0.092-0.066-0.136-0.12-0.062-0.214-0.168-0.246-0.325-0.001-0.005-0.002-0.009-0.003-0.014-0.104-0.157-0.187-0.327-0.254-0.505-0.109-0.185-0.182-0.388-0.226-0.601-0.002-0.012-0.005-0.024-0.007-0.036-0.016-0.085-0.028-0.172-0.036-0.259-0.195-0.593-0.26-1.183 0.030-1.653 0.006-0.157 0.067-0.277 0.157-0.361 0.019-0.050 0.039-0.099 0.063-0.149 0.040-0.084 0.1-0.145 0.17-0.188 0.008-0.015 0.019-0.028 0.028-0.042 0.032-0.13 0.106-0.228 0.202-0.293 0.072-0.145 0.157-0.287 0.26-0.43 0.046-0.063 0.101-0.113 0.163-0.151 0.018-0.020 0.037-0.038 0.059-0.054 0.014-0.059 0.044-0.116 0.094-0.165 0.9-0.888 1.797-1.782 2.699-2.672 1.244-1.231 2.476-2.475 3.714-3.717l1.843-1.871 1.832-1.885c0.655-0.681 0.669-1.793-0.044-2.48-0.652-0.631-1.693-0.624-2.385-0.038l-1.964 1.66-1.995 1.71c-1.32 1.149-2.648 2.293-3.962 3.45s-2.636 2.308-3.943 3.474c-1.311 1.159-3.284 2.806-4.106 3.689s-0.792 2.492 0.191 3.369z"></path>
                </svg>
            </div>
        </div>
        </div>
        <Transition name="modalFade">
            <WorkRecords 
                :calendars="calendars"
                :currentDay="currentDay"
                :usersData="relocateUsers"
                :auth_user="auth_user"
                :monthAverage="monthAverage"
                :shiftStartTime="shiftStartTime"
                :shiftEndTime="shiftEndTime"
                :attendanceFlag="attendanceFlag"
                @reload="reload"
            />
        </Transition>
        
        
        <Transition name="modalFade">
            <div v-if="shiftModal">
                <WorkShifts 
                    :selectedMonth="selectedMonth"
                    :selectedYear="selectedYear"
                    :shiftTypes="shiftTypes"
                    :shiftRecords="shiftRecords"
                    :calendarData="calendarData"
                    :currentDay="currentDay"
                    @closeModal="shiftModal = false"
                    @reload="reload"
                />
            </div>

        </Transition>
        
        <Transition name="modalFade">
            <div v-if="shiftAttendance">
                <WorkAttendance
                    :attendanceData="attendanceData"
                    :selectedYear="selectedYear"
                    :selectedMonth="selectedMonth"
                    @closeModal="shiftAttendance = false"
                />
            </div>
        </Transition>
        <Transition name="modalFade">
            <div v-if="showMembers">
                <WorkMembers 
                    :workGroups="workGroups"
                    :usersCheckArray="usersCheckArray"
                    @closeModal="closeMembers"
                    @selectedUsers="selectedUsers"
                />
            </div>
        </Transition>
    </div>
</template>

<script>
    import WorkButtons from './WorkButtons.vue'
    import MonthPicker from '../Global/MonthPicker.vue'
    import WorkRecords from './WorkRecords.vue'
    import HamBurger from '../Global/HamBurger.vue'
    import workStyle from './workStyle.scss'
    import moment from 'moment'
    import WorkShifts from './WorkShifts.vue'
    import WorkAttendance from './WorkAttendance.vue'
    import holiday_jp from '@holiday-jp/holiday_jp'
    import WorkMembers from './WorkMembers.vue'
    
    export default {
        data(){
            return {
                selectedYear: moment().year(),
                selectedMonth: moment().month(),
                currentDay: moment().format('YYYY-MM-DD'),
                usersCheckArray: [Number(this.$store.state.user.id)],
                shiftTypes: [],
                shiftModal: false,
                shiftAttendance: false,
                shiftRecords: [],
                timeCardRecords: [],
                showMembers: false,
                workGroups: [],
                usersData: [],
                calendars: [],
                shiftArray: [],
                attendanceData: [],
                auth_user: this.$store.state.user,
                monthAverage: [],
                shiftStartTime: '',
                shiftEndTime: '',
                attendanceFlag: false,
            }
        },
        mounted(){
            this.getWorkData()
            this.getShiftData()
            this.getWorkGroup()
            this.getAttendanceData()
        },
        computed: {
            relocateUsers(){
                const authUserId = this.auth_user.id;
                const usersCheckArray = this.usersCheckArray;
                const usersData = this.usersData.slice(); 
                if (usersCheckArray.includes(authUserId)) {
                    usersCheckArray.unshift(usersCheckArray.splice(usersCheckArray.indexOf(authUserId), 1)[0]);
                }
                usersData.sort((a, b) => usersCheckArray.indexOf(a.id) - usersCheckArray.indexOf(b.id));

                return usersData;
            },
            calendarData() {
                const thisMonth = moment([this.selectedYear, this.selectedMonth]);
                const firstDay = thisMonth.clone().startOf("isoWeek")
                const lastDay = thisMonth.clone().endOf("month").endOf("isoWeek");
                const holidays = holiday_jp.between(new Date(this.selectedYear + '-01-01'), new Date(this.selectedYear + '-12-31'));
                
                const calendar = [];
                for (let i = firstDay; i.isBefore(lastDay); i.add(1, "day")) {
                    const weekIndex = calendar.length - 1;
                    if (weekIndex < 0 || calendar[weekIndex].length === 7) {
                        calendar.push([]);
                    }
                    if (i.month() !== thisMonth.month()) {
                        // Push an empty object
                        calendar[calendar.length - 1].push({});
                    } else {
                        const holiday = holidays.find(h => moment(h.date).isSame(i, 'day'));
                        calendar[calendar.length - 1].push({ 
                            "day_short" : i.locale("ja").format("D"),
                            "day_full" : i.locale("ja").format("YYYY-MM-DD"),
                            "day_holiday" : holiday ? holiday.name : null,
                            "weekday" : (i.day() + 6) % 7 + 1,
                            "formated_date" : `${i.format('M')} / ${i.format('D')} ${i.locale('ja').format('(ddd)')}`,
                            "shift_records" : this.shiftArray,
                            "time_card_records" : this.timeCardRecords,
                            "users" : this.relocateUsers
                        });
                    }
                }
                return calendar
            }
        },
        methods: {
            closeMembers(users){
                if(users.length > 0){
                    this.showMembers = false
                }else{
                    emitter.emit('setToast', {
                        active: true,  
                        type: 'info', 
                        content: 'メンバーを選択してください。',
                        closeButton: true, 
                        autoClose: true,
                    }) 
                }     
            },
            selectedUsers(users){
                if(users.length > 0){
                    this.usersCheckArray = users
                    this.reload()
                }
            },
            reload(){
                this.getWorkData()
                this.getShiftData()
                this.getAttendanceData() 
            },
            setDate(date){
                this.selectedYear = date.year
                this.selectedMonth = date.month - 1
                this.reload()
            },
            getWorkGroup(){
                axios.get('/get_work_group').then(
                    response => {
                        this.workGroups = response.data
                    }
                )
            },
            getWorkData(){
                let yearMonth = this.selectedYear + '-' + (this.selectedMonth + 1)
                const params = {
                    current_date : yearMonth,
                    work_group : this.usersCheckArray
                };
                axios.post('/get_work_data', params).then(
                    response => {
                        this.timeCardRecords = response.data.record_array,
                        this.usersData = response.data.user_data,
                        this.shiftArray = response.data.shift_array,
                        this.monthAverage = response.data.month_average,
                        this.calendars = this.calendarData
                    }
                )
            },
            getShiftData(){
                let yearMonth = this.selectedYear + '-' + (this.selectedMonth + 1)
                const params = {
                    current_date : yearMonth,
                    work_group : this.usersCheckArray
                }
                axios.post('/get_shift_data', params).then(
                    response => {
                        this.shiftTypes = response.data.shift_type
                        this.shiftRecords = response.data.shift_record
                        this.shiftStartTime = this.shiftRecords[0].start_time
                        this.shiftEndTime = this.shiftRecords[0].end_time
                    }
                )
            },
            selectMember(){
                this.showMembers = true
            },
            getAttendanceData(){
                let yearMonth = this.selectedYear + '-' + (this.selectedMonth + 1)
                const params = {
                    current_date : yearMonth,
                    work_group : this.usersCheckArray
                }
                axios.post('/get_attendance_data', params).then(
                    response => {
                        this.attendanceData = response.data
                        this.attendanceFlag = this.attendanceData.attendance_flag
                    }
                )
            },
            selectShift(){
                if(this.usersCheckArray.length > 1){
                    emitter.emit('setToast', {
                        active: true,  
                        type: 'info', 
                        content: 'メンバーが複数選択されています。勤怠予定はメンバーを1人のみ選択してください。',
                        closeButton: true, 
                        autoClose: true,
                    }) 
                }else if(this.usersCheckArray[0] == this.auth_user.id){
                    this.shiftModal = true
                }
            },
            confirmAttendance(){
                if(this.usersCheckArray.length > 1){
                    emitter.emit('setToast', {
                        active: true,  
                        type: 'info', 
                        content: 'メンバーが複数選択されています。勤怠確定はメンバーを1人のみ選択してください。',
                        closeButton: true, 
                        autoClose: true,
                    }) 
                }else if(this.usersCheckArray[0] == this.auth_user.id){
                    this.shiftAttendance = true
                }
            },
            prevMonth(){
                this.selectedMonth = this.selectedMonth - 1
                if(this.selectedMonth < 0){
                    this.selectedYear = this.selectedYear - 1
                    this.selectedMonth = 11
                }
                this.reload()
            },
            nextMonth(){
                this.selectedMonth = this.selectedMonth + 1;
                if(this.selectedMonth > 11){
                    this.selectedYear = this.selectedYear + 1
                    this.selectedMonth = 0
                }
                this.reload()
            },
            todayScroll(){

                    let scrollPosition = document.getElementsByClassName('today')[0];
                    if(scrollPosition){
                        scrollPosition.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }   

              

            },
            toBottomScroll(){
                let scrollInto = document.getElementsByClassName('records-footer')[0];
                scrollInto.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        },
        components: {
            MonthPicker,
            WorkButtons,
            WorkRecords,
            HamBurger,
            WorkShifts,
            WorkAttendance,
            WorkMembers
        }
    }
</script>