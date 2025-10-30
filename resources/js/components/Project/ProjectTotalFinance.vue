<template>
    <div class="overlay">
        <div class="projectModalInner" style="width: 100%;height: 100%;">
            <div class="projectModalMainHeader !bg-[var(--bg3)]">
                <p class="ml-[30px]">集計</p>
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
                <div class="projectModalSideMenu" id="mb-p-select"
                    :style="{ opacity: responsive.mobile && loader ? '0' : '1' }"
                    v-if="(menu.parent == 'mb-p-select' || !responsive.mobile)">
                    <div class="sub-tab-container sticky top-0 z-[5] bg-[var(--background-color)]">
                        <button @click="leftTab = 'project'"
                            :class="['sub-tab-item !bg-inherit', { 'selected-sub-tab': leftTab == 'project' }]">プロジェクト別</button>
                        <button @click="leftTab = 'manager'"
                            :class="['sub-tab-item !bg-inherit', { 'selected-sub-tab': leftTab == 'manager' }]">管理者別</button>
                    </div>
                    <div v-if="leftTab == 'project'" class="project-selector-left">
                        <label class="flex items-center gap-[15px] text-[14px] cursor-pointer" title="全て選択">
                            <input type="checkbox" name="project-selector"
                                @change="selectAllProjects" :checked="selectedProjects.length === projects.length">
                            <span class="text-[13px] overflow-hidden whitespace-nowrap text-ellipsis">全て選択</span>
                        </label>
                        <label v-for="project in projects" :title="project.name"
                            class="flex items-center gap-[15px] text-[14px] cursor-pointer">
                            <input type="checkbox" name="project-selector" :value="project.id"
                                v-model="selectedProjects">
                            <span class="text-[13px] overflow-hidden whitespace-nowrap text-ellipsis">{{ project.name }}</span>
                            <div v-if="showAnyArrow(project.name)" class="flex" title="対応が必要です。">
                                <svg fill="tomato" style="transform: rotate(180deg);" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 30 30">
                                    <path d="M14.978 0C6.735-.055-.129 6.931.002 15.153c-.028 8.166 6.815 14.939 14.976 14.811v-.04c.965.012 1.935-.068 2.889-.243 4.817-.861 9.056-4.274 10.937-8.8C32.986 11.04 25.688-.021 14.978 0m0 27.903C6.08 27.659-.075 18.755 3.433 10.373 7.813.292 22.129.294 26.49 10.385c3.512 8.225-2.605 17.404-11.512 17.518m-1.735-13.968c-.293 2.283-.156 4.58-.125 6.873l.166 2.289c.304 2.068 3.234 2.088 3.548 0 .186-1.523.193-3.051.205-4.58.028-1.53.044-3.058-.164-4.582-.334-2.082-3.284-2.104-3.63 0m-.344-4.565c.115.303.278.565.465.811.473.371 1.062.634 1.685.627 1.248.021 2.335-1.09 2.278-2.331-.015-.643-.308-1.218-.729-1.681-1.906-1.558-4.534.238-3.699 2.574"/>
                                </svg>
                            </div>
                            <BadgeLoader v-if="badgeLoader == 0" />
                        </label>
                    </div>
                    <div v-if="leftTab == 'manager'" class="project-selector-left">
                        <div v-for="manager in managers">
                            <label class="flex items-center gap-[15px] text-[14px] cursor-pointer">
                                <input type="checkbox" name="project-selector-by-manager"
                                    v-model="selectedManagers" :value="manager.id">
                                <UserPanel :user="manager" size="30" with-name disable-instant />
                            </label>
                            <div v-if="selectedManagers.includes(manager.id)" class="project-selector-left">
                                <label class="flex items-center gap-[15px] text-[14px] cursor-pointer">
                                    <input type="checkbox" name="project-selector"
                                        @change="toggleByManager($event, manager)" :checked="isChecked(manager)">
                                    <span>全て選択</span>
                                </label>
                                <label v-for="project in managersProjects(manager)"
                                    class="flex items-center gap-[15px] text-[14px] cursor-pointer">
                                    <input type="checkbox" name="project-selector"
                                        :value="project.id" v-model="selectedProjects">
                                    <span>{{ project.name }}</span>
                                </label>

                            </div>
                        </div>
                    </div>
                </div>

                <div class="projectModalContent relative !overflow-hidden">
                    <div class="cal-month-loader" style="height: 100%; top: 0;opacity: 0.6;" v-if="loader">
                        <div id="loaderMini">
                            <div class="spinner-mini"
                                style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
                        </div>
                    </div>
                    <div class="overflow-auto h-full">
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
                            <div class="flex items-center gap-[20px] relative w-full justify-end">
                                <button @click="adjustByOne(-1)" class="flex items-center justify-center h-[30px] w-[30px] min-w-[30px]" v-if="interval.startYear == interval.endYear && interval.startMonth == interval.endMonth">
                                    <Back size="13"/>
                                </button>
                                <button @click.stop="menu.setMenu({parent: 'intervalPicker'})" class="cursor-pointer text-[15px]">
                                    <template v-if="interval.startYear == interval.endYear && interval.startMonth == interval.endMonth">
                                        {{ `${interval.startYear}年${interval.startMonth}月` }}
                                    </template>
                                    <template v-else>
                                        <span>{{ `${interval.startYear}年${interval.startMonth}月` }}</span>
                                        <span class="text-[var(--primary-color)] mx-[10px]">~</span>
                                        <span>{{ `${interval.endYear}年${interval.endMonth}月` }}</span>
                                    </template>
                                </button>
                                <button @click="adjustByOne(1)" class="flex items-center justify-center h-[30px] w-[30px] min-w-[30px]" v-if="interval.startYear == interval.endYear && interval.startMonth == interval.endMonth">
                                    <Back size="13" class="rotate-180"/>
                                </button>
                                <Transition name="slidePop">
                                    <div v-if="menu.parent == 'intervalPicker'" id="intervalPicker" class="absolute top-[30px] right-0 shadow-me p-[20px] z-[5] bg-[var(--background-color)]">
                                        <div class="flex items-center gap-[20px]">
                                            <CommandButton :buttons="[
                                                {
                                                    title: '今月', action: () => {
                                                        interval.startYear = interval.endYear = DateTime.now().year
                                                        interval.startMonth = interval.endMonth = DateTime.now().month
                                                        getTotalFinance()
                                                    }
                                                },
                                                {
                                                    title: '今年', action: () => {
                                                        interval.startYear = interval.endYear = DateTime.now().year
                                                        interval.startMonth = 1
                                                        interval.endMonth = 12
                                                        getTotalFinance()
                                                    }
                                                }
                                            ]" />
                                        </div>
                                        <div class="flex flex-wrap mt-[20px] items-center w-max">
                                            <div class="flex items-center">
                                                <!-- Start Year -->
                                                <select ref="startYearRef" :value="interval.startYear"
                                                    class="appearance-none px-[10px] h-[30px] text-[13px] border border-solid border-[var(--primary-color)] cursor-pointer"
                                                    :class="[{ 'date-color': theme.dark }]">
                                                    <option
                                                        v-for="year in years"
                                                        :key="year.value" :value="year.value">
                                                        {{ year.label }}
                                                    </option>
                                                </select>

                                                <!-- Start Month -->
                                                <select ref="startMonthRef" :value="interval.startMonth"
                                                    class="appearance-none px-[10px] h-[30px] text-[13px] border border-solid border-[var(--primary-color)] cursor-pointer ml-[-1px]"
                                                    :class="[{ 'date-color': theme.dark }]">
                                                    <option v-for="month in months" :key="month.value" :value="month.value">
                                                        {{ month.label }}
                                                    </option>
                                                </select>
                                            </div>

                                            <div class="mx-[10px]">~</div>

                                            <div class="flex items-center">
                                                <!-- End Year -->
                                                <select ref="endYearRef" :value="interval.endYear"
                                                    class="appearance-none px-[10px] h-[30px] text-[13px] border border-solid border-[var(--primary-color)] cursor-pointer"
                                                    :class="[{ 'date-color': theme.dark }]">
                                                    <option
                                                        v-for="year in years"
                                                        :key="year.value" :value="year.value">
                                                        {{ year.label }}
                                                    </option>
                                                </select>

                                                <!-- End Month -->
                                                <select ref="endMonthRef" :value="interval.endMonth"
                                                    class="appearance-none px-[10px] h-[30px] text-[13px] border border-solid border-[var(--primary-color)] cursor-pointer ml-[-1px]"
                                                    :class="[{ 'date-color': theme.dark }]">
                                                    <option v-for="month in months" :key="month.value" :value="month.value">
                                                        {{ month.label }}
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="mt-[20px]">
                                            <CommandButton :buttons="[{title: '決定', action: () => setIntervalData()}]"/>
                                        </div>
                                    </div>
                                </Transition>
                            </div>
                        </div>
                        <div v-if="tab == 'table'">
                            <table>
                                <thead class="!top-[80px]">
                                    <tr>
                                        <th>プロジェクト名</th>
                                        <th>区分</th>
                                        <th>売上</th>
                                        <th>販管費</th>
                                        <th>利益</th>
                                        <th>利益率</th>
                                        <th v-if="hasPrivilage">コメント</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-for="(data, projectName) in financeData" :key="projectName">
                                        <tr>
                                            <td class="p-name" :rowspan="3">{{ projectName }}</td>
                                            <td class="sub-name">年度予算</td>
                                            <td>
                                                <div class="inner-col"><span class="mobile">売上</span>{{
                                                    amountOfMoneyParser(data.yearly_plan.sales) }}</div>
                                            </td>
                                            <td>
                                                <div class="inner-col"><span class="mobile">販管費</span>{{
                                                    amountOfMoneyParser(data.yearly_plan.expense) }}</div>
                                            </td>
                                            <td>
                                                <div class="inner-col"><span class="mobile">利益</span>{{
                                                    amountOfMoneyParser(data.yearly_plan.sales -
                                                    data.yearly_plan.expense) }}</div>
                                            </td>
                                            <td>
                                                <div class="inner-col"><span class="mobile">利益率</span>{{
                                                    percentizer(data.yearly_plan).display }}</div>
                                            </td>
                                            <td v-if="hasPrivilage"></td>
                                        </tr>
                                        <tr>

                                            <td class="sub-name">損益計画</td>
                                            <td>
                                                <div class="flex items-center gap-[5px]">
                                                    <div class="inner-col"><span class="mobile">売上</span>{{
                                                        amountOfMoneyParser(data.profit.sales) }}</div>
                                                    <DeltaNumbers type="sales" :planned="data.yearly_plan.sales"
                                                        :actual="data.profit.sales" />
                                                </div>
                                            </td>
                                            <td>
                                                <div class="flex items-center gap-[5px]">
                                                    <div class="inner-col"><span class="mobile">販管費</span>{{
                                                        amountOfMoneyParser(data.profit.expense) }}</div>
                                                    <DeltaNumbers type="expense" :planned="data.yearly_plan.expense"
                                                        :actual="data.profit.expense" />
                                                </div>
                                            </td>
                                            <td>
                                                <div class="flex items-center gap-[5px]">
                                                    <div class="inner-col"><span class="mobile">利益</span>{{
                                                        amountOfMoneyParser(data.profit.profit) }}
                                                    </div>
                                                    <DeltaNumbers type="profit"
                                                        :planned="data.yearly_plan.sales - data.yearly_plan.expense"
                                                        :actual="data.profit.profit" />
                                                </div>
                                            </td>
                                            <td>
                                                <div class="flex items-center gap-[5px]">
                                                    <div class="inner-col"><span class="mobile">利益率</span>{{
                                                        percentizer(data.profit).display }}</div>
                                                    <DeltaNumbers type="profit_rate"
                                                        :planned="percentizer(data.yearly_plan).value"
                                                        :actual="percentizer(data.profit).value" />
                                                </div>
                                            </td>
                                            <td v-if="hasPrivilage"></td>
                                        </tr>
                                        <tr>
                                            <td class="sub-name flex gap-1 items-center">
                                                <div v-if="showAnyArrow(projectName as string)" class="flex" title="コメントを残してください">
                                                    <svg fill="tomato" style="transform: rotate(180deg);" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 30 30">
                                                        <path d="M14.978 0C6.735-.055-.129 6.931.002 15.153c-.028 8.166 6.815 14.939 14.976 14.811v-.04c.965.012 1.935-.068 2.889-.243 4.817-.861 9.056-4.274 10.937-8.8C32.986 11.04 25.688-.021 14.978 0m0 27.903C6.08 27.659-.075 18.755 3.433 10.373 7.813.292 22.129.294 26.49 10.385c3.512 8.225-2.605 17.404-11.512 17.518m-1.735-13.968c-.293 2.283-.156 4.58-.125 6.873l.166 2.289c.304 2.068 3.234 2.088 3.548 0 .186-1.523.193-3.051.205-4.58.028-1.53.044-3.058-.164-4.582-.334-2.082-3.284-2.104-3.63 0m-.344-4.565c.115.303.278.565.465.811.473.371 1.062.634 1.685.627 1.248.021 2.335-1.09 2.278-2.331-.015-.643-.308-1.218-.729-1.681-1.906-1.558-4.534.238-3.699 2.574"/>
                                                    </svg>
                                                </div>
                                                実績
                                            </td>
                                            <td>
                                                <div class="flex items-center gap-[5px]">
                                                    <div class="inner-col"><span class="mobile">売上</span>{{
                                                        amountOfMoneyParser(data.settlement.sales) }}</div>
                                                    <DeltaNumbers type="sales" :planned="data.profit.sales"
                                                        :actual="data.settlement.sales" />
                                                </div>
                                            </td>
                                            <td>
                                                <div class="flex items-center gap-[5px]">
                                                    <div class="inner-col"><span class="mobile">販管費</span>{{
                                                        amountOfMoneyParser(data.settlement.expense) }}</div>
                                                    <DeltaNumbers type="expense" :planned="data.profit.expense"
                                                        :actual="data.settlement.expense" />
                                                </div>
                                            </td>
                                            <td>
                                                <div class="flex items-center gap-[5px]">
                                                    <div class="inner-col"><span class="mobile">利益</span>{{
                                                        amountOfMoneyParser(data.settlement.sales -
                                                        data.settlement.expense) }}</div>
                                                    <DeltaNumbers type="profit"
                                                        :planned="data.profit.profit"
                                                        :actual="data.settlement.sales - data.settlement.expense" />
                                                </div>
                                            </td>
                                            <td>
                                                <div class="flex items-center gap-[5px]">
                                                    <div class="inner-col"><span class="mobile">利益率</span>{{
                                                        percentizer(data.settlement).display }}</div>
                                                    <DeltaNumbers type="profit_rate"
                                                        :planned="percentizer(data.profit).value"
                                                        :actual="percentizer(data.settlement).value" />
                                                </div>
                                            </td>
                                            <td v-if="hasPrivilage">
                                                <div class="flex items-center gap-2 cursor-pointer">
                                                    <svg fill="var(--primary-color)" height="15" version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 33" @click="selectedId = data.settlement.id as number">
                                                        <path d="M10.788 8.109c1.574-0.063 3.148-0.083 4.711-0.104l2.356-0.031 2.356-0.010 2.356 0.010c0.782 0 1.574 0.021 2.356 0.031 1.574 0.031 3.148 0.063 4.711 0.136 0.459 0.021 0.823 0.417 0.803 0.876-0.021 0.438-0.375 0.771-0.803 0.792-1.574 0.073-3.148 0.115-4.711 0.136-0.782 0.010-1.574 0.031-2.356 0.031l-2.345 0.021-2.356-0.010-2.356-0.031c-1.574-0.021-3.148-0.052-4.711-0.104-0.479-0.021-0.855-0.417-0.844-0.896 0.010-0.459 0.386-0.823 0.834-0.844zM10.788 13.050c1.574-0.052 3.148-0.083 4.711-0.104l2.356-0.031 2.356-0.010 2.356 0.010c0.782 0 1.574 0.021 2.356 0.031 1.574 0.031 3.148 0.063 4.711 0.136 0.459 0.021 0.823 0.417 0.803 0.876-0.021 0.438-0.375 0.771-0.803 0.792-1.574 0.073-3.148 0.115-4.711 0.136-0.782 0.010-1.574 0.031-2.356 0.031l-2.356 0.010-2.356-0.010-2.356-0.031c-1.574-0.021-3.148-0.052-4.711-0.104-0.479-0.021-0.855-0.417-0.844-0.907 0.021-0.438 0.396-0.803 0.844-0.823zM10.788 17.991c0.74-0.052 1.491-0.083 2.231-0.104l1.115-0.031c0.375-0.010 0.74-0.010 1.115-0.010 0.74 0 1.491 0.010 2.231 0.042 0.75 0.031 1.491 0.063 2.231 0.136 0.459 0.052 0.803 0.459 0.75 0.928-0.042 0.407-0.365 0.709-0.75 0.75-0.75 0.073-1.491 0.115-2.231 0.136-0.75 0.031-1.491 0.042-2.231 0.042-0.375 0-0.74 0-1.115-0.010l-1.115-0.031c-0.74-0.021-1.491-0.052-2.231-0.104-0.479-0.042-0.844-0.459-0.803-0.938 0.031-0.427 0.375-0.771 0.803-0.803z"></path><path d="M39.432 11.393c-0.188-1.063-0.521-2.116-0.99-3.106-0.479-0.99-1.105-1.897-1.835-2.71s-1.564-1.511-2.45-2.106c-0.886-0.594-1.835-1.084-2.794-1.501-1.939-0.813-3.95-1.313-5.973-1.605s-4.055-0.396-6.066-0.365c-2.022 0.042-4.055 0.219-6.066 0.605-2.012 0.396-4.013 1.001-5.889 1.949-0.938 0.479-1.845 1.042-2.679 1.699-0.834 0.667-1.616 1.428-2.272 2.293-0.667 0.855-1.209 1.824-1.605 2.835-0.396 1.021-0.636 2.095-0.74 3.169-0.052 0.532-0.052 1.084-0.042 1.605 0.010 0.532 0.052 1.053 0.125 1.584 0.146 1.053 0.417 2.116 0.844 3.117s1.011 1.939 1.72 2.762c0.709 0.823 1.532 1.532 2.418 2.126 1.772 1.188 3.44 1.824 5.41 2.356 1.803 0.49 3.867 0.782 5.681 0.876 0.146 0.010 0.281 0.073 0.386 0.177 0.459 0.5 0.938 1.074 1.449 1.511 0.667 0.584 1.407 1.126 2.178 1.584 0.761 0.448 1.564 0.803 2.387 1.115 0.865 0.313 2.21 0.605 2.929 0.657 0.698 0.052 0.782-0.479 0.563-0.938-0.229-0.469-0.281-0.552-0.375-0.761s-0.188-0.417-0.271-0.625-0.344-0.844-0.49-1.261c-0.115-0.344-0.292-0.938-0.386-1.407-0.031-0.167 0.083-0.323 0.25-0.344 1.626-0.229 3.242-0.552 4.847-1.032 0.98-0.292 1.939-0.657 2.877-1.094s1.855-0.98 2.7-1.626c0.844-0.646 1.626-1.418 2.272-2.293 0.323-0.438 0.615-0.907 0.865-1.397s0.459-0.99 0.636-1.511c0.344-1.032 0.532-2.106 0.594-3.169 0.021-1.032-0.021-2.106-0.208-3.169zM37.347 14.478c-0.031 0.896-0.167 1.782-0.427 2.616-0.125 0.417-0.292 0.823-0.479 1.22s-0.407 0.771-0.657 1.126c-0.5 0.719-1.115 1.365-1.814 1.928-1.397 1.126-3.106 1.928-4.899 2.522-0.896 0.302-1.814 0.542-2.752 0.75-0.928 0.208-1.876 0.375-2.835 0.511h-0.031c-0.396 0.063-0.709 0.396-0.719 0.813-0.010 0.594 0.083 1.126 0.208 1.626s0.292 0.969 0.469 1.438c0.146 0.375 0.292 0.698 0.542 1.105 0.042 0.073-0.021 0.146-0.104 0.125-1.167-0.365-2.304-0.907-3.461-1.845-1.23-0.99-1.762-1.584-2.814-2.835-0.146-0.177-0.365-0.302-0.615-0.323h-0.031c-1.908-0.188-3.805-0.479-5.629-0.98-1.814-0.5-3.565-1.199-5.055-2.22-0.74-0.511-1.407-1.105-1.97-1.772-0.563-0.678-1.022-1.418-1.355-2.231s-0.552-1.678-0.657-2.564-0.125-1.824-0.031-2.689c0.104-0.876 0.313-1.73 0.646-2.543 0.334-0.803 0.771-1.564 1.324-2.251 1.115-1.386 2.595-2.481 4.232-3.273 0.823-0.396 1.678-0.74 2.564-1.022s1.793-0.511 2.71-0.678c1.845-0.354 3.742-0.511 5.639-0.532 1.907-0.010 3.815 0.073 5.67 0.344 1.866 0.271 3.69 0.709 5.378 1.418 1.689 0.698 3.242 1.668 4.44 2.95 0.594 0.636 1.105 1.355 1.491 2.126s0.667 1.605 0.834 2.481c0.167 0.855 0.219 1.751 0.188 2.658z"></path>
                                                    </svg>
                                                    <span v-if="commentCount[data.settlement.id as number] > 0" class="text-xs">{{ commentCount[data.settlement.id as number] }}</span>
                                                    <span class="side-notification" style="position: unset;" v-if="financeTotalBadge(data.settlement.id as number)">{{ financeTotalBadge(data.settlement.id as number) }}</span>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                    <tr class="bg-[var(--bg3)]">
                                        <td class="p-name" rowspan="3">集計</td>
                                        <td class="sub-name">年度予算</td>
                                        <td>
                                            <div class="flex items-center gap-[5px]">
                                                <div class="inner-col"><span class="mobile">売上</span>{{
                                                    amountOfMoneyParser(summarizeData.yearly_plan.sales)
                                                    }}</div>
                                                <DeltaNumbers type="sales" :planned="summarizeData.yearly_plan.sales"
                                                    :actual="summarizeData.yearly_plan.sales" />
                                            </div>
                                        </td>
                                        <td>
                                            <div class="flex items-center gap-[5px]">
                                                <div class="inner-col"><span class="mobile">販管費</span>{{
                                                    amountOfMoneyParser(summarizeData.yearly_plan.expense) }}</div>
                                                <DeltaNumbers type="expense"
                                                    :planned="summarizeData.yearly_plan.expense"
                                                    :actual="summarizeData.yearly_plan.expense" />
                                            </div>
                                        </td>
                                        <td>
                                            <div class="flex items-center gap-[5px]">
                                                <div class="inner-col"><span class="mobile">利益</span>{{
                                                    amountOfMoneyParser(summarizeData.yearly_plan.sales
                                                    - summarizeData.yearly_plan.expense) }}</div>
                                                <DeltaNumbers type="profit"
                                                    :planned="summarizeData.yearly_plan.sales - summarizeData.yearly_plan.expense"
                                                    :actual="summarizeData.yearly_plan.sales - summarizeData.yearly_plan.expense" />
                                            </div>
                                        </td>
                                        <td>
                                            <div class="flex items-center gap-[5px]">
                                                <div class="inner-col"><span class="mobile">利益率</span>{{
                                                    percentizer(summarizeData.yearly_plan).display }}
                                                </div>
                                                <DeltaNumbers type="profit_rate"
                                                    :planned="percentizer(summarizeData.yearly_plan).value"
                                                    :actual="percentizer(summarizeData.yearly_plan).value" />
                                            </div>
                                        </td>
                                        <td v-if="hasPrivilage"></td>
                                    </tr>
                                    <tr class="bg-[var(--bg3)]">
                                        <td class="sub-name">損益計画</td>
                                        <td>
                                            <div class="flex items-center gap-[5px]">
                                                <div class="inner-col"><span class="mobile">売上</span>{{
                                                    amountOfMoneyParser(summarizeData.profit.sales) }}
                                                </div>
                                                <DeltaNumbers type="sales" :planned="summarizeData.yearly_plan.sales"
                                                    :actual="summarizeData.profit.sales" />
                                            </div>
                                        </td>
                                        <td>
                                            <div class="flex items-center gap-[5px]">
                                                <div class="inner-col"><span class="mobile">販管費</span>{{
                                                    amountOfMoneyParser(summarizeData.profit.expense)
                                                    }}</div>
                                                <DeltaNumbers type="expense"
                                                    :planned="summarizeData.yearly_plan.expense"
                                                    :actual="summarizeData.profit.expense" />
                                            </div>
                                        </td>
                                        <td>
                                            <div class="flex items gap-[5px]">
                                                <div class="inner-col"><span class="mobile">利益</span>{{
                                                    amountOfMoneyParser(summarizeData.profit.profit) }}</div>
                                                <DeltaNumbers type="profit"
                                                    :planned="summarizeData.yearly_plan.sales - summarizeData.yearly_plan.expense"
                                                    :actual="summarizeData.profit.profit" />
                                            </div>
                                        </td>
                                        <td>
                                            <div class="flex items gap-[5px]">
                                                <div class="inner-col"><span class="mobile">利益率</span>{{
                                                    percentizer(summarizeData.profit).display }}</div>
                                                <DeltaNumbers type="profit_rate"
                                                    :planned="percentizer(summarizeData.yearly_plan).value"
                                                    :actual="percentizer(summarizeData.profit).value" />
                                            </div>
                                        </td>
                                        <td v-if="hasPrivilage"></td>
                                    </tr>
                                    <tr class="bg-[var(--bg3)]">
                                        <td class="sub-name">実績</td>
                                        <td>
                                            <div class="flex items gap-[5px]">
                                                <div class="inner-col"><span class="mobile">売上</span>{{
                                                    amountOfMoneyParser(summarizeData.settlement.sales)
                                                    }}</div>
                                                <DeltaNumbers type="sales" :planned="summarizeData.profit.sales"
                                                    :actual="summarizeData.settlement.sales" />
                                            </div>
                                        </td>
                                        <td>
                                            <div class="flex items gap-[5px]">
                                                <div class="inner-col"><span class="mobile">販管費</span>{{
                                                    amountOfMoneyParser(summarizeData.settlement.expense) }}</div>
                                                <DeltaNumbers type="expense"
                                                    :planned="summarizeData.profit.expense"
                                                    :actual="summarizeData.settlement.expense" />
                                            </div>
                                        </td>
                                        <td>
                                            <div class="flex items gap-[5px]">
                                                <div class="inner-col"><span class="mobile">利益</span>{{
                                                    amountOfMoneyParser(summarizeData.settlement.sales -
                                                    summarizeData.settlement.expense) }}</div>
                                                <DeltaNumbers type="profit"
                                                    :planned="summarizeData.profit.profit"
                                                    :actual="summarizeData.settlement.sales - summarizeData.settlement.expense" />
                                            </div>
                                        </td>
                                        <td>
                                            <div class="flex items gap-[5px]">
                                                <div class="inner-col"><span class="mobile">利益率</span>{{
                                                    percentizer(summarizeData.settlement).display }}
                                                </div>
                                                <DeltaNumbers type="profit_rate"
                                                    :planned="percentizer(summarizeData.profit).value"
                                                    :actual="percentizer(summarizeData.settlement).value" />
                                            </div>
                                        </td>
                                        <td v-if="hasPrivilage"></td>
                                    </tr>

                                </tbody>



                            </table>

                        </div>
                        <div v-if="tab == 'pie' || tab == 'bar'">
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
                :period="`${interval.endYear}-${String(interval.endMonth).padStart(2, '0')}`" 
                @close="selectedId = null"
                @getCommentCounts="get_finance_comment_counts" 
            />
        </Transition>
    </div>
</template>
<script setup lang="ts">
import { Project, YearlyFinancialData } from '@/interface/projectInterface';
import CloseIcon from '../Form/CloseIcon.vue';
import { computed, onMounted, reactive, ref, useTemplateRef, watch } from 'vue';
import 'styles/customForm.css'
import { MonthNumbers, DateTime } from 'luxon';
import { amountOfMoneyParser } from '@/utils/tools';
import { routerKey, useRoute, useRouter } from 'vue-router';
import LoaderButton from '../Global/LoaderButton.vue';
import { useResponsive } from '@/store/responsive';
import { useMenuStore } from '@/store/menu';
import { User } from '@/interface/globalInterface';
import DeltaNumbers from '@/components/Project/ProjectTabs/Finance/DeltaNumbers.vue'
import UserPanel from '../Global/UserPanel.vue';
import BarChart from './ProjectTabs/Finance/BarChart.vue';
import PieChart from './ProjectTabs/Finance/PieChart.vue';
import CommandButton from '../Global/CommandButton.vue';
import { useTheme } from '@/store/theme';
import Back from '../Icons/Back.vue';
import { useApi } from '@/composables/api';
import BadgeLoader from './ProjectTabs/Finance/BadgeLoader.vue';
import CommentWindow from './ProjectTabs/Finance/CommentWindow.vue';
import { useBadgeStore } from '@/store/badge';
import { useAuthUserStore } from '@/store/auth';

const router = useRouter()
const props = defineProps<{
    projects: Project[]
    ownProjectIds: number[]
}>()
const emit = defineEmits<{
    close: []
}>()
const months = Array.from({ length: 12 }, (_, index) => ({
    label: `${index + 1}月`,
    value: index + 1
}));
const years = Array.from({ length: DateTime.now().year - 2024 + 2 }, (_, index) => {
    const year = 2024 + index;
    return {
        label: `${year}年`, // label as string (optional)
        value: year
    };
});

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
const selectedId = ref<number | null>(null)
interface UnitData {
    expense: number,
    sales: number,
    profit: number,
    id?: number
}

const selectedProjects = ref<number[]>([])
const financeData = ref<YearlyFinancialData>({})
const variance = ref<{
    [projectName:string]: {
        sales: number | null,
        expenses: number | null,
        profit: number| null,
    }
}>()
const theme = useTheme()
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

const interval = reactive({
    startMonth: <MonthNumbers>DateTime.now().month,
    startYear: <number>DateTime.now().year,
    endMonth: <MonthNumbers>DateTime.now().month,
    endYear: <number>DateTime.now().year
})

const selectedManagers = ref<number[]>([])
const loader = ref(true)
const badgeLoader = ref(0)
const tab = ref('table')
const route = useRoute()
const responsive = useResponsive()
const menu = useMenuStore()
const leftTab = ref<'project' | 'manager'>('project')
const startYearRef = useTemplateRef('startYearRef')
const endYearRef = useTemplateRef('endYearRef')
const startMonthRef = useTemplateRef('startMonthRef')
const endMonthRef = useTemplateRef('endMonthRef')
const auth = useAuthUserStore()
const commentCount = ref<{
    [id: number]: number
}>([])
const api = useApi()

onMounted(() => {
    selectedProjects.value = route.params.projectId ? [Number(route.params.projectId)] : props.ownProjectIds && props.ownProjectIds.length ? props.ownProjectIds : []
})
const possibleTypes = [{ value: 'sales', label: '売上' }, { value: 'expense', label: '販管費' }, { value: 'profit', label: '利益' }]
const possibleScenarios = [{ value: 'yearly_plan', label: '年度予算' }, { value: 'profit', label: '損益計画' }, { value: 'settlement', label: '実績' }]
const activeType = ref('sales')
const activeScenario = ref('yearly_plan')
const hasPrivilage = computed(() => {
    return auth.user?.position_id && auth.user?.position_id <= 6 || auth.activeUser.id === 610
})
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
const selectAllProjects = (event: Event) => {
    const target = event.target as HTMLInputElement
    if (target.checked) {
        selectedProjects.value = props.projects.map(project => project.id)
    } else {
        selectedProjects.value = []
    }
}

const getTotalFinance = async () => {

    const data = await api.get('/get_total_finance',  { projects: selectedProjects.value, interval: interval }, {
        loadingRef: loader
    })
    financeData.value = data.sumData
    summarizeData.value = data.summarizeData
    menu.close()
}
const getTotalFinanceBadge = async() => {
    const data = await api.get('/get_total_finance_badge', 
        { interval: interval}, 
    )
    variance.value = data
    badgeLoader.value++
}
const THRESHOLD = 10;

type Key = 'sales' | 'expense' | 'profit';
const showAnyArrow = (name: string): boolean => {
  return (['sales','expense','profit'] as Key[]).some(k => {
    const v = variance.value?.[name]?.[k];
    return v != null && Math.abs(v) >= THRESHOLD;
  });
}
const percentizer = (data: UnitData) => {
    let ret = {
        value: 0,
        display: ''
    }
    if (data.sales === 0) return ret
    if (data.profit > 0) {
        const percent = (data.profit / data.sales * 100).toFixed(2)
        return {
            value: Number(percent),
            display: `${percent}%`
        }
    }
    const percent = ((data.sales - data.expense) / data.sales * 100).toFixed(2)
    if (Number.isNaN(percent)) return ret
    return {
        value: Number(percent),
        display: `${percent}%`
    }
}
const managersProjects = (manager: User) => {
    return props.projects.filter(project => {
        return project.manager.some(m => m.id === manager.id)
    })
}

watch(() => [selectedProjects.value], () => {
    localStorage.setItem('projectIds', JSON.stringify(selectedProjects.value))
    getTotalFinance()
    getTotalFinanceBadge()
    get_finance_comment_counts()
})

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
const setIntervalData = () => {
    if (startYearRef.value && endYearRef.value && startMonthRef.value && endMonthRef.value) {
        interval.startYear = Number(startYearRef.value.value)
        interval.endYear = Number(endYearRef.value.value)
        interval.startMonth = Number(startMonthRef.value.value) as MonthNumbers
        interval.endMonth = Number(endMonthRef.value.value) as MonthNumbers
        getTotalFinance()
    }
}
const adjustByOne = (value:number) => {
    const instance = DateTime.fromObject({
        year: interval.startYear,
        month: interval.startMonth
    })
    if(!instance.isValid) return
    const newDate = instance.plus({ months: value })
    interval.startYear = newDate.year
    interval.startMonth = newDate.month
    interval.endYear = newDate.year
    interval.endMonth = newDate.month
    getTotalFinance()
    getTotalFinanceBadge()
    get_finance_comment_counts()
}
const badge = useBadgeStore()
const financeTotalBadge = (projectId: number) => {
    return badge.financeCommentBadgeByFilter([{ by: 'project_id', value: projectId}]).length
}
const get_finance_comment_counts = async() => {
    const data = await api.get('/get_comment_count_from_total', { projectIds: selectedProjects.value})
    commentCount.value = data
}
</script>

<style scoped lang="scss">
.project-selector-left {
    display: flex;
    gap: 15px;
    flex-direction: column;
    padding: 15px;
    user-select: none;
    line-height: 1.5;
}

table {
    margin: 0 20px;
    width: calc(100% - 40px);
    border-collapse: separate;
    font-size: 13px;
    line-height: 1.5;

    thead {
        th {
            padding: 10px;
            font-weight: 500;
            text-align: left;
            background-color: var(--bg3);
        }

        position: sticky;
        top: 0;
        background-color: var(--background-color);
        z-index: 1;
    }

    tbody {
        tr {
            td {
                padding: 10px;
                border-bottom: 1px solid var(--calendarBorder);
                font-weight: 400;
                text-align: left;
                border-left: none;

                span {
                    display: block;
                }
            }

            td:last-of-type {
                border-right: solid thin var(--calendarBorder);
            }
        }
    }
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

.p-name {
    max-width: 150px;
    border-right: solid thin var(--calendarBorder);
    border-left: solid thin var(--calendarBorder);
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

    .p-name {
        max-width: 100%;
        text-align: center;
        background: var(--bg3);
    }

    .sub-name {
        text-align: center;
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

            tr:nth-child(3n) {
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

    .inner-col {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 5px;
        width: 100%;
    }
}
</style>