<template>
    <div style="position: relative;">
        <div v-if="expanded" :style="{height: record.all_day ? '25px' : '59px'}"></div>
            <div 
                :id="`w_rec_${record.id}_${user.id}`" 
                :class="['cal-w-wrap',{'pop-w-card' : expanded}]" 
                :style="{transform: expanded ? `translate(${shiftRight}px, ${shiftBottom}px)` : `translate(0, 0)`}"
                @dragover.prevent 
                @trigger="dragStart($event, record)"
                :options="{delay: 400}"
                @mousedown="setBeforeState"
                @touchstart="setBeforeState"
            > 
                <GoogleEventCard
                    :record="record"
                    :expanded="expanded"
                    :unique-id="unique"
                    ref="weekRecord"
                    @selectRecord="selectRecord"
                />
        </div>        
    </div>       

</template>
<script setup lang="ts">
import CalendarCard from '../CalendarCard.vue';
import { Ref, computed, inject, nextTick, ref } from 'vue';
import { DateTime } from 'luxon';
import { useAuthUserStore } from '@/store/auth'
import { useMenuStore } from "@/store/menu";
import { useResponsive } from '@/store/responsive';
import { CalendarRecord, GoogleEventItem } from '@/interface/calendarInterface';
import GoogleEventCard from '../GoogleEventCard.vue';
import { User } from '@/interface/globalInterface';
    const menu = useMenuStore()
    const auth = useAuthUserStore()
    const responsive = useResponsive()
    const props = defineProps<{
        record: GoogleEventItem;
        user: User
    }>()
    const shiftRight = ref(0)
    const shiftBottom = ref(0)
    const beforeLeft = ref(0)
    const weekRecord = ref<InstanceType<typeof CalendarCard> | null>(null)
    const draggingCalendar = inject<Ref<CalendarRecord | null>>('draggingCalendar')   

    const unique = computed(() => {
        const u = Math.floor(100000 + Math.random() * 900000).toString()
        const r = props.record.id.toString()
        return `cal_${r}_${u}`
    })
    const expanded = computed(() => {
        return menu.parent == unique.value
    })
    const setBeforeState = (event) => {
        
        const el = document.getElementById('cal_week_view')
        const left = el ? el.scrollLeft : 0
        beforeLeft.value = left          
    }
    const dragStart = (event, record) => {
        
        if(!expanded.value ){
            const el = document.getElementById('cal_week_view')
            const left = el ? el.scrollLeft : 0
            if(left !== beforeLeft.value) {
                return
            }
            const width = weekRecord?.value?.$el?.clientWidth
            let rec = props.record
            rec['width'] = width
            rec['x'] = event.x
            rec['y'] = event.y
            rec['from'] = 'day'
            rec['active_user_id'] = props.user.id
            menu.setMenu( {id: null, name: ''})
            
        }            
    }

    const selectRecord = (event:Event) => {
        
        menu.setMenu( {parent: unique.value})
        
        nextTick(() => {
            const el = document.getElementById(`w_rec_${props.record.id}_${props.user.id}`)
            if(el){
                if(!event){                        
                    el.scrollIntoView({block: 'nearest', behavior: 'instant'})      
                    const spacer = document.getElementById('weekSpacer')?.clientWidth || 130
                    const pre_rect = el.getBoundingClientRect();                        
                    if(pre_rect.x < spacer){
                        const scrollable = document.getElementById('cal_week_view')
                        scrollable?.scrollBy(0 - spacer, 0)
                    }                                             
                }  
                const rect = el.getBoundingClientRect();                    
                const right_check = rect.x + rect.width
                if(right_check > window.innerWidth){
                    shiftRight.value = window.innerWidth - right_check - 5
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