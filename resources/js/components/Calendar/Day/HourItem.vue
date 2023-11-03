<template>
    <div :class="['hour-tc']">
        <div class="hour-tc-inner" :ref="`hour_item_${hour}`">{{ hour == '0:00' ? '' : hour }}</div>       
    </div>
</template>
<script>
export default{
    props: ['hour'],
    
}
</script>