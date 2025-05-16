<template>
    <div id="cMonthPicker" class="monthPicker">
        <div>
            
            <div></div>
            <div @click.stop="openMonthPicker" id="activateButton" class="" style="white-space: nowrap;font-size: 15px;height: 30px;line-height: 30px;user-select: none;cursor: pointer;color: var(--primary-color);">{{ formatDate }}</div>
            <div></div>
            
        </div>
        <div id="taskYearPicker" class="month-grid" v-if="menu.id == 42 && menu.name == 'taskYearPicker'" :style="{right : right ? right : 'auto'}">
            <div class="grid-container-with-day">
                <div @click.stop="dayNavigation(-1)" class="">
                    <Back size="13"/>
                </div>
                <div @click.stop="pickerIs = 'year'" class="">{{ year }}年</div>
                <div @click.stop="pickerIs = 'month'" class="">{{ month }}月</div>
                <div @click.stop="pickerIs = 'day'" class="">{{ day }}日</div>
                <div @click.stop="dayNavigation(1)" class="">
                    <Back size="13" style="transform: rotate(180deg);"/>
                </div>
            </div>
            <div v-if="pickerIs == 'month'" class="grid-container ">
                <div @click.stop="setMonth(month)" :id="`m_${month}`" v-for="month in 12" class="grid-item">{{ month }}月</div>
            </div>
            <div v-if="pickerIs == 'year'" class="grid-container year-picker">
                <div @click.stop="setYear(y)" :id="`y_${y}`" :class="{thisYear : y == year}" v-for="y in yearList" class="grid-item">{{ y }}年</div>
            </div>
            <div v-if="pickerIs == 'day'" class="day-grid-pick">
                <div @click.stop="setDate(d.day_full)" :id="`d_${d}`" v-for="d in dayList" :class="['grid-item', 'grid-days', {'grid-days-inactive' : checkSide(d.day_full)}]">{{ d.day_short }}</div>
            </div>
        </div>
       
    </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useMenuStore } from "@/store/menu";
import Back from '../Icons/Back.vue';
import { DateTime, MonthNumbers } from 'luxon';
    const menu = useMenuStore()
    const props = defineProps([ 'right'])
    const emit = defineEmits(['setDate'])

    const month = defineModel<MonthNumbers>('month')
    const year = defineModel<number>('year')
    const day = defineModel<number>('day')
    const pickerIs = ref('day')        



    const yearList = computed(() => {
        const year = DateTime.now().year
        return Array.from({ length: 12 }, (_, i) => year - 5 + i);
    })
    const dayList = computed(() => {
        const thisMonth = DateTime.fromObject({year: year.value,month:  month.value});
        if(!thisMonth.isValid) return []
        const firstDay = thisMonth.startOf("week")
        const lastDay = thisMonth.endOf("month").endOf("week").plus({weeks: 1});
        const calendar:{day_short: string, day_full:string}[] = [];
        let instance = firstDay
        while (instance < lastDay) {
            calendar.push({ 
                "day_short" : instance.day.toString(),
                "day_full" : instance.toISODate(),
            });
            instance = instance.plus({days: 1})
        }
        return calendar
    })
    const formatDate = computed(() => {
        const instance = DateTime.fromObject({ year: year.value, month: month.value, day: day.value})


        return instance.isValid ? instance.toLocaleString() : ''
    })


    const checkSide = (date:string) => {
        const currentMonth = DateTime.fromObject({ year: year.value, month: month.value})
        const targetMont = DateTime.fromISO(date)
        return !currentMonth.hasSame(targetMont, 'month')
    }
    const dayNavigation = (index:number) => {
        const instance = DateTime.fromObject({ year: year.value, month: month.value, day: day.value}).plus({days: index}).toISODate() as string
        setDate(instance, true)        
    }     
    
    const openMonthPicker = () => {
        pickerIs.value = 'day';
        if(menu.name == 'taskYearPicker'){
            menu.setMenu( { name: '', id: null})
            return
        }
        menu.setMenu( { name: 'taskYearPicker', id: 42})
    }
    const setYear = (y) => {
        year.value = y
        pickerIs.value = 'month'
    }
    const setMonth = (m) => {
        month.value = m
        pickerIs.value = 'day'

    }
    const setDate = (dt:string, passive?:boolean) => {
        const instance = DateTime.fromISO(dt)
        if(!instance.isValid) return
        day.value = instance.day
        year.value = instance.year
        month.value = instance.month
        emit('setDate', {year: instance.year, month: instance.month, day: instance.day, select: true})
        if(!passive){
            menu.close()
        }        
    }
        

</script>
<style lang="scss" scoped>
    .grid-picker{
        height: 40px !important; 
        margin: 0 0;
    }
    .monthPicker{
        user-select: none;
        display:flex;
        justify-content: center;
        position: relative;
        // border-left: solid thin var(--normalBorder) !important;
    }
    .day-grid-pick{
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        background-color: var(--message-background);
    }
    .grid-container {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        background-color: var(--message-background);
    }

    .grid-container-with-day {
        display: flex;
        background-color: var(--message-background);
        height: 50px;
        align-items: center;
        justify-content: space-evenly;
        font-size: 14px;
        color: var(--primary-color);
        fill: var(--primary-color);
    }
    .grid-container-with-day > div{
        cursor: pointer;
        padding: 0 10px;
        white-space: nowrap;
        height: 50px;
        display: flex;
        align-items: center;
    }
    .grid-container-with-day > div:hover{
        background: var(--bg2);
    }
    .month-grid{
        position: absolute;
        top: 40px;
        box-shadow: 0 1px 2px 0 rgb(60 64 67 / 30%), 0 2px 6px 2px rgb(60 64 67 / 15%);
        z-index: 25;
        min-width: 240px;
    }
    .grid-item {
        height: 50px;
        width: 80px;
        display: flex;
        justify-content: center;
        align-items: center;
        cursor: pointer;
        color: var(--primary-color);
        font-size: 14px;
        text-align: center;
        fill: var(--primary-color);
    }
    .grid-days-inactive {
        color: var(--check-inactive);
    }
    .grid-days {
        height: 34px;
        width: auto;
        font-size: 13px;
    }
    .grid-item:hover:not(#activateButton) {
        background: var(--bg2);
    }
    .year-picker{
        max-height: 200px;
        overflow: hidden auto;
    }
    // .day-picker-wrap{
    //     display: flex;
    //     height: 50px;
    //     justify-content: space-evenly;
    //     align-items: center;
    //     background: var(--bg2);
    // }
    @media screen and (max-width: 959px){
        .grid-container{
            background-color: var(--message-background);
        }
    }
</style>
