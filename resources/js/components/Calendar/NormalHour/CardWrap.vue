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
            maxWidth: expanded ? '400%' : recordWidth, 
            width: 'max-content'
        }"
        :id="`dayRecord_${record.id}`"
        @dragover.prevent 
        @trigger="dragStart"
        :options="{delay: 400}"
        @mousedown="setBeforeState"
        @touchstart="setBeforeState"
        ref="dayRecord"
        
    >
        <CalendarCard
            :record="record"
            :viewable="viewable"
            :editable="editable"
            :expanded="expanded"
            :unique-id="unique"
            @selectRecord="selectRecord"
        />

    </OnLongPress>
</template>
<script setup>
import moment from 'moment';
import { computed, inject, nextTick, onMounted, ref } from 'vue';
import { OnLongPress } from '@vueuse/components'
import CalendarCard from '../CalendarCard.vue';
import { useAuthUserStore } from '@/store/auth'
import { useMenuStore } from "@/store/menu";
import { useResponsive } from '@/store/responsive';
import { useTempRecord } from '@/store/tempRecord';
    const menu = useMenuStore()
    const auth = useAuthUserStore()
    const responsive = useResponsive()
    const tempRecord = useTempRecord()
    const props = defineProps(['record', 'fullDayIndex'])
    const emit = defineEmits(['setParentDroppable', 'setDayIndex'])
    const draggingCalendar = inject('draggingCalendar')
    const shiftRight = ref(0)
    const shiftBottom = ref(0)
    const beforeState = ref(0)
    const beforeLeft = ref(0)
    const dayRecord = ref(null)
    onMounted(() => {
        // if(tempRecord.id && tempRecord.id == props.record.id){   
        //     menu.setMenu( {id: props.record.id, name: `cal_${props.record.id}`})  
        // }
    })

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
            return '200%'
        }else{
            const minutesDifference = Math.abs(moment(props.record.date_start).diff(moment(props.record.date_end), 'minutes'))
            const steps = Math.ceil(minutesDifference / 15)
            const until_start = Math.abs(moment(props.record.date_start).startOf('day').diff(moment(props.record.date_start), 'minutes'))                
            const before_limiter = Math.ceil(until_start / 15) 
            const max_block = 96 - before_limiter
            const computed_width = steps > max_block ? max_block : steps
            const unit = responsive.mobile ? '500vw' : '120vw'
            return `calc(((${unit} - 30px) / 96 * ${computed_width}) - 3px)`
        }        
    })
    const recordLeft = computed(() => {
        const diff = Math.abs(moment(props.record.date_start).diff(moment(props.record.date_start).startOf('hour'), 'minutes'))
        const steps = Math.floor(diff / 15) 
        const unit = responsive.mobile ? '500vw' : '120vw'
        return `calc(((${unit} - 30px) / 96 * ${steps}) + 1px)`
    })

    const setBeforeState = (event) => {
        
        const el = document.getElementById('cal_day_view')
        const left = el ? el.scrollLeft : 0
        beforeLeft.value = left
        beforeState.value = event.x     
    }
    const dragStart = (event) => {
        if(editable.value && !expanded.value){
            const el = document.getElementById('cal_day_view')
            const left = el ? el.scrollLeft : 0
            if(left !== beforeLeft.value) return
            const width = dayRecord?.value?.$el?.clientWidth
            let record = props.record
            record['width'] = width
            record['x'] = event.x
            record['y'] = event.y
            record['from'] = 'month'
            draggingCalendar.value = record
            menu.setMenu( {id: null, name: ''})
            emit('setParentDroppable')
        }            
    }
    const selectRecord =(event, record) => {
        if(event && Math.abs( event.x - beforeState.value) > 15) {
            return
        }
        menu.setMenu( {parent: unique.value})
        nextTick(() => {
            const el = document.getElementById(`dayRecord_${props.record.id}`)
            if(el){
                const rect = el.getBoundingClientRect();
                const compare_value = responsive.mobile ? 30 : 80
                if(rect.x < compare_value){
                    const val = moment(record.date_start).isAfter(moment(record.date_start).startOf('day').add(1, 'hour')) ? 1 : 0
                    const time = moment(record.date_start).subtract(val, 'hour').startOf('hour').hour()
                    
                    if(time <= 1){
                        document.getElementById(`cal_day_view`)?.scrollTo({ left: 0, behavior: 'smooth'})
                    }else{
                        document.getElementById(`d_day_${time}`)?.scrollIntoView({behavior: 'smooth'})
                    }

                }else{
                    const right_check = rect.x + rect.width
                    if(right_check > window.innerWidth){
                        shiftRight.value = window.innerWidth - right_check - 10
                    }
                }
                const bottom_check = rect.y + rect.height
                const value = responsive.mobile && auth.user.footer_view ? 45 : 0
                if(bottom_check > window.innerHeight - value){
                    shiftBottom.value = window.innerHeight - value - bottom_check - 10
                }
            }
        })

    }
    
</script>