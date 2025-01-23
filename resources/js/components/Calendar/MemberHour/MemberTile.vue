<template>
<div style="display: flex;">
    <div draggable="false" @mousedown.stop @click="emit('viewFull', true)" class="left-member-tile" :style="{ width: hideName ? '45px' : `130px`, overflowX: 'unset'}">
        <div style="cursor: pointer;position: relative;width: -webkit-fill-available;overflow: hidden;">
              
            <UserPanel :disableInstant="hideName" :user="userData.user" imgClass="userMidIcon" size="25"/>
            <div @click.stop="pushInstantUser($event, userData.user.id)" :style="{lineHeight: 1.5, visibility: hideName ? 'hidden' : 'visible'}">{{userData.user.name}}</div>
            
        </div>

    </div>  
    <div :style="{position: 'sticky',zIndex: 2, left: `${hideName ? '45px' : `130px`}`, transition: 'left 0.3s'}">
        <div v-if="fullDayRecords.length" class="hour-full-day-wrap" style="left:0;top: 10px;font-size: 12px;">
            <div style="display: flex;flex-direction: column;gap:10px">
                <div style="position: relative;cursor: pointer;width: fit-content;" v-for="record in fullDayRecords">
                    <AllDayRecord                        
                        :key="record.id"
                        :record="record"
                    />
                </div>               
            </div>            
        </div>  
    </div>
     <HourBlock
        v-for="hour in hoursOfDay"
        :data="hour"
        :fullDayIndex="fullDayRecords.length"
        @create="(date, user) => emit('create', date, user)"
    /> 
</div>
</template>
<script setup>
    import HourBlock from './HourBlock.vue';
    import moment from 'moment';
    import UserPanel from '@/components/Global/UserPanel.vue'
    import AllDayRecord from '../AllDayRecord.vue';
    import { computed, inject } from 'vue';
    import { useAuthUserStore } from '@/store/auth'
    const auth = useAuthUserStore()

    const props = defineProps (['userData', 'hideName', 'orderCreator'])
    const emit = defineEmits(['create', 'viewFull'])
    
    const fullDayRecords = computed(() => {
        return props.userData.records.filter(ob => Math.abs(moment(ob.date_start).diff(moment(ob.date_end), 'hours')) >= 23)  
    })
    const hoursOfDay = computed(() => {
        const hours = [];
        let currentHour = moment().startOf('day');
        for (let i = 0; i < 24; i++) {
            const hourRecords = orderedData.value.filter(ob => moment(ob.date_start).format('H') == currentHour.format('H'))
            hours.push({hour: currentHour.format('H:mm'), records: hourRecords, user: props.userData.user, date: props.userData.date});
            currentHour.add(1, 'hour');
        }
        return hours;
    })
    
    const orderedData = computed(() => {
        const records = props.userData.records.filter(ob => Math.abs(moment(ob.date_start).diff(moment(ob.date_end), 'hours')) < 23)  
        const sortedList = records.slice().sort((a, b) => {
            return new Date(a.date_start) - new Date(b.date_start) ||
            new Date(a.updated_at) - new Date(b.updated_at);
        });         
        const ordered = props.orderCreator(0, sortedList, props.userData.date, props.userData.user.id) 
        return ordered
    })

    const pushInstantUser = inject('pushInstantUser')
    


</script>