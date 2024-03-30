<template>
    <ul ref="innerMention" id="mentionedPc" class="mentionBox" :style="mentionBoxPosition()">
        <li 
            :id="'mentionAble_' + index" 
            :key="index" 
            @keyup.enter="mentionUser(user, index)"
            @click.stop.prevent="mentionUser(user, index)" 
            v-for="(user, index) in mentionAbleList" 
            class="mentionBox-inner" 
            :class="{mUsrSlctdPc: highlighted == index}
        ">                                    
            <div class="column-01">  
                <BoardIcon v-if="user.id == -1" imgClass="userMidIcon" :item="board"/> 
                <UserIcon :disableInstant="true" v-else size="30" :user="user" imgClass="userMidIcon"/>  
            </div>
            <p  class="cursor-pointer" style="padding:5px;font-size:13px;">{{user.name}}</p>                                   
        </li>                    
    </ul>
</template>
<script setup>
import { inject, onMounted, onUnmounted, ref, computed } from 'vue';
import BoardIcon from '../Mixed/BoardIcon.vue';
import UserIcon from '../Mixed/UserIcon.vue';
    const props = defineProps(['mentionAbleList'])
    const emit = defineEmits(['mentionUser'])
    const board = inject('openedBoard')
    const highlighted = ref(-1)
    const innerMention = ref(null)
    const keyboardHeight = inject('keyboardHeight')
    onUnmounted(() => {
        window.removeEventListener('keydown', mentionBoxNavigation);
        console.log('hiiiiiii')
    })
    onMounted(() => {
        window.addEventListener('keydown', mentionBoxNavigation);
    })
        const mentionBoxPosition = () => {
            let x = 0,
            y = 0;
            const isSupported = typeof window.getSelection !== "undefined";
            if (isSupported) {
                const selection = window.getSelection();
                if (selection.rangeCount !== 0) {
                    const range = selection.getRangeAt(0).cloneRange();
                    range.collapse(true);
                    const rect = range.getClientRects()[0];
                    if (rect) {
                        x = rect.left;
                        y = rect.top;
                    }
                }
            }
            var leftM = x - 80;      
            leftM = leftM < 0 ? 10 : leftM      
            var messagePanel = window.innerHeight;
            var bottomM = messagePanel - y + 5 - keyboardHeight.value;  
            const window_width = window.innerWidth
            const pc = window_width > 959
            const substract_from_left = pc ?  Math.floor(window_width * 0.2) : 0
            leftM = leftM - substract_from_left
            var result = 'left:' + leftM + 'px;' + 'bottom:' + bottomM + 'px;visibility:visible'     
            return result
        }
        const mentionUser = (user, index) => {
            emit('mentionUser', user, index)
        }
        const mentionBoxNavigation = (event) => { 
            if(innerMention.value && props.mentionAbleList.length){
                document.querySelectorAll("li").forEach((el) => {el.setAttribute('tabindex', 0)});
                if(event.which === 38 || event.which === 40){
                    event.preventDefault()
                }
                if(event.which === 38){
                    highlighted.value = highlighted.value == 0 ? props.mentionAbleList.length - 1 : highlighted.value - 1
                    if(highlighted.value == -1){
                        highlighted.value = props.mentionAbleList.length - 1
                    }
                    setTimeout(() => { document.querySelector("li.mUsrSlctdPc").focus() },0)                         
                }
                if(event.which === 40){//dooshoo                        
                    highlighted.value = highlighted.value == props.mentionAbleList.length - 1 ? 0 : highlighted.value + 1
                    setTimeout(() => { document.querySelector("li.mUsrSlctdPc").focus() },0)                          
                } 
            }
            
            
        }
        defineExpose({highlighted, mentionUser})
</script>