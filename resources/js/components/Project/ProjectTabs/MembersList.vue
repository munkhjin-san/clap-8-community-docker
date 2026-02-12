<template>
    <!-- <router-view v-slot="{ Component }">
        <component
            :key="memberData?.id"
            :is="Component"
        />
    </router-view> -->
    <div class="h-[calc(100%-75px)] relative">
        <div class="bg-[var(--background-color)] h-full pc px-5">
            <div class="project-table-container" style="border: none;">
                <div class="project-table">
                    <div class="project-header-row">
                        <div class="project-cell cell-width">メンバー</div>
                        <div class="project-cell cell-width">雇用形態</div>
                        <div class="project-cell cell-width">職階</div>
                        <div class="project-cell cell-width">メンター</div>
                        <div class="project-cell cell-width">職務レベル</div>                        
                        <div class="project-cell cell-width">成果目標・昇給課題</div>
                        <div class="project-cell cell-width">人事考課</div>
                        <div v-if="auth.hasPrivilage" class="project-cell cell-width">適正度</div>
                    </div>
                    <div class="project-cell-row" v-for="member in members" :key="member.id">
                        <div class="project-cell cell-width" data-label="メンバー">
                            <div style="position: relative; width: fit-content;">
                                {{ member.name }}
                            </div>                                
                        </div>
                        <div class="project-cell cell-width" data-label="雇用形態">{{ member?.positions?.name }}</div>
                        <div class="project-cell cell-width" data-label="職階">{{ member?.evaluation?.general_position }}</div>
                        <div class="project-cell cell-width" data-label="メンター">{{ member?.evaluation?.mentor?.name }}</div>
                        <div class="project-cell cell-width" data-label="職務レベル">{{ member?.evaluation?.current_level }}</div>
                        
                        <div class="project-cell cell-width" style="position: relative;" data-label="成果目標・昇給課題">
                            <div class="flex items-center gap-[5px]">                                    
                                <router-link class="user-link" :to="{name: 'outcomegoal', params: { projectId: route.params.projectId, memberId: member.id}}">閲覧</router-link>
                                <span 
                                    class="side-notification" 
                                    style="position: unset;width: fit-content;" 
                                    v-if="confirmBadges(member.id) + commentBadges(member.id) > 0"
                                    :class="{
                                        'side-notification--comment-only': !confirmBadges(member.id) && commentBadges(member.id)
                                    }">
                                    {{ confirmBadges(member.id) + commentBadges(member.id) }}
                                </span>
                            </div>
                        </div>
                        <div class="project-cell cell-width" data-label="人事考課">
                            <div>
                                <router-link class="user-link" :to="{name: 'evaluation', params: { projectId: route.params.projectId, memberId: member.id }}">閲覧</router-link >
                            </div>
                        </div>
                        <div v-if="auth.hasPrivilage" class="project-cell cell-width" data-label="適正度">
                            <div>
                                <router-link class="user-link" :to="{name: 'asignment', params: { projectId: route.params.projectId, memberId: member.id }}">{{ member.pivot.compatibility_number || '未設定' }}</router-link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
        <div class="mobile overflow-y-auto bg-[var(--background-color)] px-4 h-[calc(100%-20px)] pb-[20px]">
            <div class="flex flex-col gap-4">
                <div v-for="member in members" :key="member.id" class="flex flex-col gap-3 border border-solid border-[var(--calendarBorder)] p-4 rounded">
                    <UserPanel :user="member" with-name>
                        <template #details>
                            <div class="flex items-center gap-2  ml-3 mt-1">
                                <div class="text-[13px] text-[gray]">{{ member?.positions?.name }}</div>
                                <span class="bg-[var(--bg2)] text-[11px] px-3 py-1 rounded-xl" v-if="member?.evaluation?.general_position">{{ member.evaluation.general_position }}</span>
                            </div>                              
                        </template>
                    </UserPanel>
                    <div class="text-xs" v-if="member?.evaluation?.mentor">メンター：{{ member?.evaluation?.mentor?.name }}</div>
                    <div class="flex items-center justify-between flex-wrap gap-3">
                        <router-link class="jump-link text-[12px]" :to="{name: 'evaluation', params: { projectId: route.params.projectId, memberId: member.id }}">成果目標・昇給課題
                            <span 
                                class="side-notification" 
                                style="position: unset;width: fit-content;" 
                                v-if="confirmBadges(member.id) + commentBadges(member.id) > 0"
                                :class="{
                                    'side-notification--comment-only': !confirmBadges(member.id) && commentBadges(member.id)
                                }">
                                {{ confirmBadges(member.id) + commentBadges(member.id) }}
                            </span>
                        </router-link >
                        <router-link class="jump-link text-[12px]" :to="{name: 'evaluation', params: { projectId: route.params.projectId, memberId: member.id }}">人事考課</router-link >
                    </div>
                </div>
                    
            </div>
        </div>
    </div>

</template>
<script setup lang="ts">
import UserPanel from '@/components/Global/UserPanel.vue';
import { useProject } from '@/composables/project';
import { useAuthUserStore } from '@/store/auth';
import { useBadgeStore } from '@/store/badge';
import { computed } from 'vue';
import { useRoute } from 'vue-router';

const badge = useBadgeStore()
const route = useRoute()
const auth = useAuthUserStore()
const { memberData, selectedProject } = useProject()
const projectId = Number(route.params.projectId)

const members = computed(() => [...(selectedProject.value?.manager || []), ...(selectedProject.value?.members || [])])
const confirmBadges = (memberId: number) => {
    const goalsBadge = badge.goalsBadgeByFilter([{by: 'project_id', value: projectId}, {by: 'user_id', value: memberId}]).length 
    const issuesBadge = badge.salaryIssueByFilter([{by: 'project_id', value: projectId}, {by: 'user_id', value: memberId}]).length
    return goalsBadge + issuesBadge
}
const commentBadges = (memberId: number) => {
    return badge.goalIssueCommentBadgeByFilter([{by: 'project_id', value: projectId}, {by: 'user_id', value: memberId}]).length
}
</script>
<style scoped>
.project-header-row{
    background: var(--bg3);
}
.project-header-row .project-cell{
    border-bottom: none;
}
.project-cell{
    border-right: none;
}
.project-cell-row:last-child .project-cell {
    border-bottom: 1px solid var(--calendarBorder);
}
.project-cell-row:first-child .project-cell {
    border-bottom: 1px solid var(--calendarBorder);
}
@media screen and (max-width: 959px) {
    .project-cell-row:last-child .project-cell {
        border-bottom: none;
    }
    .project-cell:last-child {
        border-bottom: none;
    }
}
</style>