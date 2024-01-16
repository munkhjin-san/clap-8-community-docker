<template>
    <div>
        <div v-if="expanded" style="height:25px"></div>
        <div :style="{
            position: expanded ? 'absolute' : 'unset',
            top: 0,
            left: 0,
            maxWidth: expanded ? ($store.state.mobile ? '40vw' : '20vw') : '110px'

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
    import { useStore } from 'vuex'
    const props = defineProps(['record', 'day'])
    const emit = defineEmits('setDayIndex')
    const store = useStore()

    const viewable = computed(() => {
        return props.record.release_flag == 0 || editable.value
    })

    const editable = computed (() => {
        const me = props.record.calendar_users.filter(ob => ob.id == store.state.user.id)
        return (me.length || props.record.edit_all) && props.record.shift == 0
    })

    const expanded = computed(() => {
        return store.state.menu.id == props.record.id && (store.state.menu.name == `cal_${props.record.id}` || store.state.menu.name == `calendarRecordMenu`) 
    })

    const selectRecord = () => {
        store.commit('setMenu', {id: props.record.id, name: `cal_${props.record.id}`})
    }    

</script>