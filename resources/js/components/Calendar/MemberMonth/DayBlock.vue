<template>
    <div @click.stop="createAtTime" class="w-day-item"  @mouseenter="enter" @mouseleave="leave" style="position: relative;">
        <div :title="`新規作成\n${user.name}\n${day.day_full}`" @click.stop="emit('addRecord', 'day', day.day_full, user)" class="m-record-add pc" style="margin-left: auto;">
            <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 32 32" fill="#9b9b9b">
                <path d="M30.044 14.14c-2.402-0.231-4.804-0.341-7.206-0.422-1.535-0.058-3.071-0.079-4.606-0.090-0.326-0.002-0.587-0.265-0.588-0.591-0.004-1.537-0.018-3.074-0.078-4.613-0.092-2.4-0.218-4.802-0.542-7.205-0.084-0.612-0.565-1.119-1.205-1.206-0.769-0.103-1.477 0.437-1.582 1.206-0.324 2.401-0.449 4.804-0.542 7.205-0.059 1.536-0.074 3.071-0.078 4.606-0.001 0.325-0.263 0.59-0.59 0.59-1.534 0.005-3.068 0.020-4.602 0.078-2.404 0.094-4.805 0.219-7.207 0.543-0.612 0.081-1.119 0.564-1.205 1.205-0.103 0.769 0.436 1.477 1.205 1.58 2.402 0.324 4.804 0.449 7.207 0.543 1.536 0.059 3.074 0.073 4.612 0.078 0.325 0.001 0.587 0.262 0.59 0.587 0.011 1.536 0.033 3.070 0.090 4.606 0.080 2.402 0.192 4.805 0.423 7.207 0.066 0.699 0.622 1.278 1.349 1.348 0.823 0.079 1.556-0.524 1.633-1.348 0.231-2.402 0.342-4.805 0.423-7.207 0.057-1.538 0.079-3.077 0.090-4.615 0.002-0.324 0.263-0.583 0.587-0.586 1.538-0.011 3.077-0.034 4.615-0.090 2.402-0.080 4.804-0.193 7.206-0.423 0.7-0.066 1.279-0.622 1.349-1.349 0.076-0.823-0.528-1.557-1.351-1.634z"></path>
            </svg>
        </div>
        <Transition name="modalFade">
            <div v-if="dragActive && draggingCalendar && draggingCalendar.active_user_id == user.id" @mouseup="gotMove()" class="month-drop-popup"></div>
        </Transition>
        <MemberRecords
            :dayRecords="dayRecordsAfter" 
            :user="user"
            :google-events="dayGoogleEvents"
        />
    </div>
</template>
<script setup lang="ts">
import MemberRecords from './MemberRecords.vue';
import { computed, inject, ref } from 'vue';
import { DateTime } from 'luxon';
import { useCalendar } from '@/composables/calendar';
import { User } from '@/interface/globalInterface';
import { GoogleEventItem, MemberMonthDay } from '@/interface/calendarInterface';
    const props = defineProps<{
        day: MemberMonthDay
        user: User
        beforeState: number
        googleEvents: GoogleEventItem[]
    }>()
    const emit = defineEmits(['addRecord', 'create'])
    const {draggingCalendar, setDraggingCalendar} = useCalendar()
    const dragActive = ref(false)
    const dropFinish = inject<Function>('dropFinish') as Function

    const dayRecordsAfter = computed(() => {
        const user_records = props.day.records.filter(ob =>  ob.calendar_users.map(item => item.id).includes(props.user.id)).sort((a, b) => {
            return new Date(a.date_start).getTime() - new Date(b.date_start).getTime();
        }); 
        return user_records
    })
    const dayGoogleEvents = computed(() => {
        return props.googleEvents.filter((ob: GoogleEventItem) => {
            const startDate = DateTime.fromISO(ob.start_date);
            const endDate = ob.end_date ? DateTime.fromISO(ob.end_date) : startDate;
            const dayDate = DateTime.fromISO(props.day.day_full);
            return (dayDate >= startDate && dayDate <= endDate);        
        })
    })
    const gotMove = () => {
        if( draggingCalendar.value){
            const record = draggingCalendar.value
            setDraggingCalendar(null)
            const record_date = DateTime.fromSQL(record.date_start)
            const date = props.day.day_full
            const merge = DateTime.fromFormat(date, 'yyyy-MM-dd')
                .set({ hour: record_date.hour, minute: record_date.minute, second: 0 })
                .toSQL();

            console.log(merge)
            dragActive.value = false
            if(dropFinish){
                dropFinish(record, merge)
            }
        }
        
        
    }
    const enter = () => {
        if(draggingCalendar.value){
            if(!DateTime.fromFormat(props.day.day_full, 'yyyy-MM-dd').equals(DateTime.fromFormat(draggingCalendar.value.date_start, 'yyyy-MM-dd'))){
                dragActive.value = true
            }                    
        }
    }
    const leave = () => {
        dragActive.value = false
    }
    const createAtTime = (event: MouseEvent) => {
        if(Math.abs(event.pageX - props.beforeState) > 15) {
            return
        }       
        const date = props.day.day_full
        const time = DateTime.now().plus({ hours: 1 }).startOf('hour').toFormat('HH:mm:ss')
        const merge = `${date} ${time}`
        const d = {
            x: event.x,
            y: event.y,
            time: merge,
            stamp: DateTime.now().toISO()
        }
        emit('create', d, props.user)        
    }
    


</script>