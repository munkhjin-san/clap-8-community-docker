<template>
    <div class="si-learning" v-if="issue">
        <div class="post-separetor"></div>
        <div class="text-[13px] font-semibold">職能研修</div>

        <!-- Step 1: generate / show the personalized study material -->
        <div v-if="!material?.content" class="si-box">
            <p class="text-[13px] mb-[10px]">成果目標とこれまでの学びをもとに、AIがあなた専用の職能研修資料を生成します。</p>
            <LoaderButton :loading="generating" content="個人専用研修資料を生成する" style="margin:0;" @triggered="generate">
                <template #icon><AiIcon :size="18" fill="#fff" class="mr-[5px]"/></template>
            </LoaderButton>
        </div>

        <template v-else>
            <div class="si-box">
                <div class="flex items-center justify-between gap-3 flex-wrap mb-[10px]">
                    <p class="text-[13px] font-semibold">個人専用研修資料</p>
                    <LoaderButton :loading="generating" content="再生成" style="margin:0;" @triggered="generate"/>
                </div>
                <div class="si-learning__markdown" v-html="materialHtml"></div>
            </div>

            <!-- Step 2: understanding check -->
            <div class="si-box">
                <p class="text-[13px] font-semibold mb-[12px]">内容を理解しましたか？</p>
                <div v-for="opt in understandOptions" :key="String(opt.value)" class="flex items-center py-[4px]">
                    <input :id="`si-understand-${opt.value}`" v-model="understand" type="radio" name="si-understand" :value="opt.value" class="fish-eye">
                    <label :for="`si-understand-${opt.value}`" class="ml-[10px] cursor-pointer text-[13px]">{{ opt.label }}</label>
                </div>
                <div v-if="understand === true" class="mt-[12px]">
                    <LongInput v-model="importantPoint" place-holder="特に重要だと理解した点" name="siImportantPoint"/>
                </div>
                <LoaderButton :loading="savingUnderstanding" content="保存" style="margin:0; margin-top:12px;" @triggered="saveUnderstanding"/>
            </div>

            <!-- Steps 3-6: discussion topic + portfolio -->
            <div v-if="material?.understand === true" class="si-box">
                <p class="text-[13px] font-semibold mb-[12px]">ポートフォリオ</p>

                <div class="mb-[16px]">
                    <p class="text-[12px] mb-[6px]">グループディスカッションで選んだテーマ</p>
                    <LongInput v-model="discussionTopic" place-holder="研修資料末尾の3つの候補から1つ選び、記入してください" name="siDiscussionTopic"/>
                </div>
                <div class="mb-[16px]">
                    <ShortInput v-model="publicTitle" label="タイトル" place-holder="タイトル" name="siPortfolioTitle" rules="max:250"/>
                </div>
                <div class="mb-[16px]">
                    <p class="text-[12px] mb-[6px]">研修とディスカッションで得た学び・成果目標に向けた実践</p>
                    <LongInput ref="publicContentRef" v-model="publicContent" place-holder="内容" name="siPortfolioContent" rules="required"/>
                </div>
                <div class="mb-[16px]">
                    <p class="text-[12px] mb-[6px]">気づき・感想</p>
                    <LongInput v-model="noticed" place-holder="気づき・感想" name="siPortfolioNoticed"/>
                </div>

                <div v-if="portfolio?.status === 3" class="text-[#64bc44] text-[13px] font-semibold mb-[12px]">提出済み</div>
                <div class="flex gap-[15px] flex-wrap justify-center">
                    <LoaderButton :loading="savingDraft" content="下書き保存" style="margin:0;" @triggered="savePortfolio(false)"/>
                    <LoaderButton :loading="submitting" content="提出" style="margin:0;" @triggered="submitPortfolio"/>
                </div>
            </div>
        </template>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useApi } from '@/composables/api'
import { useDialog } from '@/composables/dialog'
import { renderMarkdown } from '@/utils/markdown'
import LoaderButton from '@/components/Global/LoaderButton.vue'
import LongInput from '@/components/Form/LongInput.vue'
import ShortInput from '@/components/Form/ShortInput.vue'
import AiIcon from '@/components/Icons/AiIcon.vue'
import { ProjectGoal, SalaryIssue } from '@/interface/projectInterface'

interface LearningMaterial { content: string | null; understand: boolean | null; important_point: string | null }
interface LearningPortfolio { public_title: string | null; public_content: string | null; noticed: string | null; discussion_topic: string | null; status: number }
interface LearningState { theme_title: string | null; has_prior_portfolio: boolean; material: LearningMaterial | null; portfolio: LearningPortfolio | null }

const props = defineProps<{ issue: SalaryIssue; goal: ProjectGoal }>()

const api = useApi()
const { ping, ask } = useDialog()

const material = ref<LearningMaterial | null>(null)
const portfolio = ref<LearningPortfolio | null>(null)
const generating = ref(false)
const savingUnderstanding = ref(false)
const savingDraft = ref(false)
const submitting = ref(false)

const understand = ref<boolean | null>(null)
const importantPoint = ref('')
const discussionTopic = ref('')
const publicTitle = ref('')
const publicContent = ref('')
const noticed = ref('')
const publicContentRef = ref<InstanceType<typeof LongInput> | null>(null)

const understandOptions = [
    { value: true, label: '理解した' },
    { value: false, label: '理解できなかった' },
]

const materialHtml = computed(() => renderMarkdown(material.value?.content))

const applyState = (state: LearningState) => {
    material.value = state.material
    portfolio.value = state.portfolio
    understand.value = state.material?.understand ?? null
    importantPoint.value = state.material?.important_point ?? ''
    discussionTopic.value = state.portfolio?.discussion_topic ?? ''
    publicTitle.value = state.portfolio?.public_title ?? state.theme_title ?? ''
    publicContent.value = state.portfolio?.public_content ?? ''
    noticed.value = state.portfolio?.noticed ?? ''
}

onMounted(async () => {
    const state = await api.get(`/salary_issue/${props.issue.id}/learning`)
    if (state) applyState(state)
})

const generate = async () => {
    if (material.value?.content) {
        const answer = await ask('研修資料を再生成します。よろしいですか？')
        if (!answer.value) return
    }
    generating.value = true
    try {
        const data = await api.post(`/salary_issue/${props.issue.id}/generate_study_material`, {})
        if (data?.content) {
            material.value = { content: data.content, understand: null, important_point: null }
            understand.value = null
            importantPoint.value = ''
        } else {
            ping('研修資料の生成に失敗しました。しばらくしてから再度お試しください。')
        }
    } finally {
        generating.value = false
    }
}

const saveUnderstanding = async () => {
    if (understand.value === null) {
        ping('理解度を選択してください。')
        return
    }
    if (understand.value === true && !importantPoint.value.trim()) {
        ping('特に重要だと理解した点を入力してください。')
        return
    }
    savingUnderstanding.value = true
    try {
        const state = await api.post(`/salary_issue/${props.issue.id}/learning/understanding`, {
            understand: understand.value,
            important_point: understand.value ? importantPoint.value : null,
        })
        if (state) applyState(state)
    } finally {
        savingUnderstanding.value = false
    }
}

const savePortfolio = async (submit: boolean) => {
    if (submit) {
        const valid = await publicContentRef.value?.validate?.()
        if (valid && !valid.valid) {
            ping('内容を入力してください。')
            return
        }
    }
    if (submit) submitting.value = true
    else savingDraft.value = true
    try {
        const state = await api.post(`/salary_issue/${props.issue.id}/learning/portfolio`, {
            public_title: publicTitle.value,
            public_content: publicContent.value,
            noticed: noticed.value,
            discussion_topic: discussionTopic.value,
            submit,
        }, { toast: submit ? '提出しました。' : '下書きを保存しました。' })
        if (state) applyState(state)
    } finally {
        submitting.value = false
        savingDraft.value = false
    }
}

const submitPortfolio = async () => {
    const answer = await ask('ポートフォリオを提出します。メンターへ共有されます。よろしいですか？')
    if (!answer.value) return
    await savePortfolio(true)
}
</script>

<style scoped>
.si-learning {
    display: flex;
    flex-direction: column;
    gap: 20px;
}
.si-learning__markdown {
    font-size: 14px;
    line-height: 2;
    white-space: normal;
    word-break: break-word;
}
.si-learning__markdown :deep(p),
.si-learning__markdown :deep(ul),
.si-learning__markdown :deep(ol) {
    margin: 0 0 12px;
}
.si-learning__markdown :deep(h1),
.si-learning__markdown :deep(h2),
.si-learning__markdown :deep(h3) {
    margin: 18px 0 10px;
    font-weight: 700;
    line-height: 1.6;
}
</style>
