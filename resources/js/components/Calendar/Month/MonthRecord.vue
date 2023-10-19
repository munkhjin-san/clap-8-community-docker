<template>
    <OnLongPress 
        as="div" 
        :ref="`m_rec_${record.id}`"
        class="month-card-inner" 
        :class="[{'pop-m-card' : expanded}]"
        :style="{
            maxHeight: maxHeight,
            opacity: opacity,
            transform: expanded ? `translate(${shiftRight}px, ${shiftBottom}px)` : `translate(0, 0)`
        }"
        :id="`m_rec_${record.id}`"
        @dragover.prevent 
        @trigger="dragStart"
        :options="{delay: 400}"
        @mousedown="setBeforeState"
        @touchstart="setBeforeState"
        
    >
        <CalendarCard
            :record="record"
            :facilitiesList="facilitiesList" 
            :colors="colors"
            :viewable="viewable"
            :editable="editable"
            :expanded="expanded"
            @selectRecord="selectRecord"
            @deleteRecord="deleteRecord"
            @edit="val => $emit('edit', val)"
        />

    </OnLongPress>
</template>
<script>
import moment from 'moment';
import { nextTick } from 'vue';
import CalendarFiles from '../CalendarFiles.vue'
import { OnLongPress } from '@vueuse/components'
import CalendarCard from '../CalendarCard.vue';
export default{
    props: ['record', 'colors', 'facilitiesList'],
    emits: ['scrollToTime', 'edit', 'setParentDroppable', 'delete',],
    data(){
        return{
            viewRepeatDetails: false,
            shiftRight: '0',
            shiftBottom: '0',
            truncate: true,
            viewDetails: false,
            beforeState: 0,
            beforeLeft: 0
        }
    },
    components:{
        CalendarFiles,
        OnLongPress,
        CalendarCard
    },
    mounted(){
        if(this.$store.state.tempRecord && this.$store.state.tempRecord == this.record.id){   
            this.$store.commit('setMenu', {id: this.record.id, name: `cal_${this.record.id}`})
            nextTick(() => {
                this.$refs[`dayRecord_${this.record.id}`].scrollIntoView({block: 'center', inline: 'center'})
                console.log('jumpfromday')
            })           
        }
    },
    computed: {
        viewable(){
            return this.record.release_flag == 0 || this.editable
        },
        opacity(){
            return ( this.$store.state.draggingCalendar && this.$store.state.draggingCalendar.id == this.record.id ) ? '0.5' : '1'
        },
        editable(){
            const me = this.record.calendar_users.filter(ob => ob.id == this.$store.state.user.id)
            return me.length || this.record.edit_all
        },
        maxHeight(){
            return this.expanded ? '100vh' : '60px'
        },
        otherExpanded(){
            return this.$store.state.menu.id !== this.record.id && this.$store.state.menu && (this.$store.state.menu.name?.includes('cal_') || this.$store.state.menu.name?.includes(`calendarRecordMenu`))
        },
        expanded(){
            return this.$store.state.menu.id == this.record.id && (this.$store.state.menu.name == `cal_${this.record.id}` || this.$store.state.menu.name == `calendarRecordMenu`) 
        },
        recordWidth(){
            if(this.expanded){
                return '200%'
            }else{
                const minutesDifference = Math.abs(moment(this.record.date_start).diff(moment(this.record.date_end), 'minutes'))
                const steps = Math.floor(minutesDifference / 15)
                const until_start = Math.abs(moment(this.record.date_start).startOf('day').diff(moment(this.record.date_start), 'minutes'))                
                const before_limiter = Math.floor(until_start / 15) 
                const max_block = 96 - before_limiter
                const computed_width = steps > max_block ? max_block : steps
                const unit = this.$store.state.mobile ? '500vw' : '120vw'
                return `calc(((${unit} - 30px) / 96 * ${computed_width}) - 3px)`
            }
            
        },
        recordLeft(){
            const diff = Math.abs(moment(this.record.date_start).diff(moment(this.record.date_start).startOf('hour'), 'minutes'))
            const steps = Math.floor(diff / 15) 
            const unit = this.$store.state.mobile ? '500vw' : '120vw'
            return `calc(((${unit}  - 30px) / 96 * ${steps}) + 1px)`
        }
    },
    methods:{
        setBeforeState(event){
            
            const el = document.getElementById('cal_month_view')
            console.log(el.scrollTop)
            const left = el ? el.scrollTop : 0
            this.beforeLeft = left           
            console.log(this.beforeLeft)
        },
        deleteRecord(record){
            this.$emit('delete', record)
            this.$store.commit('setMenu', {id: null, name: ''})
        },
        dragStart(event){
            if(this.editable && !this.expanded){
                const el = document.getElementById('cal_month_view')
                const left = el ? el.scrollTop : 0
                if(left !== this.beforeLeft) {
                    return
                }
                const width = this.$refs[`dayRecord_${this.record.id}`]
                let record = this.record
                record['width'] = width
                record['x'] = event.x
                record['y'] = event.y
                this.$store.commit('setDraggingCalendar', record)
                this.$store.commit('setMenu', {id: null, name: ''})
                this.$emit('setParentDroppable')
            }            
        },
        selectRecord(record){
            // if(!this.viewable) return
            this.$store.commit('setMenu', {id: this.record.id, name: `cal_${this.record.id}`})
            nextTick(() => {
                // const el = this.$refs[`m_rec_${this.record.id}`]
                const el = document.getElementById(`m_rec_${this.record.id}`)
                if(el){
                    const rect = el.getBoundingClientRect();                    
                    const right_check = rect.x + rect.width
                    if(right_check > window.innerWidth){
                        this.shiftRight = window.innerWidth - right_check - 5
                    }
                }
                
            })

        }
    }
}
</script>