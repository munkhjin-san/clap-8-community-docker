<template>
    <div class="kadai-content bg-[var(--bg3)] text-[12px] px-2 w-fit text-[var(--primary-color)]">
        <div @click="showStatusLog = !showStatusLog" class="flex items-center gap-1 cursor-pointer flex-wrap">
            <p :class="{'!text-[tomato]' : attentionGoal}">{{ goalStatus(status) }}</p>
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
                        <div v-if="log.before_number && log.after_number">{{ goalStatus(log.before_number) }} → {{ goalStatus(log.after_number) }}</div>
                    </div>   
                </div>
            </div>
            <div v-else class="text-[11px] text-[gray]">更新履歴はありません</div>
        </div>
    </div>
</template>
<script setup lang="ts">
import UserPanel from '@/components/Global/UserPanel.vue';
import { useDashboardGoalsStore } from '@/store/dashboardGoals';
import { DateTime } from 'luxon';
import { computed, ref } from 'vue';
import { ProjectGoal } from '@/interface/projectInterface';
import { useAuthUserStore } from '@/store/auth';

const props = defineProps<{
    item: ProjectGoal

}>()

const auth = useAuthUserStore()
const showStatusLog = ref(false)

const status = computed(() => {
    return props.item?.status || 0
})

const logs = computed(() => {
    return props.item?.status_logs || []
})
const isManager = computed(() => {
    return props.item.project?.is_manager
})

const { goalStatus, salaryIssueStatus } = useDashboardGoalsStore()

const attentionGoal = computed(() => {
    if(auth.activeUser.id == props.item.user_id){
        return props.item.status === 1 || props.item.status === 8
    }
    if(isManager.value) {
        return props.item.status === 2 || props.item.status === 7
    }
    if(auth.isBoss){
        return props.item.user.position_id == 6 && (props.item.status === 2 || props.item.status === 7)
    }
    if(auth.isAdmin) {
        return props.item.status === 3 || props.item.status === 4 
    }
})
</script>