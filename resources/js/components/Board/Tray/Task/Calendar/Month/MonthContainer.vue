<template>
    <div ref="mContainer" class="w-container">
        
        <div class="calendar-overflow" ref="monthRef" style="touch-action: manipulation">
            <swiper :initialSlide="swipeMonth" :longSwipes="false" :speed="500" :slidesPerView="1" :observeParents="true" :loop="true" @swiper="onSwiper" @slideChange="onSlideChange" ref="swiperref" style="height:100%;">
                <swiper-slide v-for="(month, index) in calendarData" :key="index" id="calendarsTable" style=" display:block !important; font-weight: 400; text-align: inherit;">
                    <div v-if="index == swipeMonth" style="height:100%;">
                        <div id="weekdayhead" class="weekday-header">
                            <div class="weekday-header-item" v-for="num in 7">
                                {{ weekDay(num) }}
                            </div>
                        </div>
                        <div style="height:calc(100% - 40px)">
                            <div v-for="(week, index) in month" :key="index" class="week-wrapper">                
                                <MonthRow
                                    v-for="(day, index) in week"
                                    :key="day.day_full"
                                    :day="day"
                                    :records="dayRecords(day)"
                                    :selectedMonth="selectedMonth"
                                    :selectedYear="selectedYear"
                                    :myColor="myColor"
                                    :taskCount="taskCount" 
                                    @addTask="addTask"
                                />
                            </div>
                        </div>
                    </div>
                </swiper-slide>
            </swiper>
        </div>
    </div> 
</template>

<script>
import moment from 'moment'
import MonthRow from './MonthRow.vue';
import { Swiper, SwiperSlide } from 'swiper/vue';
import 'swiper/css'
import { nextTick } from 'vue'
export default {
    props: ["records", "selectedYear", "selectedMonth", 'months', 'isSwiperChange', 'myColor'],
    data(){
        return{
            swipeMonth : this.selectedMonth,
            slide: '',
            swiper: null,
            taskCount: null
        }
    },
    // setup(props, {emit}) {
    //   const onSwiper = (swiper) => {
    //     console.log(swiper)
    //     swiper.slideTo(props.selectedMonth, false)
    //   };
    //   const onSlideChange = (swiper) => {
    //     emit('increase', swiper.realIndex, swiper.activeIndex)
    //     const wrapper = document.getElementById('calendarWrapper')
    //     if(!wrapper.classList.value){
    //         wrapper.classList.add("calendar-overflow");
    //     }
    //   };
    //   return {
    //     onSwiper,
    //     onSlideChange,
    //   };
    // },
    mounted(){
        const wrapper = document.getElementById('calendarWrapper')
        const wrapperRect = wrapper.getBoundingClientRect()
        const daysWrapperheight = wrapperRect.height - 40 - 40
        const daysHeight = Math.round(daysWrapperheight * (100 / 6) / 100)
        if(this.$store.state.mobile){
            this.taskCount = Math.round((daysHeight - 30) / 16 - 1) 
        }else{
            this.taskCount = Math.round((daysHeight - 30) / 20 - 1)
        }
    },
    computed: {
        calendarData() {
            
            const fullCalendar = []
            for(let month in this.months){
                const thisMonth = moment([this.selectedYear, month]);
                const firstDay = thisMonth.clone().startOf("isoWeek")
                const lastDay = thisMonth.clone().endOf("month").endOf("isoWeek");
                const calendar = [];
                for (let i = firstDay; i.isBefore(lastDay); i.add(1, "day")) {
                    const weekIndex = calendar.length - 1;
                    if (weekIndex < 0 || calendar[weekIndex].length === 7) {
                        calendar.push([]);
                    }
                    calendar[calendar.length - 1].push({ 
                        "day_short" : i.locale("mn").format("D"),
                        "day_full" : i.locale("mn").format("YYYY-MM-DD")
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
                
                
                fullCalendar.push(calendar);
            }
            
            return fullCalendar
        },
    },
    watch:{
        selectedMonth: {
            immediate: true,
            handler(newValue, oldValue) {
                setTimeout(() => {
                    if(!this.swiper.destroyed){
                        this.swiper.slideToLoop(newValue, false)
                    }
                })
            }
        },
    }, 
    methods:{
        onSwiper(swiper){
            this.swiper = swiper
        },
        onSlideChange(swiper){
            this.$emit('increase', swiper.realIndex, swiper.previousRealIndex)
            this.swipeMonth = swiper.realIndex
        },
        weekDay(num){
            return moment().weekday(num).locale(this.$store.state.local).format("dd")
        },
        dayRecords(day){
            return this.records.filter(ob => moment(ob.end_at).format('YYYY-MM-DD') == moment(day.day_full).format('YYYY-MM-DD'))
        },
        addTask(day){
            this.$emit('addTask', day)
        },
    },
    components: { MonthRow, Swiper, SwiperSlide }
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
        z-index: 2;
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
    }
  
    .swiper-slide{
        background-color: var(--background-color);
        color: var(--primary-color);
    }
    .week-wrapper{
        display:flex;
        height:calc(100% / 6);
        border-left: 1px solid var(--calendarBorder);
    }
</style>
