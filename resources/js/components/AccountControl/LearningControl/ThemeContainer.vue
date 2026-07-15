<template>
    <div class="admin-window">
        <LearningThemeTabs
            :theme="theme"
            :current-route-name="route.name"
            @back="router.push({ name: 'learningcontrol' })"
            @open-tab="(routeName) => router.push({ name: routeName })"
        />
        <div v-if="theme" class="theme-container__content">
            <router-view :theme="theme"></router-view>
        </div>
        
    </div>

</template>
<script setup lang="ts">
import { computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import type { LearningTheme } from '@/types/learning';
import LearningThemeTabs from './Shell/LearningThemeTabs.vue';
const props = defineProps<{
    themes: LearningTheme[]
}>()
const router = useRouter()
const route = useRoute()
const theme = computed(() => {
    const themeId = Array.isArray(route.params.themeId) ? route.params.themeId[0] : route.params.themeId
    return props.themes?.find(ob => Number(ob.id) === Number(themeId)) ?? null
})
</script>

<style scoped>
.theme-container__content{
    height: calc(100% - 110px);
    margin: 0 20px;
    padding: 20px 0;
    background: var(--background-color);
}
</style>
