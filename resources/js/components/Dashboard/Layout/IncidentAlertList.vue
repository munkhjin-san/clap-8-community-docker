<template>
    <div>
        <ExpansionGrid class="gap-x-4" :col="col">
            <ExpansionPanelItem
                v-for="(candidate, index) in candidates"
                :key="candidate.id ?? index"
                selected-class="selected-panel-item"
                hide-actions
                static
                :tile="true"
                class="rm-p"
                :value="candidate.id ?? index"
                :col="col"
            >
                <template #title="{ expanded }">
                    <PanelTitle :expanded="expanded">
                        <div class="mr-2 mx-0.5 rounded-full bg-[tomato] w-1.5 min-w-1.5 h-1.5 custom-heartbeat"></div>
                        <UserPanel v-if="candidate.subject" with-name disable-instant size="22" :user="(candidate.subject as any)" />
                        <div v-else class="truncate text-[13px] font-medium leading-normal">{{ subjectName(candidate) }}</div>
                    </PanelTitle>
                </template>
                <template #body>
                    <PanelData>
                        <div class="flex flex-col gap-2 text-[12px]">
                            <div>
                                <span class="incident-alert-tag">{{ sourceLabel(candidate) }}</span>
                            </div>
                            <div v-for="line in detailLines(candidate)" :key="line.label" class="break-words">
                                <span class="text-[gray]">{{ line.label }}：</span>{{ line.value }}
                            </div>
                            <div class="flex flex-wrap gap-4 justify-end mt-1">
                                <button
                                    type="button"
                                    class="jump-link bg-inherit text-[12px] disabled:opacity-50 disabled:pointer-events-none"
                                    :disabled="isProcessing(candidate.id)"
                                    @click.stop="openIncidentCreate(candidate)"
                                >インシデント化する</button>
                                <button
                                    type="button"
                                    class="jump-link bg-inherit text-[12px] disabled:opacity-50 disabled:pointer-events-none"
                                    :disabled="isProcessing(candidate.id)"
                                    @click.stop="dismissCandidate(candidate)"
                                >インシデントではない</button>
                            </div>
                        </div>
                    </PanelData>
                </template>
            </ExpansionPanelItem>
        </ExpansionGrid>

        <Teleport to="body">
            <Transition name="modalFade">
                <IncidentDetailModal
                    v-if="detailModalOpen"
                    create-mode
                    :initial-incident="modalInitialIncident"
                    @created="handleIncidentCreated"
                    @close="detailModalOpen = false"
                />
            </Transition>
        </Teleport>
    </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { DateTime } from 'luxon';
import type { IncidentCandidate } from '@/interface/dashboard';
import type { Incident } from '@/interface/incident';
import ExpansionGrid from '../ExpansionGrid.vue';
import ExpansionPanelItem from '../ExpansionPanelItem.vue';
import PanelTitle from './PanelTitle.vue';
import PanelData from './PanelData.vue';
import UserPanel from '@/components/Global/UserPanel.vue';
import IncidentDetailModal from '@/components/Incident/IncidentDetailModal.vue';
import { useDialog } from '@/composables/dialog';
import { useApi } from '@/composables/api';
import { useDashboardStore } from '@/store/dashboard';

withDefaults(defineProps<{
    candidates: IncidentCandidate[]
    col?: number
}>(), {
    col: 1,
})

const { askInput } = useDialog()
const api = useApi()
const dashboardStore = useDashboardStore()

const processingIds = ref<Set<number>>(new Set())
const isProcessing = (id: number) => processingIds.value.has(id)

const detailModalOpen = ref(false)
const activeCandidate = ref<IncidentCandidate | null>(null)
const modalInitialIncident = ref<Partial<Incident>>({})

const formatDate = (date?: string | null) => {
    if (!date) return ''
    const parsed = DateTime.fromISO(date)
    return parsed.isValid ? parsed.toFormat('yyyy/MM/dd') : date
}

const missedCount = (candidate: IncidentCandidate) =>
    candidate.context?.missed_count ?? candidate.context?.missed_dates?.length ?? 0

const sourceLabel = (candidate: IncidentCandidate): string => {
    switch (candidate.source_type) {
        case 'daily_report_streak':
            return `日報未申請 ${missedCount(candidate)}回`
        case 'outcome_goal_submission':
            return '成果目標 未申請'
        case 'outcome_goal_pm_approval':
            return '成果目標 PM未承認'
        default:
            return 'アラート'
    }
}

const subjectName = (candidate: IncidentCandidate): string => candidate.subject?.name || '不明'

const detailLines = (candidate: IncidentCandidate): { label: string; value: string }[] => {
    const lines: { label: string; value: string }[] = []
    lines.push({ label: 'プロジェクト', value: candidate.project?.name || '未設定' })

    if (candidate.source_type === 'daily_report_streak') {
        const dates = (candidate.context?.missed_dates ?? []).map(formatDate).filter(Boolean).join('、')
        lines.push({ label: '未申請日', value: dates || '不明' })
    } else {
        lines.push({ label: '成果目標', value: candidate.context?.goal_title || '未設定' })
        if (candidate.source_type === 'outcome_goal_submission') {
            lines.push({ label: '終了日', value: formatDate(candidate.context?.end_date) || '不明' })
        } else {
            lines.push({ label: '対象者', value: candidate.context?.goal_owner_name || '不明' })
            lines.push({ label: '申請日', value: formatDate(candidate.context?.submitted_at) || '不明' })
        }
    }
    return lines
}

const buildDescription = (candidate: IncidentCandidate): string => {
    if (candidate.source_type === 'daily_report_streak') {
        const dates = (candidate.context?.missed_dates ?? []).map(formatDate).filter(Boolean).join('、')
        return `${subjectName(candidate)}さんの日報未申請が${missedCount(candidate)}回発生しています。（未申請日：${dates}）`
    }
    if (candidate.source_type === 'outcome_goal_submission') {
        return `${subjectName(candidate)}さんの成果目標「${candidate.context?.goal_title ?? ''}」の結果入力が未申請です。`
    }
    return `${candidate.context?.goal_owner_name ?? ''}さんの成果目標「${candidate.context?.goal_title ?? ''}」がPM未承認です。`
}

const refresh = async () => {
    await dashboardStore.getBatchDashboardData(['incidentAlerts'])
}

const openIncidentCreate = (candidate: IncidentCandidate) => {
    activeCandidate.value = candidate
    const today = DateTime.now().toISODate()
    const occurred =
        candidate.source_type === 'daily_report_streak'
            ? (candidate.context?.missed_dates?.slice(-1)[0] ?? today)
            : (candidate.context?.end_date ?? today)

    modalInitialIncident.value = {
        description: buildDescription(candidate),
        caused_by: candidate.subject?.id ?? null,
        caused_by_user: (candidate.subject ?? null) as any,
        project_record_id: candidate.project_record_id,
        occurred_date: occurred,
        reported_date: today,
    }
    detailModalOpen.value = true
}

const handleIncidentCreated = async (incident: Incident) => {
    const candidate = activeCandidate.value
    detailModalOpen.value = false

    if (!candidate || !incident?.id) {
        activeCandidate.value = null
        return
    }

    processingIds.value.add(candidate.id)
    try {
        await api.post(
            '/incident_candidate_decision',
            {
                candidate_id: candidate.id,
                decision: 'create_incident',
                resulting_incident_id: incident.id,
            },
            { toast: 'インシデントを作成しました。' },
        )
        await refresh()
    } finally {
        processingIds.value.delete(candidate.id)
        activeCandidate.value = null
    }
}

const dismissCandidate = async (candidate: IncidentCandidate) => {
    const { input, decision } = await askInput(
        'インシデントとしない理由を入力してください',
        {
            required: true,
            multiline: true,
            maxLength: 2000,
            placeholder: '理由を入力してください',
            submitText: '確定',
            validate: (v: string) => (v && v.trim().length ? null : '理由を入力してください。'),
        },
    )

    if (!decision?.value) return

    const reason = (input ?? '').trim()
    if (!reason) return

    processingIds.value.add(candidate.id)
    try {
        await api.post(
            '/incident_candidate_decision',
            {
                candidate_id: candidate.id,
                decision: 'dismiss',
                reason,
            },
            { toast: 'インシデントではないと記録しました。' },
        )
        await refresh()
    } finally {
        processingIds.value.delete(candidate.id)
    }
}
</script>

<style scoped>
.incident-alert-tag {
    display: inline-flex;
    align-items: center;
    flex-shrink: 0;
    width: fit-content;
    background: rgba(249, 115, 22, 0.14);
    color: #c2410c;
    font-size: 11px;
    font-weight: 700;
    line-height: 1;
    padding: 5px 8px;
    border-radius: 4px;
}
</style>
