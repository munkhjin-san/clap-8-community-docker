<template>

        <div style="position: relative;">
            <div v-if="expanded" :style="{height: fullDay ? '25px' : '59px'}"></div>
            <OnLongPress 
                :id="`w_rec_${record.id}_${user.id}`" 
                :class="['cal-w-wrap',{'pop-w-card' : expanded}]" 
                :style="{transform: expanded ? `translate(${shiftRight}px, ${shiftBottom}px)` : `translate(0, 0)`,opacity: opacity,}"
                @dragover.prevent 
                @trigger="dragStart($event, record)"
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
                    ref="weekRecord"
                    @selectRecord="(event, val, from) => selectRecord(event, val, from, user)"
                />
            </OnLongPress>
           
        </div>
        

</template>
<script setup>
import CalendarCard from '../CalendarCard.vue';
import { computed, inject, nextTick, ref } from 'vue';
import moment from 'moment';
import { OnLongPress } from '@vueuse/components'
import { useAuthUserStore } from '@/store/auth'
import { useMenuStore } from "@/store/menu";
import { useResponsive } from '@/store/responsive';
    const menu = useMenuStore()
    const auth = useAuthUserStore()
    const responsive = useResponsive()
    const props = defineProps(['record', 'user'])
 
    const shiftRight = ref(0)
    const shiftBottom = ref(0)
    const beforeLeft = ref(0)
    const weekRecord = ref(null)
    const draggingCalendar = inject('draggingCalendar')
        const opacity = computed(() => {
            return ( draggingCalendar.value && draggingCalendar.value.id == props.record.id ) ? '0.5' : '1'
        })

        const fullDay = computed(() => {
            return Math.abs(moment(props.record.date_start).diff(moment(props.record.date_end), 'hours')) >= 23
        })   
        const viewable = computed(() => {
            return (props.record.release_flag == 0 && props.record.members_only == 0) || editable.value
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
            
            if(editable.value && !expanded.value){
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
                draggingCalendar.value = rec
                menu.setMenu( {id: null, name: ''})
                
            }            
        }

        const selectRecord = (event, record, from, user) => {
            
            menu.setMenu( {parent: unique.value})
            
            nextTick(() => {
                const el = document.getElementById(`w_rec_${record.id}_${user.id}`)
                if(el){
                    if(from == 'auto'){                        
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
                    const value = responsive.mobile && auth.user.footer_view ? 45 : 0
                    if(bottom_check > window.innerHeight - value){
                        shiftBottom.value = window.innerHeight - value - bottom_check - 10
                    }
                }
                
            })
         
        
        }
</script>