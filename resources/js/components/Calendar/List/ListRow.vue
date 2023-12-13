<template>
    <div class="w-day-item" :style="{position: 'relative', minHeight: `${layer * 70 + 30}px`}" @click.self.stop="createAtTime" @mousedown="setBeforeState" @touchstart="setBeforeState">
        <!-- <div 
            v-for="item in data.records" 
            :style="{
                width: width(item),
                position: 'absolute',
                background: 'gray',
                marginTop: `${(item.order * 30) + (item.order + 1) * 10}px`,
                zIndex: 1
            }">
            <div style="height: 30px;font-size: 13px;padding: 0 8px;line-height: 30px;hite-space: nowrap;overflow: hidden;">
                {{item.title }}
            </div>
            
        </div> -->
        <ListRecord
            v-for="item in data.records" 
            :record="item"
            :colors="colors"
            :facilitiesList="facilitiesList"
            :user="data.user"
            @edit="val => $emit('edit', val)"
            @delete="val => $emit('delete', val)"
        />
        <div v-if="dragActive && $store.state.draggingCalendar" style="position: absolute;left: 0;top:0;z-index: 9;height: 100%;width: 100%;display: flex;">
            <div @mouseup="gotMove(val)" v-for="val in hours" class="min-separete">
                <div class="min-popup">{{ fullDate(val) }}</div>
            </div>
        </div>
    </div>
</template>
<script>
import moment from 'moment'
import ListRecord from './ListRecord.vue';
export default{
    props: ['data', 'colors', 'facilitiesList', 'edit', 'delete'],
    emits: ['create'],
    data(){
        return{
            dragActive: false,
            beforeState: 0
        }
    },
    mounted(){

    },
    components:{
        ListRecord
    },
    computed:{
        layer(){
            const num = this.data.records.map(ob => ob.order)
            const max = num.length ? Math.max(...num) + 1 : 0;
            return max
           
        },
        hours(){
            return [
                { val: '00' },
                { val: '30' },
            ]
        },
    },
    methods:{
        setBeforeState(event){
            this.beforeState = event.x     
        },
        width(record){
            const minutesDifference = Math.abs(moment(record.date_start).diff(moment(record.date_end), 'minutes'))
            const steps = Math.ceil(minutesDifference / 15)
            return `calc(100% / 4 * ${steps} + ${Math.floor(minutesDifference / 60)}px)`
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
            const date = this.data.date
            const time = this.data.hour.split(":");
            const merge = moment(date).set('hour', time[0]).set('minute', min).set('second', 0).format('YYYY-MM-DD HH:mm:ss');
            const d = {
                x: event.x,
                y: event.y,
                time: merge,
                stamp: moment()
            }
            this.$emit('create', d, this.data.user)
            
        },
        
    }
}

</script>