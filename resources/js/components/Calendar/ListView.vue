<template>
    <div         
        :style="{
            overflow: 'auto',
            scrollSnapType: '',
            cursor: 'grab'
        }" 
        ref="cal_week_view" 
        id="cal_list_view" 
        class="calendar-day-root"
        @mouseup="onMouseUp"
        @scroll="scrollListen"
        >
        <Transition name="modalFade">
            <div class="cal-day-loader" v-if="initialLoader">
                <div id="loaderMini">
                    <div class="spinner-mini" style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
                </div>
            </div>
        </Transition>
        <div @mousedown="onMouseDown" @touchstart="handleTouchStart" @touchmove="handleTouchMove" class="calendar-container-outer-week" :style="{width: `calc((100% / ${$store.state.mobile ? 4 : 13}) * ${24})`, height: '100%', background: 'var(--background-color)'}">
          
            <div class="calendar-header">  
                <div ref="spacer" :style="{ width: hideName ? '45px' : `130px`}" class="left-member-tile"></div>
                <div :ref="`w_day_${index}`" v-for="(hour, index) in hoursOfDay" class="w-day-item" style="border-right: solid thin transparent;">
                    <div :class="['top-list-tile']" ><div>{{ hour.hour }}</div></div> 
                </div>
            </div>     
            
            <div>
                <UserItem 
                    v-for="userData in listMembers"
                    :userData="userData"
                    :facilitiesList="facilitiesList"
                    :colors="colors"
                    :hideName="hideName"
                    @edit="val => $emit('edit', val)"
                    @delete="val => $emit('delete', val)"
                    @create="(date, user) => $emit('create', date, user)"
                    @viewFull="hideName = false"
                />
            </div>
        </div>
    </div>
</template>
<script>
import moment from 'moment';
import ListRow from './List/ListRow.vue'
import UserItem from './List/UserItem.vue'
export default{
    props: ['records', 'activeMembers', 'selectedDate', 'facilitiesList', 'edit', 'delete', 'initialLoader'],
    emits: ['create', 'resetFastCreate'],
    data(){
        return{
            hideName: false,
            lastScrollLeft: 0,
            scrollDirection: null,
            lockScroll: false,
            startX: 0,
            startY: 0,
            isHorizontalScroll: null
        }
    },
    watch:{
        lockScroll(after, before){
            if(after){
                setTimeout(() => {
                    this.lockScroll = false
                }, 300);
            }
        }
    },
    computed:{
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
        listMembers() {
            const uniqueUserIds = new Set();
            const memberList = [];
            this.activeMembers.forEach((user) => {
                    if (!uniqueUserIds.has(user.id)) {
                        uniqueUserIds.add(user.id);
                        const user_records = this.records.filter(ob => ob.calendar_users.map(item => item.id).includes(user.id))
                        memberList.push({user: user, records: user_records, date: this.selectedDate});
                    }
                });
           
            return memberList;
        },
        hoursOfDay() {
            const hours = [];
            let currentHour = moment().startOf('day');        
            for (let i = 0; i < 24; i++) {
                hours.push({hour: currentHour.format('H:mm')});
                currentHour.add(1, 'hour')
            }
            return hours;
        },
    },
    components:{
        ListRow,
        UserItem
    },
    unmounted(){
        window.removeEventListener("mouseup", this.onMouseUp);
    },
    mounted(){
        localStorage.setItem('viewType', 3)
        window.addEventListener("mouseup", this.onMouseUp);

        const now = moment().subtract(1, 'hour').startOf('hour').format('H')        
            const el = this.$refs[`w_day_${now}`]
            if(el && el.length){
                el[0].scrollIntoView({block : 'start', inline: "start" })
                setTimeout(() => {
                    this.hideName = false
                }, 0);
                
            }
    },
    methods:{
        handleTouchStart(event) {
            this.startX = event.touches[0].clientX;
            this.startY = event.touches[0].clientY;
            this.isHorizontalScroll = null;
        },
        handleTouchMove(event) {
        if (this.isHorizontalScroll === null) {
            const deltaX = Math.abs(event.touches[0].clientX - this.startX);
            const deltaY = Math.abs(event.touches[0].clientY - this.startY);
            const scrollThreshold = 10;
            if (deltaX > scrollThreshold || deltaY > scrollThreshold) {
                this.determineScrollDirection(deltaX, deltaY);
            }
        }
        },
        determineScrollDirection(deltaX, deltaY) {
            const scrollThreshold = 5;
            if (deltaX > deltaY && deltaX > scrollThreshold) {
                this.isHorizontalScroll = true;
            } else if (deltaY > deltaX && deltaY > scrollThreshold) {
                this.isHorizontalScroll = false;
            }
        },
        scrollListen(event){            
            if(this.$store.state.mobile && !this.lockScroll && this.isHorizontalScroll){
                // const currentScrollLeft = event.target.scrollLeft;
                // this.hideName = currentScrollLeft > this.lastScrollLeft    
                // if(currentScrollLeft > this.lastScrollLeft){
                    this.hideName = true
                    this.lockScroll = true
                // }           
                // this.lastScrollLeft = currentScrollLeft;
            }            
            this.$emit('resetFastCreate')
        },
        scrollTo(index){            
            requestAnimationFrame(() => {
                const val = window.innerWidth / 6 * index
                this.$refs.cal_week_view.scrollBy({
                    left: val,
                    behavior: "smooth",
                });
            })
        },
        onMouseDown(ev) {
            this.cursorPos = [ev.pageX, ev.pageY];
            window.addEventListener("mousemove", this.onMouseHold);
        },

        /** @param {MouseEvent} ev */
        onMouseUp(ev) {
            window.removeEventListener("mousemove", this.onMouseHold);
            this.isDragging = false;
        },

        /** @param {MouseEvent} ev */
        onMouseHold(ev) {
            ev.preventDefault();
            if(this.$store.state.draggingCalendar) return

            requestAnimationFrame(() => {
                const delta = [
                ev.pageX - this.cursorPos[0],
                ev.pageY - this.cursorPos[1],
                ];
                
                this.cursorPos = [ev.pageX, ev.pageY];

                if (!this.$refs.cal_week_view) return;
                this.$refs.cal_week_view.scrollBy({
                    left: -delta[0],
                    // top: -delta[1],
                });
                
            });
        },
    }

}
</script>