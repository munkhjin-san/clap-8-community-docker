<template>
    <div class="overlay">
        <div class="chatCreate kadaiCreate scrollable">
            <div class="recordFormTitle" style="display:flex;margin-bottom: 10px;">
                <div @click="emit('close')" style="margin: 2px 10px 0 0;" v-if="responsive.mobile">
                    <svg class="dot-menu" version="1.1" width="15" height="15" viewBox="0 0 20 32" xmlns="http://www.w3.org/2000/svg">
                        <path d="M0.775 17.789c1.305 1.166 2.612 2.332 3.927 3.486 1.311 1.161 2.634 2.308 3.953 3.46 1.316 1.156 2.646 2.296 3.973 3.439 1.33 1.139 2.667 2.273 4.015 3.394 0.662 0.551 1.647 0.52 2.272-0.107 0.65-0.654 0.619-1.725-0.020-2.393-1.198-1.253-2.407-2.495-3.621-3.729-1.232-1.245-2.462-2.492-3.704-3.725-0.902-0.9-1.803-1.802-2.707-2.699-0.033-0.032-0.055-0.069-0.072-0.106-0.045-0.036-0.082-0.080-0.111-0.129-0.069-0.047-0.129-0.117-0.176-0.216-0.021-0.047-0.044-0.092-0.066-0.136-0.12-0.062-0.214-0.168-0.246-0.325-0.001-0.005-0.002-0.009-0.003-0.014-0.104-0.157-0.187-0.327-0.254-0.505-0.109-0.185-0.182-0.388-0.226-0.601-0.002-0.012-0.005-0.024-0.007-0.036-0.016-0.085-0.028-0.172-0.036-0.259-0.195-0.593-0.26-1.183 0.030-1.653 0.006-0.157 0.067-0.277 0.157-0.361 0.019-0.050 0.039-0.099 0.063-0.149 0.040-0.084 0.1-0.145 0.17-0.188 0.008-0.015 0.019-0.028 0.028-0.042 0.032-0.13 0.106-0.228 0.202-0.293 0.072-0.145 0.157-0.287 0.26-0.43 0.046-0.063 0.101-0.113 0.163-0.151 0.018-0.020 0.037-0.038 0.059-0.054 0.014-0.059 0.044-0.116 0.094-0.165 0.9-0.888 1.797-1.782 2.699-2.672 1.244-1.231 2.476-2.475 3.714-3.717l1.843-1.871 1.832-1.885c0.655-0.681 0.669-1.793-0.044-2.48-0.652-0.631-1.693-0.624-2.385-0.038l-1.964 1.66-1.995 1.71c-1.32 1.149-2.648 2.293-3.962 3.45s-2.636 2.308-3.943 3.474c-1.311 1.159-3.284 2.806-4.106 3.689s-0.792 2.492 0.191 3.369z"></path>
                    </svg>                    
                </div>  
                <p>プロジェクト人事承認</p>
                <div v-if="!responsive.mobile" class="cursor-pointer" @click="emit('close')" style="position:unset; margin:auto 0 auto auto">
                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" class="modalWindowCloseButton" viewBox="0 0 32 32">
                        <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                    </svg>                        
                </div> 
            </div>
            <div v-if="projectGoals.length" v-for="goal in projectGoals" style="position: relative">            
                <div class="goal-detail cursor-pointer" @click="chosenGoal = goal" style="position: relative;gap:10px;margin-bottom: 20px;">
                    <div>
                        <div>成果目標</div>
                        <div class="kadai-content">{{ sliceGoal(goal?.outcome_goal) }}</div>
                    </div>
                    <div>
                        <div>期間</div>
                        <div class="kadai-content">{{ goal?.start_date }} ～ {{ goal?.end_date }}</div>
                    </div>
                    <div>
                        <div>ステータス</div>
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
                        <div>ステータス</div>
                        <div class="kadai-content">{{ statuses[goal?.salary_issue?.status] }}</div>
                    </div>
                </div>
            </div>
        </div>
        <Transition name="modalFade">
            <ProjectGoalMore 
                v-if="chosenGoal" 
                :goal="chosenGoal"
                :statuses="statuses"
                @close="chosenGoal = null, emit('close')"
            />
        </Transition>
    </div>
</template>
<script lang="ts" setup>
import { useResponsive } from '@/store/responsive';
import { ref } from 'vue';
import ProjectGoalMore from '../Project/ProjectGoalMore.vue';
const chosenGoal = ref(null)
const props = defineProps(['projectGoals'])
const statuses = [
    '作成中', 
    '差戻中', 
    '申請中', 
    '人事申請中', 
    '人事棄却', 
    '人事承認済', 
    '報告中',
    '未達成', 
    '達成'
]
const responsive = useResponsive()
const emit = defineEmits(['close'])
const sliceGoal = (content: string) => {
    const truncatedGoal = content.length > 100 
    ? content.slice(0, 100) + '...' 
    : content;
    return truncatedGoal
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