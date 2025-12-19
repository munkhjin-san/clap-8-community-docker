<template>
    <img :src="`/images/reactions/v2/${prefix}_${props.emoteId}.webp`" :style="{ height: props.emoteId == 8 ? percent57 + 'px' : props.size + 'px' }" class=" w-auto cursor-pointer"/>
</template>
<script setup lang="ts">
import { useTheme } from '@/store/theme';
import { computed, ref } from 'vue';

    const props = defineProps<{
        emoteId: number;
        size: number;
    }>();

    const percent57 = ref(props.size * 0.57);

    const theme = useTheme()
    const prefix = computed(() => theme.dark ? 'dark' : 'light')
</script>