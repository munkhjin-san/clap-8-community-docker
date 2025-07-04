<template>
    <router-link style="font-size: 14px;" :to="`/${String(route.name)}?search_tags=${sanitized}`" class="jump-link">#{{ sanitized }}</router-link>
</template>
<script setup lang="ts">
import { Tag } from '@/interface/globalInterface';
import { computed } from 'vue';
import { useRoute } from 'vue-router';
    const route = useRoute()
    const props = defineProps<{
        tag: Tag
    }>()
    const sanitized = computed(() => {
        const sanitizedString = props.tag.text ? props.tag.text.replace(/#|♯|＃/g, '') : '';
        return sanitizedString;
    })
    

</script>