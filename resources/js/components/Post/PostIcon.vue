<template>
    <component :size="size" :is="iconComponent"></component>
</template>
<script lang="ts" setup>
    import { computed } from 'vue';
    import Nice from '../Icons/Nice.vue';
    import Knowledge from '../Icons/Knowledge.vue';
    import Challenge from '../Icons/Challenge.vue';
    import Note from '../Icons/Note.vue';
    import Help from '../Icons/Help.vue';
    import Glolympic from '../Icons/Glolympic.vue';
    import Refresh from '../Icons/Refresh.vue';

    const props = defineProps(['which', 'size'])

    const bank = [Nice, Knowledge, Challenge, Note, Help, Glolympic, Refresh]

    const iconComponent = computed(() => bank[Number(props.which)] ?? null)
</script>