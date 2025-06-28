<template>
<div  class="left-panel-outer" style="overflow:hidden;position: absolute;
    left: 0;
    top: -1px;
    width: 100%;
    height: 100%;
    z-index: 10;">                    
    <div class="left-panel-inner cursor-pointer">                  
        <div class="left-panel-wrap" style="height:65px;" v-for="num in 50">       
            <div class="left-panel-items skeleton-items">
                <div class="sk-i1"></div>
                <div style="width:100%;align-self: center;margin:0 10px;overflow:hidden">
                    <p class="sk-t1" :style="{width: randomWidth()}"></p>
                    <div class="sk-t2 contentsText"  :style="{width: randomWidthLs()}"></div>
                </div>                                       
            </div>                                    
        </div>
    </div>                
</div> 
</template>
<script setup lang="ts">

    const randomWidth = () => {
        const x = window.innerWidth
        return x > 959 ? 
        Math.floor(Math.random() * (90 - 70 + 1)) + 70 + '%' : 
        Math.floor(Math.random() * (60 - 40 + 1)) + 40 + '%'
    }
    const randomWidthLs = () => {
        const x = window.innerWidth
        return x > 959 ? 
        Math.floor(Math.random() * (100 - 90 + 1)) + 90 + '%' : 
        Math.floor(Math.random() * (80 - 60 + 1)) + 60 + '%'
    }

</script>
<style lang="scss">
    
    .sk-t1{
        background:lightgray;
        height: 20px;
        margin-bottom: 5px;
        animation: pulse-bg 2s infinite;
        border-radius: 3px;
    }
    .sk-t2{
        margin-bottom:5px;
        overflow: hidden;
        height: 17px;
        animation: pulse-bg 2s infinite;
        border-radius: 3px;
        
    }
    .sk-i1 {
        min-width:45px;
        min-height:45px;
        border-radius:50px;
        animation: pulse-bg 1s infinite;
        
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