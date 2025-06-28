<template>
<div class="p-[10px] text-[12px]" :style="{ background: background, color: color,}">
    <div class="flex items-center mb-[10px]">
        <UserPanel :disableInstant="true" v-for="user in record.calendar_users.slice(0, 3)" :user="user" imgStyle="pointer-events: none" imgClass="userSmallIcon" size="15"/>
        <span style="line-height: 15px;" v-if="record.calendar_users?.length > 3">...({{ record.calendar_users.length }})</span>
    </div>
    <div class="overflow-hidden break-words leading-normal">{{ record?.title }}</div>
    <div class="cal-card-item leading-normal my-[10px] flex gap-[10px] items-center" v-html="timeDetailed"></div> 
    <div class="flex gap-[10px]"></div>
    <div class="flex gap-[10px]">
        <CommandButton  :buttons="[
            {title: '確定', action: () => confirmTemp(record.id, 1)},
            {title: 'キャンセル', action: () => confirmTemp(record.id, 0)}
        ]"/>
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
}>()

const emit = defineEmits<{
    refresh: []
}>()

const auth = useAuthUserStore()
const theme = useTheme()
const api = useApi()
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