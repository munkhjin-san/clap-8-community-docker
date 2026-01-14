<template>
    <div class="help-container">
        <div class="help-container-inner">
            <div class="p-4">            
                <div class="admin-command-bar">            
                    <div class="sub-tab-container">
                        <router-link to="/help/tutorial" class="sub-tab-item" :class="{ 'selected-sub-tab': route.fullPath.includes('tutorial')}">チュートリアル</router-link>
                    </div>          
                </div>
            </div>
            <div>
                <router-view class="help-content"/>
            </div>            
        </div>
    </div>
</template>
<script setup lang="ts">
import { useRoute } from 'vue-router';

const route = useRoute();
</script>
<style>
.help-container {
    display: flex;
    height: 100%;
    width: 100%;
    padding: 20px;
    color: var(--primary-color);
}
.help-container-inner{
    background: var(--background-color);
    width: 100%;
}
.help-menu {
    display: flex;
}
.help-menu-item {
    padding: 15px;
    text-decoration: none;
    color: #333;
}
.help-menu-item.active {
    background-color: #f0f0f0;
    font-weight: bold;
}
</style>