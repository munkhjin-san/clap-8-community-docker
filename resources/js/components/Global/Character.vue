<template>
    <img 
        :src="`/images/reactions/v5/${prefix}_${props.emoteId}.webp`" 
        :srcset="`/images/reactions/v5/${prefix}_${props.emoteId}.webp 1x, /images/reactions/v5/${prefix}_${props.emoteId}@2x.webp 2x`"
        :style="{ maxHeight: sizeMap[emoteId] }" 
        class="w-auto cursor-pointer"
    />
</template>
<script setup lang="ts">
import { useTheme } from '@/store/theme';
import { computed, ref } from 'vue';

    const sizeMap = {
        1: '40px',
        2: '40px',
        3: '40px',
        4: '40px',
        5: '40px',
        6: '40px',
        7: '40px',
        8: '40px',
        9: '40px',
        10: '40px',
        11: '40px',
        12: '40px',
        13: '40px',
        14: '40px',
        15: '38px',
        16: '40px',
        17: '40px',
        18: '40px',
        19: '24px',
        20: '40px',
        21: '42px',
        22: '45px',
        23: '42px',
        24: '43px',
        25: '45px',
    }

    const props = defineProps<{
        emoteId: number;
        size: number;
    }>();

    const percent57 = ref(props.size * 0.57);

    const theme = useTheme()
    const prefix = computed(() => theme.dark ? 'dark' : 'light')
</script>