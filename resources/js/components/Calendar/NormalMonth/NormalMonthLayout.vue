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
                    @scroll="emit('scroll', $event)"
                >
                    <div id="weekdayhead" class="weekday-header" style="position: sticky;top: 0;">
                        <div class="weekday-header-item" v-for="num in 7">{{ weekDay(num as WeekdayNumbers) }}</div>
                    </div>
                    <div id="cal_month_inner" style="height:calc(100% - 40px);color: var(--primary-color);">
                        <div v-for="(week, index) in calendarData" :key="index" class="calendar-week-wrapper" ref="weekHeader">                
                            <DayBlock                                
                                v-for="(day, index) in week"
                                :key="day.day_full"
                                :day="day"
                                :records="dayRecords(day)"
                                :google-day-events="dayGoogleEvents(day)"
                                :selectedMonth="selectedMonth"
                                :selectedYear="selectedYear"                               
                                @fromMonth="val => emit('fromMonth', val)"
                                @jumpToDate="val => emit('jumpToDate', val)"
                                @addRecord="(type, val) => emit('addRecord' ,type ,val)"
                                @create="(date, user) => emit('create', date)"
                            />
                        </div>
                    </div>
                </div>
            </div>           
        </div>
    </div> 
</template>

<script setup lang="ts">
import DayBlock from './DayBlock.vue';
import { computed, ComputedRef, inject, onMounted, ref, useTemplateRef} from 'vue';
import { DateTime, WeekdayNumbers } from 'luxon';
import { CalendarRecord, GoogleEventItem, NormalMonthDay, WeeksArray } from '@/interface/calendarInterface';

    const props = defineProps<{
        records: CalendarRecord[];
        googleEvents: GoogleEventItem[];
        selectedYear: number;
        selectedMonth: number;
        initialLoader: boolean;
        activeMonth: number;
        activeYear: number;
    }>()
    const emit = defineEmits(['fromMonth', 'addRecord', 'jumpToDate', 'scroll', 'create'])      
    const holidays = inject<ComputedRef>('holidays')
    onMounted(() => {
        localStorage.setItem('viewType', '1')      
    })
    const weekHeader = ref([])
    const monthScrollContainer = useTemplateRef('monthScrollContainer')
    const calendarData = computed(() => {            
        const thisMonth = DateTime.fromObject({year: props.activeYear, month: props.activeMonth});
        let firstDay = thisMonth.startOf('week')
        const lastDay = thisMonth.endOf('month').endOf('week')
        const calendar:WeeksArray = [];
        
        while(firstDay <= lastDay){
            const week: NormalMonthDay[] = [];
            for (let i = 0; i < 7; i++) {
                const holiday = holidays?.value.find((h: { date: Date }) => {
                    const holidayDate = DateTime.fromISO(h.date.toISOString());
                    return holidayDate.hasSame(firstDay, 'day');
                });
                    week.push({ 
                    "day_short": firstDay.toFormat("d"),
                    "day_full": firstDay.toFormat("yyyy-MM-dd"),
                    "day_holiday": holiday ? holiday.name : '',
                });
                firstDay = firstDay.plus({ days: 1 });
            }
            calendar.push(week);
        }
        if(calendar.length < 6){
            const nextWeek = thisMonth.endOf("month").endOf("week").plus({ weeks: 6 - calendar.length }).plus({ days: 1 });
            let i = lastDay.plus({ days: 1 });
            while (i < nextWeek) {
                const nextweekIndex = calendar.length - 1;
                if (nextweekIndex < 0 || calendar[nextweekIndex].length === 7) {
                    calendar.push([]);                    
                }
                calendar[calendar.length - 1].push({ 
                    "day_short": i.toFormat("d"),
                    "day_full": i.toFormat("yyyy-MM-dd"),
                    "day_holiday": ""
                });    
                i = i.plus({ days: 1 });            
            }
        }
        return calendar
    })

    const weekDay = (num: WeekdayNumbers) => {
        return DateTime.now()
        .set({ weekday: num }) 
        .toFormat('ccc');
    }
    const dayRecords = (day: NormalMonthDay) => {        
        return props.records.filter((ob) => DateTime.fromSQL(ob.date_start).hasSame(DateTime.fromISO(day.day_full), 'day'))
        .sort((a, b) => {
            const dateA = new Date(a.date_start);
            const dateB = new Date(b.date_start);
            return dateA.getTime() - dateB.getTime();
        }); 
    }
    const dayGoogleEvents = (day: NormalMonthDay) => {
        return props.googleEvents.filter((ob: GoogleEventItem) => {
            const startDate = DateTime.fromISO(ob.start_date);
            const endDate = ob.end_date ? DateTime.fromISO(ob.end_date) : startDate;
            const dayDate = DateTime.fromISO(day.day_full);
            return (dayDate >= startDate && dayDate <= endDate);        
        })
    }
    const containerScroll = async(day:string) => {
        const index = calendarData.value.findIndex(ob => {
            return ob.find(ob => ob.day_full == day)
        })
        if(index !== null && index !== undefined){
            const block = weekHeader.value[index] as HTMLElement
            block.scrollIntoView({block: 'start', behavior: 'instant'})
            monthScrollContainer.value?.scrollBy(0, -40)  
        }
    }
    defineExpose({containerScroll})

</script>
<style lang="scss" scoped>    
    $primary_gray: #ddd;
    .weekday-header{
        width: calc(100% - 1px);
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
