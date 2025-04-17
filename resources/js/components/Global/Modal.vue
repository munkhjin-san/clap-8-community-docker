<template>
    <div class="overlay" @mousedown="emit('close', false)">                         
        <div :class="['chatCreate', {'scrollable' : !disableScroll}]" @mousedown.stop>     
            <div class="recordFormTitle" style="display:flex">
                <slot name="title"></slot>
                <div class="ml-auto">
                    <slot name="menu"></slot>
                </div>
                <div class="w-[40px] min-w-[40px] h-[40px] flex items-center justify-center cursor-pointer -mr-[15px]" @mousedown="emit('close', false)">
                    <CloseIcon size="13"/>                      
                </div>                 
            </div>
            <div class="w-[calc(100%-2px)]">
                <slot name="content"></slot>
            </div>
            
        </div>
    </div>
</template>
<script setup lang="ts">
import CloseIcon from '../Form/CloseIcon.vue';

const props = defineProps<{
    disableScroll?: boolean
}>()
const emit = defineEmits(['close'])
</script>