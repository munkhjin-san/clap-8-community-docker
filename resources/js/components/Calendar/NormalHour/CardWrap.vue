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
            @selectRecord="selectRecord"
        />

    </OnLongPress>
</template>
<script setup>
import moment from 'moment';
import { computed, nextTick, onMounted, ref } from 'vue';
import { OnLongPress } from '@vueuse/components'
import CalendarCard from '../CalendarCard.vue';
import { useStore } from 'vuex';
    const store = useStore()
    const props = defineProps(['record', 'fullDayIndex'])
    const emit = defineEmits(['setParentDroppable', 'setDayIndex'])

    const shiftRight = ref(0)
    const shiftBottom = ref(0)
    const beforeState = ref(0)
    const beforeLeft = ref(0)
    const dayRecord = ref(null)
    onMounted(() => {
        if(store.state.tempRecord && store.state.tempRecord == props.record.id){   
            store.commit('setMenu', {id: props.record.id, name: `cal_${props.record.id}`})
            nextTick(() => {
                document.getElementById(`dayRecord_${props.record.id}`)?.scrollIntoView({block: 'center', inline: 'center'})
                console.log('jumpfromday')
            })           
        }
    })

    const viewable = computed(() => {
        return props.record.release_flag == 0 || editable.value
    })
    const opacity = computed(() => {
        return store.state.draggingCalendar && store.state.draggingCalendar.id == props.record.id ? '0.5' : '1'
    })
    const editable = computed(() => {
        const me = props.record.calendar_users.filter(ob => ob.id == store.state.user.id)
        return (me.length || props.record.edit_all) && props.record.shift == 0
    })
    const maxHeight = computed(() => {
        return expanded.value ? '100vh' : '60px'
    })
    const expanded = computed(() => {
        return store.state.menu.id == props.record.id && (store.state.menu.name == `cal_${props.record.id}` || store.state.menu.name == `calendarRecordMenu`) 
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
            const unit = store.state.mobile ? '500vw' : '120vw'
            return `calc(((${unit} - 30px) / 96 * ${computed_width}) - 3px)`
        }        
    })
    const recordLeft = computed(() => {
        const diff = Math.abs(moment(props.record.date_start).diff(moment(props.record.date_start).startOf('hour'), 'minutes'))
        const steps = Math.floor(diff / 15) 
        const unit = store.state.mobile ? '500vw' : '120vw'
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
            store.commit('setDraggingCalendar', record)
            store.commit('setMenu', {id: null, name: ''})
            emit('setParentDroppable')
        }            
    }
    const selectRecord =(record) => {
        if(Math.abs( event.x - beforeState.value) > 15) {
            return
        }
        store.commit('setMenu', {id: props.record.id, name: `cal_${props.record.id}`})
        nextTick(() => {
            const el = document.getElementById(`dayRecord_${props.record.id}`)
            if(el){
                const rect = el.getBoundingClientRect();
                const compare_value = store.state.mobile ? 30 : 80
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
                const value = store.state.mobile && store.state.user.footer_view ? 45 : 0
                if(bottom_check > window.innerHeight - value){
                    shiftBottom.value = window.innerHeight - value - bottom_check - 10
                }
            }
        })

    }
    
</script>