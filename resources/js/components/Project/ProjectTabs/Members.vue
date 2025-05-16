<template>
    <router-view v-slot="{ Component }">
        <component
            :key="memberData?.id"
            :is="Component"
        />
    </router-view>
    <div class="h-full relative">
        <div class="bg-[var(--background-color)] p-[20px] !h-[calc(100%-40px)]">
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
                    </div>
                    <div class="project-cell-row" v-for="member in [...(selectedProject?.manager || []), ...(selectedProject?.members || [])]">
                        <div class="project-cell cell-width" data-label="メンバー">
                            <div style="position: relative; width: fit-content;">
                                {{ member.name }}
                            </div>                                
                        </div>
                        <div class="project-cell cell-width" data-label="雇用形態">{{ member?.positions?.name }}</div>
                        <div class="project-cell cell-width" data-label="職階">{{ member?.evaluation?.general_position }}</div>
                        <div class="project-cell cell-width" data-label="メンター">{{ member?.evaluation?.mentor?.name }}</div>
                        <div class="project-cell cell-width" data-label="職務評価基準">{{ member?.evaluation?.current_level }}</div>
                        
                        <div class="project-cell cell-width" style="position: relative;" data-label="成果目標・昇給課題">
                            <div class="flex items-center gap-[5px]">                                    
                                <router-link class="user-link" :to="{name: 'outcomegoal', params: { projectId: route.params.projectId, memberId: member.id}}">閲覧</router-link>
                                <span 
                                    class="side-notification" 
                                    style="position: unset;width: fit-content;" 
                                    v-if="badge.goalsBadgeByFilter([{by: 'project_id', value: Number(route.params.projectId)}, {by: 'user_id', value: member.id}]).length + badge.salaryIssueByFilter([{by: 'project_id', value: Number(route.params.projectId)}, {by: 'user_id', value: member.id}]).length">
                                    {{ badge.goalsBadgeByFilter([{by: 'project_id', value: Number(route.params.projectId)}, {by: 'user_id', value: member.id}]).length + badge.salaryIssueByFilter([{by: 'project_id', value: Number(route.params.projectId)}, {by: 'user_id', value: member.id}]).length }}
                                </span>
                            </div>
                        </div>
                        <div class="project-cell cell-width" data-label="人事考課">
                            <div>
                                <router-link class="user-link" :to="{name: 'evaluation', params: { projectId: route.params.projectId, memberId: member.id }}">閲覧</router-link >
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</template>
<script setup lang="ts">
import { useProject } from '@/composables/project';
import { Project } from '@/interface/projectInterface';
import { useBadgeStore } from '@/store/badge';
import { computed } from 'vue';
import { useRoute } from 'vue-router';

const props = defineProps<{
    userList: any;
}>();
const badge = useBadgeStore()
const route = useRoute()

const { memberData, selectedProject } = useProject()

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