<template>
    <router-link style="font-size: 14px;" :to="`/${route.name}?search_tags=${sanitized}`" class="jump-link">#{{ sanitized }}</router-link>
</template>
<script setup>
import { computed } from 'vue';
import { useRoute } from 'vue-router';
    const route = useRoute()
    const props = defineProps(['tag'])
    const sanitized = computed(() => {
        const sanitizedString = props.tag.text ? props.tag.text.replace(/#|♯|＃/g, '') : '';
        return sanitizedString;
    })
    

</script>