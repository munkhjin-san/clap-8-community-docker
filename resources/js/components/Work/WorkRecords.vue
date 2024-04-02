<template>
    <div class="records-wrapper" ref="wrapper" :style="{height: `calc(100% - ${headerHeight.value}px)`}">
        <Teleport to="body">
            <OverTimeRequest 
                v-if="overtimeRequestData"
                @close="overtimeRequestData = null"
                :data="overtimeRequestData"
                :statuses="statuses"
            />
        </Teleport>
        <div v-if="!records.length" class="absolute-div">
            メンバーを選択してください。
        </div>  
        <v-data-table-virtual
            :headers="headers"
            :items="records"
            height="100%"
            :loading="loading == 0"
            :hide-no-data="true"
            item-value="name"
            id="dt-responsive-table"
            class="p-datatable-table"
            dense
            disable-sort
        >
            <template v-slot:loading>
                <Transition name="modalFade">
                    <div class="work-loader">
                        <div class="spinner-mini" style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
                    </div> 
                </Transition>
            </template>
            <template v-slot:item="{ item }">
                <tr :class="['w-row', {'last-row': item.last}]" >
                    <td :class="[getDayClass(item.day_full), {'working' : item.time_card?.stamp_flag == 0}]">{{ dayFormatter(item.day_show) }}</td>
                    <td style="white-space: nowrap;">{{  item.user_name }}</td>
                    <td :class="getShiftClass(item.shift)">{{  item.shift?.apply_request?.status == 1 ? item.shift?.shift_type?.abbreviation : item.shift?.apply_request?.status == 0 ? '申請中' : ''}}</td>
                    <td :class="earlyOrLateClass(item, 'start_time')">{{  timeFormatter(item?.time_card?.start_time, item?.time_card?.end_time, 'start') }}</td>
                    <td :class="earlyOrLateClass(item, 'end_time')">{{ timeFormatter(item?.time_card?.start_time, item?.time_card?.end_time, 'end') }}</td>
                    <td>{{ workTimeDisplay(item?.time_card) }}</td>
                    <td>{{ overTimeDisplay(item) }}</td>
                    <td>{{ breakTimeDisplay(item) }}</td>
                    <td style="word-break: auto-phrase;">{{ hasAllowance(item?.time_card?.custom_field_data_records) }}</td>
                    <td>{{ hasValue(item, 40, 'label') }}</td>
                    <td>{{ hasValue(item, 41, 'label') }}</td>
                    <td v-html="hasCondition(item.weather)"></td> 
                    <td>
                        <div style="position: relative;">
                            <div @click.stop="hasValue(item, 39, 'value_text') !== '' ? menu.setMenu({name: 'commentBox', id: item.time_card?.id}) : false">{{ hasValue(item, 39, 'value_text', true) }}</div>
                            <div @click="menu.close()" class="comment-box" id="commentBox" v-if="menu.name == 'commentBox' && menu.id == item.time_card?.id">{{ hasValue(item, 39, 'value_text') }}                                
                            </div>
                        </div>
                    </td>
                    <td>
                        <div style="position: relative;white-space:nowrap;" class="workButton-wrapper mt-10" v-if="ableTo(item)">
                            <div v-if="responsive.mobile">残業申請:</div>
                            <div @click.stop="menu.setMenu({name: 'approveBox', id: item.shift?.overtime_request?.id})" v-if="item?.shift?.overtime_request">{{ overTimeRequestDisplay(item) }}</div>
                            <div style="display:inline-block;" v-else-if="item.user_id == auth.activeUser.id">
                                <CommandButton v-if="ableToRequestOvertime(item)" @select="overtimeRequest(item)" :buttons="[{name: '申請'}]"/>
                            </div>
                            <div @click="menu.close()" class="comment-box" id="approveBox" style="padding: 10px; display:flex; flex-direction: column; gap: 10px;" v-if="menu.name == 'approveBox' && menu.id == item.shift?.overtime_request?.id">
                                {{ overTimeRequestDisplay(item) }} <br>
                                {{ item.shift?.overtime_request?.content }}
                            </div>
                            <div style="display:inline-block;" v-if="auth.activeUser.work_authority > item.work_authority">
                                <CommandButton v-if="item?.shift?.overtime_request?.status == 1" :buttons="[{name: '承認', value: 2}, {name: '差戻', value: 0}]" @select="(button) => respondOvertime(item?.shift?.overtime_request, button.value, button.name)"/>
                                <CommandButton v-if="item?.shift?.overtime_request?.status == 2" :buttons="[{name: '承認取消', value: 1}]" @select="(button) => respondOvertime(item?.shift?.overtime_request, button.value, button.name)"/>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="workButton-wrapper" v-if="isTodayOrFuture(item.day_full) || auth.activeUser.id == 610">
                            <div v-if="responsive.mobile">日報申請:</div>
                            <div style="display:inline-block" v-if="item.user_id == auth.activeUser.id">
                                <CommandButton
                                    v-if="buttonsCollection(item).length" 
                                    :buttons="buttonsCollection(item)"
                                    @select="(button) => emit(button.value, item)"
                                />
                                <p v-if="item?.time_card?.status_flag == 1">申請中</p>
                                <p v-else-if="item?.time_card?.status_flag == 2">承認済み</p>
                            </div>
                            <div style="display:inline-block" v-else-if="auth.activeUser.work_authority > item.work_authority">
                                
                                <div class="workButton-wrapper">
                                    <p style="line-height: 2.5;white-space: nowrap;" v-if="item?.time_card?.status_flag == 0">作成中</p>
                                    <p style="line-height: 2.5;white-space: nowrap;" v-else-if="item?.time_card?.status_flag == 10">差戻中</p>
                                    <CommandButton 
                                        v-if="manageButtons(item).length"
                                        :buttons="manageButtons(item)"
                                        @select="(button) => button.value == 'timeStampEdit' || button.value == 'timeStampDelete' ? emit(button.value, item) : dailyButtons(button.value, item)"
                                    />
                                </div>
                            </div>
                        </div>
                        
                    </td>
                </tr>
                
            </template>
            <template v-slot:body.append>
                <tr id="bottomTotal" class="w-row" style="background-color: #606060;color:white" v-for="(user, index) in usersData">
                    <td style="border-bottom: thin solid transparent;">
                        <div v-if="index == 0">
                            <span>集計</span>
                            <div class="cursor-pointer" @click="exportCSV()" v-if="auth.activeUser.id == 610 || auth.user.position_id == 6 || auth.activeUser.id == 608">CSV</div>
                        </div>
                    </td>
                    <td>{{ user.name }}</td>
                    <td v-if="!responsive.mobile"></td>
                    <td v-if="!responsive.mobile"></td>
                    <td v-if="!responsive.mobile"></td>
                    <td>{{ workTotalTimeFormat(monthAverage[user.id]?.month_work_time) }}</td>
                    <td>
                        <p v-if="user.work_type == 1 || showOverTime(monthAverage[user.id])">
                            {{ monthAverage?.[user.id]?.month_over_time ? overTimeFormat(monthAverage?.[user.id]?.month_over_time) : '' }}
                        </p>
                        <p v-else></p>
                    </td>
                    <td v-if="!responsive.mobile"></td>
                    <td v-if="!responsive.mobile"></td>
                    <td v-if="!responsive.mobile"></td>
                    <td>{{ monthAverage[user.id]?.month_achievement_average }}</td>
                    <td v-html="hasCondition(monthAverage[user.id]?.month_weather_average)"></td>
                    <td v-if="!responsive.mobile"></td>
                    <td v-if="!responsive.mobile"></td>
                    <td v-if="!responsive.mobile"></td>
                </tr>
            </template>            
                
            
        </v-data-table-virtual>
             
    </div>
</template>
<script setup>
import { VDataTableVirtual } from 'vuetify/components/VDataTable'
import moment from 'moment'
import { inject, ref } from 'vue';
import { useMenuStore } from "@/store/menu";
import { useResponsive } from '@/store/responsive';
import { useAuthUserStore } from '@/store/auth';
import holiday_jp from '@holiday-jp/holiday_jp'
import { mkConfig, generateCsv, download } from "export-to-csv";
import OverTimeRequest from './OverTimeRequest.vue';
import CommandButton from '../Global/CommandButton.vue'
    const menu = useMenuStore()
    const responsive = useResponsive()
    const auth = useAuthUserStore()
    const props = defineProps([
        'currentDay', 
        'monthAverage',
        'usersData',
        'selectedMonth',
        'records',
        'loading',
        'selectedYear',
        'headerHeight',
        'attendanceFlag'
    ]) 
    const { confirm, notify, info } = inject('dialog')
    const emit = defineEmits(['reload', 'timeStampDelete'])
    const headers = ref([
        { title: '日付'},
        { title: 'メンバー'},
        { title: '予定'},
        { title: '出勤'},
        { title: '退勤'},
        { title: '労働時間'},
        { title: '時間外'},
        { title: '休憩時間'},
        { title: '諸手当'},
        { title: 'インシデント'},
        { title: '目標達成率'},
        { title: 'コンディション'},
        { title: 'コメント'},
        { title: '残業申請'},
        { title: '日報申請'},
        
    ])
    const statuses = ['差戻中', '申請中', '承認済み']
    const overtimeRequestData = ref(null)
    const holiday = (day) => {
        const holidays = holiday_jp.between(new Date(props.selectedYear + '-01-01'), new Date(props.selectedYear + '-12-31'));
        return holidays.find(h => moment(h.date).isSame(day, 'day'));
    }
    const wrapper = ref(null)
    const dayFormatter = (value) => {
        if(value){
            const date =  moment(value).format('M / D (dd)')
            return date
        }
    }
    const hasCondition = (index) => {
        const mobileTitle = responsive.mobile ? 'コンディション : ' : ''
        if(index){
            return `<div class="condition-area"><div>${mobileTitle}</div><img class="condition-img" src="images/icon_${index}.svg" width="17" height="17"/></div>`
        }
        return responsive.mobile ? '' : ''
        
    }
    const dailyButtons = (value, item) => {
        console.log(value)
        switch (value) {
            case 0:
                dailyApproval(item)
                break
            case 1:
                timeCardRemand(item)
                break
            default:
                dailyCancel(item)
                break
        }
    }
    const manageButtons = (item) => {
        const buttons = []
        if(item?.time_card?.status_flag == 1){
            const temp = {
                name: '承認',
                value: 0
            }
            buttons.push(temp)
            const backTemp = {
                name: '差戻',
                value: 1
            }
            buttons.push(backTemp)
        } else if(item?.time_card?.status_flag == 2){
            const temp = {
                name: '承認取消',
                value: 2
            }
            buttons.push(temp)
        }
        if(!props.attendanceFlag){
            if((auth.activeUser.id == 608 || auth.activeUser.id == 610) && item?.time_card?.work_time == null && item?.time_card?.start_time == null){
                const temp = {
                    name: '作成',
                    value: 'timeStampEdit'
                }
                buttons.push(temp)
            } else if (auth.activeUser.id == 608 || auth.activeUser.id == 610){
                const temp = {
                    name : '編集',
                    value: 'timeStampEdit',
                }
                buttons.push(temp)
                const tempDelete = {
                    name: '削除',
                    value: 'timeStampDelete',
                }
                buttons.push(tempDelete)
            }
        }
        
        return buttons

    }
    const buttonsCollection = (item) => {
        const buttons = []
        if(startOrEnd(item, null)){
            const temp = {
                name: '始業',
                value: 'timeStampStart'
            }
            buttons.push(temp)
        }else if(startOrEnd(item, 0)){
            const temp = {
                name: '終業',
                value: 'timeStampEnd'
            }
            buttons.push(temp)
        }
        if(item?.time_card?.status_flag == 10 || item?.time_card?.status_flag == 0){
            const temp = {
                name : '編集',
                value: 'timeStampEdit',
            }
            buttons.push(temp)
            const tempDelete = {
                name: '削除',
                value: 'timeStampDelete',
            }
            buttons.push(tempDelete)
        }
        
        if(item.time_card?.work_time == null && item.time_card?.start_time == null && !props.attendanceFlag){
            const temp = {
                name: '作成',
                value: 'timeStampEdit'
            }
            buttons.push(temp)
        }
        return buttons
    }
    const hasValue = (record, number, label, trim) => {
        const headers = [
            { label: 'インシデント : ', id:40},
            { label: '目標達成率 : ', id:41},
            { label: 'コメント : ', id:39},            
        ]
        const header = headers.find(ob => ob.id == number)        
        if(record.time_card){
            const incident = record.time_card.custom_field_data_records.find(ob => ob.type_id == number)
            if(incident){
                const title = responsive.mobile && header ? header.label : ''
                return trim ? title + commentTextLength(incident[label]) : incident[label]
            }
            return  responsive.mobile ? '' : ''
        }
        return responsive.mobile ? '' : ''
    }
    const startOrEnd = (data, flag) => {
        const today = moment(data.day_full).isSame(moment(), 'day')  
        const stamp = data.time_card?.stamp_flag == flag
        return stamp && today
        
    }

    const emojis = ['🌈','☀️','☁️','☂️','⚡','☃️']
    
    

    const showOverTime = (monthAvg) => {
        if(monthAvg){
            if(monthAvg.month_should_work_time && monthAvg.month_work_time && monthAvg.month_annual_leave != null){
                return monthAvg.month_work_time + monthAvg.month_annual_leave > monthAvg.month_should_work_time
            }else{
                return monthAvg.month_work_time > monthAvg.month_should_work_time
            }
        }
        return false
    }
    const exportCSV = () => {
        const csvConfig = mkConfig({ useKeysAsHeaders: true, filename: `work_${props.selectedMonth + 1}月`});
        const data = []
        props.records.forEach(item => {            
            const allowanceData = hasAllowance(item?.time_card?.custom_field_data_records)
            const workhour = workTimeDisplay(item?.time_card)
            const overtime = overTimeDisplay(item)
            const incident = hasValue(item, 40, 'label')
            const manzoku = hasValue(item, 41, 'label')
            const comment = hasValue(item, 39, 'value_text')
            const row = {
                "日付" : dayFormatter(item.day_full),
                "氏名" : item.user_name,
                "予定" : item.shift?.shift_type?.abbreviation,
                "出勤" : item?.time_card?.start_time,
                "退勤" : item?.time_card?.end_time,
                "労働時間" : workhour == '' ? '' : workhour,
                "残業時間" : overtime == '' ? '' : overtime,
                "休憩時間" : item?.time_card?.break_time,
                "諸手当": allowanceData,
                "インシデント": incident == '' ? '' : incident,
                "目標達成率" : manzoku == '' ? '' : manzoku,
                "コンディション": item.weather !== null ? emojis[item.weather] : '',
                "コメント": comment
            }
            data.push(row)
        });
        const csv = generateCsv(csvConfig)(data);
        download(csvConfig)(csv);
    }        
    const overTimeFormat = (minutes) => {
        if (minutes === 0) {
            return responsive.mobile ? '残業時間合計： 0時間' : '0時間';
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
            
            return responsive.mobile ? '残業時間合計：' + formatted : formatted;
        }
    }
    const hasAllowance = (fields) => {         
        const allowances = fields && fields.length ? fields.filter(ob => ob.type_id == 37) : []
        const mobileTitle = allowances.length && responsive.mobile ? '諸手当 : ' : ''  
        const label = allowances.length ? allowances.map(ob => ob.label).join(' ') : responsive.mobile ? '' : ''
        return `${mobileTitle}${label}`      
    }
    const workTotalTimeFormat = (time) => {
        if(time){
            const hours = Math.floor(time / 60);
            const minutes = time % 60;
            
            return responsive.mobile ? `労働時間合計： ${hours}時間${minutes}分` : `${hours}時間${minutes}分`;
        }
        return ''
    }
    const overTimeDisplay = (data) => {
        const def = responsive.mobile ? '' : ''
        if(data.flex) return def
        const mobileTitle = responsive.mobile ? '残業時間 : ' : ''
        return data.time_card && data.time_card.over_time && data.time_card.over_time !== '0' ? mobileTitle + data.time_card.over_time + '分' : def
    }
    const breakTimeDisplay = (data) => {
        const def = responsive.mobile ? '' : ''
        const mobileTitle = responsive.mobile ? '休憩時間 : ' : ''        
        return data.time_card?.break_time ? `${mobileTitle}${data.time_card.break_time}分` : def
    }
    const workTimeDisplay = (timeCard) => {
        if(timeCard){
            const mobileTitle = responsive.mobile ? '労働時間 : ' : ''
            if(timeCard.work_time){
                const hours = Math.floor(timeCard.work_time / 60);
                const minutes = timeCard.work_time % 60;                
                return `${mobileTitle}${hours}時間${minutes}分`;
            }else if(timeCard.stamp_flag == 0){
                return `${mobileTitle}${countdown(timeCard.start_time)}`
            }            
        }
        return responsive.mobile ? '' : ''
    }
    const countdown = (time) => {
        const dateTime = props.currentDay + ' ' + time
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
    }


    const getDayClass = (date) => {
        const day = moment(date).day()
        return {
            'shift-saturday': day === 6,
            'shift-sunday': day === 0,
            'shift-everyholiday' : holiday(date),
            'today' : date === props.currentDay
        }
    }
    const getShiftClass = (shift) => {
        return shift?.apply_request?.status == 1 && shift?.shift_type && [0,5,14,15,16,3].includes(shift?.shift_type.id) ? 'shift-sunday' : ''
    }
    const commentTextLength = (value) => {
        return value && value.length > 10 ? value.slice(0, 6) + "..." : value;
    }
    const isTodayOrFuture = (date) => {
        return props.currentDay >= date;
    }
    const timeFormatter = (start, end, which) => {
        if(!start && !end) return responsive.mobile ? '' : ''
        if(start && !end && which == 'end') return '打刻なし'        
        const time = which == 'start' ? start : end      
        const mobileTitle = which == 'start' ? '出勤' : '退勤'
        const [hour, min] = time.split(':').slice(0, 2);
        return responsive.mobile ?  `${mobileTitle} : ${hour}:${min}` : `${hour}:${min}`        
    }
            
    const timeCardRemand = async(item) => {
        const answer = await confirm(item.day_full + "日報を差し戻しますか。")
        if(!answer) return
        
        const params = {
            user_id: item.user_id,
            record_day: item.day_full,
            overTimeRequest: item?.shift?.overtime_request,
        }
        try{
            await axios.post('/remand_time_card', params)
            emit('reload')
        }catch (e){
            notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
        }
    }
    
    const dailyApproval = async(item) => {
        const params = {
            user_id: item.user_id,
            record_day: item.day_full,
            overTimeRequest: item?.shift?.overtime_request,
        };
        try{
            await axios.post('/approve_time_card', params )
            emit('reload')
        }catch (e){
            notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
        }
    }
    const dailyCancel = async(item) => {
        const params = {
            user_id: item.user_id,
            record_day: item.day_full
        };
        try{
            await axios.post('/cancel_time_card', params )
            emit('reload')
        }catch (e){
            notify(e.response?.data.message || e?.message || 'エラーが発生しました。')  
        }
    }
    const earlyOrLateClass = (item, which) => {
        const shift = item?.shift
        const timecard = item?.time_card
        if(!shift || !timecard) return
        if(timecard[which]){
            const shiftStart = moment(`${shift.shift_day} ${shift[which]}`)
            const cardStart = moment(`${timecard.day} ${timecard[which]}`)
            return cardStart.isAfter(shiftStart) ?  (which == 'start_time' ? 'late-class' : 'over-class')
            : shiftStart.isAfter(cardStart) ? (which == 'start_time' ? 'over-class' : 'late-class') : ''
        }
        
        return ''
    }
    const overtimeRequest = (record) => {  
        overtimeRequestData.value = record
    }
    const ableTo = (item) => {
        const shift = item?.shift
        if(!shift) return false
        const futureOrToday = moment(shift.shift_day).isSameOrAfter(moment(), 'day')
        const possibleTypes = [1,6,7,8,9,10,11,12,13]
        return futureOrToday && possibleTypes.includes(shift.shift_type.id)
    }
    const ableToRequestOvertime = (item) => {
        const shift = item?.shift
        if(!shift) return false
        const futureOrToday = moment(shift.shift_day).isSameOrAfter(moment(), 'day')
        const possibleTypes = [1,6,7,8,9,10,11,12,13]
        return futureOrToday && possibleTypes.includes(shift.shift_type.id) && item.user_id == auth.activeUser.id && (!item.time_card || item.time_card?.status_flag == 10 || item.time_card?.status_flag == 0)
    }
    const overTimeRequestDisplay = (item) => {
        const overtime = item?.shift?.overtime_request
        if(!overtime) return 
        const status = statuses[overtime.status]
        return `${status}${overtime.minutes}分`
    }
    const appliedOvertime = (overtime) => {
        const hours = Math.floor(overtime.minutes / 60);
        const minutes = overtime.minutes % 60;
        const status = overtime.status == 1 ? '承認済み' : '申請中'
        return `${status} : ${hours}時間${minutes}分`
    } 
    const respondOvertime = async(data, status, action) => {
        if(status == 0){
            const answer = await confirm(`${data?.overtime_day}申請を差し戻しますか。差し戻した場合、申請社員に連絡してください。`)
            if(!answer) return
        }
        const params = {
            id: data.id,      
            approved_by: auth.activeUser.id,
            status: status
        }

        try{
            await axios.patch('/request_overtime', params).then(res => res.data)
            emit('reload')
            info(`${action}しました。`)
            // emit('close')
        } catch (e) { 
            notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
        } 
    }
</script>
<style lang="scss">
.absolute-div{
    position:absolute; 
    color: var(--primary-color); 
    display: flex; 
    justify-content: center; 
    align-items: center;
    width: 100%;
    height: 100%;
}
.workButton-wrapper{
    display: flex;
    justify-content: center;
    gap: 5px;
    align-items: center;
}
.v-table{
    height: 100%;
    background: var(--bg2) !important;
    table{
        font-size: 12px;
        background: var(--background-color);
        border-collapse: separate;
        border-spacing: 0;
        color: var(--primary-color);
        thead{
            position: sticky;
            top: 0;
            line-height: 40px;
            text-align: center;
            width: 100px;
            background-color: #606060;
            font-size: 12px;
            color: #fff;
            z-index: 1;
            vertical-align: middle;
            white-space: nowrap;
            height: 40px;
            th{
                border-right: 1px solid var(--calendarBorder);
                border-left: none;
                border-top: none;
                text-align: center;
                font-weight: 400;
                padding: 0 !important;
                height: 40px !important;

                .v-data-table-header__content{
                    justify-content: center;
                }
            }
            
        }
        tbody{
            .w-row{
                td{
                    border-bottom: 1px solid var(--calendarBorder);
                    border-right: 1px solid var(--calendarBorder);
                    vertical-align: middle;
                    width: 90px;
                    text-align: center;
                    height: 40px !important;
                    box-sizing: border-box;
                    padding: 0 !important;
                }
            }
            .w-row:hover{
                background: var(--bg3);
            }
            
            
        }
    }   
}



.v-table .v-table__wrapper > table > tbody {
    tr:not(:last-child)>td{
        border-bottom: none;
    }
}
.last-row > td{
    border-bottom: thin solid var(--calendarBorder) !important;
}

@media (max-width: 959px) {
    .condition-area{
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .workButton-wrapper{
        justify-content: flex-start;
    }
    .mt-10 {
        margin-bottom: 5px;
    }
    .last-row{
        margin-bottom: 25px !important;
    }
    .v-table{
        table{            
            font-size: 14px;        
            width: 100% !important;
            background: var(--bg2);
            .memberName{
                // font-weight: 600;
                white-space: nowrap;
            }
             thead {
                display: none !important; /* Hide the table header on mobile */
            }
            tfoot {
                display: none !important; /* Hide the table header on mobile */
            }
            tbody {
                display: flex !important;
                flex-direction: column !important;
                align-items: stretch !important;
                min-height: auto !important; 
            

                /* Styles for individual table rows (cards) */
                .w-row {
                    border: 1px solid var(--calendarBorder);
                    margin: 0 20px;
                    display: table-row !important;
                    background: var(--background-color);
                    height: auto !important;
                    box-sizing: border-box;
                    font-size: 13px;
                    padding-bottom: 10px;
                    .date-cell{
                        padding: 5px 20px !important;
                        text-align: center !important;
                    }
                }
                
                /* Styles for table cells within rows */
                .w-row td {
                    text-align: left !important;
                    border: none !important;
                    border-bottom: none !important;
                    display: block;
                    height: fit-content !important;
                    position: relative;
                    width: 100%;
                    line-height: 2;
                    padding: 0 20px !important;
                }
                .w-row:hover{
                    background: var(--background-color);
                }
                .command-cell{
                    display: flex !important;
                    width: 100% !important;
                    justify-content: center;
                }

            }
            
            
            .work-loader{
                height: 100%;
            }
        }
    }
}

</style>
<style scoped>


.tc{
    border-bottom: 1px solid var(--calendarBorder);
    vertical-align: middle;
    text-align: center;
    white-space: nowrap;
    box-sizing: border-box;
    min-height: 40px;
    height: 40px;
}
.tc:last-of-type{
    border-bottom: none;
}
.csv-button{
    padding: 3px 10px;
    background: var(--background-color);
    color: var(--primary-color);
    border-radius: 5px;
    margin: 0 10px;
    cursor: pointer;
}
</style>