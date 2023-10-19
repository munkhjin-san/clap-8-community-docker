<template>
<div @mousedown="setBeforeState" @touchstart="setBeforeState" @click.self.stop="createAtTime" class="hour-slot" @mouseenter="enter" @mouseleave="leave">
    <DayRecord 
        v-for="record in hourRecords"
        :record="record"
        :key="record.id"
        :colors="colors"
        :facilitiesList="facilitiesList"
        @scrollToTime="val => $emit('scrollToTime', val)"
        @edit="val => $emit('edit', val)"
        @delete="val => $emit('delete', val)"
        @setParentDroppable="dragActive = true"
    />
    <div v-if="dragActive && $store.state.draggingCalendar" style="position: absolute;left: 0;top:0;z-index: 9;height: 100%;width: 100%;display: flex;">
        <div @mouseup="gotMove(val)" v-for="val in hours" class="min-separete">
            <div class="min-popup">{{ fullDate(val) }}</div>
        </div>
    </div>
</div>
        
   
</template>
<script>
import moment  from 'moment';
import UserIcon from '../../Board/Mixed/UserIcon.vue';
import DayRecord from './DayRecord.vue';

export default {
    data(){
        return{
            dragActive: false,
            beforeState: 0
        }
    },
    components:{
        UserIcon,
        DayRecord
    },
    props: ['hourRecords', 'facilitiesList', 'hour', 'day'],
    emits: ['scrollToTime', 'edit', 'dropFinish', 'delete', 'viewDetails', 'create'],
    computed: {
        hours(){
            return [
                { val: '00' },
                { val: '30' },
            ]
        },
        colors(){
                return [
                "#f7d5d5",
                "#ffd4a8",
                "#f8f2a6",
                "#cee4d2",
                "#c2d2e4",
                "#d6cfed"
            ]
        }
    },
    methods:{
        setBeforeState(event){
            
            const el = document.getElementById('cal_day_view')
            this.beforeState = event.x   
        },
        createAtTime(event){
            if(Math.abs(event.x - this.beforeState) > 15) {
                return
            }
            const targetElement = event.target;
            const elementWidth = targetElement.offsetWidth;
            const clickX = event.clientX - targetElement.getBoundingClientRect().left;
            let min = ''
            if (clickX < elementWidth / 2) {
                min = '00'
            } else {
                min = '30'
            }
            const date = this.day.full
            const time = this.hour.split(":");
            const minute = min
            const merge = moment(date).set('hour', time[0]).set('minute', min).set('second', 0).format('YYYY-MM-DD HH:mm:ss');
            const d = {
                x: event.x,
                y: event.y,
                time: merge,
                stamp: moment()
            }
            this.$emit('create', d)
            
        },
        enter(){
            if(this.$store.state.draggingCalendar){
                this.dragActive = true
            }
            
        },
        leave(){
            this.dragActive = false
        },
        gotMove(val){
            if(this.$store.state.draggingCalendar){
                const record = this.$store.state.draggingCalendar
                this.$store.commit('setDraggingCalendar', null)
                const date = this.day.full
                const time = this.hour.split(":");
                const min = val.val
                const merge = moment(date).set('hour', time[0]).set('minute', min).set('second', 0).format('YYYY-MM-DD HH:mm:ss');
                this.dragActive = false
                this.$emit('dropFinish', record, merge)
            }
            
            
        },
        fullDate(val){
            const date = this.day.full
            const time = this.hour.split(":");
            const min = val.val
            const merge = moment(date).set('hour', time[0]).set('minute', min).set('second', 0).format('YYYY-MM-DD HH:mm');
            return merge
        }
    }
}


</script>