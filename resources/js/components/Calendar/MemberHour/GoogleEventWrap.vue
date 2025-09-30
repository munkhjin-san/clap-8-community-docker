<template>
    <div 
        class="calendar-card" 
        :class="[{'pop-cal-card' : expanded}]"
        :style="{
            minWidth: recordWidth, 
            marginTop: `${record.order !== undefined ? ((record.order * 60) + ((record.order + 1) * 10) + (fullDayIndex * 35)) : 0}px`,
            left: recordLeft,
            maxHeight: maxHeight,
            transform: expanded ? `translate(${shiftRight}px, ${shiftBottom}px)` : `translate(0, 0)`,
            maxWidth: recordWidth, 
            width: 'max-content',
            minHeight: 'auto',
        }"
        
        :id="`dayRecordMember_${props.record.id}_${props.user.id}`"
        ref="listRecord"
        @mousedown="setBeforeState"
        @touchstart="setBeforeStateTouch"
        
    >
        <GoogleEventCard
            :record="record"
            :expanded="expanded"
            :unique-id="unique"
            mode="mini"
            @selectRecord="selectRecord"
        />

    </div>
</template>
<script setup lang="ts">
import { DateTime } from 'luxon';
import { ComponentPublicInstance, computed, nextTick, ref } from 'vue';
import { useAuthUserStore } from '@/store/auth'
import { useMenuStore } from "@/store/menu";
import { useResponsive } from '@/store/responsive';
import { GoogleEventItem } from '@/interface/calendarInterface';
import { User } from '@/interface/globalInterface';
import GoogleEventCard from '../GoogleEventCard.vue';
    const menu = useMenuStore()
    const auth = useAuthUserStore()
    const responsive = useResponsive()
    const props = defineProps<{
        record: GoogleEventItem
        user: User
        fullDayIndex: number
        offset: number
    }>()
    const emit = defineEmits(['setParentDroppable'])
    const shiftRight = ref(0)
    const shiftBottom = ref(0)
    const beforeState = ref(0)
    const beforeLeft = ref(0)
    const listRecord = ref<ComponentPublicInstance | null>(null)
    const maxHeight = computed(() => {
        return expanded.value ? '100vh' : '60px'
    })
    const unique = computed(() => {
        const u = Math.floor(100000 + Math.random() * 900000).toString()
        const r = props.record.id.toString()
        return `cal_${r}_${u}`
    })
    const expanded = computed(() => {
        return menu.parent == unique.value
    })
    const recordWidth = computed(() => {
        if(expanded.value){
            return '250%'
        }else{
            const minutesDifference = Math.abs(DateTime.fromFormat(`${props.record.start_date} ${props.record.start_time}`, 'yyyy-MM-dd HH:mm').diff(DateTime.fromFormat(`${props.record.end_date} ${props.record.end_time}`, 'yyyy-MM-dd HH:mm'), 'minutes').minutes)
            const steps = Math.ceil(minutesDifference / 15)
            return `calc(100% / 4 * ${steps} + ${Math.floor(minutesDifference / 60)}px - 3px)`
        }
        
    })
    const recordLeft = computed(() => {
        const diff = Math.abs(DateTime.fromFormat(`${props.record.start_date} ${props.record.start_time}`, 'yyyy-MM-dd HH:mm').diff(DateTime.fromFormat(`${props.record.start_date} ${props.record.start_time}`, 'yyyy-MM-dd HH:mm').startOf('hour'), 'minutes').minutes)
        const steps = Math.floor(diff / 15) 
        const unit = responsive.mobile ? '500vw' : '120vw'
        return `calc(((${unit}  - 30px) / 96 * ${steps}) + 1px)`
    })


    const setBeforeState = (event:MouseEvent) => {
        
        const el = document.getElementById('cal_list_view')
        const left = el ? el.scrollLeft : 0
        beforeLeft.value = left
        beforeState.value = event.x     
    }

    const setBeforeStateTouch = (event:TouchEvent) => {
        const el = document.getElementById('cal_list_view')
        const left = el ? el.scrollLeft : 0
        beforeLeft.value = left
        if (event.touches && event.touches[0]) {
            beforeState.value = event.touches[0].clientX
        }
    }
    const selectRecord = (event:MouseEvent) => {
        if(event && Math.abs(event.x - beforeState.value) > 15) {
            return
        }
        menu.setMenu( {parent: unique.value})

        nextTick(() => {
            const el = document.getElementById(`dayRecordMember_${props.record.id}_${props.user.id}`)
            if(el){
                const rect = el.getBoundingClientRect();                   
                const compare_value = document.getElementById('listViewSpacer')?.clientWidth || 110
                const final = compare_value && responsive.mobile ? 0 : 60                    
                if(rect.x < (compare_value + final)){
                    const val = DateTime.fromFormat(`${props.record.start_date} ${props.record.start_time}`, 'yyyy-MM-dd HH:mm').toJSDate() > DateTime.fromFormat(`${props.record.start_date} ${props.record.start_time}`, 'yyyy-MM-dd HH:mm').startOf('day').plus({ hours: 2 }).toJSDate() ? 2 : 0
                    const time = DateTime.fromFormat(`${props.record.start_date} ${props.record.start_time}`, 'yyyy-MM-dd HH:mm').minus({ hours: val }).startOf('hour').hour
                    if(time <= 1){
                        document.getElementById(`cal_list_view`)?.scrollTo({ left: 0, behavior: 'smooth'})
                    }else{
                        // document.getElementById(`w_day_${time}`)?.scrollIntoView({behavior: 'smooth'})
                        const realTime =  DateTime.fromFormat(`${props.record.start_date} ${props.record.start_time}`, 'yyyy-MM-dd HH:mm').hour
                        const index = ( el.parentElement ? el.parentElement.clientWidth : 0) * realTime
                        document.getElementById(`cal_list_view`)?.scrollTo({ left: index, behavior: 'smooth'})
                    }                       

                }else{
                    const right_check = rect.x + rect.width
                    if(right_check > window.innerWidth){
                        shiftRight.value = window.innerWidth - right_check - 10
                    }
                }
                const bottom_check = rect.y + rect.height
                const value = responsive.mobile && auth.user?.footer_view ? 45 : 0
                if(bottom_check > window.innerHeight - value){
                    shiftBottom.value = window.innerHeight - value - bottom_check - 10
                }
            }
        })

    }
    

</script>