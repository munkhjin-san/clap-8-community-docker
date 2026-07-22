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
<script setup lang="ts">
import { nextTick, ref, computed, useTemplateRef } from 'vue';
import { OnLongPress } from '@vueuse/components'
import CalendarCard from '../CalendarCard.vue';
import { useAuthUserStore } from '@/store/auth'
import { useMenuStore } from "@/store/menu";
import { useResponsive } from '@/store/responsive';
import { DateTime } from 'luxon';
import { useCalendar } from '@/composables/calendar';
import { CalendarGroupUser } from '@/interface/calendarInterface';
    const responsive = useResponsive()
    const menu = useMenuStore()
    const auth = useAuthUserStore()
    const props = defineProps(['record'])
    const emit = defineEmits(['setParentDroppable'])
    const {draggingCalendar, setDraggingCalendar} = useCalendar()
    const shiftRight = ref(0)
    const shiftBottom = ref(0)
    const beforeLeft = ref(0)
      
    const fullDay = computed(() => {
        return Math.abs(DateTime.fromISO(props.record.date_start).diff(DateTime.fromISO(props.record.date_start), 'hours').hours) >= 23;
    })
    const viewable = computed(() => {
        return (props.record.release_flag == 0 && props.record.members_only == 0) || editable.value
    })
    const opacity = computed(() => {
        return ( draggingCalendar.value && draggingCalendar.value.id == props.record.id ) ? '0.5' : '1'
    })
    const editable = computed(() => {
        const me = props.record.calendar_users.filter((ob: CalendarGroupUser) => ob.id == auth.activeUser.id)
        return (me.length || props.record.edit_all || canview.value) && props.record.shift == 0
    })
    const canview = computed(() => {
        const me = props.record.calendar_view_users.some((user: CalendarGroupUser) => user.id === auth.activeUser.id)   
        return me && props.record.shift == 0
    })
    const maxHeight = computed(() => {
        return expanded.value ? '100vh' : '60px'
    })
    const u = Math.floor(100000 + Math.random() * 900000).toString()
    const unique = computed(() => {
        const r = props.record.id.toString()
        return `cal_${r}_${u}`
    })
    const expanded = computed(() => {
        return menu.parent == unique.value
    })



    const setBeforeState = () => {            
        const el = document.getElementById('cal_month_view')
        const left = el ? el.scrollTop : 0
        beforeLeft.value = left          
    }
    const sCard = useTemplateRef('sCard')
    const dragStart = (event: MouseEvent) => {
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
            if(draggingCalendar)
            setDraggingCalendar(record)
            menu.setMenu( {id: null, name: ''})
            emit('setParentDroppable')
        }            
    }
    const M = 8;

    const resetAndMeasure = async (el: HTMLElement) => {
        shiftRight.value = 0;
        shiftBottom.value = 0;

        await nextTick();
        await new Promise(r => requestAnimationFrame(r));

        // 3) measure and compute dy
        const parent = document.getElementById('cal_month_view') as HTMLElement;
        const pr = parent.getBoundingClientRect();
        const r  = el.getBoundingClientRect();

        const footer = (responsive.mobile && auth.user?.footer_view) ? 45 : 0;
        const bottomLimit = Math.min(pr.bottom, window.innerHeight - footer) - M;
        const topLimit    = pr.top + M + 40;
        const right_check = r.x + r.width
        if(right_check > window.innerWidth){
            shiftRight.value = window.innerWidth - right_check - 5
        }
        let dy = 0;
        const overflowBottom = r.bottom - bottomLimit;
        if (overflowBottom > 0) dy -= overflowBottom;           
        if (r.top + dy < topLimit) dy = topLimit - r.top;       

        shiftBottom.value = dy;                                 
    };

    const selectRecord = async (event: Event) => {
        menu.setMenu({ parent: unique.value });

        await nextTick();                                      
        const el = document.getElementById(`m_rec_${props.record.id}`);
        if (!el) return;

        await resetAndMeasure(el);
    };    

</script>