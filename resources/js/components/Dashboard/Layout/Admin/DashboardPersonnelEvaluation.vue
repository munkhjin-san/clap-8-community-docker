<template>
    <BaseLayout
        :title="'【管理者】'" 
        :count="data.data.pendingEvaluations.length + data.data.pendingAssignments.length"
        :fullscreen="fullscreen" 
        :type="data.type" 
        :can-resize="data.canResize"
        :can-fullscreen="data.canFullscreen"
        @toggle="(el, title) =>emit('toggle', el, data.type)" 
        @resize="emit('resize', data.type)"
    >
        <template #default>
            <div class="m-5">
                <div v-if="data.data.pendingEvaluations.length" >
                    <div class="text-[14px] font-bold mb-3">承認待ち人事考課（{{ data.data.pendingEvaluations.length }}）</div>
                    <ExpansionGrid class="gap-x-4" :col="Number(data.col?.split('-')[2] ?? 1)">
                        <ExpansionPanelItem
                            selected-class="selected-panel-item"
                            hide-actions
                            static
                            :tile="true"
                            class="rm-p"
                            v-for="(record, index) in data.data.pendingEvaluations"
                            :key="record.id ?? index"
                            :value="record.id ?? index"
                            :col="Number(data.col?.split('-')[2] ?? 1)"
                        >
                            <template #title="{ expanded }">
                                <PanelTitle :expanded="expanded">
                                    <div class="flex items-center gap-3">
                                        <UserPanel :user="record.user" size="30" with-name disable-instant>
                                            <template #details>
                                                <div class="ml-3 mt-1 text-[11px] text-[gray]">メンター：{{ record?.mentor?.name }}</div>
                                            </template>
                                        </UserPanel>
                                    </div>
                                </PanelTitle>
                            </template>
                            <template #body>
                                <PanelData class="px-4 py-4 pt-0">
                                    <div>
                                        <p class="text-sm text-[gray] mb-2">評価期間：{{ record.year }}年 {{ isFirstHalf(record.which_half) ? '上期' : '下期' }}</p>
                                        <div class="mt-3 ml-auto w-fit">
                                            <span @click="setDetail(record)" class="jump-link">対応</span>
                                        </div>
                                    </div>
                                </PanelData>
                            </template>
                        </ExpansionPanelItem>
                    </ExpansionGrid>
                </div>
                <div v-else class="text-sm text-[gray] mb-3 text-center">
                    対象の人事評価はありません
                </div>
            
                <Modal v-if="detailedData" @close="detailedData = null">
                    <template #title>
                        <p>{{ `${detailedData?.memberData?.name} ~ ${detailedData?.date?.short_name}` }}</p>
                    </template>
                    <template #content>
                        <EvaluationDetail 
                            :member-data-remind="detailedData?.memberData" 
                            :date="detailedData?.date" 
                            @reload="{emit('refreshData', data.type); detailedData = null}"
                        />
                    </template> 
                </Modal>



                <div v-if="data.data.pendingAssignments.length" class="mt-5">
                    <div class="text-[14px] font-bold mb-3">対応待ち適合評価（{{ data.data.pendingAssignments.length }}）</div>
                    <ExpansionGrid class="gap-x-4" :col="Number(data.col?.split('-')[2] ?? 1)">
                        <ExpansionPanelItem
                            selected-class="selected-panel-item"
                            hide-actions
                            static
                            :tile="true"
                            class="rm-p"
                            v-for="(record, index) in data.data.pendingAssignments"
                            :key="record.id ?? index"
                            :value="record.id ?? index"
                            :col="Number(data.col?.split('-')[2] ?? 1)"
                        >
                            <template #title="{ expanded }">
                                <PanelTitle :expanded="expanded">
                                    <div class="flex items-center gap-3">
                                        <UserPanel v-if="record.user" :user="record.user" size="25" with-name disable-instant></UserPanel>
                                    </div>
                                </PanelTitle>
                            </template>
                            <template #body>
                                <PanelData class="px-4 py-4 pt-0">
                                    <div>
                                        <div>プロジェクト: {{ record.project_record?.name }}</div>
                                        <div>スコア: {{ record.score }}</div>
                                        <div>サポートレベル: 
                                            <span class="p-1 rounded-md" :class="{
                                                'bg-green-100 text-green-800 border border-green-200': record.support_level === 'green',
                                                'bg-orange-100 text-orange-800 border border-orange-200': record.support_level === 'orange',
                                                'bg-red-100 text-red-800 border border-red-200': record.support_level === 'red',
                                            }">{{ record.support_level ? levelMap[record.support_level] : '' }}</span>
                                        </div>
                                        <div class="mt-3 ml-auto w-fit">
                                            <router-link :to="{name: 'assign-member', params: { projectId: record.project_record?.id, memberId: record.user?.id }}">詳細</router-link>
                                        </div>

                                    </div>
                                </PanelData>
                            </template>
                        </ExpansionPanelItem>
                    </ExpansionGrid>
                </div>

                <div v-if="data.data.pendingChangeRequests && data.data.pendingChangeRequests.length" class="mt-5">
                    <div class="text-[14px] font-bold mb-3">対応待ち各種届出（{{ data.data.pendingChangeRequests.length }}）</div>
                    <ExpansionGrid class="gap-x-4" :col="Number(data.col?.split('-')[2] ?? 1)">
                        <ExpansionPanelItem
                            selected-class="selected-panel-item"
                            hide-actions
                            static
                            :tile="true"
                            class="rm-p"
                            v-for="(record, index) in data.data.pendingChangeRequests"
                            :key="record.id ?? index"
                            :value="record.id ?? index"
                            :col="Number(data.col?.split('-')[2] ?? 1)"
                        >
                            <template #title="{ expanded }">
                                <PanelTitle :expanded="expanded">
                                    <div class="flex items-center gap-3 truncate">
                                        <UserPanel v-if="record.user" :user="record.user" size="25" with-name disable-instant></UserPanel>
                                    </div>
                                </PanelTitle>
                            </template>
                            <template #body>
                                <PanelData class="px-4 py-4 pt-0">
                                    <div>
                                        <div>申請内容: {{ record.type_label }}</div>
                                        <div>申請日: {{ DateTime.fromISO(record.created_at).toLocaleString(DateTime.DATETIME_SHORT) }}</div>
                                        <div>ステータス: {{ record.status_label }}</div>
                                        <div class="mt-3 ml-auto w-fit">
                                            <router-link :to="{name: 'employee-change-application-detail', params: { applicationId: record.id }}">詳細</router-link>
                                        </div>

                                    </div>
                                </PanelData>
                            </template>
                        </ExpansionPanelItem>
                    </ExpansionGrid>
                </div>
            </div>
        </template>
    </BaseLayout>
</template>
<script setup lang="ts">
import CommandButton from '@/components/Global/CommandButton.vue';
import BaseLayout from '../BaseLayout.vue';
import UserPanel from '@/components/Global/UserPanel.vue';
import { EvaluationRecord } from '@/interface/evaluationInterface';
import { ref } from 'vue';
import { User } from '@/interface/globalInterface';
import { detailedDateOptions } from '@/utils/tools';
import Modal from '@/components/Global/Modal.vue';
import EvaluationDetail from '@/components/Project/PersonnelEvaluation/EvaluationDetail.vue';
import ExpansionGrid from '../../ExpansionGrid.vue';
import ExpansionPanelItem from '../../ExpansionPanelItem.vue';
import { DashboardPersonnelEvaluationCard } from '@/interface/dashboard';
import PanelTitle from '../PanelTitle.vue';
import PanelData from '../PanelData.vue';
import { DateTime } from 'luxon';

const props = defineProps<{
    data: DashboardPersonnelEvaluationCard
    fullscreen: boolean
}>()

const levelMap = {
    green: '対応完了',
    orange: '通常対応',
    red: '重点対応'
}

const emit = defineEmits<{
    resize: [type: string]
    toggle: [el: HTMLElement | null, title: string]
    refreshData: [key: string]
}>()
const targetDates = detailedDateOptions()

const detailedData = ref<{
    evaluation: EvaluationRecord
    date: typeof targetDates[0]
    memberData: User
} | null>(null)

const isFirstHalf = (whichHalf: string | number) => ['first', '1'].includes(String(whichHalf))

const setDetail = (evaluation: EvaluationRecord) => {
    const memberData = evaluation.user
    const dateOptions = detailedDateOptions()
    const date = dateOptions.find(option => option.year == evaluation.year && option.which_half == evaluation.which_half)
    if(memberData && date){
        detailedData.value = {
            evaluation,
            date,
            memberData
        }
    }
}

defineExpose({
    cardType: props.data.type,
})
</script>
