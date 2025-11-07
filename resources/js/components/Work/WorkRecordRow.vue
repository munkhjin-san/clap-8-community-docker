<template>
    <tr :class="['w-row', {'last-row': item.last}]">
        <td :class="[getDayClass, {'working' : item.time_card?.stamp_flag == 0}]">
            <div class="td-first">{{ dayFormatter }}</div>
        </td>
        <td>{{  item.user_name }}</td>
        <td v-if="hasHeader('予定')" :class="getShiftClass">{{ item.shift?.status_flag == 2 ? '申請中' : item.shift?.shift_type?.abbreviation }}</td>
        
        <td :class="startEarly">
            <div v-if="item.ability.start_stamp" class="w-hover-button mb-space">
                <CommandButton :buttons="[{title: '始業', action:() => start(item)}]"/>
            </div>
            <div v-else>{{ startTimeFormatted }}</div>
        </td>
        <td :class="goLately">
            <div v-if="item.ability.end_stamp" class="w-hover-button mb-space">
                <CommandButton :buttons="[{title: '終業', action: () => end(item)}]"/>
            </div>
            <div v-else>{{ endTimeFormatted }}</div>
        </td>
        <td>{{ workTimeFormatted }}</td>
        <td v-if="hasHeader('研修時間')">{{ trainTimeFormatted }}</td>
        <td>{{ overTimeFormatted }}</td>
        <td>
            <div style="white-space: pre-wrap;" v-if="item.time_card?.stamp_flag == 1">{{ breakTimeFormatted }}</div>
            <div v-if="item.ability.break_stamp" class="w-hover-button mb-space">
                <CommandButton :buttons="[{title: item.time_card?.stamp_flag == 0 ? '休憩' : '再開', action:() => takeBreak(item)}]"/>
            </div>
            
        </td>
        <!-- <td>{{ item.time_card?.work_group?.name }}</td> -->
        <td>
            <div v-if="item.time_card?.department" class="text-wrap">
                {{ responsive.mobile ? '部門 : ' : '' }}{{ item.time_card.department?.name }}
            </div>
            <div v-else-if="item.shift?.department" class="text-wrap">
                {{ responsive.mobile ? '部門 : ' : '' }}{{ item.shift.department?.name }}
            </div>
        </td>
        <td style="word-break: auto-phrase;white-space: break-spaces;">{{ hasAllowance }}</td>
        <td>{{ incidentFormatted }}</td>
        <td>{{ satisfyFormatted }}</td>
        <td>
            <div v-if="item.weather !== null" class="condition-area">
                <div>{{ responsive.mobile ? 'コンディション : ' : '' }}</div>
                <WeatherIcon :which="item.weather" :size="17"/>
            </div>
        </td> 
        <td>
            <div style="position: relative;">
                <div class="text-wrap comment-wrap" @click.stop="boxPosition('commentBox')"> 
                    {{ commentFormatted }}
                </div>
                <div @click="menu.close()" ref="commentBox" class="comment-box" id="commentBox" :style="{top: `${topOffset}px`}" v-if="menu.name == 'commentBox' && menu.id == item.time_card?.id">
                    <div style="word-break: break-word;" v-if="overTimeReasonFormatted">{{ overTimeReasonFormatted }}</div>
                    <div style="word-break: break-word;">{{ commentFormatted }}</div>                              
                </div>
            </div>  
        </td>
        <td>
            <div style="position: relative;word-break: auto-phrase;" class="w-hover-button">
                <div @click.stop="boxPosition('costBox')" class="text-wrap comment-wrap">{{ hasWorkCost }}</div>
                <div @click="menu.close()" ref="costBox" class="comment-box" id="costBox" :style="{top: `${topOffset}px`}" v-if="menu.name == 'costBox' && menu.id == item.time_card?.id">
                    <div v-for="cost in item.time_card?.timecard_costs" :key="cost.id">
                        <div style="word-break: break-word;" v-html="formatCostString(cost)"></div>
                        <div v-if="cost.file_path?.split('.').pop() == 'webp'">
                            <img @click="workFilePreview(cost.file_path, 'image', '/cdn/timecard_files')" style="height:120px;cursor: pointer;" v-if="cost?.file_path" :src="`/cdn/timecard_files/${cost?.file_path}`"/>
                        </div>
                        <div v-else-if="cost.file_path?.split('.').pop() == 'pdf'">
                            <div class="cursor-pointer" style="position:relative;" @click="workFilePreview(cost.file_path, 'application', '/cdn/timecard_files')">
                                <FileIcon ext="pdf"/>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
            
        </td>
        <td>
            <div style="position: relative;word-break: auto-phrase;" class="w-hover-button">
                <div @click.stop="boxPosition('vehicleBox')" class="text-wrap">{{ hasVehicle }}</div>
                <div @click="menu.close()" ref="vehicleBox" class="comment-box" id="vehicleBox" :style="{top: `${topOffset}px`}" v-if="menu.name == 'vehicleBox' && menu.id == item.time_card?.id">
                    <div style="word-break: break-word;">{{ vehicleDetail }}</div>                              
                </div>
            </div>
        </td>
        <td>
            <div style="position: relative;word-break: auto-phrase;" class="w-hover-button">
                <div @click.stop="boxPosition('mileageBox')" class="text-wrap comment-wrap">{{ mileageFormatted }}</div>
                <div @click="menu.close()" ref="mileageBox" class="comment-box" id="mileageBox" :style="{top: `${topOffset}px`}" v-if="menu.name == 'mileageBox' && menu.id == item.time_card?.id">
                    <div style="word-break: break-word;">{{ mileageDetail }}</div> 
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
                
                <CommandButton v-if="hasAction" :buttons="[{title: '報告', action:() => emit('procedureStart', item)}]"/>
                
            </div>
        </td>
    </tr>
</template>
<script setup>
import { computed, inject, ref, useTemplateRef } from 'vue';
import { useResponsive } from '@/store/responsive';
import { useMenuStore } from "@/store/menu";
import CommandButton from '../Global/CommandButton.vue';
import { vehicleAsOptions, workFilePreview } from '../../utils/workApi';
import FileIcon from '../Board/Mixed/FileIcon.vue';
import WeatherIcon from '../Global/WeatherIcon.vue';
import { DateTime } from 'luxon';
import { customParser } from '@/utils/tools';
const menu = useMenuStore()
const responsive = useResponsive()
const costOptions = [{label: '交通費', value: 1},
                    {label:'通信費', value: 2},
                    {label:'宿泊費', value: 3},
                    {label: '旅費交通費', value: 4},
                    {label:'消耗品費', value: 5},
                    {label:'交際費', value: 6},
                    {label:'支払手数料', value: 7},
                    {label:'福利厚生費', value: 8}]
const {start, end, takeBreak } = inject('stamps')
const props = defineProps({
    item: {type: Object, default: null},
    hasHeader: {type: Function},
    holidays: {type: Array, default: []},
    wrapper: {type: HTMLDivElement}
})
const emit = defineEmits(['callModal', 'procedureStart'])

const commentBox = ref(null)
const costBox = ref(null)
const vehicleBox = useTemplateRef('vehicleBox')
const topOffset = ref(0)
const getDayClass = computed(() => {
    const date = props.item.day_full
    const dateInstance = DateTime.fromISO(date)
    return {
        'shift-saturday': dateInstance.weekday === 6,
        'shift-sunday': dateInstance.weekday === 7,
        'shift-everyholiday' : props.holidays.find(h => DateTime.fromJSDate(h.date).hasSame(dateInstance, 'day')),
        'today' : date === DateTime.now().toISODate(),
    }
})

const dayFormatter = computed(() => {
    const value = props.item.day_show
    if(value){
        const date =  DateTime.fromISO(value).toFormat('M / d (ccc)')
        return date
    }
})
const boxPosition = (name) => {
    if(!commentFormatted.value && name == 'commentBox') return
    topOffset.value = 0
    menu.setMenu({name: name, id: props.item.time_card?.id})
    setTimeout(() => {
        const box = name == 'costBox' ? costBox.value : name == 'commentBox' ? commentBox.value : vehicleBox.value
        if(box){
            const rects = box.getBoundingClientRect()
            topOffset.value = rects.bottom > window.innerHeight ? Math.ceil(window.innerHeight - rects.bottom - 10) : 0
        }
    })
}
const getShiftClass = computed(() => {
    const shift = props.item.shift?.shift_type
    return shift && [0,3,5,14,15,16,17,18].includes(shift?.id) ? 'shift-sunday' : ''
})

const startEarly = computed(() => {
    const shift = props.item?.shift
    const timecard = props.item?.time_card
    if(!shift || !timecard) return
    if(timecard.start_time){
        const shiftStart = customParser(`${shift.shift_day} ${shift.start_time}`)
        const cardStart = customParser(`${timecard.day} ${timecard.start_time}`)
        return cardStart > shiftStart ?  'late-class'
        : shiftStart > cardStart ?  'over-class' : ''
    }      
    return ''
})

const goLately = computed(() => {
    const shift = props.item?.shift
    const timecard = props.item?.time_card
    if(!shift || !timecard) return
    if(timecard.end_time){
        const shiftEnd = customParser(`${shift.shift_day} ${shift.end_time}`)
        const cardEnd = customParser(`${timecard.day} ${timecard.end_time}`)
        return cardEnd > shiftEnd ?  'over-class'
        : shiftEnd > cardEnd ?  'late-class' : ''
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
    if(timeCard?.stamp_flag == 2) return
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
const durationJa = (startStr, endStr) => {
  const [sh, sm, ss = 0] = startStr.split(":").map(Number);
  const [eh, em, es = 0] = endStr.split(":").map(Number);

  const start = new Date(0, 0, 0, sh, sm, ss);
  const end = new Date(0, 0, 0, eh, em, es);

  if (end < start) end.setDate(end.getDate() + 1);

  const diffMs = end.getTime() - start.getTime();
  const totalMinutes = Math.round(diffMs / 60000);
  const hours = Math.floor(totalMinutes / 60);
  const minutes = totalMinutes % 60;

  return `${hours}時間${minutes}分`;
}
const trainTimeFormatted = computed(() => {
    const timeCard = props.item?.time_card
    let label = ""
    if (timeCard){
        const mobileTitle = responsive.mobile ? '研修時間 : ' : ''
        if (timeCard.training_start_time && timeCard.training_end_time) {
            label = durationJa(timeCard.training_start_time, timeCard.training_end_time);
            return mobileTitle + label
        }
    }
})
const countdown = computed(() => {
    const currentTime = DateTime.now();
    const givenTime = props.item?.time_card.start_time;
    const breakMinute = props.item?.total_break_time || 0;
    
    if (!givenTime) return '0時間0分';
    
    const todayWithGivenTime = `${currentTime.toFormat('yyyy-MM-dd')} ${givenTime}`;
    const givenTimeInstance = DateTime.fromFormat(todayWithGivenTime, 'yyyy-MM-dd HH:mm:ss');
    
    // Calculate difference and subtract break time
    let difference = currentTime.diff(givenTimeInstance, ['hours', 'minutes']);
    difference = difference.minus({ minutes: breakMinute });
    
    // Ensure we don't return negative time
    if (difference.hours < 0 || (difference.hours === 0 && difference.minutes < 0)) {
        return '0時間0分';
    }
    
    return `${Math.floor(difference.hours)}時間${Math.floor(difference.minutes)}分`;
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

const yenFmt = new Intl.NumberFormat('ja-JP');

const toNum = (n) => {
  const v = Number(n);
  return Number.isFinite(v) ? v : 0;
}

const mileageFormatted = computed(() => {
  const tc = props.item?.time_card;
  if (!tc) return '';

  const km = toNum(tc.car_mileage);
  const gas = toNum(tc.gas_full_price);

  const title = km && responsive.mobile ? 'マイカー走行距離 : ' : '';
  const kmPart = km ? `${km}km` : '';
  const gasPart = gas > 0 ? `${yenFmt.format(gas)}円` : '';

  // two-line if gas exists, otherwise one line
  return gas > 0 ? `${title}${kmPart}\n${gasPart}` : `${title}${kmPart}`;
});

const mileageDetail = computed(() => {
  const tc = props.item?.time_card;
  if (!tc) return '';

  const km = toNum(tc.car_mileage);
  const gas = toNum(tc.gas_full_price);
  const dept = tc.car_project?.name ?? '';

  const kmPart = km ? `${km}km` : '';
  const gasPart = `ガソリン代 : ${gas > 0 ? `${yenFmt.format(gas)}円` : '—'}`;

  // department prefix only if it exists
  const deptPart = dept ? `${dept}:` : '';
  return `${deptPart}${kmPart}\n${gasPart}`;
});

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
    if(index !== null){
        return `<div class="condition-area"><div>${mobileTitle}</div><WeatherIcon :which="${index}" size="17"/></div>`
    }
    return ''
    
})

const hasWorkCost = computed(() => {
    const costs = props.item.time_card?.timecard_costs
    const costText = costs && costs.length ?
    costs.map(ob => {
        const department = ob.department !== null ? ob.department + '\n' : ''
        const costOption = costOptions.find(opt => opt.value === ob.type);
        const expense = ob.expenses !== null ? ob.expenses : 0
        return costOption ? `${department}${costOption.label}:${expense}円`: '';
    }).join(' ') : '';
    const title = costText && responsive.mobile ? '経費 : ' : ''
    return title + costText
})

const hasWorkCostLabel = (cost) => {
    return costOptions.find(opt => opt.value === cost.type)?.label;
}
const formatCostString = (cost) => {
    let result = '';

    if (cost.department) {
      result += `部門:${cost.department}<br>`;
    }

    if (hasWorkCostLabel(cost)) {
      result += `${hasWorkCostLabel(cost)}:`;
    }

    if (cost.content) {
      result += `${cost.content} `;
    }

    if (cost.expenses) {
      result += `${cost.expenses}円`;
    }

    return result;
}
const hasAction = computed(() => {
    const authorityCheck = Object.values(props.item.ability).some(val => val == true)
    return authorityCheck
})

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
const hasVehicle = computed(() => {
    const vehicleData = props.item?.time_card?.vehicle_data
    if (vehicleData) {
        const mobileTitle = responsive.mobile ? '車両使用 : ' : ''
        return mobileTitle + vehicleAsOptions.find(ob => ob.value === vehicleData.vehicle).label
    }
})
const vehicleDetail = computed(() => {
    const vehicleData = props.item?.time_card?.vehicle_data
    if (vehicleData) {
        const vehicle = vehicleAsOptions.find(ob => ob.value === vehicleData.vehicle)
        const text = `使用車両\n${vehicle.label}\n\nアルコールチェックした時間\n車両使用前: ${vehicleData.alcohol_before_time}\n車両使用後: ${vehicleData.alcohol_after_time}\n\nアルコールチェックした値\n車両使用前: ${vehicleData.alcohol_before_value}\n車両使用後: ${vehicleData.alcohol_after_value}\n\nアルコールチェックした確認者\n車両使用前: ${vehicleData.before_user.name}\n車両使用後: ${vehicleData.after_user.name}`
        return text
    }
})
</script>