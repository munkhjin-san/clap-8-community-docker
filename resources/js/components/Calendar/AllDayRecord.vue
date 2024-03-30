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
        return props.record.release_flag == 0 || editable.value
    })

    const editable = computed (() => {
        const me = props.record.calendar_users.filter(ob => ob.id == auth.activeUser.id)
        return (me.length || props.record.edit_all) && props.record.shift == 0
    })

    const expanded = computed(() => {
        return menu.id == props.record.id && (menu.name == `cal_${props.record.id}` || menu.name == `calendarRecordMenu`) 
    })

    const selectRecord = () => {
        menu.setMenu( {id: props.record.id, name: `cal_${props.record.id}`})
    }    

</script>