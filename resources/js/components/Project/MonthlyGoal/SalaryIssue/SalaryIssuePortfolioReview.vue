<template>
    <div class="flex flex-col gap-[24px] relative" v-if="issue">
        <IssueStatus :issue="issue" :goal="goal"/>

        <div>
            <div class="text-[13px] font-semibold">テーマ</div>
            <div class="kadai-content">{{ issue.theme }}</div>
        </div>
        <div>
            <div class="text-[13px] font-semibold">メンター</div>
            <div class="kadai-content">{{ evaluationData?.mentor?.name ?? '未設定' }}</div>
        </div>
        <div v-if="goal">
            <div class="text-[13px] font-semibold">成果目標</div>
            <div class="kadai-content">{{ goal.title || goal.outcome_goal }}</div>
        </div>

        <div v-if="portfolio" class="flex flex-col gap-[16px]">
            <div class="text-[13px] font-semibold">ポートフォリオ</div>

            <template v-if="canEdit">
                <ShortInput label="タイトル" name="pfTitle" rules="required" :initialValue="editTitle" v-model="editTitle" />
                <LongInput ref="contentRef" placeHolder="ポートフォリオの内容" name="pfContent" rules="required" :initialValue="editContent" v-model="editContent" />
            </template>
            <template v-else>
                <div>
                    <div class="text-[12px] text-[var(--third-color)] mb-[4px]">タイトル</div>
                    <div class="kadai-content">{{ portfolio.public_title || '（未入力）' }}</div>
                </div>
                <div>
                    <div class="text-[12px] text-[var(--third-color)] mb-[4px]">内容</div>
                    <div class="kadai-content">{{ portfolio.public_content || '（未入力）' }}</div>
                </div>
            </template>

            <div v-if="portfolio.discussion_topic">
                <div class="text-[12px] text-[var(--third-color)] mb-[4px]">選んだディスカッションテーマ</div>
                <div class="kadai-content">{{ portfolio.discussion_topic }}</div>
            </div>
            <div v-if="portfolio.positive_feedback">
                <div class="text-[12px] text-[var(--third-color)] mb-[4px]">ポジティブフィードバック</div>
                <div class="kadai-content">{{ portfolio.positive_feedback }}</div>
            </div>
            <div v-if="portfolio.negative_feedback">
                <div class="text-[12px] text-[var(--third-color)] mb-[4px]">ネガティブフィードバック</div>
                <div class="kadai-content">{{ portfolio.negative_feedback }}</div>
            </div>
            <div v-if="portfolio.noticed">
                <div class="text-[12px] text-[var(--third-color)] mb-[4px]">気づき・感想</div>
                <div class="kadai-content">{{ portfolio.noticed }}</div>
            </div>
        </div>

        <div v-if="issue.comment">
            <div class="text-[13px] font-semibold">コメント</div>
            <div class="kadai-content">{{ issue.comment }}</div>
        </div>

        <!-- Reviewer comment (mentor / HR) -->
        <div v-if="isReviewer">
            <div class="text-[13px] font-semibold mb-[6px]">コメント（任意）</div>
            <LongInput placeHolder="承認・差戻の理由など" name="reviewComment" v-model="comment" />
        </div>

        <MessageArea which="salary_issue" :item="issue" :passing-data="passingData" :key="`si-review-${issue.id}`" @refresh="refresh"/>

        <!-- Challenger edit + re-apply (差戻中) -->
        <div v-if="canEdit" class="flex gap-5 mb-3 justify-center flex-wrap">
            <LoaderButton style="margin:0;" :loading="saving" content="保存して再申請" @triggered="saveAndReapply"/>
        </div>

        <!-- Mentor review -->
        <div v-if="status === 12 && (isMentor || auth.isAdmin)" class="flex gap-5 mb-3 justify-center flex-wrap">
            <LoaderButton style="margin:0;" content="差戻" @triggered="approve(13, '差戻')"/>
            <LoaderButton style="margin:0;" content="メンター承認（人事へ）" @triggered="approve(14, 'メンター承認')"/>
        </div>

        <!-- HR review -->
        <template v-if="status === 14 && auth.isAdmin">
            <div class="flex justify-center">
                <select v-model.number="finalStatus" class="py-[6px] px-[10px] text-[13px] text-[var(--primary-color)] bg-[var(--background-color)] border border-solid border-[var(--formBorder)]">
                    <option :value="10">昇給達成（完了）</option>
                    <option :value="11">未達成（完了）</option>
                </select>
            </div>
            <div class="flex gap-5 mb-3 justify-center flex-wrap">
                <LoaderButton style="margin:0;" content="人事差戻" @triggered="approve(13, '人事差戻')"/>
                <LoaderButton style="margin:0;" content="人事承認（確定）" @triggered="approve(finalStatus, '人事承認')"/>
            </div>
        </template>
    </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { storeToRefs } from 'pinia'
import { useApi } from '@/composables/api'
import { useAuthUserStore } from '@/store/auth'
import { useDashboardGoalsStore } from '@/store/dashboardGoals'
import { ProjectGoal, SalaryIssue } from '@/interface/projectInterface'
import IssueStatus from './IssueStatus.vue'
import MessageArea from '../../MessageArea.vue'
import LoaderButton from '@/components/Global/LoaderButton.vue'
import ShortInput from '@/components/Form/ShortInput.vue'
import LongInput from '@/components/Form/LongInput.vue'

const props = defineProps<{ issue: SalaryIssue; goal: ProjectGoal }>()
const emit = defineEmits<{ refresh: [] }>()

const api = useApi()
const auth = useAuthUserStore()
const { evaluationData } = storeToRefs(useDashboardGoalsStore())

const passingData = {
    path: '/project_goal_comment_create',
    title: '進捗報告・メッセージ',
    file_path: 'project_goal_report_files',
}

const portfolio = computed(() => props.issue.portfolio ?? null)
const status = computed(() => props.issue.status)
const isMentor = computed(() => evaluationData.value?.mentor_id === auth.id)
const isOwner = computed(() => auth.id === props.goal.user_id)
const canEdit = computed(() => status.value === 13 && isOwner.value)
const isReviewer = computed(() => (status.value === 12 && (isMentor.value || auth.isAdmin)) || (status.value === 14 && auth.isAdmin))

const editTitle = ref(portfolio.value?.public_title ?? '')
const editContent = ref(portfolio.value?.public_content ?? '')
const contentRef = ref<InstanceType<typeof LongInput> | null>(null)
const comment = ref('')
const finalStatus = ref<number>(10)
const saving = ref(false)

const refresh = () => emit('refresh')

const approve = async (newStatus: number, label: string) => {
    const res = await api.put('/approve_salary_issue', { id: props.issue.id, status: newStatus, comment: comment.value || null }, {
        ask: `この昇給課題を${label}しますか？`,
        toast: `${label}しました。`,
    })
    if (!res) return
    comment.value = ''
    refresh()
}

const saveAndReapply = async () => {
    if (!portfolio.value) return
    const valid = contentRef.value?.validate ? await contentRef.value.validate() : { valid: true }
    if (valid && !valid.valid) return
    if (!editTitle.value.trim() || !editContent.value.trim()) return

    saving.value = true
    try {
        await api.post('/update_lesson_portfolio', {
            id: portfolio.value.id,
            params: { public_title: editTitle.value, public_content: editContent.value },
        })
        await api.put('/approve_salary_issue', { id: props.issue.id, status: 12, comment: null }, {
            toast: '再申請しました。',
        })
        refresh()
    } finally {
        saving.value = false
    }
}
</script>
