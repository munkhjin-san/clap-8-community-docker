<template>
    <div class="kadai-content bg-[var(--bg3)] text-[13px] px-3 py-1 w-fit text-[var(--primary-color)]" >
            <div @click="showStatusLog = !showStatusLog" class="flex items-center gap-1 cursor-pointer">
                <p v-if="type == 'project_goal'">{{ goalStatus(status) }}</p>
                <p v-else-if="type == 'salary_issue'">{{ salaryIssueStatus(status) }}</p>
                <slot name="badge"></slot>
                <div v-if="logs && logs.length">
                    <UserPanel disable-instant size="15" :user="logs[logs.length - 1]?.user"/>
                </div>
            </div>
            <div v-if="showStatusLog">
                <p>ステータス更新履歴</p>
                <div v-if="logs && logs.length" class="flex flex-col gap-2 py-2 text-[var(--primary-color)]">                
                    <div v-for="log in logs" class="">
                        <div class="flex flex-wrap text-[11px]">
                            <div>
                                <span class="text-[gray]">【{{ DateTime.fromISO(log.created_at).toLocaleString(DateTime.DATETIME_MED) }}】</span>
                                {{ log.user.name }} :
                            </div>
                            <div v-if="type == 'project_goal' && log.before_number && log.after_number">{{ goalStatus(log.before_number) }} → {{ goalStatus(log.after_number) }}</div>
                            <div v-if="type == 'salary_issue' && log.before_number && log.after_number">{{ salaryIssueStatus(log.before_number) }} → {{ salaryIssueStatus(log.after_number) }}</div>
                        </div>   
                    </div>
                </div>
                <div v-else class="text-[11px] text-[gray]">更新履歴はありません</div>
            </div>
        </div>
</template>
<script setup lang="ts">
import UserPanel from '@/components/Global/UserPanel.vue';
import { useGoal } from '@/composables/dashboard';
import { StatusLog } from '@/interface/globalInterface';
import { DateTime } from 'luxon';
import { ref } from 'vue';

const props = defineProps<{
    type: 'project_goal' | 'salary_issue'
    logs?: StatusLog[]
    status: number
}>()

const showStatusLog = ref(false)

const { goalStatus, salaryIssueStatus } = useGoal()
</script>