<template>
    <OnLongPress 
        as="div" 
        class="calendar-card" 
        :class="[{'pop-cal-card' : expanded}]"
        :style="{
            minWidth: recordWidth, 
            marginTop: `${(record.order * 60) + ((record.order + 1) * 10) + (fullDayIndex * 35)}px`,
            left: recordLeft,
            maxHeight: maxHeight,
            opacity: opacity,
            transform: expanded ? `translate(${shiftRight}px, ${shiftBottom}px)` : `translate(0, 0)`,
            maxWidth: recordWidth, 
            width: 'max-content',
            minHeight: 'auto',
        }"
        
        :id="`dayRecordMember_${props.record.id}_${props.user.id}`"
        ref="listRecord"
        @dragover.prevent 
        @trigger="dragStart"
        :options="{delay: 400}"
        @mousedown="setBeforeState"
        @touchstart="setBeforeState"
        
    >
        <CalendarCard
            :record="record"
            :viewable="viewable"
            :editable="editable"
            :expanded="expanded"
            :unique-id="unique"
            mode="mini"
            @selectRecord="selectRecord"
        />

    </OnLongPress>
</template>
<script setup lang="ts">
import { computed, inject, nextTick, Ref, ref, useTemplateRef } from 'vue';
import { OnLongPress } from '@vueuse/components'
import CalendarCard from '../CalendarCard.vue';
import { useAuthUserStore } from '@/store/auth'
import { useMenuStore } from "@/store/menu";
import { useResponsive } from '@/store/responsive';
import { DateTime } from 'luxon';
import { CalendarRecord } from '@/interface/calendarInterface';
import { useCalendar } from '@/composables/calendar';
    const menu = useMenuStore()
    const auth = useAuthUserStore()
    const responsive = useResponsive()
    const props = defineProps(['record', 'user', 'fullDayIndex'])
    const emit = defineEmits(['setParentDroppable'])
    const shiftRight = ref(0)
    const shiftBottom = ref(0)
    const beforeState = ref(0)
    const beforeLeft = ref(0)
    const listRecord = useTemplateRef('listRecord')
    const {draggingCalendar, setDraggingCalendar} = useCalendar()
        const viewable = computed(() => {
            return (props.record.release_flag == 0 && props.record.members_only == 0) || editable.value
        })
        const opacity = computed(() => {
            return draggingCalendar.value && draggingCalendar.value.id == props.record.id ? '0.5' : '1'
        })
        const editable = computed(() => {
            const me = props.record.calendar_users.filter(ob => ob.id == auth.activeUser.id)
            return (me.length || props.record.edit_all || canview.value) && props.record.shift == 0
        })
        const canview = computed(() => {
            const me = props.record.calendar_view_users.some(user => 
                user.id === auth.activeUser.id
            );      
            return me && props.record.shift == 0
        })
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
                const minutesDifference = Math.abs(DateTime.fromSQL(props.record.date_start).diff(DateTime.fromSQL(props.record.date_end), 'minutes').minutes)
                const steps = Math.ceil(minutesDifference / 15)
                return `calc(100% / 4 * ${steps} + ${Math.floor(minutesDifference / 60)}px - 3px)`
            }
            
        })
        const recordLeft = computed(() => {
            const diff = Math.abs(DateTime.fromSQL(props.record.date_start).diff(DateTime.fromSQL(props.record.date_start).startOf('hour'), 'minutes').minutes)
            const steps = Math.floor(diff / 15) 
            const unit = responsive.mobile ? '500vw' : '120vw'
            return `calc(((${unit}  - 30px) / 96 * ${steps}) + 1px)`
        })


        const setBeforeState = (event) => {
            
            const el = document.getElementById('cal_list_view')
            const left = el ? el.scrollLeft : 0
            beforeLeft.value = left
            beforeState.value = event.x     
        }
        const dragStart = (event) => {
            if(editable.value && !expanded.value){
                const el = document.getElementById('cal_list_view')
                const left = el ? el.scrollLeft : 0
                if(left !== beforeLeft.value) return
                const width = listRecord?.value?.$el?.clientWidth
                let record = props.record
                record['width'] = width
                record['x'] = event.x
                record['y'] = event.y
                record['from'] = 'month'
                record['active_user_id'] = props.user.id
                if(draggingCalendar)
                setDraggingCalendar(record)
                menu.setMenu( {id: null, name: ''})
                emit('setParentDroppable')
            }            
        }
        const selectRecord = (event, record) => {
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
                        const val = DateTime.fromSQL(props.record.date_start).toJSDate() > DateTime.fromSQL(props.record.date_start).startOf('day').plus({ hours: 2 }).toJSDate() ? 2 : 0
                        const time = DateTime.fromSQL(props.record.date_start).minus({ hours: val }).startOf('hour').hour
                        if(time <= 1){
                            document.getElementById(`cal_list_view`)?.scrollTo({ left: 0, behavior: 'smooth'})
                        }else{
                            const realTime =  DateTime.fromSQL(props.record.date_start).hour
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
                    const value = responsive.mobile && auth?.user?.footer_view ? 45 : 0
                    if(bottom_check > window.innerHeight - value){
                        shiftBottom.value = window.innerHeight - value - bottom_check - 10
                    }
                }
            })

        }
        const calendarDateInstances = computed(() => {
            const start = DateTime.fromSQL(props.record.date_start)
            const end = DateTime.fromSQL(props.record.date_end)
            return {start, end}
        })

</script>