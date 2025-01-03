<template>
    <div class="overlay">
        <div class="chatCreate kadaiCreate scrollable">
            <div class="recordFormTitle" style="display:flex;margin-bottom: 10px;">
                <div @click="emit('close')" style="margin: 2px 10px 0 0;" v-if="responsive.mobile">  
                    <Back/>             
                </div>  
                <p>プロジェクト人事承認</p>
                <div v-if="!responsive.mobile" class="cursor-pointer" @click="emit('close')" style="position:unset; margin:auto 0 auto auto">
                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" class="modalWindowCloseButton" viewBox="0 0 32 32">
                        <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                    </svg>                        
                </div> 
            </div>
            <div v-if="projectGoals.length" v-for="goal in projectGoals" style="position: relative">            
                <div class="goal-detail cursor-pointer" @click="pickGoal(goal)" style="position: relative;gap:10px;margin-bottom: 20px;">
                    <div>
                        <div>成果目標</div>
                        <div class="kadai-content">{{ sliceGoal(goal?.outcome_goal) }}</div>
                    </div>
                    <div>
                        <div>期間</div>
                        <div class="kadai-content">{{ goal?.start_date }} ～ {{ goal?.end_date }}</div>
                    </div>
                    <div>
                        <div>成果目標ステータス</div>
                        <div class="kadai-content">{{ statuses[goal?.status] }}</div>
                    </div>
                    <div v-if="goal?.achievement_rate">
                        <div>達成率</div>
                        <div class="kadai-content">{{ goal?.achievement_rate }}%</div>
                    </div>
                    <div v-if="goal?.salary_issue">
                        <div>昇給課題</div>
                        <div class="kadai-content">{{ goal?.salary_issue?.title }}</div>
                    </div>
                    <div v-if="goal?.salary_issue">
                        <div>昇給課題ステータス</div>
                        <div class="kadai-content">{{ statuses[goal?.salary_issue?.status] }}</div>
                    </div>
                </div>
            </div>
        </div>
        <Transition name="modalFade">
            <ProjectGoalMore 
                v-if="chosenGoal" 
                :goal="chosenGoal"
                :selectedProject="selectedProject"
                :isManagerOrMember="isManagerOrMember"
                :statuses="statuses"
                :memberData="memberData"
                @close="chosenGoal = null"
            />
        </Transition>
    </div>
</template>
<script lang="ts" setup>
import { useResponsive } from '@/store/responsive';
import { computed, ref } from 'vue';
import ProjectGoalMore from '../Project/ProjectGoalMore.vue';
import { Project, ProjectGoal } from '@/interface/projectInterface';
import { useAuthUserStore } from '@/store/auth';
import Back from '../Icons/Back.vue';
const chosenGoal = ref<ProjectGoal | null>(null)
const props = defineProps(['projectGoals', 'memberData'])
const auth = useAuthUserStore()
const statuses = [
    '作成中', 
    '差戻中', 
    '申請中', 
    '人事申請中', 
    '変更申請中', 
    '人事承認済', 
    '報告中',
    '未達成', 
    '達成'
]
const responsive = useResponsive()
const selectedProject = ref<Project | null>(null)
const emit = defineEmits(['close'])
const sliceGoal = (content: string) => {
    const truncatedGoal = content.length > 100 
    ? content.slice(0, 100) + '...' 
    : content;
    return truncatedGoal
}
const isManagerOrMember = computed(() => {
    return selectedProject.value?.director_id === auth.id ||
           selectedProject.value?.manager?.some(user => user.id === auth.id)
})
const pickGoal = (goal: ProjectGoal) => {
    chosenGoal.value = goal
    selectedProject.value = goal?.project
}
</script>
<style>
.goal-detail{
    background-color: var(--bg3);
    line-height: 1.5;
    word-break: break-word;
    white-space: break-spaces;
    padding: 10px;
    margin-bottom: 30px;
    display: flex;
    flex-direction: column;
    gap: 30px;
    font-size: 14px;
}
.kadaiCreate{
    width: 90% !important;
    height: 90%!important;
}
.kadaiSwitch{
    padding: 10px;
    background: var(--background-color);
    cursor: pointer;
    font-size: 13px;
}
.kadai-content{
    white-space: break-spaces;
    line-height: 2;
}
.kadai-active{
    background: var(--bg3);
}
.kadai-root{
    width: 100%;
    height: auto;
    left: 0;
    top: 0;
    font-size: 14px;
    line-height: 1.5;
}
</style>