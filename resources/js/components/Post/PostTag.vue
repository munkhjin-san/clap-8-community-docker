<template>
    <router-link style="font-size: 14px;" :to="`/${$route.name}?search_tags=${sanitized}`" class="jump-link">#{{ sanitized }}</router-link>
</template>
<script>
export default {
    props: ['tag'],
    computed:{
        sanitized(){
            const sanitizedString = this.tag.text.replace(/#|♯|＃/g, '');
            return sanitizedString;
        }
    }
}
</script>