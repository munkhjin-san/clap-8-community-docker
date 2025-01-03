<template>
    <div id="cMonthPicker" class="monthPicker">
        <div>
            
            <div></div>
            <div @click.stop="openMonthPicker" id="activateButton" class="" style="white-space: nowrap;font-size: 15px;height: 30px;line-height: 30px;user-select: none;cursor: pointer;color: var(--primary-color);">{{ formatDate }}</div>
            <div></div>
            
        </div>
        <div id="taskYearPicker" class="month-grid" v-if="menu.id == uniqueId && menu.name == 'taskYearPicker'" :style="{right : right ? right : 'auto'}">
            <div class="grid-container">
                <div @click.stop="decreaseYear" class="grid-item grid-picker">
                    <Back size="13"/>
                </div>
                <div @click.stop="pickerIs = 'year'" class="grid-item grid-picker">{{ year }}年</div>
                <div @click.stop="increaseYear" class="grid-item grid-picker">
                    <Back size="13" style="transform: rotate(180deg);"/>
                </div>
            </div>
            <div v-if="pickerIs == 'month'" class="grid-container ">
                <div @click.stop="setMonth(month)" :id="`m_${month}`" v-for="month in 12" class="grid-item">{{ month }}月</div>
            </div>
            <div v-if="pickerIs == 'year'" class="grid-container year-picker">
                <div @click.stop="setYear(y)" :id="`y_${y}`" :class="{thisYear : y == year}" v-for="y in yearList" class="grid-item">{{ y }}年</div>
            </div>
        </div>
       
    </div>
</template>

<script setup>
import { computed, nextTick, ref, watch } from 'vue'
import moment from 'moment'     
    const props = defineProps(['selectedMonth', 'selectedYear', 'right'])
    const emit = defineEmits(['setDate'])
    const month = ref(props.selectedMonth + 1)
    const year = ref(props.selectedYear)
    const pickerIs = ref('')
    const uniqueId = ref(Math.floor(100000 + Math.random() * 900000))
    import { useMenuStore } from "@/store/menu";
import Back from '../Icons/Back.vue'
    const menu = useMenuStore()
    watch(() => pickerIs.value, (after) => {
        if(after == 'month'){
            nextTick(() =>{
                document.getElementById(`m_${month.value}`)
            })
        }
    })
    watch(() => props.selectedMonth, (newValue) => {
        console.log(props.selectedMonth)
        month.value = newValue + 1
    }, {immediate: true})
    watch(() => props.selectedYear, (newValue) => {
        year.value = newValue
    }, {immediate: true})
    const yearList = computed(() => {
        const year = parseInt(moment().year())
        return Array.from({ length: 12 }, (_, i) => year - 5 + i);
    })
    const formatDate = computed(() => {
        let formattedMonth = `${month.value}`.padStart(2, '0');
        const date = `${year.value}-${formattedMonth}`
        return moment(date).locale('ja').format('YYYY年M月')
    })
    const decreaseYear = () => {
        const currentYear = parseInt(moment().year())
        if(year.value > currentYear - 5){
            year.value --
        }
    }
    const increaseYear = () => {
        const currentYear = parseInt(moment().year())
        if(year.value < currentYear + 6){
            year.value ++
        }
    }
    const openMonthPicker = () => {
        pickerIs.value = 'month';
        if(menu.name == 'taskYearPicker'){
            menu.close()
            return
        }
        menu.setMenu( { name: 'taskYearPicker', id: uniqueId.value})
    }
    const setYear = (y) => {
        year.value = y
        pickerIs.value = 'month'
    }
    const setMonth = (m) => {
        month.value = m
        pickerIs.value = ''
        emit('setDate', {year: year.value, month: m, select: true})
        menu.setMenu({ name: '', id: null})
    }
</script>
<style lang="scss">
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
    .grid-container {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        background-color: var(--message-background);
    }
    .month-grid{
        position: absolute;
        top: 40px;
        box-shadow: 0 1px 2px 0 rgb(60 64 67 / 30%), 0 2px 6px 2px rgb(60 64 67 / 15%);
        z-index: 25;
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
    .grid-item:hover:not(#activateButton) {
        background: var(--bg2);
    }
    .year-picker{
        max-height: 200px;
        overflow: hidden auto;
    }

    @media screen and (max-width: 959px){
        .grid-container{
            background-color: var(--message-background);
        }
    }
</style>
