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
import { useResponsive } from '@/store/responsive';
import { inject, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { DateTime } from 'luxon';
import axios from 'axios';
import { Dialog } from '@/interface/globalInterface';
import { Shift } from '@/interface/workInterface';
const responsive = useResponsive()
const router = useRouter()
const year = ref(DateTime.now().year)
const props = defineProps(['userId'])
const paidHolidays = ref<Shift[]>([])
const { notify, info } = inject<Dialog>('dialog')!
const route = useRoute()
const emit = defineEmits(['close'])
const setDate = (val) => {
    year.value = val.year
    getPlannedLeaves()
}
const getPlannedLeaves = async() => {
    try{
        const response = await axios.post('/get_planned_leaves', {user_id: props.userId, year: year.value})
        paidHolidays.value = response.data
    } catch (e){
        notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
    }
}
onMounted(() => {
    getPlannedLeaves()
})
</script>