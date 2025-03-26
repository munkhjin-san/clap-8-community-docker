<template>

    <div v-if="info" class="mini-info">    
        <span>{{ info }}</span>
    </div>  
    <div v-else class="cu-toast-mask">
        <div ref="cuToastCont" class="cu-toast-container">
            <div class="cu-toast-inner">
                <div v-if="confirm || notify">
                    <div v-html="confirm || notify"></div>
                </div>
                <div style="display:flex;gap:20px;justify-content: space-evenly;margin: 20px 0 0 0;">
            
                    <div
                        @click="sendAnswer(answer, index)"  
                        :key="index" 
                        v-for="(answer, index) in options?.answers"
                        :style="{transform: `scale(${selected === index ? '1.3': '1'})`}" 
                        class="cu-answer-button"
                    >
                        {{ answer.label }}
                    </div>
                </div>               
            </div>
        </div>
    </div>

</template>


<script setup lang="ts">
import {  ref } from 'vue';
import { Answer, ConfirmOptions } from '@/interface/globalInterface'
interface Props {
    confirm: string | null;
    notify: string | null;
    info: string | null;
    options: ConfirmOptions | null;
}

const props = defineProps<Props>()
const cuToastCont = ref(null)
const selected = ref()
const emit = defineEmits<{
    (e: 'close'): void
    (e: 'handle', answer: Answer): void
}>()
const sendAnswer = (answer: Answer, index: number) => {
    selected.value = index        
    emit('handle', answer)             
    setTimeout(() => {
        emit('close')   
    }, 50);
    
}

</script>
<style lang="scss" scoped>
$primary: #626262;
$secondary: #fff;

.cu-toast-container {
font-size: 14px;
background: var(--background-color);
max-width: 40%;
line-height: 1.5;
white-space: break-spaces;
color:var(--primary-color);
fill:var(--primary-color);
}

.cu-toast-inner {
position: relative;
padding: 20px;
}

.toast-close-button {
position: absolute;
right: 5px;
top: 5px;
border-radius: 50px;
width: 20px;
height: 20px;
display: flex;
background-color: inherit;
cursor: pointer;
color: $secondary;
transition: background-color 0.2s, color 0.2s;
}

.toast-close-button:hover {
background-color: var(--primary-color);
fill: var(--background-color);
}

.toast-close-button>svg {
width: 10px;
height: 10px;
transition: fill 0.2s;
margin: auto;
}

.cu-answer-button {
background-color: var(--primary-button);
color: #ffffff;
padding: 10px 20px;
cursor: pointer;
font-size: 13px;
transition: transform 0.1s;
}

.cu-toast-mask {
position: fixed;
width: 100%;
height: 100%;
background: var(--overlay);
display: flex;
align-items: center;
justify-content: center;
flex-direction: column;
z-index: 54;
left: 0;
top: 0;
}
.mini-info {
position: fixed;
right: 0;
top: 10px;
background-color: green;
display: flex;
gap: 5px;
padding: 10px;
align-items: center;
z-index: 105;
color: #fff;
font-size: 13px;
left: 0;
width: -moz-fit-content;
width: fit-content;
margin: 0 auto;
white-space: nowrap;
}
@media screen and (max-width: 959px) {
.cu-toast-container {
    max-width: 90%;

}
}
</style>
