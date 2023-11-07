<template>
    <div class="work-modal" @mousedown="$emit('closeModal')">
        <div class="work-modal-inner" @mousedown.stop>
            <div class="recordFormTitle" style="z-index: 26;">
                <p style="font-size: 18px;">{{ shiftYear }}年{{ shiftMonth+1 }}月の勤怠予定</p>
                <div @click="$emit('closeModal')" class="cursor-pointer" style="margin: auto 0 auto auto;">
                    <svg class="modalWindowCloseButton" version="1.1" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 32 32">
                        <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                    </svg>
                </div>
            </div>
            <div class="shift-title">
                <p>予定の入力</p>
                <div style="margin-left:auto;">
                    <MonthPicker 
                        v-if="shiftModal"
                        :selectedMonth="shiftMonth"
                        :selectedYear="shiftYear"
                        right='0' 
                        @setDate="setDate"
                    />
                </div>
            </div>
            <div class="shift-wrapper">
                <div class="shift-types">
                    <div class="shift-type_name" v-for="(shift_type, index) in shiftTypes" :key="index">
                        <input type="radio" :id="shift_type.id" v-model="selectedShiftType" :value="shift_type.id">
                        <label :for="shift_type.id">{{ shift_type.name }}</label>
                    </div>
                </div>
                <div class="shift-holiday">
                    <p v-if="selectedShiftType == 3">計画有給: {{ this.scheduled_vacation }}日</p>
                    <p v-if="selectedShiftType == 3">予定日: {{ this.planned_days }}日</p>
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
                        <div class="shift-month" v-for="(week, index) in calendarData" :key="index">                
                            <div class="shift-week" v-for="(day, index) in week" :key="index">
                                <div @click="selectShift(day)" :class="{ 'hidden-date': !day.day_short, 'showed-date': day.day_short }">
                                    <div>
                                        <div class="shift-day" :class="{'shift-saturday' : index == 5, 'shift-sunday' : index == 6, 'shift-everyholiday' : day.day_holiday}">
                                        {{ day.day_short }}
                                        </div>
                                        <div class="shift-select">{{ selectedShift(day) }}</div>
                                    </div>
                                </div>   
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <Form v-slot="{ errors }" @submit.prevent ref="shiftTime">
                <div class="shift-title">
                    <p>基本就業時間の入力</p>
                </div>
                <div class="shift-workTime">
                    <div>
                        <p v-if="!$store.state.mobile">始業時間</p>
                    </div>
                    <div>
                        <Field class="taskDateTimePicker" type="time" v-model="startTime" name="start_time" rules="required" :class="{'clock-color' : $store.state.dark == true }"/>
                        <span class="valid-error post-error">{{ errors.start_time }}</span>
                    </div>
                    <div>
                        <p>{{$store.state.mobile ? '～' : '終業時間'}}</p>
                    </div>
                    <div>
                        <Field class="taskDateTimePicker" type="time" v-model="endTime" name="end_time" rules="required" :class="{'clock-color' : $store.state.dark == true }"/>
                        <span class="valid-error post-error">{{ errors.end_time }}</span>
                    </div>
                </div>
                <LoaderButton style="margin-top:30px;" @triggered="shiftAdd" :loading="loading" :content="attendanceFlag ? '勤怠確定後は編集できません' : '保存'"/>
                
            </Form>
        </div>
    </div>
        
</template>
<script>
    import moment from 'moment'
    import { Field, Form } from 'vee-validate'
    import LoaderButton from '../Global/LoaderButton.vue'
    import MonthPicker from '../Global/MonthPicker.vue'
    export default{
        props: [
            'selectedMonth', 
            'selectedYear', 
            'shiftTypes', 
            'shiftRecords', 
            'calendarData',
            'attendanceFlag',
            'usersData',
            'kintone_data',
            'shiftModal',
            'planned_days'
            ],
        data() {
            return {
                selectedShiftType: 0,
                loading: false,
                selectedShifts: [],
                holidayCount: 0,
                startTime: '',
                endTime: '',
                shiftMonth: this.selectedMonth,
                shiftYear: this.selectedYear,
                windowWidth: window.innerWidth,
            }
        },
        mounted(){
            this.isShiftRecord(this.shiftRecords)
        },
        computed:{
            scheduled_vacation(){
                const remainingdays = this.kintone_data.planned_days - this.kintone_data.consumed_days
                return remainingdays >= 0 ? remainingdays : 0
            }       
        },
        watch: {
            shiftRecords(newVal, oldVal){
                if(newVal != oldVal){
                    this.isShiftRecord(newVal)
                }
            }
        },
        methods: {
            isShiftRecord(shifts){
                if(shifts && shifts.length){
                    this.selectedShifts = []
                    this.startTime = shifts[0] ? shifts[0].start_time : ''
                    this.endTime = shifts[0] ? shifts[0].end_time : ''
                    for(let shift of shifts){
                        let date = {
                            day_full : shift.shift_day,
                        }
                        this.selectedShiftType = shift.shift_type.id
                        this.selectShift(date)
                    } 
                }else{
                    this.holidayCount = 0
                    this.startTime = '09:00'
                    this.endTime = '18:00'
                }
            },
            weekDay(num){
                return moment().weekday(num).locale(this.$store.state.local).format("dd")
            },
            selectShift(date){
                if(this.selectedShiftType == 3 && this.scheduled_vacation <= 0){
                    emitter.emit('setToast', {
                        active: true,  
                        type: 'info', 
                        content: '予定された休暇日はありません。',
                        closeButton: false, 
                        autoClose: false,
                        answers: ['OK']
                    })   
                    return
                }
                let existingShift = this.selectedShifts.find(shift => shift.date === date.day_full)
                
                if (existingShift) {
                    this.selectedShifts = this.selectedShifts.filter(shift => shift.date !== date.day_full);   
                } else {
                    this.selectedShifts.push({date: date.day_full, type: this.selectedShiftType});
                }
                this.holidayCount = this.selectedShifts.filter(shift => shift.type === 0).length
            },
            selectedShift(date){
                return this.selectedShifts.map(shift => {
                    if (shift.date === date.day_full) {
                        let shiftType = this.shiftTypes.find(st => st.id === shift.type)
                        return shiftType ? shiftType.name : null;
                    }
                }).join('');
            },
            async shiftAdd(){
                if(this.attendanceFlag) return
                const month = this.shiftMonth + 1
                var lastDay = new Date(this.shiftYear, month, 0).getDate();
                var holidayNum;
                console.log(month)
                if (month == 12 || month == 1) {
                    holidayNum = (this.usersData[0].position_id <= 11) ? ((month == 12) ? 10 : 12) : 9;
                } else {
                    holidayNum = (lastDay >= 29) ? 9 : 8;
                }
                if(this.endTime < this.startTime){
                    emitter.emit('setToast', {
                        active: true,  
                        type: 'info', 
                        content: '正しい時間を選択してください。',
                        closeButton: true, 
                        autoClose: true,
                    })
                    return
                }
                if(this.holidayCount >= holidayNum){
                    const result = await this.$refs.shiftTime.validate();
                    if (this.loading) return

                    if(result.valid){
                        this.loading = true
                        const params = {
                            shiftTimeStart : this.startTime,
                            shiftEndStart : this.endTime,
                            shift_array : this.selectedShifts,
                            kintone_id: this.kintone_data.id,
                            userId: this.usersData[0].id
                        }
                        axios.post('/add_shift', params).then(
                            response => {
                                this.loading = false
                                this.$emit('closeModal')
                                this.$emit('reload')
                            }
                        ).catch(function (error) {
                            if (error.response) this.errorToast('エラーが発生しました。 ' + error.response.data.message)
                            else if (error.request) this.errorToast('エラーが発生しました。')
                            else this.errorToast('エラーが発生しました。 ' + error.message)     
                        }.bind(this))
                    }
                }else{
                    emitter.emit('setToast', {
                        active: true,  
                        type: 'info', 
                        content: '今月の休日数は' + holidayNum + '日以上取得が必要です。',
                        closeButton: true, 
                        autoClose: true,
                    })
                }
                
            },
            setDate(date){
                console.log(date)
                this.shiftYear = date.year
                this.shiftMonth = date.month - 1
                this.$emit('changeDate', this.shiftMonth, this.shiftYear)
            },
            errorToast(message){
                emitter.emit('setToast', {
                    active: true,  
                    type: 'info', 
                    content: message,
                    closeButton: false, 
                    autoClose: false,
                    answers: ['OK']
                })                
            },
            selectByWeek(num){
                let selectedDays = []
                for (let week of this.calendarData) {
                    for (let day of week) {
                        if (day.weekday === num) {
                            selectedDays.push(day);
                        }
                    }
                }
                let count = 0
                for(let date of selectedDays){
                    let existingShift = this.selectedShifts.find(shift => shift.date === date.day_full);
                    if (!existingShift) {
                        this.selectShift(date);
                    }else{
                        count++
                        if(count === selectedDays.length){
                            for(let date of selectedDays){
                                this.selectShift(date)
                            }
                        }
                    }
                }
            },
        },
        components: {
            Form,
            Field,
            LoaderButton,
            MonthPicker
        }
    }
</script>