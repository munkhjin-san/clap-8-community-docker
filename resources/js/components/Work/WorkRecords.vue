<template>
    <div class="records-wrapper">
        <div class="records-table">
            <div class="records-header">
                <div class="header-row">
                    <div class="header-cell" v-for="(header, index) in headers" :key="index">
                        {{ header.label }}
                    </div>
                </div>
            </div>
            <div class="records-body">
                <div class="body-row" v-for="(day, index) in daysList" :key="index">
                    <div class="body-cell" :class="getDayClass(day.weekday, day.day_holiday, day.day_full)">
                        <p :class="{'working' : day.time_card_records?.[day.day_full]?.[auth_user.id]?.stamp_flag === 0 && day.day_full === currentDay}">{{ day.formated_date }}</p>
                    </div>
                    <div class="body-cell">
                        <div v-for="(user, index) in day.users" :key="index">
                            <p>{{ user.name }}</p>
                        </div>
                    </div>
                    <div class="body-cell">
                        <div v-for="(user, index) in day.users" :key="index">
                            <p :class="{ 
                                'shift-sunday' : (day.shift_records?.[day.day_full]?.[user.id]?.shift_type == 0 
                                || day.shift_records?.[day.day_full]?.[user.id]?.shift_type == 5 
                                || day.shift_records?.[day.day_full]?.[user.id]?.shift_type == 14 
                                || day.shift_records?.[day.day_full]?.[user.id]?.shift_type == 15) }">
                                {{ day.shift_records?.[day.day_full]?.[user.id]?.abbreviation ?? '--'}}
                            </p>
                        </div>
                    </div>
                    <div class="body-cell">
                        <div v-for="(user, index) in day.users" :key="index">
                            <p :class="lateTimeGenerate(day.time_card_records?.[day.day_full]?.[user.id]?.start_time, day.day_full)">
                                {{ formatTime(day.time_card_records?.[day.day_full]?.[user.id]?.start_time) ?? '--'}}
                            </p>
                        </div>   
                    </div>
                    <div class="body-cell">
                        <div v-for="(user, index) in day.users" :key="index">
                            <p :class="overTimeGenerate(day.time_card_records?.[day.day_full]?.[user.id]?.end_time, day.day_full)">{{ 
                                    day.time_card_records?.[day.day_full]?.[user.id]?.start_time
                                    ? (day.time_card_records?.[day.day_full]?.[user.id]?.end_time
                                        ? formatTime(day.time_card_records?.[day.day_full]?.[user.id]?.end_time)
                                        : '打刻なし')
                                    : '--'
                                }}
                            </p>
                        </div>
                    </div>
                    <div class="body-cell">
                        <div v-for="(user, index) in day.users" :key="index">
                            <p v-if="today(day.day_full) && day.time_card_records?.[day.day_full]?.[user.id]?.stamp_flag == 0">{{ day.time_card_records?.[day.day_full]?.[user.id]?.start_time ? countdown(day.time_card_records[day.day_full][user.id].start_time) : '--' }}</p>
                            <p v-else>{{ workTimeFormat(day.time_card_records?.[day.day_full]?.[user.id]?.work_time) ?? '--' }}</p>
                        </div>
                    </div>
                    <div class="body-cell">
                        <div v-for="(user, index) in day.users" :key="index">
                            <p :class="{ 'shift-saturday' : day.time_card_records?.[day.day_full]?.[user.id]?.over_time }" v-if="user.work_type == 1">{{ (day.time_card_records?.[day.day_full]?.[user.id]?.over_time ? day.time_card_records[day.day_full][user.id].over_time + '分' : '--') }}</p>
                            <p v-else>--</p>
                        </div>
                    </div> 
                    <div class="body-cell">
                        <div v-for="(user, index) in day.users" :key="index">
                            <p>{{ (day.time_card_records?.[day.day_full]?.[user.id]?.break_time ? day.time_card_records[day.day_full][user.id].break_time + '分' : '--') }}</p>
                        </div>
                    </div>
                    <div class="body-cell">
                        <div v-for="(user, index) in day.users" :key="index">
                            <p v-if="day.time_card_records?.[day.day_full]?.[user.id]?.allowance.length">
                                <span :key="index" v-for="(allowance, index) in day.time_card_records[day.day_full][user.id].allowance">{{ allowance.label ? allowance.label : allowance }}</span></p>
                            <p v-else>--</p>
                        </div>
                    </div>
                    <div class="body-cell">
                        <div v-for="(user, index) in day.users" :key="index">
                            <p>{{ day.time_card_records?.[day.day_full]?.[user.id]?.incident ? day.time_card_records[day.day_full][user.id].incident.label : '--' }}</p>
                        </div>
                    </div>
                    <div class="body-cell">
                        <div v-for="(user, index) in day.users" :key="index">
                            <p>{{ day.time_card_records?.[day.day_full]?.[user.id]?.achievement ? day.time_card_records[day.day_full][user.id].achievement.label : '--' }}</p>
                        </div>                    
                    </div>
                    <div class="body-cell">
                        <p></p>
                    </div>
                    <div class="body-cell">
                        <div v-for="(user, index) in day.users" :key="index">
                            <p>{{ day.time_card_records?.[day.day_full]?.[user.id]?.comment ? commentTextLength(day.time_card_records[day.day_full][user.id].comment.value_text) : '--' }}</p>
                        </div>                    
                    </div>
                    <div class="body-cell">
                        <div v-for="(user, index) in day.users" :key="index">
                            <div style="display:inline-block" v-if="isTodayOrFuture(day.day_full) && user.id == auth_user.id">
                                <div class="workButton-wrapper">
                                    <button v-if="today(day.day_full) && day.time_card_records?.[day.day_full]?.[user.id]?.stamp_flag == 0" @click="timeStampEnd()" class="workRecords-button">終業</button>
                                    <button v-else-if="today(day.day_full) && day.time_card_records?.[day.day_full]?.[user.id]?.stamp_flag == null" @click="timeStampStart()" class="workRecords-button">始業</button>
                                    
                                    <button v-if="day.time_card_records?.[day.day_full]?.[user.id]?.work_time == null && day.time_card_records?.[day.day_full]?.[user.id]?.start_time == null" class="workRecords-button" @click="timeStampEdit(day.shift_records?.[day.day_full]?.[user.id], true, user.id)">作成</button>
                                    <p v-else-if="day.time_card_records?.[day.day_full]?.[user.id]?.status_flag == 1">申請中</p>
                                    <p v-else-if="day.time_card_records?.[day.day_full]?.[user.id]?.status_flag == 2">承認済み</p>
                                    <button v-else class="workRecords-button" @click="timeStampEdit(day.time_card_records?.[day.day_full]?.[user.id], false, user.id)">編集</button>
                                </div>
                            </div>
                            <div style="display:inline-block" v-else-if="auth_user.work_authority > user.work_authority">
                                <div class="workButton-wrapper">
                                    <button v-if="day.time_card_records?.[day.day_full]?.[user.id]?.status_flag == 1" @click="dailyApproval(user.id,day.day_full)" class="workRecords-button">承認</button>
                                    <button v-if="day.time_card_records?.[day.day_full]?.[user.id]?.status_flag == 1" @click="timeCardRemand(user.id,day.day_full)" class="workRecords-button">差戻</button>
                                    <button v-if="day.time_card_records?.[day.day_full]?.[user.id]?.status_flag == 2" @click="dailyCancel(user.id,day.day_full)" class="workRecords-button">承認取消</button>
                                    <p style="line-height: 2.5" v-else-if="day.time_card_records?.[day.day_full]?.[user.id]?.status_flag == 0">作成中</p>
                                    <p style="line-height: 2.5" v-else-if="day.time_card_records?.[day.day_full]?.[user.id]?.status_flag == 10">差戻中</p>
                                    <button v-if="auth_user.id == 608 && day.time_card_records?.[day.day_full]?.[user.id]?.work_time == null && day.time_card_records?.[day.day_full]?.[user.id]?.start_time == null" class="workRecords-button" @click="timeStampEdit(day.shift_records?.[day.day_full]?.[user.id], true, user.id)">作成</button>
                                    <button v-else-if="auth_user.id == 608" class="workRecords-button" @click="timeStampEdit(day.time_card_records?.[day.day_full]?.[user.id], false, user.id)">編集</button>
                                    <p v-else-if="day.time_card_records?.[day.day_full]?.[user.id]?.work_time == null && day.time_card_records?.[day.day_full]?.[user.id]?.start_time == null">--</p>
                                </div>
                            </div>
                            <p v-else>--</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="records-footer">
                <div class="footer-row" v-for="(user, index) in usersData" :key="index">
                    <div class="footer-cell">
                        集計
                    </div>
                    <div class="footer-cell">
                        {{ user.name }}
                    </div>
                    <div class="footer-cell">
                        <p>--</p>
                    </div>
                    <div class="footer-cell">
                        <p>--</p>
                    </div>
                    <div class="footer-cell">
                        <p>--</p>
                    </div>
                    <div class="footer-cell">
                        <p>{{ monthAverage?.[user.id]?.month_work_time ? overTimeFormat(monthAverage?.[user.id]?.month_work_time) : '--' }}</p>
                    </div>
                    <div class="footer-cell">
                        <p v-if="user.work_type == 1 || attendanceFlag">{{ monthAverage?.[user.id]?.month_over_time ? overTimeFormat(monthAverage?.[user.id]?.month_over_time) : '--' }}</p>
                        <p v-else>--</p>
                    </div>
                    <div class="footer-cell">
                        <p>--</p>
                    </div>
                    <div class="footer-cell">
                        <p>--</p>
                    </div>
                    <div class="footer-cell">
                        <p>--</p>
                    </div>
                    <div class="footer-cell">
                        <p>{{ monthAverage?.[user.id]?.month_achievement_average ? monthAverage?.[user.id]?.month_achievement_average : '--'}}</p>
                    </div>
                    <div class="footer-cell">
                        <p>--</p>
                    </div>
                    <div class="footer-cell">
                        <p>--</p>
                    </div>
                    <div class="footer-cell">
                        <p>--</p>
                    </div>
                </div>
            </div>
        </div>
        <Transition name="modalFade">
            <div v-if="reportModal">
                <WorkReport
                    @reload="reload"
                    @closeModal="reportModal = false"
                    :choosenDate="choosenDate"
                    :todayStartTime="formatTime(todayStartTime)"
                    :todayEndTime="formatTime(todayEndTime)"
                    :todayBreakTime="todayBreakTime"
                    :customFieldData="customFieldData"
                    :info="info"
                    :createReport="createReport"
                    :chosenUserId="chosenUserId"
                    :shiftStartTime="shiftStartTime"
                    :shiftEndTime="shiftEndTime"
                />
            </div>
        </Transition>
    </div>
</template>
<script>
    import WorkReport from './WorkReport.vue'
    export default{
        props: [
            'currentDay', 
            'shiftRecords',
            'calendars',
            'auth_user',
            'monthAverage',
            'usersData',
            'shiftStartTime',
            'shiftEndTime',
            'attendanceFlag'
        ],
        data(){
            return{
                headers: [
                    { label: '日付'},
                    { label: 'メンバー'},
                    { label: '予定'},
                    { label: '出勤'},
                    { label: '退勤'},
                    { label: '労働時間'},
                    { label: '残業時間'},
                    { label: '休憩時間'},
                    { label: '諸手当'},
                    { label: 'インシデント'},
                    { label: '目標達成率'},
                    { label: 'コンディション'},
                    { label: 'コメント'},
                    { label: '日報申請'},
                   
                ],
                reportModal: false,
                choosenDate: null,
                todayStartTime: null,
                todayEndTime: null,
                todayBreakTime: 0,
                customFieldData: [],
                stampStart: true,
                stampEnd: false,
                info: [],
                createReport: false,
                chosenUserId: ''
            }
        },
        mounted(){
            this.getCustomFields()
        },
        computed: {
            
            daysList() {
                return this.calendars.flat().filter(day => Object.keys(day).length !== 0);
            },
        },
        methods: {
            overTimeFormat(minutes){
                if (minutes === 0) {
                    return '0時間';
                } else {
                    const hours = Math.floor(minutes / 60);
                    const remainingMinutes = minutes % 60;
                    let formatted = '';
                    
                    if (hours > 0) {
                        formatted += hours + '時間';
                    }
                    
                    if (remainingMinutes > 0) {
                        formatted += remainingMinutes + '分';
                    }
                    
                    return formatted;
                }
            },
            workTimeFormat(time){
                if(time){
                    const hours = Math.floor(time / 60);
                    const minutes = time % 60;
                    
                    return `${hours}時間${minutes}分`;
                }
                return '--'
            },
            countdown(time) {
                const dateTime = this.currentDay + ' ' + time
                const now = new Date().getTime();
                const startTime = new Date(dateTime).getTime();
                let timeDifference = now - startTime;
                
                if (timeDifference <= 0) {
                    return '0時間0分';
                }

                const hours = Math.floor((timeDifference % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((timeDifference % (1000 * 60 * 60)) / (1000 * 60));

                return `${hours}時間${minutes}分`;
            },
            getDayClass(day, holiday, date, working){
                return {
                    'shift-saturday': day === 6,
                    'shift-sunday': day === 7,
                    'shift-everyholiday' : holiday,
                    'today' : date === this.currentDay
                }
            },
            commentTextLength(value){
                return value.length > 10 ? value.slice(0, 6) + "..." : value;

            },
            today(date){
                return date === this.currentDay
            },
            isTodayOrFuture(date) {
                return this.currentDay >= date;
            },
            timeStampStart(){
                var date = new Date(); // get current date
                var minutes = date.getMinutes();
                var quarterHours = Math.ceil(minutes / 15);
                date.setMinutes(quarterHours * 15);
                date.setSeconds(0);
                var hours = date.getHours();
                var minutes = date.getMinutes();

                // pad with zero if needed
                hours = hours < 10 ? '0' + hours : hours;
                minutes = minutes < 10 ? '0' + minutes : minutes;
                let time = hours + ':' + minutes + ':00'
                this.todayStartTime = time
                this.stampEnd = true
                this.stampStart = false
                const params = {
                    start_time : time,
                    day : this.currentDay
                }
                axios.post('/daily_report_add', params).then(
                    response => {
                        this.$emit('reload')
                    }
                )
            },
            timeStampEnd(){
                var date = new Date(); // get current date
                var minutes = date.getMinutes();
                var rounded = Math.floor(minutes / 15) * 15;
                date.setMinutes(rounded);
                date.setSeconds(0);
                var hours = date.getHours();
                var minutes = date.getMinutes();

                // pad with zero if needed
                hours = hours < 10 ? '0' + hours : hours;
                minutes = minutes < 10 ? '0' + minutes : minutes;
                let time = hours + ':' + minutes + ':00'
                this.todayEndTime = time
                this.stampEnd = false
                const params = {
                    end_time : time,
                    day : this.currentDay
                }
                const uniqueChannell = Math.random().toString(36).substring(5);
                emitter.emit('setToast', {
                    active: true,  
                    type: 'info', 
                    content: '本日の勤務を終業しますか。',
                    closeButton: false, 
                    autoClose: false,
                    answers: [this.$t('confirmToAction'),this.$t('cancelToAction')],
                    channel: uniqueChannell

                })            
                emitter.on(uniqueChannell, (data) => { 
                    if(data.answer === this.$t('confirmToAction')){
                        axios.post('/daily_report_add', params).then(
                            response => {
                                this.$emit('reload')
                                this.timeStampEdit(response.data, false)
                            }
                        )
                    }else{
                        this.stampEnd = true
                    } 
                });
                
                
            },
            getCustomFields(){
                const params = {
                    app_name : 'work'
                };

                axios.post('/custom_field_data', params ).then(
                    response => {
                            this.info = response.data
                        }
                    );

            },
            reload(){
                this.$emit('reload')
                this.reportModal = false
            },
            timeStampEdit(data, val, userId){
                this.todayStartTime = data.start_time ? data.start_time : data.shift_start_time
                this.todayEndTime = data.end_time ? data.end_time : data.shift_end_time
                this.todayBreakTime = data.break_time ? data.break_time : 0
                this.choosenDate = data.day ? data.day : data.shift_day
                const fields = ['allowance', 'incident', 'achievement', 'comment'];
                fields.forEach(field => {
                    if (data[field]) {
                        this.customFieldData.push(data[field]);
                    }else{
                        this.customFieldData = []
                    }
                });
                this.chosenUserId = userId
                this.reportModal = true
                this.createReport = val
            },
            formatTime(time){
                if(!time) return '--'
                
                if(/^([01]\d|2[0-3]):[0-5]\d:[0-5]\d$/.test(time)){
                    const date = new Date(`2000-01-01T${time}`)
                    return date.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' })
                }else if(time === '打刻なし'){
                    return time
                }
            },
            timeCardRemand(userId,day){
                const uniqueChannell = Math.random().toString(36).substring(5);
            
                emitter.emit('setToast', {
                    active: true,  
                    type: 'info', 
                    content: day + "日報を差し戻しますか。",
                    closeButton: false, 
                    autoClose: false,
                    answers: [this.$t('confirmToAction'),this.$t('cancelToAction')],
                    channel: uniqueChannell
                }) 
                emitter.on(uniqueChannell, (data) => {                            
                    if(data.answer === this.$t('confirmToAction')){
                        const params = {
                            user_id: userId,
                            record_day: day
                        }
                        axios.post('/remand_time_card', params ).then(
                            response => {
                                    this.$emit('reload')
                            }
                        );
                    }else{
                        return
                    }
                })
            },
            dailyApproval(userId,day){
                const params = {
                    user_id: userId,
                    record_day: day
                };
                axios.post('/approve_time_card', params ).then(
                    response => {
                        this.$emit('reload')
                    }
                );
            },
            dailyCancel(userId,day){
                this.showLoader = true;
                const params = {
                    user_id: userId,
                    record_day: day
                };
                axios.post('/cancel_time_card', params ).then(
                    response => {
                        this.$emit('reload')
                    }
                );
            },
            lateTimeGenerate(start_time,day){
                let value_start = new Date( day + ' ' + start_time);
                let shift_start = new Date( day + ' ' + this.shiftStartTime);
                let value_strat_s = value_start.getTime();
                let shift_start_s = shift_start.getTime();
                if(value_strat_s > shift_start_s){
                    return 'late-class';
                }else if(value_strat_s = shift_start_s){
                    return null;
                }else{
                    return null;
                }
            },
            overTimeGenerate(end_time,day){
                let value_end = new Date( day + ' ' + end_time );
                let shift_end = new Date( day + ' ' + this.shiftEndTime);
                let value_end_s = value_end.getTime();
                let shift_end_s = shift_end.getTime();
                if(value_end_s > shift_end_s){
                    return 'over-class';
                }else if(value_end_s < shift_end_s){
                    return 'early-class';
                }else{
                    return null;
                }
            },
        },
        components: {
            WorkReport
        }
    }
</script>