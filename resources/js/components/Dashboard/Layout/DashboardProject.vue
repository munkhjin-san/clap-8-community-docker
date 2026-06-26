<template>
    <BaseLayout 
        :title="`プロジェクト`" 
        :count="dashboardCount"
        :fullscreen="fullscreen" 
        :type="data.type"
        :can-resize="data.canResize"
        :can-fullscreen="data.canFullscreen"
        @toggle="(el, title) => emit('toggle', el, data.type)" 
        @resize="emit('resize', data.type)"
    >
        <template #icon>
            <svg class="side-app-icon mr-2" version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 45 32" style="height: 16px;overflow: visible;">
                <path d="M32.084 18.983c0.433 0.409 0.938 0.722 1.492 0.95 0.433 0.18 1.191 0.265 1.732 0.265 0.553 0 1.191-0.096 1.72-0.277 0.553-0.192 1.059-0.529 1.492-0.938s0.782-0.914 1.023-1.456c0.241-0.553 0.373-1.155 0.373-1.756 0.024-1.203-0.457-2.43-1.323-3.284-0.433-0.433-0.938-0.782-1.504-1.011-0.565-0.241-1.179-0.361-1.78-0.361s-1.215 0.12-1.78 0.361c-0.565 0.241-1.083 0.577-1.504 1.011-0.854 0.866-1.347 2.081-1.323 3.284-0.012 1.203 0.517 2.394 1.383 3.212zM33.829 14.28c0.397-0.385 0.926-0.601 1.48-0.601s1.083 0.217 1.48 0.601c0.397 0.385 0.638 0.914 0.65 1.48s-0.18 1.131-0.577 1.552c-0.385 0.433-1.071 0.686-1.564 0.698-0.481 0.012-1.155-0.289-1.54-0.71-0.192-0.217-0.349-0.457-0.445-0.722s-0.144-0.541-0.132-0.83c0-0.553 0.253-1.083 0.65-1.468zM35.308 23.471c1.107 0 2.262 0.289 3.164 0.902 0.926 0.626 1.78 1.684 2.202 2.37 0.325 0.517 0.866 0.77 1.395 0.553 0.517-0.204 0.686-0.83 0.553-1.371-0.12-0.541-0.577-1.395-1.059-2.045-0.505-0.662-1.167-1.323-2.25-1.949-1.215-0.71-2.454-1.035-4.018-1.035s-2.803 0.325-4.018 1.035c-1.083 0.626-1.744 1.287-2.25 1.949-0.493 0.65-0.938 1.504-1.059 2.045s0.048 1.167 0.553 1.371c0.541 0.217 1.083-0.036 1.395-0.553 0.421-0.686 1.275-1.756 2.201-2.37s2.081-0.902 3.188-0.902z"></path><path d="M43.898 29.931c-1.768-0.18-3.537-0.253-5.305-0.301-0.89-0.024-1.78-0.024-2.671-0.036l-13.353-0.12-10.683-0.048h-8.782c-0.337 0-0.602-0.265-0.602-0.602 0.012-1.889-0.096-7.122-0.144-10.298-0.060-3.188-0.144-7.916-0.204-10.43-0.072-2.514-0.084-5.029-0.325-7.531-0.024-0.301-0.277-0.541-0.589-0.553-0.325-0.012-0.602 0.253-0.638 0.577-0.265 2.49-0.265 4.992-0.337 7.507-0.060 2.514-0.204 8.469-0.241 11.116-0.036 2.635-0.024 8.974 0.060 11.489 0 0-0.096-3.176 0 0.036 0.024 0.686 0.577 1.215 1.263 1.227 1.78 0.012 3.429 0.012 5.209 0.012h5.341l10.683-0.048 13.353-0.12c0.89-0.012 1.78-0.012 2.671-0.036 1.78-0.048 3.561-0.12 5.341-0.301 0.373-0.036 0.662-0.337 0.686-0.722 0-0.433-0.325-0.77-0.734-0.818z"></path><path d="M6.953 20.547l0.926-1.095 0.938-1.119c0.614-0.758 1.239-1.504 1.853-2.262s1.227-1.504 1.829-2.274c0.313-0.385 0.626-0.782 0.926-1.167 0.072-0.096 0.217-0.096 0.301-0.012l4.439 4.451c0.445 0.421 1.155 0.409 1.588-0.036l0.048-0.060c1.023-1.083 2.033-2.201 3.020-3.308 0.986-1.119 1.961-2.25 2.923-3.392 0.77-0.914 2.37-2.875 3.056-3.717 0.096-0.108 0.277-0.036 0.265 0.108-0.060 0.517-0.144 1.299-0.18 1.72-0.060 0.662-0.156 1.323-0.060 2.021 0.024 0.168 0.144 0.313 0.325 0.361 0.204 0.048 0.397-0.072 0.493-0.253 0.301-0.614 0.421-1.263 0.577-1.901 0.156-0.65 0.301-1.299 0.445-1.949s0.289-1.299 0.421-1.961c0.072-0.325 0.313-1.227 0.385-1.997s-0.361-1.167-0.95-1.059c-0.589 0.108-1.54 0.505-2.165 0.734l-1.877 0.722-1.865 0.734c-0.614 0.265-1.516 0.65-1.72 0.794s-0.313 0.385-0.253 0.589c0.060 0.205 0.217 0.385 0.577 0.349 0.361-0.048 1.191-0.241 1.829-0.409l1.72-0.469c0.144-0.036 0.253 0.132 0.156 0.241-0.734 0.818-2.406 2.719-3.188 3.633-0.962 1.143-1.925 2.286-2.863 3.441-0.614 0.746-1.215 1.504-1.817 2.262-0.12 0.156-0.349 0.168-0.493 0.036l-4.391-4.211c-0.048-0.036-0.084-0.072-0.144-0.108-0.469-0.313-1.095-0.18-1.432 0.277-0.577 0.77-1.143 1.54-1.708 2.322-0.577 0.782-1.143 1.576-1.708 2.358-0.565 0.794-1.131 1.588-1.684 2.382l-0.83 1.203-0.818 1.203c-0.217 0.313-0.144 0.758 0.205 0.974 0.277 0.18 0.674 0.108 0.902-0.156z"></path>
            </svg>
        </template>
        <template #default>
            <div v-if="!fullscreen" class="m-5 space-y-6">
                <div v-if="data.data.officer_approval_waiting.length">
                    <div class="text-[14px] font-bold mb-3">承認待ち（{{ data.data.officer_approval_waiting.length }}）</div>
                    <ExpansionGrid class="gap-x-4" :col="Number(data.col?.split('-')[2] ?? 1)">
                        <ExpansionPanelItem
                            selected-class="selected-panel-item"
                            hide-actions
                            static
                            :tile="true"
                            class="rm-p"
                            v-for="(project, index) in data.data.officer_approval_waiting"
                            :key="project.id ?? index"
                            :value="project.id ?? index"
                            :col="Number(data.col?.split('-')[2] ?? 1)"
                        >
                            <template #title="{ expanded }">
                                <PanelTitle :expanded="expanded">
                                    <div class="text-[14px] flex-1 whitespace-nowrap overflow-hidden text-ellipsis leading-normal flex items-center">
                                        <div class="text-[14px] flex-1 whitespace-nowrap overflow-hidden text-ellipsis leading-normal" v-html="project.name"></div>
                                        <span class="text-xs text-[tomato]">{{ PROJECT_STATUS_LABEL[project.status] ?? '不明' }}</span>
                                    </div>                                
                                </PanelTitle>
                            </template>
                            <template #body>
                                <PanelData class="px-4 py-4 pt-1 space-y-4">
                                    <div class="whitespace-pre-wrap text-[13px] leading-normal">
                                        <span class="p-1 text-xs bg-[var(--bg3)] mr-[10px]">プロジェクト期間</span>
                                        {{ DateTime.fromISO(project.date_start).toLocaleString(DateTime.DATE_SHORT) }} ~ {{DateTime.fromISO(project.date_end).toLocaleString(DateTime.DATE_SHORT)}}
                                    </div>
                                    <div class="whitespace-pre-wrap text-[13px] leading-normal">
                                        <span class="p-1 text-xs bg-[var(--bg3)] mr-[10px]">契約開始日</span>
                                        {{ project.contract_started_at }}
                                    </div>
                                    <div class="whitespace-pre-wrap text-[13px] leading-normal">
                                        <span class="p-1 text-xs bg-[var(--bg3)] mr-[10px]">サービスカテゴリー</span>
                                        <span v-for="category in project.category">{{ category }}</span>
                                    </div>
                                    <div v-for="manager in project.manager">
                                        <div class="flex items-center">
                                            <span class="p-1 text-xs bg-[var(--bg3)] mr-[10px]">管理者</span>
                                            <p>{{ manager.name }}</p>
                                        </div>
                                        
                                    </div>
                                    <div class="mt-3 text-right" v-if="project.id">
                                        <router-link
                                            :to="{ name: 'project-overview-detail', params: { projectId: project.id } }"
                                        >
                                            対応
                                        </router-link>
                                    </div>
                                </PanelData>
                            </template>
                        </ExpansionPanelItem>
                    </ExpansionGrid>
                </div>

                <div v-if="data.data.assign_approval_waiting.length">
                    <div class="text-[14px] font-bold mb-3">確認待ち（{{ data.data.assign_approval_waiting.length }}）</div>
                    <ExpansionGrid class="gap-x-4" :col="Number(data.col?.split('-')[2] ?? 1)">
                        <ExpansionPanelItem
                            selected-class="selected-panel-item"
                            hide-actions
                            static
                            :tile="true"
                            class="rm-p"
                            v-for="(record, index) in data.data.assign_approval_waiting"
                            :key="record.id ?? index"
                            :value="record.id ?? index"
                            :col="Number(data.col?.split('-')[2] ?? 1)"
                        >
                            <template #title="{ expanded }">
                                <PanelTitle :expanded="expanded">
                                    <div class="text-[14px] flex-1 whitespace-nowrap overflow-hidden text-ellipsis leading-normal flex items-center">
                                        <div class="text-[14px] flex-1 whitespace-nowrap overflow-hidden text-ellipsis leading-normal">
                                            {{ record.project_record?.name ?? 'プロジェクト' }}                                            
                                        </div>
                                    </div>                                
                                </PanelTitle>
                            </template>
                            <template #body>
                                <PanelData class="px-4 py-4 pt-1 space-y-4 flex flex-col">
                                    <div>
                                        <span class="text-[11px] text-[gray]">プロジェクト名：</span>{{ record.project_record?.name ?? 'プロジェクト' }}
                                    </div>
                                    <div v-if="record.project_member_role">
                                        <span class="text-[11px] text-[gray]">役割：</span>{{ record.project_member_role.title }}
                                        <div class="mt-3">
                                            <span class="text-[11px] text-[gray]">内容：</span>{{ record.project_member_role.description }}
                                        </div>
                                    </div>
                                    <!-- HR's confirmation items -->
                                    <span class="text-[11px] text-[gray]">確認項目：</span>
                                    <div class="space-y-4" v-if="record.actions?.length && record.actions.filter((a: any) => a.action_type === 'member_confirmation_items').length">
                                        <div v-for="action in record.actions.filter((a: any) => a.action_type === 'member_confirmation_items')" class="whitespace-pre-wrap text-[13px] leading-normal">
                                            {{ action.content }}
                                        </div>
                                    </div>

                                    <!-- Member comment input -->
                                    <div class="flex">
                                        <LongInput 
                                            class="w-full bg-[var(--background-color)]"
                                            :key="`comment-${record.id}`"
                                            v-model="memberComments[record.id]"
                                            :placeHolder="'コメント（理由がある場合に記入）'"
                                        />
                                    </div>

                                    <!-- Decision buttons -->
                                    <div class="flex gap-3 justify-end">
                                        <CommandButton 
                                            :buttons="[
                                                {title: loadingRecords[record.id] ? '処理中...' : '承認', action: () => approveRecord(record)},
                                                {title: loadingRecords[record.id] ? '処理中...' : '取り下げ', action: () => rejectRecord(record)}
                                            ]"
                                        />
                                    </div>
                                </PanelData>
                            </template>
                        </ExpansionPanelItem>
                    </ExpansionGrid>
                </div>

                <div v-if="projectComments.length">
                    <div class="text-[14px] font-bold mb-3">コメント（{{ projectCommentCount }}）</div>
                    <ExpansionGrid class="gap-x-4" :col="Number(data.col?.split('-')[2] ?? 1)">
                        <ExpansionPanelItem
                            selected-class="selected-panel-item"
                            hide-actions
                            static
                            :tile="true"
                            class="rm-p"
                            v-for="(comment, index) in projectComments"
                            :key="`${comment.type}-${comment.project_id}-${comment.period ?? comment.section ?? index}`"
                            :value="`${comment.type}-${comment.project_id}-${comment.period ?? comment.section ?? index}`"
                            :col="Number(data.col?.split('-')[2] ?? 1)"
                        >
                            <template #title="{ expanded }">
                                <PanelTitle :expanded="expanded">
                                    <div class="text-[14px] flex-1 whitespace-nowrap overflow-hidden text-ellipsis leading-normal flex items-center gap-2">
                                        <div class="text-[14px] flex-1 whitespace-nowrap overflow-hidden text-ellipsis leading-normal">
                                            {{ comment.project_name }}
                                        </div>
                                        <span class="side-notification side-notification--comment-only" style="position: static; text-indent: inherit; z-index: 0;">{{ comment.count }}</span>
                                    </div>
                                </PanelTitle>
                            </template>
                            <template #body>
                                <PanelData class="px-4 py-4 pt-1 space-y-3">
                                    <div class="text-[13px] leading-normal">
                                        <span class="p-1 text-xs bg-[var(--bg3)] mr-[10px]">{{ commentTypeLabel(comment) }}</span>
                                        {{ commentTargetLabel(comment) }}
                                    </div>
                                    <div class="text-[12px] text-[gray] leading-normal">
                                        {{ comment.count }}件の未確認コメントがあります。
                                    </div>
                                    <div class="mt-3 text-right">
                                        
                                        <span type="button" class="jump-link text-sm" @click="openProjectComment(comment)">
                                            コメントを見る
                                        </span>
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
import ExpansionGrid from '../ExpansionGrid.vue';
import ExpansionPanelItem from '../ExpansionPanelItem.vue';
import BaseLayout from './BaseLayout.vue';
import PanelTitle from './PanelTitle.vue';
import PanelData from './PanelData.vue';
import { DateTime } from 'luxon';
import { PROJECT_STATUS_LABEL } from '@/utils/tools';
import { DashboardProjectCard } from '@/interface/dashboard';
import { computed, reactive } from 'vue';
import { useApi } from '@/composables/api';
import { useDashboardStore } from '@/store/dashboard';
import LongInput from '@/components/Form/LongInput.vue';
import CommandButton from '@/components/Global/CommandButton.vue';
import { useRouter } from 'vue-router';

const props = defineProps<{
    data: DashboardProjectCard,
    fullscreen: boolean
}>()
type ProjectComment = NonNullable<DashboardProjectCard['data']['comments']>[number];

const emit = defineEmits<{
    resize: [type: string]
    toggle: [el: HTMLElement | null, title: string]
}>()

const api = useApi();
const dashboardStore = useDashboardStore();
const router = useRouter();
const memberComments = reactive<Record<number, string>>({});
const loadingRecords = reactive<Record<number, boolean>>({});
const projectComments = computed(() => props.data.data.comments ?? []);
const projectCommentCount = computed(() => projectComments.value.reduce((total, comment) => total + (comment.count ?? 0), 0));
const dashboardCount = computed(() => (
    props.data.data.officer_approval_waiting.length
    + props.data.data.assign_approval_waiting.length
    + projectCommentCount.value
));

const getMemberConfirmationItems = (record: any): string => {
    if (!record.actions) return '';
    const action = record.actions.find((a: any) => a.action_type === 'member_confirmation_items');
    return action?.content || '';
};

const approveRecord = async (record: any) => {
    if (loadingRecords[record.id]) return;
    loadingRecords[record.id] = true;
    
    try {
        await api.post('/confirm_assign_record', {
            assign_record_id: record.id,
            decision: 'approved',
            member_comment: memberComments[record.id] || '',
        }, {
            toast: '承認しました。',
        });
        
        // Refresh dashboard data
        await dashboardStore.getBatchDashboardData(['projects']);
    } catch (error) {
        console.error('Failed to approve record:', error);
    } finally {
        loadingRecords[record.id] = false;
    }
};

const rejectRecord = async (record: any) => {
    if (loadingRecords[record.id]) return;
    loadingRecords[record.id] = true;
    
    try {
        await api.post('/confirm_assign_record', {
            assign_record_id: record.id,
            decision: 'rejected',
            member_comment: memberComments[record.id] || '',
        }, {
            toast: '取り下げしました。',
        });
        
        // Refresh dashboard data
        await dashboardStore.getBatchDashboardData(['projects']);
    } catch (error) {
        console.error('Failed to reject record:', error);
    } finally {
        loadingRecords[record.id] = false;
    }
};

const commentTypeLabel = (comment: ProjectComment) => {
    if (comment.type === 'finance') return '予算・実績';
    if (comment.type === 'confirmation_item') return '確認事項';
    return '詳細';
};

const commentTargetLabel = (comment: ProjectComment) => {
    if (comment.type === 'finance') return comment.month_label ?? comment.period ?? '対象月';
    if (comment.type === 'confirmation_item') return comment.section ?? '確認事項';
    return 'プロジェクト詳細';
};

const openProjectComment = (comment: ProjectComment) => {
    if (comment.type === 'finance') {
        if (!comment.period) return;
        router.push({
            name: 'finance',
            params: { projectId: comment.project_id },
            query: {
                period: comment.period,
                comment_period: comment.period,
            },
        });
        return;
    }

    if (comment.type === 'confirmation_item') {
        router.push({
            name: 'project-overview-checkitems',
            params: { projectId: comment.project_id },
            query: { comment_type: comment.section },
        });
        return;
    }

    router.push({
        name: 'project-overview-detail',
        params: { projectId: comment.project_id },
        query: { check: 'true' },
    });
};

defineExpose({
    cardType: props.data.type,
})
</script>
<style>
.compact-selector .vs--single.vs--open .vs__selected, .vs--single.vs--loading .vs__selected {
    position: relative;
    opacity: .4;
}
</style>
