<template>
    <div class="deck">
        <!-- 1. Title -->
        <section class="slide slide--title">
            <div class="slide-title__theme">選択テーマ：{{ spec.selected_theme }}</div>
            <div class="slide-title__main">個別研修資料</div>
            <div class="slide-title__goal">「{{ spec.goal_title }}」を達成するために</div>
            <div class="slide-title__rule"></div>
        </section>

        <!-- 2-6. Sections 1-5 -->
        <section v-for="s in sectionSlides" :key="s.key" class="slide slide--auto">
            <div class="slide-head">
                <div class="slide-badge">{{ s.number }}</div>
                <div class="slide-head__title">{{ s.title }}</div>
            </div>
            <div class="slide-cols">
                <ul class="slide-body">
                    <li v-for="(line, i) in s.section.body" :key="i">{{ line }}</li>
                </ul>
                <div class="fig">
                    <div v-if="s.section.figure.title" class="fig__title">{{ s.section.figure.title }}</div>

                    <!-- flow: boxes joined by arrows -->
                    <template v-if="s.section.figure.type === 'flow'">
                        <template v-for="(it, i) in s.section.figure.items" :key="i">
                            <div :class="['fbox', { 'fbox--alt': i % 2 === 1 }]">
                                <div class="fbox__label">{{ it.label }}</div>
                                <div v-if="it.detail" class="fbox__detail">{{ it.detail }}</div>
                            </div>
                            <div v-if="i < s.section.figure.items.length - 1" class="fig-arrow">↓</div>
                        </template>
                    </template>

                    <!-- list: stacked labeled boxes -->
                    <template v-else-if="s.section.figure.type === 'list'">
                        <div
                            v-for="(it, i) in s.section.figure.items"
                            :key="i"
                            :class="['fbox', { 'fbox--alt': i % 2 === 1 }]"
                        >
                            <div class="fbox__label">{{ it.label }}</div>
                            <div v-if="it.detail" class="fbox__detail">{{ it.detail }}</div>
                        </div>
                    </template>

                    <!-- concept: highlighted card + optional chain -->
                    <template v-else>
                        <div class="fbox fbox--alt">
                            <div v-if="s.section.figure.title" class="fbox__label">{{ s.section.figure.title }}</div>
                            <p v-if="s.section.figure.note" class="fig-note">{{ s.section.figure.note }}</p>
                        </div>
                        <div v-if="s.section.figure.items.length" class="fig-chain">
                            <template v-for="(it, i) in s.section.figure.items" :key="i">
                                <span>{{ it.label }}</span>
                                <i v-if="i < s.section.figure.items.length - 1">→</i>
                            </template>
                        </div>
                    </template>
                </div>
            </div>
            <div v-if="s.section.summary" class="slide-summary">{{ s.section.summary }}</div>
        </section>

        <!-- 7. Discussion -->
        <section class="slide slide--auto">
            <div class="slide-head">
                <div class="slide-badge">6</div>
                <div class="slide-head__title">グループディスカッションテーマ</div>
            </div>
            <p v-if="spec.discussion.intro" class="disc__intro">{{ spec.discussion.intro }}</p>
            <div class="disc__cols">
                <div v-for="t in discussionThemes" :key="t.number" class="tcard">
                    <div class="tcard__name">{{ t.theme.name }}</div>
                    <div class="tcard__talk">
                        <div class="tlabel">話し言葉</div>
                        <p class="ttext">{{ t.theme.talk_script }}</p>
                    </div>
                    <div class="tcard__land">
                        <div class="tlabel tlabel--navy">着地の方向</div>
                        <p class="ttext">{{ t.theme.landing }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- 8. Closing -->
        <section class="slide slide--closing">
            <div class="closing__main">お疲れ様でした。</div>
            <div class="closing__sub">トークテーマを選択し、グループディスカッションへ進んでください。</div>
        </section>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { LearningSlideDeckSpec } from '@/types/learning'

const props = defineProps<{
    spec: LearningSlideDeckSpec
}>()

// Section titles are fixed by the format; the AI only supplies content.
const SECTION_TITLES: Record<string, string> = {
    section1: 'このテーマを今回の成果目標にどう活かせるか',
    section2: '成果目標達成に向けて本人が理解すべき考え方',
    section3: '過去の自分から見える強み',
    section4: '逆に注意すべき点',
    section5: '達成に向けて意識したい具体的な行動',
}

const sectionSlides = computed(() =>
    (['section1', 'section2', 'section3', 'section4', 'section5'] as const).map((key, index) => ({
        key,
        number: index + 1,
        title: SECTION_TITLES[key],
        section: props.spec.sections[key],
    })),
)

const discussionThemes = computed(() => [
    { number: 1, theme: props.spec.discussion.theme1 },
    { number: 2, theme: props.spec.discussion.theme2 },
    { number: 3, theme: props.spec.discussion.theme3 },
])
</script>

<style scoped>
.deck {
    --navy: #1e2761;
    --muted: #595959;
    --box: #f2f2f2;
    --body: #141414;
    --line: #e3e3e3;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 24px;
    padding: 24px 0;
    background: #e7e7e7;
    color: var(--body);
    font-family: "Hiragino Kaku Gothic ProN", "Yu Gothic", Meiryo, Calibri, system-ui, sans-serif;
}

.slide {
    box-sizing: border-box;
    width: min(1000px, 94%);
    aspect-ratio: 16 / 9;
    background: #fff;
    border: 1px solid var(--line);
    box-shadow: 0 8px 26px rgb(0 0 0 / 8%);
    padding: 38px 46px;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
/* dense slides grow instead of clipping */
.slide--auto {
    aspect-ratio: auto;
    min-height: calc(min(1000px, 94%) * 0.5625);
}
.slide * {
    box-sizing: border-box;
}

/* title */
.slide--title {
    align-items: center;
    justify-content: center;
    text-align: center;
}
.slide-title__theme {
    font-size: 17px;
    color: var(--muted);
    margin-bottom: 26px;
}
.slide-title__main {
    font-size: 44px;
    font-weight: 700;
    color: var(--navy);
    letter-spacing: 0.02em;
}
.slide-title__goal {
    font-size: 22px;
    font-weight: 700;
    margin-top: 20px;
}
.slide-title__rule {
    width: 64px;
    height: 4px;
    background: var(--navy);
    margin-top: 34px;
}

/* section header */
.slide-head {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 22px;
    flex: 0 0 auto;
}
.slide-badge {
    width: 42px;
    height: 42px;
    flex: 0 0 42px;
    background: var(--navy);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    font-weight: 700;
}
.slide-head__title {
    font-size: 23px;
    font-weight: 700;
    line-height: 1.3;
}

/* body / figure split */
.slide-cols {
    display: grid;
    grid-template-columns: 1.02fr 0.98fr;
    gap: 36px;
    flex: 1 1 auto;
    min-height: 0;
}
.slide-body {
    margin: 0;
    padding: 0 0 0 1.1em;
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.slide-body li {
    font-size: 15px;
    line-height: 1.65;
}
.slide-body li::marker {
    color: var(--navy);
}
.slide-summary {
    margin-top: 18px;
    flex: 0 0 auto;
    border-left: 4px solid var(--navy);
    background: var(--box);
    padding: 12px 16px;
    font-size: 14px;
    line-height: 1.6;
}

/* figures */
.fig {
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.fig__title {
    font-size: 14px;
    font-weight: 700;
    color: var(--navy);
    margin-bottom: 2px;
}
.fbox {
    border: 1px solid #dcdcdc;
    padding: 12px 16px;
    background: #fff;
}
.fbox--alt {
    background: var(--box);
    border-color: #e2e2e2;
}
.fbox__label {
    font-size: 16px;
    font-weight: 700;
    color: var(--navy);
    line-height: 1.4;
}
.fbox__detail {
    font-size: 11.5px;
    color: var(--muted);
    margin-top: 3px;
    line-height: 1.5;
}
.fig-arrow {
    color: var(--navy);
    font-size: 20px;
    text-align: center;
    line-height: 1;
    margin: -2px 0;
}
.fig-note {
    font-size: 13.5px;
    line-height: 1.75;
    margin: 8px 0 0;
    color: var(--body);
}
.fig-chain {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
    margin-top: 6px;
}
.fig-chain span {
    background: var(--navy);
    color: #fff;
    font-size: 13px;
    font-weight: 700;
    padding: 6px 12px;
}
.fig-chain i {
    color: var(--navy);
    font-style: normal;
    font-weight: 700;
}

/* discussion */
.disc__intro {
    font-size: 13px;
    color: var(--muted);
    line-height: 1.6;
    margin: 0 0 16px;
    flex: 0 0 auto;
}
.disc__cols {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    flex: 1 1 auto;
    min-height: 0;
}
.tcard {
    display: flex;
    flex-direction: column;
    border: 1px solid #dcdcdc;
}
.tcard__name {
    background: #fff;
    color: var(--navy);
    font-size: 13px;
    font-weight: 700;
    line-height: 1.5;
    padding: 12px 14px;
    border-bottom: 2px solid var(--navy);
}
.tcard__talk {
    padding: 12px 14px;
    flex: 1 1 auto;
}
.tlabel {
    font-size: 11px;
    font-weight: 700;
    margin-bottom: 6px;
}
.tlabel--navy {
    color: var(--navy);
}
.ttext {
    font-size: 12px;
    line-height: 1.7;
    margin: 0;
    white-space: pre-wrap;
}
.tcard__land {
    background: var(--box);
    padding: 12px 14px;
}

/* closing */
.slide--closing {
    align-items: center;
    justify-content: center;
    text-align: center;
    gap: 18px;
}
.closing__main {
    font-size: 32px;
    font-weight: 700;
    color: var(--navy);
}
.closing__sub {
    font-size: 17px;
}

@media (max-width: 720px) {
    .slide {
        aspect-ratio: auto;
        padding: 22px 20px;
    }
    .slide-cols {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    .disc__cols {
        grid-template-columns: 1fr;
    }
    .slide-title__main {
        font-size: 32px;
    }
}
</style>
