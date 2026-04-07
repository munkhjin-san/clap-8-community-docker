<template>
    <div class="legal-tab">
        <div v-if="!hasPrivilage" class="legal-state-card legal-state-card--locked">
            <p class="legal-state-card__eyebrow">契約レビュー</p>
            <h2 class="legal-state-card__title">契約レビューにアクセスできません</h2>
            <p class="legal-state-card__description">
                このタブの閲覧権限がありません。管理者またはプロジェクト責任者に権限付与を依頼してください。
            </p>
        </div>

        <div v-else class="legal-tab__body">
            <AiLoader v-if="aiLoading" message="徹底的な検査中です。<br>この処理には数分かかる場合があります。" />

            <div v-if="fetchError" class="legal-banner legal-banner--error">
                <div>
                    <p class="legal-banner__title">契約レビューの取得に失敗しました</p>
                    <p class="legal-banner__text">{{ fetchError }}</p>
                </div>
                <button type="button" class="legal-btn legal-btn--secondary" @click="fetchContract(true)">再読み込み</button>
            </div>

            <div v-if="deepSummary && contract" class="legal-banner legal-banner--warning">
                <div>
                    <p class="legal-banner__title">未保存のディープレビュー結果があります</p>
                    <p class="legal-banner__text">内容を確認して保存すると、この契約の正式なレビュー結果として反映されます。</p>
                </div>
                <button
                    type="button"
                    class="legal-btn legal-btn--primary"
                    :disabled="saveLoading"
                    @click="saveReview(contract)"
                >
                    {{ saveLoading ? '保存中...' : '結果を保存' }}
                </button>
            </div>

            <section v-if="contracts.length" class="legal-main-grid">
                <section v-if="contract" class="legal-summary">
                    <div class="legal-summary__head">
                        <div class="legal-summary__file">
                            <div class="legal-summary__icon">
                                <FileIcon :ext="fileMeta.extension" />
                            </div>
                            <div class="legal-summary__info">
                                <div class="legal-summary__title-row">
                                    <p class="legal-summary__title" :title="fileMeta.name">{{ fileMeta.name }}</p>
                                    <span v-if="contract.version" class="legal-summary__version">v{{ contract.version }}</span>
                                </div>
                                <p class="legal-summary__lead">{{ reviewStatusText }}</p>
                                <div class="legal-summary__chips">
                                    <span class="legal-chip">{{ statusLabel }}</span>
                                    <span class="legal-chip">{{ contractTypeLabel(contract.contract_type) }}</span>
                                    <span class="legal-chip">{{ contract.role }}</span>
                                    <span class="legal-chip">{{ deepSummary ? 'ディープ（未保存）' : `${reviewTypeLabel}レビュー` }}</span>
                                    <span v-if="fileMeta.sizeLabel" class="legal-chip">{{ fileMeta.sizeLabel }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="legal-summary__actions">
                            <button type="button" class="legal-btn legal-btn--primary" @click="toggleDetail">
                                {{ detailOpen ? '詳細を閉じる' : '詳細・ディープレビュー' }}
                            </button>
                            <button v-if="downloadUrl" type="button" class="legal-btn legal-btn--secondary" @click="downloadContract">
                                ダウンロード
                            </button>
                            <button type="button" class="legal-btn legal-btn--secondary" @click="toggleRenewal">
                                {{ renewalOpen ? '追加パネルを閉じる' : 'ファイルを追加' }}
                            </button>
                            <button type="button" class="legal-btn legal-btn--secondary" @click="toggleCompare">
                                {{ compareOpen ? '比較を閉じる' : '比較する' }}
                            </button>
                            <button type="button" class="legal-btn legal-btn--danger" @click="removeContract">削除</button>
                        </div>
                    </div>

                    <div class="legal-summary__stats">
                        <article class="legal-metric-card">
                            <span class="legal-metric-card__label">表示中のリスク</span>
                            <span :class="['legal-summary__badge', `legal-summary__badge--${activeSummary.overallRisk}`]">
                                {{ severityLabel(activeSummary.overallRisk) }}
                            </span>
                        </article>
                        <article class="legal-metric-card">
                            <span class="legal-metric-card__label">指摘件数</span>
                            <strong class="legal-metric-card__value">{{ activeSummary.findings.length }}</strong>
                        </article>
                        <article class="legal-metric-card">
                            <span class="legal-metric-card__label">高リスク</span>
                            <strong class="legal-metric-card__value">{{ activeSummaryCounts.high }}</strong>
                        </article>
                        <article class="legal-metric-card">
                            <span class="legal-metric-card__label">最終更新</span>
                            <strong class="legal-metric-card__value legal-metric-card__value--small">
                                {{ formatDate(contract.updated_at || contract.created_at || '') }}
                            </strong>
                        </article>
                    </div>

                    <dl class="legal-summary__details">
                        <div class="legal-summary__detail">
                            <dt>保存済みレビュー</dt>
                            <dd>{{ reviewTypeLabel }}レビュー</dd>
                        </div>
                        <div class="legal-summary__detail">
                            <dt>保存済み指摘</dt>
                            <dd>{{ summary.findings.length }}件</dd>
                        </div>
                        <div class="legal-summary__detail">
                            <dt>保存済み高リスク</dt>
                            <dd>{{ storedSummaryCounts.high }}件</dd>
                        </div>
                        <div class="legal-summary__detail">
                            <dt>プレビュー</dt>
                            <dd>{{ canInlinePreview ? '画面内で確認可能' : 'ダウンロード確認を推奨' }}</dd>
                        </div>
                    </dl>
                </section>

                <aside class="legal-files">
                    <div class="legal-files__head">
                        <div>
                            <p class="legal-files__eyebrow">履歴</p>
                            <p class="legal-files__title">契約レビュー履歴</p>
                        </div>
                        <button type="button" class="legal-btn legal-btn--secondary" @click="openRenewal">ファイル追加</button>
                    </div>

                    <div class="legal-files__list">
                        <button
                            v-for="item in contracts"
                            :key="item.id"
                            type="button"
                            class="legal-files__item"
                            :class="{ 'legal-files__item--active': contract?.id === item.id }"
                            @click="selectContract(item.id)"
                        >
                            <div class="legal-files__item-icon">
                                <FileIcon :ext="extensionFromPath(item.file_path)" />
                            </div>
                            <div class="legal-files__item-main">
                                <div class="legal-files__item-row">
                                    <p class="legal-files__item-name" :title="nameFromPath(item.file_path)">
                                        {{ nameFromPath(item.file_path) }}
                                    </p>
                                    <span v-if="item.version" class="legal-files__item-version">v{{ item.version }}</span>
                                </div>
                                <p class="legal-files__item-meta">
                                    {{ contractTypeLabel(item.contract_type) }}・{{ item.role }}・{{ formatDate(item.updated_at || item.created_at || '') }}
                                </p>
                            </div>
                            <span :class="['legal-summary__badge', `legal-summary__badge--${summaryFromContract(item).overallRisk}`]">
                                {{ severityLabel(summaryFromContract(item).overallRisk) }}
                            </span>
                        </button>
                    </div>
                </aside>
            </section>

            <section v-else-if="!renewalOpen" class="legal-state-card legal-state-card--empty">
                <p class="legal-state-card__title">レビュー済みの契約書がまだ登録されていません</p>
                <p class="legal-state-card__description">
                    1回のレビューで1ファイルを処理します。まず契約書を追加してクイックレビューを保存してください。
                </p>
                <div class="legal-state-card__actions">
                    <button type="button" class="legal-btn legal-btn--primary" @click="openRenewal">ファイルを追加</button>
                </div>
            </section>

            <transition v-if="renewalOpen" name="slide-fade">
                <section class="legal-upload-panel" :class="{ 'legal-upload-panel--empty': !contracts.length }">
                    <div class="legal-upload-panel__head">
                        <div>
                            <p class="legal-upload-panel__title">契約書を追加</p>
                            <p class="legal-upload-panel__caption">
                                まずクイックレビューを保存し、必要な契約だけ後からディープレビューに進めます。
                            </p>
                        </div>
                        <p class="legal-upload-panel__badge">クイックレビュー</p>
                    </div>

                    <div class="legal-upload-panel__form">
                        <div class="legal-upload-panel__field">
                            <label class="legal-upload-panel__label">契約種別</label>
                            <select v-model="uploadContractType" class="legal-upload-panel__select">
                                <option
                                    v-for="type in contractTypeDefaults"
                                    :key="type.value"
                                    :value="type.value"
                                >
                                    {{ type.label }}
                                </option>
                            </select>
                            <p v-if="uploadFocus" class="legal-upload-panel__hint">注目ポイント: {{ uploadFocus }}</p>
                        </div>

                        <div class="legal-upload-panel__field">
                            <label class="legal-upload-panel__label">当事者区分</label>
                            <div class="legal-upload-panel__chips">
                                <button
                                    v-for="role in contractRoleDefaults"
                                    :key="role.value"
                                    type="button"
                                    class="legal-upload-panel__chip"
                                    :class="{ 'legal-upload-panel__chip--active': uploadRole === role.value }"
                                    :aria-pressed="uploadRole === role.value"
                                    @click="uploadRole = role.value"
                                >
                                    {{ role.label }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <div
                        class="legal-upload"
                        :class="{
                            'legal-upload--filled': !!uploadFileMeta,
                            'legal-upload--dragging': uploadDragging,
                        }"
                        role="button"
                        tabindex="0"
                        @click="triggerUploadInput"
                        @keydown.enter.prevent="triggerUploadInput"
                        @keydown.space.prevent="triggerUploadInput"
                        @dragenter.prevent="uploadDragging = true"
                        @dragover.prevent="uploadDragging = true"
                        @dragleave.prevent="uploadDragging = false"
                        @drop.prevent="handleUploadDrop"
                    >
                        <input
                            ref="uploadInput"
                            type="file"
                            class="legal-upload__input"
                            :accept="uploadAccept"
                            @change="handleUploadChange"
                        />

                        <template v-if="!uploadFileMeta">
                            <div class="legal-upload__placeholder">
                                <div class="legal-upload__icon">
                                    <FileIcon ext="file" />
                                </div>
                                <div class="legal-upload__text">
                                    <p class="legal-upload__title">契約書ファイルをアップロード</p>
                                    <p class="legal-upload__hint">PDF / Office ドキュメントなどを 1 件まで選択できます。</p>
                                    <p class="legal-upload__cta">クリックまたはドラッグ&ドロップで追加</p>
                                </div>
                            </div>
                        </template>

                        <template v-else>
                            <div class="legal-upload__content">
                                <div class="legal-upload__info">
                                    <div class="legal-upload__icon">
                                        <FileIcon :ext="uploadFileMeta.ext" />
                                    </div>
                                    <div class="legal-upload__details">
                                        <p class="legal-upload__filename" :title="uploadFileMeta.name">{{ uploadFileMeta.name }}</p>
                                        <p class="legal-upload__meta">{{ uploadFileMeta.sizeLabel }}</p>
                                    </div>
                                </div>
                                <div class="legal-upload__actions">
                                    <button type="button" class="legal-btn legal-btn--secondary" @click.stop="triggerUploadInput">ファイルを変更</button>
                                    <button type="button" class="legal-btn legal-btn--ghost" @click.stop="clearUploadFile">削除</button>
                                </div>
                            </div>
                        </template>
                    </div>

                    <p v-if="uploadError" class="legal-upload-panel__error">{{ uploadError }}</p>

                    <div class="legal-upload-panel__actions">
                        <button type="button" class="legal-btn legal-btn--ghost" @click="toggleRenewal">閉じる</button>
                        <button
                            type="button"
                            class="legal-btn legal-btn--primary"
                            :disabled="uploadLoading"
                            @click="uploadContract"
                        >
                            {{ uploadLoading ? 'レビュー中...' : 'AIレビューして追加' }}
                        </button>
                    </div>
                </section>
            </transition>

        </div>

        <Teleport to="body">
            <transition name="legal-review-panel">
                <div v-if="detailOpen && contract" class="legal-review-panel" @click.self="toggleDetail">
                    <section class="legal-review-panel__surface">
                        <header class="legal-review-panel__header">
                            <div class="legal-review-panel__header-left">
                                <button type="button" class="legal-review-panel__back" @click="toggleDetail">
                                    <Back size="13" />
                                    <!-- <span>戻る</span> -->
                                </button>
                                <div class="legal-review-panel__header-copy">
                                    <p class="legal-review-panel__filename" :title="fileMeta.name">{{ fileMeta.name }}</p>
                                    <p class="legal-review-panel__meta">
                                        {{ contractTypeLabel(contract.contract_type) }}・{{ contract.role }}・{{ deepSummary ? 'ディープレビュー（未保存）' : `${reviewTypeLabel}レビュー` }}
                                    </p>
                                </div>
                            </div>

                            <div class="legal-review-panel__header-actions">
                                <button
                                    v-if="previewUrl && canInlinePreview"
                                    type="button"
                                    class="legal-btn legal-btn--secondary"
                                    @click="openPreviewInNewTab"
                                >
                                    別タブで開く
                                </button>
                                <button
                                    v-if="downloadUrl"
                                    type="button"
                                    class="legal-btn legal-btn--secondary"
                                    @click="downloadContract"
                                >
                                    ダウンロード
                                </button>
                            </div>
                        </header>

                        <div class="legal-review-panel__body" :class="{ 'legal-review-panel__body--compare': compareOpen }">
                            <section v-if="!compareOpen" class="legal-review-panel__preview">
                                <div class="legal-review-panel__section-head">
                                    <div>
                                        <p class="legal-review-panel__section-title">契約書プレビュー</p>
                                        <p class="legal-review-panel__section-caption">
                                            抽出した条文と段落を表示し、リスク箇所をそのまま確認できます。
                                        </p>
                                    </div>
                                </div>

                                <div class="legal-review-panel__preview-frame legal-review-panel__preview-frame--text">
                                    <ContractRiskReviewView
                                        v-if="currentDocumentIndex"
                                        :document-index="currentDocumentIndex"
                                        :document-title="fileMeta.name"
                                        :document-meta="`${contractTypeLabel(contract.contract_type)} / ${contract.role}`"
                                        :focus-request="focusRequest"
                                    />
                                    <div
                                        v-else-if="currentTextIndexLoading"
                                        class="legal-review-panel__preview-empty"
                                        aria-live="polite"
                                        >
                                        <p class="legal-review-panel__preview-empty-title">抽出テキストを準備しています</p>
                                        <p class="legal-review-panel__preview-empty-text">
                                            契約書の条文と段落を整理して、リスク箇所へ移動できるようにしています。
                                        </p>
                                    </div>
                                </div>
                                <div v-if="!currentDocumentIndex && !currentTextIndexLoading" class="legal-review-panel__preview-empty">
                                    <p class="legal-review-panel__preview-empty-title">
                                        {{ previewUrl ? 'ダウンロードして元ファイルをご確認ください。' : 'プレビューを表示できません。' }}
                                    </p>
                                    <p class="legal-review-panel__preview-empty-text">
                                        {{ previewUrl ? '別タブまたはダウンロードで内容を確認してください。' : '元ファイルが見つからないか、URL の生成に失敗しています。' }}
                                    </p>
                                </div>
                            </section>

                            <section v-else class="legal-compare-workspace">
                                <div class="legal-review-panel__section-head">
                                    <div>
                                        <p class="legal-review-panel__section-title">契約比較</p>
                                        <p class="legal-review-panel__section-caption">
                                            2つの契約書を並べて確認し、変更箇所を同じ位置で見比べられます。
                                        </p>
                                    </div>
                                    <select v-model="compareContractId" class="legal-compare__select">
                                        <option :value="null">比較元の契約を選択</option>
                                        <option
                                            v-for="item in compareCandidates"
                                            :key="item.id"
                                            :value="item.id"
                                        >
                                            {{ nameFromPath(item.file_path) }}{{ item.version ? ` / v${item.version}` : '' }}
                                        </option>
                                    </select>
                                </div>

                                <template v-if="compareContract">
                                    <div class="legal-compare__stats" v-if="comparisonResult">
                                        <span class="legal-compare__stat-chip legal-compare__stat-chip--added">追加 {{ comparisonResult.summary.added }}</span>
                                        <span class="legal-compare__stat-chip legal-compare__stat-chip--removed">削除 {{ comparisonResult.summary.removed }}</span>
                                        <span class="legal-compare__stat-chip legal-compare__stat-chip--modified">変更 {{ comparisonResult.summary.modified }}</span>
                                    </div>

                                    <section v-if="compareDataReady" class="legal-compare-summary">
                                        <div class="legal-compare-summary__head">
                                            <div>
                                                <p class="legal-compare-summary__title">AI比較サマリー</p>
                                                <p class="legal-compare-summary__caption">決定的な差分結果をもとに、法務上の変更点だけを短く整理しています。</p>
                                            </div>
                                            <button
                                                type="button"
                                                :class="['legal-btn', comparisonAiSummary ? 'legal-btn--secondary' : 'legal-btn--primary']"
                                                :disabled="comparisonSummaryLoading"
                                                @click="summarizeComparison(Boolean(comparisonAiSummary))"
                                            >
                                                {{ comparisonSummaryLoading ? '生成中...' : comparisonAiSummary ? '再生成' : 'AIサマリーを生成' }}
                                            </button>
                                        </div>

                                        <p v-if="comparisonSummaryError" class="legal-compare-summary__error">{{ comparisonSummaryError }}</p>
                                        <p v-else-if="comparisonSummaryLoading && !comparisonAiSummary" class="legal-compare-summary__loading">
                                            AIが比較内容を要約しています...
                                        </p>
                                        <p v-else-if="!comparisonAiSummary" class="legal-compare-summary__empty">
                                            必要な場合のみ AI サマリーを生成できます。条文差分を確認したうえで、法務上の要点を短く整理したいときに実行してください。
                                        </p>

                                        <template v-else-if="comparisonAiSummary">
                                            <div class="legal-compare-summary__grid">
                                                <article class="legal-compare-summary__card">
                                                    <p class="legal-compare-summary__card-label">全体像</p>
                                                    <p class="legal-compare-summary__card-text">{{ comparisonAiSummary.overview }}</p>
                                                </article>
                                                <article class="legal-compare-summary__card">
                                                    <p class="legal-compare-summary__card-label">法務影響</p>
                                                    <p class="legal-compare-summary__card-text">{{ comparisonAiSummary.legal_impact }}</p>
                                                </article>
                                            </div>

                                            <div class="legal-compare-summary__lists">
                                                <article v-if="comparisonAiSummary.key_changes.length" class="legal-compare-summary__list-card">
                                                    <p class="legal-compare-summary__card-label">重要な変更点</p>
                                                    <ul class="legal-compare-summary__list">
                                                        <li v-for="(item, index) in comparisonAiSummary.key_changes" :key="`compare-key-${index}`">{{ item }}</li>
                                                    </ul>
                                                </article>
                                                <article v-if="comparisonAiSummary.negotiation_points.length" class="legal-compare-summary__list-card">
                                                    <p class="legal-compare-summary__card-label">確認・交渉したい点</p>
                                                    <ul class="legal-compare-summary__list">
                                                        <li v-for="(item, index) in comparisonAiSummary.negotiation_points" :key="`compare-negotiation-${index}`">{{ item }}</li>
                                                    </ul>
                                                </article>
                                                <article v-if="comparisonAiSummary.caution_items.length" class="legal-compare-summary__list-card">
                                                    <p class="legal-compare-summary__card-label">追加確認メモ</p>
                                                    <ul class="legal-compare-summary__list">
                                                        <li v-for="(item, index) in comparisonAiSummary.caution_items" :key="`compare-caution-${index}`">{{ item }}</li>
                                                    </ul>
                                                </article>
                                            </div>
                                        </template>
                                    </section>

                                    <div class="legal-compare__tabs" v-if="compareDataReady">
                                        <button
                                            type="button"
                                            class="legal-compare__tab"
                                            :class="{ 'legal-compare__tab--active': compareViewMode === 'full' }"
                                            @click="compareViewMode = 'full'"
                                        >
                                            全文比較
                                        </button>
                                        <button
                                            type="button"
                                            class="legal-compare__tab"
                                            :class="{ 'legal-compare__tab--active': compareViewMode === 'article' }"
                                            @click="compareViewMode = 'article'"
                                        >
                                            条文比較
                                        </button>
                                    </div>

                                    <ContractCompareTextView
                                        v-if="compareDataReady && compareViewMode === 'full'"
                                        :key="`full-${compareRenderKey}`"
                                        :base-blocks="comparisonColumns.baseBlocks"
                                        :target-blocks="comparisonColumns.targetBlocks"
                                        :base-title="nameFromPath(compareContract.file_path)"
                                        :base-meta="compareBaseMeta"
                                        :target-title="fileMeta.name"
                                        :target-meta="compareTargetMeta"
                                    />

                                    <ContractCompareArticleView
                                        v-else-if="compareDataReady"
                                        :key="`article-${compareRenderKey}`"
                                        :rows="comparisonRows"
                                        :base-title="nameFromPath(compareContract.file_path)"
                                        :base-meta="compareBaseMeta"
                                        :target-title="fileMeta.name"
                                        :target-meta="compareTargetMeta"
                                    />

                                    <p v-else-if="compareIndexLoading" class="legal-compare__empty">
                                        比較用の契約テキストを読み込み中...
                                    </p>
                                    <p v-else class="legal-compare__empty">
                                        比較画面を準備しています...
                                    </p>
                                </template>

                                <p v-else class="legal-compare__empty">
                                    比較を開始するには、比較元の契約を選択してください。
                                </p>
                            </section>

                            <aside v-if="!compareOpen" class="legal-review-panel__findings">
                                <div class="legal-review-panel__section-head">
                                    <div>
                                        <p class="legal-review-panel__section-title">検出されたリスク</p>
                                        <p class="legal-review-panel__section-caption">
                                            {{ deepSummary ? '未保存のディープレビュー結果を表示中です。' : '保存済みレビュー結果を表示中です。' }}
                                        </p>
                                    </div>
                                </div>

                                <div class="legal-review-panel__findings-content">
                                    <ContractFindings :contract="resolvedSummary" @focus-finding="focusFinding" />
                                </div>

                                <div class="legal-review-panel__footer">
                                    <button
                                        v-if="deepSummary"
                                        type="button"
                                        class="legal-btn legal-btn--primary"
                                        :disabled="saveLoading"
                                        @click="saveReview(contract)"
                                    >
                                        {{ saveLoading ? '保存中...' : 'ディープレビューを保存' }}
                                    </button>
                                    <button
                                        v-else
                                        type="button"
                                        class="legal-btn legal-btn--primary"
                                        :disabled="aiLoading"
                                        @click="runDeepReview(contract)"
                                    >
                                        {{ aiLoading ? '解析中...' : 'ディープレビューを実行' }}
                                    </button>
                                </div>
                            </aside>
                        </div>
                    </section>
                </div>
            </transition>
        </Teleport>
    </div>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import { DateTime } from 'luxon'
import { filesize } from 'filesize'
import FileIcon from '@/components/Board/Mixed/FileIcon.vue'
import Back from '@/components/Icons/Back.vue'
import ContractCompareArticleView from '@/components/Project/Legal/ContractCompareArticleView.vue'
import ContractCompareTextView from '@/components/Project/Legal/ContractCompareTextView.vue'
import ContractFindings from '@/components/Project/Legal/ContractFindings.vue'
import ContractRiskReviewView from '@/components/Project/Legal/ContractRiskReviewView.vue'
import AiLoader from '@/components/Global/AiLoader.vue'
import { useApi } from '@/composables/api'
import { useDialog } from '@/composables/dialog'
import { useProject } from '@/composables/project'
import {
    ContractFindingSeverity,
    ProjectContractFinding,
    ProjectContractResponse,
} from '@/interface/projectInterface'
import { contractRoleDefaults, contractTypeDefaults } from '@/utils/tools'
import {
    attachFindingAnchors,
    buildContractComparisonColumns,
    buildContractComparisonRows,
    buildContractDocumentIndexFromText,
    compareContractIndexes,
    type ContractDocumentIndex,
} from '@/utils/contractAnalysis'

const props = defineProps<{
    hasPrivilage: boolean
}>()

type ContractExtractResponse = {
    contract_id?: number
    extension?: string
    text?: string | null
    document_index?: ContractDocumentIndex | null
}

type ContractComparisonAiSummary = {
    overview: string
    legal_impact: string
    key_changes: string[]
    negotiation_points: string[]
    caution_items: string[]
}

const INLINE_PREVIEW_EXTENSIONS = ['pdf', 'png', 'jpg', 'jpeg', 'gif', 'webp', 'txt']
const UPLOAD_ACCEPT = '.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.rtf,.odt,.ods,.odp'
const MAX_UPLOAD_BYTES = 209715 * 1024

const { selectedProject } = useProject()
const api = useApi()
const { ping, ask } = useDialog()

const detailOpen = ref(false)
const loading = ref(false)
const contractsState = ref<ProjectContractResponse[]>([])
const selectedContractId = ref<number | null>(null)
const fetchAttempted = ref(false)
const fetchError = ref('')
const aiLoading = ref(false)
const saveLoading = ref(false)
const uploadLoading = ref(false)
const renewalOpen = ref(false)
const compareOpen = ref(false)
const compareContractId = ref<number | null>(null)
const compareViewMode = ref<'full' | 'article'>('full')
const currentTextIndexLoading = ref(false)
const compareTextIndexLoading = ref(false)
const uploadDragging = ref(false)
const uploadError = ref('')
const uploadInput = ref<HTMLInputElement | null>(null)
const uploadFile = ref<File | null>(null)
const uploadContractType = ref<string>(contractTypeDefaults[0]?.value ?? '')
const uploadRole = ref('乙')
const deepResult = ref<Record<string, any> | null>(null)
const comparisonSummaryLoading = ref(false)
const comparisonSummaryError = ref('')
const comparisonAiSummary = ref<ContractComparisonAiSummary | null>(null)
const currentDocumentIndex = ref<ContractDocumentIndex | null>(null)
const compareDocumentIndex = ref<ContractDocumentIndex | null>(null)
const focusRequest = ref<{ token: number; page?: number; query?: string | null; fallbackQuery?: string | null } | null>(null)
let currentTextIndexRequestId = 0
let compareTextIndexRequestId = 0

const contracts = computed<ProjectContractResponse[]>(() => {
    if (fetchAttempted.value) {
        return contractsState.value
    }
    if (contractsState.value.length) {
        return contractsState.value
    }
    if (Array.isArray(selectedProject.value?.contracts) && selectedProject.value.contracts.length) {
        return selectedProject.value.contracts
    }
    if (selectedProject.value?.contract) {
        return [selectedProject.value.contract]
    }
    return []
})

const contract = computed<ProjectContractResponse | null>(() => {
    if (!contracts.value.length) return null
    if (selectedContractId.value) {
        const current = contracts.value.find(item => item.id === selectedContractId.value)
        if (current) return current
    }
    return contracts.value[0]
})

const extractFindingPage = (...sources: Array<string | number | null | undefined>) => {
    for (const source of sources) {
        if (typeof source === 'number' && Number.isFinite(source)) {
            return source
        }

        if (typeof source !== 'string' || !source.trim()) {
            continue
        }

        const match = source.match(/p\.?\s*(\d+)/i)
            ?? source.match(/page\s*(\d+)/i)
            ?? source.match(/(\d+)\s*ページ/u)

        if (match?.[1]) {
            return Number(match[1])
        }
    }

    return undefined
}

const parseSummary = (target: { result_json?: any; overall_risk?: ContractFindingSeverity } | null) => {
    if (!target) {
        return {
            overallRisk: 'unknown' as ContractFindingSeverity,
            findings: [],
        }
    }

    const raw = target.result_json ?? {}
    const overall = (raw.overall_risk ?? target.overall_risk ?? 'unknown') as ContractFindingSeverity
    const findings = Array.isArray(raw.findings) ? raw.findings : []

    return {
        overallRisk: overall || 'unknown',
        findings: findings.map((item: any) => ({
            section: item.section ?? item.clause_title ?? '',
            location: item.clause_location ?? '',
            issue: item.issue ?? item.risk_reason ?? item.title ?? item.clause_title ?? '',
            severity: (item.severity ?? item.risk_level ?? 'unknown') as ContractFindingSeverity,
            rationale: item.rationale ?? item.reason ?? item.risk_reason ?? '',
            suggestion: item.suggestion ?? item.remedy ?? '',
            category: item.category,
            score: item.score,
            quote: item.quote ?? item.clause_text ?? '',
            negotiation_tip: item.negotiation_tip ?? '',
            page: extractFindingPage(item.page, item.section, item.clause_location),
            anchor: item.anchor ?? null,
        })),
    }
}

const deepSummary = computed(() => {
    if (!deepResult.value?.json) {
        return null
    }
    return parseSummary({ result_json: deepResult.value.json })
})

const summary = computed(() => parseSummary(contract.value))
const activeSummary = computed(() => deepSummary.value ?? summary.value)
const resolvedSummary = computed(() => ({
    overallRisk: activeSummary.value.overallRisk,
    findings: attachFindingAnchors(activeSummary.value.findings, currentDocumentIndex.value),
}))

const createFindingCounts = (findings: Array<{ severity: ContractFindingSeverity }>) => ({
    high: findings.filter(item => item.severity === 'high').length,
    medium: findings.filter(item => item.severity === 'medium').length,
    low: findings.filter(item => item.severity === 'low').length,
})

const activeSummaryCounts = computed(() => createFindingCounts(activeSummary.value.findings))
const storedSummaryCounts = computed(() => createFindingCounts(summary.value.findings))

const nameFromPath = (path?: string | null) => {
    if (!path) return 'レビュー結果'
    const segments = path.split('/')
    return segments[segments.length - 1]
}

const extensionFromPath = (path?: string | null) => {
    const name = nameFromPath(path)
    return name.includes('.') ? name.split('.').pop()?.toString().toLowerCase() || 'file' : 'file'
}

const fileMeta = computed(() => {
    if (!contract.value?.file_path) {
        return {
            name: 'レビュー結果',
            extension: 'file',
            sizeLabel: '',
        }
    }

    const name = nameFromPath(contract.value.file_path)
    const extension = extensionFromPath(contract.value.file_path)
    const size = contract.value.file_size ?? contract.value.size ?? null

    return {
        name,
        extension: extension?.toString().toLowerCase() || 'file',
        sizeLabel: size ? filesize(size, size > 1_000_000 ? { standard: 'jedec', round: 1 } : { standard: 'jedec', round: 0 }) : '',
    }
})

const uploadFileMeta = computed(() => {
    if (!uploadFile.value) return null

    const name = uploadFile.value.name
    const extension = name.includes('.') ? name.split('.').pop()?.toLowerCase() || 'file' : 'file'
    const sizeLabel = filesize(
        uploadFile.value.size,
        uploadFile.value.size > 1_000_000 ? { standard: 'jedec', round: 1 } : { standard: 'jedec', round: 0 }
    )

    return { name, ext: extension, sizeLabel }
})

const uploadFocus = computed(() => contractTypeDefaults.find(item => item.value === uploadContractType.value)?.focus ?? '')
const uploadAccept = computed(() => UPLOAD_ACCEPT)

const reviewTypeLabel = computed(() => {
    if (!contract.value) return ''
    return contract.value.review_type === 'deep' ? 'ディープ' : 'クイック'
})

const deepReviewStateLabel = computed(() => {
    if (deepSummary.value) return '未保存'
    if (!contract.value) return '未実施'
    return contract.value.review_type === 'deep' ? '保存済み' : 'クイックのみ'
})

const reviewStatusText = computed(() => {
    if (deepSummary.value) {
        return 'ディープレビュー結果を確認中です。保存すると正式なレビュー結果に反映されます。'
    }
    if (!contract.value) {
        return '契約レビューはまだ登録されていません。'
    }
    return contract.value.review_type === 'deep'
        ? '保存済みのディープレビューを表示しています。'
        : '保存済みのクイックレビューを表示しています。'
})

const statusLabel = computed(() => {
    if (!contract.value) return ''
    return contract.value.active === false ? '契約終了' : '稼動中'
})

const previewUrl = computed(() => {
    if (!contract.value) return null
    if (contract.value.file_url) return contract.value.file_url
    if (contract.value.file_path && selectedProject.value?.id) {
        return `/projects/${selectedProject.value.id}/contract/file?contract_id=${contract.value.id}`
    }
    return null
})

const downloadUrl = computed(() => {
    if (!contract.value || !selectedProject.value?.id) return null
    if (contract.value.download_url) return contract.value.download_url
    if (!contract.value.file_path) return null
    return `/projects/${selectedProject.value.id}/contract/download?contract_id=${contract.value.id}`
})

const canInlinePreview = computed(() => INLINE_PREVIEW_EXTENSIONS.includes(fileMeta.value.extension))
const compareCandidates = computed(() => {
    if (!contract.value) return []
    return contracts.value.filter(item => item.id !== contract.value?.id)
})
const compareContract = computed<ProjectContractResponse | null>(() => {
    if (!compareContractId.value) return null
    return compareCandidates.value.find(item => item.id === compareContractId.value) ?? null
})
const comparisonRows = computed(() => buildContractComparisonRows(compareDocumentIndex.value, currentDocumentIndex.value))
const comparisonColumns = computed(() => buildContractComparisonColumns(compareDocumentIndex.value, currentDocumentIndex.value))
const comparisonResult = computed(() => {
    if (!contract.value || !compareContract.value) return null

    return compareContractIndexes(
        compareDocumentIndex.value,
        currentDocumentIndex.value,
        compareContract.value.id,
        contract.value.id,
    )
})
const compareIndexLoading = computed(() => currentTextIndexLoading.value || compareTextIndexLoading.value)
const compareDataReady = computed(() => Boolean(compareContract.value && currentDocumentIndex.value && compareDocumentIndex.value))
const compareRenderKey = computed(() => {
    const baseId = compareContract.value?.id ?? 'none'
    const targetId = contract.value?.id ?? 'none'
    const baseClauses = compareDocumentIndex.value?.clauses.length ?? 0
    const targetClauses = currentDocumentIndex.value?.clauses.length ?? 0

    return `${baseId}-${targetId}-${baseClauses}-${targetClauses}`
})
const compareBaseMeta = computed(() => {
    if (!compareContract.value) return ''
    const parts = [formatDate(compareContract.value.updated_at || compareContract.value.created_at || '')]
    if (compareContract.value.version) {
        parts.push(`v${compareContract.value.version}`)
    }
    return parts.filter(Boolean).join(' / ')
})
const compareTargetMeta = computed(() => {
    if (!contract.value) return ''
    const parts = [formatDate(contract.value.updated_at || contract.value.created_at || '')]
    if (contract.value.version) {
        parts.push(`v${contract.value.version}`)
    }
    return parts.filter(Boolean).join(' / ')
})
const shouldLoadCurrentDocumentIndex = computed(() => Boolean(
    contract.value
    && (
        compareOpen.value
        || detailOpen.value
    )
))

const comparisonSummaryPayload = computed(() => {
    if (!comparisonResult.value || !compareContract.value || !contract.value) {
        return null
    }

    return {
        base_contract_name: nameFromPath(compareContract.value.file_path),
        target_contract_name: nameFromPath(contract.value.file_path),
        summary: comparisonResult.value.summary,
        changes: comparisonResult.value.changes
            .slice(0, 25)
            .map(change => ({
                change_type: change.change_type,
                clause_label: change.clause_label,
                before_text: change.before_text ?? '',
                after_text: change.after_text ?? '',
            })),
    }
})

const severityLabel = (severity: ContractFindingSeverity) => {
    switch (severity) {
        case 'high':
            return '高'
        case 'medium':
            return '中'
        case 'low':
            return '低'
        default:
            return '不明'
    }
}

const formatDate = (value: string) => {
    if (!value) return '日時未設定'

    let dt = DateTime.fromISO(value)
    if (!dt.isValid) {
        dt = DateTime.fromSQL(value)
    }

    return dt.isValid ? dt.toFormat('yyyy年MM月dd日 HH:mm') : '日時未設定'
}

const contractTypeLabel = (value: string) => contractTypeDefaults.find(item => item.value === value)?.label ?? '—'
const summaryFromContract = (item: ProjectContractResponse) => parseSummary(item)

const resolveErrorMessage = (error: unknown, fallback: string) => {
    if (error instanceof Error && error.message) {
        return error.message
    }
    return fallback
}

const toggleDetail = () => {
    detailOpen.value = !detailOpen.value
}

const toggleRenewal = () => {
    renewalOpen.value = !renewalOpen.value
    uploadError.value = ''
}

const resetCompareState = () => {
    compareContractId.value = null
    compareDocumentIndex.value = null
    compareViewMode.value = 'full'
    comparisonAiSummary.value = null
    comparisonSummaryError.value = ''
}

const toggleCompare = () => {
    if (!contract.value) {
        return
    }

    if (!compareOpen.value) {
        if (!compareCandidates.value.length) {
            ping('比較するには、もう1つ契約書が必要です。先に別の契約書を追加してください。')
            return
        }

        compareOpen.value = true
        detailOpen.value = true
        compareContractId.value = compareContractId.value ?? compareCandidates.value[0]?.id ?? null
        return
    }

    compareOpen.value = false
    resetCompareState()
}

const openRenewal = () => {
    renewalOpen.value = true
    uploadError.value = ''
}

const downloadContract = () => {
    if (downloadUrl.value) {
        window.open(downloadUrl.value, '_blank', 'noopener')
    }
}

const openPreviewInNewTab = () => {
    if (previewUrl.value) {
        window.open(previewUrl.value, '_blank', 'noopener')
    }
}

const requestViewerFocus = (payload: { page?: number; query?: string | null; fallbackQuery?: string | null }) => {
    focusRequest.value = {
        token: Date.now(),
        page: payload.page,
        query: payload.query ?? null,
        fallbackQuery: payload.fallbackQuery ?? null,
    }
}

const focusFinding = (finding: ProjectContractFinding) => {
    requestViewerFocus({
        page: finding.anchor?.page ?? finding.page,
        query: finding.anchor?.query ?? finding.quote ?? finding.section ?? null,
        fallbackQuery: finding.anchor?.fallback_query ?? finding.section ?? finding.issue ?? null,
    })
}

const coerceDocumentIndex = (response: ContractExtractResponse | null | undefined) => {
    if (response?.document_index?.pages && response?.document_index?.clauses) {
        return response.document_index
    }

    return buildContractDocumentIndexFromText(response?.text ?? '')
}

const fetchContractTextIndex = async (
    targetContract: ProjectContractResponse,
    loadingRef: typeof currentTextIndexLoading,
) => {
    if (!selectedProject.value?.id) {
        return null
    }

    const response = await api.get(
        `/projects/${selectedProject.value.id}/contract/extract?contract_id=${targetContract.id}`,
        null,
        {
            loadingRef,
            silent: true,
        }
    ) as ContractExtractResponse | null

    return coerceDocumentIndex(response)
}

const summarizeComparison = async (force = false) => {
    if (!comparisonSummaryPayload.value) {
        comparisonAiSummary.value = null
        return
    }

    if (comparisonSummaryLoading.value) {
        return
    }

    if (!force && comparisonAiSummary.value) {
        return
    }

    comparisonSummaryError.value = ''
    comparisonSummaryLoading.value = true

    try {
        const response = await api.post('/summarize_contract_comparison', comparisonSummaryPayload.value, {
            loadingRef: comparisonSummaryLoading,
            silent: true,
        }) as ContractComparisonAiSummary | null

        if (response) {
            comparisonAiSummary.value = response
        }
    } catch (error) {
        comparisonSummaryError.value = resolveErrorMessage(error, '比較サマリーを生成できませんでした。')
    } finally {
        comparisonSummaryLoading.value = false
    }
}

const syncCurrentDocumentIndex = async () => {
    const targetContract = contract.value
    const requestId = ++currentTextIndexRequestId

    if (!shouldLoadCurrentDocumentIndex.value || !targetContract) {
        return
    }

    currentDocumentIndex.value = null

    try {
        const nextIndex = await fetchContractTextIndex(targetContract, currentTextIndexLoading)
        if (
            requestId === currentTextIndexRequestId
            && shouldLoadCurrentDocumentIndex.value
            && contract.value?.id === targetContract.id
        ) {
            currentDocumentIndex.value = nextIndex
        }
    } catch (error) {
        if (requestId === currentTextIndexRequestId) {
            ping(resolveErrorMessage(error, '比較用の契約テキストを読み取れませんでした。'))
        }
    }
}

const syncBaseCompareIndex = async () => {
    const targetContract = compareContract.value
    const requestId = ++compareTextIndexRequestId

    if (!compareOpen.value || !targetContract) {
        return
    }

    compareDocumentIndex.value = null

    try {
        const nextIndex = await fetchContractTextIndex(targetContract, compareTextIndexLoading)
        if (requestId === compareTextIndexRequestId && compareOpen.value && compareContract.value?.id === targetContract.id) {
            compareDocumentIndex.value = nextIndex
        }
    } catch (error) {
        if (requestId === compareTextIndexRequestId) {
            ping(resolveErrorMessage(error, '比較元の契約テキストを読み取れませんでした。'))
        }
    }
}

const confirmDiscardDeepReview = async () => {
    if (!deepSummary.value) return true
    const answer = await ask('未保存のディープレビュー結果があります。破棄して契約を切り替えますか？')
    return !!answer.value
}

const selectContract = async (id: number) => {
    if (selectedContractId.value === id) return
    if (!(await confirmDiscardDeepReview())) return

    selectedContractId.value = id
    deepResult.value = null
    currentDocumentIndex.value = null
    focusRequest.value = null
    if (compareContractId.value === id) {
        compareContractId.value = null
    }
}

const fetchContract = async (force = false) => {
    if (!selectedProject.value?.id) return

    if (!force) {
        if (fetchAttempted.value) return
        fetchAttempted.value = true
    }

    fetchError.value = ''

    try {
        const data = await api.get(`/projects/${selectedProject.value.id}/contract`, null, {
            loadingRef: loading,
            silent: true,
        })
        const list = Array.isArray(data?.contracts)
            ? data.contracts
            : (data?.contract ? [data.contract] : [])

        contractsState.value = list

        if (!list.length) {
            selectedContractId.value = null
            detailOpen.value = false
            return
        }

        if (!selectedContractId.value || !list.some((item: ProjectContractResponse) => item.id === selectedContractId.value)) {
            selectedContractId.value = list[0].id
        }
    } catch (error) {
        contractsState.value = []
        selectedContractId.value = null
        fetchError.value = resolveErrorMessage(error, '時間を置いて再度お試しください。')
    }
}

const getContractBlob = async () => {
    if (!previewUrl.value) throw new Error('プレビューURLを生成できませんでした。')

    const response = await fetch(previewUrl.value, { credentials: 'include' })
    if (!response.ok) {
        throw new Error('契約ファイルの取得に失敗しました。')
    }

    return response.blob()
}

const runDeepReview = async (selected: ProjectContractResponse) => {
    if (!selected.file_path) {
        ping('契約ファイルがありません。')
        return
    }

    try {
        const blob = await getContractBlob()
        const file = new File([blob], fileMeta.value.name, { type: blob.type || 'application/octet-stream' })
        const formData = new FormData()

        formData.append('file', file)
        formData.append('role', selected.role)
        formData.append('type', selected.contract_type)
        formData.append('review_type', 'deep')

        const data = await api.post('/review_document', formData, {
            loadingRef: aiLoading,
            silent: true,
        })

        if (data) {
            deepResult.value = data
            detailOpen.value = true
        }
    } catch (error) {
        ping(resolveErrorMessage(error, 'ディープレビューの実行に失敗しました。'))
    }
}

const saveReview = async (selected: ProjectContractResponse) => {
    if (!selectedProject.value?.id) {
        ping('プロジェクトが見つかりません。')
        return
    }
    if (!deepSummary.value) {
        ping('保存するレビューはありません。')
        return
    }

    try {
        if (!currentDocumentIndex.value) {
            await syncCurrentDocumentIndex()
        }

        const anchoredSummary = {
            overallRisk: deepSummary.value.overallRisk,
            findings: attachFindingAnchors(deepSummary.value.findings, currentDocumentIndex.value),
        }

        await api.post('/save_review', {
            id: selected.id,
            project_id: selectedProject.value.id,
            summary: anchoredSummary,
        }, {
            toast: '保存しました。',
            loadingRef: saveLoading,
            silent: true,
        })

        deepResult.value = null
        await fetchContract(true)
    } catch (error) {
        ping(resolveErrorMessage(error, 'レビュー結果の保存に失敗しました。'))
    }
}

const removeContract = async () => {
    if (!selectedProject.value?.id || !contract.value) return

    const answer = await ask('選択中の契約レビューを削除します。よろしいですか？')
    if (!answer.value) return

    const deletingId = contract.value.id

    try {
        const response = await api.del(`/projects/${selectedProject.value.id}/contract/${deletingId}`, null, {
            toast: '契約レビューを削除しました。',
            silent: true,
        })
        if (!response) return

        if (selectedContractId.value === deletingId) {
            selectedContractId.value = null
            detailOpen.value = false
            deepResult.value = null
        }

        await fetchContract(true)
    } catch (error) {
        ping(resolveErrorMessage(error, '契約レビューの削除に失敗しました。'))
    }
}

const assignUploadFile = (file: File | null) => {
    uploadError.value = ''

    if (!file) {
        return
    }

    if (file.size > MAX_UPLOAD_BYTES) {
        uploadError.value = 'ファイルサイズは 205MB 以下にしてください。'
        if (uploadInput.value) {
            uploadInput.value.value = ''
        }
        return
    }

    uploadFile.value = file
}

const triggerUploadInput = () => {
    uploadInput.value?.click()
}

const handleUploadChange = (event: Event) => {
    const target = event.target as HTMLInputElement
    const file = target.files ? target.files[0] : null
    assignUploadFile(file)
}

const handleUploadDrop = (event: DragEvent) => {
    uploadDragging.value = false
    const file = event.dataTransfer?.files?.[0] ?? null
    assignUploadFile(file)
}

const clearUploadFile = () => {
    uploadFile.value = null
    uploadError.value = ''
    if (uploadInput.value) {
        uploadInput.value.value = ''
    }
}

const uploadContract = async () => {
    if (!selectedProject.value?.id) {
        ping('プロジェクトが見つかりません。')
        return
    }
    if (!uploadFile.value) {
        uploadError.value = '契約書ファイルを選択してください。'
        return
    }

    try {
        const reviewForm = new FormData()
        reviewForm.append('file', uploadFile.value)
        reviewForm.append('role', uploadRole.value)
        reviewForm.append('type', uploadContractType.value)
        reviewForm.append('review_type', 'quick')

        const review = await api.post('/review_document', reviewForm, {
            loadingRef: uploadLoading,
            silent: true,
        })

        if (!review?.json || !review?.path) {
            throw new Error('レビュー結果の取得に失敗しました。')
        }

        const payload = await api.post(`/projects/${selectedProject.value.id}/contract`, {
            contract_data: review.json,
            file_path: review.path,
            contract_role: review.role,
            contract_type: review.type,
        }, {
            loadingRef: uploadLoading,
            toast: '契約書を追加しました。',
            silent: true,
        })

        if (payload) {
            selectedContractId.value = payload.id ?? null
            clearUploadFile()
            renewalOpen.value = false
            deepResult.value = null
            detailOpen.value = true
            await fetchContract(true)
        }
    } catch (error) {
        ping(resolveErrorMessage(error, '契約書の追加に失敗しました。'))
    }
}

watch(
    () => selectedProject.value?.id,
    () => {
        contractsState.value = []
        selectedContractId.value = null
        detailOpen.value = false
        renewalOpen.value = false
        compareOpen.value = false
        compareContractId.value = null
        deepResult.value = null
        currentDocumentIndex.value = null
        compareDocumentIndex.value = null
        focusRequest.value = null
        fetchAttempted.value = false
        fetchError.value = ''
        clearUploadFile()
        fetchContract()
    },
    { immediate: true }
)

watch(
    () => contract.value,
    newContract => {
        if (newContract?.role) {
            uploadRole.value = newContract.role
        } else {
            uploadRole.value = '乙'
        }

        if (newContract?.contract_type) {
            uploadContractType.value = newContract.contract_type
        } else if (!uploadContractType.value && contractTypeDefaults.length) {
            uploadContractType.value = contractTypeDefaults[0].value
        }

        deepResult.value = null
        currentDocumentIndex.value = null
        focusRequest.value = null
    },
    { immediate: true }
)

watch(
    () => contracts.value,
    list => {
        if (!list.length) {
            selectedContractId.value = null
            return
        }

        if (!selectedContractId.value || !list.some(item => item.id === selectedContractId.value)) {
            selectedContractId.value = list[0].id
        }
    },
    { immediate: true }
)

watch(detailOpen, isOpen => {
    document.body.style.overflow = isOpen ? 'hidden' : ''

    if (!isOpen && compareOpen.value) {
        compareOpen.value = false
        resetCompareState()
    }
})

watch(compareContractId, value => {
    compareDocumentIndex.value = null

    if (!value) {
        return
    }
})

watch(compareRenderKey, () => {
    comparisonAiSummary.value = null
    comparisonSummaryError.value = ''
})

watch(
    () => [shouldLoadCurrentDocumentIndex.value, contract.value?.id],
    ([shouldLoad]) => {
        if (shouldLoad && contract.value) {
            void syncCurrentDocumentIndex()
            return
        }

        currentDocumentIndex.value = null
    },
    { immediate: true }
)

watch(
    () => [compareOpen.value, compareContract.value?.id],
    () => {
        if (compareOpen.value && compareContract.value) {
            void syncBaseCompareIndex()
        }
    },
    { immediate: true }
)

onBeforeUnmount(() => {
    document.body.style.overflow = ''
})
</script>

<style scoped>
.legal-tab {
    --legal-surface: var(--background-color);
    --legal-surface-muted: var(--bg3);
    --legal-border: var(--calendarBorder);
    --legal-text: var(--primary-color);
    --legal-muted: var(--font-color, #666);
    display: flex;
    flex-direction: column;
    min-height: 100%;
    background: var(--background-color);
}

.legal-tab__body {
    display: flex;
    flex-direction: column;
    gap: 14px;
    padding: 16px;
    height: 100%;
    box-sizing: border-box;
}

.legal-files,
.legal-summary,
.legal-upload-panel,
.legal-detail__preview,
.legal-detail__findings,
.legal-state-card {
    border: 1px solid var(--legal-border);
    border-radius: 12px;
    background: var(--legal-surface);
}

.legal-files__eyebrow,
.legal-state-card__eyebrow {
    margin: 0;
    font-size: 10px;
    letter-spacing: 0.08em;
    color: var(--legal-muted);
    font-weight: 600;
}

.legal-state-card__title {
    margin: 0;
    font-size: 20px;
    line-height: 1.2;
    color: var(--legal-text);
}

.legal-state-card__description {
    margin: 0;
    font-size: 12px;
    line-height: 1.6;
    color: var(--legal-muted);
}

.legal-stat-card,
.legal-metric-card {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 6px;
    padding: 10px 12px;
    border-radius: 10px;
    border: 1px solid var(--legal-border);
    background: var(--legal-surface-muted);
}

.legal-stat-card__label,
.legal-metric-card__label {
    font-size: 11px;
    color: var(--legal-muted);
}

.legal-stat-card__value,
.legal-metric-card__value {
    font-size: 14px;
    line-height: 1.2;
    color: var(--legal-text);
}

.legal-metric-card__value--small {
    font-size: 12px;
    line-height: 1.5;
}

.legal-banner {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    align-items: center;
    padding: 12px 14px;
    border-radius: 10px;
}

.legal-banner--error {
    border: 1px solid rgba(209, 67, 67, 0.18);
    background: var(--legal-surface-muted);
}

.legal-banner--warning {
    border: 1px solid var(--legal-border);
    background: var(--legal-surface-muted);
}

.legal-banner__title {
    margin: 0 0 4px;
    font-size: 13px;
    font-weight: 700;
    color: var(--legal-text);
}

.legal-banner__text {
    margin: 0;
    font-size: 11px;
    color: var(--legal-muted);
}

.legal-main-grid {
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.legal-files {
    display: flex;
    flex-direction: column;
    gap: 12px;
    padding: 12px;
    min-height: 0;
}

.legal-files__head {
    display: flex;
    justify-content: space-between;
    gap: 10px;
    align-items: flex-start;
}

.legal-files__title {
    margin: 4px 0 0;
    font-size: 14px;
    font-weight: 700;
    color: var(--legal-text);
}

.legal-files__list {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 8px;
    max-height: none;
    overflow: visible;
}

.legal-files__item {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    width: 100%;
    min-width: 0;
    padding: 10px;
    border-radius: 10px;
    border: 1px solid var(--legal-border);
    background: var(--legal-surface-muted);
    text-align: left;
    cursor: pointer;
    transition: background-color 0.2s ease, border-color 0.2s ease;
    box-sizing: border-box !important;
}

.legal-files__item:hover {
    background: var(--background-color);
}

.legal-files__item--active {
    border-color: var(--primary-color);
    background: var(--background-color);
}

.legal-files__item-icon {
    width: 36px;
    min-width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.legal-files__item-main {
    min-width: 0;
    flex: 1;
    overflow: hidden;
}

.legal-files__item-row {
    display: flex;
    justify-content: space-between;
    gap: 6px;
    align-items: center;
    min-width: 0;
}

.legal-files__item-name {
    margin: 0;
    flex: 1;
    min-width: 0;
    font-size: 12px;
    font-weight: 700;
    color: var(--legal-text);
    white-space: normal;
    overflow-wrap: anywhere;
    line-height: 1.4;
}

.legal-files__item-version {
    padding: 2px 8px;
    border-radius: 999px;
    background: var(--legal-border);
    color: var(--legal-text);
    font-size: 9px;
    font-weight: 700;
    white-space: nowrap;
}

.legal-files__item-meta {
    margin: 4px 0 0;
    font-size: 10px;
    color: var(--legal-muted);
    line-height: 1.5;
}

.legal-summary {
    display: flex;
    flex-direction: column;
    gap: 12px;
    padding: 14px;
}

.legal-summary__head {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    align-items: flex-start;
}

.legal-summary__file {
    display: flex;
    gap: 10px;
    align-items: center;
    min-width: 0;
}

.legal-summary__icon {
    width: 48px;
    min-width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    background: var(--legal-surface-muted);
}

.legal-summary__info {
    display: flex;
    flex-direction: column;
    gap: 6px;
    min-width: 0;
}

.legal-summary__title-row {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.legal-summary__title {
    margin: 0;
    font-size: 15px;
    line-height: 1.25;
    font-weight: 700;
    color: var(--legal-text);
    word-break: break-word;
}

.legal-summary__version {
    padding: 2px 6px;
    border-radius: 999px;
    font-size: 9px;
    font-weight: 700;
    color: var(--legal-text);
    background: var(--legal-surface-muted);
    border: 1px solid var(--legal-border);
}

.legal-summary__lead {
    margin: 0;
    font-size: 11px;
    line-height: 1.5;
    color: var(--legal-muted);
}

.legal-summary__chips {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.legal-chip {
    display: inline-flex;
    align-items: center;
    padding: 4px 8px;
    border-radius: 999px;
    border: 1px solid var(--legal-border);
    background: var(--legal-surface-muted);
    font-size: 10px;
    color: var(--legal-muted);
    white-space: nowrap;
}

.legal-summary__actions,
.legal-upload-panel__actions,
.legal-detail__section-actions,
.legal-detail__footer,
.legal-state-card__actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.legal-summary__actions {
    justify-content: flex-end;
}

.legal-summary__stats {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 8px;
}

.legal-summary__details {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 8px;
    margin: 0;
}

.legal-summary__detail {
    display: flex;
    flex-direction: column;
    gap: 4px;
    padding: 10px;
    border-radius: 10px;
    background: var(--legal-surface-muted);
    border: 1px solid var(--legal-border);
}

.legal-summary__detail dt {
    font-size: 10px;
    color: var(--legal-muted);
}

.legal-summary__detail dd {
    margin: 0;
    font-size: 12px;
    line-height: 1.5;
    font-weight: 600;
    color: var(--legal-text);
}

.legal-summary__badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    align-self: flex-start;
    min-width: 52px;
    max-width: 100%;
    padding: 3px 10px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 700;
    border: 1px solid var(--legal-border);
    white-space: nowrap;
}

.legal-files__item .legal-summary__badge,
.legal-metric-card .legal-summary__badge {
    flex: 0 0 auto;
    width: fit-content;
    /* margin-left: auto; */
}

.legal-summary__badge--high {
    color: #d14343;
    border-color: rgba(209, 67, 67, 0.4);
    background: rgba(209, 67, 67, 0.08);
}

.legal-summary__badge--medium {
    color: #ff8a00;
    border-color: rgba(255, 138, 0, 0.4);
    background: rgba(255, 138, 0, 0.08);
}

.legal-summary__badge--low {
    color: #4c566a;
    border-color: rgba(76, 86, 106, 0.3);
    background: rgba(76, 86, 106, 0.08);
}

.legal-summary__badge--unknown {
    color: var(--legal-muted);
    background: rgba(0, 0, 0, 0.04);
}

.legal-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 32px;
    padding: 0 10px;
    border-radius: 6px;
    border: 1px solid transparent;
    font-size: 11px;
    font-weight: 700;
    cursor: pointer;
    transition: background-color 0.18s ease, border-color 0.18s ease, opacity 0.18s ease;
    white-space: nowrap;
}

.legal-btn:disabled {
    cursor: default;
    opacity: 0.55;
}

.legal-btn--primary {
    background: var(--primary-button);
    color: #fff;
    border-color: var(--primary-button);
}

.legal-btn--secondary {
    background: var(--legal-surface-muted);
    color: var(--legal-text);
    border-color: var(--legal-border);
}

.legal-btn--ghost {
    background: transparent;
    color: var(--legal-text);
    border-color: var(--legal-border);
}

.legal-btn--danger {
    background: var(--legal-surface-muted);
    color: #b12e2e;
    border-color: rgba(209, 67, 67, 0.24);
}

.legal-state-card {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    justify-content: center;
    gap: 8px;
    min-height: 220px;
    padding: 18px;
}

.legal-state-card--locked {
    min-height: 100%;
    border: none;
}

.legal-state-card--empty,
.legal-upload-panel--empty {
    width: min(960px, 100%);
    align-self: center;
}

.legal-state-card--empty {
    align-items: center;
    min-height: 200px;
    text-align: center;
}

.legal-state-card--empty .legal-state-card__actions {
    justify-content: center;
}

.legal-upload-panel--empty {
    margin-top: -6px;
}

.legal-upload-panel {
    display: flex;
    flex-direction: column;
    gap: 12px;
    padding: 14px;
}

.legal-upload-panel__head {
    display: flex;
    justify-content: space-between;
    gap: 10px;
    align-items: center;
}

.legal-upload-panel__title {
    margin: 0 0 4px;
    font-size: 15px;
    font-weight: 700;
    color: var(--legal-text);
}

.legal-upload-panel__caption {
    margin: 0;
    font-size: 11px;
    line-height: 1.5;
    color: var(--legal-muted);
}

.legal-upload-panel__badge {
    margin: 0;
    padding: 4px 8px;
    border-radius: 999px;
    background: var(--legal-surface-muted);
    color: var(--legal-text);
    font-size: 10px;
    font-weight: 700;
    white-space: nowrap;
    border: 1px solid var(--legal-border);
}

.legal-upload-panel__form {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
}

.legal-upload-panel__field {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.legal-upload-panel__label {
    font-size: 11px;
    font-weight: 600;
    color: var(--legal-muted);
}

.legal-upload-panel__select {
    min-height: 36px;
    padding: 0 10px;
    border-radius: 8px;
    border: 1px solid var(--legal-border);
    background: var(--background-color);
    color: var(--legal-text);
    font-size: 12px;
}

.legal-upload-panel__hint {
    margin: 0;
    font-size: 10px;
    line-height: 1.5;
    color: var(--legal-muted);
}

.legal-upload-panel__chips {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.legal-upload-panel__chip {
    min-height: 34px;
    padding: 0 10px;
    border-radius: 999px;
    border: 1px solid var(--legal-border);
    background: var(--background-color);
    color: var(--legal-muted);
    font-size: 11px;
    font-weight: 600;
    cursor: pointer;
}

.legal-upload-panel__chip--active {
    background: var(--legal-surface-muted);
    border-color: var(--primary-color);
    color: var(--legal-text);
}

.legal-upload-panel__error {
    margin: 0;
    padding: 10px;
    border-radius: 8px;
    background: var(--legal-surface-muted);
    border: 1px solid rgba(209, 67, 67, 0.18);
    color: #b12e2e;
    font-size: 11px;
    font-weight: 600;
}

.legal-upload {
    display: flex;
    flex-direction: column;
    gap: 12px;
    padding: 14px;
    border-radius: 10px;
    border: 1px dashed var(--primary-color);
    background: var(--legal-surface-muted);
    cursor: pointer;
    transition: border-color 0.2s ease, background-color 0.2s ease;
}

.legal-upload:hover,
.legal-upload--dragging {
    border-color: var(--primary-color);
    background: var(--background-color);
}

.legal-upload--filled {
    border-style: solid;
}

.legal-upload__input {
    display: none;
}

.legal-upload__placeholder {
    display: flex;
    align-items: center;
    gap: 12px;
}

.legal-upload__icon {
    width: 42px;
    min-width: 42px;
    height: 42px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.legal-upload__text,
.legal-upload__details,
.legal-upload__content {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.legal-upload__title,
.legal-upload__filename {
    margin: 0;
    font-size: 12px;
    font-weight: 700;
    color: var(--legal-text);
}

.legal-upload__hint,
.legal-upload__cta,
.legal-upload__meta {
    margin: 0;
    font-size: 10px;
    line-height: 1.6;
    color: var(--legal-muted);
}

.legal-upload__content {
    gap: 10px;
}

.legal-upload__info {
    display: flex;
    align-items: center;
    gap: 10px;
}

.legal-upload__filename {
    word-break: break-all;
}

.legal-upload__actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.legal-review-panel {
    position: fixed;
    inset: 0;
    z-index: 1200;
    background: rgba(0, 0, 0, 0.36);
    color: var(--primary-color);
}

.legal-review-panel__surface {
    position: absolute;
    inset: 0 0 0 0;
    display: flex;
    flex-direction: column;
    min-height: 0;
    background: var(--background-color);
}

.legal-review-panel__header {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    align-items: center;
    padding: 16px 18px;
    border-bottom: 1px solid var(--legal-border);
    background: var(--background-color);
}

.legal-review-panel__header-left {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
}

.legal-review-panel__back {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-height: 34px;
    padding: 0 12px 0 10px;
    border: 1px solid var(--legal-border);
    border-radius: 6px;
    background: var(--legal-surface-muted);
    color: var(--legal-text);
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    white-space: nowrap;
}

.legal-review-panel__back :deep(svg) {
    fill: currentColor;
    flex-shrink: 0;
}

.legal-review-panel__header-copy {
    min-width: 0;
}

.legal-review-panel__filename {
    margin: 0;
    font-size: 15px;
    font-weight: 700;
    color: var(--legal-text);
    line-height: 1.4;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.legal-review-panel__meta {
    margin: 2px 0 0;
    font-size: 11px;
    color: var(--legal-muted);
    line-height: 1.5;
}

.legal-review-panel__header-actions,
.legal-review-panel__footer {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.legal-review-panel__body {
    display: grid;
    grid-template-columns: minmax(0, 1.45fr) minmax(420px, 0.95fr);
    gap: 14px;
    padding: 14px;
    flex: 1;
    min-height: 0;
    height: calc(100vh - 67px);
    box-sizing: border-box;
}

.legal-review-panel__body--compare {
    grid-template-columns: 1fr;
}

.legal-review-panel__preview,
.legal-review-panel__findings {
    min-height: 0;
    border: 1px solid var(--legal-border);
    border-radius: 10px;
    background: var(--legal-surface);
}

.legal-review-panel__preview {
    display: flex;
    flex-direction: column;
    padding: 14px;
}

.legal-review-panel__findings {
    display: flex;
    flex-direction: column;
    padding: 14px;
}

.legal-review-panel__section-head {
    display: flex;
    justify-content: space-between;
    gap: 10px;
    align-items: flex-start;
    margin-bottom: 12px;
}

.legal-review-panel__section-title {
    margin: 0 0 4px;
    font-size: 14px;
    font-weight: 700;
    color: var(--legal-text);
}

.legal-review-panel__section-caption {
    margin: 0;
    font-size: 10px;
    line-height: 1.5;
    color: var(--legal-muted);
}

.legal-review-panel__preview-frame {
    flex: 1;
    min-height: 0;
    border-radius: 10px;
    overflow: hidden;
    border: 1px solid var(--legal-border);
    background: var(--background-color);
}

.legal-review-panel__preview-frame--text {
    display: flex;
    min-height: 0;
}

.legal-review-panel__preview-frame--text > * {
    flex: 1;
    min-height: 0;
}

.legal-review-panel__preview-frame iframe {
    width: 100%;
    height: 100%;
    border: none;
    background: #fff;
}

.legal-review-panel__preview-empty {
    flex: 1;
    min-height: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
    border-radius: 10px;
    border: 1px dashed var(--legal-border);
    background: var(--legal-surface-muted);
    text-align: center;
}

.legal-review-panel__preview-empty-title {
    margin: 0;
    font-size: 13px;
    font-weight: 700;
    color: var(--legal-text);
}

.legal-review-panel__preview-empty-text {
    margin: 0;
    font-size: 11px;
    line-height: 1.6;
    color: var(--legal-muted);
}

.legal-review-panel__findings-content {
    flex: 1;
    min-height: 0;
    overflow: auto;
    padding-right: 2px;
}

.legal-compare-workspace {
    display: flex;
    flex-direction: column;
    gap: 14px;
    padding: 16px 18px 24px;
    border: 1px solid var(--legal-border);
    border-radius: 10px;
    background: var(--legal-surface);
    height: 100%;
    overflow: auto;
    box-sizing: border-box;
    min-height: 0;
}

.legal-compare__empty {
    margin: 4px 0 0;
    font-size: 12px;
    color: var(--legal-muted);
}

.legal-compare__select {
    min-width: 200px;
    max-width: 100%;
    border: 1px solid var(--calendarBorder);
    border-radius: 8px;
    background: var(--bg3);
    color: var(--primary-color);
    padding: 8px 10px;
    font-size: 12px;
    font-weight: 600;
    box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.04);
    color-scheme: dark;
}

.legal-compare__select:focus {
    outline: none;
    border-color: rgba(41, 196, 122, 0.34);
    box-shadow:
        0 0 0 3px rgba(41, 196, 122, 0.12),
        inset 0 0 0 1px rgba(255, 255, 255, 0.06);
}

.legal-compare__select option {
    background: var(--background-color);
    color: var(--primary-color);
}

.legal-compare__stats {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.legal-compare__stat-chip {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 30px;
    padding: 0 12px 0 10px;
    border-radius: 999px;
    border: 1px solid var(--legal-border);
    background: var(--legal-surface);
    color: var(--legal-text);
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0.02em;
    white-space: nowrap;
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.05);
}

.legal-compare__stat-chip::before {
    content: '';
    width: 8px;
    height: 8px;
    border-radius: 999px;
    background: currentColor;
    flex: 0 0 auto;
}

.legal-compare__stat-chip--added {
    color: #188f57;
    border-color: rgba(24, 143, 87, 0.28);
    background:
        linear-gradient(var(--legal-surface), var(--legal-surface)) padding-box,
        linear-gradient(90deg, rgba(24, 143, 87, 0.45), rgba(24, 143, 87, 0.12)) border-box;
    border: 1px solid transparent;
}

.legal-compare__stat-chip--removed {
    color: #d28308;
    border-color: rgba(210, 131, 8, 0.28);
    background:
        linear-gradient(var(--legal-surface), var(--legal-surface)) padding-box,
        linear-gradient(90deg, rgba(210, 131, 8, 0.42), rgba(210, 131, 8, 0.12)) border-box;
    border: 1px solid transparent;
}

.legal-compare__stat-chip--modified {
    color: var(--legal-text);
    background:
        linear-gradient(
            90deg,
            var(--legal-surface) 0%,
            var(--legal-surface) 100%
        ) padding-box,
        linear-gradient(
            90deg,
            rgba(210, 131, 8, 0.55) 0%,
            rgba(210, 131, 8, 0.55) 48%,
            rgba(24, 143, 87, 0.5) 52%,
            rgba(24, 143, 87, 0.5) 100%
        ) border-box;
    border: 1px solid transparent;
}

.legal-compare__stat-chip--modified::before {
    background: linear-gradient(
        90deg,
        rgba(210, 131, 8, 0.95) 0%,
        rgba(210, 131, 8, 0.95) 48%,
        rgba(24, 143, 87, 0.9) 52%,
        rgba(24, 143, 87, 0.9) 100%
    );
}

.legal-compare__tabs {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.legal-compare-summary {
    display: flex;
    flex-direction: column;
    gap: 12px;
    padding: 14px 16px;
    border: 1px solid var(--legal-border);
    border-radius: 12px;
    background: var(--legal-surface-muted);
}

.legal-compare-summary__head {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    align-items: flex-start;
}

.legal-compare-summary__title,
.legal-compare-summary__card-label {
    margin: 0;
    font-size: 13px;
    font-weight: 700;
    color: var(--legal-text);
}

.legal-compare-summary__caption,
.legal-compare-summary__loading {
    margin: 4px 0 0;
    font-size: 11px;
    line-height: 1.6;
    color: var(--legal-muted);
}

.legal-compare-summary__empty {
    margin: 0;
    padding: 12px;
    border-radius: 10px;
    border: 1px dashed var(--legal-border);
    background: var(--legal-surface);
    color: var(--legal-muted);
    font-size: 11px;
    line-height: 1.7;
}

.legal-compare-summary__error {
    margin: 0;
    padding: 10px 12px;
    border-radius: 10px;
    border: 1px solid rgba(209, 67, 67, 0.22);
    background: rgba(209, 67, 67, 0.08);
    color: #e58d8d;
    font-size: 11px;
    line-height: 1.6;
}

.legal-compare-summary__grid,
.legal-compare-summary__lists {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
}

.legal-compare-summary__lists {
    grid-template-columns: repeat(3, minmax(0, 1fr));
}

.legal-compare-summary__card,
.legal-compare-summary__list-card {
    display: flex;
    flex-direction: column;
    gap: 8px;
    padding: 12px;
    border-radius: 10px;
    border: 1px solid rgba(255, 255, 255, 0.04);
    background: var(--legal-surface);
}

.legal-compare-summary__card-text {
    margin: 0;
    font-size: 12px;
    line-height: 1.75;
    color: var(--legal-text);
}

.legal-compare-summary__list {
    margin: 0;
    padding-left: 18px;
    display: flex;
    flex-direction: column;
    gap: 8px;
    color: var(--legal-text);
    font-size: 12px;
    line-height: 1.7;
}

.legal-compare__tab {
    min-height: 36px;
    padding: 0 14px;
    border: 1px solid var(--legal-border);
    border-radius: 999px;
    background: var(--legal-surface-muted);
    color: var(--legal-text);
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
}

.legal-compare__tab--active {
    border-color: rgba(41, 196, 122, 0.36);
    background: rgba(41, 196, 122, 0.14);
}

.legal-review-panel__footer {
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid var(--legal-border);
}

.slide-fade-enter-active,
.slide-fade-leave-active {
    transition: opacity 0.25s ease, transform 0.25s ease;
}

.slide-fade-enter-from,
.slide-fade-leave-to {
    opacity: 0;
    transform: translateY(10px);
}

@media (max-width: 1279px) {
    .legal-summary__head {
        flex-direction: column;
    }

    .legal-summary__actions {
        justify-content: flex-start;
    }

    .legal-review-panel__body {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 899px) {
    .legal-tab__body {
        padding: 16px;
    }

    .legal-summary__stats,
    .legal-summary__details,
    .legal-upload-panel__form,
    .legal-compare-summary__grid,
    .legal-compare-summary__lists {
        grid-template-columns: 1fr;
    }

    .legal-upload-panel,
    .legal-summary,
    .legal-files,
    .legal-state-card {
        padding: 12px;
    }

    .legal-review-panel__header {
        padding: 12px;
    }

    .legal-review-panel__body {
        height: calc(100vh - 61px);
        padding: 12px;
    }

    .legal-review-panel__preview,
    .legal-review-panel__findings {
        padding: 12px;
    }
}

@media (max-width: 639px) {
    .legal-banner,
    .legal-files__head,
    .legal-upload-panel__head,
    .legal-review-panel__header,
    .legal-review-panel__header-left,
    .legal-review-panel__section-head,
    .legal-compare-summary__head {
        flex-direction: column;
        align-items: flex-start;
    }

    .legal-summary__file,
    .legal-upload__placeholder,
    .legal-upload__info {
        align-items: flex-start;
    }

    .legal-summary__title {
        font-size: 14px;
    }

    .legal-state-card__title {
        font-size: 18px;
    }

    .legal-review-panel__body {
        grid-template-columns: 1fr;
    }

    .legal-compare__select {
        width: 100%;
        min-width: 0;
    }

}

.legal-review-panel-enter-active,
.legal-review-panel-leave-active {
    transition: opacity 0.24s ease;
}

.legal-review-panel-enter-active .legal-review-panel__surface,
.legal-review-panel-leave-active .legal-review-panel__surface {
    transition: transform 0.24s ease;
}

.legal-review-panel-enter-from,
.legal-review-panel-leave-to {
    opacity: 0;
}

.legal-review-panel-enter-from .legal-review-panel__surface,
.legal-review-panel-leave-to .legal-review-panel__surface {
    transform: translateX(100%);
}
</style>
