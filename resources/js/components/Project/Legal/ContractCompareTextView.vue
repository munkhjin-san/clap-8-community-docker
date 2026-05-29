<template>
    <div class="contract-compare-text-view">
        <div class="contract-compare-text-view__header">
            <article class="contract-compare-text-view__doc contract-compare-text-view__doc--base">
                <p class="contract-compare-text-view__doc-label">比較元</p>
                <p class="contract-compare-text-view__doc-title">{{ baseTitle }}</p>
                <p v-if="baseMeta" class="contract-compare-text-view__doc-meta">{{ baseMeta }}</p>
            </article>
            <article class="contract-compare-text-view__doc contract-compare-text-view__doc--target">
                <p class="contract-compare-text-view__doc-label">比較先</p>
                <p class="contract-compare-text-view__doc-title">{{ targetTitle }}</p>
                <p v-if="targetMeta" class="contract-compare-text-view__doc-meta">{{ targetMeta }}</p>
            </article>
        </div>

        <div class="contract-compare-text-view__documents">
            <article class="contract-compare-text-view__document contract-compare-text-view__document--base">
                <section
                    v-for="block in baseBlocks"
                    :key="block.id"
                    class="contract-compare-text-view__section"
                    :class="sectionClass(block, 'base')"
                >
                    <h3 v-if="hasVisibleTitle(block)" class="contract-compare-text-view__title">
                        <template v-for="(fragment, fragmentIndex) in visibleTitleFragments(block)" :key="`${block.id}-base-title-${fragmentIndex}`">
                            <span :class="fragmentClass(fragment.changed, 'base')">{{ fragment.text }}</span>
                        </template>
                    </h3>

                    <div v-if="visibleParagraphs(block).length" class="contract-compare-text-view__paragraphs">
                        <p
                            v-for="paragraph in visibleParagraphs(block)"
                            :key="paragraph.id"
                            class="contract-compare-text-view__paragraph"
                            :class="{ 'contract-compare-text-view__paragraph--changed-base': paragraph.changed }"
                        >
                            <template v-for="(fragment, fragmentIndex) in paragraph.fragments" :key="`${paragraph.id}-base-fragment-${fragmentIndex}`">
                                <span :class="fragmentClass(fragment.changed, 'base')">{{ fragment.text }}</span>
                            </template>
                        </p>
                    </div>
                </section>
            </article>

            <article class="contract-compare-text-view__document contract-compare-text-view__document--target">
                <section
                    v-for="block in targetBlocks"
                    :key="block.id"
                    class="contract-compare-text-view__section"
                    :class="sectionClass(block, 'target')"
                >
                    <h3 v-if="hasVisibleTitle(block)" class="contract-compare-text-view__title">
                        <template v-for="(fragment, fragmentIndex) in visibleTitleFragments(block)" :key="`${block.id}-target-title-${fragmentIndex}`">
                            <span :class="fragmentClass(fragment.changed, 'target')">{{ fragment.text }}</span>
                        </template>
                    </h3>

                    <div v-if="visibleParagraphs(block).length" class="contract-compare-text-view__paragraphs">
                        <p
                            v-for="paragraph in visibleParagraphs(block)"
                            :key="paragraph.id"
                            class="contract-compare-text-view__paragraph"
                            :class="{ 'contract-compare-text-view__paragraph--changed-target': paragraph.changed }"
                        >
                            <template v-for="(fragment, fragmentIndex) in paragraph.fragments" :key="`${paragraph.id}-target-fragment-${fragmentIndex}`">
                                <span :class="fragmentClass(fragment.changed, 'target')">{{ fragment.text }}</span>
                            </template>
                        </p>
                    </div>
                </section>
            </article>
        </div>
    </div>
</template>

<script setup lang="ts">
import type {
    ContractCompareClauseView,
    ContractCompareFragment,
    ContractCompareParagraph,
} from '@/utils/contractAnalysis'

withDefaults(defineProps<{
    baseBlocks: ContractCompareClauseView[]
    targetBlocks: ContractCompareClauseView[]
    baseTitle: string
    targetTitle: string
    baseMeta?: string
    targetMeta?: string
}>(), {
    baseMeta: '',
    targetMeta: '',
})

const normalizeVisibleText = (value: string) => value.replace(/\s+/g, '').trim()

const visibleParagraphs = (block: ContractCompareClauseView) => {
    const normalizedTitle = normalizeVisibleText(block.title || block.label || '')

    return block.paragraphs.filter((paragraph: ContractCompareParagraph) => {
        const normalizedParagraph = normalizeVisibleText(paragraph.text)

        if (!normalizedParagraph) {
            return false
        }

        return normalizedParagraph !== normalizedTitle
    })
}

const visibleTitleFragments = (block: ContractCompareClauseView) => {
    const fragments = block.titleFragments.filter((fragment: ContractCompareFragment) => Boolean(fragment.text))

    if (fragments.length) {
        return fragments
    }

    return block.title
        ? [{ text: block.title, changed: false }]
        : []
}

const hasVisibleTitle = (block: ContractCompareClauseView) => visibleTitleFragments(block).length > 0

const sectionClass = (block: ContractCompareClauseView, side: 'base' | 'target') => ({
    'contract-compare-text-view__section--changed-base': block.changed && side === 'base' && block.changeType !== 'ocr_suspected',
    'contract-compare-text-view__section--changed-target': block.changed && side === 'target' && block.changeType !== 'ocr_suspected',
    'contract-compare-text-view__section--ocr-suspected': block.changed && block.changeType === 'ocr_suspected',
})

const fragmentClass = (changed: boolean, side: 'base' | 'target') => ({
    'contract-compare-text-view__fragment': true,
    'contract-compare-text-view__fragment--changed-base': changed && side === 'base',
    'contract-compare-text-view__fragment--changed-target': changed && side === 'target',
})
</script>

<style scoped>
.contract-compare-text-view {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.contract-compare-text-view__header,
.contract-compare-text-view__documents {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
}

.contract-compare-text-view__doc,
.contract-compare-text-view__document {
    border: 1px solid var(--calendarBorder);
    border-radius: 0;
    background: var(--background-color);
}

.contract-compare-text-view__doc {
    padding: 10px 12px;
}

.contract-compare-text-view__doc--base {
    border-color: rgba(210, 131, 8, 0.28);
}

.contract-compare-text-view__doc--target {
    border-color: rgba(24, 143, 87, 0.26);
}

.contract-compare-text-view__doc-label,
.contract-compare-text-view__doc-meta {
    margin: 0;
    font-size: 12px;
    line-height: 1.5;
    color: var(--font-color, #666);
}

.contract-compare-text-view__doc-title {
    margin: 6px 0 0;
    font-size: 14px;
    line-height: 1.5;
    font-weight: 700;
    color: var(--primary-color);
    word-break: break-word;
}

.contract-compare-text-view__document {
    padding: 12px;
}

.contract-compare-text-view__section {
    padding: 0 0 12px;
}

.contract-compare-text-view__section + .contract-compare-text-view__section {
    padding-top: 12px;
    border-top: 1px solid rgba(120, 120, 120, 0.12);
}

.contract-compare-text-view__section--changed-base {
    border-left: 3px solid rgba(210, 131, 8, 0.28);
    padding-left: 14px;
    margin-left: -14px;
}

.contract-compare-text-view__section--changed-target {
    border-left: 3px solid rgba(24, 143, 87, 0.28);
    padding-left: 14px;
    margin-left: -14px;
}

.contract-compare-text-view__section--ocr-suspected {
    border-left: 3px solid rgba(123, 91, 5, 0.24);
    padding-left: 14px;
    margin-left: -14px;
}

.contract-compare-text-view__title {
    margin: 0;
    font-size: 15px;
    line-height: 1.6;
    font-weight: 700;
    color: var(--primary-color);
    white-space: pre-wrap;
    word-break: break-word;
}

.contract-compare-text-view__paragraphs {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-top: 8px;
}

.contract-compare-text-view__paragraph {
    margin: 0;
    font-size: 13px;
    line-height: 1.8;
    color: var(--primary-color);
    white-space: pre-wrap;
    word-break: break-word;
}

.contract-compare-text-view__fragment {
    border-radius: 0;
    transition: background-color 0.18s ease;
}

.contract-compare-text-view__fragment--changed-base {
    background: rgba(255, 186, 59, 0.14);
}

.contract-compare-text-view__fragment--changed-target {
    background: rgba(41, 196, 122, 0.12);
}

@media (max-width: 899px) {
    .contract-compare-text-view__header,
    .contract-compare-text-view__documents {
        grid-template-columns: 1fr;
    }

    .contract-compare-text-view__document {
        padding: 10px;
    }

    .contract-compare-text-view__title {
        font-size: 15px;
    }

    .contract-compare-text-view__paragraph {
        font-size: 13px;
    }
}
</style>
