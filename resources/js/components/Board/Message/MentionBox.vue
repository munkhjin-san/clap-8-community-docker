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
    const props = defineProps(['mentionAbleList', 'forced', 'fromProject'])
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
    function getCaretClientRect(): DOMRect | null {
        const sel = window.getSelection();
        if (!sel || sel.rangeCount === 0) return null;

        const r = sel.getRangeAt(0).cloneRange();
        r.collapse(true);

        // Try native rects first
        const rects = r.getClientRects();
        if (rects && rects.length) return rects[0];

        // Fallback: inject a temporary marker to measure
        const marker = document.createElement("span");
        // zero-width char keeps layout stable
        marker.textContent = "\u200B";
        marker.style.display = "inline-block";
        r.insertNode(marker);

        const rect = marker.getBoundingClientRect();
        marker.remove(); // cleanup
        return rect;
    }
    const mentionBoxPosition = () => {
        if(props.forced){
            return `bottom: 45px;left:0px;visibility:visible;width:fit-content;position:absolute`
        }else{
            const caret = getCaretClientRect();
            let x = caret ? caret.left : 0;
            let y = caret ? caret.top : 0;
            var leftM = x - 80;      
            leftM = leftM < 0 ? 10 : leftM      
            var messagePanel = window.innerHeight;
            var bottomM = messagePanel - y + 5 - keyboardStore.height; 
            const leftPanel = document.getElementById('leftPanel')
            if (leftPanel) {
                leftM = leftM - leftPanel.clientWidth
            } 
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