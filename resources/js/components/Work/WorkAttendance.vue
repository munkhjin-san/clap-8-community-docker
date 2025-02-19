<template>
    <div class="work-modal" @mousedown="emit('closeModal')">
        <div class="work-modal-inner" @mousedown.stop >
            <Transition name="modalFade">
                <div class="work-loader" v-if="loading == 0">
                    <div class="spinner-mini" style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
                </div> 
            </Transition>
            <div v-if="attendanceData">            
                <div class="recordFormTitle" style="background: var(--background-color);">
                    <p style="font-size: 18px;">勤怠確定</p>
                    <div @click="emit('closeModal')" class="cursor-pointer" style="display:flex;align-items:center;margin: auto 0 auto auto;">
                        <button class="work-delete-button" @click.stop="attendanceCreate" v-if="!attendanceData.attendance_flag && (auth.activeUser.id == 610 || auth.activeUser.id  == 608)">休業確定</button>
                        <button class="work-delete-button" @click.stop="deleteAttendance" v-if="attendanceData.attendance_flag && (auth.activeUser.id  == 610 || auth.activeUser.id  == 608)">勤怠確定を取り下げる</button>
                        <svg class="modalWindowCloseButton" version="1.1" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 32 32">
                            <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                        </svg>
                    </div>
                </div>
                <div style="font-size:14px;line-height:2.0;">
                    <p>※ 確定した内容で今月の給与計算を行います。必ず内容を本人確認の上「今月の勤怠を確定」を行なってください。</p>
                    <p>※「日報」の差し戻しがある場合は、再申請または削除を行なってください。</p>
                    <p>※ 上長より承認されていない「日報」がある場合は勤怠確定を行うことができません。</p>
                </div>
                <div class="attendance-wrapper">
                    <div class="attendance-row">
                        <div class="attendance-title">
                            <span>勤怠確定ステータス</span>
                        </div>
                        <div class="attendance-value">
                            <span :class="{'shift-sunday' : !attendanceData.attendance_flag, 'shift-saturday' : attendanceData.attendance_flag }">{{ attendanceData.attendance_flag == true ? '確定済み' : '未確定' }}</span>
                        </div>
                    </div>
                    <div class="attendance-row">
                        <div class="attendance-title">
                            <span>勤怠年月</span>
                        </div>
                        <div class="attendance-value">
                            <span>{{monthFormat}}</span>
                        </div>
                    </div>
                    <div class="attendance-row">
                        <div class="attendance-title">
                            <span>氏名</span>
                        </div>
                        <div class="attendance-value">
                            <span>{{attendanceData.user.name}}</span>
                        </div>
                    </div>
                    <div class="attendance-row" v-if="attendanceData.user.position_id !== 15">
                        <div class="attendance-title">
                            <span>予定稼働日数 / 所定労働時間</span>
                        </div>
                        <div class="attendance-value">
                            <span>{{ shiftDay(attendanceData) }}</span>
                        </div>
                    </div>
                    <div class="attendance-row">
                        <div class="attendance-title">
                            <span>実稼働日数 / 実稼働時間</span>
                        </div>
                        <div class="attendance-value">
                            <span>{{workedDay(attendanceData)}}</span>
                        </div>
                    </div>
                    <div class="attendance-row">
                        <div class="attendance-title">
                            <span>承認済み日報</span>
                        </div>
                        <div class="attendance-value">
                            <span>{{ attendanceData.approved_count ? attendanceData.approved_count + '日' : '--'}}</span>
                        </div>
                    </div>
                    <div class="attendance-row">
                        <div class="attendance-title">
                            <span>未承認日報</span>
                        </div>
                        <div class="attendance-value">
                            <span>{{ attendanceData.unapproved_count ? attendanceData.unapproved_count + '日' : '--'}}</span>
                        </div>
                    </div>
                    <div class="attendance-row" v-if="attendanceData.user.position_id !== 15">
                        <div class="attendance-title">
                            <span>未承認勤怠予定</span>
                        </div>
                        <div class="attendance-value">
                            <span>{{ attendanceData.unapproved_shift_count ? attendanceData.unapproved_shift_count + '日' : '--'}}</span>
                        </div>
                    </div>
                    <div class="attendance-row">
                        <div class="attendance-title">
                            <span>年休</span>
                        </div>
                        <div class="attendance-value">
                            <span>{{ attendanceData.annual_leave ? timeFormat(attendanceData.annual_leave) : '--' }}</span>
                        </div>
                    </div>
                    <div class="attendance-row" v-if="attendanceData.user.position_id !== 15">
                        <div class="attendance-title">
                            <span>休日出勤</span>
                        </div>
                        <div class="attendance-value">
                            <span>{{ holidayWork(attendanceData) }}</span>
                        </div>
                    </div>
                    <div class="attendance-row" v-if="attendanceData.user.position_id !== 15">
                        <div class="attendance-title">
                            <span>慶弔休暇</span>
                        </div>
                        <div class="attendance-value">
                            <span>{{ attendanceData.condolence_leave + '日' }}</span>
                        </div>
                    </div>
                    <div class="attendance-row" v-if="attendanceData.user.position_id !== 15">
                        <div class="attendance-title">
                            <span>転勤休暇</span>
                        </div>
                        <div class="attendance-value">
                            <span>{{ attendanceData.transfer_leave + '日' }}</span>
                        </div>
                    </div>
                    <div class="attendance-row" v-if="attendanceData.user.position_id !== 15">
                        <div class="attendance-title">
                            <span>ODA休暇</span>
                        </div>
                        <div class="attendance-value">
                            <span>{{ attendanceData.oda_leave + '日' }}</span>
                        </div>
                    </div>
                    <div class="attendance-row" v-if="attendanceData.user.position_id == 6">
                        <div class="attendance-title">
                            <span>代休</span>
                        </div>
                        <div class="attendance-value">
                            <span>{{ attendanceData.comp_holiday + '日' }}</span>
                        </div>
                    </div>
                    <div class="attendance-row" v-if="attendanceData.user.position_id !== 15">
                        <div class="attendance-title">
                            <span>残業</span>
                        </div>
                        <div class="attendance-value">
                            <span>{{ overTime(attendanceData.month_over_time) }}</span>
                        </div>
                    </div>
                    <div class="attendance-row" v-if="attendanceData.user.position_id !== 15">
                        <div class="attendance-title">
                            <span>深夜勤務</span>
                        </div>
                        <div class="attendance-value">
                            <span>{{ attendanceData.night_over_time }}分</span>
                        </div>
                    </div>
                    <div class="attendance-row" v-if="attendanceData.user.position_id !== 15">
                        <div class="attendance-title">
                            <span>諸手当</span>
                        </div>
                        <div class="attendance-value">
                            <p>宿泊日当 : {{ attendanceData.month_move_allowance_count }}</p>
                            <p><br>遠方手当 : {{ attendanceData.month_stay_allowance_count }}</p>
                            <p><br>待機手当 : {{ attendanceData.month_waiting_allowance_count }}</p>
                            <p><br>在宅手当 : {{ attendanceData.month_remote_allowance_count }}</p>
                        </div>
                    </div>
                    <div class="attendance-row">
                        <div class="attendance-title">
                            <span>経費</span>
                        </div>
                        <div class="attendance-value">
                            <span>{{ attendanceData.annual_costs }}円</span>
                        </div>
                    </div>
                    <div class="attendance-row" v-if="attendanceData.user.position_id === 15">
                        <div class="attendance-title">
                            <span>インセンティブ</span>
                        </div>
                        <div class="attendance-value">
                            <span>{{ attendanceData.annual_incentives }}件</span>
                        </div>
                    </div>
                </div>
                <LoaderButton style="margin-top:30px;" :loading="sending" @triggered="attendanceConfirm" :content="buttonTexts"/>
            </div>
        </div>
    </div>
</template>
<script setup>
    import moment from 'moment'
    import LoaderButton from '../Global/LoaderButton.vue'
    import { computed, inject, onMounted, ref } from 'vue';
    import { useAuthUserStore } from '@/store/auth';
    import { getAttendanceData } from '../../utils/workApi';
    import { timeFormat } from '@/utils/tools';
    
    const emit = defineEmits(['reload', 'closeModal'])
    const props = defineProps([
            'selectedYear', 
            'selectedMonth', 
            'usersCheckArray'
        ])
    const auth = useAuthUserStore()
    const { confirm, notify, info } = inject('dialog')
    const attendanceData = ref(null)
    const sending = ref(false)
    const disableButton = computed(() => {
        return attendanceData.value.unapproved_shift_count > 0 || attendanceData.value.unsaved_count > 0 || attendanceData.value.unapproved_count > 0 || attendanceData.value.attendance_flag === true
    })
    const loading = ref(0)
    onMounted(() => {
        fetchAttendanceData()
    })
    const fetchAttendanceData = async() => {
        let yearMonth = moment([props.selectedYear, props.selectedMonth]).format('YYYY-MM')
        try{
            attendanceData.value  = await getAttendanceData(yearMonth, props.usersCheckArray)
            loading.value ++
        }catch (e){
            notify(e?.message)
        }
    }
    const buttonTexts = computed(() => {
        if(attendanceData.value.unapproved_count > 0){
            return '未承認日報があります'
        }else if(attendanceData.value.unsaved_count > 0){
            return '未作成日報があります'
        }else if(attendanceData.value.unapproved_shift_count > 0){
            return '未承認勤怠予定があります。'
        }else if(attendanceData.value.attendance_flag){
            return '確定済み'
        }else{
            return '今月の勤怠を確定する'
        }
    })

    const deleteAttendance = async() => {
        const date = moment([props.selectedYear, props.selectedMonth]).format('YYYY-MM')
        const params = {
            date_year_month : date,
            user_id : props.usersCheckArray[0]
        }
        const answer = await confirm('今月の勤怠確定を取り消しますか?。')
        if(!answer) return        
        try{
            await axios.post('attendance_delete', params)
            info('勤怠確定を取り消しました。')
            emit('closeModal')
            emit('reload')
        }catch (e){
            notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
        }   
    }
    const monthFormat = computed(() => {
        let yearMonth = moment([props.selectedYear, props.selectedMonth]).format('YYYY-MM')
        return moment(yearMonth).format("YYYY年M月");
    })
    const shiftDay = (data) => {
        let days = data.shift_count + '日'
        let hours = data.should_work/60 + '時間'
        return `${days} / ${hours}`
    }
    const workedDay = (data) => {
        let days = data.workedday_count + '日'
        let minutes = data.worked_time
        if (minutes === 0) {
            minutes = '0時間';
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
            
            minutes = formatted;
        }
        return `${days} / ${minutes}`
    }
    const annualTime = (data) => {
        return (data.annual_leave)/60 + '時間' 
    }
    const holidayWork = (data) => {
        let days = data.holiday_count + '日'
        let minutes = data.holiday_worked_time
        if (minutes === 0) {
            minutes = '0時間';
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
            
            minutes = formatted;
        }
        return `${days}(${minutes})`
    }
    const overTime = (minutes) => {
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
    }
    const attendanceConfirm = async() => {
        if(disableButton.value) return
        let yearMonth = moment([props.selectedYear, props.selectedMonth]).format('YYYY-MM')
        const params = {
            date_year_month: yearMonth,
            user: attendanceData.value.user,
            shift_working_hours: attendanceData.value.user.work_time_day * attendanceData.value.should_work_days,
            shift_working_days: attendanceData.value.shift_count,
            worked_days: attendanceData.value.workedday_count,
            holiday_worked_days: attendanceData.value.holiday_count,
            annual_leave: attendanceData.value.annual_leave/60,
            condolence_leave: attendanceData.value.condolence_leave,
            transfer_leave: attendanceData.value.transfer_leave,
            oda_leave: attendanceData.value.oda_leave,
            worked_hours: attendanceData.value.worked_time,
            worked_hours_no_over_time: attendanceData.value.worked_time - attendanceData.value.month_over_time - attendanceData.value.night_over_time,
            over_time: attendanceData.value.month_over_time,
            night_work_time: attendanceData.value.night_over_time,
            stay_pay: attendanceData.value.month_stay_allowance_count,
            move_pay: attendanceData.value.month_move_allowance_count,
            waiting_pay: attendanceData.value.month_waiting_allowance_count,
            remote_pay: attendanceData.value.month_remote_allowance_count,
            expenses: attendanceData.value.annual_costs,
            incentive: attendanceData.value.annual_incentives
        }
        try{
            sending.value = true
            await axios.post('/attendance_confirm', params)
            info('確定しました。')
            emit('closeModal')
            emit('reload')
        }catch (e){
            notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
            sending.value = false
        }
    }
    const attendanceCreate = async() => {
        const date = moment([props.selectedYear, props.selectedMonth]).format('YYYY-MM')
        
        const params = {
            date : date,
            user : attendanceData.value.user
        }
        try{
            await axios.post('/attendance_closed', params)
            info('確定しました。')
            emit('closeModal')
            emit('reload')
        }catch (e){
            notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
        }
    }
</script>