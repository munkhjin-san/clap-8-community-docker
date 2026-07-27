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

        <div v-if="expanded && editable && record.shift == 0 && !record.task" class="flex" style="align-self: normal;position: absolute;right: 10px;top: 10px;gap: 5px;" @click.stop>
            <ItemMenu 
                type="share" :items="shareMenuItems"
            />
            <ItemMenu :items="[
                {title: '編集する', action: () => edit(record)},
                {title: '複製する', action: () => duplicate(record)},
                {title: '削除する', action: () => removeItem(record)},
                {title: '最終更新者', action: () => viewDetails = !viewDetails}
            ]"/>
        </div>  
        <div v-if="record.task" @click.stop="toTask" style="position: absolute; top: 5px; right: 5px;">
            <TaskIcon size="13" />                                             
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
import { MenuList } from '@/interface/globalInterface.js';
import { useDialog } from '@/composables/dialog.js';
import TaskIcon from '../Icons/TaskIcon.vue';
    const menu = useMenuStore()
    const auth = useAuthUserStore()
    const tempRecord = useTempRecord()
    const theme = useTheme()
    const truncate = ref(true)
    const viewDetails = ref(false)
    const props = defineProps(['record', 'viewable', 'editable', 'expanded', 'mode', 'uniqueId'])
    const emit = defineEmits(['selectRecord'])
    const { toast } = useDialog()
    onMounted(() => {
        if(tempRecord.id && tempRecord.id == props.record.id){  
            setTimeout(() => {
                emit('selectRecord', null, props.record, 'auto')  
            });
                             
        }
    })
    const shareMenuItems = computed(() => {
        const list:MenuList[]= []; 
        function addItem(title: string, action: () => void) {
            list.push({ title, action });
        }
        const builtInApps = [
            {name: 'copy', name_jp: '内容をコピー'}, 
        ] 
        builtInApps.forEach(app => {
            addItem(app.name_jp, () => copyRecordInfo())
        });

        return list
    })
    const copyRecordInfo = () => {
        const lines: string[] = []
        if(props.record.title){
            lines.push(`タイトル : ${props.record.title}`)
        }
        lines.push(`日時 : ${timeDetailed.value}`)
        if(props.record.remarks){
            lines.push(`内容 : ${props.record.remarks}`)
        }
        if(props.record.zoom_value !== null && props.record.zoom_url){
            lines.push(`URL : ${props.record.zoom_url}`)
        }
        navigator.clipboard.writeText(lines.join('\n'))
        .then(() => {
            toast('コピーしました。')
            menu.setMenu({ name: '', id: null })
        })
    }
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
        const me = props.record.calendar_users.filter((ob: CalendarGroupUser) => ob.id == auth.id)
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
