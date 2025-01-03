<template>
    <div class="monthPicker">
        <div @click.stop="openYearPicker" style="width: 100%;height: 100%;align-items: center;cursor:pointer;place-content: center;display:flex">
            {{ year }}年
        </div>
        <Transition name="slidePop">
            <div id="cYearPicker" v-if="menu.name=='cYearPicker' && menu.id==87" class="month-grid" :style="{right : right ? right : '0'}">
                <div v-if="pickerIs == 'year'" class="grid-container year-picker">
                    <div @click.stop="setYear(y)" :id="`y_${y}`" :class="{thisYear : y == year}" v-for="y in yearList" class="grid-item">{{ y }}年</div>
                </div>
            </div>
        </Transition>
    </div>

</template>

<script setup>
import { computed, nextTick, ref } from 'vue'
import { useMenuStore } from "@/store/menu";
    const menu = useMenuStore()
    const props = defineProps(['selectedYear', 'right'])
    const emit = defineEmits(['setDate'])
    const pickerIs = ref('year')
    const year = ref(props.selectedYear)
    const yearList = computed(() => {
        return Array.from({ length: 12 }, (_, i) => year.value - 5 + i);
    })
    
    const openYearPicker = () => {
        if(menu.name == 'cYearPicker'){
            menu.setMenu({ name: '', id: null})
            return
        }
        menu.setMenu( {name: 'cYearPicker', id:87})   
    }
        
    const setYear = (y) => {
        year.value = y
        emit('setDate', {year: y})
        menu.setMenu({ name: '', id: null})
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