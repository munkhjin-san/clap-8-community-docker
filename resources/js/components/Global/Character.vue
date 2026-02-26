<template>
    <img 
        :src="`/images/reactions/v6/${prefix}_${emoteId}.webp`" 
        :srcset="`/images/reactions/v5/${prefix}_${emoteId}.webp 1x, /images/reactions/v5/${prefix}_${emoteId}@2x.webp 2x`"
        :style="{ maxHeight: oikawaSizeMap[emoteId] }" 
        class="w-auto cursor-pointer"
    />
</template>
<script setup lang="ts">
import { useTheme } from '@/store/theme';
import { oikawaSizeMap } from '@/utils/tools';
import { computed, ref } from 'vue';



    const props = defineProps<{
        emoteId: number;
        size: number;
    }>();

    const percent57 = ref(props.size * 0.57);

    const theme = useTheme()
    const prefix = computed(() => theme.dark ? 'dark' : 'light')
</script>