<template>
    <section v-if="state" ref="rootRef" class="lh">
        <p class="lh__title">学習履歴</p>

        <p v-if="!orderedAttempts.length" class="lh__empty">まだ学習履歴はありません。最初の研修を始めましょう。</p>

        <div v-else class="lh__cards">
            <article
                v-for="attempt in orderedAttempts"
                :key="attempt.id"
                class="lh-card"
                :class="{ 'lh-card--active': attempt.status < 3 }"
            >
                <div class="lh-card__top">
                    <span class="lh-card__no">{{ attempt.attempt_no }}回目</span>
                    <span class="lh-card__type" :class="`lh-card__type--${attempt.path}`">{{ pathLabel(attempt.path) }}</span>
                    <span class="lh-card__spacer"></span>
                    <span class="lh-card__state" :class="{ 'lh-card__state--done': attempt.status >= 3 }">
                        {{ attempt.status >= 3 ? '修了' : '学習中' }}
                    </span>
                    <span v-if="formatDate(attempt.created_at)" class="lh-card__date">{{ formatDate(attempt.created_at) }}</span>
                </div>

                <div class="lh-card__steps">
                    <template v-for="(stage, i) in stagesFor(attempt.status)" :key="stage.label">
                        <span v-if="i > 0" class="lh-step__conn" :class="{ 'lh-step__conn--done': stage.prevDone }"></span>
                        <span
                            class="lh-step"
                            :class="{ 'lh-step--done': stage.done, 'lh-step--current': stage.current, 'lh-step--link': stepClickable(attempt, i) }"
                            @click="onStepClick(attempt, i)"
                        >
                            <span class="lh-step__mark"></span>
                            <span class="lh-step__label">{{ stage.label }}</span>
                        </span>
                    </template>
                </div>

                <div v-if="attempt.status < 3 && attempt.id === currentAttemptId" class="lh-card__action">
                    <button type="button" class="lh__btn lh__btn--primary" @click="goStage(attempt.status)">研修を続ける</button>
                    <button
                        v-if="attempt.status < 1"
                        type="button"
                        class="lh__btn lh__btn--ghost lh__btn--sm"
                        :disabled="busy"
                        @click="removeAttempt(attempt.id)"
                    >
                        削除
                    </button>
                </div>
                <div v-else-if="attempt.status < 3" class="lh-card__paused">中断中</div>
            </article>
        </div>

        <!-- Start a NEW learning (hidden while an attempt is in progress). -->
        <div v-if="showStart" class="lh__foot">
            <LoaderButton content="研修を始める" :loading="busy" @triggered="onStart" />
        </div>

        <Teleport to="body">
            <transition name="modalFade">
                <div v-if="mode" class="lh-overlay" @mousedown="closeModal">
                    <div class="lh-modal" @mousedown.stop>
                        <div class="lh-modal__head">
                            <span>{{ mode === 'challenge' ? `成果目標を選択（${spanLabel}）` : '学習方法を選択' }}</span>
                            <button type="button" class="lh-modal__close" @click="closeModal">✕</button>
                        </div>

                        <div v-if="mode === 'choose'" class="lh-modal__body lh-modal__choose">
                            <!-- Path 2 (もう一度学ぶ) is hidden for now; keep the handler so it can be re-enabled. -->
                            <!-- <button v-if="SHOW_LEARN_AGAIN" type="button" class="lh__btn lh__btn--primary lh__btn--block" :disabled="busy" @click="learnAgain">
                                もう一度学ぶ（AI個別教材）
                            </button> -->
                            <!-- <button v-if="state.options.path3" type="button" class="lh__btn lh__btn--primary lh__btn--block" @click="openChallenge">
                                昇給課題として学習する
                            </button> -->
                            <LoaderButton content="昇給課題として学習する" :loading="busy" @triggered="openChallenge" />
                        </div>

                        <div v-else class="lh-modal__body">
                            <template v-if="challengeOptions">
                                <p v-if="!challengeOptions.eligible" class="lh__notice">{{ challengeOptions.reason }}</p>
                                <p v-if="!challengeOptions.goals.length" class="lh__notice">
                                    今期の成果目標がありません。先に成果目標を作成してください。
                                </p>
                                <ul v-else class="lh__goals">
                                    <li
                                        v-for="goal in challengeOptions.goals"
                                        :key="goal.goal_id"
                                        class="lh__goal"
                                        :class="{ 'lh__goal--disabled': !goal.selectable }"
                                    >
                                        <div class="lh__goal-row">
                                            <div class="lh__goal-main">
                                                <span class="lh__goal-title">{{ goal.title || '（無題の目標）' }}</span>
                                                <div class="lh__goal-period">{{ goalPeriod(goal) }}</div>
                                                <div class="lh__goal-score">評価点：{{ goal.score }}点</div>
                                            </div>
                                            <div v-if="goal.selectable" class="lh__goal-action">
                                                <LoaderButton
                                                    content="選択する"
                                                    :loading="busy"
                                                    @triggered="submitChallenge(goal.goal_id)"
                                                />
                                            </div>
                                        </div>
                                        <p v-if="!goal.selectable && goal.reason" class="lh__goal-note">{{ goal.reason }}</p>
                                    </li>
                                </ul>
                            </template>
                            <div v-else class="lh__notice">
                                <div class="spinner-mini"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </transition>
        </Teleport>
    </section>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useLearningApi } from '@/composables/learningApi'
import { useDialog } from '@/composables/dialog'
import type { LearningThemeChallengeOptions, LearningThemeState } from '@/types/learning'
import LoaderButton from '@/components/Global/LoaderButton.vue'

const props = defineProps<{ themeId: number }>()
const emit = defineEmits<{ changed: [] }>()

const router = useRouter()
const learningApi = useLearningApi()
const { ask } = useDialog()

// Confirm before starting a new attempt when one is already in progress
// (it will be set aside — only the newest attempt is resumable).
const confirmSupersede = async () => {
    if (!hasInProgress.value) return true
    const answer = await ask('進行中の学習があります。新しい学習を開始すると、進行中の学習は中断され履歴に残ります。よろしいですか？')
    return !!answer?.value
}

const state = ref<LearningThemeState | null>(null)
const busy = ref(false)
const mode = ref<null | 'choose' | 'challenge'>(null)
const challengeOptions = ref<LearningThemeChallengeOptions | null>(null)
const rootRef = ref<HTMLElement | null>(null)

// Lock the background scroll (the nearest scrollable ancestor) while the modal is open.
let lockedEl: HTMLElement | null = null
const findScrollable = (el: HTMLElement | null): HTMLElement | null => {
    let node = el
    while (node) {
        const oy = getComputedStyle(node).overflowY
        if ((oy === 'auto' || oy === 'scroll') && node.scrollHeight > node.clientHeight) return node
        node = node.parentElement
    }
    return null
}
watch(mode, (m) => {
    if (m && !lockedEl) {
        lockedEl = findScrollable(rootRef.value?.parentElement ?? null)
        if (lockedEl) lockedEl.style.overflowY = 'hidden'
    } else if (!m && lockedEl) {
        lockedEl.style.overflowY = ''
        lockedEl = null
    }
})
onBeforeUnmount(() => {
    if (lockedEl) { lockedEl.style.overflowY = ''; lockedEl = null }
})

const orderedAttempts = computed(() =>
    [...(state.value?.attempts ?? [])].sort((a, b) => b.attempt_no - a.attempt_no)
)
// Only the latest attempt is the "active" one that stage routes operate on.
const currentAttemptId = computed(() => state.value?.current?.id ?? null)
const hasInProgress = computed(() => !!state.value?.current && state.value.current.status < 3)
// Path 2 (もう一度学ぶ) is temporarily hidden — keep the handler wired so it can be
// switched back on by flipping this flag.
const SHOW_LEARN_AGAIN = false
// Chooser is reachable whenever cleared (a repeater/challenger can start a new
// attempt even while one is in progress); first-timers get path 1 only. When
// path 2 is hidden, a cleared user only needs the entry if path 3 is available.
const showStart = computed(() => {
    // Never offer a new start while an attempt is still incomplete — one
    // in-progress portfolio at a time.
    if (hasInProgress.value) return false
    if (orderedAttempts.value.length === 0) return true
    if (!state.value?.cleared) return false
    return SHOW_LEARN_AGAIN || !!state.value?.options.path3
})
const spanLabel = computed(() => {
    const span = challengeOptions.value?.span
    if (!span) return '今期'
    return `${span.year}年${span.which_half === 'first' ? '上期' : '下期'}`
})

const STAGE_LABELS = ['知識研修', 'ディスカッション', 'ポートフォリオ'] as const
const stagesFor = (status: number) =>
    STAGE_LABELS.map((label, index) => ({
        label,
        done: status >= index + 1,
        current: status === index,
        prevDone: status >= index,
    }))

const load = async () => {
    state.value = await learningApi.getLearnerThemeState(props.themeId)
}
onMounted(load)

const pathLabel = (path: number) => {
    if (path === 3) return '昇給課題'
    if (path === 2) return '再学習'
    return '初回学習'
}
const formatDate = (value: string | null) => {
    if (!value) return ''
    const d = new Date(value)
    return Number.isNaN(d.getTime()) ? '' : d.toLocaleDateString('ja-JP')
}

const goalPeriod = (goal: { start_date: string | null; end_date: string | null }) => {
    const start = formatDate(goal.start_date)
    const end = formatDate(goal.end_date)
    if (!start && !end) return '期間未設定'
    return `${start || '—'} 〜 ${end || '—'}`
}

// Stage index -> route: 0 知識研修 (basic), 1 ディスカッション, 2 ポートフォリオ.
const STAGE_ROUTES = ['basic', 'discussion', 'portfolio'] as const
const goStage = (index: number) => {
    const i = Math.min(Math.max(index, 0), 2)
    router.push({ name: STAGE_ROUTES[i], params: { lessonThemeId: props.themeId } })
}
// Resume the current attempt at whatever stage it's on (first-timer → basic).
const enterLearning = () => goStage(state.value?.current?.status ?? 0)

// A stage is openable on the current attempt once it's been reached. Finished
// attempts (status 3) keep all stages openable so the learner can review them —
// the stage views render their completed state read-only.
const stepClickable = (attempt: { id: number; status: number }, index: number) =>
    attempt.id === currentAttemptId.value && attempt.status >= index
const onStepClick = (attempt: { id: number; status: number }, index: number) => {
    if (stepClickable(attempt, index)) goStage(index)
}

const removeAttempt = async (portfolioId: number) => {
    if (busy.value) return
    busy.value = true
    try {
        const res = await learningApi.deleteLearningAttempt(props.themeId, portfolioId)
        if (res) {
            await load()
            emit('changed')
        }
    } finally {
        busy.value = false
    }
}

const onStart = () => {
    if (!state.value?.cleared) {
        enterLearning()
        return
    }
    mode.value = 'choose'
}

const closeModal = () => {
    if (busy.value) return
    mode.value = null
}

const learnAgain = async () => {
    if (busy.value) return
    if (!(await confirmSupersede())) return
    busy.value = true
    try {
        const created = await learningApi.startLearningAttempt(props.themeId)
        if (created) {
            await load()
            emit('changed')
            enterLearning()
        }
    } finally {
        busy.value = false
    }
}

const openChallenge = async () => {
    mode.value = 'challenge'
    challengeOptions.value = null
    challengeOptions.value = await learningApi.getThemeChallengeOptions(props.themeId)
}

const submitChallenge = async (goalId: number) => {
    if (busy.value) return
    if (!(await confirmSupersede())) return
    busy.value = true
    try {
        const created = await learningApi.createThemeChallenge(props.themeId, goalId)
        if (created) {
            mode.value = null
            await load()
            emit('changed')
        }
    } finally {
        busy.value = false
    }
}

defineExpose({ reload: load })
</script>

<style scoped>
.lh { margin: 0 20px 20px; color: var(--primary-color); }
.lh__title { margin: 0 0 12px; font-size: 13px; }
.lh__empty {
    margin: 0 0 12px;
    padding: 20px;
    font-size: 12px;
    color: var(--third-color);
    text-align: center;
    border: 1px dashed var(--formBorder);
}

.lh__cards { display: flex; flex-direction: column; gap: 10px; margin-bottom: 14px; }

.lh-card {
    border: 1px solid var(--formBorder);
    background: var(--background-color);
    padding: 22px 24px;
}
.lh-card--active {
    border-color: var(--primary-color);
    background: var(--selected-background);
}
.lh-card__top { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; margin-bottom: 20px; }
.lh-card__no { font-size: 13px; }
.lh-card__type { font-size: 12px; padding: 5px 14px; border: solid thin var(--formBorder); white-space: nowrap; }
.lh-card__type--3 { border-color: var(--primary-color); }
.lh-card__spacer { flex: 1; }
.lh-card__state {
    font-size: 11px;
    padding: 4px 12px;
    border-radius: 999px;
    background: var(--bg3);
    color: var(--third-color);
    white-space: nowrap;
}
.lh-card__state--done { background: rgba(100, 188, 68, 0.16); color: #4a9e2f; }
.lh-card__date { font-size: 11px; color: var(--third-color); white-space: nowrap; }

.lh-card__steps { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.lh-step { display: inline-flex; align-items: center; gap: 7px; }
.lh-step__mark {
    width: 12px;
    height: 12px;
    flex: none;
    border-radius: 50%;
    border: 1px solid var(--check-inactive);
    background: var(--background-color);
}
.lh-step__label { font-size: 12px; color: var(--third-color); white-space: nowrap; }
.lh-step--done .lh-step__mark { background: #64bc44; border-color: #64bc44; }
.lh-step--done .lh-step__label { color: var(--primary-color); }
.lh-step--current .lh-step__mark { background: var(--primary-color); border-color: var(--primary-color); }
.lh-step--current .lh-step__label { color: var(--primary-color); }
.lh-step__conn { width: 18px; height: 1px; background: var(--formBorder); flex: none; }
.lh-step__conn--done { background: #64bc44; }
.lh-step--link { cursor: pointer; }
.lh-step--link:hover .lh-step__label { text-decoration: underline; }

.lh-card__action { margin-top: 14px; display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.lh-card__paused { margin-top: 12px; font-size: 11px; color: var(--third-color); }

.lh__foot { margin-top: 4px; display: flex; justify-content: center; }
.lh__notice { margin: 0 0 10px; display: flex; align-items: center; justify-content: center; gap: 10px; font-size: 12px; line-height: 1.7; color: var(--third-color); }

.lh-overlay {
    position: fixed;
    inset: 0;
    z-index: 100;
    background: rgba(0, 0, 0, 0.45);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    overscroll-behavior: contain;
}
.lh-modal {
    width: min(500px, 100%);
    background: var(--background-color);
    color: var(--primary-color);
    border: 1px solid var(--formBorder);
}
.lh-modal__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding: 12px 16px;
    background: var(--bg3);
    border-bottom: 1px solid var(--formBorder);
}
.lh-modal__close {
    border: none;
    background: transparent;
    color: var(--primary-color);
    font-size: 13px;
    cursor: pointer;
    line-height: 1;
    padding: 4px;
}
.lh-modal__body { padding: 16px; }
.lh-modal__choose { display: flex; flex-direction: column; gap: 10px; }
.lh-modal__foot { margin-top: 12px; display: flex; justify-content: flex-end; }
.lh__btn--block { align-self: stretch; text-align: center; }
.lh__goals { margin: 0 0 10px; padding: 0; list-style: none; display: flex; flex-direction: column; gap: 10px; }
.lh__goal {
    display: flex;
    flex-direction: column;
    gap: 12px;
    padding: 14px 16px;
    background: var(--background-color);
    border: solid thin var(--formBorder);
}
.lh__goal--disabled { background: var(--bg3); border-style: dashed; }
.lh__goal-row { display: flex; align-items: center; gap: 16px; }
.lh__goal-main { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 6px; }
.lh__goal-title { word-break: break-word; line-height: 1.5; font-size: 13px; }
.lh__goal--disabled .lh__goal-title { color: var(--third-color); }
.lh__goal-period { font-size: 12px; color: var(--primary-color);}
.lh__goal-score { font-size: 12px; color: var(--primary-color); margin-top: 5px;}
.lh__goal--disabled .lh__goal-score { color: var(--third-color); }
.lh__goal-action { flex: none; }
.lh__goal-note {
    margin: 0;
    padding-top: 10px;
    border-top: 1px dashed var(--formBorder);
    font-size: 11px;
    line-height: 1.6;
    color: var(--third-color);
}

.lh__btn {
    box-sizing: border-box;
    border: solid thin var(--formBorder);
    background: var(--background-color);
    color: var(--primary-color);
    font-size: 12px;
    padding: 7px 16px;
    cursor: pointer;
}
.lh__btn:hover { background: var(--bg3); }
.lh__btn--sm { padding: 4px 12px; }
.lh__btn--primary { background: var(--primary-color); color: var(--background-color); }
.lh__btn--primary:hover { opacity: 0.9; background: var(--primary-color); }
.lh__btn--ghost { border-color: transparent; }
.lh__btn:disabled { opacity: 0.5; cursor: not-allowed; }
</style>
