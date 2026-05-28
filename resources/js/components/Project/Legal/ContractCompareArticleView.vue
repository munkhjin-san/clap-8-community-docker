<template>
    <div class="contract-compare-article-view">
        <div class="contract-compare-article-view__layout">
            <div class="contract-compare-article-view__content">
                <div class="contract-compare-article-view__header">
                    <article class="contract-compare-article-view__doc contract-compare-article-view__doc--base">
                        <p class="contract-compare-article-view__label">比較元</p>
                        <p class="contract-compare-article-view__title">{{ baseTitle }}</p>
                        <p v-if="baseMeta" class="contract-compare-article-view__meta">{{ baseMeta }}</p>
                    </article>
                    <article class="contract-compare-article-view__doc contract-compare-article-view__doc--target">
                        <p class="contract-compare-article-view__label">比較先</p>
                        <p class="contract-compare-article-view__title">{{ targetTitle }}</p>
                        <p v-if="targetMeta" class="contract-compare-article-view__meta">{{ targetMeta }}</p>
                    </article>
                </div>

                <div class="contract-compare-article-view__rows">
                    <article
                        v-for="row in rows"
                        :key="row.id"
                        class="contract-compare-article-view__row"
                    >
                        <section
                            class="contract-compare-article-view__card"
                            :class="cardClass(row.changeType, 'base', Boolean(row.baseClause))"
                        >
                            <template v-if="row.baseClause">
                                <h3 class="contract-compare-article-view__card-title">
                                    <template v-for="(fragment, fragmentIndex) in visibleTitleFragments(row.baseClause)" :key="`${row.baseClause.id}-base-title-${fragmentIndex}`">
                                        <span :class="fragmentClass(fragment.changed, 'base')">{{ fragment.text }}</span>
                                    </template>
                                </h3>

                                <div v-if="visibleParagraphs(row.baseClause).length" class="contract-compare-article-view__paragraphs">
                                    <p
                                        v-for="paragraph in visibleParagraphs(row.baseClause)"
                                        :key="paragraph.id"
                                        class="contract-compare-article-view__paragraph"
                                    >
                                        <template v-for="(fragment, fragmentIndex) in paragraph.fragments" :key="`${paragraph.id}-base-fragment-${fragmentIndex}`">
                                            <span :class="fragmentClass(fragment.changed, 'base')">{{ fragment.text }}</span>
                                        </template>
                                    </p>
                                </div>
                            </template>

                            <p v-else class="contract-compare-article-view__empty-label">該当なし</p>
                        </section>

                        <section
                            class="contract-compare-article-view__card"
                            :class="cardClass(row.changeType, 'target', Boolean(row.targetClause))"
                        >
                            <template v-if="row.targetClause">
                                <h3 class="contract-compare-article-view__card-title">
                                    <template v-for="(fragment, fragmentIndex) in visibleTitleFragments(row.targetClause)" :key="`${row.targetClause.id}-target-title-${fragmentIndex}`">
                                        <span :class="fragmentClass(fragment.changed, 'target')">{{ fragment.text }}</span>
                                    </template>
                                </h3>

                                <div v-if="visibleParagraphs(row.targetClause).length" class="contract-compare-article-view__paragraphs">
                                    <p
                                        v-for="paragraph in visibleParagraphs(row.targetClause)"
                                        :key="paragraph.id"
                                        class="contract-compare-article-view__paragraph"
                                    >
                                        <template v-for="(fragment, fragmentIndex) in paragraph.fragments" :key="`${paragraph.id}-target-fragment-${fragmentIndex}`">
                                            <span :class="fragmentClass(fragment.changed, 'target')">{{ fragment.text }}</span>
                                        </template>
                                    </p>
                                </div>
                            </template>

                            <p v-else class="contract-compare-article-view__empty-label">該当なし</p>
                        </section>
                    </article>
                </div>
            </div>

            <aside class="contract-compare-article-view__sidebar">
                <section class="contract-compare-article-view__sidebar-section">
                    <div class="contract-compare-article-view__toggle">
                        <div>
                            <p class="contract-compare-article-view__sidebar-title">比較表示</p>
                            <p class="contract-compare-article-view__sidebar-caption">変更がある文字だけを本文中で控えめにハイライトします。</p>
                        </div>

                        <button
                            type="button"
                            class="contract-compare-article-view__switch"
                            :class="{ 'contract-compare-article-view__switch--on': highlightsVisible }"
                            :aria-pressed="highlightsVisible"
                            @click="highlightsVisible = !highlightsVisible"
                        >
                            <span class="contract-compare-article-view__switch-thumb" />
                        </button>
                    </div>
                </section>

                <section class="contract-compare-article-view__sidebar-section">
                    <p class="contract-compare-article-view__sidebar-title">比較元にない条文</p>

                    <div v-if="missingInBase.length" class="contract-compare-article-view__sidebar-list">
                        <article
                            v-for="item in missingInBase"
                            :key="item.id"
                            class="contract-compare-article-view__sidebar-item contract-compare-article-view__sidebar-item--target"
                        >
                            <strong>{{ item.label }}</strong>
                            <span v-if="item.detail">{{ item.detail }}</span>
                            <span>{{ item.page }}</span>
                        </article>
                    </div>
                    <p v-else class="contract-compare-article-view__sidebar-empty">該当する条文はありません</p>
                </section>

                <section class="contract-compare-article-view__sidebar-section">
                    <p class="contract-compare-article-view__sidebar-title">比較先にない条文</p>

                    <div v-if="missingInTarget.length" class="contract-compare-article-view__sidebar-list">
                        <article
                            v-for="item in missingInTarget"
                            :key="item.id"
                            class="contract-compare-article-view__sidebar-item contract-compare-article-view__sidebar-item--base"
                        >
                            <strong>{{ item.label }}</strong>
                            <span v-if="item.detail">{{ item.detail }}</span>
                            <span>{{ item.page }}</span>
                        </article>
                    </div>
                    <p v-else class="contract-compare-article-view__sidebar-empty">該当する条文はありません</p>
                </section>
            </aside>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import type {
    ContractCompareClauseView,
    ContractCompareFragment,
    ContractCompareParagraph,
    ContractComparisonRow,
} from '@/utils/contractAnalysis'

const props = withDefaults(defineProps<{
    rows: ContractComparisonRow[]
    baseTitle: string
    targetTitle: string
    baseMeta?: string
    targetMeta?: string
}>(), {
    baseMeta: '',
    targetMeta: '',
})

const highlightsVisible = ref(true)

const normalizeVisibleText = (value: string) => value.replace(/\s+/g, '').trim()

const visibleParagraphs = (clause: ContractCompareClauseView) => {
    const normalizedTitle = normalizeVisibleText(clause.title || clause.label || '')

    return clause.paragraphs.filter((paragraph: ContractCompareParagraph) => {
        const normalizedParagraph = normalizeVisibleText(paragraph.text)

        if (!normalizedParagraph) {
            return false
        }

        return normalizedParagraph !== normalizedTitle
    })
}

const visibleTitleFragments = (clause: ContractCompareClauseView) => {
    const fragments = clause.titleFragments.filter((fragment: ContractCompareFragment) => Boolean(fragment.text))

    if (fragments.length) {
        return fragments
    }

    return clause.title
        ? [{ text: clause.title, changed: false }]
        : []
}

const summarizeSidebarText = (value: string, max = 36) => {
    const clean = value.replace(/\s+/g, ' ').trim()
    return clean.length <= max ? clean : `${clean.slice(0, max - 3)}...`
}

const clauseDetail = (clause: ContractCompareClauseView | null) => {
    if (!clause) {
        return ''
    }

    const label = (clause.label || '').trim()
    const title = (clause.title || '').trim()
    const titleWithoutLabel = title.replace(label, '').trim()
    if (titleWithoutLabel) {
        return summarizeSidebarText(titleWithoutLabel)
    }

    const firstParagraph = visibleParagraphs(clause)[0]?.text ?? ''
    return summarizeSidebarText(firstParagraph)
}

const cardClass = (
    changeType: ContractComparisonRow['changeType'],
    side: 'base' | 'target',
    hasClause: boolean,
) => ({
    'contract-compare-article-view__card--empty': !hasClause,
    'contract-compare-article-view__card--base-changed': hasClause && side === 'base' && (changeType === 'removed' || changeType === 'modified'),
    'contract-compare-article-view__card--target-changed': hasClause && side === 'target' && (changeType === 'added' || changeType === 'modified'),
})

const fragmentClass = (changed: boolean, side: 'base' | 'target') => ({
    'contract-compare-article-view__fragment': true,
    'contract-compare-article-view__fragment--changed-base': highlightsVisible.value && changed && side === 'base',
    'contract-compare-article-view__fragment--changed-target': highlightsVisible.value && changed && side === 'target',
})

const missingInBase = computed(() => {
    return props.rows
        .filter(row => row.changeType === 'added' && row.targetClause)
        .map(row => ({
            id: row.id,
            label: row.targetClause?.label || row.clauseLabel,
            detail: clauseDetail(row.targetClause),
            page: row.targetClause ? `${row.targetClause.page}ページ` : 'ページ不明',
        }))
})

const missingInTarget = computed(() => {
    return props.rows
        .filter(row => row.changeType === 'removed' && row.baseClause)
        .map(row => ({
            id: row.id,
            label: row.baseClause?.label || row.clauseLabel,
            detail: clauseDetail(row.baseClause),
            page: row.baseClause ? `${row.baseClause.page}ページ` : 'ページ不明',
        }))
})
</script>

<style scoped>
.contract-compare-article-view {
    display: flex;
    flex-direction: column;
}

.contract-compare-article-view__layout {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 300px;
    gap: 10px;
}

.contract-compare-article-view__content {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.contract-compare-article-view__header,
.contract-compare-article-view__row {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
}

.contract-compare-article-view__doc {
    padding: 10px 12px;
    border: 1px solid var(--calendarBorder);
    border-radius: 0;
    background: var(--background-color);
}

.contract-compare-article-view__doc--base {
    border-color: rgba(210, 131, 8, 0.28);
}

.contract-compare-article-view__doc--target {
    border-color: rgba(24, 143, 87, 0.26);
}

.contract-compare-article-view__label,
.contract-compare-article-view__meta {
    margin: 0;
    font-size: 12px;
    line-height: 1.5;
    color: var(--font-color, #666);
}

.contract-compare-article-view__title {
    margin: 6px 0 0;
    font-size: 14px;
    line-height: 1.5;
    font-weight: 700;
    color: var(--primary-color);
    word-break: break-word;
}

.contract-compare-article-view__rows {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.contract-compare-article-view__card {
    min-height: 120px;
    padding: 12px;
    border: 1px solid var(--calendarBorder);
    border-radius: 0;
    background: var(--background-color);
    box-sizing: border-box;
}

.contract-compare-article-view__card--empty {
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--bg3);
    border-color: rgba(160, 160, 160, 0.55);
}

.contract-compare-article-view__card--base-changed {
    border-color: rgba(210, 131, 8, 0.22);
    background: rgba(255, 186, 59, 0.06);
}

.contract-compare-article-view__card--target-changed {
    border-color: rgba(24, 143, 87, 0.2);
    background: rgba(41, 196, 122, 0.06);
}

.contract-compare-article-view__card-title {
    margin: 0;
    font-size: 15px;
    line-height: 1.6;
    font-weight: 700;
    color: var(--primary-color);
    white-space: pre-wrap;
    word-break: break-word;
}

.contract-compare-article-view__paragraphs {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-top: 8px;
}

.contract-compare-article-view__paragraph,
.contract-compare-article-view__empty-label {
    margin: 0;
    font-size: 13px;
    line-height: 1.8;
    color: var(--primary-color);
    white-space: pre-wrap;
    word-break: break-word;
}

.contract-compare-article-view__empty-label {
    color: rgba(50, 50, 50, 0.8);
    font-weight: 700;
}

.contract-compare-article-view__fragment {
    border-radius: 0;
    transition: background-color 0.18s ease;
}

.contract-compare-article-view__fragment--changed-base {
    background: rgba(255, 186, 59, 0.14);
}

.contract-compare-article-view__fragment--changed-target {
    background: rgba(41, 196, 122, 0.12);
}

.contract-compare-article-view__sidebar {
    position: sticky;
    top: 0;
    align-self: start;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.contract-compare-article-view__sidebar-section {
    padding: 12px;
    border: 1px solid var(--calendarBorder);
    border-radius: 0;
    background: var(--background-color);
}

.contract-compare-article-view__sidebar-title {
    margin: 0;
    font-size: 15px;
    font-weight: 700;
    color: var(--primary-color);
}

.contract-compare-article-view__sidebar-caption {
    margin: 4px 0 0;
    font-size: 12px;
    line-height: 1.5;
    color: var(--font-color, #666);
}

.contract-compare-article-view__toggle {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.contract-compare-article-view__switch {
    position: relative;
    width: 48px;
    min-width: 48px;
    height: 28px;
    padding: 2px;
    border: 1px solid rgba(120, 120, 120, 0.18);
    border-radius: 0;
    background: rgba(160, 160, 160, 0.22);
    cursor: pointer;
    transition: background-color 0.18s ease, border-color 0.18s ease;
}

.contract-compare-article-view__switch--on {
    background: rgba(24, 143, 87, 0.2);
    border-color: rgba(24, 143, 87, 0.26);
}

.contract-compare-article-view__switch-thumb {
    display: block;
    width: 22px;
    height: 22px;
    border-radius: 0;
    background: #fff;
    transition: transform 0.18s ease;
}

.contract-compare-article-view__switch--on .contract-compare-article-view__switch-thumb {
    transform: translateX(20px);
}

.contract-compare-article-view__sidebar-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-top: 10px;
}

.contract-compare-article-view__sidebar-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
    padding: 10px;
    border-radius: 0;
    border: 1px solid transparent;
}

.contract-compare-article-view__sidebar-item--target {
    background: rgba(41, 196, 122, 0.12);
    border-color: rgba(24, 143, 87, 0.18);
}

.contract-compare-article-view__sidebar-item--base {
    background: rgba(255, 186, 59, 0.14);
    border-color: rgba(210, 131, 8, 0.18);
}

.contract-compare-article-view__sidebar-item strong {
    font-size: 13px;
    line-height: 1.5;
    color: var(--primary-color);
    word-break: break-word;
}

.contract-compare-article-view__sidebar-item span,
.contract-compare-article-view__sidebar-empty {
    font-size: 12px;
    line-height: 1.5;
    color: var(--font-color, #666);
}

.contract-compare-article-view__sidebar-item span + span {
    color: var(--primary-color);
    opacity: 0.72;
}

.contract-compare-article-view__sidebar-empty {
    margin: 12px 0 0;
}

@media (max-width: 1279px) {
    .contract-compare-article-view__layout {
        grid-template-columns: 1fr;
    }

    .contract-compare-article-view__sidebar {
        position: static;
    }
}

@media (max-width: 899px) {
    .contract-compare-article-view__header,
    .contract-compare-article-view__row {
        grid-template-columns: 1fr;
    }

    .contract-compare-article-view__card {
        min-height: 120px;
    }

    .contract-compare-article-view__card-title {
        font-size: 15px;
    }

    .contract-compare-article-view__paragraph {
        font-size: 13px;
    }
}
</style>
