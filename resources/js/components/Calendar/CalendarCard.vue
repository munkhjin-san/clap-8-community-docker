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

        <div v-if="expanded && editable && record.shift == 0 && !record.task" class="flex" style="align-self: normal;position: absolute;right: 10px;top: 10px;" @click.stop>
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
            <svg class="dot-menu" width="13" height="13" viewBox="0 0 26.37367 20.22883">
                <path d="M10.64875,4.05732c2.42348.14379,4.84883.20267,7.27528.20474,2.42757-.00831,4.85296-.05046,7.27528-.25172.56408-.04928,1.02915-.49699,1.08059-1.08057.0577-.65453-.4261-1.23183-1.08059-1.28958-4.84258-.3629-9.70444-.30689-14.55056-.04698-.60886.03962-1.10911.52522-1.1505,1.1505-.04503.68046.47006,1.26859,1.1505,1.3136Z"/>
                <path d="M25.28784,8.94723c-4.87213-.3629-9.76343-.30689-14.63909-.04698-1.52714.14413-1.531,2.31784,0,2.4641,1.21993.08026,2.43986.11674,3.65977.15272,2.43986.07117,4.87977.07218,7.31955-.00449,1.21993-.03693,2.43984-.08834,3.65977-.1952.5646-.049,1.03004-.49715,1.08122-1.08124.05735-.65447-.42673-1.23149-1.08122-1.28891Z"/>
                <path d="M25.20456,16.25078c-1.21298-.10686-2.42597-.15827-3.63895-.1952-3.63957-.11057-7.28113-.06067-10.91687.14823-1.52655.14441-1.53046,2.31728,0,2.4641,1.213.08026,2.42598.11674,3.63896.15266,2.42598.07122,4.852.07223,7.2779-.00449,1.21298-.03693,2.42597-.08834,3.63895-.1952.56412-.04922,1.0292-.49699,1.08063-1.08063.05767-.65447-.42614-1.23177-1.08063-1.28947Z"/>
                <path d="M2.27912,5.64453c-.2334,0-.44824-.09082-.60645-.25684-.13477-.13965-.26172-.32324-.36816-.48145l-.31836-.47266c-.06836-.10645-.41797-.65137-.53223-.83398-.00391-.00488-.29102-.47363-.34375-.56738-.21777-.37695-.10352-.85645.26172-1.08984.12891-.08203.2793-.12695.43457-.12695.2373,0,.46191.10449.61621.28613l.32129.39258s.55566.70312.59668.75684c.25977-.28809,1.85156-2.04004,1.85156-2.04004l.87793-.95117c.15234-.16406.36914-.25977.59473-.25977.17383,0,.34473.05859.48242.16309.17383.13574.28027.32617.30566.53809.02539.21094-.03613.42578-.16699.59082l-.94043,1.16797-.7002.85742c-.27441.33496-1.37598,1.66602-1.6543,1.99414-.17871.20898-.44434.33301-.71191.33301h0Z"/>
                <path d="M2.27912,12.93668c-.2334,0-.44824-.09082-.60645-.25684-.13477-.13965-.26172-.32324-.36816-.48145l-.31836-.47266c-.06836-.10645-.41797-.65137-.53223-.83398-.00391-.00488-.29102-.47363-.34375-.56738-.21777-.37695-.10352-.85645.26172-1.08984.12891-.08203.2793-.12695.43457-.12695.2373,0,.46191.10449.61621.28613l.32129.39258s.55566.70312.59668.75684c.25977-.28809,1.85156-2.04004,1.85156-2.04004l.87793-.95117c.15234-.16406.36914-.25977.59473-.25977.17383,0,.34473.05859.48242.16309.17383.13574.28027.32617.30566.53809.02539.21094-.03613.42578-.16699.59082l-.94043,1.16797-.7002.85742c-.27441.33496-1.37598,1.66602-1.6543,1.99414-.17871.20898-.44434.33301-.71191.33301h0Z"/>
                <path d="M2.27912,20.22883c-.2334,0-.44824-.09082-.60645-.25684-.13477-.13965-.26172-.32324-.36816-.48145l-.31836-.47266c-.06836-.10645-.41797-.65137-.53223-.83398-.00391-.00488-.29102-.47363-.34375-.56738-.21777-.37695-.10352-.85645.26172-1.08984.12891-.08203.2793-.12695.43457-.12695.2373,0,.46191.10449.61621.28613l.32129.39258s.55566.70312.59668.75684c.25977-.28809,1.85156-2.04004,1.85156-2.04004l.87793-.95117c.15234-.16406.36914-.25977.59473-.25977.17383,0,.34473.05859.48242.16309.17383.13574.28027.32617.30566.53809.02539.21094-.03613.42578-.16699.59082l-.94043,1.16797-.7002.85742c-.27441.33496-1.37598,1.66602-1.6543,1.99414-.17871.20898-.44434.33301-.71191.33301h0Z"/>
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
import { MenuList } from '@/interface/globalInterface.js';
import { useDialog } from '@/composables/dialog.js';
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
