<template>
    <div class="h-full">
        <div class="sub-tab-container p-5 bg-[var(--background-color)]" v-if="['project-overview-detail', 'project-overview-checkitems', 'project-overview-apply'].includes(route.name as string)">
            <router-link :to="{name: 'project-overview-detail'}" :class="{'selected-sub-tab': route.name === 'project-overview-detail'}" class="sub-tab-item no-underline hover:text-inherit hover:no-underline flex items-center gap-1">
                詳細
                <span v-if="checkItemConfirmBadge"
                    class="side-notification"
                    style="position: unset;"
                >
                    {{ checkItemConfirmBadge }}
                </span>
            </router-link>
            <!-- <router-link v-if="hasPrivilage" :to="{name: 'project-overview-apply'}" :class="{'selected-sub-tab': route.name === 'project-overview-apply'}" class="sub-tab-item no-underline hover:text-inherit hover:no-underline">確認・申請</router-link> -->
            <router-link v-if="isManager || auth.isBoss || auth.isAdmin" :to="{name: 'project-overview-checkitems'}" :class="{'selected-sub-tab': route.name === 'project-overview-checkitems'}" class="sub-tab-item no-underline hover:text-inherit hover:no-underline flex items-center gap-1">
                確認事項
                <span v-if="projectReportBadge"
                    class="side-notification side-notification--comment-only"
                    style="position: unset;"
                >
                    {{ projectReportBadge }}
                </span>
            </router-link>
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
import { useProject } from '@/composables/project';
import { useAuthUserStore } from '@/store/auth';
import { useBadgeStore } from '@/store/badge';
import { computed } from 'vue';
import { useRoute } from 'vue-router';
const route = useRoute()
const props = defineProps<{
    hasPrivilage: boolean
}>()
const { isManager, projectReportBadge, checkItemConfirmBadge } = useProject()
const auth = useAuthUserStore()
const badge = useBadgeStore()

</script>