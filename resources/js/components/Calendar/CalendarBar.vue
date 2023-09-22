<template>
    <div class="c-bar-wrap">
        <div class="c-bar-button" style="margin-left: 15px;">メンバー</div>
        <div class="c-bar-button">施設</div>
        <div @click="$emit('jumpToday')" class="c-bar-button">本日</div>
    </div>
</template>
<script>
export default{
    emits: ['jumpToday']
}
</script>
<style>
.c-bar-wrap{
    display: flex;
    gap: 15px;
    height: 30px;
    margin: auto 0;
    font-size: 13px;
}
.c-bar-button{
    height: 30px;
    padding: 0 10px;
    display: flex;
    align-items: center;
    background: var(--primary-color);
    color: var(--background-color);
    cursor: pointer;
}
</style>