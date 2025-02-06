<template>
    <component :size="which == 2 ? size + 12 : size" :is="iconComponent"></component>
</template>
<script setup>
import {defineAsyncComponent, computed} from 'vue'
const props = defineProps(['which', 'size'])
const bank = [
    'Rainbow', 'Sun', 'Cloud', 'Umbrella', 'Snowman', 'Lightning'
]
const iconComponent = computed(() => {
    return defineAsyncComponent(() => import(`../Icons/${bank[props.which]}.vue`));
})
</script>