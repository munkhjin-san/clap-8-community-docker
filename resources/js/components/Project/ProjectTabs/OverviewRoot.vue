<template>
    <div class="h-full overflow-y-auto">
        <div class="sub-tab-container p-5 bg-[var(--background-color)] pb-0" v-if="['project-overview-detail', 'project-overview-checkitems', 'project-overview-apply'].includes(route.name as string)">
            <router-link :to="{name: 'project-overview-detail'}" :class="{'selected-sub-tab': route.name === 'project-overview-detail'}" class="sub-tab-item no-underline hover:text-inherit hover:no-underline flex items-center gap-1">
                詳細
                <span v-if="checkItemConfirmBadge && overviewConfirm"
                    class="side-notification"
                    style="position: unset;"
                >
                    {{ checkItemConfirmBadge }}
                </span>
                <span v-if="detailCount"
                    class="side-notification side-notification--comment-only"
                    style="position: unset;"
                >
                    {{ detailCount }}
                </span>
            </router-link>
            <!-- <router-link v-if="hasPrivilage" :to="{name: 'project-overview-apply'}" :class="{'selected-sub-tab': route.name === 'project-overview-apply'}" class="sub-tab-item no-underline hover:text-inherit hover:no-underline">確認・申請</router-link> -->
            <router-link v-if="isManager || auth.isBoss || auth.isAdmin" :to="{name: 'project-overview-checkitems'}" :class="{'selected-sub-tab': route.name === 'project-overview-checkitems'}" class="sub-tab-item no-underline hover:text-inherit hover:no-underline flex items-center gap-1">
                確認事項
                <span v-if="checkItemConfirmBadge && selectedProject?.status == 'director_approved'"
                    class="side-notification"
                    style="position: unset;"
                >
                    {{ checkItemConfirmBadge }}
                </span>
                <span v-if="checklistCount"
                    class="side-notification side-notification--comment-only"
                    style="position: unset;"
                >
                    {{ checklistCount }}
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
import { User } from '@/interface/globalInterface';
import { useAuthUserStore } from '@/store/auth';
import { useBadgeStore } from '@/store/badge';
import { computed } from 'vue';
import { useRoute } from 'vue-router';
const route = useRoute()
const props = defineProps<{
    hasPrivilage: boolean
    userList: User[]
}>()
const { isManager, projectReportCheckBadge, checkItemConfirmBadge, selectedProject } = useProject()
const auth = useAuthUserStore()
const overviewConfirm = computed(() => {
    return selectedProject.value?.status === 'pending_director' || selectedProject.value?.status === 'returned'
})
const detailCount = computed(() => {
  return projectReportCheckBadge.value['詳細'] ?? 0
})

const checklistCount = computed(() => {
  return Object.entries(projectReportCheckBadge.value)
    .filter(([key]) => key !== '詳細')
    .reduce((total, [, count]) => total + count, 0)
})
</script>