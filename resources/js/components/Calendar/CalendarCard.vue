<template>
    <div 
        :ref="`dayRecord_${record.id}`" 
        class="calendar-card-inner" 
        :class="[{'highlightedCalendar' : expanded}]"
        :id="uniqueId" 
        @click.stop="openOrClose"       
        :style="{ background: background, color: color,}"
    >
        <div class="cal-userlist-full" v-if="expanded" :style="{marginBottom: '10px'}"> 
            <div v-for="user in listTruncate" style="width: fit-content;">
                <UserPanel :user="user" imgStyle="pointer-events: none" imgClass="userSmallIcon" size="15"/>
                <p @click.stop="pushInstantUser($event, user.id)" class="userName" style="white-space: break-spaces;font-size: 12px;margin-right: 25px;">{{ user.name }}</p>
            </div>
            <div style="cursor: pointer;" @click.stop="truncate = false" v-if="truncate && record.calendar_users.length > 6">...({{ record.calendar_users.length }})</div>
        </div>
        <div v-else style="display: flex;">
            <UserPanel :disableInstant="true" v-for="user in record.calendar_users.slice(0, 3)" :user="user" imgStyle="pointer-events: none" imgClass="userSmallIcon" size="15"/>
            <span style="line-height: 15px;" v-if="record.calendar_users.length > 3">...({{ record.calendar_users.length }})</span>
            <div style="margin: 1px 0 0 5px;overflow: hidden;" v-if="!expanded && fullDay">{{ viewable ? record.title : '予定' }}</div>
        </div>
        <div v-if="!fullDay || expanded" @click="expanded ? $event.stopPropagation() : false" @mousedown="expanded ? $event.stopPropagation() : false" :class="['cal-card-item', {'wrap cal-selectable' : expanded }]">{{ viewable ? record.title : '予定' }}</div>
        <div v-if="!expanded && !fullDay" class="cal-card-item" style="white-space: nowrap;">{{ time }}</div>
        <div @click="expanded ? $event.stopPropagation() : false" @mousedown.stop v-if="expanded" :class="['cal-card-item', {'wrap cal-selectable' : expanded }]" style="line-height:1.5;margin: 10px 0;display: flex;gap: 10px;align-items: center;">                
            <div v-html="timeDetailed"></div>
        </div> 
        <div @click="expanded ? $event.stopPropagation() : false" class="cal-card-item card-repet-info" v-if="expanded && record.repetition_type > 0 && viewable" v-html="repeatInformation"></div>        
        
        <div v-if="expanded && viewable" style="width: fit-content;max-width: 100%;">
            <div @click="expanded ? $event.stopPropagation() : false" @mousedown="expanded ? $event.stopPropagation() : false" v-if="remarks" class="wrap cal-remark" v-html="remarks"></div>
            <div v-if="record.referrer" style="white-space: break-spaces;line-height: 1.5;user-select: all;">
                <a :href="record.referrer">{{ record.referrer }}</a>
            </div>
            <div v-if="record.department" style="border-radius:3px;padding: 5px;background: rgba(0,0,0,60%);width: fit-content;margin: 5px 0;">
                <p style="color:#fff; font-size: 11px">{{record.department.name}}</p>
            </div>
            <div v-if="selectedFacilityExpaned && selectedFacilityExpaned.length" style="display: flex; flex-wrap: wrap;gap:10px;margin: 5px 0;">
                <div v-for="selected in selectedFacilityExpaned" style="display: flex;border-radius:3px;align-items: center;padding: 5px;background: rgba(0,0,0,60%);justify-content: end;">                       
                    <p style="color:#fff; font-size: 11px">{{selected}}</p>
                </div>
            </div>
            
            <div @click="expanded ? $event.stopPropagation() : false" @mousedown="expanded ? $event.stopPropagation() : false"  v-if="record.zoom_value !== null && record.zoom_url" class="zoom-info-box">
                <p>アカウント : <span class="zoom-info-item">{{ record.zoom_account ? record.zoom_account : '' }}</span></p>
                <p>アカウントPASS : <span class="zoom-info-item">{{ record.zoom_account_pass ? record.zoom_account_pass : '' }}</span></p>
                <p>ミーティングID : <span class="zoom-info-item">{{ record.zoom_id ? record.zoom_id : '' }}</span></p>
                <p>ミーティングPASS :<span class="zoom-info-item">{{ record.zoom_pass ? record.zoom_pass : '' }}</span> </p>
                <p>URL : <a target="_blank" :href="record.zoom_url ? record.zoom_url : ''">{{ record.zoom_url ? record.zoom_url : '' }}</a></p> 
                <div class="mt-[15px]" v-if="record.summaries_count">
                    <CommandButton :buttons="[{title: 'AIコンパニオン要約', action: () => {
                        setSummaryViewing(record)
                    }}]"/>
                </div>              
            </div>
            <div @click="expanded ? $event.stopPropagation() : false" @mousedown="expanded ? $event.stopPropagation() : false"  v-if="record.files && record.files.length" style="margin-top: 10px;width: fit-content;max-width: 100%;overflow: hidden;">
                <CalendarFiles :list="record.files"/>
            </div>
        </div>       
        <div v-if="selectedDepartment && !expanded" style="position: absolute; top: 0px; right: 0px;">
            <div style="display: flex;align-items: center;padding: 5px;background: rgba(0,0,0,60%);justify-content: end;">                       
                <p style="color:#fff; font-size: 11px">{{selectedDepartment.name}}</p>
            </div>
        </div>
        <div v-if="selectedFacility && selectedFacility.length && !expanded" style="position: absolute; bottom: 0px; right: 0px;">
            <div v-for="selected in selectedFacility" style="display: flex;align-items: center;padding: 5px;background: rgba(0,0,0,60%);justify-content: end;">                       
                <p style="color:#fff; font-size: 11px">{{selected}}</p>
            </div>
        </div>

        <div @click.stop v-if="viewDetails && expanded" style="line-height: 1.5;white-space: break-spaces;width:fit-content;user-select: text;cursor: text;">
            <div>作成日 : {{ createdDate }}</div>
            <div>作成者 : {{ creator }}</div>
            <div>更新日 : {{ updatedDate }}</div>
            <div>更新者 : {{ updater }}</div>
        </div>

        <div v-if="expanded && editable && record.shift == 0 && !record.task" style="align-self: normal;position: absolute;right: 10px;top: 10px;" @click.stop>
            <ItemMenu :items="[
                {title: '編集する', action: () => edit(record)},
                {title: '削除する', action: () => removeItem(record)},
                {title: '最終更新者', action: () => viewDetails = !viewDetails}
            ]"/>
        </div>  
        <div v-if="record.task" @click.stop="toTask" style="position: absolute; top: 5px; right: 5px;">
            <svg class="dot-menu" version="1.1" xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 37 32">
                <path d="M36.297 0.493c-0.529-0.407-1.289-0.312-1.742 0.177l-2.463 2.656-2.479 2.698c-1.644 1.805-3.295 3.607-4.927 5.425-1.633 1.815-3.274 3.625-4.9 5.446-0.906 1.016-1.818 2.030-2.726 3.046-0.293 0.329-0.814 0.303-1.073-0.054-0.061-0.083-0.124-0.169-0.187-0.252l-0.538-0.737-1.64-2.19c-0.726-0.977-1.471-1.94-2.22-2.9l-1.134-1.428c-0.384-0.472-0.767-0.947-1.16-1.413-0.435-0.515-1.21-0.637-1.791-0.225-0.567 0.401-0.704 1.19-0.355 1.792 0.296 0.513 0.607 1.020 0.914 1.528l0.961 1.551c0.652 1.030 1.306 2.056 1.978 3.069l1.509 2.284 0.509 0.755c0.68 1.007 1.366 2.011 2.070 3.003l0.082 0.115c0.095 0.133 0.207 0.252 0.339 0.36 0.794 0.645 1.97 0.495 2.63-0.283 1.569-1.848 3.105-3.724 4.657-5.585 1.564-1.876 3.113-3.766 4.667-5.649 1.558-1.882 3.096-3.779 4.641-5.67l2.304-2.852 2.291-2.858c0.436-0.547 0.358-1.364-0.22-1.809z"></path>
                <path d="M30.798 13.688c-0.736 0.045-1.297 0.682-1.307 1.417l-0.182 13.496c-0.004 0.298-0.247 0.532-0.545 0.527-1.719-0.029-3.439-0.041-5.158-0.055l-7.281-0.017-7.281-0.001-5.073 0.015c-0.257 0-0.465-0.21-0.462-0.466 0.019-1.7 0.019-3.398 0.019-5.098l-0.026-7.281-0.026-7.279-0.033-5.239c-0.001-0.21 0.168-0.38 0.378-0.381 1.558-0.010 3.114-0.023 4.671-0.031l20.184-0.204c0.809-0.008 1.46-0.691 1.409-1.517-0.046-0.754-0.701-1.326-1.457-1.334l-20.136-0.204c-2.244-0.012-4.486-0.037-6.729-0.038-0.915 0-1.66 0.739-1.667 1.655v0.010l-0.049 7.281-0.024 7.279-0.026 7.281c0 2.427 0 4.854 0.055 7.279l0.001 0.037c0.022 0.925 0.777 1.67 1.709 1.673l7.281 0.022 7.281-0.003 7.281-0.018c2.427-0.018 4.854-0.029 7.281-0.106l0.074-0.003c0.86-0.026 1.542-0.736 1.531-1.603l-0.212-15.725c-0.015-0.787-0.68-1.421-1.482-1.372z"></path>
            </svg>                                                
        </div>

    </div>
</template>
<script setup>
import moment from 'moment';
import Autolinker from 'autolinker';
import CalendarFiles from './CalendarFiles.vue';
import { ref, computed, onMounted, inject } from 'vue'
import UserPanel from '@/components/Global/UserPanel.vue'
import colors from '../../../assets/colors.json'
import { useAuthUserStore } from '@/store/auth'
import { useMenuStore } from "@/store/menu";
import { useTheme } from '@/store/theme';
import { useTempRecord } from '@/store/tempRecord';
import ItemMenu from '@/components/Global/ItemMenu.vue';
import { timeFormat } from '@/utils/tools';
import CommandButton from '../Global/CommandButton.vue';
    const menu = useMenuStore()
    const auth = useAuthUserStore()
    const tempRecord = useTempRecord()
    const theme = useTheme()
    const truncate = ref(true)
    const viewDetails = ref(false)
    const props = defineProps(['record', 'viewable', 'editable', 'expanded', 'mode', 'uniqueId'])
    const emit = defineEmits(['selectRecord'])
    onMounted(() => {
        if(tempRecord.id && tempRecord.id == props.record.id){  
            setTimeout(() => {
                emit('selectRecord', null, props.record, 'auto')  
            });
                             
        }
    })
    const createdDate = computed(() => { return props.record.created_at ? moment(props.record.created_at).format('YYYY/M/D HH:mm:ss') : ''})

    const creator = computed(() => { return props.record.created_by ? props.record.created_by.name : '' })

    const updatedDate = computed(() => { return props.record.created_at ? moment(props.record.updated_at).format('YYYY/M/D HH:mm:ss') : ''})

    const updater = computed(() => { return props.record.updated_by ? props.record.updated_by.name : '' })
    
    const listTruncate = computed(() => { return !truncate.value ? props.record.calendar_users : props.record.calendar_users.slice(0, 6)})
    
    const remove = inject('deleteCalendar')

    const removeItem = (rec) => {        
        remove(rec)        
    }
    const edit = inject('editRecord')

    const facilitiesList = inject('facilities')
    const departmentsList = inject('selectedDepartment')

    const setSummaryViewing = inject('setSummaryViewing')

    const background = computed(() => {
        if(selectedFacility.value.length){
            return '#606060'
        }
        if(selectedDepartment.value){
            return '#606060'
        }
        const me = props.record.calendar_users.filter(ob => ob.id == auth.activeUser.id)
        return me.length ? colors[auth.activeUser.color]?.light : 'var(--task-background)'
    })

    const selectedFacilityExpaned = computed(() => {
        const selected = []
        for(const index in facilitiesList.value){
            const rec_check = props.record[index]
            if(rec_check !== null && facilitiesList.value[index][rec_check]){
                selected.push(facilitiesList.value[index][rec_check].label)
            }
        }
        return selected
    })

    const selectedFacility = computed(() => {
        const selected = []
        for(const index in facilitiesList.value){
            const rec_check = props.record[index]
            if(rec_check !== null && facilitiesList.value[index][rec_check] && facilitiesList.value[index][rec_check].selected == true){
                selected.push(facilitiesList.value[index][rec_check].label)
            }
        }
        return selected
    })
    const selectedDepartment = computed(() => {
        if(departmentsList.value.includes(props.record.department_id) && props.record.department){
            return props.record.department
        } 
    })
    const toTask = () => {
        if(props.editable){
            const task = props.record.task
            const url = '/board/' + task.board_id + '?t='+ task.id + '&action=true'
            window.open(url, '_blank').focus();
        }
    }
    const color = computed(() => {
        if(selectedFacility.value.length){
            return '#fff'
        }
        if(selectedDepartment.value){
            return '#fff'
        }
        const me = props.record.calendar_users.filter(ob => ob.id == auth.activeUser.id)
        return me.length && theme.dark ? 'var(--background-color)' : 'var(--primary-color)'
    })

    const remarks = computed(() => {
        const text = props.record.remarks ? props.record.remarks : ''        
        var linkedText = Autolinker.link(text, {stripPrefix: false});              
        return linkedText;                
    })

    const time = computed(() => {
        const start = moment(props.record.date_start)
        const end = moment(props.record.date_end)
        if(props.record.task){
            const task = props.record.task               
            return timeFormat(task.response_time)
        }
        return fullDay.value ? '終日' : `${start.format('H:mm')} ~ ${end.format('H:mm')}`
    })

    const timeDetailed = computed(() => {
        const start = moment(props.record.date_start)
        const end = moment(props.record.date_end)
        if(props.record.task){
            const task = props.record.task               
            return timeFormat(task.response_time)
        }
        return fullDay.value ? '終日' : `${start.format('YYYY/M/D(ddd) H:mm')} ~ ${end.format('H:mm')}`
    })

    const fullDay = computed(() => {
        const start = moment(props.record.date_start)
        const end = moment(props.record.date_end)
        const diff = Math.abs(start.diff(end, 'hours')) >= 23 
        return diff
    })

    const repeatInformation = computed(() => {
        if(props.record.repetition_type == 0){
            return `${moment(props.record.date_start).format('H:mm')} ~ ${moment(props.record.date_end).format('H:mm')}`
        }else if(props.record.repetition_type == 1){
            if(props.record.repeat_week && props.record.expiration_start && props.record.expiration_end){
                const days = props.record.repeat_week.split(',').map(Number);
                let days_list = [];
                for(const i in days){
                    const dayOfWeekString = moment().day(days[i]).format('ddd');
                    days_list.push(dayOfWeekString)
                }
                const until = moment(props.record.expiration_end).format('YYYY/M/D')
                const daysString = days_list.join('・');
                return `${until}まで毎週<strong>${daysString}</strong>`
            }            
        }else if(props.record.repetition_type == 2){
            if(props.record.repeat_days && props.record.expiration_start && props.record.expiration_end){
                const until = moment(props.record.expiration_end).format('YYYY/M/D')
                return `${until}まで毎月<strong>${props.record.repeat_days}日</strong>`
            }
        }else if(props.record.repetition_type == 3){
            return  
        }
    })
    const openOrClose = (event) => {
        if(menu.parent === props.uniqueId){
            menu.close()
        }else{
            truncate.value = true
            emit('selectRecord',event, props.record, null)
        }
        
    }
    const pushInstantUser = inject('pushInstantUser')

</script>
