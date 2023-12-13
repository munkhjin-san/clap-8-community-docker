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
                <BoardIcon v-if="user.id == -1" imgClass="userMidIcon" :item="openedBoard"/> 
                <UserIcon :disableInstant="true" v-else size="30" :user="user" imgClass="userMidIcon"/>  
            </div>
            <p  class="cursor-pointer" style="padding:5px;font-size:13px;">{{user.name}}</p>                                   
        </li>                    
    </ul>
</template>
<script>
export default{
    props: ['mentionAbleList', 'openedBoard'],
    emits: ['mentionUser'],
    data(){
        return {
            highlighted : -1,
        }
    },
    unmounted() {
        window.removeEventListener('keydown', this.mentionBoxNavigation);
    },
    mounted() {
        window.addEventListener('keydown', this.mentionBoxNavigation);
    },
    computed:{
        
    },
    methods:{
        mentionBoxPosition(){
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
            var bottomM = messagePanel - y + 5 - this.$store.state.keyboardOffset;  
            const window_width = window.innerWidth
            const pc = window_width > 959
            const substract_from_left = pc ?  Math.floor(window_width * 0.2) : 0
            leftM = leftM - substract_from_left
            var result = 'left:' + leftM + 'px;' + 'bottom:' + bottomM + 'px;visibility:visible'     
            return result
        },
        mentionBoxNavigation(){ 
            if(this.$refs.innerMention && this.mentionAbleList.length){
                document.querySelectorAll("li").forEach((el) => {el.setAttribute('tabindex', 0)});
                if(event.which === 38 || event.which === 40){
                    event.preventDefault()
                }
                if(event.which === 38){
                    this.highlighted = this.highlighted == 0 ? this.mentionAbleList.length - 1 : this.highlighted - 1
                    if(this.highlighted == -1 && this.keyCharacters.length){
                        this.highlighted = this.mentionAbleList.length - 1
                    }
                    setTimeout(() => { document.querySelector("li.mUsrSlctdPc").focus() },0)                         
                }
                if(event.which === 40){//dooshoo                        
                    this.highlighted = this.highlighted == this.mentionAbleList.length - 1 ? 0 : this.highlighted + 1
                    setTimeout(() => { document.querySelector("li.mUsrSlctdPc").focus() },0)                          
                } 
            }
            
            
        },
        mentionUser(user, index){
            this.$emit('mentionUser', user, index)
        }
    }
}
</script>