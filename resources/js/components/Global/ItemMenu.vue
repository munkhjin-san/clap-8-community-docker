<template>
    <div class="rt">
        <div :class="['boardMenuContainer', {'active': active} ]" :title="type == 'share' ? 'シェア' : 'メニュー'" @click.stop="show"> 
            <svg v-if="type == 'share'" title="シェア" style="margin:auto;min-width: 3px;" xmlns="http://www.w3.org/2000/svg" height="11" id="Layer_2" class="dot-menu" data-name="Layer 2" viewBox="0 0 18.46 18.25">
                <path class="cls-1" d="m18.28,15.09c-.02-.07-.06-.13-.09-.19-.03-.06-.06-.12-.1-.18l-.05-.09-.03-.04-.04-.05c-.05-.07-.11-.14-.16-.2l-.07-.08s0,0,0,0c-.04-.03-.08-.06-.11-.09l-.05-.04-.04-.03-.09-.05c-.06-.04-.12-.07-.18-.1-.06-.03-.12-.06-.19-.09-.26-.11-.55-.17-.84-.18-.56-.02-1.14.2-1.57.58-.02.02-.05.02-.08.01-.49-.25-.99-.5-1.48-.75l-1.73-.86-1.73-.85-1.74-.83c-.58-.28-1.17-.54-1.75-.82-.51-.23-1.01-.47-1.52-.7-.05-.02-.08-.08-.07-.13,0-.07.01-.14.02-.21,0-.08,0-.16,0-.23,0-.05.02-.1.07-.12.5-.23,1.01-.46,1.51-.69.58-.27,1.17-.54,1.75-.82l1.74-.83,1.73-.85,1.73-.86c.49-.25.99-.5,1.48-.75.03-.01.06,0,.08.01.42.39,1,.6,1.57.58.3,0,.58-.07.84-.18.07-.02.13-.06.19-.09.06-.03.12-.06.18-.1l.09-.05.04-.03.05-.04s.08-.06.11-.09c0,0,0,0,0,0l.07-.08c.06-.06.11-.13.16-.2l.04-.05.03-.04.05-.09c.04-.06.07-.12.1-.18.03-.06.06-.12.09-.19.11-.26.18-.55.18-.84.02-.59-.21-1.2-.64-1.63-.21-.21-.46-.39-.74-.51C16.8.06,16.5,0,16.2,0c-.3,0-.6.06-.87.16-.27.1-.52.25-.73.41l-.07.05-.05.07c-.16.22-.31.46-.41.73-.1.27-.16.57-.16.87,0,.07,0,.13,0,.2,0,.03-.01.06-.04.07-.5.23-1,.46-1.49.69l-1.75.82-1.74.83-1.73.85c-.58.28-1.15.58-1.73.86-.48.24-.96.49-1.44.73-.09.04-.19.03-.27-.03-.16-.13-.34-.24-.54-.33-.28-.12-.58-.18-.88-.18-.3,0-.6.06-.87.16-.27.1-.52.25-.73.41l-.07.05-.05.07c-.16.22-.31.46-.41.73C.06,8.52,0,8.81,0,9.11c0,.61.26,1.21.69,1.63.43.42,1.04.66,1.63.64.3,0,.58-.07.84-.18.07-.02.13-.06.19-.09.06-.03.12-.06.18-.1l.09-.05.04-.03.05-.04s.01,0,.02-.01c.06-.04.13-.05.2-.02.5.26,1,.51,1.5.76.58.29,1.15.58,1.73.86l1.73.85,1.74.83,1.75.82c.49.23.99.46,1.48.68.04.02.06.05.05.09,0,.06,0,.12,0,.18,0,.3.06.6.16.87.1.27.25.52.41.73l.05.07.07.05c.22.16.46.31.73.41.27.1.57.16.87.16.3,0,.61-.06.88-.18.28-.12.53-.29.74-.51.42-.43.65-1.04.64-1.63,0-.3-.08-.58-.18-.84Z"/>
            </svg>       
            <svg v-else version="1.1" title="メニュー" xmlns="http://www.w3.org/2000/svg" height="13" class="dot-menu" viewBox="0 0 7 32" style="margin:auto;min-width: 3px;">
                <path d="M6.905 28.051c-0.011-0.447-0.114-0.881-0.275-1.273-0.039-0.1-0.085-0.196-0.135-0.287-0.047-0.093-0.096-0.185-0.153-0.27l-0.083-0.129-0.042-0.065-0.090-0.122c-0.036-0.051-0.102-0.135-0.143-0.182l-0.033-0.040c-0.095-0.111-0.2-0.214-0.319-0.302l-0.001-0.001-0.081-0.058-0.065-0.040-0.132-0.082c-0.086-0.057-0.178-0.104-0.273-0.152-0.092-0.049-0.188-0.096-0.289-0.132-0.392-0.164-0.829-0.262-1.277-0.273-0.896-0.026-1.818 0.321-2.465 0.963-0.653 0.634-1.041 1.546-1.042 2.464-0.003 0.456 0.083 0.907 0.238 1.316 0.154 0.41 0.465 0.877 0.744 1.194 0.281 0.32 0.76 0.57 1.169 0.728s0.86 0.245 1.316 0.245c0.917 0.007 1.831-0.388 2.465-1.038 0.641-0.648 0.993-1.567 0.968-2.461z"></path>
                <path d="M3.405 12.33c-0.447 0.013-0.881 0.115-1.272 0.278-0.1 0.038-0.195 0.085-0.287 0.135-0.093 0.047-0.185 0.097-0.27 0.154l-0.129 0.083-0.064 0.042-0.124 0.088c-0.050 0.039-0.132 0.104-0.181 0.145l-0.040 0.035c-0.111 0.096-0.214 0.202-0.302 0.319-0.001 0-0.001 0.001-0.001 0.001l-0.058 0.081-0.040 0.064-0.082 0.134c-0.056 0.086-0.104 0.179-0.15 0.271-0.049 0.095-0.095 0.189-0.132 0.289-0.164 0.394-0.262 0.832-0.27 1.277-0.025 0.899 0.324 1.82 0.967 2.467 0.636 0.651 1.549 1.038 2.465 1.037 0.456 0.003 0.906-0.086 1.315-0.239 0.41-0.156 0.781-0.374 1.112-0.619l0.188-0.188c0.246-0.331 0.463-0.701 0.619-1.112 0.157-0.408 0.245-0.858 0.245-1.315 0.003-0.918-0.392-1.832-1.043-2.465-0.648-0.639-1.567-0.991-2.464-0.961z"></path>
                <path d="M6.162 5.606c0.282-0.359 0.493-0.767 0.622-1.187 0.129-0.417 0.186-0.842 0.196-1.255l-0.035-0.263c-0.107-0.399-0.264-0.799-0.493-1.174-0.224-0.376-0.526-0.721-0.888-1-0.721-0.569-1.682-0.821-2.582-0.694-0.903 0.117-1.746 0.622-2.276 1.347-0.267 0.36-0.451 0.767-0.563 1.174-0.033 0.103-0.054 0.206-0.071 0.307-0.021 0.103-0.038 0.207-0.043 0.309l-0.015 0.152-0.007 0.078-0.003 0.096c-0.003 0.132-0.001 0.262 0.004 0.39l0.008 0.16c0.018 0.077 0.033 0.152 0.056 0.227l0.028 0.092 0.028 0.075 0.053 0.145c0.032 0.096 0.077 0.191 0.122 0.287 0.043 0.096 0.089 0.189 0.145 0.282 0.21 0.371 0.494 0.717 0.84 1.002 0.691 0.57 1.633 0.863 2.538 0.754 0.904-0.099 1.771-0.58 2.336-1.302z"></path>
            </svg>
        </div>
        <Transition name="modalFade"> 
            <div :id="unique.name" ref="menuRef" class="b-m" v-if="active" :style="{top: `${topAlign}px`}">
                <ul v-if="subItem == null" class="reset-bullet"> 
                    <li @click.stop="action(item)" v-for="item in items">
                        <span style="margin-right: 10px;">{{ item.title}}</span>
                        <Back v-if="item.children" size="10" style="transform:rotate(180deg);margin: auto;"/>
                    </li>
                </ul>
                <ul v-else  class="reset-bullet"> 
                    <li @click.stop="subItem = null;align()">
                    <span>戻る</span></li> 
                    <li @click.stop="action(item)" v-for="item in subItem.children">{{ item.title}}</li>
                </ul>
            </div>
        </Transition>
    </div>        
</template>
<script setup lang="ts">
import { MenuList } from "@/interface/globalInterface";
import { useMenuStore } from "@/store/menu";
import { useResponsive } from "@/store/responsive";
import { computed, ref, nextTick } from "vue";
import Back from "../Icons/Back.vue";
const menu = useMenuStore()
interface MenuProps {
    items: MenuList[]; 
    fit?: string | null;
    type?: string | null;
}
const props = defineProps<MenuProps>();

const subItem = ref<MenuList | null>()
const topAlign = ref(10)
const responsive = useResponsive()
const menuRef = ref<HTMLElement | null>(null);
const action = (item: MenuList) => {
    if(item.children){
        subItem.value = item
        align()
    }else{
        item.action()
        if(!menu.parent){
            menu.close()
        }   
    }
   
}

const unique = computed(() => {
    const characters = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
    const length = 8; // Length of the unique string
    let name = '';
    for (let i = 0; i < length; i++) {
        name += characters.charAt(Math.floor(Math.random() * characters.length));
    }
    const id = Math.floor(Math.random() * 100000000);
    return { id: id , name: name}
})

const show = () => {
    if(!props.items.length) return
    if(menu.parent || menu.name !== unique.value.name){
        menu.setMenu({name: unique.value.name, id: unique.value.id})
    }else{
        menu.close()
    }
    subItem.value = null
    align()
    
}
const align = () => {
    topAlign.value = 10
    setTimeout(() => {
        if(active.value){
            const parent = props.fit ? document.getElementById(props.fit) : null;
            if (parent && menuRef.value) {
                const parentRect = parent.getBoundingClientRect();
                const elRect = menuRef.value.getBoundingClientRect();
                if (elRect.bottom > parentRect.bottom) {
                    const diff = parentRect.bottom - elRect.bottom - 5;
                    if(diff < 0) {
                        topAlign.value = diff
                    }                    
                }
            }       
        }
    }, 0);
}
const longTapAction = (event) => {
        event.stopPropagation()
        const xPos = event.type === 'touchstart' ? Math.ceil(event.touches[0].clientX) : Math.ceil(event.clientX);
        const yPos = event.type === 'touchstart' ? Math.ceil(event.touches[0].clientY) : Math.ceil(event.clientY);        
        show()               
        nextTick(() => {
            const a = menuRef.value;                 
            if(a){
                const offset = responsive.mobile ? 40 : 10
                let l = xPos - 50 < 0 ? 10 : xPos - 50; 
                let t = yPos - a.clientHeight - offset < 0 ? yPos + offset : yPos - a.clientHeight - offset;
                a.style.position = 'fixed'
                a.style.top = t + 'px';
                a.style.left = l + 'px'; 
            }
        })   
            
    }
const active = computed(() => {
    return menu.name == unique.value.name && menu.id && menu.id == unique.value.id
})

defineExpose({show, longTapAction})
</script>
<style lang="scss" scoped>
.rt{
    position: relative;
}
.b-m{
    z-index: 99;
    width: fit-content;
    box-shadow: #3c40434d 0 1px 2px, #3c404326 0 2px 6px 2px;
    float: right;
    background-color: var(--background-color);
    position: absolute;
    right: 25px;
    min-width: 100px;
    li{
        font-size: 12px;
        background-color: var(--menu-bg);
        color: var(--primary-color);
        white-space: nowrap;
        fill: var(--primary-color);
        height: 30px;
        line-height: 30px;
        text-indent: 10px;
        transition: background-color 0.1s, color 0.1s, fill 0.1s;
        cursor: pointer;
        display: flex;
    }
    li:hover{
        background-color: var(--primary-color);
        color: var(--background-color);
        fill: var(--background-color);
    }
}
.active{
    background: var(--bg3)
}
</style>
