<template>
    <div class="records-wrapper scrollable">
        <div class="records-table" @click.stop="$store.commit('setMenu', {name: '', id: null})">
            <div id="recordsTop" class="records-header">
                <div class="header-row">
                    <div class="header-cell" v-for="(header, index) in headers" :key="index">
                        {{ header.label }}
                    </div>
                </div>
            </div>
            <div class="records-body">
                <template v-for="day in daysList">
                    <div class="body-row" v-for="(user, index) in day.users" :key="index">
                        <div class="body-cell"  :class="getDayClass(day.weekday, day.day_holiday, day.day_full), {'border-none' : index === (day.users.length - 1)}">
                            <div  >
                                <p v-if="index === 0" :class="{'working' : day.time_card_records?.[day.day_full]?.[user.id]?.stamp_flag === 0 && day.day_full === currentDay}">{{ day.formated_date }}</p>
                            </div>
                        </div>
                        <div class="body-cell" :class="{'border-none' : index === (day.users.length - 1)}">
                            <div >
                                <p>{{ user.name }}</p>
                            </div>
                        </div>
                        <div class="body-cell" :class="{'border-none' : index === (day.users.length - 1)}">
                            <div  >
                                <p v-if="day.shift_records != 0" :class="{ 
                                    'shift-sunday' : (day.shift_records?.[day.day_full]?.[user.id]?.shift_type == 0 
                                    || day.shift_records?.[day.day_full]?.[user.id]?.shift_type == 5 
                                    || day.shift_records?.[day.day_full]?.[user.id]?.shift_type == 14 
                                    || day.shift_records?.[day.day_full]?.[user.id]?.shift_type == 15
                                    || day.shift_records?.[day.day_full]?.[user.id]?.shift_type == 16
                                    || day.shift_records?.[day.day_full]?.[user.id]?.shift_type == 3) }">
                                    {{ day.shift_records?.[day.day_full]?.[user.id]?.abbreviation ?? '--'}}
                                </p>
                                <p v-else-if="day.shift_records == 0 && day.time_card_records[day.day_full]" class="alertTip" title="勤怠予定を作成してください">
                                    勤怠予定未作成
                                </p>
                                <p v-else>--</p>
                            </div>
                        </div>
                        <div class="body-cell" :class="{'border-none' : index === (day.users.length - 1)}">
                            <div class="per-user" >
                                
                                <p v-if="day.time_card_records?.[day.day_full]?.[user.id]?.work_time_edit_flag == 0" :class="lateTimeGenerate(day.time_card_records?.[day.day_full]?.[user.id]?.start_time, day.day_full, user.id)">
                                    {{ $store.state.mobile
                                        ? (day.time_card_records?.[day.day_full]?.[user.id]?.start_time
                                          ? '出勤：' + formatTime(day.time_card_records[day.day_full][user.id].start_time, 'start')
                                          : ' ')
                                        : (day.time_card_records?.[day.day_full]?.[user.id]?.start_time
                                          ? formatTime(day.time_card_records[day.day_full][user.id].start_time, 'start')
                                          : '--') }}
                                </p>
                                <p class="timeEdit" title="勤務時間変更あり" v-else-if="day.time_card_records?.[day.day_full]?.[user.id]?.work_time_edit_flag == 1" :class="lateTimeGenerate(day.time_card_records?.[day.day_full]?.[user.id]?.start_time, day.day_full, user.id)">
                                    {{ $store.state.mobile
                                        ? (day.time_card_records?.[day.day_full]?.[user.id]?.start_time
                                          ? '出勤：' + formatTime(day.time_card_records[day.day_full][user.id].start_time, 'start')
                                          : ' ')
                                        : (day.time_card_records?.[day.day_full]?.[user.id]?.start_time
                                          ? formatTime(day.time_card_records[day.day_full][user.id].start_time, 'start')
                                          : '--') }}
                                          {{ $store.state.mobile ? '※勤務時間変更あり' : '' }}
                                </p>
                                <p v-else>{{$store.state.mobile ? '' : '--'}}</p>
                            </div>   
                        </div>
                        <div class="body-cell" :class="{'border-none' : index === (day.users.length - 1)}">
                            <div class="per-user" >
                                <p v-if="day.time_card_records?.[day.day_full]?.[user.id]?.work_time_edit_flag == 0" :class="overTimeGenerate(day.time_card_records?.[day.day_full]?.[user.id]?.end_time, day.day_full, user.id)">
                                    {{ $store.state.mobile ? 
                                        (day.time_card_records?.[day.day_full]?.[user.id]?.start_time
                                        ? (day.time_card_records?.[day.day_full]?.[user.id]?.end_time
                                            ? '退勤：' + formatTime(day.time_card_records?.[day.day_full]?.[user.id]?.end_time, 'end')
                                            : '退勤： 打刻なし')
                                        : ' ')
                                        : (day.time_card_records?.[day.day_full]?.[user.id]?.start_time
                                            ? (day.time_card_records?.[day.day_full]?.[user.id]?.end_time
                                                ? formatTime(day.time_card_records?.[day.day_full]?.[user.id]?.end_time, 'end')
                                                : '打刻なし')
                                            : '--')
                                    }}
                                </p>
                                <p class="timeEdit" title="勤務時間変更あり" v-else-if="day.time_card_records?.[day.day_full]?.[user.id]?.work_time_edit_flag == 1" :class="overTimeGenerate(day.time_card_records?.[day.day_full]?.[user.id]?.end_time, day.day_full, user.id)">
                                    {{ $store.state.mobile ? 
                                        (day.time_card_records?.[day.day_full]?.[user.id]?.start_time
                                        ? (day.time_card_records?.[day.day_full]?.[user.id]?.end_time
                                            ? '退勤：' + formatTime(day.time_card_records?.[day.day_full]?.[user.id]?.end_time, 'end')
                                            : '退勤： 打刻なし')
                                        : ' ')
                                        : (day.time_card_records?.[day.day_full]?.[user.id]?.start_time
                                            ? (day.time_card_records?.[day.day_full]?.[user.id]?.end_time
                                                ? formatTime(day.time_card_records?.[day.day_full]?.[user.id]?.end_time, 'end')
                                                : '打刻なし')
                                            : '--')
                                    }}
                                    
                                </p>
                                <p v-else>{{$store.state.mobile ? '' : '--'}}</p>
                            </div>
                        </div>
                        <div class="body-cell" :class="{'border-none' : index === (day.users.length - 1)}">
                            <div class="per-user" >
                                <p v-if="today(day.day_full) && day.time_card_records?.[day.day_full]?.[user.id]?.stamp_flag == 0">
                                    {{
                                      $store.state.mobile
                                        ? day.time_card_records?.[day.day_full]?.[user.id]?.start_time
                                          ? '労働時間：' + countdown(day.time_card_records[day.day_full][user.id].start_time)
                                          : ' '
                                        : day.time_card_records?.[day.day_full]?.[user.id]?.start_time
                                        ? countdown(day.time_card_records[day.day_full][user.id].start_time)
                                        : '--'
                                    }}
                                  </p>
                                  <p v-else>
                                    {{
                                      $store.state.mobile
                                        ? (day.time_card_records?.[day.day_full]?.[user.id]?.work_time
                                          ? '労働時間：' +  workTimeFormat(day.time_card_records[day.day_full][user.id].work_time)
                                          : ' ')
                                        : workTimeFormat(day.time_card_records?.[day.day_full]?.[user.id]?.work_time) ?? '--'
                                    }}
                                  </p>
                            </div>
                        </div>
                        <div class="body-cell" :class="{'border-none' : index === (day.users.length - 1)}">
                            <div class="per-user" >
                                <p :class="{ 'shift-saturday' : day.time_card_records?.[day.day_full]?.[user.id]?.over_time }" v-if="user.work_type == 1">
                                    {{ $store.state.mobile ?
                                        (day.time_card_records?.[day.day_full]?.[user.id]?.over_time ? 
                                        '残業時間：' + day.time_card_records[day.day_full][user.id].over_time + '分' : ' ') 
                                        : (day.time_card_records?.[day.day_full]?.[user.id]?.over_time ? 
                                        day.time_card_records[day.day_full][user.id].over_time + '分' : '--') 
                                    }}
                                </p>
                                <p v-else>{{ $store.state.mobile ? ' ' : '--'}}</p>
                            </div>
                        </div> 
                        <div class="body-cell" :class="{'border-none' : index === (day.users.length - 1)}">
                            <div class="per-user" >
                                <p>
                                    {{ day.time_card_records?.[day.day_full]?.[user.id]?.break_time
                                      ? ($store.state.mobile
                                        ? '休憩時間：' + day.time_card_records[day.day_full][user.id].break_time + '分'
                                        : day.time_card_records[day.day_full][user.id].break_time + '分')
                                      : ($store.state.mobile ? ' ' : '--') }}
                                  </p>
                            </div>
                        </div>
                        <div class="body-cell" :class="{'border-none' : index === (day.users.length - 1)}">
                            <div class="per-user" >
                                <p v-if="$store.state.mobile && day.time_card_records?.[day.day_full]?.[user.id]?.allowance.length">諸手当：</p>
                                <p class="allowance-gap" v-if="day.time_card_records?.[day.day_full]?.[user.id]?.allowance.length">
                                    <span :key="index" v-for="(allowance, index) in day.time_card_records[day.day_full][user.id].allowance">{{ allowance.label ? allowance.label : allowance }}</span></p>
                                <p v-else>{{ $store.state.mobile ? ' ' : '--'}}</p>
                            </div>
                        </div>
                        <div class="body-cell" :class="{'border-none' : index === (day.users.length - 1)}">
                            <div class="per-user" >
                                <p>
                                    {{ $store.state.mobile ?
                                        day.time_card_records?.[day.day_full]?.[user.id]?.incident 
                                        ? 'インシデント/アクシデント：' + day.time_card_records[day.day_full][user.id].incident.label : ' ' 
                                        : day.time_card_records?.[day.day_full]?.[user.id]?.incident 
                                            ? day.time_card_records[day.day_full][user.id].incident.label : '--' 
                                    }}
                                </p>
                            </div>
                        </div>
                        <div class="body-cell" :class="{'border-none' : index === (day.users.length - 1)}">
                            <div class="per-user" >
                                <p>{{ $store.state.mobile ?  
                                    day.time_card_records?.[day.day_full]?.[user.id]?.achievement 
                                    ? '目標達成率：' + day.time_card_records[day.day_full][user.id].achievement.label : ' '
                                    :day.time_card_records?.[day.day_full]?.[user.id]?.achievement 
                                    ? day.time_card_records[day.day_full][user.id].achievement.label : '--' 
                                    }}
                                </p>
                            </div>                    
                        </div>
                        <div class="body-cell" :class="{'border-none' : index === (day.users.length - 1)}">
                            <div class="per-user" style="align-items:center;">
                                <p v-if="$store.state.mobile && weathers?.[user.id]?.[day.day_full]">コンディション：</p>
                                <img class="condition-img" v-if="weathers?.[user.id]?.[day.day_full]" :src="'images/icon_' +weathers[user.id][day.day_full].value_int + '.svg'" width="17" height="17" /> 
                                <p v-else>{{$store.state.mobile ? ' ' : '--'}}</p>
                            </div>
                        </div>
                        <div class="body-cell" :class="{'border-none' : index === (day.users.length - 1)}">
                            <div class="per-user" >
                                <p @click.stop="$store.commit('setMenu', {name: 'commentBox', id: day.time_card_records?.[day.day_full]?.[user.id]?.comment?.id})">
                                    {{ $store.state.mobile ? 
                                    day.time_card_records?.[day.day_full]?.[user.id]?.comment 
                                    ? 'コメント：' + commentTextLength(day.time_card_records[day.day_full][user.id].comment.value_text) : ' ' 
                                    : day.time_card_records?.[day.day_full]?.[user.id]?.comment 
                                    ? commentTextLength(day.time_card_records[day.day_full][user.id].comment.value_text) : '--' 
                                    }}
                                </p>
                                <div class="comment-box" v-if="$store.state.menu.name == 'commentBox' && $store.state.menu.id == day.time_card_records?.[day.day_full]?.[user.id]?.comment?.id && day.time_card_records?.[day.day_full]?.[user.id]?.comment">
                                    {{ day.time_card_records?.[day.day_full]?.[user.id]?.comment?.value_text }}
                                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 32 32" style="margin: auto;"><path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path></svg>
                                </div>
                            </div>                    
                        </div>
                        <div class="body-cell" :class="{'border-none' : index === (day.users.length - 1)}">
                            <div >
                                <div style="display:inline-block" v-if="isTodayOrFuture(day.day_full) && user.id == auth_user.id">
                                    <div class="workButton-wrapper">
                                        <button v-if="today(day.day_full) && day.time_card_records?.[day.day_full]?.[user.id]?.stamp_flag == 0" @click="this.$emit('timeStampEnd')" class="workRecords-button">終業</button>
                                        <button v-else-if="today(day.day_full) && day.time_card_records?.[day.day_full]?.[user.id]?.stamp_flag == null" @click="this.$emit('timeStampStart', day.shift_records?.[day.day_full]?.[user.id])" class="workRecords-button">始業</button>
                                        
                                        <button v-if="day.time_card_records?.[day.day_full]?.[user.id]?.work_time == null && day.time_card_records?.[day.day_full]?.[user.id]?.start_time == null" class="workRecords-button" @click="this.$emit('timeStampEdit', day.shift_records?.[day.day_full]?.[user.id], true, user.id)">作成</button>
                                        <p v-else-if="day.time_card_records?.[day.day_full]?.[user.id]?.status_flag == 1">申請中</p>
                                        <p v-else-if="day.time_card_records?.[day.day_full]?.[user.id]?.status_flag == 2">承認済み</p>
                                        <button v-else class="workRecords-button" @click="this.$emit('timeStampEdit', day.time_card_records?.[day.day_full]?.[user.id], false, user.id)">編集</button>
                                    </div>
                                </div>
                                <div style="display:inline-block" v-else-if="auth_user.work_authority > user.work_authority">
                                    <div class="workButton-wrapper">
                                        <button v-if="day.time_card_records?.[day.day_full]?.[user.id]?.status_flag == 1" @click="dailyApproval(user.id,day.day_full)" class="workRecords-button">承認</button>
                                        <button v-if="day.time_card_records?.[day.day_full]?.[user.id]?.status_flag == 1" @click="timeCardRemand(user.id,day.day_full)" class="workRecords-button">差戻</button>
                                        <button v-if="day.time_card_records?.[day.day_full]?.[user.id]?.status_flag == 2" @click="dailyCancel(user.id,day.day_full)" class="workRecords-button">承認取消</button>
                                        <p style="line-height: 2.5" v-else-if="day.time_card_records?.[day.day_full]?.[user.id]?.status_flag == 0">作成中</p>
                                        <p style="line-height: 2.5" v-else-if="day.time_card_records?.[day.day_full]?.[user.id]?.status_flag == 10">差戻中</p>
                                        <button v-if="(auth_user.id == 608 || auth_user.id == 610) && day.time_card_records?.[day.day_full]?.[user.id]?.work_time == null && day.time_card_records?.[day.day_full]?.[user.id]?.start_time == null" class="workRecords-button" @click="this.$emit('timeStampEdit', day.shift_records?.[day.day_full]?.[user.id], true, user.id)">作成</button>
                                        <button v-else-if="auth_user.id == 608 || auth_user.id == 610" class="workRecords-button" @click="this.$emit('timeStampEdit', day.time_card_records?.[day.day_full]?.[user.id], false, user.id)">編集</button>
                                        <p v-else-if="day.time_card_records?.[day.day_full]?.[user.id]?.work_time == null && day.time_card_records?.[day.day_full]?.[user.id]?.start_time == null">--</p>
                                    </div>
                                </div>
                                <p v-else>--</p>
                            </div>
                        </div>
                    </div>
                </template>
                
            </div>
            <div class="records-footer">
                <div class="footer-row" v-for="(user, index) in usersData" :key="index">
                    <div @click="downloadCSV()" class="footer-cell cursor-pointer" >
                        <div style="display:flex; justify-content: center; align-items: center; gap:10px;line-height: normal;">
                            <p v-if="index === 0">集計<br><span v-if="(auth_user.id == 610 || auth_user.id == 608 || auth_user.position_id == 6)">CSVダウンロード</span></p>
                        </div>
                        
                    </div>
                    <div class="footer-cell">
                        {{ user.name }}
                    </div>
                    <div class="footer-cell">
                        <p v-if="!$store.state.mobile">--</p>
                    </div>
                    <div class="footer-cell">
                        <p v-if="!$store.state.mobile">--</p>
                    </div>
                    <div class="footer-cell">
                        <p v-if="!$store.state.mobile">--</p>
                    </div>
                    <div class="footer-cell">
                        <p v-if="$store.state.mobile">労働時間合計：</p>
                        <p>{{ monthAverage?.[user.id]?.month_work_time ? overTimeFormat(monthAverage?.[user.id]?.month_work_time) : '--' }}</p>
                    </div>
                    <div class="footer-cell">
                        <p v-if="$store.state.mobile">残業時間合計：</p>
                        <p v-if="user.work_type == 1 || showOverTime(monthAverage[user.id])">{{ monthAverage?.[user.id]?.month_over_time ? overTimeFormat(monthAverage?.[user.id]?.month_over_time) : '0時間' }}</p>
                        <p v-else>--</p>
                    </div>
                    <div class="footer-cell">
                        <p v-if="!$store.state.mobile">--</p>
                    </div>
                    <div class="footer-cell">
                        <p v-if="!$store.state.mobile">--</p>
                    </div>
                    <div class="footer-cell">
                        <p v-if="!$store.state.mobile">--</p>
                    </div>
                    <div class="footer-cell">
                        <p v-if="!$store.state.mobile">{{ monthAverage?.[user.id]?.month_achievement_average ? monthAverage?.[user.id]?.month_achievement_average : '--'}}</p>
                    </div>
                    <div class="footer-cell">
                        <p v-if="$store.state.mobile">コンディション平均：</p>
                        <img class="condition-img" v-if="monthAverage?.[user.id]?.month_weather_average" :src="'/images/icon_' + monthAverage[user.id].month_weather_average + '.svg'" width="17" height="17"/>
                        <p v-else>--</p>
                    </div>
                    <div class="footer-cell">
                        <p v-if="!$store.state.mobile">--</p>
                    </div>
                    <div class="footer-cell">
                        <p v-if="!$store.state.mobile">--</p>
                    </div>
                </div>
            </div>
        </div>
           
                
           
    </div>
</template>
<script>
    import moment from 'moment'
    export default{
        props: [
            'currentDay', 
            'calendars',
            'auth_user',
            'monthAverage',
            'usersData',
            'shiftStartTime',
            'shiftEndTime',
            'attendanceFlag',
            'weathers',
            'selectedMonth'
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
                
                stampStart: true,
                stampEnd: false,
                
                emojis: ['🌈','☀️','☁️','☂️','⚡','☃️']
                
            }
        },
        computed: {
            
            daysList() {
                return this.calendars.flat().filter(day => Object.keys(day).length !== 0);
            },
        },
        methods: {
            downloadCSV(){
                if(this.auth_user.position_id == 6 || this.auth_user.id == 608 || this.auth_user.id == 610){
                    var csv = '\ufeff' + '日付,メンバー	,予定,出勤,退勤,労働時間,残業時間,休憩時間,諸手当,インシデント,目標達成率,コンディション,コメント\n';
                    Object.values(this.daysList).forEach(obj => {
                        Object.values(obj.users).forEach(el => {
                            var allowanceValues = obj.time_card_records?.[obj.day_full]?.[el.id]?.allowance || [];
                            var allowance = allowanceValues.map(allow => allow.label).join(' ');

                            var commentText = obj.time_card_records?.[obj.day_full]?.[el.id]?.comment.value_text || '';
                            var formattedComment = commentText.replace(/\n/g, ' '); // Replace newline characters with a space

                            var line =
                                obj.formated_date + ',' +
                                el.name + ',' +
                                (obj.shift_records?.[obj.day_full]?.[el.id]?.abbreviation || '') + ',' +
                                (obj.time_card_records?.[obj.day_full]?.[el.id]?.start_time || '') + ',' +
                                (obj.time_card_records?.[obj.day_full]?.[el.id]?.end_time || '') + ',' +
                                this.workTimeFormat(obj.time_card_records?.[obj.day_full]?.[el.id]?.work_time) + ',' +
                                (obj.time_card_records?.[obj.day_full]?.[el.id]?.over_time || '') + '分' + ',' +
                                (obj.time_card_records?.[obj.day_full]?.[el.id]?.break_time || '') + '分' + ',' +
                                (allowance || '') + ',' +
                                (obj.time_card_records?.[obj.day_full]?.[el.id]?.incident.label || '') + ',' +
                                (obj.time_card_records?.[obj.day_full]?.[el.id]?.achievement.label || '') + ',' +
                                (this.emojis[this.weathers?.[el.id]?.[obj.day_full]?.value_int] || '') + ',' +
                                formattedComment + '\n';
                            csv += line;
                        });
                    });
                    let blob = new Blob([csv], { type: 'text/csv' });
                    let link = document.createElement('a');
                    link.href = window.URL.createObjectURL(blob);
                    link.download = 'work_' + (this.selectedMonth + 1) + '月' + '.csv';
                    link.click();
                }else{
                    return
                }
                

            },
            showOverTime(monthAvg){
                if(monthAvg){
                    if(monthAvg.month_should_work_time && monthAvg.month_work_time && monthAvg.month_annual_leave != null){
                        return monthAvg.month_work_time + monthAvg.month_annual_leave > monthAvg.month_should_work_time
                    }else{
                        return monthAvg.month_work_time > monthAvg.month_should_work_time
                    }
                }
                return false
            },
            
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
                const now = moment();
                const startTime = moment(dateTime);
                let timeDifference = now.diff(startTime);
                
                if (timeDifference <= 0) {
                    return '0時間0分';
                }
                const duration = moment.duration(timeDifference);
                const hours = Math.floor(duration.asHours());
                const minutes = Math.floor(duration.asMinutes() % 60);

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
                return value && value.length > 10 ? value.slice(0, 6) + "..." : value;

            },
            today(date){
                return date === this.currentDay
            },
            isTodayOrFuture(date) {
                return this.currentDay >= date;
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
            
            formatTime(time, val){
                if(!time) return '--'
                
                if(/^([01]\d|2[0-3]):[0-5]\d:[0-5]\d$/.test(time)){
                    var date = new Date("2000-01-01T" + time); // get current date
                    var minutes = date.getMinutes();
                    if(val == 'start'){
                        var rounded = Math.ceil(minutes / 15) * 15;
                    }else if(val == 'end'){
                        var rounded = Math.floor(minutes / 15) * 15;
                    }
                    date.setMinutes(rounded);
                    date.setSeconds(0);
                    var hours = date.getHours();
                    var minutes = date.getMinutes();

                    // pad with zero if needed
                    hours = hours < 10 ? '0' + hours : hours;
                    minutes = minutes < 10 ? '0' + minutes : minutes;
                    let roundedTime = hours + ':' + minutes
                    return roundedTime

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
                        ).catch(function (error) {
                            if (error.response) this.errorToast('エラーが発生しました。 ' + error.response.data.message)
                            else if (error.request) this.errorToast('エラーが発生しました。')
                            else this.errorToast('エラーが発生しました。 ' + error.message)     
                        }.bind(this))
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
                ).catch(function (error) {
                    if (error.response) this.errorToast('エラーが発生しました。 ' + error.response.data.message)
                    else if (error.request) this.errorToast('エラーが発生しました。')
                    else this.errorToast('エラーが発生しました。 ' + error.message)     
                }.bind(this))
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
                ).catch(function (error) {
                    if (error.response) this.errorToast('エラーが発生しました。 ' + error.response.data.message)
                    else if (error.request) this.errorToast('エラーが発生しました。')
                    else this.errorToast('エラーが発生しました。 ' + error.message)     
                }.bind(this))
            },
            lateTimeGenerate(start_time, day, userId) {
                var date = new Date("2000-01-01T" + start_time); // get current date
                var minutes = date.getMinutes();
                var rounded = Math.ceil(minutes / 15) * 15;
                date.setMinutes(rounded);
                date.setSeconds(0);
                var hours = date.getHours();
                var minutes = date.getMinutes();

                // pad with zero if needed
                hours = hours < 10 ? '0' + hours : hours;
                minutes = minutes < 10 ? '0' + minutes : minutes;
                let roundedTime = hours + ':' + minutes
                if (roundedTime && this.shiftStartTime && userId == this.auth_user.id) {
                    const value_start_s = new Date(day + ' ' + roundedTime).getTime();
                    const shift_start_s = new Date(day + ' ' + this.shiftStartTime).getTime();
                    
                    return value_start_s > shift_start_s ? 'late-class' : null;
                }
                
            },
            overTimeGenerate(end_time, day, userId) {
                var date = new Date("2000-01-01T" + end_time); // get current date
                var minutes = date.getMinutes();
                var rounded = Math.floor(minutes / 15) * 15;
                date.setMinutes(rounded);
                date.setSeconds(0);
                var hours = date.getHours();
                var minutes = date.getMinutes();

                // pad with zero if needed
                hours = hours < 10 ? '0' + hours : hours;
                minutes = minutes < 10 ? '0' + minutes : minutes;
                let roundedTime = hours + ':' + minutes
                if(roundedTime && this.shiftEndTime && userId == this.auth_user.id){
                    const value_end_s = new Date(day + ' ' + roundedTime).getTime();
                    const shift_end_s = new Date(day + ' ' + this.shiftEndTime).getTime();
                     
                    if (value_end_s > shift_end_s) {
                        return 'over-class';
                    } else if (value_end_s < shift_end_s) {
                        return 'early-class';
                    }
                }
                

                return null;
            }
        },
        
    }
</script>