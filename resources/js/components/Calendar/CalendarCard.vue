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

        <div v-if="expanded && editable && record.shift == 0" style="align-self: normal;position: absolute;right: 10px;top: 10px;" @click.stop>
            <ItemMenu :items="[
                {title: '編集する', action: () => edit(record)},
                {title: '削除する', action: () => removeItem(record)},
                {title: '詳細情報', action: () => viewDetails = true}
            ]"/>
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
    import ItemMenu from '@/components/Global/ItemMenu.vue';
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
