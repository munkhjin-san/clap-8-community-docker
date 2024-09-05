<template>
    <div 
        :title="title ? title : '新規作成'" 
        id="boardCreate" 
        :class="[{'float-b': !plain}, {'inverse-float': inverse}, {'hiddenButton' : createHidden}, customClass]" 
        @click="emit('action')" 
        v-html="getIcon(type)"
        :style="{bottom: order ? `${(order * 20) + ((order - 1) * 35)}px` : '20px'}"
    >
    </div>
</template>


<script setup lang="ts">
import { getIcon } from 'assets/icons';
import { onUnmounted, onMounted, shallowRef } from 'vue';

    const props = defineProps<{
        hideOn?: string | HTMLElement | null;
        type: string;
        title?: string;
        order?: number;
        inverse?: boolean;
        plain?: boolean;
        customClass?: string
    }>()
    const emit = defineEmits<{
        action: []
    }>()
    const scrollPosition = shallowRef(0)
    const createHidden = shallowRef(false)
    onMounted(() => {
        setTimeout(() => {
            if(props.hideOn){
                const parent = typeof props.hideOn === 'string'  ? document.getElementById(props.hideOn) : props.hideOn instanceof HTMLElement ? props.hideOn : null                
                parent?.addEventListener('scroll', scrollListen)
            }
        }, 100);
        
    })
    onUnmounted(() => {
        if(props.hideOn){
            const parent = typeof props.hideOn === 'string'  ? document.getElementById(props.hideOn) : props.hideOn instanceof HTMLElement ? props.hideOn : null
            parent?.removeEventListener('scroll', scrollListen)
        }
    })

    const scrollListen = (event:Event) => {
        const target = event.target as HTMLElement
        var percent = 100 * target.scrollTop / (target.scrollHeight - target.clientHeight);       
        if(target.scrollTop < 0){
            createHidden.value = false            
        }else if(percent > 98){            
            createHidden.value = true
        }else{                 
            createHidden.value = target.scrollTop > scrollPosition.value
            scrollPosition.value = target.scrollTop;
        }
    }
</script>
<style lang="scss">
.float-b{
    z-index: 12;
    border-radius: 50%;
    width: 35px;
    height: 35px;
    text-align: center;
    display: flex;
    position: absolute;
    bottom: 20px;
    right: 20px;
    cursor: pointer;
    box-shadow: #3c40434d 0 1px 2px, #3c404326 0 2px 6px 2px;
    transition: all .2s;
    background: #efefef;
    align-items: center;
    justify-content: center;
    
}
.inverse-float{
    background: #000;
    
}
.float-b > svg{
    width: 15px;
    height: 15px;
    fill: #000;
}
.inverse-float > svg{
    fill: #fff;
}
</style>
