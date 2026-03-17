<template>
    <div class="overlay">
        <div class="projectModalInner" style="width: 100%;height: 100%;">
            <div class="projectModalMainHeader">
                <div class="flex justify-start gap-5 md:justify-between w-full">
                    <div class="flex gap-5 ml-5 items-center">
                        <div class="cursor-pointer fill-[var(--primary-color)]" @click="router.back()">
                            <svg version="1.1" width="15" height="15" viewBox="0 0 20 32" xmlns="http://www.w3.org/2000/svg">
                                <path d="M0.775 17.789c1.305 1.166 2.612 2.332 3.927 3.486 1.311 1.161 2.634 2.308 3.953 3.46 1.316 1.156 2.646 2.296 3.973 3.439 1.33 1.139 2.667 2.273 4.015 3.394 0.662 0.551 1.647 0.52 2.272-0.107 0.65-0.654 0.619-1.725-0.020-2.393-1.198-1.253-2.407-2.495-3.621-3.729-1.232-1.245-2.462-2.492-3.704-3.725-0.902-0.9-1.803-1.802-2.707-2.699-0.033-0.032-0.055-0.069-0.072-0.106-0.045-0.036-0.082-0.080-0.111-0.129-0.069-0.047-0.129-0.117-0.176-0.216-0.021-0.047-0.044-0.092-0.066-0.136-0.12-0.062-0.214-0.168-0.246-0.325-0.001-0.005-0.002-0.009-0.003-0.014-0.104-0.157-0.187-0.327-0.254-0.505-0.109-0.185-0.182-0.388-0.226-0.601-0.002-0.012-0.005-0.024-0.007-0.036-0.016-0.085-0.028-0.172-0.036-0.259-0.195-0.593-0.26-1.183 0.030-1.653 0.006-0.157 0.067-0.277 0.157-0.361 0.019-0.050 0.039-0.099 0.063-0.149 0.040-0.084 0.1-0.145 0.17-0.188 0.008-0.015 0.019-0.028 0.028-0.042 0.032-0.13 0.106-0.228 0.202-0.293 0.072-0.145 0.157-0.287 0.26-0.43 0.046-0.063 0.101-0.113 0.163-0.151 0.018-0.020 0.037-0.038 0.059-0.054 0.014-0.059 0.044-0.116 0.094-0.165 0.9-0.888 1.797-1.782 2.699-2.672 1.244-1.231 2.476-2.475 3.714-3.717l1.843-1.871 1.832-1.885c0.655-0.681 0.669-1.793-0.044-2.48-0.652-0.631-1.693-0.624-2.385-0.038l-1.964 1.66-1.995 1.71c-1.32 1.149-2.648 2.293-3.962 3.45s-2.636 2.308-3.943 3.474c-1.311 1.159-3.284 2.806-4.106 3.689s-0.792 2.492 0.191 3.369z"></path>
                            </svg>
                        </div>
                        <div class="mobile">
                            <button @click.stop="menu.setMenu({ parent: 'mb-p-select' })" class="flex gap-2 items-center px-2 py-[6px] border border-solid border-[var(--normalBorder)] hover:border-[var(--hoverBorder)] transition">
                                <svg class="side-app-icon" version="1.1" xmlns="http://www.w3.org/2000/svg" style="height: 15px;min-width:14px;overflow: visible;" viewBox="0 0 45 32">
                                    <path d="M32.084 18.983c0.433 0.409 0.938 0.722 1.492 0.95 0.433 0.18 1.191 0.265 1.732 0.265 0.553 0 1.191-0.096 1.72-0.277 0.553-0.192 1.059-0.529 1.492-0.938s0.782-0.914 1.023-1.456c0.241-0.553 0.373-1.155 0.373-1.756 0.024-1.203-0.457-2.43-1.323-3.284-0.433-0.433-0.938-0.782-1.504-1.011-0.565-0.241-1.179-0.361-1.78-0.361s-1.215 0.12-1.78 0.361c-0.565 0.241-1.083 0.577-1.504 1.011-0.854 0.866-1.347 2.081-1.323 3.284-0.012 1.203 0.517 2.394 1.383 3.212zM33.829 14.28c0.397-0.385 0.926-0.601 1.48-0.601s1.083 0.217 1.48 0.601c0.397 0.385 0.638 0.914 0.65 1.48s-0.18 1.131-0.577 1.552c-0.385 0.433-1.071 0.686-1.564 0.698-0.481 0.012-1.155-0.289-1.54-0.71-0.192-0.217-0.349-0.457-0.445-0.722s-0.144-0.541-0.132-0.83c0-0.553 0.253-1.083 0.65-1.468zM35.308 23.471c1.107 0 2.262 0.289 3.164 0.902 0.926 0.626 1.78 1.684 2.202 2.37 0.325 0.517 0.866 0.77 1.395 0.553 0.517-0.204 0.686-0.83 0.553-1.371-0.12-0.541-0.577-1.395-1.059-2.045-0.505-0.662-1.167-1.323-2.25-1.949-1.215-0.71-2.454-1.035-4.018-1.035s-2.803 0.325-4.018 1.035c-1.083 0.626-1.744 1.287-2.25 1.949-0.493 0.65-0.938 1.504-1.059 2.045s0.048 1.167 0.553 1.371c0.541 0.217 1.083-0.036 1.395-0.553 0.421-0.686 1.275-1.756 2.201-2.37s2.081-0.902 3.188-0.902z"></path>
                                    <path d="M43.898 29.931c-1.768-0.18-3.537-0.253-5.305-0.301-0.89-0.024-1.78-0.024-2.671-0.036l-13.353-0.12-10.683-0.048h-8.782c-0.337 0-0.602-0.265-0.602-0.602 0.012-1.889-0.096-7.122-0.144-10.298-0.060-3.188-0.144-7.916-0.204-10.43-0.072-2.514-0.084-5.029-0.325-7.531-0.024-0.301-0.277-0.541-0.589-0.553-0.325-0.012-0.602 0.253-0.638 0.577-0.265 2.49-0.265 4.992-0.337 7.507-0.060 2.514-0.204 8.469-0.241 11.116-0.036 2.635-0.024 8.974 0.060 11.489 0 0-0.096-3.176 0 0.036 0.024 0.686 0.577 1.215 1.263 1.227 1.78 0.012 3.429 0.012 5.209 0.012h5.341l10.683-0.048 13.353-0.12c0.89-0.012 1.78-0.012 2.671-0.036 1.78-0.048 3.561-0.12 5.341-0.301 0.373-0.036 0.662-0.337 0.686-0.722 0-0.433-0.325-0.77-0.734-0.818z"></path>
                                    <path d="M6.953 20.547l0.926-1.095 0.938-1.119c0.614-0.758 1.239-1.504 1.853-2.262s1.227-1.504 1.829-2.274c0.313-0.385 0.626-0.782 0.926-1.167 0.072-0.096 0.217-0.096 0.301-0.012l4.439 4.451c0.445 0.421 1.155 0.409 1.588-0.036l0.048-0.060c1.023-1.083 2.033-2.201 3.020-3.308 0.986-1.119 1.961-2.25 2.923-3.392 0.77-0.914 2.37-2.875 3.056-3.717 0.096-0.108 0.277-0.036 0.265 0.108-0.060 0.517-0.144 1.299-0.18 1.72-0.060 0.662-0.156 1.323-0.060 2.021 0.024 0.168 0.144 0.313 0.325 0.361 0.204 0.048 0.397-0.072 0.493-0.253 0.301-0.614 0.421-1.263 0.577-1.901 0.156-0.65 0.301-1.299 0.445-1.949s0.289-1.299 0.421-1.961c0.072-0.325 0.313-1.227 0.385-1.997s-0.361-1.167-0.95-1.059c-0.589 0.108-1.54 0.505-2.165 0.734l-1.877 0.722-1.865 0.734c-0.614 0.265-1.516 0.65-1.72 0.794s-0.313 0.385-0.253 0.589c0.060 0.205 0.217 0.385 0.577 0.349 0.361-0.048 1.191-0.241 1.829-0.409l1.72-0.469c0.144-0.036 0.253 0.132 0.156 0.241-0.734 0.818-2.406 2.719-3.188 3.633-0.962 1.143-1.925 2.286-2.863 3.441-0.614 0.746-1.215 1.504-1.817 2.262-0.12 0.156-0.349 0.168-0.493 0.036l-4.391-4.211c-0.048-0.036-0.084-0.072-0.144-0.108-0.469-0.313-1.095-0.18-1.432 0.277-0.577 0.77-1.143 1.54-1.708 2.322-0.577 0.782-1.143 1.576-1.708 2.358-0.565 0.794-1.131 1.588-1.684 2.382l-0.83 1.203-0.818 1.203c-0.217 0.313-0.144 0.758 0.205 0.974 0.277 0.18 0.674 0.108 0.902-0.156z"></path>
                                </svg>
                                <svg version="1.1" class="rotate-[270deg] fill-[var(--primary-color)]" width="12" height="12" viewBox="0 0 20 32" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M0.775 17.789c1.305 1.166 2.612 2.332 3.927 3.486 1.311 1.161 2.634 2.308 3.953 3.46 1.316 1.156 2.646 2.296 3.973 3.439 1.33 1.139 2.667 2.273 4.015 3.394 0.662 0.551 1.647 0.52 2.272-0.107 0.65-0.654 0.619-1.725-0.020-2.393-1.198-1.253-2.407-2.495-3.621-3.729-1.232-1.245-2.462-2.492-3.704-3.725-0.902-0.9-1.803-1.802-2.707-2.699-0.033-0.032-0.055-0.069-0.072-0.106-0.045-0.036-0.082-0.080-0.111-0.129-0.069-0.047-0.129-0.117-0.176-0.216-0.021-0.047-0.044-0.092-0.066-0.136-0.12-0.062-0.214-0.168-0.246-0.325-0.001-0.005-0.002-0.009-0.003-0.014-0.104-0.157-0.187-0.327-0.254-0.505-0.109-0.185-0.182-0.388-0.226-0.601-0.002-0.012-0.005-0.024-0.007-0.036-0.016-0.085-0.028-0.172-0.036-0.259-0.195-0.593-0.26-1.183 0.030-1.653 0.006-0.157 0.067-0.277 0.157-0.361 0.019-0.050 0.039-0.099 0.063-0.149 0.040-0.084 0.1-0.145 0.17-0.188 0.008-0.015 0.019-0.028 0.028-0.042 0.032-0.13 0.106-0.228 0.202-0.293 0.072-0.145 0.157-0.287 0.26-0.43 0.046-0.063 0.101-0.113 0.163-0.151 0.018-0.020 0.037-0.038 0.059-0.054 0.014-0.059 0.044-0.116 0.094-0.165 0.9-0.888 1.797-1.782 2.699-2.672 1.244-1.231 2.476-2.475 3.714-3.717l1.843-1.871 1.832-1.885c0.655-0.681 0.669-1.793-0.044-2.48-0.652-0.631-1.693-0.624-2.385-0.038l-1.964 1.66-1.995 1.71c-1.32 1.149-2.648 2.293-3.962 3.45s-2.636 2.308-3.943 3.474c-1.311 1.159-3.284 2.806-4.106 3.689s-0.792 2.492 0.191 3.369z"></path>
                                </svg>
                            </button>
                        </div>
                        <div class="mobile projectModalSideMenu" id="mb-p-select"
                            :style="{ opacity: responsive.mobile && loader ? '0' : '1' }"
                            v-if="(menu.parent == 'mb-p-select' || !responsive.mobile)">
                            <div class="sub-tab-container sticky top-0 z-[5] bg-[var(--background-color)]">
                                <button @click="leftTab = 'project'"
                                    :class="['sub-tab-item !bg-inherit', { 'selected-sub-tab': leftTab == 'project' }]">プロジェクト別</button>
                                <button @click="leftTab = 'manager'"
                                    :class="['sub-tab-item !bg-inherit', { 'selected-sub-tab': leftTab == 'manager' }]">管理者別</button>
                            </div>
                            <div v-if="leftTab == 'project'" class="project-selector-left">
                                <div class="mobile-filter-panel">
                                    <div class="mobile-filter-panel__header">
                                        <input
                                            v-model="mobileProjectKeywords"
                                            type="text"
                                            placeholder="プロジェクト検索"
                                            class="border border-solid border-[var(--formBorder)] px-3 py-2 text-[13px] focus:border-[var(--primary-color)]"
                                        />
                                        <div class="flex items-center justify-between gap-3">
                                            <span class="text-[12px] text-[gray]">{{ selectedProjects.length }}件選択中</span>
                                            <CommandButton :buttons="mobileProjectFilterButtons"/>
                                        </div>
                                    </div>
                                    <div v-if="filteredMobileProjects.length" class="mobile-filter-panel__list">
                                        <label
                                            v-for="project in filteredMobileProjects"
                                            :key="project.id"
                                            :title="project.name"
                                            class="mobile-filter-panel__item"
                                        >
                                            <input type="checkbox" name="project-selector" :value="project.id" v-model="selectedProjects">
                                            <span class="text-[13px] overflow-hidden whitespace-nowrap text-ellipsis">{{ project.name }}</span>
                                        </label>
                                    </div>
                                    <div v-else class="mobile-filter-panel__empty">
                                        検索結果はありません。
                                    </div>
                                </div>
                            </div>
                            <div v-if="leftTab == 'manager'" class="project-selector-left">
                                <div class="mobile-filter-panel">
                                    <div class="mobile-filter-panel__header">
                                        <input
                                            v-model="mobileManagerKeywords"
                                            type="text"
                                            placeholder="PM検索"
                                            class="border border-solid border-[var(--formBorder)] px-3 py-2 text-[13px] focus:border-[var(--primary-color)]"
                                        />
                                        <div class="flex items-center justify-between gap-3">
                                            <span class="text-[12px] text-[gray]">{{ selectedManagers.length }}件選択中</span>
                                            <CommandButton :buttons="mobileManagerFilterButtons"/>
                                        </div>
                                    </div>
                                    <div v-if="filteredMobileManagers.length" class="mobile-filter-panel__list">
                                        <label v-for="manager in filteredMobileManagers" :key="manager.id" class="mobile-filter-panel__item">
                                            <input type="checkbox" name="project-selector-by-manager" v-model="selectedManagers" :value="manager.id">
                                            <span class="text-[13px] overflow-hidden whitespace-nowrap text-ellipsis">{{ manager.name }}</span>
                                        </label>
                                    </div>
                                    <div v-else class="mobile-filter-panel__empty">
                                        検索結果はありません。
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                    </div>
                    <div class="flex gap-5">
                        <button
                            type="button"
                            class="px-2 py-1 border border-solid border-[var(--normalBorder)] hover:border-[var(--hoverBorder)] transition"
                            @click="includeForecastSettlement = !includeForecastSettlement"
                        >
                            着地予測 {{ includeForecastSettlement ? 'ON' : 'OFF' }}
                        </button>
                        <button
                            type="button"
                            class="mr-5 px-2 py-1 border border-solid border-[var(--normalBorder)] hover:border-[var(--hoverBorder)] transition"
                            @click="toggleYearlyComparison"
                        >
                            {{ totalGrouping === 'fiscal' ? '月次表示' : '年度比較' }}
                        </button>
                    </div>
                     
                </div>
                
               
            </div>
            <div class="projectModalContainer relative">
                
                <div class="projectModalContent relative" style="overflow: hidden;">
                    <div class="cal-month-loader" style="height: 100%; top: 0;opacity: 0.6;" v-if="loader">
                        <div id="loaderMini">
                            <div class="spinner-mini"
                                style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
                        </div>
                    </div>
                    <div class="h-full flex flex-col min-h-0">
                        <div
                            class="sticky top-0 bg-[var(--background-color)] z-[7] min-h-fit flex justify-between items-center px-[20px] flex-wrap after:flex-auto after:content-['']">
                            <div class="sub-tab-container pc">
                                <button @click="tab = 'table'"
                                    :class="['sub-tab-item', { 'selected-sub-tab': tab == 'table' }]">テーブル</button>
                                <button @click="tab = 'pie'"
                                    :class="['sub-tab-item', { 'selected-sub-tab': tab == 'pie' }]">円グラフ</button>
                                <button @click="tab = 'bar'"
                                    :class="['sub-tab-item', { 'selected-sub-tab': tab == 'bar' }]">棒グラフ</button>
                            </div>
                            <div v-if="tab === 'table'" class="flex items-center gap-2 text-sm pc">
                                <label class="text-xs opacity-70">並び替え</label>
                                <select v-model="sortMode" class="text-[var(--primary-color)] px-2 py-1 bg-[var(--background-color)] text-sm">
                                    <option value="name">プロジェクト名</option>
                                    <option value="manager">管理者</option>
                                </select>
                            </div>
                            <div class="flex items-center gap-[20px] py-3 relative w-full justify-end flex-wrap md:flex-nowrap">
                                <div v-if="tab === 'table' && totalGrouping === 'fiscal'" class="flex items-center gap-2 text-xs flex-wrap md:justify-end justify-center">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <label class="flex items-center gap-1">
                                            <span class="opacity-70">比較年度</span>
                                            <select v-model.number="selectedFiscalYearStart" class="text-[var(--primary-color)] px-2 py-1 bg-[var(--background-color)] text-sm">
                                                <option v-for="year in fiscalYearOptions" :key="`fy-start-${year}`" :value="year">
                                                    {{ year }}
                                                </option>
                                            </select>
                                        </label>
                                        <label class="flex items-center gap-1">
                                            <span class="opacity-70">対象年度</span>
                                            <select v-model.number="selectedFiscalYearEnd" class="text-[var(--primary-color)] px-2 py-1 bg-[var(--background-color)] text-sm">
                                                <option v-for="year in fiscalYearOptions" :key="`fy-end-${year}`" :value="year">
                                                    {{ year }}
                                                </option>
                                            </select>
                                        </label>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3 relative justify-center md:justify-end flex-wrap md:flex-nowrap" v-if="totalGrouping === 'range'">
                                    <div class="flex items-center">
                                        <span v-if="previousMonthCount" class="side-notification side-notification--comment-only" style="position: static">{{ previousMonthCount }}</span>
                                        <button @click="shiftRange(-1)" class="flex items-center justify-center h-[30px] w-fit gap-2 min-w-[30px]">
                                            <Back size="13"/>
                                        </button>
                                    </div>
                                    
                                    <PeriodRangePicker
                                        :start="periodStartIso"
                                        :end="periodEndIso"
                                        :max-months="MAX_RANGE_MONTHS"
                                        :total-badge="thisMonthCount"
                                        :period-badge="selectedBadge.period_counts"
                                        @change="handleRangeChange"
                                    />
                                    <div class="flex items-center">
                                        <button @click="shiftRange(1)" class="flex items-center justify-center h-[30px] w-fit gap-2 min-w-[30px]">
                                            <Back size="13" class="rotate-180"/>
                                        </button>
                                        <span v-if="nextMonthCount" class="side-notification side-notification--comment-only" style="position: static">{{ nextMonthCount }}</span>
                                    </div>
                                </div>
                                
                                
                            </div>
                            

                            </div>
                        <div v-if="!hasSelectedProjects" class="finance-empty-state">
                            <div class="finance-empty-state__inner">
                                <p class="finance-empty-state__title">プロジェクトが選択されていません</p>
                                <p class="finance-empty-state__text">フィルター、または「全選択」から表示するプロジェクトを選んでください。</p>
                                <div class="finance-empty-state__actions">
                                    <button type="button" class="finance-empty-state__button finance-empty-state__button--primary" @click="selectAllProjects">
                                        全選択
                                    </button>
                                    <button type="button" class="finance-empty-state__button" @click.stop="openProjectFilter">
                                        フィルターを開く
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div
                            v-else-if="tab == 'table' && responsive.mobile"
                            class="mobile-finance-list"
                        >
                            <section class="mobile-finance-hero">
                                <div class="mobile-finance-hero__switches">
                                    <button
                                        v-for="option in visibleScenarioOptions"
                                        :key="`mobile-hero-${option.value}`"
                                        type="button"
                                        class="mobile-finance-chip mobile-finance-chip--segmented"
                                        :class="{ 'mobile-finance-chip--active': mobileActiveScenario === option.value }"
                                        @click="mobileActiveScenario = option.value"
                                    >
                                        {{ option.label }}
                                    </button>
                                </div>

                                <template v-if="totalGrouping === 'fiscal'">
                                    <article class="mobile-finance-compare-card">
                                        <div class="mobile-finance-compare-card__caption">
                                            {{ scenarioLabel(mobileActiveScenario) }}
                                        </div>

                                        <div class="mobile-finance-compare-table">
                                            <div class="mobile-finance-compare-table__head"></div>
                                            <div class="mobile-finance-compare-table__head">比較年度<br>{{ selectedFiscalYearStart }}</div>
                                            <div class="mobile-finance-compare-table__head">対象年度<br>{{ selectedFiscalYearEnd }}</div>
                                            <div class="mobile-finance-compare-table__head"></div>

                                            <template v-for="metric in mobileMetricItems" :key="`mobile-summary-compare-${metric.key}`">
                                                <div class="mobile-finance-compare-table__label">{{ metric.label }}</div>
                                                <div class="mobile-finance-compare-table__value">
                                                    {{ formatMobileMetric(fiscalSummaryEntry(mobileActiveScenario, selectedFiscalYearStart), mobileActiveScenario, metric.key) }}
                                                </div>
                                                <div class="mobile-finance-compare-table__value">
                                                    {{ formatMobileMetric(fiscalSummaryEntry(mobileActiveScenario, selectedFiscalYearEnd), mobileActiveScenario, metric.key) }}
                                                </div>
                                                <div
                                                    class="mobile-finance-compare-table__delta"
                                                    :class="comparisonDeltaClass(
                                                        fiscalSummaryEntry(mobileActiveScenario, selectedFiscalYearStart),
                                                        fiscalSummaryEntry(mobileActiveScenario, selectedFiscalYearEnd),
                                                        mobileActiveScenario,
                                                        metric.key
                                                    )"
                                                >
                                                    {{ comparisonDeltaDisplay(
                                                        fiscalSummaryEntry(mobileActiveScenario, selectedFiscalYearStart),
                                                        fiscalSummaryEntry(mobileActiveScenario, selectedFiscalYearEnd),
                                                        mobileActiveScenario,
                                                        metric.key
                                                    ) }}
                                                </div>
                                            </template>
                                        </div>
                                    </article>
                                </template>

                                <template v-else>
                                    <div class="mobile-finance-card">
                                    <div class="mobile-finance-card__summary">
                                        <div class="finance-top-summary__header">
                                            <div>
                                                <p class="finance-top-summary__title">集計</p>
                                                <p class="finance-top-summary__caption">{{ mobileSummaryCaption }}</p>
                                            </div>
                                        </div>
                                        <div class="finance-top-summary__kpis">
                                            <div v-for="metric in mobileMetricItems" :key="`top-summary-${metric.key}`" class="mobile-finance-kpi">
                                                <span class="mobile-finance-kpi__label">{{ metric.label }}</span>
                                                <strong class="mobile-finance-kpi__value">
                                                    {{ formatMobileMetric(mobileSummaryScenarioEntry(mobileActiveScenario), mobileActiveScenario, metric.key) }}
                                                </strong>
                                            </div>
                                        </div>
                                        <div v-if="showTopSummaryBuckets" class="finance-top-summary__periods">
                                            <div class="finance-top-summary__periods-header">
                                                <h4>{{ topSummaryBucketsTitle }}</h4>
                                                <span class="finance-top-summary__caption">{{ mobileDetailCaption }}</span>
                                            </div>
                                            <div class="finance-top-summary__period-scroll">
                                                <article v-for="bucket in topSummaryBuckets" :key="bucket.key" class="finance-top-summary__period-card">
                                                    <p class="finance-top-summary__period-title">{{ bucket.label }}</p>
                                                    <div class="finance-top-summary__period-grid">
                                                        <div v-for="metric in mobileMetricItems" :key="`${bucket.key}-${metric.key}`" class="mobile-finance-detail-metric">
                                                            <span>{{ metric.label }}</span>
                                                            <strong>{{ formatMobileMetric(bucket.entry, mobileActiveScenario, metric.key) }}</strong>
                                                        </div>
                                                    </div>
                                                </article>
                                            </div>
                                        </div>
                                    </div>
                                    </div>
                                </template>
                            </section>

                            <div class="mobile-finance-section-title">部門別</div>

                            <template v-if="totalGrouping === 'fiscal'">
                                <article
                                    v-for="proj in sortedProjects"
                                    :key="`mobile-fiscal-card-${proj.name}`"
                                    class="mobile-finance-compare-project"
                                >
                                    <div class="mobile-finance-compare-project__header">
                                        <div>
                                            <p class="mobile-finance-card__title">{{ proj.name }}</p>
                                            <p v-if="managerNameFor(proj.name)" class="mobile-finance-card__caption">
                                                PM: {{ managerNameFor(proj.name) }}
                                            </p>
                                        </div>
                                        <div class="mobile-finance-card__badges">
                                            <span v-if="showAnyArrow(proj.name)" class="mobile-finance-alert">Alert</span>
                                            <button
                                                v-if="showComment"
                                                type="button"
                                                class="mobile-finance-comment"
                                                @click.stop="selectProjectComment(proj.name)"
                                            >
                                                <span>コメント</span>
                                                <strong>{{ commentCount[proj.name] ?? 0 }}</strong>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="mobile-finance-compare-project__grid">
                                        <div class="mobile-finance-compare-table mobile-finance-compare-table--project">
                                            <div class="mobile-finance-compare-table__head"></div>
                                            <div class="mobile-finance-compare-table__head">比較年度<br>{{ selectedFiscalYearStart }}</div>
                                            <div class="mobile-finance-compare-table__head">対象年度<br>{{ selectedFiscalYearEnd }}</div>

                                            <template v-for="metric in mobileMetricItems" :key="`mobile-project-compare-${proj.name}-${metric.key}`">
                                                <div class="mobile-finance-compare-table__label">{{ metric.label }}</div>
                                                <div class="mobile-finance-compare-table__value">
                                                    {{ formatMobileMetric(fiscalTotalEntry(proj.name, mobileActiveScenario, selectedFiscalYearStart), mobileActiveScenario, metric.key) }}
                                                </div>
                                                <div class="mobile-finance-compare-table__value">
                                                    {{ formatMobileMetric(fiscalTotalEntry(proj.name, mobileActiveScenario, selectedFiscalYearEnd), mobileActiveScenario, metric.key) }}
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </article>
                            </template>

                            <template v-else>
                                <details
                                    v-for="proj in sortedProjects"
                                    :key="`mobile-card-${proj.name}`"
                                    class="mobile-finance-card"
                                >
                                    <summary class="mobile-finance-card__summary">
                                        <div class="mobile-finance-card__header">
                                            <div>
                                                <p class="mobile-finance-card__title">{{ proj.name }}</p>
                                                <p v-if="managerNameFor(proj.name)" class="mobile-finance-card__caption">
                                                    PM: {{ managerNameFor(proj.name) }}
                                                </p>
                                            </div>
                                            <div class="mobile-finance-card__badges">
                                                <span v-if="showAnyArrow(proj.name)" class="mobile-finance-alert">Alert</span>
                                                <button
                                                    v-if="showComment"
                                                    type="button"
                                                    class="mobile-finance-comment"
                                                    @click.stop="selectProjectComment(proj.name)"
                                                >
                                                    <span>コメント</span>
                                                    <strong>{{ commentCount[proj.name] ?? 0 }}</strong>
                                                    <span
                                                        v-if="financeTotalBadge(proj.name)?.[periodStartIso]"
                                                        class="side-notification"
                                                        style="position: unset; background-color: #F28C28; z-index: inherit;"
                                                    >
                                                        {{ financeTotalBadge(proj.name)?.[periodStartIso] }}
                                                    </span>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="mobile-finance-card__meta">
                                            <span>{{ mobileSummaryCaption }}</span>
                                            <span>{{ scenarioLabel(mobileActiveScenario) }}</span>
                                        </div>
                                        <div class="mobile-finance-kpis">
                                            <div v-for="metric in mobileMetricItems" :key="`${proj.name}-${mobileActiveScenario}-${metric.key}`" class="mobile-finance-kpi">
                                                <span class="mobile-finance-kpi__label">{{ metric.label }}</span>
                                                <strong class="mobile-finance-kpi__value">
                                                    {{ formatMobileMetric(mobileProjectScenarioEntry(proj.name, mobileActiveScenario), mobileActiveScenario, metric.key) }}
                                                </strong>
                                            </div>
                                        </div>
                                    </summary>

                                    <div class="mobile-finance-card__body">
                                        <section
                                            v-for="option in visibleScenarioOptions"
                                            :key="`${proj.name}-${option.value}`"
                                            class="mobile-finance-section"
                                        >
                                            <div class="mobile-finance-section__header">
                                                <h4>{{ option.label }}</h4>
                                                <span class="mobile-finance-section__caption">{{ mobileDetailCaption }}</span>
                                            </div>

                                            <div class="finance-top-summary__period-scroll">
                                                <div
                                                    v-for="period in periods"
                                                    :key="`${proj.name}-${option.value}-${period.period}`"
                                                    class="finance-top-summary__period-card"
                                                >
                                                    <div class="mobile-finance-detail-row__label">{{ formatPeriodLabel(period) }}</div>
                                                    <div class="mobile-finance-detail-row__metrics">
                                                        <div v-for="metric in mobileMetricItems" :key="`${proj.name}-${option.value}-${period.period}-${metric.key}`" class="mobile-finance-detail-metric">
                                                            <span>{{ metric.label }}</span>
                                                            <strong>{{ formatMobileMetric(scenarioPeriodEntry(proj, period.period, option.value), option.value, metric.key) }}</strong>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </section>
                                    </div>
                                </details>
                            </template>
                        </div>
                        <div class="finance-table-scroll" v-if="tab == 'table' && !responsive.mobile">
                            <table>
                                <thead>
                                    <tr>
                                        <th :rowspan="2" class="sticky-left first-col top-border">
                                            <div class="relative">
                                                <div class="cursor-pointer flex items-center gap-[5px]" @click.stop="menu.setMenu({parent: 'projectFilter'})">
                                                    プロジェクト名
                                                    <Filter style="fill: var(--primary-color);" size="12"/>
                                                </div>
                                                <Transition name="slidePop">
                                                    <FilterById 
                                                        v-if="menu.parent == 'projectFilter'"
                                                        id="projectFilter"
                                                        :options="projects" 
                                                        :include-select-all="true"
                                                        :searchable="true"
                                                        v-model:selected="selectedProjects"
                                                        custom-place-holder="プロジェクト検索"
                                                        style="top: 25px;"
                                                    />
                                                </Transition>
                                            </div>
                                        </th>
                                        <th :rowspan="2" class="sticky-left second-col top-border">
                                            <div class="relative">
                                                <div class="cursor-pointer flex items-center gap-[5px]" @click.stop="menu.setMenu({parent: 'managerFilter'})">
                                                    PM名
                                                    <Filter style="fill: var(--primary-color);" size="12"/>
                                                </div>
                                                <Transition name="slidePop">
                                                    <FilterById 
                                                        v-if="menu.parent == 'managerFilter'"
                                                        id="managerFilter"
                                                        :options="managers" 
                                                        :include-select-all="true"
                                                        :searchable="true"
                                                        v-model:selected="selectedManagers"
                                                        custom-place-holder="PM検索"
                                                        style="top: 25px; right: auto;"
                                                    />
                                                </Transition>
                                            </div> 
                                        </th>
                                        <th :rowspan="2" class="sticky-left third-col top-border">
                                            <div class="relative">
                                                <div class="cursor-pointer flex items-center gap-[5px]" @click.stop="menu.setMenu({parent: 'scenarioFilter'})">
                                                    区分
                                                    <Filter style="fill: var(--primary-color);" size="12"/>
                                                </div>
                                                <Transition name="slidePop">
                                                    <div 
                                                        v-if="menu.parent == 'scenarioFilter'"
                                                        id="scenarioFilter"
                                                        class="pc 
                                                        shadow-me 
                                                        absolute  
                                                        bg-[var(--bg3)] 
                                                        text-[var(--primary-color)] 
                                                        gap-[10px] 
                                                        text-[13px] 
                                                        pb-[10px]
                                                        px-[10px]
                                                        top-[25px] 
                                                        max-h-[50vh] 
                                                        overflow-auto
                                                        flex
                                                        flex-col"
                                                    >         
                                                        <div class="pt-[10px]">
                                                            <CommandButton :buttons="[{title: 'リセット', action: () => {selectedOption = []; menu.close()}}]"/>
                                                        </div>
                                                        <div class="flex flex-col gap-[10px]" v-if="scenarioOptions.length">
                                                            <div v-for="option in scenarioOptions">
                                                                <label class="cursor-pointer select-none whitespace-nowrap flex items-center gap-[5px]">
                                                                    <input type="checkbox" class="custom-f-checkbox rounded-[3px]" name="class-selector"  v-model="selectedOption" :value="option.value"/>
                                                                    {{ option.label }}
                                                                </label>
                                                            </div>
                                                        </div>                   
                                                    </div>
                                                </Transition>
                                            </div>  
                                        </th>
                                        <th
                                            v-if="totalGrouping !== 'fiscal'"
                                            v-for="(p, i) in periods"
                                            :key="p.period"
                                            colspan="4"
                                             :class="[
                                                'border-r border-[var(--calendarBorder)]',
                                                '[border-right-style:solid]',
                                                'top-border'
                                            ]"
                                        >
                                            <div :id="p.period" class="flex justify-center">
                                                <span>{{ p.year }}年{{ p.month }}月</span>
                                            </div>
                                        </th>
                                        <template v-if="totalGrouping === 'fiscal'">
                                            <th
                                                v-for="(fy, i) in activeFiscalYears"
                                                :key="`fy-head-${fy}`"
                                                colspan="4"
                                                :data-cell="i === activeFiscalYears.length - 1 ? 'right-border' : null"
                                                class="totals-head top-border !text-center"
                                            >
                                                {{ fy }}
                                            </th>
                                        </template>
                                        <th v-else-if="showTotals" colspan="4" class="totals-head top-border !text-center" data-cell="right-border">集計</th>
                                        <th v-if="showComment" :rowspan="2" class="sticky-right comment-cell top-border">コメント</th>
                                    </tr>
                                    <tr>
                                        <template v-for="p in periods" :key="p.period" v-if="totalGrouping !== 'fiscal'">
                                            
                                            <th>売上</th>
                                            <th>販管費</th>
                                            <th>利益</th>
                                            <th>利益率</th>
                                        </template>
                                        <template v-if="totalGrouping === 'fiscal'">
                                            <template v-for="fy in activeFiscalYears" :key="`fy-head-values-${fy}`">
                                                <th class="totals-head">売上</th>
                                                <th class="totals-head">販管費</th>
                                                <th class="totals-head">利益</th>
                                                <th class="totals-head">利益率</th>
                                            </template>
                                        </template>
                                        <template v-else-if="showTotals">
                                            <th class="totals-head">売上</th>
                                            <th class="totals-head">販管費</th>
                                            <th class="totals-head">利益</th>
                                            <th class="totals-head">利益率</th>
                                        </template>
                                    </tr>
                                </thead>
                                <tbody v-if="hasPeriodTotals">
                                    <tr v-if="show('yearly_plan')" class="summary-row">
                                        <td
                                            v-if="firstVisibleScenario === 'yearly_plan'"
                                            class="p-name sticky-left first-col"
                                            :rowspan="visibleScenarioCount"
                                        >集計</td>
                                        <td class="m-name sticky-left second-col" v-if="firstVisibleScenario === 'yearly_plan'" 
                                            :rowspan="visibleScenarioCount"
                                        >—</td>
                                        <td class="sub-name sticky-left third-col">
                                            <span>年度予算</span>
                                        </td>
                                        <template v-for="p in periods" :key="`summary-yearly-${p.period}`" v-if="totalGrouping !== 'fiscal'">
                                            <td>
                                                <div class="inner-col"><span class="mobile">売上</span>{{
                                                    amountOfMoneyParser(periodEntry(p.period, 'yearly_plan').sales)
                                                }}</div>
                                            </td>
                                            <td>
                                                <div class="inner-col"><span class="mobile">販管費</span>{{
                                                    amountOfMoneyParser(periodEntry(p.period, 'yearly_plan').expense)
                                                }}</div>
                                            </td>
                                            <td>
                                                <div class="inner-col"><span class="mobile">利益</span>{{
                                                    amountOfMoneyParser(periodEntry(p.period, 'yearly_plan').sales - periodEntry(p.period, 'yearly_plan').expense)
                                                }}</div>
                                            </td>
                                            <td data-cell="right-border">
                                                <div class="inner-col"><span class="mobile">利益率</span>{{
                                                    percentizer(periodEntry(p.period, 'yearly_plan')).display
                                                }}</div>
                                            </td>
                                        </template>
                                        <template v-if="totalGrouping === 'fiscal'">
                                            <template v-for="fy in activeFiscalYears" :key="`summary-total-yearly-${fy}`">
                                                <td>
                                                    <div class="inner-col"><span class="mobile">売上</span>{{
                                                        amountOfMoneyParser(fiscalSummaryEntry('yearly_plan', fy).sales)
                                                    }}</div>
                                                </td>
                                                <td>
                                                    <div class="inner-col"><span class="mobile">販管費</span>{{
                                                        amountOfMoneyParser(fiscalSummaryEntry('yearly_plan', fy).expense)
                                                    }}</div>
                                                </td>
                                                <td>
                                                    <div class="inner-col"><span class="mobile">利益</span>{{
                                                        amountOfMoneyParser(fiscalSummaryEntry('yearly_plan', fy).profit)
                                                    }}</div>
                                                </td>
                                                <td data-cell="right-border">
                                                    <div class="inner-col"><span class="mobile">利益率</span>{{
                                                        percentizer(fiscalSummaryEntry('yearly_plan', fy)).display
                                                    }}</div>
                                                </td>
                                            </template>
                                        </template>
                                        <template v-else-if="showTotals">
                                            <td>
                                                <div class="inner-col"><span class="mobile">売上</span>{{
                                                    amountOfMoneyParser(totalSummaryEntry('yearly_plan').sales)
                                                }}</div>
                                            </td>
                                            <td>
                                                <div class="inner-col"><span class="mobile">販管費</span>{{
                                                    amountOfMoneyParser(totalSummaryEntry('yearly_plan').expense)
                                                }}</div>
                                            </td>
                                            <td>
                                                <div class="inner-col"><span class="mobile">利益</span>{{
                                                    amountOfMoneyParser(totalSummaryEntry('yearly_plan').profit)
                                                }}</div>
                                            </td>
                                            <td>
                                                <div class="inner-col"><span class="mobile">利益率</span>{{
                                                    percentizer(totalSummaryEntry('yearly_plan')).display
                                                }}</div>
                                            </td>
                                        </template>

                                        <td v-if="showComment" class="sticky-right comment-cell"></td>
                                    </tr>
                                    <tr v-if="show('profit')" class="summary-row">
                                        <td
                                            v-if="firstVisibleScenario === 'profit'"
                                            class="p-name sticky-left first-col"
                                            :rowspan="visibleScenarioCount"
                                        >集計</td>
                                        <td class="m-name sticky-left second-col" v-if="firstVisibleScenario === 'profit'" 
                                            :rowspan="visibleScenarioCount"
                                        >—</td>
                                        <td class="sub-name sticky-left third-col">
                                            <span>損益計画</span>
                                        </td>
                                        <template v-for="p in periods" :key="`summary-profit-${p.period}`" v-if="totalGrouping !== 'fiscal'">
                                            <td>
                                                <div class="flex items-center gap-[5px]">
                                                    <div class="inner-col"><span class="mobile">売上</span>{{
                                                        amountOfMoneyParser(periodEntry(p.period, 'profit').sales)
                                                    }}</div>
                                                    <DeltaNumbers v-if="deltaShown" type="sales"
                                                        :planned="periodEntry(p.period, 'yearly_plan').sales"
                                                        :actual="periodEntry(p.period, 'profit').sales" />
                                                </div>
                                            </td>
                                            <td>
                                                <div class="flex items-center gap-[5px]">
                                                    <div class="inner-col"><span class="mobile">販管費</span>{{
                                                        amountOfMoneyParser(periodEntry(p.period, 'profit').expense)
                                                    }}</div>
                                                    <DeltaNumbers v-if="deltaShown" type="expense"
                                                        :planned="periodEntry(p.period, 'yearly_plan').expense"
                                                        :actual="periodEntry(p.period, 'profit').expense" />
                                                </div>
                                            </td>
                                            <td>
                                                <div class="flex items-center gap-[5px]">
                                                    <div class="inner-col"><span class="mobile">利益</span>{{
                                                        amountOfMoneyParser(periodEntry(p.period, 'profit').profit)
                                                    }}</div>
                                                    <DeltaNumbers v-if="deltaShown" type="profit"
                                                        :planned="periodEntry(p.period, 'yearly_plan').sales - periodEntry(p.period, 'yearly_plan').expense"
                                                        :actual="periodEntry(p.period, 'profit').profit" />
                                                </div>
                                            </td>
                                            <td data-cell="right-border">
                                                <div class="flex items-center gap-[5px]">
                                                    <div class="inner-col"><span class="mobile">利益率</span>{{
                                                        percentizer(periodEntry(p.period, 'profit')).display
                                                    }}</div>
                                                    <DeltaNumbers v-if="deltaShown" type="profit_rate"
                                                        :planned="percentizer(periodEntry(p.period, 'yearly_plan')).value"
                                                        :actual="percentizer(periodEntry(p.period, 'profit')).value" />
                                                </div>
                                            </td>
                                        </template>
                                        <template v-if="totalGrouping === 'fiscal'">
                                            <template v-for="fy in activeFiscalYears" :key="`summary-total-profit-${fy}`">
                                                <td>
                                                    <div class="flex items-center gap-[5px]">
                                                        <div class="inner-col"><span class="mobile">売上</span>{{
                                                            amountOfMoneyParser(fiscalSummaryEntry('profit', fy).sales)
                                                        }}</div>
                                                        <DeltaNumbers
                                                            v-if="deltaShown"
                                                            type="sales"
                                                            :planned="fiscalSummaryEntry('yearly_plan', fy).sales"
                                                            :actual="fiscalSummaryEntry('profit', fy).sales"
                                                        />
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="flex items-center gap-[5px]">
                                                        <div class="inner-col"><span class="mobile">販管費</span>{{
                                                            amountOfMoneyParser(fiscalSummaryEntry('profit', fy).expense)
                                                        }}</div>
                                                        <DeltaNumbers
                                                            v-if="deltaShown"
                                                            type="expense"
                                                            :planned="fiscalSummaryEntry('yearly_plan', fy).expense"
                                                            :actual="fiscalSummaryEntry('profit', fy).expense"
                                                        />
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="flex items-center gap-[5px]">
                                                        <div class="inner-col"><span class="mobile">利益</span>{{
                                                            amountOfMoneyParser(fiscalSummaryEntry('profit', fy).profit)
                                                        }}</div>
                                                        <DeltaNumbers
                                                            v-if="deltaShown"
                                                            type="profit"
                                                            :planned="fiscalSummaryEntry('yearly_plan', fy).profit"
                                                            :actual="fiscalSummaryEntry('profit', fy).profit"
                                                        />
                                                    </div>
                                                </td>
                                                <td data-cell="right-border">
                                                    <div class="flex items-center gap-[5px]">
                                                        <div class="inner-col"><span class="mobile">利益率</span>{{
                                                            percentizer(fiscalSummaryEntry('profit', fy)).display
                                                        }}</div>
                                                        <DeltaNumbers
                                                            v-if="deltaShown"
                                                            type="profit_rate"
                                                            :planned="percentizer(fiscalSummaryEntry('yearly_plan', fy)).value"
                                                            :actual="percentizer(fiscalSummaryEntry('profit', fy)).value"
                                                        />
                                                    </div>
                                                </td>
                                            </template>
                                        </template>
                                        <template v-else-if="showTotals">
                                            <td>
                                                <div class="flex items-center gap-[5px]">
                                                    <div class="inner-col"><span class="mobile">売上</span>{{
                                                        amountOfMoneyParser(totalSummaryEntry('profit').sales)
                                                    }}</div>
                                                    <DeltaNumbers v-if="deltaShown" type="sales"
                                                        :planned="totalSummaryEntry('yearly_plan').sales"
                                                        :actual="totalSummaryEntry('profit').sales" />
                                                </div>
                                            </td>
                                            <td>
                                                <div class="flex items-center gap-[5px]">
                                                    <div class="inner-col"><span class="mobile">販管費</span>{{
                                                        amountOfMoneyParser(totalSummaryEntry('profit').expense)
                                                    }}</div>
                                                    <DeltaNumbers v-if="deltaShown" type="expense"
                                                        :planned="totalSummaryEntry('yearly_plan').expense"
                                                        :actual="totalSummaryEntry('profit').expense" />
                                                </div>
                                            </td>
                                            <td>
                                                <div class="flex items-center gap-[5px]">
                                                    <div class="inner-col"><span class="mobile">利益</span>{{
                                                        amountOfMoneyParser(totalSummaryEntry('profit').profit)
                                                    }}</div>
                                                    <DeltaNumbers v-if="deltaShown" type="profit"
                                                        :planned="totalSummaryEntry('yearly_plan').sales - totalSummaryEntry('yearly_plan').expense"
                                                        :actual="totalSummaryEntry('profit').profit" />
                                                </div>
                                            </td>
                                            <td>
                                                <div class="flex items-center gap-[5px]">
                                                    <div class="inner-col"><span class="mobile">利益率</span>{{
                                                        percentizer(totalSummaryEntry('profit')).display
                                                    }}</div>
                                                    <DeltaNumbers v-if="deltaShown" type="profit_rate"
                                                        :planned="percentizer(totalSummaryEntry('yearly_plan')).value"
                                                        :actual="percentizer(totalSummaryEntry('profit')).value" />
                                                </div>
                                            </td>
                                        </template>
                                        <td v-if="showComment" class="sticky-right comment-cell"></td>
                                    </tr>
                                    <tr v-if="show('settlement')" class="summary-row">
                                        <td
                                            v-if="firstVisibleScenario === 'settlement'"
                                            class="p-name sticky-left first-col"
                                            :rowspan="visibleScenarioCount"
                                        >集計</td>
                                        <td class="m-name sticky-left second-col" v-if="firstVisibleScenario === 'settlement'" 
                                            :rowspan="visibleScenarioCount"
                                        >—</td>
                                        <td class="sub-name sticky-left third-col">
                                            <span>実績</span>
                                        </td>
                                        <template v-for="p in periods" :key="`summary-settlement-${p.period}`" v-if="totalGrouping !== 'fiscal'">
                                            <td>
                                                <div class="flex items-center gap-[5px]">
                                                    <div class="inner-col"><span class="mobile">売上</span>{{
                                                        amountOfMoneyParser(settlementValue(periodEntry(p.period, 'settlement'), 'sales'))
                                                    }}</div>
                                                    <DeltaNumbers v-if="deltaShown" type="sales"
                                                        :planned="periodEntry(p.period, 'profit').sales"
                                                        :actual="settlementValue(periodEntry(p.period, 'settlement'), 'sales')" />
                                                </div>
                                            </td>
                                            <td>
                                                <div class="flex items-center gap-[5px]">
                                                    <div class="inner-col"><span class="mobile">販管費</span>{{
                                                        amountOfMoneyParser(settlementValue(periodEntry(p.period, 'settlement'), 'expense'))
                                                    }}</div>
                                                    <DeltaNumbers v-if="deltaShown" type="expense"
                                                        :planned="periodEntry(p.period, 'profit').expense"
                                                        :actual="settlementValue(periodEntry(p.period, 'settlement'), 'expense')" />
                                                </div>
                                            </td>
                                            <td>
                                                <div class="flex items-center gap-[5px]">
                                                    <div class="inner-col"><span class="mobile">利益</span>{{
                                                        amountOfMoneyParser(settlementProfitValue(periodEntry(p.period, 'settlement')))
                                                    }}</div>
                                                    <DeltaNumbers v-if="deltaShown" type="profit"
                                                        :planned="periodEntry(p.period, 'profit').profit"
                                                        :actual="settlementProfitValue(periodEntry(p.period, 'settlement'))" />
                                                </div>
                                            </td>
                                            <td data-cell="right-border">
                                                <div class="flex items-center gap-[5px]">
                                                    <div class="inner-col"><span class="mobile">利益率</span>{{
                                                        percentizer(periodEntry(p.period, 'settlement')).display
                                                    }}</div>
                                                    <DeltaNumbers v-if="deltaShown" type="profit_rate"
                                                        :planned="percentizer(periodEntry(p.period, 'profit')).value"
                                                        :actual="percentizer(periodEntry(p.period, 'settlement')).value" />
                                                </div>
                                            </td>
                                        </template>
                                        <template v-if="totalGrouping === 'fiscal'">
                                            <template v-for="fy in activeFiscalYears" :key="`summary-total-settlement-${fy}`">
                                                <td>
                                                    <div class="flex items-center gap-[5px]">
                                                        <div class="inner-col"><span class="mobile">売上</span>{{
                                                            amountOfMoneyParser(settlementValue(fiscalSummaryEntry('settlement', fy), 'sales'))
                                                        }}</div>
                                                        <DeltaNumbers
                                                            v-if="deltaShown"
                                                            type="sales"
                                                            :planned="fiscalSummaryEntry('profit', fy).sales"
                                                            :actual="settlementValue(fiscalSummaryEntry('settlement', fy), 'sales')"
                                                        />
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="flex items-center gap-[5px]">
                                                        <div class="inner-col"><span class="mobile">販管費</span>{{
                                                            amountOfMoneyParser(settlementValue(fiscalSummaryEntry('settlement', fy), 'expense'))
                                                        }}</div>
                                                        <DeltaNumbers
                                                            v-if="deltaShown"
                                                            type="expense"
                                                            :planned="fiscalSummaryEntry('profit', fy).expense"
                                                            :actual="settlementValue(fiscalSummaryEntry('settlement', fy), 'expense')"
                                                        />
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="flex items-center gap-[5px]">
                                                        <div class="inner-col"><span class="mobile">利益</span>{{
                                                            amountOfMoneyParser(settlementProfitValue(fiscalSummaryEntry('settlement', fy)))
                                                        }}</div>
                                                        <DeltaNumbers
                                                            v-if="deltaShown"
                                                            type="profit"
                                                            :planned="fiscalSummaryEntry('profit', fy).profit"
                                                            :actual="settlementProfitValue(fiscalSummaryEntry('settlement', fy))"
                                                        />
                                                    </div>
                                                </td>
                                                <td data-cell="right-border">
                                                    <div class="flex items-center gap-[5px]">
                                                        <div class="inner-col"><span class="mobile">利益率</span>{{
                                                            percentizer(fiscalSummaryEntry('settlement', fy)).display
                                                        }}</div>
                                                        <DeltaNumbers
                                                            v-if="deltaShown"
                                                            type="profit_rate"
                                                            :planned="percentizer(fiscalSummaryEntry('profit', fy)).value"
                                                            :actual="percentizer(fiscalSummaryEntry('settlement', fy)).value"
                                                        />
                                                    </div>
                                                </td>
                                            </template>
                                        </template>
                                        <template v-else-if="showTotals">
                                            <td>
                                                <div class="flex items-center gap-[5px]">
                                                    <div class="inner-col"><span class="mobile">売上</span>{{
                                                        amountOfMoneyParser(settlementValue(totalSummaryEntry('settlement'), 'sales'))
                                                    }}</div>
                                                    <DeltaNumbers v-if="deltaShown" type="sales"
                                                        :planned="totalSummaryEntry('profit').sales"
                                                        :actual="settlementValue(totalSummaryEntry('settlement'), 'sales')" />
                                                </div>
                                            </td>
                                            <td>
                                                <div class="flex items-center gap-[5px]">
                                                    <div class="inner-col"><span class="mobile">販管費</span>{{
                                                        amountOfMoneyParser(settlementValue(totalSummaryEntry('settlement'), 'expense'))
                                                    }}</div>
                                                    <DeltaNumbers v-if="deltaShown" type="expense"
                                                        :planned="totalSummaryEntry('profit').expense"
                                                        :actual="settlementValue(totalSummaryEntry('settlement'), 'expense')" />
                                                </div>
                                            </td>
                                            <td>
                                                <div class="flex items-center gap-[5px]">
                                                    <div class="inner-col"><span class="mobile">利益</span>{{
                                                        amountOfMoneyParser(settlementProfitValue(totalSummaryEntry('settlement')))
                                                    }}</div>
                                                    <DeltaNumbers v-if="deltaShown" type="profit"
                                                        :planned="totalSummaryEntry('profit').profit"
                                                        :actual="settlementProfitValue(totalSummaryEntry('settlement'))" />
                                                </div>
                                            </td>
                                            <td>
                                                <div class="flex items-center gap-[5px]">
                                                    <div class="inner-col"><span class="mobile">利益率</span>{{
                                                        percentizer(totalSummaryEntry('settlement')).display
                                                    }}</div>
                                                    <DeltaNumbers v-if="deltaShown" type="profit_rate"
                                                        :planned="percentizer(totalSummaryEntry('profit')).value"
                                                        :actual="percentizer(totalSummaryEntry('settlement')).value" />
                                                </div>
                                            </td>
                                        </template>
                                        <td v-if="showComment" class="sticky-right comment-cell"></td>
                                    </tr>
                                </tbody>                                    
                                <tbody v-if="hasSelectedProjects" v-for="proj in sortedProjects">
                                    <tr v-if="show('yearly_plan')" :key="`${proj.name}-yearly`">
                                        <td
                                            v-if="firstVisibleScenario === 'yearly_plan'"
                                            class="p-name sticky-left first-col"
                                            :rowspan="visibleScenarioCount"
                                        >
                                            <div>{{ proj.name }}</div>
                                            
                                        </td>
                                        <td
                                            v-if="firstVisibleScenario === 'yearly_plan'"
                                            class="m-name sticky-left second-col"
                                            :rowspan="visibleScenarioCount"
                                        >
                                            <div v-if="managerNameFor(proj.name)">
                                                {{ managerNameFor(proj.name) }}
                                            </div>
                                        </td>
                                        <td class="sub-name sticky-left third-col">
                                            <span>年度予算</span>
                                        </td>
                                        <template v-for="p in periods" :key="p.period" v-if="totalGrouping !== 'fiscal'">
                                            
                                            <td>
                                                <div class="inner-col">{{
                                                    amountOfMoneyParser(proj.data?.[p.period]?.yearly_plan.sales) }}</div>
                                            </td>
                                            <td>
                                                <div class="inner-col">{{
                                                    amountOfMoneyParser(proj.data?.[p.period]?.yearly_plan.expense) }}</div>
                                            </td>
                                            <td>
                                                <div class="inner-col">{{
                                                    amountOfMoneyParser(proj.data?.[p.period]?.yearly_plan.sales -
                                                    proj.data?.[p.period]?.yearly_plan.expense) }}</div>
                                                </td>
                                            <td data-cell="right-border">
                                                <div class="inner-col">{{
                                                    percentizer(proj.data?.[p.period]?.yearly_plan).display }}</div>
                                            </td>
                                        </template>
                                        <template v-if="totalGrouping === 'fiscal'">
                                            <template v-for="fy in activeFiscalYears" :key="`total-yearly-${proj.name}-${fy}`">
                                                <td>
                                                    <div class="inner-col">{{
                                                        amountOfMoneyParser(fiscalTotalEntry(proj.name, 'yearly_plan', fy).sales)
                                                    }}</div>
                                                </td>
                                                <td>
                                                    <div class="inner-col">{{
                                                        amountOfMoneyParser(fiscalTotalEntry(proj.name, 'yearly_plan', fy).expense)
                                                    }}</div>
                                                </td>
                                                <td>
                                                    <div class="inner-col">{{
                                                        amountOfMoneyParser(fiscalTotalEntry(proj.name, 'yearly_plan', fy).profit)
                                                    }}</div>
                                                </td>
                                                <td data-cell="right-border">
                                                    <div class="inner-col">{{
                                                        percentizer(fiscalTotalEntry(proj.name, 'yearly_plan', fy)).display
                                                    }}</div>
                                                </td>
                                            </template>
                                        </template>
                                        <template v-else-if="showTotals">
                                            <td>
                                                <div class="inner-col">{{
                                                    amountOfMoneyParser(totalEntry(proj.name, 'yearly_plan').sales)
                                                }}</div>
                                            </td>
                                            <td>
                                                <div class="inner-col">{{
                                                    amountOfMoneyParser(totalEntry(proj.name, 'yearly_plan').expense)
                                                }}</div>
                                            </td>
                                            <td>
                                                <div class="inner-col">{{
                                                    amountOfMoneyParser(totalEntry(proj.name, 'yearly_plan').profit)
                                                }}</div>
                                            </td>
                                            <td>
                                                <div class="inner-col">{{
                                                    percentizer(totalEntry(proj.name, 'yearly_plan')).display
                                                }}</div>
                                            </td>
                                        </template>
                                        
                                        <td v-if="showComment" class="sticky-right comment-cell"></td>
                                    </tr>
                                    <tr v-if="show('profit')" :key="`${proj.name}-plan`">
                                        <td
                                            v-if="firstVisibleScenario === 'profit'"
                                            class="p-name sticky-left first-col"
                                            :rowspan="visibleScenarioCount"
                                        >
                                            <div>{{ proj.name }}</div>
                                        </td>
                                        <td
                                            v-if="firstVisibleScenario === 'profit'"
                                            class="m-name sticky-left second-col"
                                            :rowspan="visibleScenarioCount"
                                        >
                                            <div v-if="managerNameFor(proj.name)">
                                                {{ managerNameFor(proj.name) }}
                                            </div>
                                        </td>
                                        <td class="sub-name sticky-left third-col">
                                            <span>損益計画</span>
                                        </td>
                                        <template v-for="p in periods" :key="p.period" v-if="totalGrouping !== 'fiscal'">
                                            <td>
                                                <div class="flex items-center gap-[5px]">
                                                    <div class="inner-col"><span class="mobile">売上</span>{{
                                                        amountOfMoneyParser(proj.data?.[p.period]?.profit.sales) }}</div>
                                                    <DeltaNumbers v-if="deltaShown" type="sales" :planned="proj.data?.[p.period]?.yearly_plan.sales"
                                                        :actual="proj.data?.[p.period]?.profit.sales" />
                                                </div>
                                            </td>
                                            <td>
                                                <div class="flex items-center gap-[5px]">
                                                    <div class="inner-col"><span class="mobile">販管費</span>{{
                                                        amountOfMoneyParser(proj.data?.[p.period]?.profit.expense) }}</div>
                                                    <DeltaNumbers v-if="deltaShown" type="expense" :planned="proj.data?.[p.period]?.yearly_plan.expense"
                                                        :actual="proj.data?.[p.period]?.profit.expense" />
                                                </div>
                                            </td>
                                            <td>
                                                <div class="flex items-center gap-[5px]">
                                                    <div class="inner-col"><span class="mobile">利益</span>{{
                                                        amountOfMoneyParser(proj.data?.[p.period]?.profit.profit) }}
                                                    </div>
                                                    <DeltaNumbers v-if="deltaShown" type="profit"
                                                        :planned="proj.data?.[p.period]?.yearly_plan.sales - proj.data?.[p.period]?.yearly_plan.expense"
                                                        :actual="proj.data?.[p.period]?.profit.profit" />
                                                </div>
                                            </td>
                                            <td data-cell="right-border">
                                                <div class="flex items-center gap-[5px]">
                                                    <div class="inner-col"><span class="mobile">利益率</span>{{
                                                        percentizer(proj.data?.[p.period]?.profit).display }}</div>
                                                    <DeltaNumbers v-if="deltaShown" type="profit_rate"
                                                        :planned="percentizer(proj.data?.[p.period]?.yearly_plan).value"
                                                        :actual="percentizer(proj.data?.[p.period]?.profit).value" />
                                                </div>
                                            </td>
                                        </template>
                                        <template v-if="totalGrouping === 'fiscal'">
                                            <template v-for="fy in activeFiscalYears" :key="`total-profit-${proj.name}-${fy}`">
                                                <td>
                                                    <div class="flex items-center gap-[5px]">
                                                        <div class="inner-col"><span class="mobile">売上</span>{{
                                                            amountOfMoneyParser(fiscalTotalEntry(proj.name, 'profit', fy).sales)
                                                        }}</div>
                                                        <DeltaNumbers
                                                            v-if="deltaShown"
                                                            type="sales"
                                                            :planned="fiscalTotalEntry(proj.name, 'yearly_plan', fy).sales"
                                                            :actual="fiscalTotalEntry(proj.name, 'profit', fy).sales"
                                                        />
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="flex items-center gap-[5px]">
                                                        <div class="inner-col"><span class="mobile">販管費</span>{{
                                                            amountOfMoneyParser(fiscalTotalEntry(proj.name, 'profit', fy).expense)
                                                        }}</div>
                                                        <DeltaNumbers
                                                            v-if="deltaShown"
                                                            type="expense"
                                                            :planned="fiscalTotalEntry(proj.name, 'yearly_plan', fy).expense"
                                                            :actual="fiscalTotalEntry(proj.name, 'profit', fy).expense"
                                                        />
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="flex items-center gap-[5px]">
                                                        <div class="inner-col"><span class="mobile">利益</span>{{
                                                            amountOfMoneyParser(fiscalTotalEntry(proj.name, 'profit', fy).profit)
                                                        }}</div>
                                                        <DeltaNumbers
                                                            v-if="deltaShown"
                                                            type="profit"
                                                            :planned="fiscalTotalEntry(proj.name, 'yearly_plan', fy).profit"
                                                            :actual="fiscalTotalEntry(proj.name, 'profit', fy).profit"
                                                        />
                                                    </div>
                                                </td>
                                                <td data-cell="right-border">
                                                    <div class="flex items-center gap-[5px]">
                                                        <div class="inner-col"><span class="mobile">利益率</span>{{
                                                            percentizer(fiscalTotalEntry(proj.name, 'profit', fy)).display
                                                        }}</div>
                                                        <DeltaNumbers
                                                            v-if="deltaShown"
                                                            type="profit_rate"
                                                            :planned="percentizer(fiscalTotalEntry(proj.name, 'yearly_plan', fy)).value"
                                                            :actual="percentizer(fiscalTotalEntry(proj.name, 'profit', fy)).value"
                                                        />
                                                    </div>
                                                </td>
                                            </template>
                                        </template>
                                        <template v-else-if="showTotals">
                                            <td>
                                                <div class="flex items-center gap-[5px]">
                                                    <div class="inner-col"><span class="mobile">売上</span>{{
                                                        amountOfMoneyParser(totalEntry(proj.name, 'profit').sales)
                                                    }}</div>
                                                    <DeltaNumbers v-if="deltaShown" type="sales"
                                                        :planned="totalEntry(proj.name, 'yearly_plan').sales"
                                                        :actual="totalEntry(proj.name, 'profit').sales" />
                                                </div>
                                            </td>
                                            <td>
                                                <div class="flex items-center gap-[5px]">
                                                    <div class="inner-col"><span class="mobile">販管費</span>{{
                                                        amountOfMoneyParser(totalEntry(proj.name, 'profit').expense)
                                                    }}</div>
                                                    <DeltaNumbers v-if="deltaShown" type="expense"
                                                        :planned="totalEntry(proj.name, 'yearly_plan').expense"
                                                        :actual="totalEntry(proj.name, 'profit').expense" />
                                                </div>
                                            </td>
                                            <td>
                                                <div class="flex items-center gap-[5px]">
                                                    <div class="inner-col"><span class="mobile">利益</span>{{
                                                        amountOfMoneyParser(totalEntry(proj.name, 'profit').profit)
                                                    }}</div>
                                                    <DeltaNumbers v-if="deltaShown" type="profit"
                                                        :planned="totalEntry(proj.name, 'yearly_plan').sales - totalEntry(proj.name, 'yearly_plan').expense"
                                                        :actual="totalEntry(proj.name, 'profit').profit" />
                                                </div>
                                            </td>
                                            <td>
                                                <div class="flex items-center gap-[5px]">
                                                    <div class="inner-col"><span class="mobile">利益率</span>{{
                                                        percentizer(totalEntry(proj.name, 'profit')).display }}</div>
                                                    <DeltaNumbers v-if="deltaShown" type="profit_rate"
                                                        :planned="percentizer(totalEntry(proj.name, 'yearly_plan')).value"
                                                        :actual="percentizer(totalEntry(proj.name, 'profit')).value" />
                                                </div>
                                            </td>
                                        </template>
                                        
                                        <td v-if="showComment" class="sticky-right comment-cell"></td>
                                    </tr>
                                    <tr v-if="show('settlement')" :key="`${proj.name}-settlement`">
                                        <td
                                            v-if="firstVisibleScenario === 'settlement'"
                                            class="p-name sticky-left first-col"
                                            :rowspan="visibleScenarioCount"
                                        >
                                            <div>{{ proj.name }}</div>
                                        </td>
                                        <td
                                            v-if="firstVisibleScenario === 'settlement'"
                                            class="m-name sticky-left second-col"
                                            :rowspan="visibleScenarioCount"
                                        >
                                            <div v-if="managerNameFor(proj.name)">
                                                {{ managerNameFor(proj.name) }}
                                            </div>
                                        </td>
                                        <td class="sub-name sticky-left third-col flex gap-1 items-center flex-center-col">
                                            <div v-if="showAnyArrow(proj.name)" class="flex" title="計画との差が大きい月です">
                                                <svg fill="tomato" style="transform: rotate(180deg);" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 30 30">
                                                    <path d="M14.978 0C6.735-.055-.129 6.931.002 15.153c-.028 8.166 6.815 14.939 14.976 14.811v-.04c.965.012 1.935-.068 2.889-.243 4.817-.861 9.056-4.274 10.937-8.8C32.986 11.04 25.688-.021 14.978 0m0 27.903C6.08 27.659-.075 18.755 3.433 10.373 7.813.292 22.129.294 26.49 10.385c3.512 8.225-2.605 17.404-11.512 17.518m-1.735-13.968c-.293 2.283-.156 4.58-.125 6.873l.166 2.289c.304 2.068 3.234 2.088 3.548 0 .186-1.523.193-3.051.205-4.58.028-1.53.044-3.058-.164-4.582-.334-2.082-3.284-2.104-3.63 0m-.344-4.565c.115.303.278.565.465.811.473.371 1.062.634 1.685.627 1.248.021 2.335-1.09 2.278-2.331-.015-.643-.308-1.218-.729-1.681-1.906-1.558-4.534.238-3.699 2.574"/>
                                                </svg>
                                            </div>
                                            実績
                                        </td>
                                        <template v-for="p in periods" :key="p.period" v-if="totalGrouping !== 'fiscal'">
                                            <td>
                                                <div class="flex items-center gap-[5px]">
                                                    <div class="inner-col"><span class="mobile">売上</span>{{
                                                        amountOfMoneyParser(settlementValue(proj.data?.[p.period]?.settlement, 'sales')) }}
                                                    </div>
                                                    <DeltaNumbers v-if="deltaShown" type="sales" :planned="proj.data?.[p.period]?.profit.sales"
                                                        :actual="settlementValue(proj.data?.[p.period]?.settlement, 'sales')" />
                                                </div>
                                            </td>
                                            <td>
                                                <div class="flex items-center gap-[5px]">
                                                    <div class="inner-col"><span class="mobile">販管費</span>{{
                                                        amountOfMoneyParser(settlementValue(proj.data?.[p.period]?.settlement, 'expense')) }}</div>
                                                    <DeltaNumbers v-if="deltaShown" type="expense" :planned="proj.data?.[p.period]?.profit.expense"
                                                        :actual="settlementValue(proj.data?.[p.period]?.settlement, 'expense')" />
                                                </div>
                                            </td>
                                            <td>
                                                <div class="flex items-center gap-[5px]">
                                                    <div class="inner-col"><span class="mobile">利益</span>{{
                                                        amountOfMoneyParser(settlementProfitValue(proj.data?.[p.period]?.settlement)) }}</div>
                                                    <DeltaNumbers v-if="deltaShown" type="profit"
                                                        :planned="proj.data?.[p.period]?.profit.profit"
                                                        :actual="settlementProfitValue(proj.data?.[p.period]?.settlement)" />
                                                </div>
                                            </td>
                                            <td data-cell="right-border">
                                                <div class="flex items-center gap-[5px]">
                                                    <div class="inner-col"><span class="mobile">利益率</span>{{
                                                        percentizer(proj.data?.[p.period]?.settlement).display }}</div>
                                                    <DeltaNumbers v-if="deltaShown" type="profit_rate"
                                                        :planned="percentizer(proj.data?.[p.period]?.profit).value"
                                                        :actual="percentizer(proj.data?.[p.period]?.settlement).value" />
                                                </div>
                                            </td>
                                        </template>
                                        
                                        <template v-if="totalGrouping === 'fiscal'">
                                            <template v-for="fy in activeFiscalYears" :key="`total-settlement-${proj.name}-${fy}`">
                                                <td>
                                                    <div class="flex items-center gap-[5px]">
                                                        <div class="inner-col"><span class="mobile">売上</span>{{
                                                            amountOfMoneyParser(settlementValue(fiscalTotalEntry(proj.name, 'settlement', fy), 'sales'))
                                                        }}</div>
                                                        <DeltaNumbers
                                                            v-if="deltaShown"
                                                            type="sales"
                                                            :planned="fiscalTotalEntry(proj.name, 'profit', fy).sales"
                                                            :actual="settlementValue(fiscalTotalEntry(proj.name, 'settlement', fy), 'sales')"
                                                        />
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="flex items-center gap-[5px]">
                                                        <div class="inner-col"><span class="mobile">販管費</span>{{
                                                            amountOfMoneyParser(settlementValue(fiscalTotalEntry(proj.name, 'settlement', fy), 'expense'))
                                                        }}</div>
                                                        <DeltaNumbers
                                                            v-if="deltaShown"
                                                            type="expense"
                                                            :planned="fiscalTotalEntry(proj.name, 'profit', fy).expense"
                                                            :actual="settlementValue(fiscalTotalEntry(proj.name, 'settlement', fy), 'expense')"
                                                        />
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="flex items-center gap-[5px]">
                                                        <div class="inner-col"><span class="mobile">利益</span>{{
                                                            amountOfMoneyParser(settlementProfitValue(fiscalTotalEntry(proj.name, 'settlement', fy)))
                                                        }}</div>
                                                        <DeltaNumbers
                                                            v-if="deltaShown"
                                                            type="profit"
                                                            :planned="fiscalTotalEntry(proj.name, 'profit', fy).profit"
                                                            :actual="settlementProfitValue(fiscalTotalEntry(proj.name, 'settlement', fy))"
                                                        />
                                                    </div>
                                                </td>
                                                <td data-cell="right-border">
                                                    <div class="flex items-center gap-[5px]">
                                                        <div class="inner-col"><span class="mobile">利益率</span>{{
                                                            percentizer(fiscalTotalEntry(proj.name, 'settlement', fy)).display
                                                        }}</div>
                                                        <DeltaNumbers
                                                            v-if="deltaShown"
                                                            type="profit_rate"
                                                            :planned="percentizer(fiscalTotalEntry(proj.name, 'profit', fy)).value"
                                                            :actual="percentizer(fiscalTotalEntry(proj.name, 'settlement', fy)).value"
                                                        />
                                                    </div>
                                                </td>
                                            </template>
                                        </template>
                                        <template v-else-if="showTotals">
                                            <td>
                                                <div class="flex items-center gap-[5px]">
                                                    <div class="inner-col"><span class="mobile">売上</span>{{
                                                        amountOfMoneyParser(settlementValue(totalEntry(proj.name, 'settlement'), 'sales'))
                                                    }}</div>
                                                    <DeltaNumbers v-if="deltaShown" type="sales" :planned="totalEntry(proj.name, 'profit').sales"
                                                        :actual="settlementValue(totalEntry(proj.name, 'settlement'), 'sales')" />
                                                </div>
                                            </td>
                                            <td>
                                                <div class="flex items-center gap-[5px]">
                                                    <div class="inner-col"><span class="mobile">販管費</span>{{
                                                        amountOfMoneyParser(settlementValue(totalEntry(proj.name, 'settlement'), 'expense'))
                                                    }}</div>
                                                    <DeltaNumbers v-if="deltaShown" type="expense" :planned="totalEntry(proj.name, 'profit').expense"
                                                        :actual="settlementValue(totalEntry(proj.name, 'settlement'), 'expense')" />
                                                </div>
                                            </td>
                                            <td>
                                                <div class="flex items-center gap-[5px]">
                                                    <div class="inner-col"><span class="mobile">利益</span>{{
                                                        amountOfMoneyParser(settlementProfitValue(totalEntry(proj.name, 'settlement')))
                                                    }}</div>
                                                    <DeltaNumbers v-if="deltaShown" type="profit"
                                                        :planned="totalEntry(proj.name, 'profit').profit"
                                                        :actual="settlementProfitValue(totalEntry(proj.name, 'settlement'))" />
                                                </div>
                                            </td>
                                            <td>
                                                <div class="flex items-center gap-[5px]">
                                                    <div class="inner-col"><span class="mobile">利益率</span>{{
                                                        percentizer(totalEntry(proj.name, 'settlement')).display }}</div>
                                                    <DeltaNumbers v-if="deltaShown" type="profit_rate"
                                                        :planned="percentizer(totalEntry(proj.name, 'profit')).value"
                                                        :actual="percentizer(totalEntry(proj.name, 'settlement')).value" />
                                                </div>
                                            </td>
                                        </template>
                                        
                                        <td v-if="showComment" class="sticky-right comment-cell">
                                            <div class="inner-col">
                                                <span class="mobile">コメント</span>
                                                <div class="flex items-center gap-2 cursor-pointer">
                                                    <svg fill="var(--primary-color)" height="15" version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 33" @click="selectProjectComment(proj.name)">
                                                        <path d="M10.788 8.109c1.574-0.063 3.148-0.083 4.711-0.104l2.356-0.031 2.356-0.010 2.356 0.010c0.782 0 1.574 0.021 2.356 0.031 1.574 0.031 3.148 0.063 4.711 0.136 0.459 0.021 0.823 0.417 0.803 0.876-0.021 0.438-0.375 0.771-0.803 0.792-1.574 0.073-3.148 0.115-4.711 0.136-0.782 0.010-1.574 0.031-2.356 0.031l-2.345 0.021-2.356-0.010-2.356-0.031c-1.574-0.021-3.148-0.052-4.711-0.104-0.479-0.021-0.855-0.417-0.844-0.896 0.010-0.459 0.386-0.823 0.834-0.844zM10.788 13.050c1.574-0.052 3.148-0.083 4.711-0.104l2.356-0.031 2.356-0.010 2.356 0.010c0.782 0 1.574 0.021 2.356 0.031 1.574 0.031 3.148 0.063 4.711 0.136 0.459 0.021 0.823 0.417 0.803 0.876-0.021 0.438-0.375 0.771-0.803 0.792-1.574 0.073-3.148 0.115-4.711 0.136-0.782 0.010-1.574 0.031-2.356 0.031l-2.356 0.010-2.356-0.010-2.356-0.031c-1.574-0.021-3.148-0.052-4.711-0.104-0.479-0.021-0.855-0.417-0.844-0.907 0.021-0.438 0.396-0.803 0.844-0.823zM10.788 17.991c0.74-0.052 1.491-0.083 2.231-0.104l1.115-0.031c0.375-0.010 0.74-0.010 1.115-0.010 0.74 0 1.491 0.010 2.231 0.042 0.75 0.031 1.491 0.063 2.231 0.136 0.459 0.052 0.803 0.459 0.75 0.928-0.042 0.407-0.365 0.709-0.75 0.75-0.75 0.073-1.491 0.115-2.231 0.136-0.75 0.031-1.491 0.042-2.231 0.042-0.375 0-0.74 0-1.115-0.010l-1.115-0.031c-0.74-0.021-1.491-0.052-2.231-0.104-0.479-0.042-0.844-0.459-0.803-0.938 0.031-0.427 0.375-0.771 0.803-0.803z"></path><path d="M39.432 11.393c-0.188-1.063-0.521-2.116-0.99-3.106-0.479-0.99-1.105-1.897-1.835-2.71s-1.564-1.511-2.45-2.106c-0.886-0.594-1.835-1.084-2.794-1.501-1.939-0.813-3.95-1.313-5.973-1.605s-4.055-0.396-6.066-0.365c-2.022 0.042-4.055 0.219-6.066 0.605-2.012 0.396-4.013 1.001-5.889 1.949-0.938 0.479-1.845 1.042-2.679 1.699-0.834 0.667-1.616 1.428-2.272 2.293-0.667 0.855-1.209 1.824-1.605 2.835-0.396 1.021-0.636 2.095-0.74 3.169-0.052 0.532-0.052 1.084-0.042 1.605 0.010 0.532 0.052 1.053 0.125 1.584 0.146 1.053 0.417 2.116 0.844 3.117s1.011 1.939 1.72 2.762c0.709 0.823 1.532 1.532 2.418 2.126 1.772 1.188 3.44 1.824 5.41 2.356 1.803 0.49 3.867 0.782 5.681 0.876 0.146 0.010 0.281 0.073 0.386 0.177 0.459 0.5 0.938 1.074 1.449 1.511 0.667 0.584 1.407 1.126 2.178 1.584 0.761 0.448 1.564 0.803 2.387 1.115 0.865 0.313 2.21 0.605 2.929 0.657 0.698 0.052 0.782-0.479 0.563-0.938-0.229-0.469-0.281-0.552-0.375-0.761s-0.188-0.417-0.271-0.625-0.344-0.844-0.49-1.261c-0.115-0.344-0.292-0.938-0.386-1.407-0.031-0.167 0.083-0.323 0.25-0.344 1.626-0.229 3.242-0.552 4.847-1.032 0.98-0.292 1.939-0.657 2.877-1.094s1.855-0.98 2.7-1.626c0.844-0.646 1.626-1.418 2.272-2.293 0.323-0.438 0.615-0.907 0.865-1.397s0.459-0.99 0.636-1.511c0.344-1.032 0.532-2.106 0.594-3.169 0.021-1.032-0.021-2.106-0.208-3.169zM37.347 14.478c-0.031 0.896-0.167 1.782-0.427 2.616-0.125 0.417-0.292 0.823-0.479 1.22s-0.407 0.771-0.657 1.126c-0.5 0.719-1.115 1.365-1.814 1.928-1.397 1.126-3.106 1.928-4.899 2.522-0.896 0.302-1.814 0.542-2.752 0.75-0.928 0.208-1.876 0.375-2.835 0.511h-0.031c-0.396 0.063-0.709 0.396-0.719 0.813-0.010 0.594 0.083 1.126 0.208 1.626s0.292 0.969 0.469 1.438c0.146 0.375 0.292 0.698 0.542 1.105 0.042 0.073-0.021 0.146-0.104 0.125-1.167-0.365-2.304-0.907-3.461-1.845-1.23-0.99-1.762-1.584-2.814-2.835-0.146-0.177-0.365-0.302-0.615-0.323h-0.031c-1.908-0.188-3.805-0.479-5.629-0.98-1.814-0.5-3.565-1.199-5.055-2.22-0.74-0.511-1.407-1.105-1.97-1.772-0.563-0.678-1.022-1.418-1.355-2.231s-0.552-1.678-0.657-2.564-0.125-1.824-0.031-2.689c0.104-0.876 0.313-1.73 0.646-2.543 0.334-0.803 0.771-1.564 1.324-2.251 1.115-1.386 2.595-2.481 4.232-3.273 0.823-0.396 1.678-0.74 2.564-1.022s1.793-0.511 2.71-0.678c1.845-0.354 3.742-0.511 5.639-0.532 1.907-0.010 3.815 0.073 5.67 0.344 1.866 0.271 3.69 0.709 5.378 1.418 1.689 0.698 3.242 1.668 4.44 2.95 0.594 0.636 1.105 1.355 1.491 2.126s0.667 1.605 0.834 2.481c0.167 0.855 0.219 1.751 0.188 2.658z"></path>
                                                    </svg>
                                                    <span v-if="commentCount[proj.name] > 0" class="text-xs">{{ commentCount[proj.name] }}</span>
                                                    <span class="side-notification" style="position: unset;background-color: #F28C28;z-index:inherit;" v-if="financeTotalBadge(proj.name)?.[periodStartIso]">{{ financeTotalBadge(proj.name)?.[periodStartIso] }}</span>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                                
                            </table>

                        </div>
                        <div class="overflow-auto h-[calc(100%-65px)]" v-if="hasSelectedProjects && (tab == 'pie' || tab == 'bar')">
                            <div class="px-[20px]">
                                <div v-if="tab == 'pie'" class="flex gap-[15px] mt-[10px]">
                                    <label v-for="item in possibleScenarios"
                                        class="flex items-center gap-[10px] text-[12px]">
                                        <input type="radio" class="custom-f-radio" name="scenario" :value="item.value"
                                            v-model="activeScenario">
                                        <span>{{ item.label }}</span>
                                    </label>
                                </div>
                                <div class="flex my-[20px] gap-[15px]">
                                    <label v-for="item in possibleTypes"
                                        class="flex items-center gap-[10px] text-[12px]">
                                        <input type="radio" name="type" class="custom-f-radio" :value="item.value"
                                            v-model="activeType">
                                        <span>{{ item.label }}</span>
                                    </label>
                                </div>
                            </div>


                            <div v-if="tab == 'pie'">

                                <div class="p-pie-chart">
                                    <PieChart :projectsData="financeData" :activeScenario="activeScenario"
                                        :activeType="activeType" />
                                </div>

                            </div>
                            <div v-if="tab == 'bar'">
                                <div>
                                    <BarChart :projectsData="financeData" :activeView="activeType" />
                                </div>

                            </div>
                        </div>
                    </div>
                    </div>
                </div>
            </div>
            <Transition name="smLoad">
                <CommentWindow 
                    v-if="selectedId"  
                    type="実績"
                    :currentProjectId="selectedId"
                    :period="`${rangeEnd.year}-${String(rangeEnd.month).padStart(2, '0')}`" 
                    @close="selectedId = null"
                    @getCommentCounts="get_finance_comment_counts" 
                />
            </Transition>
        </div>
        
</template>
<script setup lang="ts">
import { Project, YearlyFinancialData } from '@/interface/projectInterface';
import CloseIcon from '../Form/CloseIcon.vue';
import { computed, ref, watch } from 'vue';
import 'styles/customForm.css'
import { MonthNumbers, DateTime } from 'luxon';
import { amountOfMoneyParser } from '@/utils/tools';
import { useRoute, useRouter } from 'vue-router';
import LoaderButton from '../Global/LoaderButton.vue';
import { useResponsive } from '@/store/responsive';
import { useMenuStore } from '@/store/menu';
import { User } from '@/interface/globalInterface';
import DeltaNumbers from '@/components/Project/ProjectTabs/Finance/DeltaNumbers.vue'
import UserPanel from '../Global/UserPanel.vue';
import BarChart from './ProjectTabs/Finance/BarChart.vue';
import PieChart from './ProjectTabs/Finance/PieChart.vue';
import Back from '../Icons/Back.vue';
import { useApi } from '@/composables/api';
import BadgeLoader from './ProjectTabs/Finance/BadgeLoader.vue';
import CommentWindow from './ProjectTabs/Finance/CommentWindow.vue';
import { useBadgeStore } from '@/store/badge';
import { useAuthUserStore } from '@/store/auth';
import PeriodRangePicker from './ProjectTabs/Finance/PeriodRangePicker.vue';
import Filter from '../Icons/Filter.vue';
import CommandButton from '../Global/CommandButton.vue';
import ProjectMemberSort from './ProjectMemberSort.vue';
import FilterById from '../Global/FilterById.vue';
const router = useRouter()
const props = defineProps<{
    projects: Project[]
    ownProjectIds: number[]
}>()
const emit = defineEmits<{
    close: []
}>()
type PeriodCell = { year:number; month:number; period:string; fiscalYear:number }
const pad2 = (n:number) => String(n).padStart(2, '0')
const periodKey = (y:number, m:number) => `${y}-${pad2(m)}`
const fiscalYearFrom = (y:number, m:number) => (m >= 3 ? y : y - 1)

const generatePeriodRange = (start: DateTime, end: DateTime): PeriodCell[] => {
  const out: PeriodCell[] = []
  let cursor = start.startOf('month')
  const stop = end.startOf('month')
  while (cursor <= stop) {
    out.push({
      year: cursor.year,
      month: cursor.month,
      period: periodKey(cursor.year, cursor.month),
      fiscalYear: fiscalYearFrom(cursor.year, cursor.month),
    })
    cursor = cursor.plus({ months: 1 })
  }
  return out
}

const scenarioOptions: Array<{ label: string; value: 'yearly_plan' | 'profit' | 'settlement' }> = [
    {label: '年度予算', value: 'yearly_plan'},
    {label: '損益計画', value: 'profit'},
    {label: '実績', value: 'settlement'}
]
const selectedOption = ref<Array<'yearly_plan' | 'profit' | 'settlement'>>([])
const show = (k: 'yearly_plan' | 'profit' | 'settlement') => {
  const opts = selectedOption.value;
  return opts.length === 0 || opts.includes(k);
};
const includeForecastSettlement = ref(true)
const visibleScenarioCount = computed(() =>
    scenarioOptions.filter(option => show(option.value)).length
)
const firstVisibleScenario = computed<'yearly_plan' | 'profit' | 'settlement'>(() => {
    if (show('yearly_plan')) return 'yearly_plan'
    if (show('profit')) return 'profit'
    return 'settlement'
})
const deltaShown = computed(() => selectedOption.value.length === 0)

const selectedId = ref<number | null>(null)
interface UnitData {
    expense: number,
    sales: number,
    profit: number,
    id?: number
    has_data?: boolean
    is_forecast?: boolean
}
type ScenarioKey = 'yearly_plan' | 'profit' | 'settlement'
type PeriodTotalsEntry = {
    year?: number
    month?: number
    yearly_plan?: UnitData
    profit?: UnitData
    settlement?: UnitData
}
type ProjectPeriodScenarioEntry = Record<ScenarioKey, UnitData>
type ComparisonProjectPeriods = Record<string, Record<number, Record<string, Partial<ProjectPeriodScenarioEntry>>>>
type ComparisonPeriodTotals = Record<number, Record<string, PeriodTotalsEntry>>
const visibleScenarioOptions = computed(() =>
    scenarioOptions.filter(option => show(option.value))
)
const mobileActiveScenario = ref<ScenarioKey>('profit')
watch(visibleScenarioOptions, (options) => {
    if (!options.some(option => option.value === mobileActiveScenario.value)) {
        mobileActiveScenario.value = options[0]?.value ?? 'yearly_plan'
    }
}, { immediate: true })
const THRESHOLD = 10;
const emptyUnit: UnitData = {
    expense: 0,
    sales: 0,
    profit: 0,
    has_data: false,
    is_forecast: false,
}

type Key = 'sales' | 'expense' | 'profit';
const hasSettlementEntry = (unit?: UnitData | null) => unit?.has_data === true
const settlementValue = (unit: UnitData | undefined, key: Key) => hasSettlementEntry(unit) ? Math.round(Number(unit?.[key] ?? 0)) : NaN
const settlementProfitValue = (unit: UnitData | undefined) => hasSettlementEntry(unit) ? Math.round(Number(unit?.profit ?? 0)) : NaN
const filteredSettlementUnit = (unit?: Partial<UnitData> | null): UnitData | Partial<UnitData> | undefined => {
    if (!unit) return unit ?? undefined
    if (includeForecastSettlement.value || !unit.is_forecast) return unit
    return {
        ...emptyUnit,
        id: unit.id,
    }
}
const filterScenarioRecord = <T extends { settlement?: Partial<UnitData> | null } | null | undefined>(value: T): T => {
    if (!value) return value
    if (includeForecastSettlement.value) return value
    return {
        ...value,
        settlement: filteredSettlementUnit(value.settlement),
    } as T
}
const filterProjectMonthData = (value: Record<string, any>) =>
    Object.fromEntries(
        Object.entries(value ?? {}).map(([projectName, months]) => [
            projectName,
            Object.fromEntries(
                Object.entries(months as Record<string, any>).map(([period, entry]) => [
                    period,
                    filterScenarioRecord(entry),
                ])
            ),
        ])
    )
const filterComparisonProjectTotals = (value: Record<string, Record<number, Record<ScenarioKey, UnitData>>>) =>
    Object.fromEntries(
        Object.entries(value ?? {}).map(([projectName, yearlyTotals]) => [
            projectName,
            Object.fromEntries(
                Object.entries(yearlyTotals ?? {}).map(([fy, totals]) => [
                    fy,
                    filterScenarioRecord(totals),
                ])
            ),
        ])
    ) as Record<string, Record<number, Record<ScenarioKey, UnitData>>>
const filterComparisonSummaryTotals = (value: Record<number, Record<ScenarioKey, UnitData>>) =>
    Object.fromEntries(
        Object.entries(value ?? {}).map(([fy, totals]) => [
            fy,
            filterScenarioRecord(totals),
        ])
    ) as Record<number, Record<ScenarioKey, UnitData>>

const showAnyArrow = (name: string): boolean => {
  if (!projectHasSettlementData(name)) return false
  return (['sales','expense','profit'] as Key[]).some(k => {
    const v = variance.value?.[name]?.[k];
    return v != null && Math.abs(v) >= THRESHOLD;
  });
}
const percentizer = (data: UnitData | null | undefined) => {
    if (data && 'has_data' in data && data.has_data === false) {
        return { value: 0, display: '—' }
    }
    const sales = Number(data?.sales ?? 0)
    const explicitProfit = data?.profit
    const derivedProfit = Number(data?.sales ?? 0) - Number(data?.expense ?? 0)
    const profit = Number.isFinite(Number(explicitProfit)) ? Number(explicitProfit) : derivedProfit
    if (!sales || Number.isNaN(sales)) {
        return { value: 0, display: '—' }
    }
    const value = (profit / sales) * 100
    if (!Number.isFinite(value)) {
        return { value: 0, display: '—' }
    }
    return { value, display: `${value.toFixed(2)}%` }
}

const selectedProjects = ref<number[]>([])
const rawFinanceData = ref<YearlyFinancialData>({})
const financeData = computed<YearlyFinancialData>(() => {
    if (includeForecastSettlement.value) return rawFinanceData.value
    return Object.fromEntries(
        Object.entries(rawFinanceData.value ?? {}).map(([projectName, totals]) => [
            projectName,
            filterScenarioRecord(totals),
        ])
    ) as YearlyFinancialData
})
const variance = ref<Record<string, { sales: number | null; expenses: number | null; profit: number | null }>>({})
const rawSummarizeData = ref<{
    profit: UnitData,
    yearly_plan: UnitData,
    settlement: UnitData
}>({
    profit: {
        expense: 0,
        sales: 0,
        profit: 0,
    },
    yearly_plan: {
        expense: 0,
        sales: 0,
        profit: 0,
    },
    settlement: {
        expense: 0,
        sales: 0,
        profit: 0,
    }
})
const summarizeData = computed(() => {
    if (includeForecastSettlement.value) return rawSummarizeData.value
    return filterScenarioRecord(rawSummarizeData.value) ?? rawSummarizeData.value
})

const MAX_RANGE_MONTHS = 12
const currentMonth = DateTime.now().startOf('month')
const defaultStart = currentMonth
const defaultEnd = currentMonth

const normalizeRange = (rawStart: DateTime, rawEnd: DateTime) => {
  let start = rawStart.startOf('month')
  let end = rawEnd.startOf('month')

  if (end < start) {
    const tmp = start
    start = end
    end = tmp
  }

  const monthsApart = Math.round(end.diff(start, 'months').months ?? 0)
  if (monthsApart > MAX_RANGE_MONTHS - 1) {
    end = start.plus({ months: MAX_RANGE_MONTHS - 1 })
  }

  return { start, end }
}

const initialRange = normalizeRange(defaultStart, defaultEnd)
const periodStart = ref<DateTime>(initialRange.start)
const periodEnd = ref<DateTime>(initialRange.end)

const periodStartIso = computed(() => periodStart.value.toFormat('yyyy-MM'))
const periodEndIso = computed(() => periodEnd.value.toFormat('yyyy-MM'))

const normalizedRange = computed(() => normalizeRange(periodStart.value, periodEnd.value))
const nextMonthKey = computed(() =>
  periodEnd.value.plus({ months: 1 }).toFormat('yyyy-MM')
)
const previosMonthKey = computed(() => 
    periodStart.value.minus({ months: 1 }).toFormat('yyyy-MM')
)
const nextMonthCount = computed(() =>
  selectedBadge.value.period_counts[nextMonthKey.value] ?? 0
)
const previousMonthCount = computed(() =>
  selectedBadge.value.period_counts[previosMonthKey.value] ?? 0
)
const thisMonthCount = computed(() => {
    const badge = selectedBadge.value
    if (!badge || !badge.period_counts) return 0
    if (periodStartIso.value === periodEndIso.value) {
        return badge.period_counts[periodStartIso.value] ?? 0
    }
    let start = periodStart.value.startOf('month')
    let end   = periodEnd.value.startOf('month')

    if (start > end) {
        const tmp = start
        start = end
        end = tmp
    }

    let sum = 0
    let cursor = start

    while (cursor <= end) {
        const key = cursor.toFormat('yyyy-MM')
        sum += badge.period_counts[key] ?? 0
        cursor = cursor.plus({ months: 1 })
    }

    return sum
})
const intervalPayload = computed(() => ({
  startYear: normalizedRange.value.start.year,
  startMonth: normalizedRange.value.start.month as MonthNumbers,
  endYear: normalizedRange.value.end.year,
  endMonth: normalizedRange.value.end.month as MonthNumbers,
}))

const periods = computed<PeriodCell[]>(() =>
  generatePeriodRange(normalizedRange.value.start, normalizedRange.value.end)
)
const totalGrouping = ref<'range' | 'fiscal'>('range')
const minFiscalYear = 2024
const maxFiscalYear = DateTime.now().year + 2
const fiscalYearOptions = computed<number[]>(() =>
    Array.from({ length: maxFiscalYear - minFiscalYear + 1 }, (_, index) => minFiscalYear + index)
)
const currentFiscalYear = fiscalYearFrom(DateTime.now().year, DateTime.now().month)
const defaultFiscalYearStart = Math.max(minFiscalYear, currentFiscalYear - 1)
const defaultFiscalYearEnd = Math.min(maxFiscalYear, currentFiscalYear)
const selectedFiscalYearStart = ref(defaultFiscalYearStart)
const selectedFiscalYearEnd = ref(
    defaultFiscalYearStart === defaultFiscalYearEnd
        ? Math.min(maxFiscalYear, defaultFiscalYearEnd + 1)
        : defaultFiscalYearEnd
)
const activeFiscalYears = computed<number[]>(() => ([
    selectedFiscalYearStart.value,
    selectedFiscalYearEnd.value,
]))
const toggleYearlyComparison = () => {
    totalGrouping.value = totalGrouping.value === 'fiscal' ? 'range' : 'fiscal'
}

const rangeEnd = computed(() => normalizedRange.value.end)

const parsePeriod = (value: string): DateTime => {
  const dt = DateTime.fromFormat(`${value}-01`, 'yyyy-MM-dd', { zone: 'Asia/Tokyo' })
  return dt.isValid ? dt : DateTime.now().startOf('month')
}

const applyRange = (start: DateTime, end: DateTime) => {
  const normalized = normalizeRange(start, end)
  periodStart.value = normalized.start
  periodEnd.value = normalized.end
}

const handleRangeChange = ({ start, end }: { start: string; end: string }) => {
  const startDt = parsePeriod(start)
  const endDt = parsePeriod(end)
  applyRange(startDt, endDt)
}

const shiftRange = (months: number) => {
  applyRange(periodStart.value.plus({ months }), periodEnd.value.plus({ months }))
}
const categorizedProjects = computed(() => {
    const myProjects: Project[] = []
    const otherProjects: Project[] = []
    props.projects.forEach(project => {
        if (props.ownProjectIds.includes(project.id)) {
            myProjects.push(project)
        } else {
            otherProjects.push(project)
        }
    })
    return {
        myProjects,
        otherProjects
    }
})
const scrollIntoCurrent = () => {
    const currentPeriod = DateTime.now().toFormat('yyyy-MM')
    let scrollPosition = document.getElementById(currentPeriod)
    if (scrollPosition) {
        scrollPosition.scrollIntoView({ behavior: 'instant', block: 'end' });
    }
}
const selectedManagers = ref<number[]>([])
const mobileProjectKeywords = ref('')
const mobileManagerKeywords = ref('')
const loader = ref(true)
const badgeLoader = ref(0)
const tab = ref('table')
const route = useRoute()
const responsive = useResponsive()
const menu = useMenuStore()
const leftTab = ref<'project' | 'manager'>('project')
const auth = useAuthUserStore()
const rawDataByMonth = ref<Record<string, any>>({})
const dataByMonth = computed<Record<string, any>>(() => {
    if (includeForecastSettlement.value) return rawDataByMonth.value
    return filterProjectMonthData(rawDataByMonth.value)
})
const projectHasSettlementData = (projectName: string): boolean => {
    const months = dataByMonth.value?.[projectName]
    if (!months) return false
    return Object.values(months).some((entry: any) => hasSettlementEntry(entry?.settlement) && !entry?.settlement.is_forecast)
}
const rawPeriodTotals = ref<Record<string, PeriodTotalsEntry>>({})
const periodTotals = computed<Record<string, PeriodTotalsEntry>>(() => {
    if (includeForecastSettlement.value) return rawPeriodTotals.value
    return Object.fromEntries(
        Object.entries(rawPeriodTotals.value ?? {}).map(([period, totals]) => [
            period,
            filterScenarioRecord(totals),
        ])
    ) as Record<string, PeriodTotalsEntry>
})
const hasPeriodTotals = computed(() => Object.keys(periodTotals.value).length > 0)
const periodEntry = (period: string, scenario: ScenarioKey): UnitData => periodTotals.value[period]?.[scenario] ?? emptyUnit
const normalizeUnitData = (unit?: Partial<UnitData> | null): UnitData => {
    const sales = Number(unit?.sales ?? 0)
    const expense = Number(unit?.expense ?? 0)
    const explicitProfit = unit?.profit
    const profit = Number.isFinite(Number(explicitProfit)) ? Number(explicitProfit) : sales - expense
    const has_data = unit?.has_data ?? unit !== undefined
    const is_forecast = unit?.is_forecast ?? unit !== undefined
    return {
        sales,
        expense,
        profit,
        has_data,
        is_forecast
    }
}
const totalEntry = (projectName: string, scenario: ScenarioKey): UnitData => {
    if (scenario !== 'settlement' || includeForecastSettlement.value) {
        return normalizeUnitData(financeData.value?.[projectName]?.[scenario])
    }
    const months = rawDataByMonth.value?.[projectName]
    if (!months) return emptyUnit
    return aggregateScenarioUnits(
        Object.values(months).map((entry: any) => entry?.[scenario]),
        scenario
    )
}
const totalSummaryEntry = (scenario: ScenarioKey): UnitData => {
    if (scenario !== 'settlement' || includeForecastSettlement.value) {
        return normalizeUnitData(summarizeData.value?.[scenario])
    }
    return aggregateScenarioUnits(
        Object.values(rawPeriodTotals.value ?? {}).map((entry) => entry?.[scenario]),
        scenario
    )
}
const rawComparisonProjectTotals = ref<Record<string, Record<number, Record<ScenarioKey, UnitData>>>>({})
const rawComparisonSummaryTotals = ref<Record<number, Record<ScenarioKey, UnitData>>>({})
const rawComparisonProjectPeriods = ref<ComparisonProjectPeriods>({})
const rawComparisonPeriodTotals = ref<ComparisonPeriodTotals>({})
const storageProjectIdsKey = 'projectIds'
const allProjectIds = computed(() => props.projects.map(project => project.id))
const selectedProjectNames = computed(() => {
    return props.projects
        .filter(project => selectedProjects.value.includes(project.id))
        .map(project => project.name)
})
const normalizeScenarioTotals = (value: any): Record<ScenarioKey, UnitData> => ({
    yearly_plan: normalizeUnitData(value?.yearly_plan),
    profit: normalizeUnitData(value?.profit),
    settlement: normalizeUnitData(value?.settlement),
})
const aggregateScenarioUnits = (units: Array<Partial<UnitData> | UnitData | null | undefined>, scenario: ScenarioKey): UnitData => {
    let sales = 0
    let expense = 0
    let hasData = false
    let hasForecast = false
    let includedCount = 0

    units.forEach((rawUnit) => {
        if (!rawUnit) return
        const unit = scenario === 'settlement'
            ? filteredSettlementUnit(rawUnit)
            : rawUnit
        const normalized = normalizeUnitData(unit)
        if (!normalized.has_data) return
        sales += normalized.sales
        expense += normalized.expense
        hasData = true
        hasForecast = hasForecast || !!normalized.is_forecast
        includedCount++
    })

    if (!hasData) return emptyUnit

    return {
        sales,
        expense,
        profit: sales - expense,
        has_data: true,
        is_forecast: scenario === 'settlement' ? hasForecast && includedCount > 0 : false,
    }
}
const fiscalTotalEntry = (projectName: string, scenario: ScenarioKey, fiscalYear: number): UnitData => {
    if (scenario !== 'settlement' || includeForecastSettlement.value) {
        return rawComparisonProjectTotals.value?.[projectName]?.[fiscalYear]?.[scenario] ?? emptyUnit
    }
    const months = rawComparisonProjectPeriods.value?.[projectName]?.[fiscalYear]
    if (!months) return emptyUnit
    return aggregateScenarioUnits(
        Object.values(months).map((entry) => entry?.[scenario]),
        scenario
    )
}
const fiscalSummaryEntry = (scenario: ScenarioKey, fiscalYear: number): UnitData => {
    if (scenario !== 'settlement' || includeForecastSettlement.value) {
        return rawComparisonSummaryTotals.value?.[fiscalYear]?.[scenario] ?? emptyUnit
    }
    const periodsByYear = rawComparisonPeriodTotals.value?.[fiscalYear]
    if (!periodsByYear) return emptyUnit
    return aggregateScenarioUnits(
        Object.values(periodsByYear).map((entry) => entry?.[scenario]),
        scenario
    )
}
const commentCount = ref<Record<number, number>>({})
const api = useApi()
const possibleTypes = [{ value: 'sales', label: '売上' }, { value: 'expense', label: '販管費' }, { value: 'profit', label: '利益' }]
const possibleScenarios = [{ value: 'yearly_plan', label: '年度予算' }, { value: 'profit', label: '損益計画' }, { value: 'settlement', label: '実績' }]
const activeType = ref('sales')
const activeScenario = ref('yearly_plan')
const hasPrivilage = computed(() => {
    return auth.user?.position_id && auth.user?.position_id <= 6 || auth.activeUser.id === 610
})
const defaultProjectSelection = (projectIds: number[]) => {
    if (!projectIds.length) return []

    const savedProjectIds = localStorage.getItem(storageProjectIdsKey)
    if (savedProjectIds) {
        try {
            const parsedIds = JSON.parse(savedProjectIds)
            if (Array.isArray(parsedIds)) {
                const filteredIds = parsedIds
                    .map(id => Number(id))
                    .filter(id => projectIds.includes(id))
                if (filteredIds.length) return filteredIds
            }
        } catch {
            localStorage.removeItem(storageProjectIdsKey)
        }
    }

    if (hasPrivilage.value) {
        return [...projectIds]
    }

    const ownProjectIds = props.ownProjectIds.filter(id => projectIds.includes(id))
    return ownProjectIds.length ? ownProjectIds : [...projectIds]
}
const monthCount = computed(() => Math.round(normalizedRange.value.end.diff(normalizedRange.value.start, 'months').months ?? 0) + 1)
const sortMode = ref<'name' | 'manager'>('name')

const showTotals = computed(() => monthCount.value > 1)
const showComment = computed(() => hasPrivilage.value && monthCount.value === 1 && totalGrouping.value !== 'fiscal')
const activeFiscalYear = computed(() => 
    fiscalYearFrom(normalizedRange.value.end.year, normalizedRange.value.end.month)
)
type MetricDisplayKey = 'sales' | 'expense' | 'profit' | 'profit_rate'
const mobileMetricItems: Array<{ key: MetricDisplayKey; label: string }> = [
    { key: 'sales', label: '売上' },
    { key: 'expense', label: '販管費' },
    { key: 'profit', label: '利益' },
    { key: 'profit_rate', label: '利益率' },
]
const scenarioLabel = (scenario: ScenarioKey) =>
    scenarioOptions.find(option => option.value === scenario)?.label ?? scenario
const formatPeriodLabel = (period: PeriodCell) => `${period.year}/${pad2(period.month)}`
const compareUnitData = (
    baseUnit: UnitData | null | undefined,
    targetUnit: UnitData | null | undefined,
): UnitData => {
    const base = normalizeUnitData(baseUnit)
    const target = normalizeUnitData(targetUnit)

    return {
        sales: target.sales - base.sales,
        expense: target.expense - base.expense,
        profit: target.profit - base.profit,
        has_data: base.has_data || target.has_data,
        is_forecast: target.is_forecast,
    }
}
const mobileSummaryCaption = computed(() => {
    if (totalGrouping.value === 'fiscal') {
        const [baseYear, targetYear] = activeFiscalYears.value
        if (baseYear === undefined) return `FY${activeFiscalYear.value}`
        if (targetYear === undefined || targetYear === baseYear) return `FY${baseYear}`
        return `FY${baseYear} - FY${targetYear}`
    }
    return monthCount.value > 1 ? `${periodStartIso.value} - ${periodEndIso.value}` : periodStartIso.value
})
const mobileDetailCaption = computed(() =>
    totalGrouping.value === 'fiscal' ? '年度比較' : `${periods.value.length}ヶ月の推移`
)
const topSummaryBucketsTitle = computed(() => {
    if (totalGrouping.value === 'fiscal') {
        return '\u5e74\u5ea6\u5225\u96c6\u8a08'
    }
    return periods.value.length > 1 ? '\u671f\u9593\u5225\u96c6\u8a08' : '\u5bfe\u8c61\u6708\u96c6\u8a08'
})
const showTopSummaryBuckets = computed(() => totalGrouping.value === 'fiscal' || periods.value.length > 1)
const topSummaryBuckets = computed(() => {
    if (totalGrouping.value === 'fiscal') {
        return activeFiscalYears.value.map((fy) => ({
            key: `fy-${fy}`,
            label: `FY${fy}`,
            entry: fiscalSummaryEntry(mobileActiveScenario.value, fy),
        }))
    }
    return periods.value.map((period) => ({
        key: period.period,
        label: formatPeriodLabel(period),
        entry: periodEntry(period.period, mobileActiveScenario.value),
    }))
})

const metricNumericValue = (
    unit: UnitData | null | undefined,
    scenario: ScenarioKey,
    key: MetricDisplayKey,
) => {
    if (key === 'profit_rate') return percentizer(unit).value
    if (scenario === 'settlement') {
        return key === 'profit'
            ? settlementProfitValue(unit ?? undefined)
            : settlementValue(unit ?? undefined, key as Key)
    }
    return key === 'profit'
        ? Number(unit?.profit ?? (Number(unit?.sales ?? 0) - Number(unit?.expense ?? 0)))
        : Number(unit?.[key] ?? 0)
}
const comparisonDeltaDisplay = (
    left: UnitData | null | undefined,
    right: UnitData | null | undefined,
    scenario: ScenarioKey,
    key: MetricDisplayKey,
) => {
    const comparisonValue = metricNumericValue(left, scenario, key)
    const targetValue = metricNumericValue(right, scenario, key)

    if (Number.isNaN(comparisonValue) || Number.isNaN(targetValue)) return '—'

    const delta = targetValue - comparisonValue
    if (key === 'profit_rate') {
        return `${delta > 0 ? '+' : ''}${delta.toFixed(2)}pt`
    }

    if (comparisonValue === 0) {
        if (targetValue === 0) return '0.0%'
        return 'New'
    }

    if (comparisonValue !== 0) {
        const percent = (delta / Math.abs(comparisonValue)) * 100
        return `${percent > 0 ? '+' : ''}${percent.toFixed(1)}%`
    }
    
    return `${delta > 0 ? '+' : ''}${amountOfMoneyParser(delta)}`
}
const comparisonDeltaClass = (
    left: UnitData | null | undefined,
    right: UnitData | null | undefined,
    scenario: ScenarioKey,
    key: MetricDisplayKey,
) => {
    const comparisonValue = metricNumericValue(left, scenario, key)
    const targetValue = metricNumericValue(right, scenario, key)

    if (Number.isNaN(comparisonValue) || Number.isNaN(targetValue) || comparisonValue === targetValue) {
        return 'mobile-finance-compare-table__delta--neutral'
    }

    const improved = key === 'expense'
        ? targetValue < comparisonValue
        : targetValue > comparisonValue

    return improved
        ? 'mobile-finance-compare-table__delta--positive'
        : 'mobile-finance-compare-table__delta--negative'
}
const formatMillions = (value: number) => {
    if (Number.isNaN(value)) return '———'
    const abs = Math.abs(value)
    if (abs < 1_000_000) {
        return `¥${(value / 1_000).toFixed(0)}K`
    }
    return `¥${(value / 1_000_000).toFixed(1)}M`
}
const formatMobileMetric = (
    unit: UnitData | null | undefined,
    scenario: ScenarioKey,
    key: MetricDisplayKey,
) => {
    const value = metricNumericValue(unit, scenario, key)
    if (key === 'profit_rate') {
        return percentizer(unit).display === '—' ? '———' : percentizer(unit).display
    }
    return formatMillions(value)
}
const formatScenarioMetric = (
    unit: UnitData | null | undefined,
    scenario: ScenarioKey,
    key: MetricDisplayKey,
) => {
    if (key === 'profit_rate') {
        return percentizer(unit).display
    }
    if (scenario === 'settlement') {
        const settlementUnit = unit ?? undefined
        const value = key === 'profit'
            ? settlementProfitValue(settlementUnit)
            : settlementValue(settlementUnit, key as Key)
        return Number.isNaN(value) ? '—' : amountOfMoneyParser(value)
    }
    const rawValue = key === 'profit'
        ? Number(unit?.profit ?? (Number(unit?.sales ?? 0) - Number(unit?.expense ?? 0)))
        : Number(unit?.[key] ?? 0)
    return amountOfMoneyParser(rawValue)
}
const mobileProjectScenarioEntry = (projectName: string, scenario: ScenarioKey): UnitData =>
    totalGrouping.value === 'fiscal'
        ? compareUnitData(
            fiscalTotalEntry(projectName, scenario, selectedFiscalYearEnd.value),
            fiscalTotalEntry(projectName, scenario, selectedFiscalYearStart.value),
        )
        : totalEntry(projectName, scenario)
const mobileSummaryScenarioEntry = (scenario: ScenarioKey): UnitData =>
    totalGrouping.value === 'fiscal'
        ? compareUnitData(
            fiscalSummaryEntry(scenario, selectedFiscalYearEnd.value),
            fiscalSummaryEntry(scenario, selectedFiscalYearStart.value),
        )
        : totalSummaryEntry(scenario)
const scenarioPeriodEntry = (
    proj: { name: string; data: Record<string, any> },
    period: string,
    scenario: ScenarioKey,
): UnitData => normalizeUnitData(proj.data?.[period]?.[scenario])
const managers = computed(() => {
    const allManagers = props.projects.map(project => project.manager)
    const flatUsers = allManagers.flat()
    const uniqueUsers = flatUsers.filter((user, index, self) =>
        index === self.findIndex((u) => (
            u.id === user.id
        ))
    )
    return uniqueUsers
})
const filteredMobileProjects = computed(() => {
    const keyword = mobileProjectKeywords.value.trim().toLowerCase()
    if (!keyword) return props.projects
    return props.projects.filter(project => project.name?.toLowerCase().includes(keyword))
})
const filteredMobileManagers = computed(() => {
    const keyword = mobileManagerKeywords.value.trim().toLowerCase()
    if (!keyword) return managers.value
    return managers.value.filter(manager => manager.name?.toLowerCase().includes(keyword))
})
const allManagerIds = computed(() => managers.value.map(manager => manager.id))
const mobileProjectFilterButtons = computed(() => ([
    {
        title: '全選択',
        action: () => {
            selectedProjects.value = [...allProjectIds.value]
            mobileProjectKeywords.value = ''
            menu.close()
        }
    },
    {
        title: 'リセット',
        action: () => {
            selectedProjects.value = []
            mobileProjectKeywords.value = ''
            menu.close()
        }
    }
]))
const mobileManagerFilterButtons = computed(() => ([
    {
        title: '全選択',
        action: () => {
            selectedManagers.value = [...allManagerIds.value]
            mobileProjectKeywords.value = ''
            menu.close()
        }
    },
    {
        title: 'リセット',
        action: () => {
            selectedManagers.value = []
            mobileManagerKeywords.value = ''
            menu.close()
        }
    }
]))
const managerNameFor = (projectName: string) => {
    const proj = props.projects.find(p => p.name === projectName)
    const names = proj?.manager?.map(m => m.name).filter(Boolean) ?? []
    return names.length ? names.join(', ') : ''
}
const sortedProjectNames = computed(() => {
    const names = totalGrouping.value === 'fiscal'
        ? [...selectedProjectNames.value]
        : Object.keys(dataByMonth.value ?? {})
    return names.sort((a, b) => {
        if (sortMode.value === 'manager') {
            const ma = managerNameFor(a)
            const mb = managerNameFor(b)
            const cmp = ma.localeCompare(mb, 'ja')
            if (cmp !== 0) return cmp
        }
        return a.localeCompare(b, 'ja')
    })
})
const sortedProjects = computed(() =>
    sortedProjectNames.value.map(name => ({
        name,
        data: dataByMonth.value?.[name] ?? {},
    }))
)
const hasSelectedProjects = computed(() => selectedProjects.value.length > 0)
const selectProjectComment = (name: string) => {
    const findProject = props.projects.find(p => p.name === name)
    if (findProject) selectedId.value = findProject.id
}
const selectAllProjects = () => {
    selectedProjects.value = [...allProjectIds.value]
}
const openProjectFilter = () => {
    menu.setMenu({ parent: responsive.mobile ? 'mb-p-select' : 'projectFilter' })
}

const managersProjects = (manager: User) => {
    return props.projects.filter(project => project.manager.some(m => m.id === manager.id))
}

const toggleByManager = (event: Event, manager: User) => {
    const target = event.target as HTMLInputElement
    if (target.checked) {
        selectedProjects.value = managersProjects(manager).map(project => project.id)
    } else {
        selectedProjects.value = []
    }
}

const isChecked = (manager: User) => {
    const managerProjectIds = managersProjects(manager).map(project => project.id)
    return managerProjectIds.length === selectedProjects.value.length &&
        managerProjectIds.every(id => selectedProjects.value.includes(id)) &&
        selectedProjects.value.every(id => managerProjectIds.includes(id))
}


let activeFetchToken = 0

const resetFinanceSummaries = () => {
    rawFinanceData.value = {}
    rawSummarizeData.value = {
        profit: { expense: 0, sales: 0, profit: 0 },
        yearly_plan: { expense: 0, sales: 0, profit: 0 },
        settlement: { expense: 0, sales: 0, profit: 0 }
    }
    rawDataByMonth.value = {}
    rawPeriodTotals.value = {}
    variance.value = {}
    badgeLoader.value = 0
    commentCount.value = {}
    rawComparisonProjectTotals.value = {}
    rawComparisonSummaryTotals.value = {}
    rawComparisonProjectPeriods.value = {}
    rawComparisonPeriodTotals.value = {}
}

const fetchTotalFinance = async (token: number) => {
    if (totalGrouping.value !== 'range') {
        // resetFinanceSummaries()
        return
    }
    const payload = {
        projects: selectedProjects.value,
        interval: intervalPayload.value,
    }
    try {
        const data = await api.get('/get_total_finance', payload)
        if (token !== activeFetchToken) return
        rawFinanceData.value = data?.sumData ?? {}
        rawSummarizeData.value = data?.summarizeData ?? rawSummarizeData.value
        rawDataByMonth.value = data?.plan_res_data ?? {}
        rawPeriodTotals.value = data?.periodTotals ?? {}
    } catch (error) {
        if (token !== activeFetchToken) return
        throw error
    }
}

const fetchTotalFinanceBadge = async (token: number) => {
    try {
        const data = await api.get('/get_total_finance_badge', { interval: intervalPayload.value })
        if (token !== activeFetchToken) return
        variance.value = data ?? {}
        badgeLoader.value++
    } catch (error) {
        if (token !== activeFetchToken) return
        throw error
    }
}

const fetchCommentCounts = async (token: number) => {
    try {
        const data = await api.post('/get_comment_count_from_total', { projectIds: selectedProjects.value, period: periodStartIso.value })
        if (token !== activeFetchToken) return
        commentCount.value = data ?? {}
    } catch (error) {
        if (token !== activeFetchToken) return
        throw error
    }
}

const fiscalInterval = (fiscalYear: number) => ({
    startYear: fiscalYear,
    startMonth: 3,
    endYear: fiscalYear + 1,
    endMonth: 2,
})

const fetchYearlyComparisonTotals = async (token: number) => {
    if (totalGrouping.value !== 'fiscal') {
        rawComparisonProjectTotals.value = {}
        rawComparisonSummaryTotals.value = {}
        rawComparisonProjectPeriods.value = {}
        rawComparisonPeriodTotals.value = {}
        return
    }
    const years = activeFiscalYears.value
    const projectsByYear: Record<string, Record<number, Record<ScenarioKey, UnitData>>> = {}
    const summaryByYear: Record<number, Record<ScenarioKey, UnitData>> = {}
    const projectPeriodsByYear: ComparisonProjectPeriods = {}
    const periodTotalsByYear: ComparisonPeriodTotals = {}
    await Promise.all(years.map(async (fy) => {
        const data = await api.get('/get_total_finance', {
            projects: selectedProjects.value,
            interval: fiscalInterval(fy),
        })
        if (token !== activeFetchToken) return
        const sumData = data?.sumData ?? {}
        const planResData = data?.plan_res_data ?? {}
        const periodTotals = data?.periodTotals ?? {}
        selectedProjectNames.value.forEach((name) => {
            projectsByYear[name] ||= {}
            projectsByYear[name][fy] = normalizeScenarioTotals(sumData?.[name])
            projectPeriodsByYear[name] ||= {}
            projectPeriodsByYear[name][fy] = planResData?.[name] ?? {}
        })
        summaryByYear[fy] = normalizeScenarioTotals(data?.summarizeData)
        periodTotalsByYear[fy] = periodTotals
    }))
    if (token !== activeFetchToken) return
    rawComparisonProjectTotals.value = projectsByYear
    rawComparisonSummaryTotals.value = summaryByYear
    rawComparisonProjectPeriods.value = projectPeriodsByYear
    rawComparisonPeriodTotals.value = periodTotalsByYear
}

const refreshTotalFinance = async () => {
    const token = ++activeFetchToken
    if (!selectedProjects.value.length) {
        loader.value = false
        resetFinanceSummaries()
        return
    }
    loader.value = true
    badgeLoader.value = 0
    try {
        await fetchTotalFinance(token)
        await fetchYearlyComparisonTotals(token)
        fetchTotalFinanceBadge(token)
        fetchCommentCounts(token)
        scrollIntoCurrent()
    } finally {
        if (token === activeFetchToken) {
            loader.value = false
        }
    }
}

watch(selectedProjects, (projects) => {
    localStorage.setItem(storageProjectIdsKey, JSON.stringify(projects))
    refreshTotalFinance()
}, { deep: true })

watch(allProjectIds, (projectIds, previousProjectIds = []) => {
    if (!projectIds.length) return

    const hadAllSelected =
        previousProjectIds.length > 0 &&
        selectedProjects.value.length === previousProjectIds.length &&
        previousProjectIds.every(id => selectedProjects.value.includes(id))

    const hadOwnSelected =
        previousProjectIds.length > 0 &&
        props.ownProjectIds.filter(id => previousProjectIds.includes(id)).length === selectedProjects.value.length &&
        selectedProjects.value.every(id => props.ownProjectIds.includes(id))

    if (!selectedProjects.value.length) {
        selectedProjects.value = defaultProjectSelection(projectIds)
        return
    }

    if (hasPrivilage.value && hadAllSelected) {
        selectedProjects.value = [...projectIds]
        return
    }

    if (!hasPrivilage.value && hadOwnSelected) {
        selectedProjects.value = props.ownProjectIds.filter(id => projectIds.includes(id))
        return
    }

    selectedProjects.value = selectedProjects.value.filter(id => projectIds.includes(id))
}, { immediate: true })

watch([periodStartIso, periodEndIso], () => {
    refreshTotalFinance()
})
watch(totalGrouping, () => {
    refreshTotalFinance()
})
watch([selectedFiscalYearStart, selectedFiscalYearEnd], ([start, end], [previousStart, previousEnd]) => {
    if (start === end) {
        if (start !== previousStart) {
            selectedFiscalYearEnd.value = fiscalYearOptions.value.find((year) => year !== start) ?? end
            return
        }
        if (end !== previousEnd) {
            selectedFiscalYearStart.value = [...fiscalYearOptions.value].reverse().find((year) => year !== end) ?? start
            return
        }
    }
    if (totalGrouping.value === 'fiscal') {
        refreshTotalFinance()
    }
})
watch(selectedManagers, (managers) => {
    if (managers.length) {
        const set = new Set(managers)
        selectedProjects.value = props.projects.filter(p => Array.isArray(p.manager) && p.manager.some(m => set.has(m.id)))
        .map(p => p.id);
    } else {
        selectedProjects.value = []
    }
})
// watch(
//   [() => props.projects, () => selectedManagers.value],
//   ([projects, managers]) => {
//     if (!projects || projects.length === 0) return; // wait until loaded
//     console.log('hoho')
//     if (managers.length) {
//       const set = new Set(managers);
//       selectedProjects.value = projects
//         .filter(p => Array.isArray(p.manager) && p.manager.some(m => set.has(m.id)))
//         .map(p => p.id);
//     } else {
//       selectedProjects.value = []
//     }

//     refreshTotalFinance();
//   },
//   { deep: true, immediate: true }
// );

const badge = useBadgeStore()
const financeTotalBadge = (name: string) => {
    const findProject = props.projects.find(p => p.name === name)
    return badge.financeCommentBadgeByFilter({by: 'project_id', value: Number(findProject?.id)})?.period_counts || {}
}
const get_finance_comment_counts = async () => {
    await fetchCommentCounts(activeFetchToken)
}
const selectedBadge = computed(() => {
  const selected = new Set(selectedProjects.value)

  let total_unread = 0
  const period_counts: Record<string, number> = {}

  for (const proj of badge.finance_comment.projects ?? []) {
    if (!selected.has(proj.project_id)) continue

    total_unread += Number(proj.total_unread ?? 0)

    for (const [period, count] of Object.entries(proj.period_counts ?? {})) {
      period_counts[period] = (period_counts[period] ?? 0) + Number(count ?? 0)
    }
  }

  return { total_unread, period_counts }
})
</script>

<style scoped lang="scss">
.badge-count {
    background: #F28C28;
    color: white;
    border-radius: 999px;
    padding: 1px 6px;
    font-size: 10px;
    line-height: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 15px;
    height: 15px;
}
.finance-top-summary {
    border-radius: 18px;
    background: var(--bg3);
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.finance-top-summary__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
}

.finance-top-summary__title {
    font-size: 18px;
    font-weight: 700;
    line-height: 1.3;
}

.finance-top-summary__caption {
    font-size: 12px;
    color: var(--primary-color);
    opacity: 0.78;
}

.finance-top-summary__switches {
    display: flex;
    gap: 8px;
    overflow-x: auto;
    max-width: 100%;
}

.finance-top-summary__kpis {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 10px;
}

.finance-top-summary__periods {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.finance-top-summary__periods-header {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: 12px;
}

.finance-top-summary__periods-header h4 {
    font-size: 13px;
    font-weight: 600;
}

.finance-top-summary__period-scroll {
    display: flex;
    gap: 12px;
    overflow-x: auto;
    padding-bottom: 4px;
}

.finance-top-summary__period-card {
    flex: 0 0 220px;
    border: 1px solid var(--calendarBorder);
    border-radius: 14px;
    background: var(--background-color);
    padding: 12px;
}

.finance-top-summary__period-title {
    font-size: 12px;
    font-weight: 600;
    margin-bottom: 8px;
}

.finance-top-summary__period-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 8px 12px;
}
.mobile-finance-list {
    flex: 1 1 auto;
    min-height: 0;
    overflow: auto;
    padding: 0 16px 24px;
    display: flex;
    flex-direction: column;
    gap: 14px;
    height: 100%;
}
.mobile-finance-hero {
    display: flex;
    flex-direction: column;
    gap: 14px;
}
.mobile-finance-hero__switches {
    display: inline-flex;
    align-self: flex-end;
    max-width: 100%;
    padding: 4px;
    border: 1px solid var(--calendarBorder);
    border-radius: 18px;
    background: var(--bg3);
    overflow-x: auto;
}
.mobile-finance-toolbar {
    position: sticky;
    top: 0;
    z-index: 6;
    display: flex;
    gap: 8px;
    overflow: auto;
    margin-bottom: 2px;
    min-height: fit-content;
}

.mobile-finance-chip {
    border: 1px solid var(--calendarBorder);
    background: var(--bg3);
    color: var(--primary-color);
    border-radius: 999px;
    padding: 5px 10px;
    font-size: 12px;
    white-space: nowrap;
}
.mobile-finance-chip--segmented {
    border-color: transparent;
    background: transparent;
    padding: 8px 16px;
    border-radius: 14px;
}
.mobile-finance-chip__icon {
    margin-left: 4px;
    font-size: 11px;
}

.mobile-finance-chip--active {
    background: var(--primary-color);
    color: var(--background-color);
    border-color: var(--primary-color);
}
.mobile-finance-compare-card,
.mobile-finance-compare-project {
    border: 1px solid var(--calendarBorder);
    border-radius: 18px;
    background: var(--bg3);
}
.mobile-finance-compare-card__title {
    padding: 20px 20px 6px;
    font-size: 24px;
    line-height: 1.15;
    font-weight: 700;
    letter-spacing: 0.02em;
}
.mobile-finance-compare-card__caption {
    padding: 18px 20px 14px;
    font-size: 14px;
    font-weight: 600;
    color: var(--primary-color);
    opacity: 0.72;
}
.mobile-finance-compare-table {
    display: grid;
    grid-template-columns: minmax(72px, 0.9fr) repeat(3, minmax(0, 1fr));
    border-top: 1px solid var(--calendarBorder);
}
.mobile-finance-compare-table__head,
.mobile-finance-compare-table__label,
.mobile-finance-compare-table__value,
.mobile-finance-compare-table__delta {
    padding: 16px 12px;
    border-top: 1px solid var(--calendarBorder);
}
.mobile-finance-compare-table__head {
    border-top: 0;
    font-size: 12px;
    text-align: right;
    line-height: 1.45;
    color: var(--primary-color);
    opacity: 0.78;
}
.mobile-finance-compare-table__label {
    font-size: 13px;
    font-weight: 600;
}
.mobile-finance-compare-table__value {
    font-size: 13px;
    text-align: right;
    font-weight: 700;
}
.mobile-finance-compare-table__delta {
    font-size: 13px;
    text-align: right;
    font-weight: 700;
}
.mobile-finance-compare-table__delta--positive {
    color: green;
}
.mobile-finance-compare-table__delta--negative {
    color: tomato;
}
.mobile-finance-compare-table__delta--neutral {
    color: var(--primary-color);
    opacity: 0.7;
}
.mobile-finance-section-title {
    padding: 4px 2px 0;
    font-size: 16px;
    font-weight: 700;
    letter-spacing: 0.04em;
}
.mobile-finance-compare-project__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    padding: 18px 18px 14px;
}
.mobile-finance-compare-project__grid {
    padding: 0 16px 16px;
}
.mobile-finance-compare-table--project {
    grid-template-columns: minmax(72px, 0.9fr) repeat(2, minmax(0, 1fr));
    border-top: 0;
}
.mobile-finance-compare-table--project .mobile-finance-compare-table__head,
.mobile-finance-compare-table--project .mobile-finance-compare-table__label,
.mobile-finance-compare-table--project .mobile-finance-compare-table__value {
    padding-top: 14px;
    padding-bottom: 14px;
}
.mobile-finance-compare-table--project .mobile-finance-compare-table__head {
    background: transparent;
}
.mobile-finance-compare-table--project .mobile-finance-compare-table__value {
    font-weight: 400;
}

.mobile-finance-card {
    flex: 0 0 auto;
    border-radius: 18px;
    background: var(--bg3);
    overflow: hidden;
}

.mobile-finance-card--summary {
    border-color: var(--hoverBorder);
}

.mobile-finance-card__summary {
    list-style: none;
    cursor: pointer;
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.mobile-finance-card__summary::-webkit-details-marker {
    display: none;
}

.mobile-finance-card__header,
.mobile-finance-card__meta {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
}

.mobile-finance-card__title {
    font-size: 15px;
    font-weight: 600;
    line-height: 1.4;
}

.mobile-finance-card__caption,
.mobile-finance-card__meta,
.mobile-finance-section__caption,
.mobile-finance-detail-metric span,
.mobile-finance-detail-row__label,
.mobile-finance-kpi__label {
    font-size: 11px;
    color: var(--primary-color);
    opacity: 0.78;
}

.mobile-finance-card__meta {
    align-items: center;
}

.mobile-finance-card__badges {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 8px;
    flex-wrap: wrap;
}

.mobile-finance-alert {
    border-radius: 999px;
    background: rgba(242, 140, 40, 0.16);
    color: #c66a0a;
    padding: 4px 8px;
    font-size: 11px;
    font-weight: 600;
}

.mobile-finance-comment {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    border: 1px solid var(--calendarBorder);
    border-radius: 999px;
    background: var(--background-color);
    color: var(--primary-color);
    padding: 5px 9px;
    font-size: 11px;
}

.mobile-finance-kpis {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
}

.mobile-finance-kpi {
    border: 1px solid var(--calendarBorder);
    border-radius: 14px;
    background: rgba(255, 255, 255, 0.35);
    padding: 10px 12px;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.mobile-finance-kpi__value {
    font-size: 14px;
    line-height: 1.3;
}

.mobile-finance-card__body {
    border-top: 1px solid var(--calendarBorder);
    padding: 0 16px 16px;
}
.mobile-finance-card[open] {
    border: 1px solid var(--primary-color);
}
.mobile-finance-section {
    padding-top: 14px;
}

.mobile-finance-section + .mobile-finance-section {
    margin-top: 14px;
    border-top: 1px solid var(--calendarBorder);
}

.mobile-finance-section__header {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 10px;
}

.mobile-finance-section__header h4 {
    font-size: 13px;
    font-weight: 600;
}

.mobile-finance-detail-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.mobile-finance-detail-row {
    border: 1px solid var(--calendarBorder);
    border-radius: 14px;
    padding: 10px 12px;
    background: var(--background-color);
}

.mobile-finance-detail-row__metrics {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 8px 12px;
    margin-top: 8px;
}

.mobile-finance-detail-metric {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.mobile-finance-detail-metric strong {
    font-size: 13px;
    line-height: 1.3;
}
.project-selector-left {
    display: flex;
    flex-direction: column;
    user-select: none;
    line-height: 1.5;
}

.mobile-filter-panel {
    display: flex;
    flex-direction: column;
    gap: 12px;
    padding: 12px;
}

.mobile-filter-panel__header {
    position: sticky;
    top: 40px;
    z-index: 2;
    display: flex;
    flex-direction: column;
    gap: 12px;
    background: var(--bg3);
    padding-bottom: 8px;
}

.mobile-filter-panel__list {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.mobile-filter-panel__item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px;
    border-radius: 6px;
    cursor: pointer;
}

.mobile-filter-panel__item:hover {
    background: var(--secondary-background);
}

.mobile-filter-panel__empty {
    padding: 24px 16px;
    text-align: center;
    color: gray;
}
.top-border {
    border-top: solid thin var(--calendarBorder);
}
table {
    box-sizing: border-box !important;
    --first-col-width: 150px;
    --second-col-width: 150px;
    --third-col-width: 75px;
    width: max-content;
    min-width: 100%;
    border-collapse: separate;
    font-size: 13px;
    line-height: 1.5;
    white-space: nowrap;
    thead {
        position: sticky;
        top: 0;
        z-index: 6;
        
        th {
            padding: 10px;
            font-weight: 500;
            text-align: left;
            background-color: var(--bg3);
            border-bottom: 1px solid var(--calendarBorder);
        }
        th:nth-of-type(4n + 4) {
            border-right: 1px solid var(--calendarBorder);
        }
        th.sticky-left,
        th.sticky-right {
            z-index: 6;
        }
        th.sticky-left:first-of-type{
            z-index: 8;
        }
        th.sticky-left:nth-of-type(2n + 2){
            z-index: 7;
        }
    }

    tbody {
        tr {
            
            td {
                padding: 10px;
                border-bottom: 1px solid var(--calendarBorder);
                font-weight: 400;
                text-align: left;
                border-left: none;
                box-sizing: border-box !important;
                span {
                    display: block;
                }
            }
            td.sticky-left,
            td.sticky-right {
                background-color: var(--background-color);
            }
            td:last-of-type {
                border-right: solid thin var(--calendarBorder);
            }
        }
    }
}
td[data-cell=right-border], th[data-cell=right-border] {
    border-right: 1px solid var(--calendarBorder);
}
.finance-table-scroll {
    overflow: auto;
    height: calc(100% - 95px);
}

.finance-empty-state {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 280px;
    height: calc(100% - 95px);
    padding: 24px;
    position: absolute;
    width: 100%;
}

.finance-empty-state__inner {
    width: min(460px, 100%);
    text-align: center;
    display: flex;
    flex-direction: column;
    gap: 14px;
    padding: 32px 28px;
    border: 1px solid var(--calendarBorder);
    background: var(--bg3);
}

.finance-empty-state__title {
    font-size: 16px;
    font-weight: 600;
}

.finance-empty-state__text {
    font-size: 13px;
    line-height: 1.6;
    opacity: 0.8;
}

.finance-empty-state__actions {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    flex-wrap: wrap;
}

.finance-empty-state__button {
    border: 1px solid var(--normalBorder);
    padding: 8px 14px;
    font-size: 13px;
    transition: border-color 0.2s ease, background-color 0.2s ease;
}

.finance-empty-state__button:hover {
    border-color: var(--hoverBorder);
}

.finance-empty-state__button--primary {
    background: var(--primary-color);
    color: var(--background-color);
    border-color: var(--primary-color);
}

.sticky-left {
    position: sticky;
    left: 0;
    z-index: 2;
}

.first-col {
    left: 0;
    min-width: var(--first-col-width);
    max-width: var(--first-col-width);
    border-left: solid thin var(--calendarBorder);
    border-right: solid thin var(--calendarBorder);
    z-index: 4;
}

.second-col {
    left: calc(var(--first-col-width) + 22px);
    min-width: var(--second-col-width);
    border-right: solid thin var(--calendarBorder);
    z-index: 3;
}
.third-col {
    left: calc(var(--second-col-width) + var(--first-col-width) + 43px);
    min-width: var(--third-col-width);
    border-right: solid thin var(--calendarBorder);
    z-index: 3;
}
.sticky-right {
    // position: sticky;
    // right: 0;
    min-width: 80px;
    max-width: 80px;
    z-index: 3;
}

.comment-cell {
    min-width: 80px;
    max-width: 80px;
}

.summary-row {
    background-color: var(--bg3);
    font-weight: 600;
}

.summary-row .sticky-left,
.summary-row .sticky-right {
    background-color: inherit;
}

.p-name {
    min-width: var(--first-col-width);
    max-width: var(--first-col-width);
    border-right: solid thin var(--calendarBorder);
    border-left: solid thin var(--calendarBorder);
    white-space: break-spaces;
}
.m-name {
    min-width: var(--second-col-width);
    max-width: var(--second-col-width);
    border-right: solid thin var(--calendarBorder);
    white-space: break-spaces;
}
.sub-name {
    min-width: var(--third-col-width);
}
.manager-note {
    font-size: 11px;
    color: var(--primary-color);
    opacity: 0.8;
    line-height: 1.2;
}

.h-cell {
    width: 60px;
    min-width: 60px;
    background-color: var(--bg3);
    border-bottom: none;
    text-align: end;
    position: sticky;
    left: 0;
    z-index: 1;
}

.period-caption {
    font-size: 11px;
    color: var(--primary-color);
    opacity: 0.75;
    line-height: 1.2;
}

.projectModalSideMenu {
    overflow: auto;
    height: 100%;
    border-right: solid thin var(--bg3);
}

.p-pie-chart {
    width: 100%;
    display: flex;
    justify-content: center;
    margin: auto;
}

@media screen and (max-width: 959px) {
    .mobile-finance-compare-table {
        grid-template-columns: minmax(64px, 0.85fr) repeat(3, minmax(0, 1fr));
    }
    .mobile-finance-compare-table--project {
        grid-template-columns: minmax(64px, 0.85fr) repeat(2, minmax(0, 1fr));
    }
    .mobile-finance-compare-table__head,
    .mobile-finance-compare-table__label,
    .mobile-finance-compare-table__value,
    .mobile-finance-compare-table__delta {
        padding: 14px 10px;
        align-self: center;
    }

    .finance-top-summary__kpis {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .finance-top-summary__period-card {
        flex-basis: 240px;
    }
    .p-pie-chart {
        width: 80%;
    }

    .finance-table-scroll {
        height: calc(100% - 170px);
    }

    .finance-empty-state {
        height: calc(100% - 170px);
        min-height: 220px;
        width: auto;
    }

    .projectModalSideMenu {
        position: absolute;
        z-index: 5;
        max-height: 60vh;
        height: auto;
        width: 80%;
        left: 20px;
        background: var(--bg3);
        box-shadow: rgba(0, 0, 0, 0.35) 0px 5px 15px;
        z-index: 10;
        top: 45px;
    }
}
</style>
