<template>
    <div class="contract-risk-review">
        <div class="contract-risk-review__header">
            <div>
                <p class="contract-risk-review__eyebrow">抽出テキスト</p>
                <p class="contract-risk-review__title">{{ documentTitle }}</p>
                <p v-if="documentMeta" class="contract-risk-review__meta">{{ documentMeta }}</p>
            </div>
        </div>

        <div v-if="documentIndex?.clauses?.length" class="contract-risk-review__content">
            <section
                v-for="clause in documentIndex.clauses"
                :key="clause.id"
                :ref="element => setClauseRef(clause.id, element)"
                class="contract-risk-review__section"
                :class="{ 'contract-risk-review__section--active': activeClauseId === clause.id }"
            >
                <header class="contract-risk-review__section-head">
                    <h3 class="contract-risk-review__section-title">
                        {{ formatClauseHeading(clause.label, clause.title) }}
                    </h3>
                    <span class="contract-risk-review__section-page">p.{{ clause.page }}</span>
                </header>

                <div v-if="clause.paragraphs.length" class="contract-risk-review__paragraphs">
                    <p
                        v-for="(paragraph, paragraphIndex) in clause.paragraphs"
                        :key="`${clause.id}-${paragraphIndex}`"
                        :ref="element => setParagraphRef(`${clause.id}-${paragraphIndex}`, element)"
                        class="contract-risk-review__paragraph"
                        :class="{
                            'contract-risk-review__paragraph--active': isActiveParagraph(clause.id, paragraphIndex),
                        }"
                    >
                        {{ paragraph }}
                    </p>
                </div>
            </section>
        </div>
        <div v-else class="contract-risk-review__empty">
            <p>抽出テキストを表示できませんでした。</p>
        </div>
    </div>
</template>

<script setup lang="ts">
import { nextTick, ref, watch } from 'vue'
import {
    normalizeContractText,
    type ContractDocumentIndex,
} from '@/utils/contractAnalysis'

type FocusRequest = {
    token: number
    page?: number
    query?: string | null
    fallbackQuery?: string | null
}

const props = withDefaults(defineProps<{
    documentIndex: ContractDocumentIndex | null
    documentTitle?: string
    documentMeta?: string
    focusRequest?: FocusRequest | null
}>(), {
    documentIndex: null,
    documentTitle: '',
    documentMeta: '',
    focusRequest: null,
})

const clauseRefs = new Map<string, HTMLElement>()
const paragraphRefs = new Map<string, HTMLElement>()
const activeClauseId = ref<string | null>(null)
const activeParagraphKey = ref<string | null>(null)

const normalizeMatchText = (value?: string | null) => (
    normalizeContractText(value)
        .replace(/\s+/g, '')
        .toLowerCase()
)

const formatClauseHeading = (label?: string | null, title?: string | null) => {
    const cleanLabel = normalizeContractText(label)
    const cleanTitle = normalizeContractText(title)

    if (cleanLabel && cleanTitle) {
        if (cleanTitle.startsWith(cleanLabel)) {
            return cleanTitle
        }

        return `${cleanLabel}${cleanTitle}`
    }

    return cleanLabel || cleanTitle
}

const resolveElement = (target: unknown) => {
    if (target instanceof HTMLElement) {
        return target
    }

    if (target && typeof target === 'object' && '$el' in target) {
        const element = (target as { $el?: unknown }).$el
        return element instanceof HTMLElement ? element : null
    }

    return null
}

const setClauseRef = (clauseId: string, element: unknown) => {
    const resolvedElement = resolveElement(element)

    if (!resolvedElement) {
        clauseRefs.delete(clauseId)
        return
    }

    clauseRefs.set(clauseId, resolvedElement)
}

const setParagraphRef = (paragraphKey: string, element: unknown) => {
    const resolvedElement = resolveElement(element)

    if (!resolvedElement) {
        paragraphRefs.delete(paragraphKey)
        return
    }

    paragraphRefs.set(paragraphKey, resolvedElement)
}

const isActiveParagraph = (clauseId: string, paragraphIndex: number) => (
    activeParagraphKey.value === `${clauseId}-${paragraphIndex}`
)

const findFocusTarget = (request?: FocusRequest | null) => {
    if (!props.documentIndex || !request) {
        return null
    }

    const queries = [request.query, request.fallbackQuery]
        .map(value => normalizeMatchText(value))
        .filter(Boolean)

    const scopedClauses = request.page
        ? props.documentIndex.clauses.filter(clause => clause.page === request.page)
        : props.documentIndex.clauses
    const clauses = scopedClauses.length ? scopedClauses : props.documentIndex.clauses

    for (const query of queries) {
        for (const clause of clauses) {
            for (let index = 0; index < clause.paragraphs.length; index += 1) {
                if (normalizeMatchText(clause.paragraphs[index]).includes(query)) {
                    return {
                        clauseId: clause.id,
                        paragraphKey: `${clause.id}-${index}`,
                    }
                }
            }
        }
    }

    for (const query of queries) {
        for (const clause of clauses) {
            const title = normalizeMatchText(formatClauseHeading(clause.label, clause.title))
            if (title.includes(query)) {
                return {
                    clauseId: clause.id,
                    paragraphKey: null,
                }
            }
        }
    }

    if (request.page) {
        const firstClauseOnPage = clauses[0]
        if (firstClauseOnPage) {
            return {
                clauseId: firstClauseOnPage.id,
                paragraphKey: null,
            }
        }
    }

    return null
}

const scrollToActiveTarget = async (request?: FocusRequest | null) => {
    const target = findFocusTarget(request)

    activeClauseId.value = target?.clauseId ?? null
    activeParagraphKey.value = target?.paragraphKey ?? null

    await nextTick()

    const paragraphElement = target?.paragraphKey ? paragraphRefs.get(target.paragraphKey) : null
    if (paragraphElement) {
        paragraphElement.scrollIntoView({ block: 'center', behavior: 'smooth' })
        return
    }

    const clauseElement = target?.clauseId ? clauseRefs.get(target.clauseId) : null
    clauseElement?.scrollIntoView({ block: 'start', behavior: 'smooth' })
}

watch(
    () => props.focusRequest?.token,
    async () => {
        await scrollToActiveTarget(props.focusRequest)
    }
)
</script>

<style scoped>
.contract-risk-review {
    display: flex;
    flex-direction: column;
    flex: 1;
    min-height: 0;
    height: 100%;
    overflow: hidden;
    background: var(--background-color);
}

.contract-risk-review__header {
    flex-shrink: 0;
    padding: 10px 12px;
    border-bottom: 1px solid var(--calendarBorder);
    background: var(--background-color);
}

.contract-risk-review__eyebrow,
.contract-risk-review__meta {
    margin: 0;
    font-size: 11px;
    line-height: 1.5;
    color: var(--font-color, #666);
}

.contract-risk-review__title {
    margin: 6px 0 0;
    font-size: 14px;
    line-height: 1.5;
    font-weight: 700;
    color: var(--primary-color);
    word-break: break-word;
}

.contract-risk-review__content {
    display: flex;
    flex-direction: column;
    flex: 1;
    min-height: 0;
    gap: 12px;
    padding: 12px;
    overflow: auto;
}

.contract-risk-review__section {
    padding: 0 0 12px;
    border-bottom: 1px solid rgba(120, 120, 120, 0.12);
}

.contract-risk-review__section:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.contract-risk-review__section--active {
    border-left: 3px solid rgba(210, 131, 8, 0.28);
    padding-left: 14px;
    margin-left: -14px;
}

.contract-risk-review__section-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 8px;
}

.contract-risk-review__section-title {
    margin: 0;
    font-size: 15px;
    line-height: 1.6;
    font-weight: 700;
    color: var(--primary-color);
    white-space: pre-wrap;
    word-break: break-word;
}

.contract-risk-review__section-page {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 24px;
    padding: 0 8px;
    border-radius: 0;
    border: 1px solid var(--calendarBorder);
    background: var(--bg3);
    font-size: 11px;
    font-weight: 700;
    color: var(--font-color, #666);
    white-space: nowrap;
}

.contract-risk-review__paragraphs {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-top: 8px;
}

.contract-risk-review__paragraph {
    margin: 0;
    padding: 3px 0;
    border-radius: 0;
    font-size: 13px;
    line-height: 1.8;
    color: var(--primary-color);
    white-space: pre-wrap;
    word-break: break-word;
    transition: background-color 0.18s ease;
}

.contract-risk-review__paragraph--active {
    background: rgba(255, 186, 59, 0.14);
}

.contract-risk-review__empty {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 240px;
    padding: 24px;
    color: var(--font-color, #666);
    text-align: center;
}

@media (max-width: 899px) {
    .contract-risk-review__content {
        padding: 10px;
    }

    .contract-risk-review__section-title {
        font-size: 15px;
    }

    .contract-risk-review__paragraph {
        font-size: 13px;
    }
}
</style>
