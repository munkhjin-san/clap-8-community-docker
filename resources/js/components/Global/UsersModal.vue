<template>
    <div class="overlay" @mousedown="emit('close', false)">                         
        <div :class="['users-list-popup', {'scrollable' : !disableScroll}]" @mousedown.stop>     
            <div class="flex mb-5">
                <slot name="title"></slot>
                <div class="ml-auto">
                    <slot name="menu"></slot>
                </div>
                <div class="flex items-center justify-center cursor-pointer" @mousedown="emit('close', false)">
                    <CloseIcon size="13"/>                      
                </div>                 
            </div>
            <slot name="content"></slot>
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