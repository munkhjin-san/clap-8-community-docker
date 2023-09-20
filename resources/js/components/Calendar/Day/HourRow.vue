<template>
<div class="hour-slot">
        <!-- <div class="min-slot" v-for="minutes in hour">
            
        </div> -->
    <div>
        <div class="calendar-card" 
            v-for="record in hourRecords"
            :style="{
                width: recordWidth(record), 
                marginTop: `${(record.order * 60) + (record.order + 1) * 10}px`,
                left: recordLeft(record)
            }"
        >
            <div class="calendar-card-inner" :id="`cal_${record.id}`">
                <div style="display: flex;">
                    <UserIcon v-for="user in record.calendar_users.slice(0, 3)" :user="user" imgClass="userSmallIcon" size="15"/>
                </div>
                <div style="white-space: nowrap;">
                    {{ time(record) }}
                </div>
                <div>
                    {{ record.title }}
                </div>
            </div>

        </div>
    </div>
</div>
        
   
</template>
<script>
import moment  from 'moment';
import UserIcon from '../../Board/Mixed/UserIcon.vue';
export default {
    components:{
        UserIcon
    },
    props: ['hourRecords'],
    computed: {
        hour(){
            return [
                { val: 0 },
                { val: 15 },
                { val: 30 },
                { val: 45 }
            ]
        },
    },
    methods:{
        recordWidth(record){
            const minutesDifference = Math.abs(moment(record.date_start).diff(moment(record.date_end), 'minutes'))
            const steps = Math.floor(minutesDifference / 15)
            const until_start = Math.abs(moment(record.date_start).startOf('day').diff(moment(record.date_start), 'minutes'))
            
            const before_limiter = Math.floor(until_start / 15) 
            if(record.id == 6110){
                console.log(until_start, before_limiter)
            }
            const max_block = 96 - before_limiter
            const computed_width = steps > max_block ? max_block : steps
            const unit = this.$store.state.mobile ? '500vw' : '200vw'
            return `calc(((${unit} - 30px) / 96 * ${computed_width}) - 3px)`
        },
        time(record){
            return `${moment(record.date_start).format('H:mm')} ～ ${moment(record.date_end).format('H:mm')}`
        },
        recordLeft(record){
            const diff = Math.abs(moment(record.date_start).diff(moment(record.date_start).startOf('hour'), 'minutes'))
            const steps = Math.floor(diff / 15) 
            const unit = this.$store.state.mobile ? '500vw' : '200vw'
            return `calc(((${unit}  - 30px) / 96 * ${steps}) + 1px)`
        }
    }
}


</script>