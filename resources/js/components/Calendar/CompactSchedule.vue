<template>
<div class="text-[12px] flex flex-col w-full" :style="{ background: background, color: color,}">
    <div v-if="mode == 'compact'" class="overflow-hidden text-ellipsis whitespace-nowrap leading-normal px-2 py-2">
        <span v-if="isTemp" class="bg-[tomato] text-[white] px-[5px] pb-[1px] rounded-md mr-[3px] text-[10px]">仮</span>
        <span class="ml-1">{{ record?.title  }}</span>
    </div>
    <div v-if="mode == 'detailed'" class="px-3 pb-3 pt-1">
        <div class="flex items-center mb-[10px]">
            <UserPanel :disableInstant="true" v-for="user in record.calendar_users.slice(0, 3)" :user="user" imgStyle="pointer-events: none" imgClass="userSmallIcon" size="15"/>
            <span style="line-height: 15px;" v-if="record.calendar_users?.length > 3">...({{ record.calendar_users.length }})</span>
        </div>
        <div class="cal-card-item text-[11px] text-[gray] leading-normal my-[10px] flex gap-[10px] items-center" v-html="timeDetailed"></div> 
        <div style="width: fit-content;max-width: 100%;">
            <div v-if="record.remarks" class="wrap cal-remark break-all" v-html="record.remarks"></div>
            <div v-if="record.referrer" style="white-space: break-spaces;line-height: 1.5;user-select: all;">
                <a target="_blank" :href="record.referrer">{{ record.referrer }}</a>
            </div>
            
            <div v-if="record.zoom_value !== null && record.zoom_url" class="zoom-info-box">
                <p>アカウント : <span class="zoom-info-item">{{ record.zoom_account ? record.zoom_account : '' }}</span></p>
                <p>アカウントPASS : <span class="zoom-info-item">{{ record.zoom_account_pass ? record.zoom_account_pass : '' }}</span></p>
                <p>ミーティングID : <span class="zoom-info-item">{{ record.zoom_id ? record.zoom_id : '' }}</span></p>
                <p>ミーティングPASS :<span class="zoom-info-item">{{ record.zoom_pass ? record.zoom_pass : '' }}</span> </p>
                <p>URL : <a target="_blank" :href="record.zoom_url ? record.zoom_url : ''">{{ record.zoom_url ? record.zoom_url : '' }}</a></p>              
            </div>
        </div> 
        <div class="flex gap-[10px] mt-2 items-end flex-wrap">
            <CommandButton v-if="isTemp" :buttons="[
                {title: '確定', action: () => confirmTemp(record.id, 1)},
                {title: 'キャンセル', action: () => confirmTemp(record.id, 0)}
            ]"/>
            <div class="ml-auto">
                <router-link target="_blank" :to="{name: 'schedule', query: {id: record.id}}">詳細</router-link>
            </div>
            
        </div>
    </div>

</div>
</template>
<script setup lang="ts">
import { CalendarRecord } from '@/interface/calendarInterface';
import { timeFormat } from '@/utils/tools';
import { DateTime } from 'luxon';
import { computed } from 'vue';
import UserPanel from '@/components/Global/UserPanel.vue';
import CommandButton from '@/components/Global/CommandButton.vue';
import { useApi } from '@/composables/api';
import { useAuthUserStore } from '@/store/auth';
import { useTheme } from '@/store/theme';
import colors from 'assets/colors.json'

const props = defineProps<{
    record: CalendarRecord
    mode: 'compact' | 'detailed'
}>()

const emit = defineEmits<{
    refresh: []
}>()

const auth = useAuthUserStore()
const theme = useTheme()
const api = useApi()

const isTemp = computed(() => {
    return props.record.temp_flag == 1
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
const calendarDateInstances = computed(() => {
    const start = DateTime.fromSQL(props.record.date_start)
    const end = DateTime.fromSQL(props.record.date_end)
    return {start, end}
})

const background = computed(() => {
    const me = props.record.calendar_users.filter(ob => ob.id == auth.id)
    const colorIndex:number = auth.user && auth.user.color ? auth.user.color : 0
    return me.length ? colors[colorIndex]?.light : 'var(--task-background)'
})
const color = computed(() => {
    const me = props.record.calendar_users.filter(ob => ob.id == auth.activeUser.id)
    return me.length && theme.dark ? 'var(--background-color)' : 'var(--primary-color)'
})
const confirmTemp = async(id:number, status:number) => {
    const question = status ? '仮予約を確定しますか。' : '仮予約をキャンセルしますか。';
    const pie = status ? '仮予約を確定しました。' : '仮予約をキャンセルしました。';
        
    await api.post('/calendar_temp_confirm', {id: id, status: status}, {
        ask: question,            
        toast: pie,            
    })
    emit('refresh');

}
</script>