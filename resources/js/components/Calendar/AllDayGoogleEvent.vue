<template>
    <div>
        <div v-if="expanded" style="height:25px"></div>
        <div :style="{
            position: expanded ? 'absolute' : 'unset',
            top: 0,
            left: 0,
            maxWidth: expanded ? (responsive.mobile ? '40vw' : '20vw') : '110px'

        }"
        :class="[{'pop-cal-card' : expanded}]">
            <GoogleEventCard
                :day="day.full"
                :record="record"
                :expanded="expanded"    
                :unique-id="unique"
                @selectRecord="selectRecord"
            />                
        </div>
    </div>
</template>
<script setup lang="ts">
import { computed } from 'vue'
import { useAuthUserStore } from '@/store/auth'
import { useMenuStore } from "@/store/menu";
import { useResponsive } from '@/store/responsive';
import GoogleEventCard from './GoogleEventCard.vue';
import { GoogleEventItem, NormalHourDay } from '@/interface/calendarInterface';
    const menu = useMenuStore()
    const auth = useAuthUserStore()
    const responsive = useResponsive()
    // const props = defineProps(['record', 'day'])
    const props = defineProps<{
        record: GoogleEventItem, 
        day: NormalHourDay
    }>()




    const expanded = computed(() => {
        return menu.parent == unique.value
    })
    const unique = computed(() => {
        const u = Math.floor(100000 + Math.random() * 900000).toString()
        const r = props.record.id.toString()
        const d = props.day.full.replace(/-/g, '')
        return `cal_${r}_${d}_${u}`
    })
    const selectRecord = (_event:Event) => {
        menu.setMenu( {parent: unique.value})
    }    

</script>