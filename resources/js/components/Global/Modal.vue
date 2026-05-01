<template>
    <div class="overlay" @mousedown="canClose">   
        <div :class="['chatCreate', 'relative', 'bg-[var(--background-color)]', '!p-0', size, customClass]">      
            <Transition name="modalFade">            
                <div v-if="loader" class="absolute w-full h-full top-0 left-0 bg-inherit z-[6] flex items-center justify-center">          
                    <div class="spinner-mini" style="border-color: transparent rgb(134, 134, 134) rgb(134, 134, 134);"></div>     
                </div>  
            </Transition>               
            <div :class="['bg-inherit', !disableScroll ? 'scrollable h-full' : 'h-full flex flex-col overflow-hidden']" @mousedown.stop>            
                <div class="recordFormTitle flex-shrink-0 !px-[30px] !py-[20px] !w-[calc(100%-60px)]" style="display:flex">
                    <slot name="title"></slot>
                    <div class="ml-auto">
                        <slot name="menu"></slot>
                    </div>
                    <button class="bg-inherit w-[40px] min-w-[40px] h-[40px] flex items-center justify-center cursor-pointer -mr-[15px] z-[10]" @click="emit('close', false)">
                        <CloseIcon size="13"/>                      
                    </button>                 
                </div>
                <div :class="['w-[calc(100%-60px)] px-[30px] pb-[30px]', disableScroll ? 'flex-1 min-h-0' : '']" :style="props.bodyStyle ? props.bodyStyle : ''">
                    <slot name="content"></slot>
                </div>
            </div>
        </div>
    </div>
</template>
<script setup lang="ts">
import CloseIcon from '../Form/CloseIcon.vue';


const props = withDefaults(defineProps<{
    disableScroll?: boolean
    persist?: boolean
    bodyStyle?: string
    size?: 'medium' | 'large'
    loader?: boolean
    customClass?: string
}>(), {
    disableScroll: false,
    persist: false,
    bodyStyle: '',
    size: 'medium',
    loader: false,
    customClass: ''
})
const emit = defineEmits(['close'])
const canClose = (event: MouseEvent) => {
    if(!props.persist){
        emit('close', false)
    }else{
        event.stopPropagation()
    }
}
</script>
<style scoped>  
.medium{
    width: 60%;
    height: 70%;
}
.large{
    width: 80%;
    height: 85%;
}

@media screen and (max-width: 959px) {

    .chatCreate{
        width: 100% !important;
        height: 100% !important;
    }
}
</style>