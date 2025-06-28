<template>
    <ul ref="innerMention" id="mentionedPc" class="mentionBox reset-bullet" :style="mentionBoxPosition()">
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
                <BoardIcon v-if="user.id == -1 && openedBoard" size="30" :item="openedBoard"/> 
                <UserPanel :disableInstant="true" v-else size="30" :user="user" imgClass="userMidIcon"/>  
            </div>
            <p  class="cursor-pointer" style="padding:5px;font-size:13px;">{{user.name}}</p>                                   
        </li>                    
    </ul>
</template>
<script setup lang="ts">
import { inject, onMounted, onUnmounted, ref, computed } from 'vue';
import BoardIcon from '../Mixed/BoardIcon.vue';
import UserPanel from '@/components/Global/UserPanel.vue'
import { useKeyboardStore } from '@/store/keyboardStore';
import { useBoardList } from '@/composables/board';
    const props = defineProps(['mentionAbleList', 'forced'])
    const emit = defineEmits(['mentionUser', 'close'])
    const { openedBoard } = useBoardList()
    const highlighted = ref(-1)
    const innerMention = ref(null)
    const keyboardStore = useKeyboardStore()
    onUnmounted(() => {
        window.removeEventListener('keydown', mentionBoxNavigation);
        window.removeEventListener('click', clickHandler)
        
    })
    onMounted(() => {
        window.addEventListener('keydown', mentionBoxNavigation);
        window.addEventListener('click', clickHandler)
    })
    const clickHandler = (event) => {
        if(innerMention.value && !event.target.contains(innerMention.value)){
            emit('close')
        }
    }
    const mentionBoxPosition = () => {
        if(props.forced){
            return `bottom: 45px;left:0px;visibility:visible;width:fit-content`
        }else{
            let x = 0,
            y = 0;
            const isSupported = typeof window.getSelection !== "undefined";
            if (isSupported) {
                const selection = window.getSelection();
                if (selection && selection.rangeCount !== 0) {
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
            var bottomM = messagePanel - y + 5 - keyboardStore.height;  
            const window_width = window.innerWidth
            const pc = window_width > 959
            const substract_from_left = pc ?  Math.floor(window_width * 0.2) : 0
            leftM = leftM - substract_from_left
            var result = 'left:' + leftM + 'px;' + 'bottom:' + bottomM + 'px;visibility:visible'     
            return result
        }

    }
    const mentionUser = (user, index) => {
        emit('mentionUser', user, index)
    }
    const mentionBoxNavigation = (event) => { 
        if(innerMention.value && props.mentionAbleList.length){
            document.querySelectorAll("li").forEach((el) => {el.setAttribute('tabindex', '0')});
            if(event.which === 38 || event.which === 40){
                event.preventDefault()
            }
            if(event.which === 38){
                highlighted.value = highlighted.value == 0 ? props.mentionAbleList.length - 1 : highlighted.value - 1
                if(highlighted.value == -1){
                    highlighted.value = props.mentionAbleList.length - 1
                }
                focus()                     
            }
            if(event.which === 40){//dooshoo                        
                highlighted.value = highlighted.value == props.mentionAbleList.length - 1 ? 0 : highlighted.value + 1
                focus()                           
            } 
        }
        
        
    }
        const focus = () => {
        setTimeout(() => { 
            const el = document.querySelector("li.mUsrSlctdPc") as HTMLElement
            if(el){
                el.focus() 
            }                
        },0) 
    }
    defineExpose({highlighted, mentionUser})
</script>