<template>
    <BaseLayout
        :title="data.title" 
        :count="0" 
        :fullscreen="fullscreen" 
        :type="data.type" 
        :can-resize="data.canResize"
        :can-fullscreen="data.canFullscreen"
        @toggle="(el, title) =>emit('toggle', el, data.type)" 
        @resize="emit('resize', data.type)"
    >
        <template #icon>
            <svg class="side-app-icon mr-1" version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" style="width: auto; height: 17px;">
                <path d="M30.827 10.021c-0.79-1.951-1.963-3.748-3.442-5.253s-3.264-2.716-5.214-3.531c-1.963-0.816-4.080-1.237-6.183-1.237-2.116 0.013-4.233 0.433-6.183 1.249s-3.723 2.040-5.202 3.544c-1.479 1.504-2.652 3.289-3.442 5.24-0.778 1.951-1.173 4.054-1.16 6.132 0.013 2.091 0.433 4.182 1.237 6.107 0.816 1.925 1.989 3.697 3.48 5.151s3.264 2.626 5.189 3.391c1.925 0.778 4.207 1.147 6.069 1.147 0.956 0 2.065-0.115 3.085-0.306s2.014-0.484 2.983-0.867c1.925-0.765 3.697-1.925 5.189-3.378s2.69-3.213 3.493-5.138c0.816-1.925 1.249-4.016 1.262-6.107 0.025-2.091-0.37-4.194-1.16-6.145zM28.367 21.304c-0.65 1.632-1.645 3.123-2.869 4.386s-2.716 2.282-4.335 2.983-3.57 1.071-5.176 1.071-3.544-0.382-5.163-1.084c-1.619-0.688-3.111-1.708-4.335-2.971s-2.218-2.754-2.881-4.373c-0.663-1.619-1.007-3.378-0.994-5.138s0.382-3.493 1.071-5.1c0.688-1.606 1.696-3.060 2.932-4.284 2.486-2.435 5.916-3.837 9.383-3.812 3.468-0.013 6.884 1.39 9.358 3.825 1.237 1.211 2.244 2.677 2.92 4.284 0.688 1.594 1.045 3.34 1.058 5.087s-0.319 3.493-0.969 5.125z"></path><path d="M17.594 16.064c-0.026-0.038-0.064-0.064-0.089-0.102l-0.79-9.74c-0.026-0.357-0.306-0.65-0.676-0.676-0.408-0.038-0.765 0.268-0.803 0.676l-0.841 10.441c0 0.076-0.013 0.178 0 0.255 0.013 0.102 0.025 0.191 0.051 0.293 0.013 0.51 0.242 1.020 0.688 1.364l6.489 5.049c0.293 0.229 0.727 0.242 1.033-0.013 0.357-0.28 0.408-0.803 0.128-1.16l-5.189-6.387z"></path>
            </svg>
        </template>
        <div class="mx-3 mb-3">
            <div v-if="data.data.pendingPlannedLeaves && data.data.pendingPlannedLeaves.length">
                <ExpansionGrid class="gap-x-4" :col="Number(data.col?.split('-')[2] ?? 1)">
                    <ExpansionPanelItem
                        hide-actions
                        static
                        :tile="true"
                        class="rm-p"
                    >
                        <template #title="{ expanded }">
                            <PanelTitle :expanded="expanded">
                                <div class="mr-2 mx-0.5 rounded-full bg-[tomato] w-1.5 min-w-1.5 h-1.5 custom-heartbeat"></div>
                                計画有給
                            </PanelTitle>
                        </template>
                        <template #body>
                            <PanelData>
                                <div v-for="item in data.data.pendingPlannedLeaves">
                                    <WorkMessage
                                        v-if="item"
                                        :item="item"
                                    />
                                </div>
                            </PanelData>
                        </template>
                    </ExpansionPanelItem>
                </ExpansionGrid>
            </div>
            <div class="mt-3" v-if="data.data.departuresReportUsers && data.data.departuresReportUsers.length">
                <p class="text-sm mb-3"><span class="text-[11px] rounded-full bg-[var(--bg3)] px-1 mr-1 py-0.5">PM</span>出発報告状況</p>
                <div class="mx-3 mb-3 overflow-hidden w-fit flex flex-col gap-[10px] bg-[var(--background-color)]">
                    <div v-for="item in data.data.departuresReportUsers">
                        <div class="flex gap-[15px] text-[13px] items-center">
                            <UserPanel disable-instant with-name size="25" :user="item"/>
                            <div class="text-[gray]">{{ `${item.shift_records && item.shift_records.length && item.shift_records[0].departure_report ? DateTime.fromSQL(item.shift_records[0].departure_report).toFormat('M / d HH:mm') : '未報告'}` }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div v-if="data.data.pendingTimesheets.length" class="mt-3">
                <p class="my-2 text-sm overflow-hidden whitespace-nowrap text-ellipsis">
                    <span class="text-[11px] rounded-full bg-[var(--bg3)] px-1 py-0.5">PM</span>
                    承認漏れ (日報)
                </p>
                <ExpansionGrid class="gap-x-4" :col="Number(data.col?.split('-')[2] ?? 1)">
                    <ExpansionPanelItem
                        hide-actions
                        static
                        :tile="true"
                        class="rm-p"
                        v-for="item in data.data.pendingTimesheets"
                    >
                        <template #title="{ expanded }">
                            <PanelTitle :expanded="expanded">
                                <div class="flex items-center">
                                    <UserPanel disable-instant with-name size="25" :user="item.user"/>
                                    <span class="text-[gray] text-[12px]">（{{ 
                                        (item?.shift ? item.shift.map(s => s.count).reduce((a, b) => a + b, 0) : 0) + 
                                        (item?.timecard ? item.timecard.map(t => t.count).reduce((a, b) => a + b, 0) : 0) + 
                                        (item?.overtime ? item.overtime : 0)
                                    }}件）</span>
                                </div>
                                
                            </PanelTitle>
                        </template>
                        <template #body>
                            <PanelData>
                                <div class="flex flex-col gap-1">
                                    <template v-if="item.timecard && item.timecard.length">
                                        <div v-for="(timecard) in item.timecard" class="text-[12px]">
                                            日報申請 : {{ timecard.month }}月分<span class="text-[gray] text-[12px]">（{{timecard.count}}件）</span>
                                        </div>
                                    </template>
                                    <div v-if="item.overtime">残業申請 : <span class="text-[gray] text-[12px]">（{{ item.overtime }}件）</span></div>
                                    <template v-if="item.shift && item.shift.length">
                                        <div v-for="(shift) in item.shift" class="text-[12px]">
                                            勤怠予定申請 : {{ shift.month }}月分<span class="text-[gray] text-[12px]">（{{shift.count}}件）</span>
                                        </div>                                        
                                    </template>
                                    <div class="ml-auto">
                                        <router-link :to="{name: 'timesheet', query: {user_id: item.user.id}}" class="jump-link mt-2" @click="">対応する</router-link>
                                    </div>
                                    
                                </div>                                
                            </PanelData>
                        </template>
                    </ExpansionPanelItem>
                </ExpansionGrid>
            </div>
        </div>
    </BaseLayout>
</template>
<script setup lang="ts">
import UserPanel from '@/components/Global/UserPanel.vue';
import BaseLayout from './BaseLayout.vue';
import { DateTime } from 'luxon';
import { useRouter } from 'vue-router';
import WorkMessage from '@/components/Work/WorkMessage.vue';
import ExpansionGrid from '../ExpansionGrid.vue';
import ExpansionPanelItem from '../ExpansionPanelItem.vue';
import PanelTitle from './PanelTitle.vue';
import PanelData from './PanelData.vue';
import { pendingTimesheedData } from '@/interface/dashboard';

const props = defineProps<{
    data: {
        title: string,
        data: {
            pendingTimesheets: pendingTimesheedData[],
            departuresReportUsers: [] | any[],
            pendingPlannedLeaves: [] | any[],
        },
        order?: number,
        type: string
        canResize?: boolean
        canFullscreen?: boolean
        col: string
    }
    fullscreen: boolean
}>()

const emit = defineEmits<{
    resize: [type: string]
    toggle: [el: HTMLElement | null, title: string]
}>()
const router = useRouter()

defineExpose({
    cardType: props.data.type,
})

</script>