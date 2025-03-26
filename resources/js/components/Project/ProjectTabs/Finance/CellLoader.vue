<template>
    <td>
        <div class="flex items-center w-full justify-between">
            <span class="mobile">{{ cellNames[props.order] }}</span>
            <div class="sk-cell" :style="{width: randomWidthBetween()}"></div>
        </div>
    </td>
</template>
<script setup lang="ts">
const cellNames = [ 
    '',
    '売上',
    '販管費',
    '利益',
    '利益率'

]
const randomWidthBetween = () => {
    return `${Math.floor(Math.random() * (100 - 80 + 1) + 80)}px`
}
const props = defineProps<{
    order: number
}>()
</script>
<style lang="scss">
    
    .sk-cell{
        background:lightgray;
        height: 18px;
        animation: pulse-bg 2s infinite;
        border-radius: 3px;
        width: 60%;
    }
    @keyframes pulse-bg {
    0% {
        background-color: var(--skItem1);
    }
    50% {
        background-color: var(--skItem2);
    }
    100% {
        background-color: var(--skItem1);
    }
    }
</style>