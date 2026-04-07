<template>
    <div>
            
            <div>
                <YearPicker :selected-year="year" right="auto" @set-date="setDate"/>
            </div>
            <div class="si-box">
                <div v-for="(item, index) in paidHolidays" :key="index" class="p-[15px] mb-2.5 bg-[var(--bg3)] flex justify-between">
                    <p class="text-sm">{{DateTime.fromISO(item.shift_day.toString()).toFormat('y / M / d (ccc)', { locale: 'ja' })}}</p>
                    <p v-if="item.shift_day < DateTime.now().toISODate()" class="text-[tomato] text-sm">使用済み</p>
                </div>
            </div>
        
    </div>
</template>
<script lang="ts" setup>
import YearPicker from '@/components/Global/YearPicker.vue';
import { onMounted, ref } from 'vue';
import { DateTime } from 'luxon';
import { Shift } from '@/interface/workInterface';
import { useApi } from '@/composables/api';
const year = ref(DateTime.now().year)
const props = defineProps(['userId'])
const paidHolidays = ref<Shift[]>([])
const emit = defineEmits(['close'])
const api = useApi()
const setDate = (val: {year: number}) => {
    year.value = val.year
    getPlannedLeaves()
}
const getPlannedLeaves = async() => {

    const response = await api.post('/get_planned_leaves', {user_id: props.userId, year: year.value})
    paidHolidays.value = response

}
onMounted(() => {
    getPlannedLeaves()
})
</script>