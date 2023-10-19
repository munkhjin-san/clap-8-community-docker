<template>
    <div ref="mContainer" class="cal-w-container">
        <Transition name="modalFade">
            <div class="cal-month-loader" v-if="initialLoader">
                <div id="loaderMini">
                    <div class="spinner-mini" style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
                </div>
            </div>
        </Transition>
        <div style="touch-action: manipulation;height: 100%;">           
            <div id="calendarsTable" style=" display:block !important; font-weight: 400; text-align: inherit;height: 100%;">
                <div ref="monthScrollContainer" style="height:100%;overflow: auto;" id="cal_month_view">
                    <div id="weekdayhead" class="weekday-header" style="position: sticky;top: 0;">
                        <div class="weekday-header-item" v-for="num in 7">{{ weekDay(num) }}</div>
                    </div>
                    <div style="height:calc(100% - 40px);color: var(--primary-color);">
                        <div v-for="(week, index) in calendarData" :key="index" class="calendar-week-wrapper">                
                            <MonthRow
                                v-for="(day, index) in week"
                                :key="day.day_full"
                                :day="day"
                                :records="dayRecords(day)"
                                :selectedMonth="selectedMonth"
                                :selectedYear="selectedYear"
                                :colors="colors"
                                :taskCount="taskCount" 
                                :facilitiesList="facilitiesList"
                                @addTask="addTask"
                                @edit="val => $emit('edit', val)"
                                @delete="val => $emit('delete', val)"
                                @fromMonth="val => $emit('fromMonth', val)"
                                @jumpToDate="val => $emit('jumpToDate', val)"
                                @addRecord="(type, val) => $emit('addRecord' ,type ,val)"
                                @dropFinish="(record, date) => $emit('dropFinish', record, date)"
                            />
                        </div>
                    </div>
                </div>
            </div>           
        </div>
    </div> 
</template>

<script>
import moment from 'moment'
import MonthRow from './Month/MonthRow.vue';

export default {
    props: ["records", "selectedYear", "selectedMonth", 'isSwiperChange', 'facilitiesList', 'initialLoader', 'activeMonth', 'activeYear', 'holidays', 'edit', 'delete'],
    emits: ['fromMonth', 'slided', 'addRecord', 'dropFinish', 'jumpToDate', 'edit', 'delete'],
    data(){
        return{
            swipeMonth : this.selectedMonth,
            slide: '',
            swiper: null,
            taskCount: null
        }
    },
    mounted(){
        localStorage.setItem('viewType', 1)
        // const wrapper = document.getElementById('calendarWrapper')
        // const wrapperRect = wrapper.getBoundingClientRect()
        // const daysWrapperheight = wrapperRect.height - 40 - 40
        // const daysHeight = Math.round(daysWrapperheight * (100 / 6) / 100)
        // if(this.$store.state.mobile){
        //     this.taskCount = Math.round((daysHeight - 30) / 16 - 1) 
        // }else{
        //     this.taskCount = Math.round((daysHeight - 30) / 20 - 1)
        // }
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
        months(){
            return Array.from({ length: 12 }, (_, index) => index + 1);
        },
        calendarData() {
                
                const thisMonth = moment([this.activeYear, this.activeMonth]);
                const firstDay = thisMonth.clone().startOf("isoWeek")
                const lastDay = thisMonth.clone().endOf("month").endOf("isoWeek");
                const calendar = [];
                for (let i = firstDay; i.isBefore(lastDay); i.add(1, "day")) {
                    const weekIndex = calendar.length - 1;
                    if (weekIndex < 0 || calendar[weekIndex].length === 7) {
                        calendar.push([]);
                    }
                    const holiday = this.holidays.find(h => moment(h.date).isSame(i, 'day'));
                    calendar[calendar.length - 1].push({ 
                        "day_short" : i.locale("ja").format("D"),
                        "day_full" : i.locale("ja").format("YYYY-MM-DD"),
                        "day_holiday" : holiday ? holiday.name : null,
                    });
                }
                if(calendar.length < 6){
                    const nextWeek = thisMonth.clone().endOf("month").endOf("isoWeek").add(6 - calendar.length, "week").add(1, 'day');
                    for(let i = lastDay.add(1, 'day'); i.isBefore(nextWeek); i.add(1, 'day')){
                        const nextweekIndex = calendar.length - 1;
                        if (nextweekIndex < 0 || calendar[nextweekIndex].length === 7) {
                            calendar.push([]);
                            
                        }
                        calendar[calendar.length - 1].push({ 
                            "day_short" : i.locale("mn").format("D"),
                            "day_full" : i.locale("mn").format("YYYY-MM-DD")                            
                        });
                        
                    }

                }
                return calendar
        },
    },
    watch:{
        // selectedMonth: {
        //     immediate: true,
        //     handler(newValue, oldValue) {
        //         setTimeout(() => {
        //             if(!this.swiper.destroyed){
        //                 this.swiper.slideToLoop(newValue, false)
        //             }
        //         })
        //     }
        // },
    }, 
    methods:{
        onSwiper(swiper){
            this.swiper = swiper
        },
        onSlideChange(swiper){
            this.$emit('slided', swiper.realIndex, swiper.previousRealIndex)
            this.swipeMonth = swiper.realIndex
        },
        weekDay(num){
            return moment().weekday(num).locale(this.$store.state.local).format("dd")
        },
        dayRecords(day){
            
            return this.records.filter(ob => moment(ob.date_end).format('YYYY-MM-DD') == moment(day.day_full).format('YYYY-MM-DD')).sort((a, b) => {
                    return new Date(a.date_start) - new Date(b.date_start);
                }); 
        },
        addTask(day){
            this.$emit('addTask', day)
        },
    },
    components: { MonthRow }
}
</script>
<style lang="scss" scoped>
    .swiper-container {
        width: 100%;
        max-width: 100%;
        max-height: 100vh;
        // CSS Grid/Flexbox bug size workaround
        // @see https://github.com/kenwheeler/slick/issues/982
        // @see https://github.com/nolimits4web/swiper/issues/3599
        min-height: 0;
        min-width: 0;
    }
$primary_gray: #ddd;
    .weekday-header{
        width: 100%;
        height: 40px;
        display: flex;
        justify-content: space-around;
        top: 0;
        color:var(--primary-color);
        font-size: 12px;
        box-sizing: border-box;
        z-index: 12;
        background-color:var(--background-color);
        border-left: 1px solid var(--calendarBorder);
        border-top: 1px solid var(--calendarBorder);
    }
    .weekday-header-item{
        flex: 1;
        text-align: center;
        line-height: 40px;
        border-right: 1px solid var(--calendarBorder);
        border-bottom: 1px solid var(--calendarBorder);
        user-select: none;
    }
  
    .swiper-slide{
        background-color: var(--background-color);
        color: var(--primary-color);
    }
    .calendar-week-wrapper{
        display:flex;
        min-height:calc(100% / 6);
        border-left: 1px solid var(--calendarBorder);
    }
</style>
