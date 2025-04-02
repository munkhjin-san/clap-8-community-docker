<template>
    <div id="scheduleCreateFast" :style="{left: `${data.x - 15}px`, top: `${data.y -20}px`, minWidth: '35px'}" class="fastCreateButton">    
        <AddIcon size="12" style="fill:inherit"/>
        {{ date }}
    </div>
</template>
<script setup lang="ts">
import { FastCreateData } from '@/interface/calendarInterface';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { DateTime } from 'luxon';
import AddIcon from '../Form/AddIcon.vue';
    const props = defineProps<{data: FastCreateData}>()
    const myInterval = ref<any>(null)
    const emit = defineEmits(['close'])
    onMounted(() => {
        myInterval.value = setInterval(timer, 1000); 
    })
    onUnmounted(() => {
        clearInterval(myInterval.value);
    })
    const timer = () => {
        if(!props.data.stamp?.isValid) return
        const differenceInSeconds = DateTime.now().diff(props.data.stamp, 'seconds').as('seconds');
        if(differenceInSeconds >= 4){
            // emit('close')
        }
    }
    const date = computed(() => {
        const withTime = DateTime.fromFormat(props.data.time, 'yyyy-MM-dd HH:mm:ss');
        const dateOnly = DateTime.fromISO(props.data.time);
        return withTime.isValid ? withTime.toFormat('yyyy-MM-dd HH:mm') : dateOnly.toFormat('yyyy-MM-dd')
    })   
</script>