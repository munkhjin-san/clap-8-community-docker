<template>
    <div class="historyContainer" style="background:var(--background-color);overflow:hidden">
        <div style="box-sizing: border-box;border: thin solid var(--formBorder);border-top:none !important;">        
            <div 
                @click.prevent.stop="setkey(item)" 
                class="historyItem" 
                style="height: 30px;line-height: 30px;display:flex;cursor:pointer;overflow:hidden;font-size: 12px;" 
                v-for="(item, index) in allHistoryData"
                :class="{selectedHistoryItem: selected == index}"
                @keyup.enter="setkey(item)"
            >
                    
                <span style="width: -webkit-fill-available; overflow: hidden; margin-right: 10px;margin-left:10px">{{item.content}}</span>
            </div>
        </div>
    </div>
</template>

<script setup>
    const props = defineProps(['allHistoryData', 'selected'])
    const emit = defineEmits(['setKeyWordFromHistory'])
    const setkey = (item) => {
        emit('setKeyWordFromHistory', item.content)
    }
       
</script>
<style>
    .selectedHistoryItem{
        background: #000;
        color:#fff
    }
</style>

