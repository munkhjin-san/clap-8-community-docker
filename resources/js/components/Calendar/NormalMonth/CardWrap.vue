<template>
<div style="position: relative;"> 
    <div v-if="expanded" :style="{height: fullDay ? '25px' : '59px'}"></div>
    <OnLongPress 
        as="div" 
        :ref="`m_rec_${record.id}`"
        class="month-card-inner" 
        :class="[{'pop-m-card' : expanded}]"
        :style="{
            maxHeight: maxHeight,
            opacity: opacity,
            transform: expanded ? `translate(${shiftRight}px, ${shiftBottom}px)` : `translate(0, 0)`,
            position: expanded ? 'absolute' : 'relative',            
            top: '0'
        }"
        :id="`m_rec_${record.id}`"
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
            @selectRecord="selectRecord"
            ref="sCard"
        />

    </OnLongPress>
</div>
</template>
<script setup>
    import moment from 'moment';
    import { nextTick, ref, computed, inject } from 'vue';
    import { OnLongPress } from '@vueuse/components'
    import CalendarCard from '../CalendarCard.vue';
    import { useAuthUserStore } from '@/store/auth'
    import { useMenuStore } from "@/store/menu";
    import { useResponsive } from '@/store/responsive';
    const responsive = useResponsive()
    const menu = useMenuStore()
    const auth = useAuthUserStore()
    const props = defineProps(['record'])
    const emit = defineEmits(['setParentDroppable'])
    const draggingCalendar = inject('draggingCalendar')
    const shiftRight = ref(0)
    const shiftBottom = ref(0)
    const beforeLeft = ref(0)
      
    const fullDay = computed(() => {
        return Math.abs(moment(props.record.date_start).diff(moment(props.record.date_end), 'hours')) >= 23
    })
    const viewable = computed(() => {
        return props.record.release_flag == 0 || editable.value
    })
    const opacity = computed(() => {
        return ( draggingCalendar.value && draggingCalendar.value.id == props.record.id ) ? '0.5' : '1'
    })
    const editable = computed(() => {
        const me = props.record.calendar_users.filter(ob => ob.id == auth.activeUser.id)
        return (me.length || props.record.edit_all) && props.record.shift == 0
    })
    const maxHeight = computed(() => {
        return expanded.value ? 'unset' : '60px'
    })
    const unique = computed(() => {
        const u = Math.floor(100000 + Math.random() * 900000).toString()
        const r = props.record.id.toString()
        return `cal_${r}_${u}`
    })
    const expanded = computed(() => {
        return menu.parent == unique.value
    })



    const setBeforeState = (event) => {            
        const el = document.getElementById('cal_month_view')
        const left = el ? el.scrollTop : 0
        beforeLeft.value = left          
    }
    const sCard = ref(null)
    const dragStart = (event) => {
        if(editable.value && !expanded.value){
            const el = document.getElementById('cal_month_view')
            const left = el ? el.scrollTop : 0
            if(left !== beforeLeft.value) {
                return
            }
            
            const width = sCard?.value?.$el?.clientWidth
            let record = props.record
            record['width'] = width
            record['x'] = event.x
            record['y'] = event.y
            record['from'] = 'day'
            draggingCalendar.value = record
            menu.setMenu( {id: null, name: ''})
            emit('setParentDroppable')
        }            
    }
    const selectRecord = (record, from) => {
        menu.setMenu( {parent: unique.value})

        
        nextTick(() => {
            const el = document.getElementById(`m_rec_${props.record.id}`)
            if(el){                    
                if(from == 'auto'){
                    el.scrollIntoView({block: 'center', behavior: 'instant'})                        
                }                    
                const rect = el.getBoundingClientRect();                    
                const right_check = rect.x + rect.width
                if(right_check > window.innerWidth){
                    shiftRight.value = window.innerWidth - right_check - 5
                }
                const bottom_check = rect.y + rect.height
                const value = responsive.mobile && auth.user.footer_view ? 45 : 0
                if(bottom_check > window.innerHeight - value){
                    shiftBottom.value = 100 - rect.y
                }
            }
            
        })

    }
    

</script>