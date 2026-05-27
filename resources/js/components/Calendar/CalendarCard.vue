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
        <div v-if="!fullDay || expanded" @click="expanded ? $event.stopPropagation() : false" @mousedown="expanded ? $event.stopPropagation() : false" :class="['cal-card-item', {'wrap cal-selectable' : expanded }]">
            <span class="bg-[tomato] text-[white] px-[5px] pb-[1px] rounded-md mr-[3px] text-[10px]" v-if="record.temp_flag == 1">仮</span>
            <span :style="{background: projectColor}" class="w-[13px] min-w-[13px] h-[13px] inline-block mb-[-2px]" v-if="projectColor"></span>
            {{ viewable ? record.title : '予定' }}
        </div>
        <div v-if="!expanded && !fullDay" class="cal-card-item" style="white-space: nowrap;">{{ time }}</div>
        <div @click="expanded ? $event.stopPropagation() : false" @mousedown.stop v-if="expanded" :class="['cal-card-item', {'wrap cal-selectable' : expanded }]" style="line-height:1.5;margin: 10px 0;display: flex;gap: 10px;align-items: center;">                
            <div v-html="timeDetailed"></div>
        </div> 
        <div @click="expanded ? $event.stopPropagation() : false" @mousedown.stop v-if="expanded && record.temp_flag == 1" class="flex gap-[10px]">
            <CommandButton  :buttons="[
                {title: '確定', action: () => confirmTemp(record.id, 1)},
                {title: 'キャンセル', action: () => confirmTemp(record.id, 0)}
            ]"/>
        </div>
        <div @click="expanded ? $event.stopPropagation() : false" class="cal-card-item card-repet-info" v-if="expanded && record.repetition_type > 0 && viewable" v-html="repeatInformation"></div>        
        
        <div v-if="expanded && viewable" style="width: fit-content;max-width: 100%;">
            <div @click="expanded ? $event.stopPropagation() : false" @mousedown="expanded ? $event.stopPropagation() : false" v-if="remarks" class="wrap cal-remark break-all" v-html="remarks"></div>
            <div v-if="record.referrer" style="white-space: break-spaces;line-height: 1.5;user-select: all;">
                <a target="_blank" :href="record.referrer">{{ record.referrer }}</a>
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
                    <button class="px-[10px] py-[5px] bg-black text-white text-[12px]" @click="setSummaryViewing(record)">AIコンパニオン要約</button>
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
                {title: '複製する', action: () => duplicate(record)},
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
<script setup lang="ts">
import CalendarFiles from './CalendarFiles.vue';
import { ref, computed, onMounted, inject } from 'vue'
import UserPanel from '@/components/Global/UserPanel.vue'
import colors from 'assets/colors.json'
import { useAuthUserStore } from '@/store/auth'
import { useMenuStore } from "@/store/menu";
import { useTheme } from '@/store/theme';
import { useTempRecord } from '@/store/tempRecord';
import ItemMenu from '@/components/Global/ItemMenu.vue';
import { timeFormat, urlCheck } from '@/utils/tools';
import { DateTime } from 'luxon';
import { useCalendar } from '@/composables/calendar';
import CommandButton from '@/components/Global/CommandButton.vue';
import { CalendarGroupUser, CalendarRecord } from '@/interface/calendarInterface';
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
    const createdDate = computed(() => props.record.created_at && DateTime.fromISO(props.record.created_at).toLocaleString(DateTime.DATETIME_MED))

    const creator = computed(() => props.record.created_by && props.record.created_by.name)

    const updatedDate = computed(() => props.record.updated_at && DateTime.fromISO(props.record.updated_at).toLocaleString(DateTime.DATETIME_MED))

    const updater = computed(() => props.record.updated_by && props.record.updated_by.name)
    
    const listTruncate = computed(() => truncate.value ? props.record.calendar_users.slice(0, 6) : props.record.calendar_users)
    
    const remove = inject<Function>('deleteCalendar') as Function

    const removeItem = (rec: CalendarRecord) => {        
        remove(rec)        
    }
    const edit = inject<Function>('editRecord') as Function
    const duplicate = inject<Function>('duplicateRecord') as Function

    const confirmTemp = inject<Function>('confirmTemp') as Function

    const { facilitiesList, departmentsList } = useCalendar()

    const setSummaryViewing = inject<Function>('setSummaryViewing') as Function

    const background = computed(() => {
        if(selectedFacility.value.length || selectedDepartment.value){
            return '#606060'
        }
        const me = props.record.calendar_users.filter((ob: CalendarGroupUser) => ob.id == auth.id)
        const colorIndex = auth.user && auth.user.color ? auth.user.color : 0
        return me.length ? colors[colorIndex]?.light : 'var(--task-background)'
    })

    const selectedFacilityExpaned = computed(() => {
        const selected:string[] = []
        for(const index of Object.keys(facilitiesList.value) as (keyof typeof facilitiesList.value)[]){
            const rec_check = props.record[index]
            if(rec_check !== null && facilitiesList.value[index][rec_check]){
                selected.push(facilitiesList.value[index][rec_check].label)
            }
        }
        return selected
    })

    const selectedFacility = computed(() => {
        const selected:string[] = []
        for(const index of Object.keys(facilitiesList.value) as (keyof typeof facilitiesList.value)[]){
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
            const newWindow = window.open(url, '_blank');
            if (newWindow) newWindow.focus();
        }
    }
    const color = computed(() => {
        if(selectedFacility.value.length){
            return '#fff'
        }
        if(selectedDepartment.value){
            return '#fff'
        }
        const me = props.record.calendar_users.filter((ob: CalendarGroupUser) => ob.id == auth.activeUser.id)
        return me.length && theme.dark ? 'var(--background-color)' : 'var(--primary-color)'
    })

    const remarks = computed(() => {
        const text = props.record.remarks ? props.record.remarks : ''        
        var linkedText = urlCheck(text)            
        return linkedText;                
    })
    const calendarDateInstances = computed(() => {
        const start = DateTime.fromSQL(props.record.date_start)
        const end = DateTime.fromSQL(props.record.date_end)
        return {start, end}
    })
    const time = computed(() => {

        if(props.record.task){
            const task = props.record.task               
            return timeFormat(task.response_time)        }
        return fullDay.value ? '終日' : `${calendarDateInstances.value.start.toLocaleString(DateTime.TIME_24_SIMPLE)} ~ ${calendarDateInstances.value.end.toLocaleString(DateTime.TIME_24_SIMPLE)}`
    })

    const timeDetailed = computed(() => {
        if(props.record.task){
            const task = props.record.task               
            return timeFormat(task.response_time)
        }
        return fullDay.value ? '終日' : `${calendarDateInstances.value.start.toLocaleString(DateTime.DATETIME_MED_WITH_WEEKDAY)} ~ ${calendarDateInstances.value.end.toLocaleString(DateTime.TIME_24_SIMPLE)}`
    })

    const fullDay = computed(() => {
        const diff = Math.abs(calendarDateInstances.value.start.diff(calendarDateInstances.value.end, 'hours').hours);
        return diff >= 23;
    })

    const repeatInformation = computed(() => {
        if(props.record.repetition_type == 0){
            return `${calendarDateInstances.value.start.toLocaleString(DateTime.TIME_24_SIMPLE)} ~ ${calendarDateInstances.value.end.toLocaleString(DateTime.TIME_24_SIMPLE)}`
        }else if(props.record.repetition_type == 1){
            if(props.record.repeat_week && props.record.expiration_start && props.record.expiration_end){
                const days = props.record.repeat_week.split(',').map(Number);
                const days_list:string[] = [];
                for(const i in days){
                    const dayOfWeekString = DateTime.now().set({ weekday: days[i] }).toFormat('ccc');
                    days_list.push(dayOfWeekString)
                }
                const until = DateTime.fromSQL(props.record.expiration_end).toFormat('yyyy/M/d')
                const daysString = days_list.join('・');
                return `${until}まで毎週<strong>${daysString}</strong>`
            }            
        }else if(props.record.repetition_type == 2){
            if(props.record.repeat_days && props.record.expiration_start && props.record.expiration_end){
                const until = DateTime.fromSQL(props.record.expiration_end).toFormat('yyyy/M/d')
                return `${until}まで毎月<strong>${props.record.repeat_days}日</strong>`
            }
        }else if(props.record.repetition_type == 3){
            return  
        }
    })
    const projectColor = computed(() => {
        const projectColorSettings = auth.user?.project_settings
        const department_id = props.record?.department_id
        if(!projectColorSettings || projectColorSettings.length == 0 || !department_id){
            return null
        }
        const projectSetting = projectColorSettings.find(ps => ps.project_id == department_id)
        if(!projectSetting || !projectSetting.color){
            return null
        }
        return projectSetting.color   

    })
    const openOrClose = (event: Event) => {
        if(menu.parent === props.uniqueId){
            menu.close()
        }else{
            truncate.value = true
            emit('selectRecord',event, props.record, null)
        }
        
    }
    const pushInstantUser = inject<Function>('pushInstantUser') as Function

</script>
