<template>
    <div class="overlay">
        <div class="projectModalInner" style="width: 100%;height: 100%;">
            <div class="projectModalMainHeader !bg-[var(--bg3)]">
                <div class="flex flex-col ml-[30px]">
                    <p>集計</p>
                </div>
                <div class="flex items-center justify-center w-[60px] h-[60px] min-w-[60px] ml-auto cursor-pointer"
                    @click="router.back()">
                    <CloseIcon size="13" />
                </div>
            </div>
            <div class="projectModalContainer relative">
                <div class="mobile px-[20px] mt-[20px] mb-[5px]">
                    <LoaderButton :loading="false" content="プロジェクト選択" style="margin: 0;"
                        @click.stop="menu.setMenu({ parent: 'mb-p-select' })" />
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
                        <label v-for="project in projects" :title="project.name"
                            class="flex items-center gap-[15px] text-[14px] cursor-pointer">
                            <input type="checkbox" name="project-selector" :value="project.id"
                                v-model="selectedProjects">
                            <span class="text-[13px] overflow-hidden whitespace-nowrap text-ellipsis">{{ project.name }}</span>
                        </label>
                    </div>
                    <div v-if="leftTab == 'manager'" class="project-selector-left">
                        <div v-for="manager in managers">
                            <label class="flex items-center gap-[15px] text-[14px] cursor-pointer">
                                <input type="checkbox" name="project-selector-by-manager"
                                    v-model="selectedManagers" :value="manager.id">
                                {{ manager.name }}
                            </label>
                        </div>
                    </div>
                </div>

                <div class="projectModalContent relative" style="overflow: hidden;">
                    <div class="cal-month-loader" style="height: 100%; top: 0;opacity: 0.6;" v-if="loader">
                        <div id="loaderMini">
                            <div class="spinner-mini"
                                style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
                        </div>
                    </div>
                    <div class="h-full">
                        <div
                            class="sticky top-0 bg-[var(--background-color)] z-[7] min-h-[60px] flex justify-between items-center px-[20px] flex-wrap gap-[10px] pb-[20px] after:flex-auto after:content-['']">
                            <div class="sub-tab-container">
                                <button @click="tab = 'table'"
                                    :class="['sub-tab-item', { 'selected-sub-tab': tab == 'table' }]">テーブル</button>
                                <button @click="tab = 'pie'"
                                    :class="['sub-tab-item', { 'selected-sub-tab': tab == 'pie' }]">円グラフ</button>
                                <button @click="tab = 'bar'"
                                    :class="['sub-tab-item', { 'selected-sub-tab': tab == 'bar' }]">棒グラフ</button>
                            </div>
                            <div v-if="tab === 'table'" class="flex items-center gap-2 text-sm">
                                <label class="text-xs opacity-70">並び替え:</label>
                                <select v-model="sortMode" class="text-[var(--primary-color)] px-2 py-1 bg-[var(--background-color)] text-sm">
                                    <option value="name">プロジェクト名</option>
                                    <option value="manager">管理者</option>
                                </select>
                            </div>
                            <div class="flex items-center gap-[20px] relative w-full justify-center md:justify-end flex-wrap md:flex-nowrap">
                                <div v-if="tab === 'table'" class="flex items-center gap-2 text-xs flex-wrap md:justify-end justify-center">
                                    <button
                                        type="button"
                                        class="px-2 py-1 border border-solid border-[var(--normalBorder)] hover:border-[var(--hoverBorder)] transition"
                                        @click="toggleYearlyComparison"
                                    >
                                        {{ totalGrouping === 'fiscal' ? '月次表示' : '年度比較' }}
                                    </button>
                                    <span v-if="totalGrouping === 'fiscal'" class="opacity-70">
                                        比較: FY{{ activeFiscalYears[0] }} / FY{{ activeFiscalYears[1] }} / FY{{ activeFiscalYears[2] }}
                                    </span>
                                </div>
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
                        
                        <div class="finance-table-scroll" v-if="tab == 'table'">
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
                                                FY{{ fy }}
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
                                        <template v-else-if="showTotals || isMobile()">
                                            <th class="totals-head">売上</th>
                                            <th class="totals-head">販管費</th>
                                            <th class="totals-head">利益</th>
                                            <th class="totals-head">利益率</th>
                                        </template>
                                    </tr>
                                </thead>
                                <!-- <tbody> -->
                                    
                                    <tbody v-for="proj in sortedProjects">
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
                                            <template v-for="p in periods" :key="p.period" v-if="!isMobile() && totalGrouping !== 'fiscal'">
                                                
                                                <td>
                                                    <div class="inner-col"><span class="mobile">売上</span>{{
                                                        amountOfMoneyParser(proj.data?.[p.period]?.yearly_plan.sales) }}</div>
                                                </td>
                                                <td>
                                                    <div class="inner-col"><span class="mobile">販管費</span>{{
                                                        amountOfMoneyParser(proj.data?.[p.period]?.yearly_plan.expense) }}</div>
                                                </td>
                                                <td>
                                                    <div class="inner-col"><span class="mobile">利益</span>{{
                                                        amountOfMoneyParser(proj.data?.[p.period]?.yearly_plan.sales -
                                                        proj.data?.[p.period]?.yearly_plan.expense) }}</div>
                                                    </td>
                                                <td data-cell="right-border">
                                                    <div class="inner-col"><span class="mobile">利益率</span>{{
                                                        percentizer(proj.data?.[p.period]?.yearly_plan).display }}</div>
                                                </td>
                                            </template>
                                            <template v-if="totalGrouping === 'fiscal'">
                                                <template v-for="fy in activeFiscalYears" :key="`total-yearly-${proj.name}-${fy}`">
                                                    <td>
                                                        <div class="inner-col"><span class="mobile">売上</span>{{
                                                            amountOfMoneyParser(fiscalTotalEntry(proj.name, 'yearly_plan', fy).sales)
                                                        }}</div>
                                                    </td>
                                                    <td>
                                                        <div class="inner-col"><span class="mobile">販管費</span>{{
                                                            amountOfMoneyParser(fiscalTotalEntry(proj.name, 'yearly_plan', fy).expense)
                                                        }}</div>
                                                    </td>
                                                    <td>
                                                        <div class="inner-col"><span class="mobile">利益</span>{{
                                                            amountOfMoneyParser(fiscalTotalEntry(proj.name, 'yearly_plan', fy).profit)
                                                        }}</div>
                                                    </td>
                                                    <td data-cell="right-border">
                                                        <div class="inner-col"><span class="mobile">利益率</span>{{
                                                            percentizer(fiscalTotalEntry(proj.name, 'yearly_plan', fy)).display
                                                        }}</div>
                                                    </td>
                                                </template>
                                            </template>
                                            <template v-else-if="showTotals || isMobile()">
                                                <td>
                                                    <div class="inner-col"><span class="mobile">売上</span>{{
                                                        amountOfMoneyParser(totalEntry(proj.name, 'yearly_plan').sales)
                                                    }}</div>
                                                </td>
                                                <td>
                                                    <div class="inner-col"><span class="mobile">販管費</span>{{
                                                        amountOfMoneyParser(totalEntry(proj.name, 'yearly_plan').expense)
                                                    }}</div>
                                                </td>
                                                <td>
                                                    <div class="inner-col"><span class="mobile">利益</span>{{
                                                        amountOfMoneyParser(totalEntry(proj.name, 'yearly_plan').profit)
                                                    }}</div>
                                                </td>
                                                <td>
                                                    <div class="inner-col"><span class="mobile">利益率</span>{{
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
                                                class="m-name sticky-left first-col"
                                                :rowspan="visibleScenarioCount"
                                            >
                                                <div v-if="managerNameFor(proj.name)">
                                                    {{ managerNameFor(proj.name) }}
                                                </div>
                                            </td>
                                            <td class="sub-name sticky-left third-col">
                                                <span>損益計画</span>
                                            </td>
                                            <template v-for="p in periods" :key="p.period" v-if="!isMobile() && totalGrouping !== 'fiscal'">
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
                                            <template v-else-if="showTotals || isMobile()">
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
                                                <div v-if="showAnyArrow(proj.name as string)" class="flex" title="計画との差が大きい月です">
                                                    <svg fill="tomato" style="transform: rotate(180deg);" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 30 30">
                                                        <path d="M14.978 0C6.735-.055-.129 6.931.002 15.153c-.028 8.166 6.815 14.939 14.976 14.811v-.04c.965.012 1.935-.068 2.889-.243 4.817-.861 9.056-4.274 10.937-8.8C32.986 11.04 25.688-.021 14.978 0m0 27.903C6.08 27.659-.075 18.755 3.433 10.373 7.813.292 22.129.294 26.49 10.385c3.512 8.225-2.605 17.404-11.512 17.518m-1.735-13.968c-.293 2.283-.156 4.58-.125 6.873l.166 2.289c.304 2.068 3.234 2.088 3.548 0 .186-1.523.193-3.051.205-4.58.028-1.53.044-3.058-.164-4.582-.334-2.082-3.284-2.104-3.63 0m-.344-4.565c.115.303.278.565.465.811.473.371 1.062.634 1.685.627 1.248.021 2.335-1.09 2.278-2.331-.015-.643-.308-1.218-.729-1.681-1.906-1.558-4.534.238-3.699 2.574"/>
                                                    </svg>
                                                </div>
                                                実績
                                            </td>
                                            <template v-for="p in periods" :key="p.period" v-if="!isMobile() && totalGrouping !== 'fiscal'">
                                                <td>
                                                    <div class="flex items-center gap-[5px]">
                                                        <div class="inner-col"><span class="mobile">売上</span>{{
                                                            amountOfMoneyParser(settlementValue(proj.data?.[p.period]?.settlement, 'sales')) }}</div>
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
                                            <template v-else-if="showTotals || isMobile()">
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
                                            <template v-for="p in periods" :key="`summary-yearly-${p.period}`" v-if="!isMobile() && totalGrouping !== 'fiscal'">
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
                                            <template v-else-if="showTotals || isMobile()">
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
                                            <template v-for="p in periods" :key="`summary-profit-${p.period}`" v-if="!isMobile() && totalGrouping !== 'fiscal'">
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
                                            <template v-else-if="showTotals || isMobile()">
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
                                            <template v-for="p in periods" :key="`summary-settlement-${p.period}`" v-if="!isMobile() && totalGrouping !== 'fiscal'">
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
                                            <template v-else-if="showTotals || isMobile()">
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
                                <!-- </tbody> -->



                            </table>

                        </div>
                        <div class="overflow-auto h-[calc(100%-115px)]" v-if="tab == 'pie' || tab == 'bar'">
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
import { computed, onMounted, ref, watch } from 'vue';
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
import { isMobile } from '@/utils/tools';
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
}
type ScenarioKey = 'yearly_plan' | 'profit' | 'settlement'
type PeriodTotalsEntry = {
    year?: number
    month?: number
    yearly_plan?: UnitData
    profit?: UnitData
    settlement?: UnitData
}
const THRESHOLD = 10;
const emptyUnit: UnitData = {
    expense: 0,
    sales: 0,
    profit: 0,
    has_data: false,
}

type Key = 'sales' | 'expense' | 'profit';
const hasSettlementEntry = (unit?: UnitData | null) => unit?.has_data === true
const settlementValue = (unit: UnitData | undefined, key: Key) => hasSettlementEntry(unit) ? Math.round(Number(unit?.[key] ?? 0)) : NaN
const settlementProfitValue = (unit: UnitData | undefined) => hasSettlementEntry(unit) ? Math.round(Number(unit?.profit ?? 0)) : NaN

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
const financeData = ref<YearlyFinancialData>({})
const variance = ref<Record<string, { sales: number | null; expenses: number | null; profit: number | null }>>({})
const summarizeData = ref<{
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
const activeFiscalYear = computed(() =>
    fiscalYearFrom(normalizedRange.value.end.year, normalizedRange.value.end.month)
)
const activeFiscalYears = computed<number[]>(() => ([
    activeFiscalYear.value - 1,
    activeFiscalYear.value,
    activeFiscalYear.value + 1,
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
const loader = ref(true)
const badgeLoader = ref(0)
const tab = ref('table')
const route = useRoute()
const responsive = useResponsive()
const menu = useMenuStore()
const leftTab = ref<'project' | 'manager'>('project')
const auth = useAuthUserStore()
const dataByMonth = ref<Record<string, any>>({})
const projectHasSettlementData = (projectName: string): boolean => {
    const months = dataByMonth.value?.[projectName]
    if (!months) return false
    return Object.values(months).some((entry: any) => hasSettlementEntry(entry?.settlement))
}
const periodTotals = ref<Record<string, PeriodTotalsEntry>>({})
const hasPeriodTotals = computed(() => Object.keys(periodTotals.value).length > 0)
const periodEntry = (period: string, scenario: ScenarioKey): UnitData => periodTotals.value[period]?.[scenario] ?? emptyUnit
const normalizeUnitData = (unit?: Partial<UnitData> | null): UnitData => {
    const sales = Number(unit?.sales ?? 0)
    const expense = Number(unit?.expense ?? 0)
    const explicitProfit = unit?.profit
    const profit = Number.isFinite(Number(explicitProfit)) ? Number(explicitProfit) : sales - expense
    const has_data = unit?.has_data ?? unit !== undefined
    return {
        sales,
        expense,
        profit,
        has_data,
    }
}
const totalEntry = (projectName: string, scenario: ScenarioKey): UnitData =>
    normalizeUnitData(financeData.value?.[projectName]?.[scenario])
const totalSummaryEntry = (scenario: ScenarioKey): UnitData =>
    normalizeUnitData(summarizeData.value?.[scenario])
const comparisonProjectTotals = ref<Record<string, Record<number, Record<ScenarioKey, UnitData>>>>({})
const comparisonSummaryTotals = ref<Record<number, Record<ScenarioKey, UnitData>>>({})
const selectedProjectNames = computed(() => {
    if (selectedProjects.value.length) {
       return props.projects
        .filter(project => selectedProjects.value.includes(project.id))
        .map(project => project.name)
    }
    return props.projects.map(p => p.name)
}
    
)
const normalizeScenarioTotals = (value: any): Record<ScenarioKey, UnitData> => ({
    yearly_plan: normalizeUnitData(value?.yearly_plan),
    profit: normalizeUnitData(value?.profit),
    settlement: normalizeUnitData(value?.settlement),
})
const fiscalTotalEntry = (projectName: string, scenario: ScenarioKey, fiscalYear: number): UnitData =>
    comparisonProjectTotals.value?.[projectName]?.[fiscalYear]?.[scenario] ?? emptyUnit
const fiscalSummaryEntry = (scenario: ScenarioKey, fiscalYear: number): UnitData =>
    comparisonSummaryTotals.value?.[fiscalYear]?.[scenario] ?? emptyUnit
const commentCount = ref<Record<number, number>>({})
const api = useApi()

onMounted(() => {
    selectedProjects.value = props.ownProjectIds && props.ownProjectIds.length ? props.ownProjectIds : []
})
const possibleTypes = [{ value: 'sales', label: '売上' }, { value: 'expense', label: '販管費' }, { value: 'profit', label: '利益' }]
const possibleScenarios = [{ value: 'yearly_plan', label: '年度予算' }, { value: 'profit', label: '損益計画' }, { value: 'settlement', label: '実績' }]
const activeType = ref('sales')
const activeScenario = ref('yearly_plan')
const hasPrivilage = computed(() => {
    return auth.user?.position_id && auth.user?.position_id <= 6 || auth.activeUser.id === 610
})
const monthCount = computed(() => Math.round(normalizedRange.value.end.diff(normalizedRange.value.start, 'months').months ?? 0) + 1)
const sortMode = ref<'name' | 'manager'>('name')

const showTotals = computed(() => monthCount.value > 1)
const showComment = computed(() => hasPrivilage.value && monthCount.value === 1 && totalGrouping.value !== 'fiscal')
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
const managerNameFor = (projectName: string) => {
    const proj = props.projects.find(p => p.name === projectName)
    const names = proj?.manager?.map(m => m.name).filter(Boolean) ?? []
    return names.length ? names.join(', ') : ''
}
const sortedProjectNames = computed(() => {
    const names = Object.keys(dataByMonth.value ?? {})
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
const selectProjectComment = (name: string) => {
    const findProject = props.projects.find(p => p.name === name)
    if (findProject) selectedId.value = findProject.id
}
const selectAllProjects = (event: Event) => {
    const target = event.target as HTMLInputElement
    if (target.checked) {
        selectedProjects.value = props.projects.map(project => project.id)
    } else {
        selectedProjects.value = []
    }
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
    financeData.value = {}
    summarizeData.value = {
        profit: { expense: 0, sales: 0, profit: 0 },
        yearly_plan: { expense: 0, sales: 0, profit: 0 },
        settlement: { expense: 0, sales: 0, profit: 0 }
    }
    dataByMonth.value = {}
    periodTotals.value = {}
    variance.value = {}
    badgeLoader.value = 0
    commentCount.value = {}
    comparisonProjectTotals.value = {}
    comparisonSummaryTotals.value = {}
}

const fetchTotalFinance = async (token: number) => {
    const payload = {
        projects: selectedProjects.value,
        interval: intervalPayload.value,
    }
    try {
        const data = await api.get('/get_total_finance', payload)
        if (token !== activeFetchToken) return
        financeData.value = data?.sumData ?? {}
        summarizeData.value = data?.summarizeData ?? summarizeData.value
        dataByMonth.value = data?.plan_res_data ?? {}
        periodTotals.value = data?.periodTotals ?? {}
        menu.close()
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
        comparisonProjectTotals.value = {}
        comparisonSummaryTotals.value = {}
        return
    }
    const years = activeFiscalYears.value
    const projectsByYear: Record<string, Record<number, Record<ScenarioKey, UnitData>>> = {}
    const summaryByYear: Record<number, Record<ScenarioKey, UnitData>> = {}
    await Promise.all(years.map(async (fy) => {
        const data = await api.get('/get_total_finance', {
            projects: selectedProjects.value,
            interval: fiscalInterval(fy),
        })
        if (token !== activeFetchToken) return
        const sumData = data?.sumData ?? {}
        selectedProjectNames.value.forEach((name) => {
            projectsByYear[name] ||= {}
            projectsByYear[name][fy] = normalizeScenarioTotals(sumData?.[name])
        })
        summaryByYear[fy] = normalizeScenarioTotals(data?.summarizeData)
    }))
    if (token !== activeFetchToken) return
    comparisonProjectTotals.value = projectsByYear
    comparisonSummaryTotals.value = summaryByYear
}

const refreshTotalFinance = async () => {
    const token = ++activeFetchToken
    // if (!selectedProjects.value.length) {
    //     loader.value = false
    //     resetFinanceSummaries()
    //     return
    // }
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
    localStorage.setItem('projectIds', JSON.stringify(projects))
    refreshTotalFinance()
}, { deep: true })

watch([periodStartIso, periodEndIso], () => {
    refreshTotalFinance()
})
watch(totalGrouping, () => {
    refreshTotalFinance()
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
    total_unread += proj.total_unread ?? 0
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
.project-selector-left {
    display: flex;
    gap: 15px;
    flex-direction: column;
    padding: 15px;
    user-select: none;
    line-height: 1.5;
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
    height: calc(100% - 115px);
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
    .p-pie-chart {
        width: 80%;
    }

    .finance-table-scroll {
        overflow-x: visible;
        padding-bottom: 0;
        height: calc(100% - 170px);
    }
    table tbody tr td.sticky-left:first-of-type {
        background-color: var(--bg3);
    }
    .p-name {
        max-width: 100%;
        min-width: auto;
        text-align: center;
        background: var(--bg3);
    }
    .m-name {
        max-width: 100%;
        min-width: auto;
        text-align: center;
        background: var(--bg3);
    }

    .sub-name {
        text-align: center;
        min-width: auto;
        max-width: none;
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

    table {
        min-width: 100%;
        thead {
            display: none;
        }

        tbody {
            tr {
                display: block;
                margin-bottom: -1px;

                td {
                    display: block;
                    border-left: solid thin var(--calendarBorder);
                    border-right: solid thin var(--calendarBorder);
                    border-bottom: none;

                }

                td:last-of-type {
                    border-bottom: solid thin var(--calendarBorder);
                }

                td:first-of-type {
                    border-top: solid thin var(--calendarBorder);
                }

            }

            tr:not(.summary-row):nth-child(3n) {
                margin-bottom: 20px;
            }

        }
    }

    .h-cell {
        width: auto;
        text-align: start;
        border-right: none;
        border-left: none;
    }

    .sticky-left,
    .sticky-right {
        position: static;
        left: auto;
        right: auto;
        box-shadow: none;
        background: inherit;
    }

    .first-col,
    .second-col,
    .comment-cell {
        min-width: auto;
        max-width: none;
    }

    .inner-col {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 5px;
        width: 100%;
    }
    .flex-center-col {
        display: flex;
        justify-content: center;
    }
}
</style>
