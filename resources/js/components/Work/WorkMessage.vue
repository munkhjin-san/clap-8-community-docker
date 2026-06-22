<template>
    <div class="text-[13px] leading-normal">
        <p>            
            {{ tempData.date }}から{{ tempData.endDate }}までの計画有給
            <span class="text-[gray]">（残り{{ item.remaining_days }}日）</span>
        </p> 
        <div class="mt-2 ml-auto w-fit">
            <router-link class="jump-link text-sm" :to="{name: 'timesheet', query: { startDate: tempData.date }}">計画有給を入力</router-link>
        </div>        
    </div>
</template>
<script setup lang="ts">
import type { plannedLeave, tempData as PlannedLeaveTempData } from '../../interface/workInterface';
import { computed } from 'vue';
const emit = defineEmits(['close'])

const props = defineProps<{
    item: plannedLeave
}>()

const tempData = computed<PlannedLeaveTempData>(() => props.item.tempData)

</script>
