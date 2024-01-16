<template>
    <div :class="['hour-tc']">
        <div class="hour-tc-inner" :ref="`hour_item_${props.hour}`">{{ props.hour == '0:00' ? '' : props.hour }}</div>       
    </div>
</template>
<script setup>
const props = defineProps(['hour'])
</script>