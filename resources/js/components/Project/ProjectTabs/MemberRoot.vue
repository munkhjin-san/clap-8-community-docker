<template>
    <div class="h-full">
        <div class="sub-tab-container p-5 bg-[var(--background-color)]" v-if="['project-member-list', 'project-member-role', 'project-member-assign'].includes(route.name as string)">
            <router-link :to="{name: 'project-member-list'}" :class="{'selected-sub-tab': route.name === 'project-member-list'}" class="sub-tab-item no-underline hover:text-inherit hover:no-underline">一覧</router-link>
            <router-link :to="{name: 'project-member-role'}" :class="{'selected-sub-tab': route.name === 'project-member-role'}" class="sub-tab-item no-underline hover:text-inherit hover:no-underline">役割</router-link>
            <router-link v-if="auth.isBoss || auth.isAdmin || auth.isPM" :to="{name: 'project-member-assign'}" :class="{'selected-sub-tab': route.name === 'project-member-assign'}" class="sub-tab-item no-underline hover:text-inherit hover:no-underline">適合評価</router-link>
        </div>
        <router-view v-slot="{ Component }">
            <keep-alive>
                <component :is="Component" />
            </keep-alive>
        </router-view>
    </div>    
</template>
<script setup lang="ts">
import { useProject } from '@/composables/project';
import { useAuthUserStore } from '@/store/auth';
import { useRoute } from 'vue-router';
const route = useRoute()
const props = defineProps(['userList'])
const auth = useAuthUserStore()
const { isManager } = useProject()

</script>