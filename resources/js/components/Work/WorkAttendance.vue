<template>
    <Modal @close="emit('closeModal')" :loader="loading == 0">
        <template #title>
            <p>勤怠確定</p>
        </template>
        <template #menu>
            <div class="cursor-pointer" style="display:flex;align-items:center;margin: auto 0 auto auto;">
                <button class="work-delete-button" @click.stop="attendanceCreate" v-if="attendanceData && !attendanceData.attendance_flag && (auth.activeUser.id == 610 || auth.activeUser.id  == 608)">休業確定</button>
                <button class="work-delete-button" @click.stop="deleteAttendance" v-if="attendanceData && attendanceData.attendance_flag && (auth.activeUser.id  == 610 || auth.activeUser.id  == 608)">勤怠確定を取り下げる</button>
            </div>
        </template>
        <template #content>
            
            <div v-if="attendanceData"> 
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
                            <span>法定上の所定労働時間</span>
                        </div>
                        <div class="attendance-value">
                            <span>{{ mustDay(attendanceData) }}</span>
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
                    <div class="attendance-row" v-if="attendanceData.user.position_id === 15">
                        <div class="attendance-title">
                            <span>研修時間</span>
                        </div>
                        <div class="attendance-value">
                            <span>{{ attendanceData.month_training_minutes }}分</span>
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
                            <p><br>マイカー手当 : {{ attendanceData.month_vehicle_allowance_count }}</p>
                            <p><br>通勤特別手当 : {{ attendanceData.month_special_commute_allowance_count }}</p>
                            <p><br>在宅手当 : <br>
                                <p class="ml-4 leading-normal mt-2">
                                    <p>個人都合 : {{ attendanceData.month_remote_personal_allowance_count }}</p>
                                    <p>会社都合 : {{ attendanceData.month_remote_company_allowance_count }}</p>
                                </p>
                            </p>
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
                    <div class="attendance-row">
                        <div class="attendance-title">
                            <span>マイカー走行距離合計</span>
                        </div>
                        <div class="attendance-value">
                            <span>{{ attendanceData.mileage }}km</span>
                        </div>
                    </div>
                </div>
                <LoaderButton style="margin-top:30px;" :loading="sending" @triggered="attendanceConfirm" :content="buttonTexts"/>
            </div>
        </template>
    </Modal>
</template>
<script setup>
import LoaderButton from '../Global/LoaderButton.vue'
import { computed, onMounted, ref } from 'vue';
import { useAuthUserStore } from '@/store/auth';
import { getAttendanceData } from '../../utils/workApi';
import { timeFormat } from '@/utils/tools';
import { DateTime } from 'luxon';
import { useApi } from '@/composables/api';
import { useDialog } from '@/composables/dialog';
import Modal from '../Global/Modal.vue';
    
    const emit = defineEmits(['reload', 'closeModal'])
    const props = defineProps([
        'selectedYear', 
        'selectedMonth', 
        'usersCheckArray'
    ])
    const auth = useAuthUserStore()
    const attendanceData = ref(null)
    const sending = ref(false)
    const disableButton = computed(() => {
        return attendanceData.value.unapproved_shift_count > 0 || attendanceData.value.unsaved_count > 0 || attendanceData.value.unapproved_count > 0 || attendanceData.value.attendance_flag === true
    })
    const loading = ref(0)
    const api = useApi()
    const { ping } = useDialog()
    onMounted(() => {
        fetchAttendanceData()
    })

    const dateInstance = computed(() => {
        return DateTime.fromObject({year: props.selectedYear, month: props.selectedMonth})
    })
    const fetchAttendanceData = async() => {
        let yearMonth = dateInstance.value.toFormat('yyyy-MM')
        try{
            attendanceData.value  = await getAttendanceData(yearMonth, props.usersCheckArray)
            loading.value ++
        }catch (e){
            ping(e?.message)
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
        const date = dateInstance.value.toFormat('yyyy-MM')
        const params = {
            date_year_month : date,
            user_id : props.usersCheckArray[0]
        }   
      
        await api.post('attendance_delete', params, {
            toast: '勤怠確定を取り消しました。',
            ask: '今月の勤怠確定を取り消しますか?。'
        })
        emit('closeModal')
        emit('reload')

    }
    const monthFormat = computed(() => {
        return dateInstance.value.toFormat('yyyy年M月')
    })
    const mustDay = (data) => {
        let days = data.should_work_days + '日'
        let hours = data.should_work/60 + '時間'
        return `${days} / ${hours}`
    }
    const shiftDay = (data) => {
        let days = data.shift_count + '日'
        let hours = data.planned_work/60 + '時間'
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
    const noOverTimeHours = computed(() => {
        if (attendanceData.value.worked_time > attendanceData.value.should_work) {
            return attendanceData.value.worked_time - attendanceData.value.month_over_time - attendanceData.value.night_over_time
        } else {
            return attendanceData.value.worked_time
        }
    })
    const attendanceConfirm = async() => {
        if(disableButton.value) return
        let yearMonth = dateInstance.value.toFormat('yyyy-MM')
        const params = {
            date_year_month: yearMonth,
            user: attendanceData.value.user,
            shift_working_hours: attendanceData.value.should_work,
            shift_working_days: attendanceData.value.shift_count,
            worked_days: attendanceData.value.workedday_count,
            holiday_worked_days: attendanceData.value.holiday_count,
            annual_leave: attendanceData.value.annual_leave/60,
            condolence_leave: attendanceData.value.condolence_leave,
            transfer_leave: attendanceData.value.transfer_leave,
            oda_leave: attendanceData.value.oda_leave,
            worked_hours: attendanceData.value.worked_time,
            worked_hours_no_over_time: noOverTimeHours.value,
            over_time: attendanceData.value.month_over_time,
            night_work_time: attendanceData.value.night_over_time,
            stay_pay: attendanceData.value.month_stay_allowance_count,
            move_pay: attendanceData.value.month_move_allowance_count,
            waiting_pay: attendanceData.value.month_waiting_allowance_count,
            vehicle_pay: attendanceData.value.month_vehicle_allowance_count,
            special_commute_pay: attendanceData.value.month_special_commute_allowance_count,
            remote_personal_pay: attendanceData.value.month_remote_personal_allowance_count,
            remote_company_pay: attendanceData.value.month_remote_company_allowance_count,
            expenses: attendanceData.value.annual_costs,
            incentive: attendanceData.value.annual_incentives,
            mileage: attendanceData.value.mileage,
            training_time: attendanceData.value.month_training_minutes
        }
 
        sending.value = true
        await api.post('/attendance_confirm', params, {
            toast: '確定しました。'
        })
        emit('closeModal')
        emit('reload')
        sending.value = false      
    }
    const attendanceCreate = async() => {
        const date = dateInstance.value.toFormat('yyyy-MM')        
        const params = {
            date : date,
            user : attendanceData.value.user
        }
        await api.post('/attendance_closed', params, {
            toast: '確定しました。'
        })
        emit('closeModal')
        emit('reload')

    }
</script>