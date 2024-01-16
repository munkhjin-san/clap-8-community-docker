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
                <div 
                    ref="monthScrollContainer" 
                    style="height:100%;overflow:hidden auto;" 
                    id="cal_month_view"
                    @scroll="$emit('scroll', $event)"
                >
                    <div id="weekdayhead" class="weekday-header" style="position: sticky;top: 0;">
                        <div class="weekday-header-item" v-for="num in 7">{{ weekDay(num) }}</div>
                    </div>
                    <div style="height:calc(100% - 40px);color: var(--primary-color);">
                        <div v-for="(week, index) in calendarData" :key="index" class="calendar-week-wrapper">                
                            <DayBlock
                                v-for="(day, index) in week"
                                :key="day.day_full"
                                :day="day"
                                :records="dayRecords(day)"
                                :selectedMonth="selectedMonth"
                                :selectedYear="selectedYear"                               
                                @fromMonth="val => $emit('fromMonth', val)"
                                @jumpToDate="val => $emit('jumpToDate', val)"
                                @addRecord="(type, val) => $emit('addRecord' ,type ,val)"
                                @create="(date, user) => $emit('create', date)"
                            />
                        </div>
                    </div>
                </div>
            </div>           
        </div>
    </div> 
</template>

<script setup>
    import moment from 'moment'
    import DayBlock from './DayBlock.vue';
    import { computed, onMounted} from 'vue';

    const props = defineProps(["records", "selectedYear", "selectedMonth", 'initialLoader', 'activeMonth', 'activeYear', 'holidays'])
    const emit = defineEmits(['fromMonth', 'slided', 'addRecord', 'jumpToDate', 'scroll', 'create'])      

    onMounted(() => {
        localStorage.setItem('viewType', 1)      
    })

    const calendarData = computed(() => {
            
        const thisMonth = moment([props.activeYear, props.activeMonth]);
        const firstDay = thisMonth.clone().startOf("isoWeek")
        const lastDay = thisMonth.clone().endOf("month").endOf("isoWeek");
        const calendar = [];
        for (let i = firstDay; i.isBefore(lastDay); i.add(1, "day")) {
            const weekIndex = calendar.length - 1;
            if (weekIndex < 0 || calendar[weekIndex].length === 7) {
                calendar.push([]);
            }
            const holiday = props.holidays.find(h => moment(h.date).isSame(i, 'day'));
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
    })

    const weekDay = (num) => {
        return moment().weekday(num).locale('ja').format("dd")
    }
    const dayRecords = (day) => {        
        return props.records.filter(ob => moment(ob.date_start).isSame(moment(day.day_full), 'day')).sort((a, b) => {
            return new Date(a.date_start) - new Date(b.date_start);
        }); 
    }

</script>
<style lang="scss" scoped>    
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
    .calendar-week-wrapper{
        display:flex;
        min-height:calc(100% / 6);
        border-left: 1px solid var(--calendarBorder);
    }
</style>
