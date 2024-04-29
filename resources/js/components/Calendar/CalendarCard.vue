<template>
    <div 
        :ref="`dayRecord_${record.id}`" 
        class="calendar-card-inner" 
        :class="[{'highlightedCalendar' : expanded}]"
        :id="`cal_${record.id}`" 
        @click.stop="openOrClose"       
        :style="{ background: background, color: color,}"
    >
        <div class="cal-userlist-full" v-if="expanded" :style="{marginBottom: '10px'}"> 
            <div v-for="user in listTruncate" style="width: fit-content;">
                <UserIcon :user="user" imgStyle="pointer-events: none" imgClass="userSmallIcon" size="15"/>
                <p @click.stop="pushInstantUser($event, user.id)" class="userName" style="white-space: break-spaces;font-size: 12px;margin-right: 25px;">{{ user.name }}</p>
            </div>
            <div style="cursor: pointer;" @click.stop="truncate = false" v-if="truncate && record.calendar_users.length > 6">...({{ record.calendar_users.length }})</div>
        </div>
        <div v-else style="display: flex;">
            <UserIcon :disableInstant="true" v-for="user in record.calendar_users.slice(0, 3)" :user="user" imgStyle="pointer-events: none" imgClass="userSmallIcon" size="15"/>
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
                <p>URL : <a :href="record.zoom_url ? record.zoom_url : ''">{{ record.zoom_url ? record.zoom_url : '' }}</a></p>               
            </div>
            <div @click="expanded ? $event.stopPropagation() : false" @mousedown="expanded ? $event.stopPropagation() : false"  v-if="record.files && record.files.length" style="margin-top: 10px;width: fit-content;max-width: 100%;overflow: hidden;">
                <CalendarFiles :list="record.files"/>
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

        <div @click.stop="menu.setMenu( {id: record.id, name: 'calendarRecordMenu', user_id: menu.user_id ? menu.user_id : null})" v-if="expanded && editable && record.shift == 0" class="boardMenuContainer cursor-pointer" style="align-self: normal;position: absolute;right: 10px;top: 10px;">
            <svg version="1.1" xmlns="http://www.w3.org/2000/svg" class="dot-menu" height="13" viewBox="0 0 7 32" style="margin: auto;">
                <path d="M6.905 28.051c-0.011-0.447-0.114-0.881-0.275-1.273-0.039-0.1-0.085-0.196-0.135-0.287-0.047-0.093-0.096-0.185-0.153-0.27l-0.083-0.129-0.042-0.065-0.090-0.122c-0.036-0.051-0.102-0.135-0.143-0.182l-0.033-0.040c-0.095-0.111-0.2-0.214-0.319-0.302l-0.001-0.001-0.081-0.058-0.065-0.040-0.132-0.082c-0.086-0.057-0.178-0.104-0.273-0.152-0.092-0.049-0.188-0.096-0.289-0.132-0.392-0.164-0.829-0.262-1.277-0.273-0.896-0.026-1.818 0.321-2.465 0.963-0.653 0.634-1.041 1.546-1.042 2.464-0.003 0.456 0.083 0.907 0.238 1.316 0.154 0.41 0.465 0.877 0.744 1.194 0.281 0.32 0.76 0.57 1.169 0.728s0.86 0.245 1.316 0.245c0.917 0.007 1.831-0.388 2.465-1.038 0.641-0.648 0.993-1.567 0.968-2.461z"></path><path d="M3.405 12.33c-0.447 0.013-0.881 0.115-1.272 0.278-0.1 0.038-0.195 0.085-0.287 0.135-0.093 0.047-0.185 0.097-0.27 0.154l-0.129 0.083-0.064 0.042-0.124 0.088c-0.050 0.039-0.132 0.104-0.181 0.145l-0.040 0.035c-0.111 0.096-0.214 0.202-0.302 0.319-0.001 0-0.001 0.001-0.001 0.001l-0.058 0.081-0.040 0.064-0.082 0.134c-0.056 0.086-0.104 0.179-0.15 0.271-0.049 0.095-0.095 0.189-0.132 0.289-0.164 0.394-0.262 0.832-0.27 1.277-0.025 0.899 0.324 1.82 0.967 2.467 0.636 0.651 1.549 1.038 2.465 1.037 0.456 0.003 0.906-0.086 1.315-0.239 0.41-0.156 0.781-0.374 1.112-0.619l0.188-0.188c0.246-0.331 0.463-0.701 0.619-1.112 0.157-0.408 0.245-0.858 0.245-1.315 0.003-0.918-0.392-1.832-1.043-2.465-0.648-0.639-1.567-0.991-2.464-0.961z"></path><path d="M6.162 5.606c0.282-0.359 0.493-0.767 0.622-1.187 0.129-0.417 0.186-0.842 0.196-1.255l-0.035-0.263c-0.107-0.399-0.264-0.799-0.493-1.174-0.224-0.376-0.526-0.721-0.888-1-0.721-0.569-1.682-0.821-2.582-0.694-0.903 0.117-1.746 0.622-2.276 1.347-0.267 0.36-0.451 0.767-0.563 1.174-0.033 0.103-0.054 0.206-0.071 0.307-0.021 0.103-0.038 0.207-0.043 0.309l-0.015 0.152-0.007 0.078-0.003 0.096c-0.003 0.132-0.001 0.262 0.004 0.39l0.008 0.16c0.018 0.077 0.033 0.152 0.056 0.227l0.028 0.092 0.028 0.075 0.053 0.145c0.032 0.096 0.077 0.191 0.122 0.287 0.043 0.096 0.089 0.189 0.145 0.282 0.21 0.371 0.494 0.717 0.84 1.002 0.691 0.57 1.633 0.863 2.538 0.754 0.904-0.099 1.771-0.58 2.336-1.302z"></path>
            </svg>
        </div>
        <div v-if="menu.id == record.id && menu.name == 'calendarRecordMenu' && expanded" id="calendarRecordMenu" class="boxMenu boardMenuIcon" style="top: 25px; right: 35px; z-index: 6;">
            <ul>
                <li @click.stop="edit(record)" class="boxMenuItems cursor-pointer">編集する</li>
                <li @click.stop="removeItem(record)" class="boxMenuItems cursor-pointer">削除する</li>
                <li @click.stop="viewDetails = true, menu.setMenu( {id: record.id, name: `cal_${record.id}`, user_id: menu.user_id ? menu.user_id : null})" class="boxMenuItems cursor-pointer">詳細情報</li>
            </ul>
        </div>
        
    </div>
</template>
<script setup>
    import moment from 'moment';
    import Autolinker from 'autolinker';
    import CalendarFiles from './CalendarFiles.vue';
    import { ref, computed, onMounted, inject } from 'vue'
    import UserIcon from '../Board/Mixed/UserIcon.vue';
    import colors from '../../../assets/colors.json'
    import { useAuthUserStore } from '@/store/auth'
    import { useMenuStore } from "@/store/menu";
    import { useTheme } from '@/store/theme';
    import { useTempRecord } from '@/store/tempRecord';
    const menu = useMenuStore()
    const auth = useAuthUserStore()
    const tempRecord = useTempRecord()
    const theme = useTheme()
    const truncate = ref(true)
    const viewDetails = ref(false)
    const props = defineProps(['record', 'viewable', 'editable', 'expanded', 'mode'])
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

    const background = computed(() => {
        if(selectedFacility.value.length){
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

    const color = computed(() => {
        if(selectedFacility.value.length){
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
        return fullDay.value ? '終日' : `${start.format('H:mm')} ~ ${end.format('H:mm')}`
    })

    const timeDetailed = computed(() => {
        const start = moment(props.record.date_start)
        const end = moment(props.record.date_end)
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
        if(menu.id == props.record.id){
            menu.setMenu({name: '', id: null})
        }else{
            emit('selectRecord',event, props.record, null)
        }
        
    }
    const pushInstantUser = inject('pushInstantUser')

</script>
