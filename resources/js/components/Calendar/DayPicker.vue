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
                    <svg version="1.1" width="13" height="13" viewBox="0 0 20 32" xmlns="http://www.w3.org/2000/svg">
                        <path d="M0.775 17.789c1.305 1.166 2.612 2.332 3.927 3.486 1.311 1.161 2.634 2.308 3.953 3.46 1.316 1.156 2.646 2.296 3.973 3.439 1.33 1.139 2.667 2.273 4.015 3.394 0.662 0.551 1.647 0.52 2.272-0.107 0.65-0.654 0.619-1.725-0.020-2.393-1.198-1.253-2.407-2.495-3.621-3.729-1.232-1.245-2.462-2.492-3.704-3.725-0.902-0.9-1.803-1.802-2.707-2.699-0.033-0.032-0.055-0.069-0.072-0.106-0.045-0.036-0.082-0.080-0.111-0.129-0.069-0.047-0.129-0.117-0.176-0.216-0.021-0.047-0.044-0.092-0.066-0.136-0.12-0.062-0.214-0.168-0.246-0.325-0.001-0.005-0.002-0.009-0.003-0.014-0.104-0.157-0.187-0.327-0.254-0.505-0.109-0.185-0.182-0.388-0.226-0.601-0.002-0.012-0.005-0.024-0.007-0.036-0.016-0.085-0.028-0.172-0.036-0.259-0.195-0.593-0.26-1.183 0.030-1.653 0.006-0.157 0.067-0.277 0.157-0.361 0.019-0.050 0.039-0.099 0.063-0.149 0.040-0.084 0.1-0.145 0.17-0.188 0.008-0.015 0.019-0.028 0.028-0.042 0.032-0.13 0.106-0.228 0.202-0.293 0.072-0.145 0.157-0.287 0.26-0.43 0.046-0.063 0.101-0.113 0.163-0.151 0.018-0.020 0.037-0.038 0.059-0.054 0.014-0.059 0.044-0.116 0.094-0.165 0.9-0.888 1.797-1.782 2.699-2.672 1.244-1.231 2.476-2.475 3.714-3.717l1.843-1.871 1.832-1.885c0.655-0.681 0.669-1.793-0.044-2.48-0.652-0.631-1.693-0.624-2.385-0.038l-1.964 1.66-1.995 1.71c-1.32 1.149-2.648 2.293-3.962 3.45s-2.636 2.308-3.943 3.474c-1.311 1.159-3.284 2.806-4.106 3.689s-0.792 2.492 0.191 3.369z"></path>
                    </svg>
                </div>
                <div @click.stop="pickerIs = 'year'" class="">{{ year }}年</div>
                <div @click.stop="pickerIs = 'month'" class="">{{ month }}月</div>
                <div @click.stop="pickerIs = 'day'" class="">{{ day }}日</div>
                <div @click.stop="dayNavigation(1)" class="">
                    <svg version="1.1" width="13" height="13" viewBox="0 0 20 32" xmlns="http://www.w3.org/2000/svg" style="transform: rotate(180deg);">
                        <path d="M0.775 17.789c1.305 1.166 2.612 2.332 3.927 3.486 1.311 1.161 2.634 2.308 3.953 3.46 1.316 1.156 2.646 2.296 3.973 3.439 1.33 1.139 2.667 2.273 4.015 3.394 0.662 0.551 1.647 0.52 2.272-0.107 0.65-0.654 0.619-1.725-0.020-2.393-1.198-1.253-2.407-2.495-3.621-3.729-1.232-1.245-2.462-2.492-3.704-3.725-0.902-0.9-1.803-1.802-2.707-2.699-0.033-0.032-0.055-0.069-0.072-0.106-0.045-0.036-0.082-0.080-0.111-0.129-0.069-0.047-0.129-0.117-0.176-0.216-0.021-0.047-0.044-0.092-0.066-0.136-0.12-0.062-0.214-0.168-0.246-0.325-0.001-0.005-0.002-0.009-0.003-0.014-0.104-0.157-0.187-0.327-0.254-0.505-0.109-0.185-0.182-0.388-0.226-0.601-0.002-0.012-0.005-0.024-0.007-0.036-0.016-0.085-0.028-0.172-0.036-0.259-0.195-0.593-0.26-1.183 0.030-1.653 0.006-0.157 0.067-0.277 0.157-0.361 0.019-0.050 0.039-0.099 0.063-0.149 0.040-0.084 0.1-0.145 0.17-0.188 0.008-0.015 0.019-0.028 0.028-0.042 0.032-0.13 0.106-0.228 0.202-0.293 0.072-0.145 0.157-0.287 0.26-0.43 0.046-0.063 0.101-0.113 0.163-0.151 0.018-0.020 0.037-0.038 0.059-0.054 0.014-0.059 0.044-0.116 0.094-0.165 0.9-0.888 1.797-1.782 2.699-2.672 1.244-1.231 2.476-2.475 3.714-3.717l1.843-1.871 1.832-1.885c0.655-0.681 0.669-1.793-0.044-2.48-0.652-0.631-1.693-0.624-2.385-0.038l-1.964 1.66-1.995 1.71c-1.32 1.149-2.648 2.293-3.962 3.45s-2.636 2.308-3.943 3.474c-1.311 1.159-3.284 2.806-4.106 3.689s-0.792 2.492 0.191 3.369z"></path>
                    </svg>
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

<script setup>
import { computed, ref, watch } from 'vue'
import moment from 'moment'
import { useMenuStore } from "@/store/menu";
    const menu = useMenuStore()
    const props = defineProps(['selectedMonth', 'selectedYear','selectedDay', 'right'])
    const emit = defineEmits(['setDate'])

    const month = ref(props.selectedMonth + 1)
    const year = ref(props.selectedYear)
    const day = ref(props.selectedDay)
    const pickerIs = ref('day')        
    watch(() => props.selectedMonth, (after) => {
        if(after){
            setTimeout(() => {
                month.value = after + 1
            }, 300);
        }
    })
        
    watch(() => props.selectedYear, (after) => {
        year.value = after            
    })
    watch(() => props.selectedDay, (after) => {            
        day.value = after            
    })


    const yearList = computed(() => {
        const year = parseInt(moment().year())
        return Array.from({ length: 12 }, (_, i) => year - 5 + i);
    })
    const dayList = computed(() => {
        const thisMonth = moment([year.value, month.value - 1]);
        const firstDay = thisMonth.clone().startOf("isoWeek")
        const lastDay = thisMonth.clone().endOf("month").endOf("isoWeek").add(1, 'weeks');
        let calendar = [];
        for (let i = firstDay; i.isBefore(lastDay); i.add(1, "day")) {
            calendar.push({ 
                "day_short" : i.locale("ja").format("D"),
                "day_full" : i.locale("ja").format("YYYY-MM-DD"),
            });
        }
        return calendar
    })
    const formatDate = computed(() => {
        const instance = moment([year.value, month.value - 1, day.value])
        console.log(instance.format('YYYY-MM-DD'))
        return moment([year.value, month.value - 1, day.value]).format('YYYY年M月D日')
    })

    const checkSide = (date) => {
        const currentMonth = moment([year.value, month.value - 1])
        const targetMont = moment(date)
        return !currentMonth.isSame(targetMont, 'month')
    }
    const dayNavigation = (index) => {
        const instance = moment([year.value, month.value - 1, day.value]).add(index, 'days').format('YYYY-MM-DD')
        console.log(instance)
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
    const setDate = (d, passive) => {
        const selected = d.split('-')
        day.value = parseInt(selected[2])
        year.value = parseInt(selected[0])
        month.value = parseInt(selected[1])
        emit('setDate', {year: year.value, month: month.value, day: day.value, select: true})
        if(!passive){
            menu.setMenu({ name: '', id: null})
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
