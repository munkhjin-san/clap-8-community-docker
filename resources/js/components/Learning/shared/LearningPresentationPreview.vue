<template>
    <Modal
        size="large"
        disable-scroll
        custom-class=""
        body-style="width: 100%; height: 100%; padding: 0;"
        @close="emit('close')"
    >
        <template #title>
            <strong class="learning-presentation__title">個別研修資料｜{{ presentation.selected_theme }}</strong>
        </template>
        <template #menu>
            <TTSPlayer
                v-if="presentationText"
                :key="presentation.goal_title"
                :text="presentationText"
            />
        </template>
        <template #content>
            <div class="learning-presentation__content">
                <div class="learning-presentation__frame">
                    <SlideDeck :spec="presentation" />
                </div>
                <footer v-if="selectable" class="learning-presentation__discussion-selector">
                    <p class="learning-presentation__discussion-prompt">
                        <strong>ディスカッション</strong>テーマを選択してください
                    </p>
                    <div class="learning-presentation__discussion-actions">
                        <button
                            v-for="theme in discussionThemes"
                            :key="theme.number"
                            type="button"
                            class="learning-presentation__discussion-button"
                            @click="selectDiscussionTheme(theme)"
                        >
                            テーマ{{ theme.number }}
                        </button>
                    </div>
                </footer>
            </div>
        </template>
    </Modal>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import Modal from '@/components/Global/Modal.vue'
import TTSPlayer from '@/components/Global/TTSPlayer.vue'
import SlideDeck from '@/components/Learning/shared/SlideDeck.vue'
import type { LearningDiscussionTheme, LearningSlideDeckSpec } from '@/types/learning'

const props = withDefaults(defineProps<{
    presentation: LearningSlideDeckSpec
    // Only the active-learning stage lets the learner pick a discussion theme.
    // Read-only views (completed, or the group-discussion reference) hide it.
    selectable?: boolean
}>(), {
    selectable: true,
})

const emit = defineEmits<{
    close: []
    selectDiscussionTheme: [content: string]
}>()

const SECTION_TITLES = [
    'このテーマを今回の成果目標にどう活かせるか',
    '成果目標達成に向けて本人が理解すべき考え方',
    '過去の自分から見える強み',
    '逆に注意すべき点',
    '達成に向けて意識したい具体的な行動',
]

const discussionThemes = computed(() => [
    { number: 1, theme: props.presentation.discussion.theme1 },
    { number: 2, theme: props.presentation.discussion.theme2 },
    { number: 3, theme: props.presentation.discussion.theme3 },
])

// Flatten the structured deck into readable speech text for the TTS menu.
const presentationText = computed(() => {
    const s = props.presentation
    const lines: string[] = [
        '個別研修資料',
        `選択テーマ ${s.selected_theme}`,
        `「${s.goal_title}」を達成するために`,
    ]

    const sectionKeys = ['section1', 'section2', 'section3', 'section4', 'section5'] as const
    sectionKeys.forEach((key, i) => {
        const sec = s.sections[key]
        lines.push('', SECTION_TITLES[i])
        sec.body.forEach(b => lines.push(b))
        if (sec.figure.title) lines.push(sec.figure.title)
        sec.figure.items.forEach(it => lines.push(it.detail ? `${it.label}。${it.detail}` : it.label))
        if (sec.figure.note) lines.push(sec.figure.note)
        if (sec.summary) lines.push(sec.summary)
    })

    lines.push('', 'グループディスカッションテーマ')
    if (s.discussion.intro) lines.push(s.discussion.intro)
    discussionThemes.value.forEach(({ theme }) => {
        lines.push(theme.name, `話し言葉。${theme.talk_script}`, `着地の方向。${theme.landing}`)
    })
    lines.push('', 'お疲れ様でした。')

    return lines.join('\n').trim()
})

const selectDiscussionTheme = (entry: { theme: LearningDiscussionTheme }) => {
    const { name, talk_script, landing } = entry.theme
    const content = [name, talk_script, `着地の方向：${landing}`].filter(Boolean).join('\n\n')
    emit('selectDiscussionTheme', content)
}
</script>

<style scoped>
:deep(.learning-presentation-modal) {
    width: min(1400px, 94vw) !important;
    height: 92vh !important;
    color: #151515;
    background: #f1f1f1 !important;
    box-shadow: 0 28px 90px rgb(0 0 0 / 35%);
}

.learning-presentation__title {
    min-width: 0;
    overflow: hidden;
    font-size: 13px;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.learning-presentation__content {
    display: flex;
    flex-direction: column;
    width: 100%;
    height: 100%;
    overflow: hidden;
    background: #e7e7e7;
}

.learning-presentation__frame {
    flex: 1;
    min-height: 0;
    width: 100%;
    overflow-y: auto;
    overflow-x: hidden;
}

.learning-presentation__discussion-selector {
    display: flex;
    flex: 0 0 auto;
    align-items: center;
    justify-content: space-between;
    gap: 18px;
    padding: 14px 24px;
    border-top: 1px solid #b8b8b8;
    background: var(--background-color);
    color: var(--primary-color);
}

.learning-presentation__discussion-prompt {
    margin: 0;
    color: var(--primary-color);
    font-size: 13px;
    line-height: 1.5;
}

.learning-presentation__discussion-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.learning-presentation__discussion-button {
    min-width: 88px;
    padding: 9px 14px;
    border: 1px solid var(--primary-color);
    background: transparent;
    color: var(--primary-color);
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    transition: background-color 150ms ease, color 150ms ease;
}

.learning-presentation__discussion-button:hover,
.learning-presentation__discussion-button:focus-visible {
    background: var(--primary-color);
    color: var(--background-color);
}

@media (max-width: 760px) {
    :deep(.learning-presentation-modal) {
        width: 100%;
        height: 100%;
    }

    .learning-presentation__discussion-selector {
        align-items: flex-start;
        flex-direction: column;
        gap: 10px;
        padding: 12px 16px;
    }

    .learning-presentation__discussion-actions {
        width: 100%;
    }

    .learning-presentation__discussion-button {
        flex: 1;
        min-width: 0;
    }
}
</style>
