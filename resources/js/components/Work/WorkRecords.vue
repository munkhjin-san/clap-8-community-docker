<template>
    <div class="records-wrapper" ref="wrapper" :style="{height: `calc(100% - ${headerHeight.value}px)`}">
        
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
            <template v-slot:item="{ item, index }">
                <tr :class="['w-row', {'last-row': item.last}]">
                    <td :class="[getDayClass(item.day_full), {'working' : item.time_card?.stamp_flag == 0}]">
                        <div class="td-first">{{ dayFormatter(item.day_show) }}</div>
                    </td>
                    <td style="white-space: nowrap;">{{  item.user_name }}</td>
                    <td v-if="hasHeader('予定')" :class="getShiftClass(item.shift?.shift_type)">{{ item.shift?.status_flag == 2 ? '申請中' : item.shift?.shift_type?.abbreviation }}</td>
                    <td :class="earlyOrLateClass(item, 'start_time')">
                        <div v-if="startOrEnd(item, null) && item.user_id == auth.activeUser.id && !item.attendance" class="w-hover-button mb-space">
                            <CommandButton @select="start(item)" :buttons="[{name: '始業'}]"/>
                        </div>
                        <div v-else>
                            {{  timeFormatter(item?.time_card?.start_time, item?.time_card?.end_time, 'start') }}
                        </div>
                    </td>
                    <td :class="earlyOrLateClass(item, 'end_time')">
                        <div v-if="startOrEnd(item, 0) && item.user_id == auth.activeUser.id" class="w-hover-button mb-space">
                            <CommandButton @select="end(item)" :buttons="[{name: '終業'}]"/>
                        </div>
                        <div v-else>
                            {{ timeFormatter(item?.time_card?.start_time, item?.time_card?.end_time, 'end') }}
                        </div>
                    </td>
                    <td>{{ workTimeDisplay(item?.time_card) }}</td>
                    <td>{{ overTimeDisplay(item) }}</td>
                    <td>{{ breakTimeDisplay(item) }}</td>
                    <td style="word-break: auto-phrase;">{{ hasAllowance(item?.time_card?.custom_field_data_records) }}</td>
                    <td>{{ hasValue(item, 40, 'label', true) }}</td>
                    <td>{{ hasValue(item, 41, 'label', true) }}</td>
                    <td v-html="hasCondition(item.weather)"></td> 
                    <td>
                        <div style="position: relative;">
                            <div @click.stop="hasValue(item, 39, 'value_text') !== '' ? menu.setMenu({name: 'commentBox', id: item.time_card?.id}) : false"> 
                                <div>{{ hasValue(item, 39, 'value_text', true) }}</div>
                            </div>
                            <div @click="menu.close()" class="comment-box" id="commentBox" v-if="menu.name == 'commentBox' && menu.id == item.time_card?.id">
                                <div style="word-break: break-all;" v-if="hasValue(item, 42, 'value_text') !== null">残業内容 : {{ hasValue(item, 42, 'value_text') }}</div>
                                <div>{{ hasValue(item, 39, 'value_text') }}</div>                              
                            </div>
                        </div>
                    </td>
                    <td v-if="hasHeader('経費')">
                        <div style="position: relative;word-break: auto-phrase;" class="w-hover-button">
                            <div v-if="responsive.mobile && item.time_card?.timecard_costs.length">経費 : </div>
                            <div @click.stop="hasWorkCost(item.time_card?.timecard_costs) !== '' ? menu.setMenu({name: 'costBox', id: item.time_card?.id}) : false">{{ hasWorkCost(item.time_card?.timecard_costs) }}</div>
                            <div @click="menu.close()" class="comment-box" id="costBox" v-if="menu.name == 'costBox' && menu.id == item.time_card?.id">
                                <div v-for="cost in item.time_card?.timecard_costs" :key="cost.id">
                                    <div>{{ `${hasWorkCostLabel(cost) ? hasWorkCostLabel(cost) : ''}:${cost.content ? cost.content : ''} ${cost.expenses ? cost.expenses + '円' : ''}` }}</div>
                                    <img @click="previewImage(cost.file)" style="height:120px;cursor: pointer;" v-if="cost?.file" :src="`/cdn/timecard_files/${cost?.file?.id}_${cost?.file?.user_id}_${cost?.file?.path}.${cost?.file?.extension}`"/>
                                </div>
                            </div>
                        </div>
                        
                    </td>
                    <td v-if="hasHeader('インセンティブ')">
                        <div style="position: relative;word-break: auto-phrase;" class="w-hover-button">
                            <div v-if="responsive.mobile && item.time_card?.timecard_incentives.length">インセンティブ : </div>
                            <div @click.stop="incentiveCount(item.time_card?.timecard_incentives, '件') !== '' ? menu.setMenu({name: 'incentiveBox', id: item.time_card?.id}) : false">{{ incentiveCount(item.time_card?.timecard_incentives, '件') }}</div>
                            <div @click="menu.close()" class="comment-box" id="incentiveBox" v-if="menu.name == 'incentiveBox' && menu.id == item.time_card?.id">
                                <div v-for="incentive in item.time_card?.timecard_incentives" :key="incentive.id">
                                    <div>{{ `${incentive.count ? incentive.count + '件' : ''}` }}</div>
                                    <img @click="previewImage(incentive.file)" style="height:120px;cursor: pointer;" v-if="incentive?.file" :src="`/cdn/timecard_files/${incentive?.file?.id}_${incentive?.file?.user_id}_${incentive?.file?.path}.${incentive?.file?.extension}`"/>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div style="position: relative;">
                            <div>
                                <div @click.stop="menu.setMenu({name: 'approveBox', id: item.shift?.overtime_request?.id})" v-if="item?.shift?.overtime_request">残業 : {{ overTimeRequestDisplay(item) }}</div>
                                <div @click="menu.close()" class="comment-box" id="approveBox" style="padding: 10px; display:flex; flex-direction: column; gap: 10px;" v-if="menu.name == 'approveBox' && menu.id == item.shift?.overtime_request?.id">
                                    {{ overTimeRequestDisplay(item) }} <br>
                                    {{ item.shift?.overtime_request?.content }}
                                </div>
                                <div v-if="isTodayOrFuture(item.day_full) || auth.activeUser.id == 610"> 
                                    <div style="display:inline-block" v-if="item?.time_card?.status_flag">
                                        <div>日報 : {{ getStatusText(item?.time_card?.status_flag) }}</div>
                                    </div>
                                </div> 
                            </div>
                            
                        </div>
                        
                    </td>
                    <td>
                        <div class="w-hover-button center-mobile">
                            
                            <CommandButton v-if="ableTo(item)" @select="callModal(item)" :buttons="[{name: '手続き'}]"/>
                            
                        </div>
                    </td>
                    
                </tr>
                
            </template>
            <template v-slot:body.append>
                <tr id="bottomTotal" class="w-row" style="background-color: #606060;color:white" v-for="(user, index) in usersData">
                    <td style="border-bottom: thin solid transparent;">
                        <div v-if="index == 0">
                            <span>集計</span>
                            <div class="cursor-pointer" @click="exportCSV" v-if="auth.activeUser.id == 610 || auth.user.position_id == 6 || auth.activeUser.id == 608">CSV</div>
                        </div>
                    </td>
                    <td>{{ user.name }}</td>
                    <td v-if="!responsive.mobile && hasHeader('予定')"></td>
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
                    <td v-if="!responsive.mobile && hasHeader('経費')">{{ monthAverage?.[user.id]?.mont_total_costs ? `${monthAverage?.[user.id]?.mont_total_costs}円` : ''}}</td>
                    <td v-if="!responsive.mobile && hasHeader('インセンティブ')">{{ monthAverage?.[user.id]?.mont_total_incentive ? `${monthAverage?.[user.id]?.mont_total_incentive}件` : ''}}</td>
                    <td v-if="!responsive.mobile"></td>
                    <td v-if="!responsive.mobile"></td>
                </tr>
            </template>            
                
            
        </v-data-table-virtual>
        <RecordButtons
            v-if="tempItem" 
            :item="tempItem"
            :currentDay="currentDay"
            :statuses="statuses"
            @closeModal="closeModal"
            @dailyButtons="dailyButtons"
            @reload="emit('reload')"
        />
    </div>
</template>
<script setup>
import { VDataTableVirtual } from 'vuetify/components/VDataTable'
import moment from 'moment'
import { inject, ref, computed } from 'vue';
import { useMenuStore } from "@/store/menu";
import { useResponsive } from '@/store/responsive';
import { useAuthUserStore } from '@/store/auth';
import holiday_jp from '@holiday-jp/holiday_jp'
import { mkConfig, generateCsv, download } from "export-to-csv";
import CommandButton from '../Global/CommandButton.vue'
import RecordButtons from './RecordButtons.vue'
import { useFilePreview } from '../../store/filePreview';
    const statuses = ['差戻中', '申請中', '承認済']
    const { start, end } = inject('stamps')
    const menu = useMenuStore()
    const responsive = useResponsive()
    const auth = useAuthUserStore()
    const filePreview = useFilePreview()
    const props = defineProps([
        'currentDay', 
        'monthAverage',
        'usersData',
        'selectedMonth',
        'records',
        'loading',
        'selectedYear',
        'headerHeight',
        'workGroups'
    ]) 
    const { confirm, notify, info } = inject('dialog')
    const emit = defineEmits(['reload', 'timeStampDelete'])

    const costOptions = inject('costOptions')
    const tempItem = ref(null)
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
        if(index != null){
            return `<div class="condition-area"><div>${mobileTitle}</div><img class="condition-img" src="images/icon_${index}.svg" width="17" height="17"/></div>`
        }
        return responsive.mobile ? '' : ''
        
    }
    const dailyButtons = (value, item) => {
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
    const getStatusText = (statusFlag) => {
        switch (statusFlag) {
            case 0:
                return '作成中';
            case 10:
                return '差戻中';
            case 1:
                return '申請中';
            case 2:
                return '承認済';
            default:
                return '';
        }
    }
    const includeRegistered = computed(() => {
        return !!props.usersData.find(ob => ob.position_id === 15)
    })
    const hasHeader = (title) => {
        return headers.value.findIndex(element => element.title == title) !== -1
    }
    const headers = computed(() => {
        let headersArray = [
            { title: '日付'},
            { title: 'メンバー'},
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
            { title: 'ステータス'},
            { title: '手続き'},
        ];
        if(auth.user.position_id !== 15){
            headersArray.splice(2, 0, {title: '予定'})
        }
        if(includeRegistered.value){
            const index = headersArray.findIndex(element => element.title == 'ステータス')
            headersArray.splice(index, 0, {title: '経費'})
            headersArray.splice(index + 1, 0, {title: 'インセンティブ'})
        }
        

        return headersArray;
    })
    const closeModal = () => {
        tempItem.value = null
    }
    const callModal = (item) => {
        tempItem.value = item
    }
    const previewImage = (file) => {
        if(file?.id){
            let target_data = file
            const file_path = `/cdn/timecard_files/${file.id}_${file.user_id}_${file.path}.${file.extension}`
            target_data['file_path'] = file_path
            const data = {
                active: true,
                files: [target_data],
                source: 'work',
                index: 0,
                message: null,
            }
            filePreview.setFilePreview(data)
        }
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
    const workCostForCSV = (timecard_costs) => {
        const costs = timecard_costs && timecard_costs.length ?  timecard_costs.reduce((acc, cost) => {
            if (cost.type === 1) {
                acc.sum1 += cost.expenses;
            } else {
                acc.sum2 += cost.expenses;
            }
            return acc
        }, { sum1: 0, sum2: 0}) : []
        let costsSum = [];

        if (costs.sum1 > 0) {
            costsSum.push(`交通費: ${costs.sum1}円`)
        }

        if (costs.sum2 > 0) {
            costsSum.push(`通信費: ${costs.sum2}円`)
        }

        return costsSum.join(' ')
    }
    const exportCSV = () => {
        const csvConfig = mkConfig({ useKeysAsHeaders: true, filename: `work_${props.selectedMonth + 1}月`});
        const data = []
        props.records.forEach(item => {            
            const allowanceData = hasAllowance(item?.time_card?.custom_field_data_records)
            const workhour = workTimeDisplay(item?.time_card)
            const overtime = overTimeDisplay(item)
            const workcost = workCostForCSV(item?.time_card?.timecard_costs)
            const workcount = incentiveCount(item?.time_card?.timecard_incentives)
            const incident = hasValue(item, 40, 'label')
            const manzoku = hasValue(item, 41, 'label')
            const overtime_text = hasValue(item, 42, 'value_text')
            const comment = hasValue(item, 39, 'value_text').replace(/\n/g, "")
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
                "コメント": overtime_text ? `残業内容 : ${overtime_text}\n${comment}` : comment,
            }
            if (includeRegistered.value) {
                row["経費"] = workcost;
                row["インセンティブ"] = workcount;
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
    const hasWorkCost = (costs) => {
        return costs && costs.length ?
            costs.map(ob => {
                const costOption = costOptions.find(opt => opt.value === ob.type);
                return costOption ? costOption.label : '';
            }).join(' ') : '';
    }
    const incentiveCount = (costs, unit) => {
        const sum = costs && costs.length ? costs.reduce((accumulator, element) => accumulator + element.count, 0) : 0
        const suffix = unit ? unit : ''
        return sum !== 0 ? sum + suffix : ''
    }
    const hasWorkCostLabel = (cost) => {
        return costOptions.find(opt => opt.value === cost.type)?.label;
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
        return shift && [0,5,14,15,16,3].includes(shift?.id) ? 'shift-sunday' : ''
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
            info('差戻しました。')
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
            info('承認しました。')
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
            info('承認取消しました。')
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
    const ableTo = (item) => {
        const { shift } = item || {}
        if(!shift && item.position_id !== 15) return false

        const possibleTypes = [1,6,7,8,9,10,11,12,13]
        const { time_card } = item || {}
        const inThePast = props.currentDay >= item.day_full
        const timeCardChange = time_card === null || time_card.status_flag === 10 || time_card.status_flag === 0
        const isAttendancePending = item.attendance
        if(auth.isRegistered){
            return inThePast && timeCardChange && !isAttendancePending
        }
        
        const overTimeRequest = possibleTypes.includes(shift?.shift_type.id) && !shift?.overtime_request
        
        const hasAuthority = auth.activeUser.work_authority > item.work_authority
        const overTimeOrTimeCard = shift?.overtime_request || time_card
        const authorityOver = authorityCheck(item) && (overTimeOrTimeCard || auth.activeUser.id == 610)
        return (timeCardChange && (overTimeRequest || inThePast) && 
            auth.activeUser.id === item.user_id || authorityOver && 
            (overTimeOrTimeCard || inThePast)) && !isAttendancePending
        
    }
    const authorityCheck = (item) => {
        const findUser = filterGroups.value.find(ob => ob.id === item.user_id)
        return auth.activeUser.work_authority > findUser?.work_authority
    }
    const filterGroups = computed(() => {
        let groups
        if(auth.activeUser.id === 610 || auth.activeUser.id === 608){
            groups = props.workGroups
        } else {
            let filter = props.workGroups.filter(val => val.members.some(ob => ob.id === auth.id && ob.pivot.authority === 1))
            groups = filter
            .flatMap(workGroup => workGroup.members)
            .reduce((acc, member) => {
                if (!acc.some(m => m.id === member.id)) {
                acc.push(member);
                }
                return acc;
            }, [])
        }     
        
        const uniqueMemberObjects = groups.sort((a, b) => {
                if (a.id === auth.id) return -1;
                if (b.id === auth.id) return 1;
                return a.id - b.id;
            });
        return uniqueMemberObjects   
    })

    const overTimeRequestDisplay = (item) => {
        const overtime = item?.shift?.overtime_request
        if(!overtime) return 
        const status = statuses[overtime.status]
        return `${status}${overtime.minutes}分`
    }   
    
</script>
<style lang="scss">
.w-hover-button{
    display: flex;
    justify-content: center;
}
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
                    width: 100px;
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
    .mb-space{
        margin-top: 10px;
    }
    .w-hover-button{
        justify-content: flex-start;
    }
    .center-mobile{
        justify-content: center;
    }
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
    .td-first{
        padding: 15px 0px 0px 0px;
        text-align: center;
        margin-bottom: 5px;
    }
    .today .td-first{
        padding: 10px 0px;
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
                    padding-bottom: 20px;
                    position: relative;
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