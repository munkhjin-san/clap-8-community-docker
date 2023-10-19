<template>
    <div v-if="record">
        <div 
            v-if="!mobile"
            ref="draggingCalendar" 
            class="calendar-card" 
            :style="{
                width: `${record['width']}px`, 
                maxHeight: '60px',
                background: background,
                color: color,
                position: 'fixed',
                zIndex: '100',
                top: `${record['y'] + 10}px`,
                left: `${record['x'] + 10}px`
            }"
        >
            <div class="calendar-card-inner">            
                <div style="display: flex;">
                    <UserIcon v-for="user in record.calendar_users.slice(0, 3)" :user="user" imgClass="userSmallIcon" size="15"/>
                    <span style="line-height: 15px;" v-if="record.calendar_users.length > 3">...({{ record.calendar_users.length }})</span>
                </div>
                <div style="white-space: nowrap;">{{ time }}</div>          
                <div>{{ record.title }}</div>
            </div>
        </div>
        <Transition name="slidePop">
            <div v-if="mobile" class="copySuccess">    
                <span>移動先を選択してください</span>
                <div @click="cancelMove" style="margin-left: 10px;" class="commentEditButton">キャンセル</div>
            </div>
        </Transition>  
    </div>
    
</template>
<script>
import moment from 'moment';
export default{    
    data(){
        return{
            mobile: false
        }
    },  
    mounted() {
        if ('ontouchstart' in window || navigator.msMaxTouchPoints) {
            this.mobile = true;
        }
        document.body.style.userSelect = 'none'
        window.addEventListener('mousemove', this.onMove);
        window.addEventListener('mouseup', this.onReset);
    },
    unmounted(){
        
        window.removeEventListener('mousemove', this.onMove);
        window.removeEventListener('mouseup', this.onReset);
    },
    methods:{
        onReset(e){            
            this.$store.commit('setDraggingCalendar', null)           
        },
        onMove(e){
            if(!this.$store.state.draggingCalendar || this.$store.state.mobile) return
            let el = this.$refs.draggingCalendar              
            el.style.top = e.clientY + 10 + 'px'
            el.style.left = e.clientX + 10 + 'px'
        },   
        cancelMove(){
            this.$store.commit('setDraggingCalendar', null)
        }
    },
    computed: {
        colors(){        
            return [
                "#f7d5d5",
                "#ffd4a8",
                "#f8f2a6",
                "#cee4d2",
                "#c2d2e4",
                "#d6cfed"
            ]        
        },
        record(){
            return this.$store.state.draggingCalendar ? this.$store.state.draggingCalendar : null
        },      
        background(){
            
            const me = this.record.calendar_users.filter(ob => ob.id == this.$store.state.user.id)
            return me.length ? this.colors[this.$store.state.user.color] : 'var(--task-background)'
        },
        color(){
            const me = this.record.calendar_users.filter(ob => ob.id == this.$store.state.user.id)
            return me.length && this.$store.state.dark ? 'var(--background-color)' : 'var(--primary-color)'
        },      
               
        time(){
            return `${moment(this.record.date_start).format('H:mm')} ~ ${moment(this.record.date_end).format('H:mm')}`
        },
        
        
    },
    
}
</script>