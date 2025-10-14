<template>
    <div 
        class="g-event-container text-[black] flex flex-col gap-[5px] relative" 
        :id="`g_rec${googleEvent.id}_${day}`"
        :style="{
            minHeight: googleEvent.all_day ? '25px' : '42px',
        }"

    >
        <div
            :class="[{'pop-m-card' : expanded}]"
            :style="{
                transform: expanded ? `translate(${shiftRight}px, ${shiftBottom}px)` : `translate(0, 0)`,
                position: expanded ? 'absolute' : 'relative',            
                top: '0',
                backgroundColor: googleEvent.color || '#e0e0e0', 
                color: googleEvent.textColor || '#000000',
            }"
        >
            <GoogleEventCard 
                :record="googleEvent" 
                :expanded="expanded"
                :day="day"
                @select-record="selectRecord"
            />
        </div>
        
    </div>
</template>
<script setup lang="ts">
import { GoogleEventItem } from '@/interface/calendarInterface';
import GoogleEventCard from '../GoogleEventCard.vue';
import { computed, nextTick, ref } from 'vue';
import { useMenuStore } from '@/store/menu';
import { useResponsive } from '@/store/responsive';
import { useAuthUserStore } from '@/store/auth';
const props = defineProps<{
    googleEvent: GoogleEventItem;
    day: string;
}>()

const menu = useMenuStore()

const expanded = computed(() => {
    return menu.parent == `dayRecord_${props.googleEvent.id}_${props.day}`
})
const shiftRight = ref(0)
const shiftBottom = ref(0)
const responsive = useResponsive()
const auth = useAuthUserStore()
const selectRecord = (event:Event) => {
    menu.setMenu({parent: `dayRecord_${props.googleEvent.id}_${props.day}`})
    
    nextTick(() => {
        const el = document.getElementById(`g_rec${props.googleEvent.id}_${props.day}`)
        if(el){                    
            if(!event){
                el.scrollIntoView({block: 'center', behavior: 'instant'})                        
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
<style scoped>
</style>