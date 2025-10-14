<template>
    <div 
        :ref="`dayRecord_${record.id}`" 
        class="calendar-card-inner" 
        :class="[{'highlightedCalendar' : expanded}]"
        @click.stop="openOrClose"       
        :style="{ 
            background: record.color, 
            color: record.textColor,            
        }"
    >
        
        <div style="display: flex;">
            <div class="flex items-center text-[12px] gap-[5px]" :title="record.summary">
                <img v-if="record.user_info.avatar_url" :src="record.user_info.avatar_url" alt="User Avatar" class="w-[15px] h-[15px] rounded-full"/>
                <p class="whitespace-nowrap">{{ record.summary || 'タイトルはありません。' }}</p>
            </div>
        </div>
        <div v-if="!expanded && !fullDay" class="cal-card-item text-[12px]" style="white-space: nowrap;">{{ time }}</div>
        <div @click="expanded ? $event.stopPropagation() : false" @mousedown.stop v-if="expanded" :class="['cal-card-item text-[12px]', {'wrap cal-selectable' : expanded }]" style="line-height:1.5;margin: 10px 0;display: flex;gap: 10px;align-items: center;">                
            <div v-html="timeDetailed"></div>
        </div> 
        <div v-if="expanded" class="text-[12px] w-fit max-w-[100%] whitespace-pre-line leading-normal">
            {{ record.description }}
        </div>       

             
    </div>
</template>
<script setup lang="ts">
import { ref, computed, onMounted, inject, Ref } from 'vue'
import colors from 'assets/colors.json'
import { useAuthUserStore } from '@/store/auth'
import { useMenuStore } from "@/store/menu";
import { useTheme } from '@/store/theme';
import { useTempRecord } from '@/store/tempRecord';
import { GoogleEventItem } from '@/interface/calendarInterface';
import { DateTime, Interval } from 'luxon';
    const menu = useMenuStore()
    const props = defineProps<{
        record: GoogleEventItem;
        expanded: boolean;
        day: string;
    }>()

    const facilityQuery = inject<Ref<number[]>>('facilityQuery')
    const emit = defineEmits(['selectRecord'])







    const time = computed(() => {
        return fullDay.value ? '終日' : `${props.record.start_time} ~ ${props.record.end_time}`
    })


    const timeDetailed = computed(() => {
        if(fullDay.value){
            return '終日'
        }else if(props.record.start_time && props.record.end_time){
            return `${DateTime.fromFormat(`${props.record.start_date} ${props.record.start_time}`, 'yyyy-MM-dd HH:mm').toFormat('y/M/d (ccc) HH:mm')} ~ ${DateTime.fromFormat(`${props.record.end_date} ${props.record.end_time}`, 'yyyy-MM-dd HH:mm').toFormat('H:mm')}`
        }else{
            return `${DateTime.fromFormat(`${props.record.start_date}`, 'yyyy-MM-dd').toFormat('y/M/d (ccc)')} ~ ${DateTime.fromFormat(`${props.record.end_date}`, 'yyyy-MM-dd').toFormat('y/M/d (ccc)')}`
        }
    })

    const fullDay = computed(() => {
        return props.record.all_day
    })


    const openOrClose = (event:Event) => {
        if(menu.parent === `dayRecord_${props.record.id}_${props.day}`){
            menu.close()
        }else{
            emit('selectRecord', event)
        }
        
    }

</script>
