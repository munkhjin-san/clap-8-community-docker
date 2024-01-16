<template>
<div :class="['month-shifter', {'to-next-month' : jumpTo == 'down'}, {'to-prev-month' : jumpTo == 'up'}]">{{ jumpTo == 'down' ? '翌月へ' : '前月へ' }}</div>
</template>
<script setup>
import { onMounted } from 'vue';
    const props = defineProps(['jumpTo'])
    const emit = defineEmits(['close'])
    onMounted(() => {
        setTimeout(() => {
            emit('close')
        }, 5000);
    })

</script>