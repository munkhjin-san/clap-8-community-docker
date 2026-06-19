<template>
    <component :size="size" :is="iconComponent"></component>
</template>
<script lang="ts" setup>
    import { computed } from 'vue';
    import CostIcon from '../Icons/CostIcon.vue';
    import CommentIcon from '../Icons/CommentIcon.vue';
    import MyCarIcon from '../Icons/MyCarIcon.vue';
    import ResultIcon from '../Icons/ResultIcon.vue';
    import WheelIcon from '../Icons/WheelIcon.vue';
    import YenIcon from '../Icons/YenIcon.vue';
    import IncidentIcon from '../Icons/IncidentIcon.vue';
    
    const props = defineProps<{ which: string; size?: string | number }>()

    const bank = { 'expenses': CostIcon, 'comment': CommentIcon, 'mileage': MyCarIcon, 'actual': ResultIcon, 'vehicle': WheelIcon, 'allowance': YenIcon, 'incident': IncidentIcon }

    const iconComponent = computed(() => bank[props.which as keyof typeof bank] ?? null)
</script>