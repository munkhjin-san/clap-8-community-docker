<template>
<div :class="['month-shifter', {'to-next-month' : jumpTo == 'down'}, {'to-prev-month' : jumpTo == 'up'}]">{{ jumpTo == 'down' ? '翌月へ' : '前月へ' }}</div>
</template>
<script>
export default{
    props: ['jumpTo'],
    emits: ['close'],
    mounted(){
        setTimeout(() => {
            this.$emit('close')
        }, 5000);
    }
}
</script>