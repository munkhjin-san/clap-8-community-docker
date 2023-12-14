<template>
    <div         
        :style="{
            overflow: 'auto',
            scrollSnapType: '',
            cursor: 'grab'
        }" 
        ref="cal_week_view" 
        class="calendar-day-root"
        @scroll="scrollListen"
        @mouseup="onMouseUp">
        <Transition name="modalFade">
            <div class="cal-day-loader" v-if="initialLoader">
                <div id="loaderMini">
                    <div class="spinner-mini" style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
                </div>
            </div>
        </Transition>
        <div class="calendar-container-outer-week" :style="{width: `calc((100% / ${$store.state.mobile ? 4 : 8}) * ${days.length + 1})`}">
            
            <div class="calendar-header">  
                <div ref="spacer" :style="{ width: hideName ? '45px' : `130px`}" class="left-member-tile"></div>
                <div 
                    :id="`day_val_w_${day.day_full}`"
                    @click="shiftToListView($event,day.day_full)" 
                    @mousedown="onMouseDown" :ref="`w_day_${day.day_full}`" 
                    v-for="day in days" 
                    class="w-day-item" 
                    style="cursor: pointer;"
                >
                    <div :class="['top-day-tile', {isPastDay : isPastDay(day)}, {isTodayWeek : isToday(day)}]" >
                        <div :class="['cal-m-day-title', {'special-day': specialDay(day), 'isSaturday' : isSaturday(day)}]">{{ dayTitle(day) }}</div>
                        <p class="pc" style="margin-left: 5px;white-space: nowrap;overflow: hidden;font-size:11px;color:tomato" v-if="day.day_holiday">{{ day.day_holiday }}</p>
                    </div> 
                </div>
            </div>
      
            
            <div >
                <div v-for="user in listMembers" style="display: flex;">
                    <div @click="hideName = false" class="left-member-tile" :style="{ width: hideName ? '45px' : `130px`}">
                        <div style="cursor: pointer;">
                            <UserIcon :disableInstant="hideName" :user="user" imgClass="userMidIcon" size="25"/>
                            <div @click.stop="pushInstantUser($event, user.id)" :style="{lineHeight: 1.5, visibility: hideName ? 'hidden' : 'visible'}">{{user.name}}</div>
                        </div>                        
                    </div>
                    <WeekDay 
                        @mousedown="onMouseDown" 
                        @touchstart="handleTouchStart"
                        @touchmove="handleTouchMove"
                        v-for="day in days" 
                        :key="`${user.id}_${day.day_full}`" 
                        :user="user" 
                        :day="day"
                        :colors="colors"
                        :facilitiesList="facilitiesList"
                        :beforeState="beforeState"
                        @edit="val => $emit('edit', val)"
                        @delete="val => $emit('delete', val)"
                        @addRecord="(type, day, user) => $emit('addRecord',type, day, user)"
                        @create="(date, user) => $emit('create', date, user)"
                        />
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import moment from 'moment';
import WeekRecord from './Week/WeekRecord.vue';
import WeekDay from './Week/WeekDay.vue';
import UserIcon from '../Board/Mixed/UserIcon.vue';
export default{
    props: ["records", "selectedYear", "selectedMonth", 'isSwiperChange', 'facilitiesList', 'initialLoader', 'activeMonth', 'activeYear', 'holidays', 'edit', 'delete', 'activeMembers', 'appendLock'],
    emits: ['edit', 'delete', 'addRecord', 'create', 'resetFastCreate'],
    data(){
        return{
            cursorPos: [0, 0],
            beforeState: 0,
            hideName: false,
            lockScroll: false,
            scrollCount: 0,
            startX: 0,
            startY: 0,
            isHorizontalScroll: null
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
                    memberList.push(user);
                }
            });
          
            return memberList;
        },
        days(){

            const thisMonth = moment([this.activeYear, this.activeMonth]);
            const firstDay = thisMonth.clone().startOf("month")
            let index = 0
            const today = moment()
            if(today.isSame(thisMonth, 'month')){
                const diff = today.add(1, 'week').diff(thisMonth.clone().endOf('month'), 'days')
                if(diff > 0){
                    index = diff
                }
            }
            const lastDay = thisMonth.clone().endOf("month").add(index, 'days');
            let calendar = [];
            for (let i = firstDay; i.isBefore(lastDay); i.add(1, "day")) {
                const holiday = this.holidays.find(h => moment(h.date).isSame(i, 'day'));
                const records = this.records.filter(ob => moment(ob.date_start).isSame(moment(i), 'day'))
                calendar.push({ 
                    "day_short" : i.locale("ja").format("D"),
                    "day_full" : i.locale("ja").format("YYYY-MM-DD"),
                    "day_holiday" : holiday ? holiday.name : null,
                    "records" : records
                });
            }
            return calendar
        }   
    },
    components:{
        WeekRecord,
        WeekDay,
        UserIcon
    },
    unmounted(){
        window.removeEventListener("mouseup", this.onMouseUp);
    },
    mounted(){
        localStorage.setItem('viewType', 2)
        window.addEventListener("mouseup", this.onMouseUp);
        // const today = moment().format('YYYY-MM-DD')
        // const el = this.$refs[`w_day_${today}`]
        // if(el && el.length){
        //     const rect = el[0].getBoundingClientRect()
        //     const r_el = this.$refs.cal_week_view
        //     const space = this.$refs.spacer
        //     console.log(space.getBoundingClientRect())
        //     if(r_el && space){
        //         const index = this.$store.state.mobile ? 0 : 60
        //         r_el.scrollTo(rect.x - space.getBoundingClientRect().width - index, 0)
        //     }
        // }
        // .scrollIntoView()
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
    methods:{
        pushInstantUser(event, id){
            if(this.$store.state.user && id == this.$store.state.user.id) return
            const cX = event.clientX;
            const cY = event.clientY;  
            const data = {
                id: id,
                cX: cX,
                cY: cY
            }
            this.$store.commit('setInstantUser', data)   
            this.$store.commit('setMenu', {name: 'instantProfileWindow', id: 5000})                 
        },
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
        scrollListen(){
            // console.log(this.isHorizontalScroll)
            if(this.$store.state.mobile && !this.lockScroll && !this.appendLock && this.scrollCount > 0 && this.isHorizontalScroll){
                // const currentScrollLeft = event.target.scrollLeft;
                // this.hideName = currentScrollLeft > this.lastScrollLeft    
                // if(currentScrollLeft > this.lastScrollLeft){
                    this.hideName = true
                    this.lockScroll = true
                    
                // }           
                // this.lastScrollLeft = currentScrollLeft;
            }        
            this.scrollCount ++   
            this.$emit('resetFastCreate')
        },
        shiftToListView(event, date){
            if(Math.abs(event.pageX - this.beforeState) > 15) {
                console.log('stop!!!', Math.abs(event.x - this.beforeState))
                return
            }
            this.$emit('setListView', date)
            
        },
        isSaturday(day){
            return moment(day.day_full).day() === 6
        },
        specialDay(day){
            return moment(day.day_full).day() === 0 || day.day_holiday
        },
        isPastDay(day){
            return moment(day.day_full).isBefore(moment(), 'day')
        },
        isToday(day){
            return moment(day.day_full).isSame(moment(), 'day')
        },
        dayTitle(day){
            const format = moment([this.selectedYear, this.selectedMonth]).isSame(moment(day.day_full), 'month') ? 'D(ddd)' : moment().isSame(moment(day.day_full), 'year') ? 'M/D(ddd)' : 'YYYY/M/D(ddd)'
            return moment(day.day_full).format(format)
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
            this.beforeState = ev.pageX
            // this.isDragging = true;
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