<template>
    <div class="p-[15px] bg-[var(--message-background)] max-w-[80%] w-fit min-w-[40%]" :class="{'self-end': report.user?.id == auth.activeUser.id}">
        <div class="flex justify-between gap-[20px]">
            <UserPanel :user="report.user" with-name size="25"/>
            <p class="text-[gray] text-[12px]">{{DateParser(report.created_at)}}</p>
        </div>
        <div class="whitespace-break-spaces leading-normal mt-[15px]" v-html="mentionFormatter(report.content, true)"></div>
        <div>
            <GoalMessageFile v-if="report.files && report.files.length" :list="report.files"/>
        </div>
    </div>    
</template>
<script lang="ts" setup>
import UserPanel from '@/components/Global/UserPanel.vue';
import { ProjectGoalReport } from '@/interface/projectInterface';
import { useAuthUserStore } from '@/store/auth';
import { DateParser, mentionFormatter } from '@/utils/tools';
import GoalMessageFile from './GoalMessageFile.vue';

const props = defineProps<{
    report: ProjectGoalReport
}>();
const auth = useAuthUserStore()
</script>