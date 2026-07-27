<template>
    <!-- <div v-if="selectedProject" class="h-full relative bg-[var(--background-color)]">
        <div class="flex justify-between items-center p-4">
            <div class="sub-tab-container">
                <div @click="router.push({name: 'finance'})" :class="['sub-tab-item', { 'selected-sub-tab': route.name === 'finance'}]">収支確認</div>
                <div v-if="selectedProject.has_actual_func" id="performanceManagement" @click="router.push({name: 'result'})" :class="['sub-tab-item', { 'selected-sub-tab': route.name === 'result'}]">実績管理</div>
                <div v-if="hasPrivilage" @click="router.push({name: 'plan'})" :class="['sub-tab-item', { 'selected-sub-tab': route.name === 'plan'}]">損益</div>
            </div>
        </div> -->

        <div v-if="selectedProject" class="h-[calc(100%-60px)] overflow-y-auto">
            <div class="flex items-center gap-4 static flex-wrap md:flex-nowrap px-5 md:justify-normal justify-center">
                <div class="text-sm"><span class="p-[5px] text-xs bg-[var(--bg3)] mr-[10px]">期間</span> {{ selectedProject?.date_start && selectedProject.date_end ? `${DateTime.fromISO(selectedProject.date_start).toLocaleString(DateTime.DATE_SHORT)}  ~  ${DateTime.fromISO(selectedProject.date_end).toLocaleString(DateTime.DATE_SHORT)}` : '未設定' }}</div>
                <div class="flex items-center gap-3 relative justify-end ml-auto">
                    <div class="flex items-center gap-2">
                        <span v-if="previousMonthCount" class="side-notification side-notification--comment-only" style="position: static">{{ previousMonthCount }}</span>
                        <div @click="shiftMonth(-1)" class="flex items-center justify-center h-[30px] w-fit gap-2 min-w-[30px]">
                            <Back size="13"/>
                        </div>
                    </div>
                    <PeriodRangePicker
                        :start="periodStartIso"
                        :end="periodEndIso"
                        :max-months="isMobile() ? 1 : 12"
                        :periodBadge="financeCommentBadgeByPeriod"
                        :totalBadge="thisMonthCount"
                        @change="handleRangeChange"
                    />
                    <div class="flex items-center gap-2">
                        <div @click="shiftMonth(1)" class="flex items-center justify-center h-[30px] w-fit gap-2 min-w-[30px]">
                            <Back size="13" class="rotate-180"/>
                        </div>
                        <span v-if="nextMonthCount" class="side-notification side-notification--comment-only" style="position: static">{{ nextMonthCount }}</span>
                    </div>
                </div>
            </div>
            <div class="mb-[20px] mt-4 flex justify-end px-[20px] gap-4">
                <LoaderButton @triggered="router.push({name: 'total-finance'})" style="margin: 0;" content="集計" :loading="false"/>
            </div>
            <div class="overflow-x-auto whitespace-nowrap m-5 pb-2">
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
                                    <button
                                        v-if="hasPrivilage"
                                        class="comment-trigger"
                                        type="button"
                                        @click.stop="openComment(p.period)"
                                    >
                                        <svg fill="var(--primary-color)" height="14" version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 33">
                                            <path d="M10.788 8.109c1.574-0.063 3.148-0.083 4.711-0.104l2.356-0.031 2.356-0.010 2.356 0.010c0.782 0 1.574 0.021 2.356 0.031 1.574 0.031 3.148 0.063 4.711 0.136 0.459 0.021 0.823 0.417 0.803 0.876-0.021 0.438-0.375 0.771-0.803 0.792-1.574 0.073-3.148 0.115-4.711 0.136-0.782 0.010-1.574 0.031-2.356 0.031l-2.345 0.021-2.356-0.010-2.356-0.031c-1.574-0.021-3.148-0.052-4.711-0.104-0.479-0.021-0.855-0.417-0.844-0.896 0.010-0.459 0.386-0.823 0.834-0.844zM10.788 13.050c1.574-0.052 3.148-0.083 4.711-0.104l2.356-0.031 2.356-0.010 2.356 0.010c0.782 0 1.574 0.021 2.356 0.031 1.574 0.031 3.148 0.063 4.711 0.136 0.459 0.021 0.823 0.417 0.803 0.876-0.021 0.438-0.375 0.771-0.803 0.792-1.574 0.073-3.148 0.115-4.711 0.136-0.782 0.010-1.574 0.031-2.356 0.031l-2.356 0.010-2.356-0.010-2.356-0.031c-1.574-0.021-3.148-0.052-4.711-0.104-0.479-0.021-0.855-0.417-0.844-0.907 0.021-0.438 0.396-0.803 0.844-0.823zM10.788 17.991c0.74-0.052 1.491-0.083 2.231-0.104l1.115-0.031c0.375-0.010 0.74-0.010 1.115-0.010 0.74 0 1.491 0.010 2.231 0.042 0.75 0.031 1.491 0.063 2.231 0.136 0.459 0.052 0.803 0.459 0.75 0.928-0.042 0.407-0.365 0.709-0.75 0.75-0.75 0.073-1.491 0.115-2.231 0.136-0.75 0.031-1.491 0.042-2.231 0.042-0.375 0-0.74 0-1.115-0.010l-1.115-0.031c-0.74-0.021-1.491-0.052-2.231-0.104-0.479-0.042-0.844-0.459-0.803-0.938 0.031-0.427 0.375-0.771 0.803-0.803z"></path><path d="M39.432 11.393c-0.188-1.063-0.521-2.116-0.99-3.106-0.479-0.99-1.105-1.897-1.835-2.71s-1.564-1.511-2.45-2.106c-0.886-0.594-1.835-1.084-2.794-1.501-1.939-0.813-3.95-1.313-5.973-1.605s-4.055-0.396-6.066-0.365c-2.022 0.042-4.055 0.219-6.066 0.605-2.012 0.396-4.013 1.001-5.889 1.949-0.938 0.479-1.845 1.042-2.679 1.699-0.834 0.667-1.616 1.428-2.272 2.293-0.667 0.855-1.209 1.824-1.605 2.835-0.396 1.021-0.636 2.095-0.74 3.169-0.052 0.532-0.052 1.084-0.042 1.605 0.010 0.532 0.052 1.053 0.125 1.584 0.146 1.053 0.417 2.116 0.844 3.117s1.011 1.939 1.72 2.762c0.709 0.823 1.532 1.532 2.418 2.126 1.772 1.188 3.44 1.824 5.41 2.356 1.803 0.49 3.867 0.782 5.681 0.876 0.146 0.010 0.281 0.073 0.386 0.177 0.459 0.5 0.938 1.074 1.449 1.511 0.667 0.584 1.407 1.126 2.178 1.584 0.761 0.448 1.564 0.803 2.387 1.115 0.865 0.313 2.21 0.605 2.929 0.657 0.698 0.052 0.782-0.479 0.563-0.938-0.229-0.469-0.281-0.552-0.375-0.761s-0.188-0.417-0.271-0.625-0.344-0.844-0.49-1.261c-0.115-0.344-0.292-0.938-0.386-1.407-0.031-0.167 0.083-0.323 0.25-0.344 1.626-0.229 3.242-0.552 4.847-1.032 0.98-0.292 1.939-0.657 2.877-1.094s1.855-0.98 2.7-1.626c0.844-0.646 1.626-1.418 2.272-2.293 0.323-0.438 0.615-0.907 0.865-1.397s0.459-0.99 0.636-1.511c0.344-1.032 0.532-2.106 0.594-3.169 0.021-1.032-0.021-2.106-0.208-3.169zM37.347 14.478c-0.031 0.896-0.167 1.782-0.427 2.616-0.125 0.417-0.292 0.823-0.479 1.22s-0.407 0.771-0.657 1.126c-0.5 0.719-1.115 1.365-1.814 1.928-1.397 1.126-3.106 1.928-4.899 2.522-0.896 0.302-1.814 0.542-2.752 0.75-0.928 0.208-1.876 0.375-2.835 0.511h-0.031c-0.396 0.063-0.709 0.396-0.719 0.813-0.010 0.594 0.083 1.126 0.208 1.626s0.292 0.969 0.469 1.438c0.146 0.375 0.292 0.698 0.542 1.105 0.042 0.073-0.021 0.146-0.104 0.125-1.167-0.365-2.304-0.907-3.461-1.845-1.23-0.99-1.762-1.584-2.814-2.835-0.146-0.177-0.365-0.302-0.615-0.323h-0.031c-1.908-0.188-3.805-0.479-5.629-0.98-1.814-0.5-3.565-1.199-5.055-2.22-0.74-0.511-1.407-1.105-1.97-1.772-0.563-0.678-1.022-1.418-1.355-2.231s-0.552-1.678-0.657-2.564-0.125-1.824-0.031-2.689c0.104-0.876 0.313-1.73 0.646-2.543 0.334-0.803 0.771-1.564 1.324-2.251 1.115-1.386 2.595-2.481 4.232-3.273 0.823-0.396 1.678-0.74 2.564-1.022s1.793-0.511 2.71-0.678c1.845-0.354 3.742-0.511 5.639-0.532 1.907-0.010 3.815 0.073 5.67 0.344 1.866 0.271 3.69 0.709 5.378 1.418 1.689 0.698 3.242 1.668 4.44 2.95 0.594 0.636 1.105 1.355 1.491 2.126s0.667 1.605 0.834 2.481c0.167 0.855 0.219 1.751 0.188 2.658z"></path>
                                        </svg>
                                        <span v-if="commentCount && commentCount[p.period]" class="text-xs">{{ commentCount[p.period] }}</span>
                                        <span v-if="financeCommentBadgeByPeriod && financeCommentBadgeByPeriod[p.period]" style="position: static; text-indent: inherit;" class="side-notification side-notification--comment-only">{{ financeCommentBadgeByPeriod[p.period] }}</span>
                                    </button>
                                </div>
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
                                <td class="h-cell border-r [border-right-style:solid] border-[var(--calendarBorder)]">予算</td>
                                <template v-for="p in periods" :key="p.period">
                                    <td>
                                        <div class="inner-col"><span class="mobile">売上</span>{{ amountOfMoneyParser(yearlyPlanData?.[p.period]?.sales ?? NaN) }}</div>
                                    </td>
                                    <td>
                                        <div class="inner-col"><span class="mobile">販管費</span>{{ amountOfMoneyParser(yearlyPlanData?.[p.period]?.expense ?? NaN) }}</div>
                                    </td>
                                    <td>
                                        <div class="inner-col"><span class="mobile">利益</span>{{ amountOfMoneyParser(profitCalculate(yearlyPlanData?.[p.period]?.sales, yearlyPlanData?.[p.period]?.expense) ?? NaN) }}</div>
                                    </td>
                                    <td>
                                <div class="inner-col"><span class="mobile">利益率</span>{{ formatRate(yearlyPlanData?.[p.period]?.profit_rate ?? null) }}</div>
                            </td>
                        </template>
                        </tr>
                    </template>
                    <template v-else>
                        <tr>
                            <td class="h-cell">予算</td>
                            <template v-for="p in periods" :key="p.period">
                                <CellLoader :order="num" v-for="num in cellloadNum"/>
                            </template>
                        </tr>
                    </template>
                    <template v-if="!loaderProfit">
                        <tr>
                            <td class="h-cell border-r [border-right-style:solid] border-[var(--calendarBorder)]">損益</td>
                                <template v-for="p in periods" :key="p.period">
                                    <td>
                                        <div class="flex items-center gap-[5px] w-full">
                                            <div class="inner-col"><span class="mobile">売上</span>
                                                {{ amountOfMoneyParser(profitData?.[p.period]?.sales ?? NaN) }}
                                            </div>
                                            <DeltaNumbers type="sales" :actual="profitData?.[p.period]?.sales ?? 0" :planned="yearlyPlanData?.[p.period]?.sales ?? 0"/>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="flex items-center gap-[5px] w-full">
                                            <div class="inner-col"><span class="mobile">販管費</span>
                                                {{ amountOfMoneyParser(profitData?.[p.period]?.expense ?? NaN) }}
                                            </div>
                                            <DeltaNumbers type="expense" :actual="profitData?.[p.period]?.expense ?? 0" :planned="yearlyPlanData?.[p.period]?.expense ?? 0"/>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="flex items-center gap-[5px] w-full">
                                            <div class="inner-col"><span class="mobile">利益</span>{{ amountOfMoneyParser(profitData?.[p.period]?.profit  ?? NaN) }}</div>
                                            <DeltaNumbers type="profit" :actual="profitData?.[p.period]?.profit ?? 0" :planned="profitCalculate(yearlyPlanData?.[p.period]?.sales, yearlyPlanData?.[p.period]?.expense) ?? 0"/>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="flex items-center gap-[5px] w-full">
                                        <div class="inner-col"><span class="mobile">利益率</span>{{ formatRate(profitData?.[p.period]?.profit_rate ?? null) }}</div>
                                            <DeltaNumbers type="profit_rate" :actual="profitData?.[p.period]?.profit_rate ?? 0" :planned="yearlyPlanData?.[p.period]?.profit_rate ?? 0"/>
                                        </div>
                                    </td>
                                </template>
                            </tr>
                        </template>
                        <template v-else>
                            <tr>
                                <td class="h-cell">損益</td>
                                <template v-for="p in periods" :key="p.period">
                                    <CellLoader :order="num" v-for="num in cellloadNum"/>
                                </template>
                            </tr>
                        </template>
                        <template v-if="!loaderSettlement">
                            <tr>
                                <td class="h-cell border-r [border-right-style:solid] border-[var(--calendarBorder)]">
                                    <span>実績</span>
                                    <button
                                        v-if="hasPrivilage && isMobile()"
                                        class="comment-trigger"
                                        type="button"
                                        @click.stop="openComment(periodStartIso)"
                                    >
                                        <svg fill="var(--primary-color)" height="14" version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 33">
                                            <path d="M10.788 8.109c1.574-0.063 3.148-0.083 4.711-0.104l2.356-0.031 2.356-0.010 2.356 0.010c0.782 0 1.574 0.021 2.356 0.031 1.574 0.031 3.148 0.063 4.711 0.136 0.459 0.021 0.823 0.417 0.803 0.876-0.021 0.438-0.375 0.771-0.803 0.792-1.574 0.073-3.148 0.115-4.711 0.136-0.782 0.010-1.574 0.031-2.356 0.031l-2.345 0.021-2.356-0.010-2.356-0.031c-1.574-0.021-3.148-0.052-4.711-0.104-0.479-0.021-0.855-0.417-0.844-0.896 0.010-0.459 0.386-0.823 0.834-0.844zM10.788 13.050c1.574-0.052 3.148-0.083 4.711-0.104l2.356-0.031 2.356-0.010 2.356 0.010c0.782 0 1.574 0.021 2.356 0.031 1.574 0.031 3.148 0.063 4.711 0.136 0.459 0.021 0.823 0.417 0.803 0.876-0.021 0.438-0.375 0.771-0.803 0.792-1.574 0.073-3.148 0.115-4.711 0.136-0.782 0.010-1.574 0.031-2.356 0.031l-2.356 0.010-2.356-0.010-2.356-0.031c-1.574-0.021-3.148-0.052-4.711-0.104-0.479-0.021-0.855-0.417-0.844-0.907 0.021-0.438 0.396-0.803 0.844-0.823zM10.788 17.991c0.74-0.052 1.491-0.083 2.231-0.104l1.115-0.031c0.375-0.010 0.74-0.010 1.115-0.010 0.74 0 1.491 0.010 2.231 0.042 0.75 0.031 1.491 0.063 2.231 0.136 0.459 0.052 0.803 0.459 0.75 0.928-0.042 0.407-0.365 0.709-0.75 0.75-0.75 0.073-1.491 0.115-2.231 0.136-0.75 0.031-1.491 0.042-2.231 0.042-0.375 0-0.74 0-1.115-0.010l-1.115-0.031c-0.74-0.021-1.491-0.052-2.231-0.104-0.479-0.042-0.844-0.459-0.803-0.938 0.031-0.427 0.375-0.771 0.803-0.803z"></path><path d="M39.432 11.393c-0.188-1.063-0.521-2.116-0.99-3.106-0.479-0.99-1.105-1.897-1.835-2.71s-1.564-1.511-2.45-2.106c-0.886-0.594-1.835-1.084-2.794-1.501-1.939-0.813-3.95-1.313-5.973-1.605s-4.055-0.396-6.066-0.365c-2.022 0.042-4.055 0.219-6.066 0.605-2.012 0.396-4.013 1.001-5.889 1.949-0.938 0.479-1.845 1.042-2.679 1.699-0.834 0.667-1.616 1.428-2.272 2.293-0.667 0.855-1.209 1.824-1.605 2.835-0.396 1.021-0.636 2.095-0.74 3.169-0.052 0.532-0.052 1.084-0.042 1.605 0.010 0.532 0.052 1.053 0.125 1.584 0.146 1.053 0.417 2.116 0.844 3.117s1.011 1.939 1.72 2.762c0.709 0.823 1.532 1.532 2.418 2.126 1.772 1.188 3.44 1.824 5.41 2.356 1.803 0.49 3.867 0.782 5.681 0.876 0.146 0.010 0.281 0.073 0.386 0.177 0.459 0.5 0.938 1.074 1.449 1.511 0.667 0.584 1.407 1.126 2.178 1.584 0.761 0.448 1.564 0.803 2.387 1.115 0.865 0.313 2.21 0.605 2.929 0.657 0.698 0.052 0.782-0.479 0.563-0.938-0.229-0.469-0.281-0.552-0.375-0.761s-0.188-0.417-0.271-0.625-0.344-0.844-0.49-1.261c-0.115-0.344-0.292-0.938-0.386-1.407-0.031-0.167 0.083-0.323 0.25-0.344 1.626-0.229 3.242-0.552 4.847-1.032 0.98-0.292 1.939-0.657 2.877-1.094s1.855-0.98 2.7-1.626c0.844-0.646 1.626-1.418 2.272-2.293 0.323-0.438 0.615-0.907 0.865-1.397s0.459-0.99 0.636-1.511c0.344-1.032 0.532-2.106 0.594-3.169 0.021-1.032-0.021-2.106-0.208-3.169zM37.347 14.478c-0.031 0.896-0.167 1.782-0.427 2.616-0.125 0.417-0.292 0.823-0.479 1.22s-0.407 0.771-0.657 1.126c-0.5 0.719-1.115 1.365-1.814 1.928-1.397 1.126-3.106 1.928-4.899 2.522-0.896 0.302-1.814 0.542-2.752 0.75-0.928 0.208-1.876 0.375-2.835 0.511h-0.031c-0.396 0.063-0.709 0.396-0.719 0.813-0.010 0.594 0.083 1.126 0.208 1.626s0.292 0.969 0.469 1.438c0.146 0.375 0.292 0.698 0.542 1.105 0.042 0.073-0.021 0.146-0.104 0.125-1.167-0.365-2.304-0.907-3.461-1.845-1.23-0.99-1.762-1.584-2.814-2.835-0.146-0.177-0.365-0.302-0.615-0.323h-0.031c-1.908-0.188-3.805-0.479-5.629-0.98-1.814-0.5-3.565-1.199-5.055-2.22-0.74-0.511-1.407-1.105-1.97-1.772-0.563-0.678-1.022-1.418-1.355-2.231s-0.552-1.678-0.657-2.564-0.125-1.824-0.031-2.689c0.104-0.876 0.313-1.73 0.646-2.543 0.334-0.803 0.771-1.564 1.324-2.251 1.115-1.386 2.595-2.481 4.232-3.273 0.823-0.396 1.678-0.74 2.564-1.022s1.793-0.511 2.71-0.678c1.845-0.354 3.742-0.511 5.639-0.532 1.907-0.010 3.815 0.073 5.67 0.344 1.866 0.271 3.69 0.709 5.378 1.418 1.689 0.698 3.242 1.668 4.44 2.95 0.594 0.636 1.105 1.355 1.491 2.126s0.667 1.605 0.834 2.481c0.167 0.855 0.219 1.751 0.188 2.658z"></path>
                                        </svg>
                                        <span v-if="commentCount && commentCount[periodStartIso]" class="text-xs">{{ commentCount[periodStartIso] }}</span>
                                        <span v-if="financeCommentBadgeByPeriod && financeCommentBadgeByPeriod[periodStartIso]" style="position: static; text-indent: inherit;" class="side-notification side-notification--comment-only">{{ financeCommentBadgeByPeriod[periodStartIso] }}</span>
                                    </button>
                                </td>
                                <template v-for="p in periods" :key="p.period">
                                    <td>
                                        <div class="flex items-center gap-[5px] w-full">
                                            <div class="inner-col"><span class="mobile">売上</span>{{ amountOfMoneyParser(settlementData?.[p.period]?.sales ?? NaN) }}</div>
                                            <DeltaNumbers type="sales" :actual="settlementData?.[p.period]?.sales ?? 0" :planned="profitData?.[p.period]?.sales ?? 0"/>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="flex items-center gap-[5px] w-full">
                                            <div class="inner-col"><span class="mobile">販管費</span>{{ amountOfMoneyParser(settlementData?.[p.period]?.expense ?? NaN) }}</div>
                                            <DeltaNumbers type="expense" :actual="settlementData?.[p.period]?.expense ?? 0" :planned="profitData?.[p.period]?.expense ?? 0"/>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="flex items-center gap-[5px] w-full">
                                            <div class="inner-col"><span class="mobile">利益</span>{{ amountOfMoneyParser(settlementData?.[p.period]?.profit ?? NaN) }}</div>
                                            <DeltaNumbers type="profit" :actual="settlementData?.[p.period]?.profit ?? 0" :planned="profitData?.[p.period]?.profit ?? 0"/>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="flex items-center gap-[5px] w-full">
                                            <div class="inner-col"><span class="mobile">利益率</span>{{ formatRate(settlementData?.[p.period]?.profit_rate ?? null) }}</div>
                                            <DeltaNumbers type="profit_rate" :actual="settlementData?.[p.period]?.profit_rate ?? 0" :planned="profitData?.[p.period]?.profit_rate ?? 0"/>
                                        </div>
                                    </td>
                                </template>
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
                                        <div class="inner-col variance-cell">
                                            <span class="mobile">利益率</span>{{ formatVariance('profit_rate', variancePercentMap[p.period]?.profit_rate ?? null) }}
                                        </div>
                                    </td>
                                </template>
                            </tr>
                        </template>
                    </tbody>
                </table>

            </div>
            <div v-if="loaderActualResult" class="actual-breakdown actual-breakdown--loading">
                実績内訳を読み込み中...
            </div>
            <div v-else-if="hasActualBreakdown" class="actual-breakdown">
                <div class="actual-breakdown__header">
                    <span>実績内訳</span>
                </div>
                <div class="actual-breakdown__months">
                    <section
                        v-for="row in actualBreakdownRows"
                        :key="row.period"
                        class="actual-breakdown__month"
                    >
                        <div class="actual-breakdown__month-header">
                            <span>{{ row.label }}</span>
                            <span v-if="row.department.manual_adjusted" class="actual-breakdown__badge">手動編集あり</span>
                        </div>
                        <div class="actual-breakdown__statement">
                            <div class="actual-breakdown__group">
                                <div class="actual-breakdown__total-row">
                                    <span>売上合計</span>
                                    <span class="actual-breakdown__value">{{ formatActualAmount(row.department.sales) }}</span>
                                </div>
                                <div
                                    v-for="line in row.salesLines"
                                    :key="`${row.period}-sales-${line.key}`"
                                    class="actual-breakdown__line actual-breakdown__line--child"
                                >
                                    <span>{{ line.label }}</span>
                                    <span class="actual-breakdown__value">{{ formatActualAmount(line.amount) }}</span>
                                </div>
                            </div>
                            <div
                                class="actual-breakdown__group"
                            >
                                <div class="actual-breakdown__total-row">
                                    <span>費用合計</span>
                                    <span class="actual-breakdown__value">{{ formatActualAmount(row.department.total_expenses) }}</span>
                                </div>
                                <div
                                    v-for="line in row.expenseLines"
                                    :key="`${row.period}-expense-${line.key}`"
                                    class="actual-breakdown__line actual-breakdown__line--child"
                                >
                                    <span>
                                        {{ line.label }}
                                        <!-- <span v-if="line.meta" class="actual-breakdown__meta">{{ line.meta }}</span> -->
                                    </span>
                                    <span class="actual-breakdown__value">{{ formatActualAmount(line.amount) }}</span>
                                </div>
                                <div
                                    v-if="row.department.accounts_restricted"
                                    class="actual-breakdown__line actual-breakdown__line--child"
                                >
                                    <span>給与関連の明細は権限により非表示</span>
                                </div>
                            </div>
                            <div class="actual-breakdown__result">
                                <div class="actual-breakdown__total-row actual-breakdown__total-row--result">
                                    <span>利益</span>
                                    <span class="actual-breakdown__value">{{ formatActualAmount(row.department.real_profit) }}</span>
                                </div>
                                <div class="actual-breakdown__total-row actual-breakdown__total-row--result">
                                    <span>利益率</span>
                                    <span class="actual-breakdown__value">{{ formatRate(row.department.real_margin) }}</span>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
            <Transition name="smLoad">
                <CommentWindow
                    v-if="commentView && selectedCommentPeriod"
                    type="実績"
                    :currentProjectId="selectedProject.id"
                    :currentProjectName="selectedProject.name"
                    :period="selectedCommentPeriod"
                    @close="commentView = false; selectedCommentPeriod = null"
                    @getCommentCounts="getCommentCounts"
                    @goToPeriod="selectedCommentPeriod = $event"
                    @navigateUnread="onNavigateUnread"
                />
            </Transition>

        </div>

        <!-- <router-view
            :has-privilage="hasPrivilage"
            :year="defaultFiscalYear"
            :month="month"
        /> -->
    <!-- </div> -->
</template>
<script setup lang="ts">
import Back from '@/components/Icons/Back.vue';
import { DateTime, MonthNumbers } from 'luxon';
import { computed, onMounted, ref } from 'vue';
import { amountOfMoneyParser } from '@/utils/tools';
import CellLoader from './CellLoader.vue';
import { RouterView, useRoute, useRouter } from 'vue-router';
import LoaderButton from '@/components/Global/LoaderButton.vue';
import DeltaNumbers from './DeltaNumbers.vue';
import { useApi } from '@/composables/api';
import CommentWindow from './CommentWindow.vue';
import { useBadgeStore } from '@/store/badge';
import PeriodRangePicker from './PeriodRangePicker.vue';
import { isMobile } from '@/utils/tools';
import { useTutorialStore } from '@/store/tutorial';
import { useTour } from '@/composables/useTour';
import { useProject } from '@/composables/project';
import type { ActualAccount, ActualDepartment, ActualResultDepartmentResponse } from '@/interface/actualResultInterface';
const props = defineProps<{
    hasPrivilage: boolean
}>();
const commentView = ref(false)
const selectedCommentPeriod = ref<string | null>(null)
const loaderYP = ref(true)
const loaderSettlement = ref(true)
const loaderProfit = ref(true)
const loaderActualResult = ref(true)
const route = useRoute()
const router = useRouter()
const badge = useBadgeStore()
const api = useApi()
const { selectedProject } = useProject()
const parsePeriodParam = (value: unknown): DateTime | null => {
  if (typeof value !== 'string' || !value) return null
  const dt = DateTime.fromISO(`${value}-01`)
  return dt.isValid ? dt.startOf('month') : null
}

const initialEnd =
  parsePeriodParam(route.query.period_end) ??
  parsePeriodParam(route.query.period) ??
  DateTime.now().minus({ months: 1 }).startOf('month')

const defaultFiscalYear = initialEnd.year
const parsedStart = parsePeriodParam(route.query.period_start)
const fallbackStart = DateTime.fromObject({ year: defaultFiscalYear, month: initialEnd.month, day: 1 }).startOf('month')

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
const financeCommentBadgeByPeriod = computed(() => {
    return badge.financeCommentBadgeByFilter({by: 'project_id', value: Number(route.params.projectId)})?.period_counts || {}
})

const normalizedRange = normalizeRange(parsedStart ?? fallbackStart, initialEnd)

const periodStart = ref<DateTime>(normalizedRange.start)
const periodEnd = ref<DateTime>(normalizedRange.end)

const periodStartIso = computed(() => periodStart.value.toFormat('yyyy-MM'))
const periodEndIso = computed(() => periodEnd.value.toFormat('yyyy-MM'))
// 表示中の期間より後（未来方向）で、未読コメントが残っている最も近い月。
// 例：5月表示で12月にだけ未読がある場合でも、12月の件数を表示し続ける。
const nextMonthKey = computed<string | null>(() => {
  const counts = financeCommentBadgeByPeriod.value
  const keys = Object.keys(counts)
    .filter(p => (counts[p] ?? 0) > 0 && p > periodEndIso.value)
    .sort() // yyyy-MM は文字列ソートで時系列順（年跨ぎも含む）
  return keys.length ? keys[0] : null // 直近 = 最も小さいキー
})
// 表示中の期間より前（過去方向）で、未読コメントが残っている最も近い月。
const previosMonthKey = computed<string | null>(() => {
  const counts = financeCommentBadgeByPeriod.value
  const keys = Object.keys(counts)
    .filter(p => (counts[p] ?? 0) > 0 && p < periodStartIso.value)
    .sort()
  return keys.length ? keys[keys.length - 1] : null // 直近 = 最も大きいキー
})
const nextMonthCount = computed(() =>
  nextMonthKey.value ? (financeCommentBadgeByPeriod.value[nextMonthKey.value] ?? 0) : 0
)
const previousMonthCount = computed(() =>
  previosMonthKey.value ? (financeCommentBadgeByPeriod.value[previosMonthKey.value] ?? 0) : 0
)
const thisMonthCount = computed(() => {
    const badge = financeCommentBadgeByPeriod.value
    if (!badge) return 0
    if (periodStartIso.value === periodEndIso.value) {
        return badge[periodStartIso.value] ?? 0
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
        sum += badge[key] ?? 0
        cursor = cursor.plus({ months: 1 })
    }

    return sum
})
const year = ref<number>(periodEnd.value.year)
const month = ref<MonthNumbers>(periodEnd.value.month as MonthNumbers)


const commentCount = ref<Record<string, number>>()

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

const actualResultDepartments = ref<Record<string, ActualDepartment | null>>({})

const profitCalculate = (sales: number | null, expense: number | null) => {
    return sales && expense ? sales - expense : null
}

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

const openComment = (period: string) => {
  if (!props.hasPrivilage) return
  selectedCommentPeriod.value = period
  commentView.value = true
}
// 別プロジェクトの未読コメントへ遷移する。
// プロジェクト切替で finance ページが再マウントされ、onMounted が comment_period クエリを読んで自動で開く。
const onNavigateUnread = ({ projectId, period }: { projectId: number, period: string }) => {
  // ダッシュボードのコメント遷移と同じパターン（period で表示月、comment_period で自動オープン）
  router.push({
    name: 'finance',
    params: { projectId },
    query: { period, comment_period: period },
  })
}
const tutorialStore = useTutorialStore()
const { startTour } = useTour()
onMounted(async() => {
    const commentPeriod = parsePeriodParam(route.query.comment_period)
    if (commentPeriod) {
        applyRange(commentPeriod, commentPeriod, { skipRefresh: true })
        selectedCommentPeriod.value = commentPeriod.toFormat('yyyy-MM')
        commentView.value = true
    } else {
        updateRouteQuery()
    }
    refreshFinanceData()
    if (tutorialStore.state.active && tutorialStore.state.name.includes('project.details.finance')) {
        setTimeout(() => {
            startTour('project.details.finance.performance', { version: '2025-09' });
        }, 200);
        tutorialStore.setTutorial({ active: true, name: ['project.details.finance.performance'] });
    }
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
    return 'ー'
  }
  return `${sign}${value.toFixed(2)}%`
}

const formatRate = (value: number | null | undefined) => {
  if (value == null || Number.isNaN(Number(value))) return '—'
  return `${Number(value).toFixed(2)}%`
}

const formatActualAmount = (value: unknown) => {
    const num = toNumeric(value)
    if (num === null || Number.isNaN(num)) return '—'

    return amountOfMoneyParser(num)
}

type ActualBreakdownLine = {
    key: string;
    label: string;
    amount: number;
    meta?: string;
}

const actualBreakdownSalesLines = (department: ActualDepartment): ActualBreakdownLine[] => [
    {
        key: 'external_sales',
        label: '売上高',
        amount: toNumeric(department.external_sales) ?? 0,
    },
    {
        key: 'internal_sales',
        label: '内部売上',
        amount: toNumeric(department.internal_sales) ?? 0,
    },
]

const actualBreakdownFallbackExpenseLines = (department: ActualDepartment): ActualBreakdownLine[] => [
    { key: 'cost_of_goods_sold', label: '売上原価', amount: toNumeric(department.cost_of_goods_sold) ?? 0 },
    { key: 'sg_and_a_expenses', label: '販管費', amount: toNumeric(department.sg_and_a_expenses) ?? 0 },
    { key: 'performance_bonus_reserve', label: '業績賞与', amount: toNumeric(department.performance_bonus_reserve) ?? 0 },
    { key: 'indirect_allocation_expense', label: '間接費配賦', amount: toNumeric(department.indirect_allocation_expense) ?? 0 },
    { key: 'basic_bonus_reserve', label: '基本賞与', amount: toNumeric(department.basic_bonus_reserve) ?? 0 },
    { key: 'paid_leave_reserve', label: '有給', amount: toNumeric(department.paid_leave_reserve) ?? 0 },
    { key: 'welfare_reserve', label: '福利厚生', amount: toNumeric(department.welfare_reserve) ?? 0 },
    { key: 'refresh_reserve', label: 'リフレッシュ', amount: toNumeric(department.refresh_reserve) ?? 0 },
].filter(line => line.amount !== 0)

const actualBreakdownExpenseLines = (department: ActualDepartment): ActualBreakdownLine[] => {
    const grouped = new Map<string, ActualBreakdownLine>()

    ;(department.accounts ?? [])
        .filter(account => account.category === 'expense')
        .forEach((account: ActualAccount) => {
            const amount = toNumeric(account.amount)
            if (amount === null || amount === 0) return

            const label = account.account_name || account.bucket_label || '費用'
            const meta = account.bucket_label && account.bucket_label !== label ? account.bucket_label : ''
            const key = `${label}|${meta}`
            const existing = grouped.get(key)

            if (existing) {
                existing.amount += amount
                return
            }

            grouped.set(key, {
                key,
                label,
                amount,
                meta,
            })
        })

    const lines = Array.from(grouped.values())

    return lines.length ? lines : actualBreakdownFallbackExpenseLines(department)
}

type ActualBreakdownRow = {
    period: string;
    label: string;
    department: ActualDepartment;
    salesLines: ActualBreakdownLine[];
    expenseLines: ActualBreakdownLine[];
}

const actualBreakdownRows = computed<ActualBreakdownRow[]>(() => {
    return periods.value.flatMap(p => {
        const department = actualResultDepartments.value[p.period]
        if (!department) return []

        return [{
            period: p.period,
            label: `${p.year}年${p.month}月`,
            department,
            salesLines: actualBreakdownSalesLines(department),
            expenseLines: actualBreakdownExpenseLines(department),
        }]
    })
})

const hasActualBreakdown = computed(() => actualBreakdownRows.value.length > 0)

const getYearlyPlan = async (token?: number) => {
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

            if (token != null && token !== refreshToken) return
            const months = fiscalMonthDates(fy)
            const rawEntries = response ?? {}
            months.forEach(dt => {
                const raw = rawEntries[String(dt.month)] ?? rawEntries[dt.month]
                assignBalance(aggregated, periodKey(dt.year, dt.month), normalizeBalanceEntry(raw ?? {}))
            })
        }))
        if (token != null && token !== refreshToken) return
        yearlyPlanData.value = aggregated
    } catch (error) {
        if (token != null && token !== refreshToken) return
        console.error('Failed to load yearly plan', error)
        yearlyPlanData.value = {}
    } finally {
        if (token == null || token === refreshToken) loaderYP.value = false
    }
}

const getSettlement = async (token?: number) => {
    loaderSettlement.value = true
    try {
        const aggregated: BalanceMap<SettlementColumn> = {}
        const fiscalYears = fiscalYearsInRange.value

        await Promise.all(fiscalYears.map(async fy => {
            const response = await api.get('/get_settlement', {
                project_id: route.params.projectId,
                month: month.value,
                start: periodStartIso.value,
                end: periodEndIso.value,
                year: fy
            }, {silent: true})
            if (token != null && token !== refreshToken) return

            const months = fiscalMonthDates(fy)
            const rawEntries = response ?? {}
            months.forEach(dt => {
                const raw = rawEntries[String(dt.month)] ?? rawEntries[dt.month]
                assignBalance(aggregated, periodKey(dt.year, dt.month), normalizeSettlementEntry(raw ?? {}))
            })
        }))
        if (token != null && token !== refreshToken) return
        settlementData.value = aggregated
    } catch (error) {
        if (token != null && token !== refreshToken) return
        console.error('Failed to load settlement data', error)
        settlementData.value = {}
    } finally {
        if (token == null || token === refreshToken) loaderSettlement.value = false
    }
}

const getActualResultDepartments = async (token?: number) => {
    loaderActualResult.value = true
    try {
        const response = await api.get(`/projects/${route.params.projectId}/actual-results`, {
            start: periodStartIso.value,
            end: periodEndIso.value,
        }, {silent: true}) as ActualResultDepartmentResponse

        if (token != null && token !== refreshToken) return
        actualResultDepartments.value = response?.months ?? {}
    } catch (error) {
        if (token != null && token !== refreshToken) return
        console.error('Failed to load actual result department data', error)
        actualResultDepartments.value = {}
    } finally {
        if (token == null || token === refreshToken) loaderActualResult.value = false
    }
}

let refreshToken = 0
const refreshFinanceData = async() => {
    const token = ++refreshToken

    await Promise.all([
        getYearlyPlan(token),
        getProfit(token),
        getSettlement(token),
        getActualResultDepartments(token),
        getCommentCounts(token),
        // getMetrics(),
    ])

    if (token !== refreshToken) return
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
const getProfit = async (token?: number) => {
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
            if (token != null && token !== refreshToken) return
            const months = fiscalMonthDates(fy)
            const rawEntries = response ?? {}
            months.forEach(dt => {
                const raw = rawEntries[String(dt.month)] ?? rawEntries[dt.month]
                assignBalance(aggregated, periodKey(dt.year, dt.month), normalizeBalanceEntry(raw ?? {}))
            })
        }))
        if (token != null && token !== refreshToken) return
        profitData.value = aggregated
    } catch (error) {
        if (token != null && token !== refreshToken) return
        console.error('Failed to load profit data', error)
        profitData.value = {}
    } finally {
        if (token == null || token === refreshToken) loaderProfit.value = false
    }
}
let refreshTimer: number | null = null

const applyRange = (start: DateTime, end: DateTime, options: { skipRefresh?: boolean } = {}) => {
    const normalized = normalizeRange(start.startOf('month'), end.startOf('month'))
    periodStart.value = normalized.start
    periodEnd.value = normalized.end
    year.value = normalized.end.year
    month.value = normalized.end.month as MonthNumbers
    updateRouteQuery()
    if (options.skipRefresh) return

    if (refreshTimer) window.clearTimeout(refreshTimer)
    refreshTimer = window.setTimeout(() => {
        refreshFinanceData()
    }, 150)
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
const getCommentCounts = async(token?: number) => {
    const data = await api.get(`/projects/${route.params.projectId}/finance-comments/monthly-count`, {
        period_start: periodStartIso.value,
        period_end: periodEndIso.value,
    }, { cancel: true });
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
.comment-trigger{
    display: inline-flex;
    align-items: center;
    gap: 5px;
    border: none;
    background: transparent;
    cursor: pointer;
    padding: 0;
    color: var(--primary-color);
}
.comment-trigger svg {
    overflow: visible;
}

.comment-total{
    font-size: 11px;
    color: var(--primary-color);
    line-height: 1;
}
.range-comment-total{
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: var(--bg3);
    padding: 4px 10px;
    border-radius: 999px;
    color: var(--primary-color);
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
.actual-breakdown{
    margin: 0 20px 24px;
    border: 1px solid var(--calendarBorder);
    background: var(--background-color);
    color: var(--primary-color);
    font-size: 12px;
}
.actual-breakdown--loading{
    padding: 12px 14px;
}
.actual-breakdown__header{
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 14px;
    border-bottom: 1px solid var(--calendarBorder);
    background: var(--bg3);
    font-weight: 500;
}
.actual-breakdown__months{
    display: flex;
    overflow: auto;
}
.actual-breakdown__month{
    padding: 12px 14px;
    border-bottom: 1px solid var(--calendarBorder);
    width: 100%;
    min-width: 250px;
}
.actual-breakdown__month:last-child{
    border-bottom: none;
}
.actual-breakdown__month-header{
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 10px;
    font-weight: 500;
}
.actual-breakdown__badge{
    border: 1px solid var(--calendarBorder);
    padding: 2px 7px;
    font-size: 11px;
    font-weight: 400;
    background: var(--bg2);
}
.actual-breakdown__statement{
    border-top: 1px solid var(--calendarBorder);
}
.actual-breakdown__group{
    border-bottom: 1px solid var(--calendarBorder);
}
.actual-breakdown__total-row,
.actual-breakdown__line{
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 8px 10px;
}
.actual-breakdown__total-row{
    background: var(--bg3);
    font-weight: 500;
}
.actual-breakdown__line{
    border-top: 1px dashed var(--calendarBorder);
}
.actual-breakdown__line--child{
    padding-left: 24px;
}
.actual-breakdown__line--child::before{
    content: "-";
    margin-right: -4px;
    opacity: 0.7;
}
.actual-breakdown__value{
    font-weight: 500;
    text-align: right;
    white-space: nowrap;
}
.actual-breakdown__meta{
    margin-left: 6px;
    opacity: 0.7;
    font-size: 11px;
}
// .actual-breakdown__result{
//     display: grid;
//     grid-template-columns: repeat(2, minmax(0, 1fr));
// }
.actual-breakdown__total-row--result{
    border-bottom: 1px solid var(--calendarBorder);
}
.actual-breakdown__total-row--result:last-child{
    border-bottom: none;
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
                    display: flex;
                    border-left: solid thin var(--calendarBorder);
                    border-right: solid thin var(--calendarBorder);
                    justify-content: space-between;
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
    .actual-breakdown__result{
        grid-template-columns: 1fr;
    }
    .actual-breakdown__total-row--result{
        border-right: none;
        border-bottom: 1px solid var(--calendarBorder);
    }
    .actual-breakdown__total-row--result:last-child{
        border-bottom: none;
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
