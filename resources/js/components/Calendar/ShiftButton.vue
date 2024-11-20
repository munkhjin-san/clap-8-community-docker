<template>
<div :class="['month-shifter', `to-${jumpTo}-month`]">{{ directions[jumpTo] }}</div>
</template>
<script setup>
import { onMounted, ref } from 'vue';

    const props = defineProps(['jumpTo', 'viewType'])
    const emit = defineEmits(['close'])
    const directions = ref({
        up: '前月へ',
        down: '翌月へ',
        left:  props.viewType == 3 ? '前日へ' : '前月へ' ,
        right: props.viewType == 3 ? '翌日へ' : '翌月へ'
    })
    onMounted(() => {
        setTimeout(() => {
            emit('close')
        }, 5000);
    })


</script>
<style scoped>
.month-shifter{
    z-index: 15;
    margin: auto;
    position: absolute;
    color: var(--background-color);
    background: var(--primary-color);
    height: 25px;
    width: fit-content;
    padding: 0px 30px;
    text-align: center;
    border-radius: 13px;
    font-size: 12px;
    line-height: 2;
    cursor: pointer;
    user-select: none;

}
.to-up-month{
    bottom: 20px;
    left: 0;
    right: 0;
}
.to-down-month{
    top: 100px;
    left: 0;
    right: 0;
}
.to-left-month{
    left: 50px;
    top: 0;
    bottom: 0;
}
.to-right-month{
    right: 10px;
    top: 0;
    bottom: 0;
}
</style>