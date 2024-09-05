<template>
    <div class="post-header">
        <HamBurger v-if="responsive.mobile"/>
        <div class="project-search-wrap">
            <PostSearchBar 
                className="newChatMemberSearch" 
                :customPlaceHolder="`プロジェクト検索`" 
                v-model="keywords"
            />                
        </div>
    </div>
</template>
<script setup lang="ts">

</script>