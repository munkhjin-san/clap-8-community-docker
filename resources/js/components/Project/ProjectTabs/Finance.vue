<template>
    <div class="h-full relative bg-[var(--background-color)]">
        <div class="flex justify-between items-center p-4">
            <div class="sub-tab-container">
                <div @click="changeBetweenTabs('check')" :class="['sub-tab-item', { 'selected-sub-tab': activeTab === 'check'}]">収支確認</div>
                <div @click="changeBetweenTabs('case')" :class="['sub-tab-item', { 'selected-sub-tab': activeTab === 'case'}]">案件報告集計</div>
                <!-- <div @click="changeBetweenTabs('yearly')" :class="['sub-tab-item', { 'selected-sub-tab': activeTab === 'yearly'}]">年度予算入力</div> -->
            </div>
        </div>
        
        <div v-if="activeTab === 'check'" class="h-[calc(100%-60px)] overflow-y-auto">
            <div class="flex items-center gap-4 static flex-wrap md:flex-nowrap px-5 md:justify-normal justify-center">
                <div class="text-sm"><span class="p-[5px] text-xs bg-[var(--bg3)] mr-[10px]">期間</span> {{ selectedProject?.date_start && selectedProject.date_end ? `${DateTime.fromISO(selectedProject.date_start).toLocaleString(DateTime.DATE_SHORT)}  ~  ${DateTime.fromISO(selectedProject.date_end).toLocaleString(DateTime.DATE_SHORT)}` : '未設定' }}</div>
                <div class="work-monthpicker">
                    <div @click="shiftMonth(-1)" class="work-prevmonth">
                        <Back size="13"/>
                    </div>
                    <PeriodRangePicker
                        :start="periodStartIso"
                        :end="periodEndIso"
                        :max-months="isMobile() ? 1 : 12"
                        @change="handleRangeChange"
                    />
                    <div @click="shiftMonth(1)" class="work-nextmonth">
                        <Back size="13" class="rotate-180"/>
                    </div>
                </div>
            </div>
            <div class="mb-[20px] mt-2 flex justify-end px-[20px] gap-4">
                <LoaderButton @triggered="router.push({name: 'total-finance'})" style="margin: 0;" content="集計" :loading="false"/>
            </div>
            <div class="overflow-x-auto min-w-auto md:min-w-[1400px] whitespace-nowrap m-5 pb-2">
                <table>
                    <thead>
                        <tr>
                            <th class="z-[1] border-r [border-right-style:solid] border-[var(--calendarBorder)] sticky left-0 !text-end" rowspan="2">
                                月 • 年
                            </th>
                            <th
                                v-for="(p, i) in periods"
                                :key="p.period"
                                colspan="4"
                                :class="[
                                    'border-r border-[var(--calendarBorder)]',
                                    {'[border-right-style:solid]': i !== periods.length - 1} 
                                ]"
                            >
                                <div :id="p.period" class="month-header">
                                    <span>{{ p.year }}月{{ monthLabel(p.month) }}</span>
                                    <span
                                        v-if="showAnyArrow(p.period) && hasPrivilage"
                                        class="variance-flag"
                                        title="計画との差が大きい月です"
                                    >
                                        <svg fill="tomato" style="transform: rotate(180deg);" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 30 30">
                                            <path d="M14.978 0C6.735-.055-.129 6.931.002 15.153c-.028 8.166 6.815 14.939 14.976 14.811v-.04c.965.012 1.935-.068 2.889-.243 4.817-.861 9.056-4.274 10.937-8.8C32.986 11.04 25.688-.021 14.978 0m0 27.903C6.08 27.659-.075 18.755 3.433 10.373 7.813.292 22.129.294 26.49 10.385c3.512 8.225-2.605 17.404-11.512 17.518m-1.735-13.968c-.293 2.283-.156 4.58-.125 6.873l.166 2.289c.304 2.068 3.234 2.088 3.548 0 .186-1.523.193-3.051.205-4.58.028-1.53.044-3.058-.164-4.582-.334-2.082-3.284-2.104-3.63 0m-.344-4.565c.115.303.278.565.465.811.473.371 1.062.634 1.685.627 1.248.021 2.335-1.09 2.278-2.331-.015-.643-.308-1.218-.729-1.681-1.906-1.558-4.534.238-3.699 2.574"/>
                                        </svg>
                                    </span>
                                </div>
                            </th>
                            <th v-if="hasPrivilage" class="sticky right-0 border-l [border-left-style:solid] border-[var(--calendarBorder)]" rowspan="2">
                                コメント
                            </th>
                        </tr>
                        <tr>
                            <template v-for="(p, i) in periods" :key="p.period">
                                <th>売上</th>
                                <th>販管費</th>
                                <th>利益</th>
                                <th>利益率</th>
                            </template>
                            
                            
                        </tr>
                    </thead>
                    <tbody>
                        <template v-if="!loaderYP">
                            <tr>
                                <td class="h-cell border-r [border-right-style:solid] border-[var(--calendarBorder)]">年度予算</td>
                                <template v-for="p in periods" :key="p.period">
                                    <td>
                                        <div class="inner-col"><span class="mobile">売上</span>{{ amountOfMoneyParser(yearlyPlanData?.[p.period]?.sales ?? NaN) }}</div>
                                    </td>
                                    <td>
                                        <div class="inner-col"><span class="mobile">販管費</span>{{ amountOfMoneyParser(yearlyPlanData?.[p.period]?.expense ?? NaN) }}</div>
                                    </td>
                                    <td>
                                        <div class="inner-col"><span class="mobile">利益</span>{{ amountOfMoneyParser(yearlyPlanData?.[p.period]?.profit ?? NaN) }}</div>
                                    </td>
                                    <td>
                                        <div class="inner-col"><span class="mobile">利益率</span>{{ formatRate(yearlyPlanData?.[p.period]?.profit_rate ?? null) }}</div>
                                    </td>
                                </template>
                                <td class="sticky right-0 bg-[var(--background-color)] border-l [border-left-style:solid] border-[var(--calendarBorder)]" v-if="hasPrivilage">—</td>
                            </tr>
                        </template>
                        <template v-else>
                            <tr>
                                <td class="h-cell">年度予算</td>
                                <template v-for="p in periods" :key="p.period">
                                    <CellLoader :order="num" v-for="num in cellloadNum"/>
                                </template>
                            </tr>
                        </template>
                        <template v-if="!loaderProfit">
                            <tr>
                                <td class="h-cell border-r [border-right-style:solid] border-[var(--calendarBorder)]">損益計画</td>
                                <template v-for="p in periods" :key="p.period">
                                    <td>
                                        <div class="flex items-center gap-[5px]">
                                            <div class="inner-col"><span class="mobile">売上</span>{{ amountOfMoneyParser(profitData?.[p.period]?.sales ?? NaN) }}</div>
                                            <DeltaNumbers type="sales" :actual="profitData?.[p.period]?.sales ?? 0" :planned="yearlyPlanData?.[p.period]?.sales ?? 0"/>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="flex items-center gap-[5px]">
                                            <div class="inner-col"><span class="mobile">販管費</span>{{ amountOfMoneyParser(profitData?.[p.period]?.expense ?? NaN) }}</div>
                                            <DeltaNumbers type="expense" :actual="profitData?.[p.period]?.expense ?? 0" :planned="yearlyPlanData?.[p.period]?.expense ?? 0"/>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="flex items-center gap-[5px]">
                                            <div class="inner-col"><span class="mobile">利益</span>{{ amountOfMoneyParser(profitData?.[p.period]?.profit ?? NaN) }}</div>
                                            <DeltaNumbers type="profit" :actual="profitData?.[p.period]?.profit ?? 0" :planned="yearlyPlanData?.[p.period]?.profit ?? 0"/>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="flex items-center gap-[5px]">
                                            <div class="inner-col"><span class="mobile">利益率</span>{{ formatRate(profitData?.[p.period]?.profit_rate ?? null) }}</div>
                                            <DeltaNumbers type="profit_rate" :actual="profitData?.[p.period]?.profit_rate ?? 0" :planned="yearlyPlanData?.[p.period]?.profit_rate ?? 0"/>
                                        </div>
                                    </td>
                                </template>
                                <td class="sticky right-0 bg-[var(--background-color)] border-l [border-left-style:solid] border-[var(--calendarBorder)]" v-if="hasPrivilage">—</td>
                            </tr>
                        </template>
                        <template v-else>
                            <tr>
                                <td class="h-cell">損益計画</td>
                                <template v-for="p in periods" :key="p.period">
                                    <CellLoader :order="num" v-for="num in cellloadNum"/>
                                </template>
                            </tr>
                        </template>
                        <template v-if="!loaderSettlement">
                            <tr>
                                <td class="h-cell border-r [border-right-style:solid] border-[var(--calendarBorder)]">
                                    <span>実績</span>
                                </td>
                                <template v-for="p in periods" :key="p.period">
                                    <td>
                                        <div class="flex items-center gap-[5px]">
                                            <div class="inner-col"><span class="mobile">売上</span>{{ amountOfMoneyParser(settlementData?.[p.period]?.sales ?? NaN) }}</div>
                                            <DeltaNumbers type="sales" :actual="settlementData?.[p.period]?.sales ?? 0" :planned="profitData?.[p.period]?.sales ?? 0"/>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="flex items-center gap-[5px]">
                                            <div class="inner-col"><span class="mobile">販管費</span>{{ amountOfMoneyParser(settlementData?.[p.period]?.expense ?? NaN) }}</div>
                                            <DeltaNumbers type="expense" :actual="settlementData?.[p.period]?.expense ?? 0" :planned="profitData?.[p.period]?.expense ?? 0"/>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="flex items-center gap-[5px]">
                                            <div class="inner-col"><span class="mobile">利益</span>{{ amountOfMoneyParser(settlementData?.[p.period]?.profit ?? NaN) }}</div>
                                            <DeltaNumbers type="profit" :actual="settlementData?.[p.period]?.profit ?? 0" :planned="profitData?.[p.period]?.profit ?? 0"/>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="flex items-center gap-[5px]">
                                            <div class="inner-col"><span class="mobile">利益率</span>{{ formatRate(settlementData?.[p.period]?.profit_rate ?? null) }}</div>
                                            <DeltaNumbers type="profit_rate" :actual="settlementData?.[p.period]?.profit_rate ?? 0" :planned="profitData?.[p.period]?.profit_rate ?? 0"/>
                                        </div>
                                    </td>
                                </template>
                                <td class="sticky right-0 bg-[var(--background-color)] border-l [border-left-style:solid] border-[var(--calendarBorder)]" v-if="hasPrivilage">
                                    <div class="inner-col">
                                        <span class="mobile">コメント</span>
                                        <div class="flex items-center gap-2 cursor-pointer" @click="commentView = true">
                                            <svg fill="var(--primary-color)" height="15" version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 33">
                                                <path d="M10.788 8.109c1.574-0.063 3.148-0.083 4.711-0.104l2.356-0.031 2.356-0.010 2.356 0.010c0.782 0 1.574 0.021 2.356 0.031 1.574 0.031 3.148 0.063 4.711 0.136 0.459 0.021 0.823 0.417 0.803 0.876-0.021 0.438-0.375 0.771-0.803 0.792-1.574 0.073-3.148 0.115-4.711 0.136-0.782 0.010-1.574 0.031-2.356 0.031l-2.345 0.021-2.356-0.010-2.356-0.031c-1.574-0.021-3.148-0.052-4.711-0.104-0.479-0.021-0.855-0.417-0.844-0.896 0.010-0.459 0.386-0.823 0.834-0.844zM10.788 13.050c1.574-0.052 3.148-0.083 4.711-0.104l2.356-0.031 2.356-0.010 2.356 0.010c0.782 0 1.574 0.021 2.356 0.031 1.574 0.031 3.148 0.063 4.711 0.136 0.459 0.021 0.823 0.417 0.803 0.876-0.021 0.438-0.375 0.771-0.803 0.792-1.574 0.073-3.148 0.115-4.711 0.136-0.782 0.010-1.574 0.031-2.356 0.031l-2.356 0.010-2.356-0.010-2.356-0.031c-1.574-0.021-3.148-0.052-4.711-0.104-0.479-0.021-0.855-0.417-0.844-0.907 0.021-0.438 0.396-0.803 0.844-0.823zM10.788 17.991c0.74-0.052 1.491-0.083 2.231-0.104l1.115-0.031c0.375-0.010 0.74-0.010 1.115-0.010 0.74 0 1.491 0.010 2.231 0.042 0.75 0.031 1.491 0.063 2.231 0.136 0.459 0.052 0.803 0.459 0.75 0.928-0.042 0.407-0.365 0.709-0.75 0.75-0.75 0.073-1.491 0.115-2.231 0.136-0.75 0.031-1.491 0.042-2.231 0.042-0.375 0-0.74 0-1.115-0.010l-1.115-0.031c-0.74-0.021-1.491-0.052-2.231-0.104-0.479-0.042-0.844-0.459-0.803-0.938 0.031-0.427 0.375-0.771 0.803-0.803z"></path><path d="M39.432 11.393c-0.188-1.063-0.521-2.116-0.99-3.106-0.479-0.99-1.105-1.897-1.835-2.71s-1.564-1.511-2.45-2.106c-0.886-0.594-1.835-1.084-2.794-1.501-1.939-0.813-3.95-1.313-5.973-1.605s-4.055-0.396-6.066-0.365c-2.022 0.042-4.055 0.219-6.066 0.605-2.012 0.396-4.013 1.001-5.889 1.949-0.938 0.479-1.845 1.042-2.679 1.699-0.834 0.667-1.616 1.428-2.272 2.293-0.667 0.855-1.209 1.824-1.605 2.835-0.396 1.021-0.636 2.095-0.74 3.169-0.052 0.532-0.052 1.084-0.042 1.605 0.010 0.532 0.052 1.053 0.125 1.584 0.146 1.053 0.417 2.116 0.844 3.117s1.011 1.939 1.72 2.762c0.709 0.823 1.532 1.532 2.418 2.126 1.772 1.188 3.44 1.824 5.41 2.356 1.803 0.49 3.867 0.782 5.681 0.876 0.146 0.010 0.281 0.073 0.386 0.177 0.459 0.5 0.938 1.074 1.449 1.511 0.667 0.584 1.407 1.126 2.178 1.584 0.761 0.448 1.564 0.803 2.387 1.115 0.865 0.313 2.21 0.605 2.929 0.657 0.698 0.052 0.782-0.479 0.563-0.938-0.229-0.469-0.281-0.552-0.375-0.761s-0.188-0.417-0.271-0.625-0.344-0.844-0.49-1.261c-0.115-0.344-0.292-0.938-0.386-1.407-0.031-0.167 0.083-0.323 0.25-0.344 1.626-0.229 3.242-0.552 4.847-1.032 0.98-0.292 1.939-0.657 2.877-1.094s1.855-0.98 2.7-1.626c0.844-0.646 1.626-1.418 2.272-2.293 0.323-0.438 0.615-0.907 0.865-1.397s0.459-0.99 0.636-1.511c0.344-1.032 0.532-2.106 0.594-3.169 0.021-1.032-0.021-2.106-0.208-3.169zM37.347 14.478c-0.031 0.896-0.167 1.782-0.427 2.616-0.125 0.417-0.292 0.823-0.479 1.22s-0.407 0.771-0.657 1.126c-0.5 0.719-1.115 1.365-1.814 1.928-1.397 1.126-3.106 1.928-4.899 2.522-0.896 0.302-1.814 0.542-2.752 0.75-0.928 0.208-1.876 0.375-2.835 0.511h-0.031c-0.396 0.063-0.709 0.396-0.719 0.813-0.010 0.594 0.083 1.126 0.208 1.626s0.292 0.969 0.469 1.438c0.146 0.375 0.292 0.698 0.542 1.105 0.042 0.073-0.021 0.146-0.104 0.125-1.167-0.365-2.304-0.907-3.461-1.845-1.23-0.99-1.762-1.584-2.814-2.835-0.146-0.177-0.365-0.302-0.615-0.323h-0.031c-1.908-0.188-3.805-0.479-5.629-0.98-1.814-0.5-3.565-1.199-5.055-2.22-0.74-0.511-1.407-1.105-1.97-1.772-0.563-0.678-1.022-1.418-1.355-2.231s-0.552-1.678-0.657-2.564-0.125-1.824-0.031-2.689c0.104-0.876 0.313-1.73 0.646-2.543 0.334-0.803 0.771-1.564 1.324-2.251 1.115-1.386 2.595-2.481 4.232-3.273 0.823-0.396 1.678-0.74 2.564-1.022s1.793-0.511 2.71-0.678c1.845-0.354 3.742-0.511 5.639-0.532 1.907-0.010 3.815 0.073 5.67 0.344 1.866 0.271 3.69 0.709 5.378 1.418 1.689 0.698 3.242 1.668 4.44 2.95 0.594 0.636 1.105 1.355 1.491 2.126s0.667 1.605 0.834 2.481c0.167 0.855 0.219 1.751 0.188 2.658z"></path>
                                            </svg>
                                            <span v-if="commentCount > 0" class="text-xs">{{ commentCount }}</span>
                                            <span class="side-notification" style="position: unset;" v-if="financeTotalBadge">{{ financeTotalBadge }}</span>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <template v-else>
                            <tr>
                                <td class="h-cell">実績</td>
                                <template v-for="p in periods" :key="p.period">
                                    <CellLoader :order="num" v-for="num in cellloadNum"/>
                                </template>
                            </tr>
                        </template>
                        <template v-if="!loaderSettlement && !loaderProfit">
                            <tr class="variance-row">
                                <td class="h-cell border-r [border-right-style:solid] border-[var(--calendarBorder)]">計画比</td>
                                <template v-for="p in periods" :key="p.period">
                                    <td>
                                        <div class="inner-col variance-cell" :style="{ color: varianceColor('sales', variancePercentMap[p.period]?.sales ?? null) }">
                                            <span class="mobile">売上</span>{{ formatVariance('sales', variancePercentMap[p.period]?.sales ?? null) }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="inner-col variance-cell" :style="{ color: varianceColor('expense', variancePercentMap[p.period]?.expense ?? null) }">
                                            <span class="mobile">販管費</span>{{ formatVariance('expense', variancePercentMap[p.period]?.expense ?? null) }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="inner-col variance-cell" :style="{ color: varianceColor('profit', variancePercentMap[p.period]?.profit ?? null) }">
                                            <span class="mobile">利益</span>{{ formatVariance('profit', variancePercentMap[p.period]?.profit ?? null) }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="inner-col variance-cell" :style="{ color: varianceColor('profit_rate', variancePercentMap[p.period]?.profit_rate ?? null) }">
                                            <span class="mobile">利益率</span>{{ formatVariance('profit_rate', variancePercentMap[p.period]?.profit_rate ?? null) }}
                                        </div>
                                    </td>
                                </template>
                                <td class="sticky right-0 bg-[var(--bg3)] border-l [border-left-style:solid] border-[var(--calendarBorder)]" v-if="hasPrivilage">—</td>
                            </tr>
                        </template>
                    </tbody>
                </table>
                
            </div>
            
            <!-- <table>
                <thead>
                    <tr>
                        <th class="h-cell"></th>
                        <th v-for="l in lineOrder" :key="l">{{ lineLabelJa[l] }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="group in scenarioRows" :key="group.label">
                    <td class="h-cell">
                        <div class="flex flex-col">
                            <span>{{ group.label }}</span>
                            <span v-if="group.extras.length" class="text-[10px] text-[var(--primary-color)] opacity-70">
                                他 {{ group.extras.length }} 指標（ライン外）
                            </span>
                        </div>
                    </td>
                    <td v-for="l in lineOrder" :key="l">
                        <template v-if="group.lines[l]">
                            <div class="flex items-center">
                                <span>{{ fmt(group.lines[l]!.value, group.lines[l]!.value_type) }}</span>
                                <span
                                  v-if="hasSubMetric(group.lines[l]!.sub_metric_value)"
                                  class="text-[11px] whitespace-nowrap ml-[5px]"
                                  :style="{ color: subColor(l, group.lines[l]!.sub_metric_value) }"
                                >
                                  {{ `${subArrow(group.lines[l]!.sub_metric_value)} ${fmtSubMetric(group.lines[l]!.sub_metric_value, group.lines[l]!.value_type)}` }}
                                </span>
                            </div>
                        </template>
                        <template v-else>—</template>
                    </td>
                    </tr>
                </tbody>
            </table>
            <div v-if="orphanMetrics.length" class="orphan-wrapper">
                <p class="orphan-title">表示枠に収まらない指標</p>
                <ul class="orphan-list">
                    <li v-for="item in orphanMetrics" :key="`orphan-${item.metric.id}-${item.reason}`">
                        <span class="orphan-label">{{ item.metric.label_ja }}</span>
                        <span class="orphan-reason">{{ item.reason }}</span>
                    </li>
                </ul>
            </div> -->
            
        </div>
        <div class="overflow-auto h-[calc(100%-100px)]" v-if="activeTab === 'case'">
            <CaseConfirm 
                :select-project="selectedProject" 
                :refresh-key="caseRefreshKey"
                :has-privilage="hasPrivilage"
                @view="(val) => viewCase(val)"
            />
        
            <FloatButton @action="caseWindow = true">
                <template #icon>
                    <AddIcon size="15" fill="black"/>
                </template>
            </FloatButton>

            <CaseCreate 
                v-if="caseWindow && selectedProject"
                :project-id="selectedProject.id"
                :selected-project="selectedProject"
                :report-year="year"
                :report-month="month"
                :has-privilage="hasPrivilage"
                :selected-case-id="selectedCaseId"
                @close="caseWindow = false, selectedCaseId = null"
                @saved="handleCaseSaved"
            />
        </div>
        
        
        
        <YearlyBudget 
            v-else-if="activeTab === 'yearly'"
            :year="year"
            :selectedProjectName="selectedProject.name"
            :selectedProjectId="selectedProject.id"
        />
        <Transition name="smLoad">
            <CommentWindow 
                v-if="commentView"  
                type="実績"
                :currentProjectId="selectedProject.id"
                @close="commentView = false"
                @getCommentCounts="getCommentCounts" 
            />
        </Transition>
        <router-view 
            :selected-project="selectedProject"
        />
    </div>
</template>
<script setup lang="ts">
import Back from '@/components/Icons/Back.vue';
import { DateTime, MonthNumbers } from 'luxon';
import { computed, inject, onMounted, ref } from 'vue';
import { amountOfMoneyParser } from '@/utils/tools';
import CellLoader from './Finance/CellLoader.vue';
import { useRoute, useRouter } from 'vue-router';
import LoaderButton from '@/components/Global/LoaderButton.vue';
import DeltaNumbers from './Finance/DeltaNumbers.vue';
import { useApi } from '@/composables/api';
import { useAuthUserStore } from '@/store/auth';
import CommentWindow from './Finance/CommentWindow.vue';
import { User } from '@/interface/globalInterface';
import { Project } from '@/interface/projectInterface';
import { useBadgeStore } from '@/store/badge';
import YearlyBudget from './Finance/YearlyBudget.vue';
import CaseCreate from './Finance/CaseCreate.vue';
import FloatButton from '@/components/Global/FloatButton.vue';
import AddIcon from '@/components/Form/AddIcon.vue';
import CaseConfirm from './Finance/CaseConfirm.vue';
import PeriodRangePicker from './Finance/PeriodRangePicker.vue';
import { isMobile } from '@/utils/tools';
const auth = useAuthUserStore()
const props = defineProps<{
    userList: any;
    mentionableUsers: User[];
    selectedProject: Project;
    hasPrivilage: boolean
}>();
const caseWindow = ref(false)
const caseRefreshKey = ref(0)
const commentView = ref(false)
const loaderYP = ref(true)
const loaderSettlement = ref(true)
const loaderProfit = ref(true)
const route = useRoute()
const router = useRouter()
const badge = useBadgeStore()
const api = useApi()

const parsePeriodParam = (value: unknown): DateTime | null => {
  if (typeof value !== 'string' || !value) return null
  const dt = DateTime.fromISO(`${value}-01`)
  return dt.isValid ? dt.startOf('month') : null
}

const initialEnd =
  parsePeriodParam(route.query.period_end) ??
  parsePeriodParam(route.query.period) ??
  DateTime.now().startOf('month')

const defaultFiscalYear = initialEnd.month >= 3 ? initialEnd.year : initialEnd.year - 1
const parsedStart = parsePeriodParam(route.query.period_start)
const fallbackStart = DateTime.fromObject({ year: defaultFiscalYear, month: isMobile() ? initialEnd.month : 3, day: 1 }).startOf('month')

const normalizeRange = (start: DateTime, end: DateTime) => {
  let rangeStart = start
  let rangeEnd = end

  if (rangeEnd < rangeStart) {
    const tmp = rangeStart
    rangeStart = rangeEnd
    rangeEnd = tmp
  }

  const monthsApart = Math.round(rangeEnd.diff(rangeStart, 'months').months ?? 0)
  const maxEnd = rangeStart.plus({ months: 11 })
  if (monthsApart > 11) {
    rangeEnd = maxEnd
  } else if (rangeEnd > maxEnd) {
    rangeEnd = maxEnd
  }

  return { start: rangeStart, end: rangeEnd }
}

const normalizedRange = normalizeRange(parsedStart ?? fallbackStart, initialEnd)

const periodStart = ref<DateTime>(normalizedRange.start)
const periodEnd = ref<DateTime>(normalizedRange.end)

const periodStartIso = computed(() => periodStart.value.toFormat('yyyy-MM'))
const periodEndIso = computed(() => periodEnd.value.toFormat('yyyy-MM'))

const year = ref<number>(periodEnd.value.year)
const month = ref<MonthNumbers>(periodEnd.value.month as MonthNumbers)

const handleCaseSaved = () => {
    caseWindow.value = false
    caseRefreshKey.value += 1
    selectedCaseId.value = null
}
const commentCount = ref(0)
const metrics_list = ref<MetricDTO[]>([])
const selectedCaseId = ref<number | null>(null)
const viewCase = (id: number | null) => {
    selectedCaseId.value = id
    caseWindow.value = true
}

type Line = 'sales'|'expense'|'profit'|'profit_rate'
type ValueType = 'currency'|'amount'|'rate'
type SubMetricDTO = {
  id: number
  expression?: string | null
  expression_normalized?: string | null
  sort_order?: number | null
}
type MetricDTO = {
  id: number
  label_ja: string
  line: Line
  kind: 'input'|'derived'
  scenario_label_ja?: string | null
  value: number | null
  expression: string | null
  expression_normalized?: string | null
  value_type: ValueType
  sub_metrics?: SubMetricDTO[]
  sub_metric?: SubMetricDTO | null
  sub_metric_value?: number | null
  sort_order?: number | null
}
type MetricWithComputed = MetricDTO & {
  value: number | null | undefined
  sub_metric_value?: number | null | undefined
  sub_metric?: SubMetricDTO | null
}
type ScenarioExtra = { metric: MetricWithComputed; reason: string }
type ScenarioRow = { label: string; lines: Partial<Record<Line, MetricWithComputed>>; extras: ScenarioExtra[] }
const lineOrder: Line[] = ['sales','expense','profit','profit_rate']
const lineLabelJa: Record<Line,string> = {
  sales:'売上', expense:'販管費', profit:'利益', profit_rate:'利益率'
}
const scenarioPref = [
  { code: 'annual_budget', label_ja: '年度予算' },
  { code: 'plan',          label_ja: '損益計画' },
  { code: 'actual',        label_ja: '実績' },
  { code: 'forecast',      label_ja: '予測' },
]
const extractSubMetric = (metric: MetricDTO): SubMetricDTO | null => {
  if ((metric as unknown as { sub_metric?: SubMetricDTO | null }).sub_metric) {
    return (metric as unknown as { sub_metric?: SubMetricDTO | null }).sub_metric ?? null
  }
  if (Array.isArray(metric.sub_metrics) && metric.sub_metrics.length) {
    return metric.sub_metrics[0] ?? null
  }
  return null
}
interface BalanceColumn {
    sales: number | null;
    expense: number | null;
    profit: number | null;
    profit_rate: number | null;
}

interface SettlementColumn extends BalanceColumn {
    overhead?: number | null;
    row?: number | null;
}

type BalanceMap<T> = Record<string, T>;

const yearlyPlanData = ref<BalanceMap<BalanceColumn>>({})

const settlementData = ref<BalanceMap<SettlementColumn>>({})

const profitData = ref<BalanceMap<BalanceColumn>>({})

const setTotalFinanceWindow = inject('setTotalFinanceWindow') as (flag: boolean) => void

const normalizedPeriod = computed(() => `${year.value}-${String(month.value).padStart(2, '0')}-01`)

const FISCAL_START_MONTH = 3
const fiscalStartDate = (fy: number) => DateTime.fromObject({ year: fy, month: FISCAL_START_MONTH, day: 1 }).startOf('month')
const fiscalMonthDates = (fy: number) =>
  Array.from({ length: 12 }, (_, idx) => fiscalStartDate(fy).plus({ months: idx }))

const toNumeric = (value: unknown): number | null => {
  if (value === null || value === undefined || value === '') return null
  if (typeof value === 'number') {
    return Number.isFinite(value) ? value : null
  }
  if (typeof value === 'string') {
    const sanitized = value.replace(/[,％%]/g, '')
    const parsed = Number(sanitized)
    return Number.isFinite(parsed) ? parsed : null
  }
  return null
}

const toRate = (value: unknown): number | null => {
  const num = toNumeric(value)
  if (num === null) return null
  return Number.isFinite(num) ? num : null
}

const assignBalance = <T extends BalanceColumn>(target: BalanceMap<T>, key: string, payload: Partial<T>) => {
  const existing = target[key] ?? { sales: null, expense: null, profit: null, profit_rate: null } as T
  target[key] = {
    ...existing,
    ...payload,
  }
}

const normalizeBalanceEntry = (raw: any): BalanceColumn => ({
  sales: toNumeric(raw?.sales) ?? NaN,
  expense: toNumeric(raw?.expense) ?? NaN,
  profit: toNumeric(raw?.profit) ?? NaN,
  profit_rate: toRate(raw?.profit_rate),
})

const normalizeSettlementEntry = (raw: any): SettlementColumn => ({
  sales: toNumeric(raw?.sales) ?? NaN,
  expense: toNumeric(raw?.expense) ?? NaN,
  profit: toNumeric(raw?.profit) ?? NaN,
  profit_rate: toRate(raw?.profit_rate),
  overhead: toNumeric(raw?.overhead) ?? null,
  row: raw?.row ?? null,
})

const financeTotalBadge = computed(() => {
    return badge.financeCommentBadgeByFilter([{ by: 'project_id', value: Number(route.params.projectId)}]).length
})
const activeTab = ref<'check' | 'yearly' | 'monthly' | 'actual' | 'case'>('check')

const changeBetweenTabs = (which: 'check' | 'yearly' | 'monthly' | 'actual' | 'case') => {
  activeTab.value = which
}

onMounted(async() => {
    updateRouteQuery()
    refreshFinanceData()
})
const pad2 = (n:number) => String(n).padStart(2, '0')
const monthLabel = (m:number) => ['1月','2月','3月','4月','5月','6月','7月','8月','9月','10月','11月','12月'][m-1]
const periodKey = (y:number, m:number) => `${y}-${pad2(m)}`
const fiscalYearFrom = (y:number, m:number) => (m >= 3 ? y : y - 1)

type PeriodCell = { year:number; month:number; period:string; fiscalYear:number }
const scrollIntoCurrent = () => {
    const currentPeriod = DateTime.now().toFormat('yyyy-MM')
    let scrollPosition = document.getElementById(currentPeriod)
    if (scrollPosition) {
        scrollPosition.scrollIntoView({ behavior: 'instant', block: 'end' });
    }
}
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

const periods = computed<PeriodCell[]>(() => generatePeriodRange(periodStart.value, periodEnd.value))
const fiscalYearsInRange = computed(() => {
  const set = new Set<number>()
  periods.value.forEach(p => set.add(p.fiscalYear))
  return Array.from(set.values()).sort((a, b) => a - b)
})
const cellloadNum = computed(() => {
    return 4
})
const getMetrics = async() => {
    const data = await api.get(`/project_metrics/${route.params.projectId}/by_period`, {period: normalizedPeriod.value})
    if (data) {
    metrics_list.value = data
}
  // console.log(grouped.value)
  // console.log(scenarioOrder.value)
  // console.log(tableRows.value)

}
const sortedMetrics = computed<MetricWithComputed[]>(() => {
  return [...tableRows.value].sort((a, b) => {
    const labelA = (a.scenario_label_ja ?? '').trim()
    const labelB = (b.scenario_label_ja ?? '').trim()
    if (labelA === labelB) {
      const orderA = a.sort_order ?? 0
      const orderB = b.sort_order ?? 0
      if (orderA !== orderB) return orderA - orderB
      return (a.id ?? 0) - (b.id ?? 0)
    }
    if (!labelA) return 1
    if (!labelB) return -1
    return labelA.localeCompare(labelB, 'ja')
  })
})
const scenarioAnalysis = computed(() => {
  const buckets = new Map<string, ScenarioRow>()
  const unmatched: ScenarioExtra[] = []

  const pushingExtras = (bucket: ScenarioRow | null, metric: MetricWithComputed, reason: string) => {
    const entry: ScenarioExtra = { metric, reason }
    if (bucket) bucket.extras.push(entry)
    unmatched.push(entry)
  }

  for (const metric of sortedMetrics.value) {
    const rawLabel = metric.scenario_label_ja?.trim()
    if (!rawLabel) {
      pushingExtras(null, metric, 'シナリオ未設定')
      continue
    }

    let bucket = buckets.get(rawLabel)
    if (!bucket) {
      bucket = { label: rawLabel, lines: {}, extras: [] }
      buckets.set(rawLabel, bucket)
    }

    if (metric.line && lineOrder.includes(metric.line)) {
      const current = bucket.lines[metric.line]
      if (!current) {
        bucket.lines[metric.line] = metric
      } else {
        const reason = `${rawLabel}: ${lineLabelJa[metric.line]}が複数あります`
        pushingExtras(bucket, metric, reason)
      }
    } else {
      const reason = `${rawLabel}: ライン未設定`
      pushingExtras(bucket, metric, reason)
    }
  }

  const orderedLabels: string[] = []
  for (const pref of scenarioPref) {
    if (buckets.has(pref.label_ja)) orderedLabels.push(pref.label_ja)
  }
  const remaining = Array.from(buckets.keys()).filter(label => !orderedLabels.includes(label)).sort((a, b) => a.localeCompare(b, 'ja'))
  const rows = orderedLabels.concat(remaining).map(label => buckets.get(label)!)

  return { rows, unmatched }
})
const scenarioRows = computed(() => scenarioAnalysis.value.rows)
const orphanMetrics = computed(() => scenarioAnalysis.value.unmatched)

const nfNumber = new Intl.NumberFormat('ja-JP')
const nfInt    = new Intl.NumberFormat('ja-JP', { maximumFractionDigits: 0 })
const fmt = (v: number|null|undefined, vt: ValueType) =>
  v == null || Number.isNaN(v)
    ? '—'
    : vt === 'rate'
      ? `${nfInt.format(Math.round(v))}%`
      : vt === 'amount'
        ? `${nfInt.format(Math.round(v))}件`
        : vt === 'currency'
          ? `${nfInt.format(Math.round(v))}円`
          : nfNumber.format(v)
const fmtSubMetric = (v: number|null|undefined, vt: ValueType) => {
  if (v == null || Number.isNaN(v) || v === 0) return ''
  const abs = v
  if (vt === 'rate') return `${nfInt.format(Math.round(abs))}%`
  if (vt === 'amount') return `${nfInt.format(Math.round(abs))}件`
  if (vt === 'currency') return `${nfInt.format(Math.round(abs))}円`
  return nfNumber.format(abs)
}
const subArrow = (v: number | null | undefined) => {
  if (v == null || Number.isNaN(v) || v === 0) return ''
  return v > 0 ? '↑' : '↓'
}
const subColor = (line: Line, value: number | null | undefined) => {
  if (value == null || Number.isNaN(value) || value === 0) return ''
  if (line === 'expense') {
    return value > 0 ? 'tomato' : 'green'
  }
  return value > 0 ? 'green' : 'tomato'
}
const hasSubMetric = (value: number | null | undefined) => {
  return value != null && !Number.isNaN(value) && value !== 0
}
const THRESHOLD = 10;

type Key = 'sales' | 'expense' | 'profit';
type VarianceKey = Key | 'profit_rate';

const calcVariancePercent = (actual: number | null | undefined, planned: number | null | undefined): number | null => {
  if (actual == null || planned == null || !Number.isFinite(actual) || !Number.isFinite(planned) || planned === 0) {
    return null
  }
  return ((actual - planned) / Math.abs(planned)) * 100
}

const calcVariancePoints = (actual: number | null | undefined, planned: number | null | undefined): number | null => {
  if (actual == null || planned == null || !Number.isFinite(actual) || !Number.isFinite(planned)) {
    return null
  }
  return actual - planned
}

const variancePercentMap = computed<Record<string, Record<VarianceKey, number | null>>>(() => {
  const result: Record<string, Record<VarianceKey, number | null>> = {}
  periods.value.forEach(p => {
    const key = p.period
    const actual = settlementData.value[key]
    const planned = profitData.value[key]
    result[key] = {
      sales: calcVariancePercent(actual?.sales ?? null, planned?.sales ?? null),
      expense: calcVariancePercent(actual?.expense ?? null, planned?.expense ?? null),
      profit: calcVariancePercent(actual?.profit ?? null, planned?.profit ?? null),
      profit_rate: calcVariancePoints(actual?.profit_rate ?? null, planned?.profit_rate ?? null),
    }
  })
  return result
})

const getVarPct = (period: string, metric: Key): number | null => {
  const value = variancePercentMap.value?.[period]?.[metric] ?? null
  return value != null && Number.isFinite(value) ? value : null
}

const showAnyArrow = (period: string): boolean =>
  (['sales', 'expense', 'profit'] as Key[]).some(metric => {
    const value = getVarPct(period, metric)
    return value != null && Math.abs(value) >= THRESHOLD
  })

const varianceColor = (line: VarianceKey, value: number | null) => {
  if (value == null || Number.isNaN(value)) return ''
  if (line === 'expense') {
    return value > 0 ? 'tomato' : 'green'
  }
  return value > 0 ? 'green' : 'tomato'
}

const formatVariance = (line: VarianceKey, value: number | null) => {
  if (value == null || Number.isNaN(value)) return '—'
  const sign = value > 0 ? '+' : ''
  if (line === 'profit_rate') {
    return `${sign}${value.toFixed(2)}pt`
  }
  return `${sign}${value.toFixed(2)}%`
}

const formatRate = (value: number | null | undefined) => {
  if (value == null || Number.isNaN(Number(value))) return '—'
  return `${Number(value).toFixed(2)}%`
}
interface EvalOptions { failOnMissing?: boolean }
const evalExpression = (normalizedExpr: string | null, resolver: (id: number) => number | null, options: EvalOptions = {}) => {
  if (!normalizedExpr) return null
  const expr = normalizedExpr.replace(/\{\{m:(\d+)\}\}/g, (_, raw) => `getValue(${Number(raw)})`)
  try {
    const fn = Function('getValue', `"use strict";
      const nullif = (a, b) => (a === b ? null : a);
      const pct = (num, denom) => denom ? (num / denom) * 100 : 0;
      const ratio = (num, denom) => denom ? num / denom : 0;
      return (${expr});`)
    let missing = false
    const result = fn((key: number) => {
      const val = resolver(Number(key))
      if (val == null || Number.isNaN(val)) {
        missing = true
        return 0
      }
      return val
    })
    if (missing && options.failOnMissing) return null
    return typeof result === 'number' && Number.isFinite(result) ? result : null
  } catch (err) {
    console.warn('bad expr', normalizedExpr, err)
    return null
  }
}

const createValueResolver = () => {
  const byId = new Map<number, MetricDTO>(metrics_list.value.map(m => [m.id, m]))
  const cache = new Map<number, number | null>()
  const resolving = new Set<number>()

  const resolve = (id: number): number | null => {
    if (cache.has(id)) return cache.get(id) ?? null

    const metric = byId.get(id)
    if (!metric) {
      cache.set(id, null)
      return null
    }

    if (metric.value != null && !Number.isNaN(Number(metric.value))) {
      const numeric = Number(metric.value)
      cache.set(id, numeric)
      return numeric
    }

    if (!metric.expression_normalized) {
      cache.set(id, null)
      return null
    }

    if (resolving.has(id)) {
      console.warn('Metric dependency cycle detected for id', id)
      cache.set(id, null)
      return null
    }

    resolving.add(id)
    const computed = evalExpression(metric.expression_normalized, resolve)
    resolving.delete(id)
    cache.set(id, computed)
    return computed
  }

  return resolve
}

const tableRows = computed(() => {
  const resolve = createValueResolver()
  return metrics_list.value.map(m => {
    const value = resolve(m.id)
    const sub = extractSubMetric(m)
    const subExpression = sub?.expression_normalized ?? null
    const subValue = subExpression ? evalExpression(subExpression, resolve, { failOnMissing: true }) : null
    return {
      ...m,
      value,
      sub_metric: sub ?? null,
      sub_metric_value: subValue,
    }
  })
})

const getYearlyPlan = async () => {
    loaderYP.value = true
    try {
        const aggregated: BalanceMap<BalanceColumn> = {}
        const fiscalYears = fiscalYearsInRange.value

        await Promise.all(fiscalYears.map(async fy => {
            const response = await api.get('/get_yearly_plan', {
                project_id: route.params.projectId,
                month: month.value,
                year: fy
            }, {silent: true})
            const months = fiscalMonthDates(fy)
            const rawEntries = response ?? {}
            months.forEach(dt => {
                const raw = rawEntries[String(dt.month)] ?? rawEntries[dt.month]
                assignBalance(aggregated, periodKey(dt.year, dt.month), normalizeBalanceEntry(raw ?? {}))
            })
        }))

        yearlyPlanData.value = aggregated
    } catch (error) {
        console.error('Failed to load yearly plan', error)
        yearlyPlanData.value = {}
    } finally {
        loaderYP.value = false
    }
}

const getSettlement = async () => {
    loaderSettlement.value = true
    try {
        const aggregated: BalanceMap<SettlementColumn> = {}
        const fiscalYears = fiscalYearsInRange.value

        await Promise.all(fiscalYears.map(async fy => {
            const response = await api.get('/get_settlement', {
                project_id: route.params.projectId,
                month: month.value,
                year: fy
            }, {silent: true})
            const months = fiscalMonthDates(fy)
            const rawEntries = response ?? {}
            months.forEach(dt => {
                const raw = rawEntries[String(dt.month)] ?? rawEntries[dt.month]
                assignBalance(aggregated, periodKey(dt.year, dt.month), normalizeSettlementEntry(raw ?? {}))
            })
        }))

        settlementData.value = aggregated
    } catch (error) {
        console.error('Failed to load settlement data', error)
        settlementData.value = {}
    } finally {
        loaderSettlement.value = false
    }
}
const refreshFinanceData = async() => {
    await Promise.all([
        getYearlyPlan(),
        getProfit(),
        getSettlement(),
        getCommentCounts(),
        // getMetrics(),
    ])
    scrollIntoCurrent()
}

const formattedRange = () => ({
    start: periodStart.value.toFormat('yyyy-MM'),
    end: periodEnd.value.toFormat('yyyy-MM'),
})

const updateRouteQuery = () => {
    const { start, end } = formattedRange()
    const currentStart = typeof route.query.period_start === 'string' ? route.query.period_start : ''
    const currentEnd = typeof route.query.period_end === 'string' ? route.query.period_end : ''
    if (start === currentStart && end === currentEnd) return

    router.replace({
        query: {
            ...route.query,
            period_start: start,
            period_end: end,
        },
    })
}
const getProfit = async () => {
    loaderProfit.value = true
    try {
        const aggregated: BalanceMap<BalanceColumn> = {}
        const fiscalYears = fiscalYearsInRange.value

        await Promise.all(fiscalYears.map(async fy => {
            const response = await api.get('/get_profit', {
                project_id: route.params.projectId,
                month: month.value,
                year: fy
            }, {silent: true})
            const months = fiscalMonthDates(fy)
            const rawEntries = response ?? {}
            months.forEach(dt => {
                const raw = rawEntries[String(dt.month)] ?? rawEntries[dt.month]
                assignBalance(aggregated, periodKey(dt.year, dt.month), normalizeBalanceEntry(raw ?? {}))
            })
        }))

        profitData.value = aggregated
    } catch (error) {
        console.error('Failed to load profit data', error)
        profitData.value = {}
    } finally {
        loaderProfit.value = false
    }
}
const applyRange = (start: DateTime, end: DateTime, options: { skipRefresh?: boolean } = {}) => {
    const normalized = normalizeRange(start.startOf('month'), end.startOf('month'))
    periodStart.value = normalized.start
    periodEnd.value = normalized.end
    year.value = normalized.end.year
    month.value = normalized.end.month as MonthNumbers
    updateRouteQuery()
    if (!options.skipRefresh) {
        refreshFinanceData()
    }
}

const handleRangeChange = ({ start, end }: { start: string; end: string }) => {
    const startDt = DateTime.fromFormat(`${start}-01`, 'yyyy-MM-dd', { zone: 'Asia/Tokyo' })
    const endDt = DateTime.fromFormat(`${end}-01`, 'yyyy-MM-dd', { zone: 'Asia/Tokyo' })
    if (!startDt.isValid || !endDt.isValid) return
    if (startDt.equals(periodStart.value) && endDt.equals(periodEnd.value)) return
    applyRange(startDt, endDt)
}

const shiftMonth = (value: number) => {
    const newStart = periodStart.value.plus({ months: value })
    const newEnd = periodEnd.value.plus({ months: value })
    applyRange(newStart, newEnd)
}
const viewTotalFinance = () => {
    if(typeof setTotalFinanceWindow === 'function'){
        setTotalFinanceWindow(true)
    }
}
const getCommentCounts = async() => {
    const data = await api.get(`/projects/${route.params.projectId}/finance-comments/monthly-count`);
    commentCount.value = data
    
}
</script>

<style scoped lang="scss">
table{
    width: 100%;
    border-collapse: separate;
    font-size: 13px;
    line-height: 1.5;
    thead{
        th{
            padding: 10px;
            font-weight: 500;
            text-align: left;
            background-color: var(--bg3);
        }
        th:nth-of-type(4n + 4):has(~ th:nth-of-type(4n + 4)) {
            border-right: 1px solid var(--calendarBorder);
        }
        position: sticky;
        top: 0;
        background-color: var(--background-color);
        z-index: 1;
    }
    tbody{
        tr{
            td{
                padding: 10px;
                border-bottom: 1px solid var(--calendarBorder);
                font-weight: 400;
                text-align: left;
                span{
                    display: block;
                }
            }
            td:nth-of-type(4n + 5):has(~ td:nth-of-type(4n + 5)) {
                border-right: 1px solid var(--calendarBorder);
            }
        }
    }
}
.h-cell{
    width: 60px;
   min-width: 60px;
   background-color: var(--bg3);
   border-bottom: none;
   text-align: end;
   position: sticky;
   left: 0;
   z-index: 1;
}
.month-header{
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}
.variance-flag{
    font-size: 14px;
    color: tomato;
    line-height: 1;
}
.variance-row{
    background-color: var(--bg3);
}
.variance-cell{
    font-size: 12px;
    font-weight: 500;
}
@media screen and (max-width: 959px) {
    table{
        thead{
            display: none;
        }
        tbody{
            tr{
                display: block;
                margin-bottom: 20px;
                td{
                    display: block;
                    border-left: solid thin var(--calendarBorder);
                    border-right: solid thin var(--calendarBorder);
                }
            }
        }
    }   
    .h-cell{
        width: auto;
        text-align: start;
        border-right: none;
        border-left: none;
    }
    .inner-col{
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 5px;
        width: 100%;
    }
}
.orphan-wrapper{
    margin: 20px;
    padding: 12px 16px;
    border: 1px solid var(--calendarBorder);
    border-radius: 8px;
    background: var(--bg2);
    color: var(--primary-color);
}
.orphan-title{
    font-size: 12px;
    font-weight: 600;
    margin-bottom: 8px;
}
.orphan-list{
    list-style: none;
    padding: 0;
    margin: 0;
    display: grid;
    gap: 6px;
}
.orphan-list li{
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    font-size: 11px;
    border-bottom: 1px dashed var(--calendarBorder);
    padding-bottom: 4px;
}
.orphan-list li:last-child{
    border-bottom: none;
}
.orphan-label{
    font-weight: 600;
}
.orphan-reason{
    opacity: 0.7;
}
</style>
