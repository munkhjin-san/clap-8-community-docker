<template>
    <div class="kadai-content bg-[var(--bg3)] text-[12px] px-2 w-fit text-[var(--primary-color)]">
        <div @click="showStatusLog = !showStatusLog" class="flex items-center gap-1 cursor-pointer flex-wrap">
            <p :class="{'!text-[tomato]' : attentionIssue}">{{ salaryIssueStatus(status) }}</p>
            <slot name="badge"></slot>
            <div v-if="logs && logs.length" class="jump-link text-[11px] ml-1">変更履歴</div>
        </div>
        <div v-if="showStatusLog">
            <p>ステータス変更履歴</p>
            <div v-if="logs && logs.length" class="flex flex-col gap-2 py-2 text-[var(--primary-color)]">                
                <div v-for="log in logs" class="">
                    <div class="flex flex-wrap text-[11px]">
                        <div>
                            <span class="text-[gray]">【{{ DateTime.fromISO(log.created_at).toLocaleString(DateTime.DATETIME_MED) }}】</span>
                            {{ log.user.name }} :
                        </div>
                        <div v-if="log.before_number && log.after_number">{{ salaryIssueStatus(log.before_number) }} → {{ salaryIssueStatus(log.after_number) }}</div>
                    </div>   
                </div>
            </div>
            <div v-else class="text-[11px] text-[gray]">更新履歴はありません</div>
        </div>
    </div>
</template>
<script setup lang="ts">
import { useDashboardGoalsStore } from '@/store/dashboardGoals';
import { DateTime } from 'luxon';
import { computed, ref } from 'vue';
import { ProjectGoal, SalaryIssue } from '@/interface/projectInterface';
import { useAuthUserStore } from '@/store/auth';

const props = defineProps<{
    issue: SalaryIssue
    goal: ProjectGoal

}>()

const auth = useAuthUserStore()
const showStatusLog = ref(false)

const status = computed(() => {
    return props.issue?.status || 0
})

const logs = computed(() => {
    return props.issue?.status_logs || []
})
const isMentor = computed(() => {
    return evaluationData?.mentor_id == auth.activeUser.id
})
const { salaryIssueStatus, evaluationData } = useDashboardGoalsStore()

const attentionIssue = computed(() => {
    if(auth.activeUser.id == props.issue.user_id){
        return props.issue.status === 1 || props.issue.status === 8
    }
    if(isMentor.value) {
        return props.issue.status === 2 || props.issue.status === 7
    }
    if(auth.isAdmin) {
        return props.issue.status === 3 || props.issue.status === 4 || props.issue.status === 9
    }
})
</script>