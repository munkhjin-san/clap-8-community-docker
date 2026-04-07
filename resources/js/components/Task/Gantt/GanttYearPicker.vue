<template>
    <div id="gMonthPicker" class="monthPicker">
        <div>            
            <div></div>
            <div @click.stop="openYearPicker" id="activateButton" class="g-y-pick" style="">{{ `${year}年` }}</div>
            <div></div>            
        </div>
        <div id="ganttYearPicker" class="month-grid right-[0]" v-if="menu.id == uniqueId && menu.name == 'ganttYearPicker'">            
            <div class="grid-container year-picker">
                <div @click.stop="setYear(y)" :id="`y_${y}`" :class="{thisYear : y == year}" v-for="y in yearList" class="grid-item">{{ y }}年</div>
            </div>
        </div>       
    </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'   
import { useMenuStore } from "@/store/menu";
import { Interval } from 'luxon';
const props = defineProps<{
    interval: Interval
}>()
const emit = defineEmits<{
    (e: 'setDate'): void
}>()

const year = defineModel()
const uniqueId = ref(Math.floor(100000 + Math.random() * 900000))
const menu = useMenuStore()

const yearList = computed(() => {
    const start = props.interval.start?.year;
    const end = props.interval.end?.year;
    if (!start || !end) return [];
    return Array.from({ length: end - start + 1 }, (_, i) => start + i);
});

const setYear = (y: number) => {
    menu.close()
    year.value = y
    emit('setDate')
}

const openYearPicker = () => {
    if(menu.name == 'ganttYearPicker'){
        menu.close()
        return
    }
    menu.setMenu( { name: 'ganttYearPicker', id: uniqueId.value})
}


</script>
