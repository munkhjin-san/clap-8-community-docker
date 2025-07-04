<template>
    <component :size="size" :is="iconComponent"></component>
</template>
<script lang="ts" setup>
    import { computed, defineAsyncComponent } from 'vue';
    const props = defineProps(['which', 'size'])
    const bank = [
        'Nice', 'Knowledge', 'Challenge', 'Note', 'Help', 'Glolympic'
    ]
    const iconComponent = computed(() => {
        return defineAsyncComponent(() => import(`../Icons/${bank[props.which]}.vue`));
    })
</script>