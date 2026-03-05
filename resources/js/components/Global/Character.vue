<template>
    <img 
        :src="`/images/reactions/v7/${prefix}_${emoteName}.webp`" 
        :srcset="`/images/reactions/v7/${prefix}_${emoteName}@2x.webp 2x`"
        :style="{ maxHeight: style  }" 
        class="w-auto cursor-pointer"
    />
</template>
<script setup lang="ts">
import { useTheme } from '@/store/theme';
import { oikawaMap } from '@/utils/tools';
import { computed, ref } from 'vue';



    const props = withDefaults(defineProps<{
        emoteName: string;
        multiple?: number;
    }>(), {
        multiple: 1,
    });
    
    const theme = useTheme()
    const prefix = computed(() => theme.dark ? 'dark' : 'light')
    const style = computed(() => {
        const found = oikawaMap.find(ob => ob.name === props.emoteName)
        if (!found) return
        return (parseFloat(found.size) * props.multiple) + 'px'
    })
</script>