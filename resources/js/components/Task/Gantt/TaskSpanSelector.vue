<template>
    <div class="relative flex items-center gap-[20px]">
        <Teleport to="#taskMenuHeader">
            <div v-if="selectedYear || selectedMonth" class="flex items-center gap-[5px]">
                <button @click="setIncrementOrDecrement(-1)" 
                    class="flex items-center justify-center h-[30px] w-[30px] min-w-[30px] cursor-pointer">            
                    <Back style="cursor: inherit;" size="13"/>
                </button>
                <div @click.stop="menu.setMenu({parent: 'taskSpanSelector'})" class="relative cursor-pointer">{{ selectedMonth ? `${selectedYear}年${selectedMonth}月` : `${selectedYear}年` }}
                    <Transition name="slidePop">
                        <div id="taskSpanSelector" v-if="menu.parent == 'taskSpanSelector'" class="absolute top-[40px] -right-[50px] z-[15] shadow-me p-[10px] bg-[var(--background-color)] max-h-[200px] overflow-y-auto">
                            <div v-if="selectedYear && !selectedMonth" class="flex flex-col gap-[10px]">
                                <div class="grid grid-cols-3 gap-1 w-max">
                                    <div v-for="year in availableYears" :key="year" class="text-center min-w-[60px] transition-transform delay-100 cursor-pointer text-[13px] hover:font-semibold whitespace-nowrap py-[10px]" :class="{'font-semibold scale-[1.2]' : selectedMonth == year}" @click.stop="setSelectedYear(year)">{{ year }}年</div>
                                </div>
                            </div>
                            <div v-if="selectedYear && selectedMonth" class="flex flex-col gap-[10px]">
                                <div class="grid grid-cols-3 gap-1 w-max">
                                    <div v-for="month in 12" :key="month" class="text-center min-w-[40px] transition-transform delay-100 cursor-pointer text-[13px] hover:font-semibold whitespace-nowrap py-[10px]" :class="{'font-semibold scale-[1.2]' : selectedMonth == month}" @click.stop="setSelectedMonth(month)">{{ month }}月</div>
                                </div>
                            </div>  
                        </div>
                    </Transition>
                </div>
                <button @click="setIncrementOrDecrement(1)" 
                    class="flex items-center justify-center h-[30px] w-[30px] min-w-[30px] cursor-pointer">            
                    <Back style="cursor: inherit;" size="13" class="rotate-180"/>
                </button>
            </div>
        </Teleport>
        <div @click.stop="toggle" class="c-bar-button !text-[12px] whitespace-nowrap !px-[5px]">{{ selected }}</div>
        <!--  -->
    </div>
</template>
<script setup lang="ts">
import { computed, ref } from 'vue';
import { useMenuStore } from '@/store/menu';
import { Project, VirtualSpan } from '@/interface/projectInterface';
import { DateTime } from 'luxon';
import CommandButton from '@/components/Global/CommandButton.vue';
import Back from '@/components/Icons/Back.vue';
const props = defineProps<{
    project: Project
}>()
const emit = defineEmits<{
    update: [],
    reset: []
}>()
const menu = useMenuStore()
const expandedYear = ref<number | null>(null);

const selectedYear = defineModel<number | null>('year')
const selectedMonth = defineModel<number | null>('month')


const availableYears = computed<number[]>(() => {
    if (!props.project.date_start || !props.project.date_end) return [];
    
    const startYear = DateTime.fromISO(props.project.date_start).year;
    const endYear = DateTime.fromISO(props.project.date_end).year;
    const years: number[] = [];
  
    for (let year = startYear; year <= endYear; year++) {
        years.push(year);
    }

    return years;
});

const setSelectedMonth = (month: number) => {
    selectedMonth.value = month;
    menu.close()
}

const setSpan = (year:number | null, month:number | null, hasEmit: boolean) => {
    selectedYear.value = year;
    selectedMonth.value = month;
    if (hasEmit) {
        emit('reset')
    }
    menu.close()
}
const toggle = () => {
    if(!selectedYear.value && !selectedMonth.value) {
        setSpan(DateTime.now().year, null, false)
    }
    else if(!selectedMonth.value) {
        setSpan(DateTime.now().year, DateTime.now().month, false)
    }
    else {
        setSpan(null, null, true)
    }
}
    
const setIncrementOrDecrement = (direction: number) => {
    if (selectedYear.value && !selectedMonth.value) {
        selectedYear.value += direction;
    } else if (selectedYear.value && selectedMonth.value) {
        const instance = DateTime.fromObject({ year: selectedYear.value, month: selectedMonth.value });
        const accum = instance.plus({ months: direction });
        selectedYear.value = accum.year;
        selectedMonth.value = accum.month;        
    }
}

const setSelectedYear = (year: number) => {
    selectedYear.value = year;
    selectedMonth.value = null;
    menu.close()
}
const selected = computed(() => selectedYear.value ? (selectedMonth.value ? '月単位' : '年単位') : '期間 : すべて');
</script>
<style scoped>
.radio-group {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.radio-button {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
}

.radio-button input {
  display: none;
}

.custom-radio {
    width: 15px;
    height: 17px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: transparent;
    transition: border-color 0.3s ease;
    position: relative;
}

.custom-radio .checkmark-t {
  display: none;
  fill: var(--primary-color);
}

.radio-button input:checked + .custom-radio {
  border-color: green;
}

.radio-button input:checked + .custom-radio .checkmark-t {
  display: block;
}

.label-text {
  font-size: 13px;
}
</style>