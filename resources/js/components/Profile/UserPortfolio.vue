<template>
<div style="margin-bottom: 20px;border: 1px solid #ccc;">
    <p class="record-inner" style="font-size: 16px;padding:5px 15px 0 15px"><strong>{{ portfolio.lesson_theme.title }}</strong></p>
    <div :style="{height: `${dynamicHeight}`, overflow: 'hidden', transition: 'height 0.1s ease'}" style="padding:0 15px 15px 15px;">        
        <div ref="portfolioBody">            
            <p class="record-inner"><strong>{{ portfolio.public_title }}</strong></p>
            <p class="record-inner">{{ portfolio.public_content }}</p>        
        </div>       
    </div>
    <div @click="toggleFull" class="jump-link" style="margin: 20px" v-if="dynamicHeight !== 'auto'">{{ dynamicHeight == '250px' ? '続きを表示する' : '閉じる' }}</div>                            
</div>

</template>
<script setup>
import { ref, onMounted } from 'vue';

const props = defineProps(['portfolio'])
const portfolioBody = ref(null)
onMounted(() => {
    if(portfolioBody.value?.clientHeight > 250){
        dynamicHeight.value = '250px'   
    }
                
})
const dynamicHeight = ref('auto')
const toggleFull = () => {
        dynamicHeight.value = dynamicHeight.value == '250px' ? `${portfolioBody.value?.clientHeight}px` : '250px'
    }
</script>