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
            <CalendarCard
                :record="record"
                :viewable="viewable"
                :editable="editable"
                :expanded="expanded"
                :unique-id="unique"
                @selectRecord="selectRecord"
            />                
        </div>
    </div>
</template>
<script setup>
import CalendarCard from './CalendarCard.vue';
import { computed } from 'vue'
import { useAuthUserStore } from '@/store/auth'
import { useMenuStore } from "@/store/menu";
import { useResponsive } from '@/store/responsive';
    const menu = useMenuStore()
    const auth = useAuthUserStore()
    const responsive = useResponsive()
    const props = defineProps(['record', 'day'])
    const emit = defineEmits('setDayIndex')

    const viewable = computed(() => {
        return (props.record.release_flag == 0 && props.record.members_only == 0) || editable.value
    })
    
    const editable = computed (() => {
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

    const selectRecord = () => {
        menu.setMenu( {parent: unique.value})
    }    

</script>