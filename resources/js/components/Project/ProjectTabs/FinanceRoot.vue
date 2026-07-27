<template>
    <div v-if="selectedProject" class="h-full relative bg-[var(--background-color)]">
        <div class="flex justify-between items-center p-4">
            <div class="sub-tab-container">
                <router-link :to="{name: 'income-expense'}" :class="['sub-tab-item', { 'selected-sub-tab': route.name === 'income-expense'}]" class="sub-tab-item no-underline hover:text-inherit hover:no-underline flex items-center gap-1">収支確認</router-link>
                <router-link v-if="selectedProject.has_actual_func" :to="{name: 'result'}" :class="['sub-tab-item', { 'selected-sub-tab': route.name === 'result'}]" class="sub-tab-item no-underline hover:text-inherit hover:no-underline flex items-center gap-1">実績管理</router-link>
                <router-link v-if="hasPrivilage" :to="{name: 'plan'}" :class="['sub-tab-item', { 'selected-sub-tab': route.name === 'plan'}]" class="sub-tab-item no-underline hover:text-inherit hover:no-underline flex items-center gap-1">損益</router-link>
            </div>
        </div>
        <router-view v-slot="{ Component }">
            <keep-alive>
                <component 
                    :is="Component" 
                    :hasPrivilage="hasPrivilage"
                    :year="DateTime.now().year"
                    :month="DateTime.now().month"
                />
            </keep-alive>
        </router-view>
    </div>
</template>
<script setup lang="ts">
import { useProject } from '@/composables/project';
import { DateTime } from 'luxon';
import { useRoute } from 'vue-router';
const props = defineProps<{
    hasPrivilage: boolean
}>()
const { selectedProject } = useProject()
const route = useRoute()
</script>