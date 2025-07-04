<template>
    <td class="!border-r-0 text-[12px] no-hover t-cell cursor-default align-middle" :title="title" :class="{ 'isSaturday': isSaturday, 'special-day': isSunday || holidayName, 'cal-todayTitle': isToday }">
        <div>{{ dateInstance.toFormat('ccc') }}</div>
        <div class="mt-[5px]">{{ dateInstance.toFormat('d日') }}</div>                            
    </td>  
</template>
<script setup lang="ts">
import { DateTime } from 'luxon';
import { computed } from 'vue';
const props = defineProps<{
    date: string;
    holidays: {date: Date, name: string}[]
}>();

const dateInstance = computed(() => DateTime.fromISO(props.date)) ;

const isSaturday = computed(() => dateInstance.value.weekday === 6);
const isSunday = computed(() => dateInstance.value.weekday === 7);
const isoDate = computed(() => dateInstance.value.toISODate());
const isToday = computed(() => dateInstance.value.hasSame(DateTime.now(), 'day'));
const holidayName = computed(() => {
    const matched = props.holidays.find(h => 
        DateTime.fromJSDate(h.date).toISODate() === isoDate.value
    );
    return matched?.name ?? '';
});
const title = computed(() => `${dateInstance.value.toFormat('yyyy年M月d日')} (${dateInstance.value.toFormat('ccc')}) ${holidayName.value}`);
</script>
<style scoped>
td{
    border-bottom: solid 1px var(--calendarBorder);
}
</style>