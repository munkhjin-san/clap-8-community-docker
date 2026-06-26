<template>
    <div class="community-admin-window">
        <CommunityControl/>
        <AdminAccount/>
    </div>
</template>

<script setup lang="ts">
import AdminAccount from '../AdminAccount.vue';
import CommunityControl from './CommunityControl.vue';
</script>

<style scoped>
.community-admin-window{
    width: 100%;
    height: 100%;
    min-height: 0;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
</style>
