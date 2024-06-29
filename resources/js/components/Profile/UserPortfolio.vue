<template>
<div style="margin-bottom: 20px;border: 1px solid #ccc;">

    <div class="p-header">
        <p class="record-inner" style="font-size: 16px;padding:5px 15px 0 15px"><strong>{{ portfolio?.lesson_theme?.title }}</strong></p>
        <ItemMenu :items="[
            {title: '編集する', action:() => emit('editPortfolio')}
        ]"/>
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
import ItemMenu from '@/components/Global/ItemMenu.vue'
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