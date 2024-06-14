<template>
<div style="margin-bottom: 20px;border: 1px solid #ccc;">

    <div class="p-header">
        <p class="record-inner" style="font-size: 16px;padding:5px 15px 0 15px"><strong>{{ portfolio?.lesson_theme?.title }}</strong></p>
        <div v-if="editable" class="boardMenuContainer cursor-pointer" @click.stop="menu.setMenu({name: 'portfolioBoxMenu', id: portfolio.id})" @touchstart.stop>                                            
            <svg version="1.1" xmlns="http://www.w3.org/2000/svg" class="dot-menu" height="13" viewBox="0 0 7 32" style="margin:auto;">
                <path d="M6.905 28.051c-0.011-0.447-0.114-0.881-0.275-1.273-0.039-0.1-0.085-0.196-0.135-0.287-0.047-0.093-0.096-0.185-0.153-0.27l-0.083-0.129-0.042-0.065-0.090-0.122c-0.036-0.051-0.102-0.135-0.143-0.182l-0.033-0.040c-0.095-0.111-0.2-0.214-0.319-0.302l-0.001-0.001-0.081-0.058-0.065-0.040-0.132-0.082c-0.086-0.057-0.178-0.104-0.273-0.152-0.092-0.049-0.188-0.096-0.289-0.132-0.392-0.164-0.829-0.262-1.277-0.273-0.896-0.026-1.818 0.321-2.465 0.963-0.653 0.634-1.041 1.546-1.042 2.464-0.003 0.456 0.083 0.907 0.238 1.316 0.154 0.41 0.465 0.877 0.744 1.194 0.281 0.32 0.76 0.57 1.169 0.728s0.86 0.245 1.316 0.245c0.917 0.007 1.831-0.388 2.465-1.038 0.641-0.648 0.993-1.567 0.968-2.461z"></path>
                <path d="M3.405 12.33c-0.447 0.013-0.881 0.115-1.272 0.278-0.1 0.038-0.195 0.085-0.287 0.135-0.093 0.047-0.185 0.097-0.27 0.154l-0.129 0.083-0.064 0.042-0.124 0.088c-0.050 0.039-0.132 0.104-0.181 0.145l-0.040 0.035c-0.111 0.096-0.214 0.202-0.302 0.319-0.001 0-0.001 0.001-0.001 0.001l-0.058 0.081-0.040 0.064-0.082 0.134c-0.056 0.086-0.104 0.179-0.15 0.271-0.049 0.095-0.095 0.189-0.132 0.289-0.164 0.394-0.262 0.832-0.27 1.277-0.025 0.899 0.324 1.82 0.967 2.467 0.636 0.651 1.549 1.038 2.465 1.037 0.456 0.003 0.906-0.086 1.315-0.239 0.41-0.156 0.781-0.374 1.112-0.619l0.188-0.188c0.246-0.331 0.463-0.701 0.619-1.112 0.157-0.408 0.245-0.858 0.245-1.315 0.003-0.918-0.392-1.832-1.043-2.465-0.648-0.639-1.567-0.991-2.464-0.961z"></path>
                <path d="M6.162 5.606c0.282-0.359 0.493-0.767 0.622-1.187 0.129-0.417 0.186-0.842 0.196-1.255l-0.035-0.263c-0.107-0.399-0.264-0.799-0.493-1.174-0.224-0.376-0.526-0.721-0.888-1-0.721-0.569-1.682-0.821-2.582-0.694-0.903 0.117-1.746 0.622-2.276 1.347-0.267 0.36-0.451 0.767-0.563 1.174-0.033 0.103-0.054 0.206-0.071 0.307-0.021 0.103-0.038 0.207-0.043 0.309l-0.015 0.152-0.007 0.078-0.003 0.096c-0.003 0.132-0.001 0.262 0.004 0.39l0.008 0.16c0.018 0.077 0.033 0.152 0.056 0.227l0.028 0.092 0.028 0.075 0.053 0.145c0.032 0.096 0.077 0.191 0.122 0.287 0.043 0.096 0.089 0.189 0.145 0.282 0.21 0.371 0.494 0.717 0.84 1.002 0.691 0.57 1.633 0.863 2.538 0.754 0.904-0.099 1.771-0.58 2.336-1.302z"></path>
            </svg>
        </div>
        
        <Transition name="modalFade">
        <div id="portfolioBoxMenu" class="boxMenu boardMenuIcon" v-if="menu.name == 'portfolioBoxMenu' && menu.id == portfolio.id" style="top: 25px;right: 40px;z-index:6;">
            <ul>
                <li @click="emit('editPortfolio')" class="boxMenuItems cursor-pointer">編集する</li>
            </ul>                                            
        </div>
        </Transition>
    </div>
    <div :style="{height: `${dynamicHeight}`, overflow: 'hidden', transition: 'height 0.1s ease'}" style="padding:0 15px 15px 15px;">        
        <div ref="portfolioBody">  
            <p class="record-inner"><strong>{{ portfolio.public_title }}</strong></p>               
            <slot name="user" :user="portfolio.user"></slot>      
            <p class="record-inner">{{ portfolio.public_content }}</p>        
        </div>       
    </div>
    <div @click="toggleFull" class="jump-link" style="margin: 20px" v-if="dynamicHeight !== 'auto'">{{ dynamicHeight == '250px' ? '続きを表示する' : '閉じる' }}</div>     
    <div style="display: flex;justify-content: flex-end;padding: 0px 15px 15px;">   
        <ClapButton @updateClap="emit('reload')" :item="portfolio" app-name="portfolio"/>
    </div>    
</div>

</template>
<script setup>
import { ref, onMounted, computed } from 'vue';
import ClapButton from '../Post/ClapButton.vue';
import { useMenuStore } from "@/store/menu";
import { useAuthUserStore } from '@/store/auth'

const props = defineProps(['portfolio'])
const emit = defineEmits(['reload', 'editPortfolio'])
const menu = useMenuStore()
const auth = useAuthUserStore()
const portfolioBody = ref(null)
const dynamicHeight = ref('auto')
const editTarget = ref(null)
onMounted(() => {
    if(portfolioBody.value?.clientHeight > 250){
        dynamicHeight.value = '250px'   
    }
                
})
const editable = computed(() => {
    const permitted = [608, 610]
    return props.portfolio.user_id == auth.activeUser.id || permitted.includes(auth.activeUser.id)
})
const toggleFull = () => {
    dynamicHeight.value = dynamicHeight.value == '250px' ? `${portfolioBody.value?.clientHeight}px` : '250px'
}

</script>
<style scoped>
.p-header{
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: relative;
    padding-right: 10px;
}
</style>