<template>
    <tr :class="['w-row', {'last-row': item.last}]">
        <td :class="[getDayClass, {'working' : item.time_card?.stamp_flag == 0}]">
            <div class="td-first">{{ dayFormatter }}</div>
        </td>
        <td style="white-space: nowrap;">{{  item.user_name }}</td>
        <td v-if="hasHeader('予定')" :class="getShiftClass">{{ item.shift?.status_flag == 2 ? '申請中' : item.shift?.shift_type?.abbreviation }}</td>
        <td :class="startEarly">
            <div v-if="item.ability.start_stamp" class="w-hover-button mb-space">
                <CommandButton @select="start(item)" :buttons="[{name: '始業'}]"/>
            </div>
            <div v-else>{{ startTimeFormatted }}</div>
        </td>
        <td :class="goLately">
            <div v-if="item.ability.end_stamp" class="w-hover-button mb-space">
                <CommandButton @select="end(item)" :buttons="[{name: '終業'}]"/>
            </div>
            <div v-else>{{ endTimeFormatted }}</div>
        </td>
        <td>{{ workTimeFormatted }}</td>
        <td>{{ overTimeFormatted }}</td>
        <td>{{ breakTimeFormatted }}</td>
        <td style="word-break: auto-phrase;">{{ hasAllowance }}</td>
        <td>{{ incidentFormatted }}</td>
        <td>{{ satisfyFormatted }}</td>
        <td v-html="hasCondition"></td> 
        <td>
            <div style="position: relative;">
                <div @click.stop="commentFormatted !== '' ? menu.setMenu({name: 'commentBox', id: item.time_card?.id}) : false"> 
                    <div>{{ commentTrim }}</div>
                </div>
                <div @click="menu.close()" class="comment-box" id="commentBox" v-if="menu.name == 'commentBox' && menu.id == item.time_card?.id">
                    <div style="word-break: break-all;" v-if="overTimeReasonFormatted">{{ overTimeReasonFormatted }}</div>
                    <div>{{ commentFormatted }}</div>                              
                </div>
            </div>  
        </td>
        <td v-if="hasHeader('経費')">
            <div style="position: relative;word-break: auto-phrase;" class="w-hover-button">
                <div v-if="responsive.mobile && item.time_card?.timecard_costs.length">経費 : </div>
                <div @click.stop="hasWorkCost !== '' ? menu.setMenu({name: 'costBox', id: item.time_card?.id}) : false">{{ hasWorkCost }}</div>
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
                <div>{{ incentiveCount}}</div>
                <!-- <div @click="menu.close()" class="comment-box" id="incentiveBox" v-if="menu.name == 'incentiveBox' && menu.id == item.time_card?.id">
                    <div v-for="incentive in item.time_card?.timecard_incentives" :key="incentive.id">
                        <div>{{ `${incentive.count ? incentive.count + '件' : ''}` }}</div>
                        <img @click="previewImage(incentive.file)" style="height:120px;cursor: pointer;" v-if="incentive?.file" :src="`/cdn/timecard_files/${incentive?.file?.id}_${incentive?.file?.user_id}_${incentive?.file?.path}.${incentive?.file?.extension}`"/>
                    </div>
                </div> -->
            </div>
        </td>
        <td>
            <div style="position: relative;">
                <div>
                    <div @click.stop="menu.setMenu({name: 'approveBox', id: item.shift?.overtime_request?.id})" v-if="item?.shift?.overtime_request">残業 : {{ overTimeRequestDisplay }}</div>
                    <div @click="menu.close()" class="comment-box" id="approveBox" style="padding: 10px; display:flex; flex-direction: column; gap: 10px;" v-if="menu.name == 'approveBox' && menu.id == item.shift?.overtime_request?.id">
                        {{ overTimeRequestDisplay }} <br>
                        {{ item.shift?.overtime_request?.content }}
                    </div>
                    <div> 
                        <div style="display:inline-block" v-if="item?.time_card?.status_flag">
                            <div>日報 : <span :class="{'shift-sunday' : item?.time_card?.status_flag == 1}">{{ getStatusText }}</span></div>
                        </div>
                    </div> 
                </div>
                
            </div>
            
        </td>
        <td>
            <div class="w-hover-button center-mobile">
                
                <CommandButton v-if="hasAction" @select="emit('procedureStart', item)" :buttons="[{name: '手続き'}]"/>
                
            </div>
        </td>
    </tr>
</template>
<script setup>
import moment from 'moment';
import { computed, inject, onMounted } from 'vue';
import { useResponsive } from '@/store/responsive';
import { useMenuStore } from "@/store/menu";
import CommandButton from '../Global/CommandButton.vue';
import { useFilePreview } from '../../store/filePreview';
const menu = useMenuStore()
const responsive = useResponsive()
const filePreview = useFilePreview()
const costOptions = inject('costOptions')
const {start, end } = inject('stamps')
const props = defineProps({
    item: {type: Object, default: null},
    hasHeader: {type: Function},
    holidays: {type: Array, default: []}
})
const emit = defineEmits(['callModal', 'procedureStart'])
const getDayClass = computed(() => {
    const date = props.item.day_full
    const day = moment(date).day()
    return {
        'shift-saturday': day === 6,
        'shift-sunday': day === 0,
        'shift-everyholiday' : props.holidays.find(h => moment(h.date).isSame(props.item.day_full, 'day')),
        'today' : date === moment().format('YYYY-MM-DD')
    }
})

const dayFormatter = computed(() => {
    const value = props.item.day_show
    if(value){
        const date =  moment(value).format('M / D (dd)')
        return date
    }
})

const getShiftClass = computed(() => {
    const shift = props.item.shift?.shift_type
    return shift && [0,5,14,15,16,3].includes(shift?.id) ? 'shift-sunday' : ''
})

const startEarly = computed(() => {
    const shift = props.item?.shift
    const timecard = props.item?.time_card
    if(!shift || !timecard) return
    if(timecard.start_time){
        const shiftStart = moment(`${shift.shift_day} ${shift.start_time}`)
        const cardStart = moment(`${timecard.day} ${timecard.start_time}`)
        return cardStart.isAfter(shiftStart) ?  'late-class'
        : shiftStart.isAfter(cardStart) ?  'over-class' : ''
    }      
    return ''
})

const goLately = computed(() => {
    const shift = props.item?.shift
    const timecard = props.item?.time_card
    if(!shift || !timecard) return
    if(timecard.end_time){
        const shiftEnd = moment(`${shift.shift_day} ${shift.end_time}`)
        const cardEnd = moment(`${timecard.day} ${timecard.end_time}`)
        return cardEnd.isAfter(shiftEnd) ?  'over-class'
        : shiftEnd.isAfter(cardEnd) ?  'late-class' : ''
    }      
    return ''
})
const startTimeFormatted = computed(() => {
    const start = props.item?.time_card?.start_time
    const end = props.item?.time_card?.end_time
    if(!start && !end) return '' 
    const [hour, min] = start.split(':').slice(0, 2);
    return responsive.mobile ?  `出勤 : ${hour}:${min}` : `${hour}:${min}`        
})

const endTimeFormatted = computed(() => {
    const start = props.item?.time_card?.start_time
    const end = props.item?.time_card?.end_time
    if(!start && !end) return ''
    if(start && !end ) return '打刻なし'         
    const [hour, min] = end.split(':').slice(0, 2);
    return responsive.mobile ?  `退勤 : ${hour}:${min}` : `${hour}:${min}`        
})

const workTimeFormatted = computed(() => {
    const timeCard = props.item?.time_card
    if(timeCard){
        const mobileTitle = responsive.mobile ? '労働時間 : ' : ''
        if(timeCard.work_time){
            const hours = Math.floor(timeCard.work_time / 60);
            const minutes = timeCard.work_time % 60;                
            return `${mobileTitle}${hours}時間${minutes}分`;
        }else if(timeCard.stamp_flag == 0){
            return `${mobileTitle}${countdown.value}`
        }            
    }
    return ''
})
const countdown = computed(() => {
    const currentTime = moment();
    const givenTime = props.item?.time_card.start_time
    const todayWithGivenTime = moment().format('YYYY-MM-DD') + ' ' + givenTime;
    const givenTimeInstance = moment(todayWithGivenTime, 'YYYY-MM-DD HH:mm');
    const difference = moment.duration(currentTime.diff(givenTimeInstance));
    return difference < 0 ? '0時間0分' : `${difference.hours()}時間${difference.minutes()}分`;
})
const overTimeFormatted = computed(() => {
    const data = props.item
    if(data.flex) return ''
    const mobileTitle = responsive.mobile ? '残業時間 : ' : ''
    return data.time_card && data.time_card.over_time && data.time_card.over_time !== '0' ? mobileTitle + data.time_card.over_time + '分' : ''
})

const breakTimeFormatted = computed(() => {
    const data = props.item
    const mobileTitle = responsive.mobile ? '休憩時間 : ' : ''        
    return data.time_card?.break_time ? `${mobileTitle}${data.time_card.break_time}分` : ''
})

const hasAllowance = computed(() => {      
    const mobileTitle = props.item?.allowances && responsive.mobile ? '諸手当 : ' : ''  
    const label = props.item?.allowances
    return `${mobileTitle}${label}`      
})

const incidentFormatted = computed(() => {
    const title = props.item?.incident && responsive.mobile ? 'インシデント : ' : ''
    return title + props.item?.incident
})

const satisfyFormatted = computed(() => {
    
    const title = props.item?.satisfy && responsive.mobile ? '目標達成率 : ' : ''
    return  title + props.item?.satisfy
})

const commentFormatted = computed(() => {
    
    const title = props.item?.comment && responsive.mobile ? 'コメント : ' : ''
    return title + props.item?.comment
})
const commentTrim = computed(() => {
    return commentFormatted.value && commentFormatted.value.length > 10 ? commentFormatted.value.slice(0, 10) + "..." : commentFormatted.value
})
const overTimeReasonFormatted = computed(() => {
    
    const content = props.item?.overtime_reason ? '残業内容 : ' +  props.item.overtime_reason : ''
    return content
})

const hasCondition = computed(() => {
    const index = props.item.weather
    const mobileTitle = responsive.mobile ? 'コンディション : ' : ''
    if(index != null){
        return `<div class="condition-area"><div>${mobileTitle}</div><img class="condition-img" src="images/icon_${index}.svg" width="17" height="17"/></div>`
    }
    return ''
    
})

const hasWorkCost = computed(() => {
    const costs = props.item.time_card?.timecard_costs
    return costs && costs.length ?
    costs.map(ob => {
        const costOption = costOptions.find(opt => opt.value === ob.type);
        const expense = ob.expenses !== null ? ob.expenses : 0
        return costOption ? `${costOption.label} : ${expense}円`: '';
    }).join(' ') : '';
})

const hasWorkCostLabel = (cost) => {
    return costOptions.find(opt => opt.value === cost.type)?.label;
}

const hasAction = computed(() => {
    const authorityCheck = Object.values(props.item.ability).some(val => val == true)
    return authorityCheck
})


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
const incentiveCount = computed(() => {
    const costs = props.item.time_card?.timecard_incentives
    const sum = costs && costs.length ? costs.reduce((accumulator, element) => accumulator + element.count, 0) : 0
    return sum !== 0 ? `${sum}件` : ''
})

const overTimeRequestDisplay = computed(() => {
    const overtime = props.item?.shift?.overtime_request
    if(!overtime) return 
    const statuses = ['差戻中', '申請中', '承認済']
    const status = statuses[overtime.status]
    return `${status}${overtime.minutes}分`
})  


const getStatusText = computed(() => {
    const statusFlag = props.item?.time_card?.status_flag
    const statuses = {
        0: '作成中',
        1: '申請中',
        2: '承認済',
        10: '差戻中'
    }
    return statusFlag ? statuses[statusFlag] : ''
})


</script>