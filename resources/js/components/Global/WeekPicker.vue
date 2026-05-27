<template>
<div class="relative w-[300px] text-center flex justify-between items-center">
    <Back @click="shiftWeek(-1)" size="12"/>
    
    <button @click.stop="menu.setMenu({parent: 'weekPicker'}), offset = 0" :style="{background: 'inherit', color: 'inherit'}">{{ parsedDate }}
        <Back class="rotate-[270deg]" size="9"/>
    </button>  
    <Back @click="shiftWeek(1)" size="12" class="rotate-[180deg]"/>
    <Transition name="slidePop">
        <div id="weekPicker" v-if="menu.parent=='weekPicker'" class="month-grid z-[7] !top-[30px]" :style="{background: 'var(--background-color)', color: 'inherit'}">
            <div class="flex items-center justify-between mt-[5px]">
                <button @click="offset--" class="px-[15px] flex items-center gap-[10px] text-[13px] min-h-[40px] min-w-[40px] justify-center bg-inherit">
                    <Back/>
                </button>
                <div>{{ instance ? instance.plus({month: offset}).toFormat('yyyy年M月') : '' }}</div>
                <button @click="offset++" class="px-[15px] flex items-center gap-[10px] text-[13px] min-h-[40px] min-w-[40px] justify-center bg-inherit">
                    <Back style="transform: rotate(180deg);"/>
                </button>
            </div>
            <div class="px-[10px] pb-[10px]">            
                <table class="text-[13px]">
                    <thead>
                        <tr>
                            <th v-for="num in 7">{{ weekDay(num) }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr @click.stop="setDate(week)" v-for="(week, index) in calendarData" :key="index" class="w-row cursor-pointer">                
                            <td 
                                v-for="(day, index) in week" 
                                :key="day.day_full" 
                                class="text-center" 
                                :style="{
                                    opacity: instance?.plus({month: offset}).hasSame(DateTime.fromISO(day.day_full), 'month') ? '1' : '0.6',
                                    background: DateTime.fromISO(day.day_full).hasSame(DateTime.now(), 'day') ? '#c5af72' : 'inherit',
                                }"
                            >{{ day.day_short }}</td>
                        </tr>                    
                    </tbody>
                </table>
            </div>
            
        </div>
    </Transition> 
</div>
</template>
<script setup lang="ts">
import { useMenuStore } from '@/store/menu';
import { DateTime, WeekdayNumbers } from 'luxon';
import { computed, onMounted } from 'vue';
import { WeeksArray, NormalMonthDay } from '@/interface/calendarInterface'
import { usePublicHolidayStore } from '@/store/publicHoliday'
import Back from '../Icons/Back.vue';
import { ref } from 'vue';
const props = defineProps<{
    
}>()
const date = defineModel<string>()
const menu = useMenuStore()
const publicHolidayStore = usePublicHolidayStore()

onMounted(() => {
    publicHolidayStore.ensureLoaded()
})

const parsedDate = computed(() => {
    if(!instance.value) return ''
    return `${instance.value.toFormat('yyyy / M / d')} ~ ${instance.value.plus({ days: 6 }).toFormat('yyyy / M / d')}`
})
const weekDay = (num:number) => {
    return DateTime.now()
    .set({ weekday: num as WeekdayNumbers }) 
    .toFormat('ccc');
}
const holidays = computed(() => {
    if(!instance.value) return []
    const holidays = publicHolidayStore.between(instance.value.startOf('year').toJSDate(), instance.value.endOf('year').toJSDate());
    return holidays
})

const offset = ref(0)

const instance = computed(() => {
    if(!date.value) return null
    return DateTime.fromISO(date.value)
})
const calendarData = computed(() => {
    if(!instance.value) return []
    const thisMonth = instance.value.plus({month: offset.value});
    let firstDay = thisMonth.startOf('month').startOf('week');
    const lastDay = thisMonth.endOf('month').endOf('week')
    const calendar:WeeksArray = [];
    
    while(firstDay <= lastDay){
        const week: NormalMonthDay[] = [];
        for (let i = 0; i < 7; i++) {
            const holiday = holidays.value.find(h => {
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


const setDate = (dateData:NormalMonthDay[]) => {
    if(!instance.value) return
    const firstDay = dateData[0].day_full
    date.value = firstDay
    offset.value = 0
    menu.close()
}
const shiftWeek = (index: number) => {
    if(!instance.value) return
    const newDate = instance.value.plus({ weeks: index });
    console.log(newDate)
    date.value = newDate.toISODate() || '';
    offset.value = offset.value + index;
}
</script>
<style scoped>
td, th {
    padding: 0;
    margin: 0;
    height: 40px;
    width: 40px;
    min-width: 40px;
    vertical-align: middle;
}
.w-row:hover  {
  background-color: var(--bg3); /* Light green background for hovered cell */
}
</style>