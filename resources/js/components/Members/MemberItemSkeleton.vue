<template>
    <div class="memberItemContainer">
        <div class="memberInnerBox">
            <div class="memberInnerTop">
                <div>
                    <div :style="{width: randomWidth()}" class="sk-i2">
                    </div>
                </div>
                
                <div :style="{width: randomWidth()}" class="sk-t2" style="height:14px"></div>
            </div>
            <div v-for="num in randomLine" style="margin-top:10px;height:14px" :style="{width: randomWidth()}" class="sk-t2"></div>
        </div>
    </div>
</template>

<script>
    export default {
        mounted() {
            
        },
        computed:{
            randomLine(){
                return Math.floor(Math.random() * 6) + 1;
            }
        },
        methods: {
            randomWidth(){
                const x = window.innerWidth || document.clientWidth || body.clientWidth
                return x > 959 ? 
                Math.floor(Math.random() * (90 - 70 + 1)) + 30 + '%' : 
                Math.floor(Math.random() * (60 - 40 + 1)) + 40 + '%'
            },
            randomWidthLs(){
                const x = window.innerWidth || document.clientWidth || body.clientWidth
                return x > 959 ? 
                Math.floor(Math.random() * (100 - 90 + 1)) + 90 + '%' : 
                Math.floor(Math.random() * (80 - 60 + 1)) + 60 + '%'
            },
        }
    }
</script>
<style lang="scss">
    .memberItemContainer{
        background: var(--background-color);
        font-size:14px;
        width: 100%;
        height: 100%;
        color: var(--primary-color);
    }
    .memberInnerBox{
        padding: 15px;
    }
    .memberInnerTop{
        display: flex;
        width: 100%;
        align-items: center;
        gap: 10px;
    }
    .memberTag{
        margin-top: 15px;
    }
    .sk-i2 {
        min-width:30px;
        min-height:30px;
        border-radius:50px;
        animation: pulse-bg 1s infinite;
        
    }
</style>
