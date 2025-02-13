<template>
    <div class="relative">
        <div @click.stop="menu.setMenu({parent: 'taskSpanSelector'})" class="c-bar-button !text-[12px] whitespace-nowrap !px-[5px]">{{ selected }}</div>
        <Transition name="slidePop">
            <div id="taskSpanSelector" v-if="menu.parent == 'taskSpanSelector'" class="absolute top-[40px] -right-[50px] z-[15] shadow-me p-[10px] bg-[var(--background-color)] max-h-[200px] overflow-y-auto">
                <div class="flex items-center gap-[10px] justify-end p-[5px] mb-[5px]">
                    <button class="bg-[var(--bg2)] text-[12px] px-[10px] py-[3px] whitespace-nowrap" @click="setSpan(null, null, true)">リセット</button>
                    <button class="bg-[var(--bg2)] text-[12px] px-[10px] py-[3px] whitespace-nowrap" @click="setSpan(DateTime.now().year, null, false)">今年</button>
                    <button class="bg-[var(--bg2)] text-[12px] px-[10px] py-[3px] whitespace-nowrap" @click="setSpan(DateTime.now().year, DateTime.now().month, false)">今月</button>
                </div>
                <div class="flex flex-col gap-[10px]">
                    <div v-for="block in selectableSpan" class="flex flex-col">
                        <div class="flex">
                            <div v-for="year in block" @click="selectedYear = selectedYear == year ? null : year" :key="year" class="p-[10px] hover:bg-[var(--bg3)] cursor-pointer" :class="{ 'bg-[var(--bg3)]': selectedYear == year }">
                                <div class="text-[13px] whitespace-nowrap">{{ year }}年</div>
                            </div>  
                        </div>
  
                        <div v-if="selectedYear && block.includes(selectedYear)">
                            <div class="grid grid-cols-3 gap-1 bg-[var(--bg3)]">
                                <div v-for="month in 12" :key="month" class="text-center transition-transform delay-100 cursor-pointer text-[13px] hover:font-semibold whitespace-nowrap py-[10px]" :class="{'font-semibold scale-[1.2]' : selectedMonth == month}" @click.stop="selectMonth(month)">{{ month }}月</div>
                            </div>
                        </div>  
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>
<script setup lang="ts">
import { computed, ref } from 'vue';
import { useMenuStore } from '@/store/menu';
import { Project, VirtualSpan } from '@/interface/projectInterface';
import { DateTime } from 'luxon';
import CommandButton from '@/components/Global/CommandButton.vue';
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


const selectableSpan = computed<number[][]>(() => {
    if (!props.project.date_start || !props.project.date_end) return [];
    
    const startYear = DateTime.fromISO(props.project.date_start).year;
    const endYear = DateTime.fromISO(props.project.date_end).year;
    const years: number[] = [];
  
    for (let year = startYear; year <= endYear; year++) {
        years.push(year);
    }

    // Group into chunks of 3
    const chunkedYears: number[][] = [];
    for (let i = 0; i < years.length; i += 3) {
        chunkedYears.push(years.slice(i, i + 3));
    }

    return chunkedYears;
});

const selectMonth = (month: number) => {
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

    



const selected = computed(() => {
    const title = '期間 : ';
    if (!selectedYear.value) {
        return title + 'すべて';
    }
    let period = `${selectedYear.value}年`;
    if (selectedMonth.value) {
        period += `${selectedMonth.value}月`;
    }
    return title + period;
})
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