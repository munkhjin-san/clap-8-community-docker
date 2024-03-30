<template>
    <div class="l-button" @click="clickHandler">
        <div>
            <span>{{ content }}</span>
            <div v-if="loading" class="l-spinner">
                <span class="l-loader"></span>
            </div>
            
        </div>
    </div>
</template>

<script setup>
    const props = defineProps(['loading', 'content'])
    const emit = defineEmits(['triggered'])
    const clickHandler = () => {
        if(props.loading) return
        emit('triggered')
    }
   
   
</script>
<style lang="scss">
    .l-button{
        background: var(--primary-button);
        color: #fff;
        font-size: 14px;
        white-space: nowrap;
        width: fit-content;
        margin:auto;
        position:relative;
        min-width: 100px;
        min-height: 35px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        padding: 0 15px;
        user-select: none;
    }
    .l-spinner{
        position: absolute;
        width: 100%;
        height: 100%;
        background-color: var(--primary-button);
        z-index: 5;
        display: flex;
        align-items: center;
        justify-content: center;
        top: 0;
        left: 0;
    }
    .l-loader {
        width: 25px;
        height: 25px;
        border-radius: 50%;
        animation: rotate 1s linear infinite;
        display: block;
        margin: auto;
    }
    .l-loader::before {
        content: "";
        box-sizing: border-box;
        position: absolute;
        inset: 0px;
        border-radius: 50%;
        border: 3px solid #FFF;
        animation: prixClipFix 2s linear infinite ;
    }

    @keyframes rotate {
      100%   {transform: rotate(360deg)}
    }

    @keyframes prixClipFix {
        0%   {clip-path:polygon(50% 50%,0 0,0 0,0 0,0 0,0 0)}
        25%  {clip-path:polygon(50% 50%,0 0,100% 0,100% 0,100% 0,100% 0)}
        50%  {clip-path:polygon(50% 50%,0 0,100% 0,100% 100%,100% 100%,100% 100%)}
        75%  {clip-path:polygon(50% 50%,0 0,100% 0,100% 100%,0 100%,0 100%)}
        100% {clip-path:polygon(50% 50%,0 0,100% 0,100% 100%,0 100%,0 0)}
    }
</style>