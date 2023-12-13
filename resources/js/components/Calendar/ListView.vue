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
        >
        <Transition name="modalFade">
            <div class="cal-day-loader" v-if="initialLoader">
                <div id="loaderMini">
                    <div class="spinner-mini" style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
                </div>
            </div>
        </Transition>
        <div class="calendar-container-outer-week" :style="{width: `calc((100% / ${$store.state.mobile ? 4 : 13}) * ${24})`, height: '100%', background: 'var(--background-color)'}">
          
            <div class="calendar-header">  
                <div ref="spacer" class="left-member-tile"></div>
                <div :ref="`w_day_${index}`" v-for="(hour, index) in hoursOfDay" class="w-day-item" style="border-right: solid thin transparent;">
                    <div :class="['top-list-tile']" >
                        <div>{{ hour.hour }}</div>
                        
                    </div> 
                </div>
            </div>
      
            
            <div @mousedown="onMouseDown">
                <UserItem 
                    v-for="userData in listMembers"
                    :userData="userData"
                    :facilitiesList="facilitiesList"
                    :colors="colors"
                    @edit="val => $emit('edit', val)"
                    @delete="val => $emit('delete', val)"
                    @create="(date, user) => $emit('create', date, user)"
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
    emits: ['create'],
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
            }
    },
    methods:{
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