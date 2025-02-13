<template>
<div ref="dateItem" class="flex-[1] text-center h-full items-center hover:bg-[var(--bg3)] cursor-pointer flex justify-center min-w-[60px]">
    {{ `${date.value}${date.unit}` }}
</div>
</template>
<script setup lang="ts">
import { useResponsive } from '@/store/responsive';
import { DateTime } from 'luxon';
import { onMounted, useTemplateRef } from 'vue';

const props = defineProps<{
    date: {
        value: number,
        unit: string
    },
    selectedYear: number | null,
    selectedMonth: number | null,
}>()

const emit = defineEmits<{
    setLine: [value: number]
}>()
const dateItem = useTemplateRef('dateItem')
const responsive = useResponsive()
onMounted(() => {
    console.log('mounted block')
    const today = DateTime.now()
    setTimeout(() => {
        const now = DateTime.now()
        if(!dateItem.value) return
        if(props.date.unit === '月' && props.selectedYear === today.year && props.date.value === today.month) {
            const daysInYear = now.isInLeapYear ? 366 : 365;
            const percentage = (now.ordinal / daysInYear) * 100;
            emit('setLine', percentage);

        }
        if(props.date.unit === '日' && props.selectedYear === today.year && props.selectedMonth === today.month && props.date.value === today.day) {
            const hoursPassed = ((today.day - 1) * 24) + now.hour + now.minute / 60;
            const totalHours = now.daysInMonth * 24;
            const percentage = (hoursPassed / totalHours) * 100;
            emit('setLine', percentage);
        }
    }, 0);


})
</script>