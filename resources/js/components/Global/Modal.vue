<template>
    <div class="overlay" @mousedown="canClose">                         
        <div :class="['chatCreate', {'scrollable' : !disableScroll}, '!p-0', size]" @mousedown.stop>     
            <div class="recordFormTitle !px-[30px] !py-[20px] !w-[calc(100%-60px)]" style="display:flex">
                <slot name="title"></slot>
                <div class="ml-auto">
                    <slot name="menu"></slot>
                </div>
                <div class="w-[40px] min-w-[40px] h-[40px] flex items-center justify-center cursor-pointer -mr-[15px]" @mousedown="emit('close', false)">
                    <CloseIcon size="13"/>                      
                </div>                 
            </div>
            <div class="w-[calc(100%-60px)] px-[30px] pb-[30px]" :style="props.bodyStyle ? props.bodyStyle : ''">
                <slot name="content"></slot>
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
}>(), {
    disableScroll: false,
    persist: false,
    bodyStyle: '',
    size: 'medium'
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