<template>
    <div>
        <div class="si-box">
            <div class="switchLabel">
                <p class="form-lbl theme-structure-section__label">ポートフォリオ</p>
            </div>
            <div class="selectSwitchArea theme-structure-section__switch">
                <input
                    id="portfolio_create"
                    v-model="portfolio"
                    type="checkbox"
                    :disabled="caseStudy"
                >
                <label
                    for="portfolio_create"
                    class="cursor-pointer theme-structure-section__toggle"
                    :class="{ 'disabled-toggle': caseStudy }"
                >
                    <span></span>
                    <div class="switch-toggle"></div>
                </label>
            </div>
            <p class="form-helper theme-structure-section__helper">
                ONにすると受講者は全セクション完了後にポートフォリオ作成フローへ進みます。案内文が学習画面に表示されます。<br>
                ※ポートフォリオが ON の場合、ケーススタディタイプは選択できません。
            </p>
        </div>

        <div
            v-if="portfolio"
            class="si-box"
        >
            <div class="theme-structure-section__editor-label">ポートフォリオに関する説明</div>
            <RichEditor
                :initilaValue="initialPortfolioGuidance"
                @content-updated="emit('portfolio-guidance-updated', $event)"
            />
            <span
                v-if="errors.portfolioGuidance"
                class="form-error theme-structure-section__error"
            >
                {{ errors.portfolioGuidance }}
            </span>
        </div>

        <div
            v-if="portfolio"
            class="si-box"
        >
            <div class="theme-structure-section__editor-label">エピソードに関する説明</div>
            <RichEditor
                :initilaValue="initialEpisodeGuidance"
                @content-updated="emit('episode-guidance-updated', $event)"
            />
            <span
                v-if="errors.episodeGuidance"
                class="form-error theme-structure-section__error"
            >
                {{ errors.episodeGuidance }}
            </span>
        </div>

        <div
            v-if="portfolio"
            class="si-box"
        >
            <div class="theme-structure-section__editor-label">タイトルに関する説明</div>
            <RichEditor
                :initilaValue="initialTitleGuidance"
                @content-updated="emit('title-guidance-updated', $event)"
            />
            <span
                v-if="errors.titleGuidance"
                class="form-error theme-structure-section__error"
            >
                {{ errors.titleGuidance }}
            </span>
        </div>

        <div class="si-box">
            <div class="switchLabel">
                <p class="form-lbl theme-structure-section__label">ケーススタディ</p>
            </div>
            <div class="selectSwitchArea theme-structure-section__switch">
                <input
                    id="has_case_study"
                    v-model="caseStudy"
                    type="checkbox"
                    :disabled="portfolio"
                >
                <label
                    for="has_case_study"
                    class="cursor-pointer theme-structure-section__toggle"
                    :class="{ 'disabled-toggle': portfolio }"
                >
                    <span></span>
                    <div class="switch-toggle"></div>
                </label>
            </div>
            <p class="form-helper theme-structure-section__helper">
                ONにすると各レッスンで『ケーススタディ』タイプを選択でき、学習画面ではカード形式で表示されます。<br>
                ※ケーススタディが ON の場合、ポートフォリオは選択できません。
            </p>
            <span
                v-if="errors.structure"
                class="form-error theme-structure-section__error"
            >
                {{ errors.structure }}
            </span>
        </div>
    </div>
</template>

<script setup lang="ts">
import RichEditor from '@/components/Global/RichEditor.vue'

defineProps<{
    initialPortfolioGuidance: string
    initialEpisodeGuidance: string
    initialTitleGuidance: string
    errors: {
        portfolioGuidance: string
        episodeGuidance: string
        titleGuidance: string
        structure: string
    }
}>()

const portfolio = defineModel<boolean>('portfolio', { required: true })
const caseStudy = defineModel<boolean>('caseStudy', { required: true })

const emit = defineEmits<{
    'portfolio-guidance-updated': [html: string]
    'episode-guidance-updated': [html: string]
    'title-guidance-updated': [html: string]
}>()
</script>

<style scoped>
.theme-structure-section__label{
    white-space: nowrap;
    font-size: 14px;
}

.theme-structure-section__switch{
    display: flex;
    width: 100%;
}

.theme-structure-section__toggle{
    min-width: 80px;
}

.theme-structure-section__helper{
    margin-top: 5px;
    font-size: 12px;
    color: gray;
    line-height: normal;
}

.theme-structure-section__editor-label{
    margin-bottom: 15px;
    font-size: 14px;
}

.theme-structure-section__error{
    font-size: 11px;
    color: tomato;
}
</style>
