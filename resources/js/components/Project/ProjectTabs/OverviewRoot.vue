<template>
    <div class="h-full">
        <div class="sub-tab-container p-5 bg-[var(--background-color)]" v-if="['project-overview-detail', 'project-overview-checkitems', 'project-overview-apply'].includes(route.name as string)">
            <router-link :to="{name: 'project-overview-detail'}" :class="{'selected-sub-tab': route.name === 'project-overview-detail'}" class="sub-tab-item no-underline hover:text-inherit hover:no-underline">詳細</router-link>
            <!-- <router-link v-if="hasPrivilage" :to="{name: 'project-overview-apply'}" :class="{'selected-sub-tab': route.name === 'project-overview-apply'}" class="sub-tab-item no-underline hover:text-inherit hover:no-underline">確認・申請</router-link> -->
            <!-- <router-link v-if="hasPrivilage" :to="{name: 'project-overview-checkitems'}" :class="{'selected-sub-tab': route.name === 'project-overview-checkitems'}" class="sub-tab-item no-underline hover:text-inherit hover:no-underline">
                確認事項
            </router-link> -->
        </div>
        <router-view v-slot="{ Component }">
            <keep-alive>
                <component 
                    :is="Component" 
                    :hasPrivilage="hasPrivilage"
                />
            </keep-alive>
        </router-view>
    </div>    
</template>
<script setup lang="ts">
import { useRoute } from 'vue-router';
const route = useRoute()
const props = defineProps<{
    hasPrivilage: boolean
}>()
</script>