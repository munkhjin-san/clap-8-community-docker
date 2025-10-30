<template>
    <div class="h-full relative bg-[var(--background-color)]">
        <div class="flex justify-between items-center p-4">
            <div class="sub-tab-container">
                <div @click="changeBetweenTabs('check')" :class="['sub-tab-item', { 'selected-sub-tab': activeTab === 'check'}]">収支確認</div>
                <div @click="changeBetweenTabs('case')" :class="['sub-tab-item', { 'selected-sub-tab': activeTab === 'case'}]">案件報告集計</div>
                <!-- <div @click="changeBetweenTabs('yearly')" :class="['sub-tab-item', { 'selected-sub-tab': activeTab === 'yearly'}]">年度予算入力</div> -->
            </div>
        </div>
        
        <!-- <div class="flex justify-between items-center p-4">
            <div class="sub-tab-container">
                <div @click="changeBetweenTabs('check')" :class="['sub-tab-item', { 'selected-sub-tab': activeTab === 'check'}]">収支確認</div>
                <div @click="changeBetweenTabs('yearly')" :class="['sub-tab-item', { 'selected-sub-tab': activeTab === 'yearly'}]">年度予算入力</div>
                <div @click="changeBetweenTabs('monthly')" :class="['sub-tab-item', { 'selected-sub-tab': activeTab === 'monthly'}]">損益計画入力</div>
                <div @click="changeBetweenTabs('actual')" :class="['sub-tab-item', { 'selected-sub-tab': activeTab === 'actual'}]">実績入力</div>
            </div>
            
        </div> -->
        
        <div v-if="activeTab === 'check'" class="h-[calc(100%-60px)] overflow-y-auto">
            <div class="flex items-center gap-4 static flex-wrap md:flex-nowrap px-5 md:justify-normal justify-center">
                <div class="text-sm"><span class="p-[5px] text-xs bg-[var(--bg3)] mr-[10px]">期間</span> {{ selectedProject?.date_start && selectedProject.date_end ? `${DateTime.fromISO(selectedProject.date_start).toLocaleString(DateTime.DATE_SHORT)}  ~  ${DateTime.fromISO(selectedProject.date_end).toLocaleString(DateTime.DATE_SHORT)}` : '未設定' }}</div>
                <div class="work-monthpicker">
                    <div @click="shiftMonth(-1)" class="work-prevmonth">
                        <Back size="13"/>
                    </div>
                    <MonthPickerNew
                        v-model:month="month"
                        v-model:year="year"
                        :right="windowWidth < 425 ? 'auto' : '0'"
                        @setDate="setDate"
                    />
                    <div @click="shiftMonth(1)" class="work-nextmonth">
                        <Back size="13" class="rotate-180"/>
                    </div>
                </div>
            </div>
            <div class="mb-[20px] mt-2 flex justify-end px-[20px] gap-4">
                <!-- <LoaderButton @triggered="viewYearlyPlan" content="年間計画入力"/> -->
                <LoaderButton @triggered="router.push({name: 'total-finance'})" style="margin: 0;" content="集計" :loading="false"/>
            </div>
            <table>
                <thead>
                    <tr>
                        <th class="h-cell"></th>
                        <th>売上</th>
                        <th>販管費</th>
                        <th>利益</th>
                        <th>利益率</th>
                        <th v-if="canViewComment">コメント</th>
                    </tr>
                </thead>
                <tbody>
                    <template v-if="!loaderYP">
                        <tr v-for="data in yearlyPlanData">
                            <td class="h-cell">年度予算</td>
                            <td>
                                <div class="inner-col"><span class="mobile">売上</span>
                                    {{ amountOfMoneyParser(data.sales) }}
                                </div>
                            </td>
                            <td>
                                <div class="inner-col"><span class="mobile">販管費</span>
                                    {{ amountOfMoneyParser(data.expense) }}
                                </div>
                            </td>
                            <td>
                                <div class="inner-col"><span class="mobile">利益</span>
                                    {{ amountOfMoneyParser(data.profit) }}
                                </div>
                            </td>
                            <td>
                                <div class="inner-col"><span class="mobile">利益率</span>
                                    {{ Number.isNaN(data.profit_rate) ? '-' : `${data.profit_rate}%` }}
                                </div>
                            </td>
                            <td v-if="canViewComment">
                                <!-- <svg fill="var(--primary-color)" height="15" version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 33">
                                    <path d="M10.788 8.109c1.574-0.063 3.148-0.083 4.711-0.104l2.356-0.031 2.356-0.010 2.356 0.010c0.782 0 1.574 0.021 2.356 0.031 1.574 0.031 3.148 0.063 4.711 0.136 0.459 0.021 0.823 0.417 0.803 0.876-0.021 0.438-0.375 0.771-0.803 0.792-1.574 0.073-3.148 0.115-4.711 0.136-0.782 0.010-1.574 0.031-2.356 0.031l-2.345 0.021-2.356-0.010-2.356-0.031c-1.574-0.021-3.148-0.052-4.711-0.104-0.479-0.021-0.855-0.417-0.844-0.896 0.010-0.459 0.386-0.823 0.834-0.844zM10.788 13.050c1.574-0.052 3.148-0.083 4.711-0.104l2.356-0.031 2.356-0.010 2.356 0.010c0.782 0 1.574 0.021 2.356 0.031 1.574 0.031 3.148 0.063 4.711 0.136 0.459 0.021 0.823 0.417 0.803 0.876-0.021 0.438-0.375 0.771-0.803 0.792-1.574 0.073-3.148 0.115-4.711 0.136-0.782 0.010-1.574 0.031-2.356 0.031l-2.356 0.010-2.356-0.010-2.356-0.031c-1.574-0.021-3.148-0.052-4.711-0.104-0.479-0.021-0.855-0.417-0.844-0.907 0.021-0.438 0.396-0.803 0.844-0.823zM10.788 17.991c0.74-0.052 1.491-0.083 2.231-0.104l1.115-0.031c0.375-0.010 0.74-0.010 1.115-0.010 0.74 0 1.491 0.010 2.231 0.042 0.75 0.031 1.491 0.063 2.231 0.136 0.459 0.052 0.803 0.459 0.75 0.928-0.042 0.407-0.365 0.709-0.75 0.75-0.75 0.073-1.491 0.115-2.231 0.136-0.75 0.031-1.491 0.042-2.231 0.042-0.375 0-0.74 0-1.115-0.010l-1.115-0.031c-0.74-0.021-1.491-0.052-2.231-0.104-0.479-0.042-0.844-0.459-0.803-0.938 0.031-0.427 0.375-0.771 0.803-0.803z"></path><path d="M39.432 11.393c-0.188-1.063-0.521-2.116-0.99-3.106-0.479-0.99-1.105-1.897-1.835-2.71s-1.564-1.511-2.45-2.106c-0.886-0.594-1.835-1.084-2.794-1.501-1.939-0.813-3.95-1.313-5.973-1.605s-4.055-0.396-6.066-0.365c-2.022 0.042-4.055 0.219-6.066 0.605-2.012 0.396-4.013 1.001-5.889 1.949-0.938 0.479-1.845 1.042-2.679 1.699-0.834 0.667-1.616 1.428-2.272 2.293-0.667 0.855-1.209 1.824-1.605 2.835-0.396 1.021-0.636 2.095-0.74 3.169-0.052 0.532-0.052 1.084-0.042 1.605 0.010 0.532 0.052 1.053 0.125 1.584 0.146 1.053 0.417 2.116 0.844 3.117s1.011 1.939 1.72 2.762c0.709 0.823 1.532 1.532 2.418 2.126 1.772 1.188 3.44 1.824 5.41 2.356 1.803 0.49 3.867 0.782 5.681 0.876 0.146 0.010 0.281 0.073 0.386 0.177 0.459 0.5 0.938 1.074 1.449 1.511 0.667 0.584 1.407 1.126 2.178 1.584 0.761 0.448 1.564 0.803 2.387 1.115 0.865 0.313 2.21 0.605 2.929 0.657 0.698 0.052 0.782-0.479 0.563-0.938-0.229-0.469-0.281-0.552-0.375-0.761s-0.188-0.417-0.271-0.625-0.344-0.844-0.49-1.261c-0.115-0.344-0.292-0.938-0.386-1.407-0.031-0.167 0.083-0.323 0.25-0.344 1.626-0.229 3.242-0.552 4.847-1.032 0.98-0.292 1.939-0.657 2.877-1.094s1.855-0.98 2.7-1.626c0.844-0.646 1.626-1.418 2.272-2.293 0.323-0.438 0.615-0.907 0.865-1.397s0.459-0.99 0.636-1.511c0.344-1.032 0.532-2.106 0.594-3.169 0.021-1.032-0.021-2.106-0.208-3.169zM37.347 14.478c-0.031 0.896-0.167 1.782-0.427 2.616-0.125 0.417-0.292 0.823-0.479 1.22s-0.407 0.771-0.657 1.126c-0.5 0.719-1.115 1.365-1.814 1.928-1.397 1.126-3.106 1.928-4.899 2.522-0.896 0.302-1.814 0.542-2.752 0.75-0.928 0.208-1.876 0.375-2.835 0.511h-0.031c-0.396 0.063-0.709 0.396-0.719 0.813-0.010 0.594 0.083 1.126 0.208 1.626s0.292 0.969 0.469 1.438c0.146 0.375 0.292 0.698 0.542 1.105 0.042 0.073-0.021 0.146-0.104 0.125-1.167-0.365-2.304-0.907-3.461-1.845-1.23-0.99-1.762-1.584-2.814-2.835-0.146-0.177-0.365-0.302-0.615-0.323h-0.031c-1.908-0.188-3.805-0.479-5.629-0.98-1.814-0.5-3.565-1.199-5.055-2.22-0.74-0.511-1.407-1.105-1.97-1.772-0.563-0.678-1.022-1.418-1.355-2.231s-0.552-1.678-0.657-2.564-0.125-1.824-0.031-2.689c0.104-0.876 0.313-1.73 0.646-2.543 0.334-0.803 0.771-1.564 1.324-2.251 1.115-1.386 2.595-2.481 4.232-3.273 0.823-0.396 1.678-0.74 2.564-1.022s1.793-0.511 2.71-0.678c1.845-0.354 3.742-0.511 5.639-0.532 1.907-0.010 3.815 0.073 5.67 0.344 1.866 0.271 3.69 0.709 5.378 1.418 1.689 0.698 3.242 1.668 4.44 2.95 0.594 0.636 1.105 1.355 1.491 2.126s0.667 1.605 0.834 2.481c0.167 0.855 0.219 1.751 0.188 2.658z"></path>
                                </svg> -->
                            </td>
                        </tr>
                    </template>
                    <template v-else>
                        <tr>
                            <td class="h-cell">年度予算</td>
                            <CellLoader :order="num" v-for="num in cellloadNum"/>
                        </tr>
                    </template>
                    <template v-if="!loaderProfit">
                        <tr v-for="data in profitData">                        
                            <td class="h-cell">損益計画</td>
                            <td>
                                <div class="flex items-center gap-[5px]">
                                    <div class="inner-col"><span class="mobile">売上</span>{{ amountOfMoneyParser(data.sales)}}</div>
                                    <DeltaNumbers type="sales" :actual="data.sales" :planned="yearlyPlanData[0].sales"/>
                                </div>
                                
                            </td>
                            <td>
                                <div class="flex items-center gap-[5px]">
                                    <div class="inner-col"><span class="mobile">販管費</span>{{ amountOfMoneyParser(data.expense)}}</div>
                                    <DeltaNumbers type="expense" :actual="data.expense" :planned="yearlyPlanData[0].expense"/>
                                </div>
                            </td>
                            <td>
                                <div class="flex items-center gap-[5px]">
                                    <div class="inner-col"><span class="mobile">利益</span>{{ amountOfMoneyParser(data.profit) }}</div>
                                    <DeltaNumbers type="profit" :actual="data.profit" :planned="yearlyPlanData[0].profit"/>
                                </div>    
                            </td>
                            <td>
                                <div class="flex items-center gap-[5px]">
                                    <div class="inner-col"><span class="mobile">利益率</span>{{ Number.isNaN(data.profit_rate) ? '-' : `${data.profit_rate}%` }}</div>
                                    <DeltaNumbers type="profit_rate" :actual="data.profit_rate" :planned="yearlyPlanData[0].profit_rate"/>
                                </div>
                            </td>
                            <td v-if="canViewComment">
                                <!-- <svg fill="var(--primary-color)" height="15" version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 33">
                                    <path d="M10.788 8.109c1.574-0.063 3.148-0.083 4.711-0.104l2.356-0.031 2.356-0.010 2.356 0.010c0.782 0 1.574 0.021 2.356 0.031 1.574 0.031 3.148 0.063 4.711 0.136 0.459 0.021 0.823 0.417 0.803 0.876-0.021 0.438-0.375 0.771-0.803 0.792-1.574 0.073-3.148 0.115-4.711 0.136-0.782 0.010-1.574 0.031-2.356 0.031l-2.345 0.021-2.356-0.010-2.356-0.031c-1.574-0.021-3.148-0.052-4.711-0.104-0.479-0.021-0.855-0.417-0.844-0.896 0.010-0.459 0.386-0.823 0.834-0.844zM10.788 13.050c1.574-0.052 3.148-0.083 4.711-0.104l2.356-0.031 2.356-0.010 2.356 0.010c0.782 0 1.574 0.021 2.356 0.031 1.574 0.031 3.148 0.063 4.711 0.136 0.459 0.021 0.823 0.417 0.803 0.876-0.021 0.438-0.375 0.771-0.803 0.792-1.574 0.073-3.148 0.115-4.711 0.136-0.782 0.010-1.574 0.031-2.356 0.031l-2.356 0.010-2.356-0.010-2.356-0.031c-1.574-0.021-3.148-0.052-4.711-0.104-0.479-0.021-0.855-0.417-0.844-0.907 0.021-0.438 0.396-0.803 0.844-0.823zM10.788 17.991c0.74-0.052 1.491-0.083 2.231-0.104l1.115-0.031c0.375-0.010 0.74-0.010 1.115-0.010 0.74 0 1.491 0.010 2.231 0.042 0.75 0.031 1.491 0.063 2.231 0.136 0.459 0.052 0.803 0.459 0.75 0.928-0.042 0.407-0.365 0.709-0.75 0.75-0.75 0.073-1.491 0.115-2.231 0.136-0.75 0.031-1.491 0.042-2.231 0.042-0.375 0-0.74 0-1.115-0.010l-1.115-0.031c-0.74-0.021-1.491-0.052-2.231-0.104-0.479-0.042-0.844-0.459-0.803-0.938 0.031-0.427 0.375-0.771 0.803-0.803z"></path><path d="M39.432 11.393c-0.188-1.063-0.521-2.116-0.99-3.106-0.479-0.99-1.105-1.897-1.835-2.71s-1.564-1.511-2.45-2.106c-0.886-0.594-1.835-1.084-2.794-1.501-1.939-0.813-3.95-1.313-5.973-1.605s-4.055-0.396-6.066-0.365c-2.022 0.042-4.055 0.219-6.066 0.605-2.012 0.396-4.013 1.001-5.889 1.949-0.938 0.479-1.845 1.042-2.679 1.699-0.834 0.667-1.616 1.428-2.272 2.293-0.667 0.855-1.209 1.824-1.605 2.835-0.396 1.021-0.636 2.095-0.74 3.169-0.052 0.532-0.052 1.084-0.042 1.605 0.010 0.532 0.052 1.053 0.125 1.584 0.146 1.053 0.417 2.116 0.844 3.117s1.011 1.939 1.72 2.762c0.709 0.823 1.532 1.532 2.418 2.126 1.772 1.188 3.44 1.824 5.41 2.356 1.803 0.49 3.867 0.782 5.681 0.876 0.146 0.010 0.281 0.073 0.386 0.177 0.459 0.5 0.938 1.074 1.449 1.511 0.667 0.584 1.407 1.126 2.178 1.584 0.761 0.448 1.564 0.803 2.387 1.115 0.865 0.313 2.21 0.605 2.929 0.657 0.698 0.052 0.782-0.479 0.563-0.938-0.229-0.469-0.281-0.552-0.375-0.761s-0.188-0.417-0.271-0.625-0.344-0.844-0.49-1.261c-0.115-0.344-0.292-0.938-0.386-1.407-0.031-0.167 0.083-0.323 0.25-0.344 1.626-0.229 3.242-0.552 4.847-1.032 0.98-0.292 1.939-0.657 2.877-1.094s1.855-0.98 2.7-1.626c0.844-0.646 1.626-1.418 2.272-2.293 0.323-0.438 0.615-0.907 0.865-1.397s0.459-0.99 0.636-1.511c0.344-1.032 0.532-2.106 0.594-3.169 0.021-1.032-0.021-2.106-0.208-3.169zM37.347 14.478c-0.031 0.896-0.167 1.782-0.427 2.616-0.125 0.417-0.292 0.823-0.479 1.22s-0.407 0.771-0.657 1.126c-0.5 0.719-1.115 1.365-1.814 1.928-1.397 1.126-3.106 1.928-4.899 2.522-0.896 0.302-1.814 0.542-2.752 0.75-0.928 0.208-1.876 0.375-2.835 0.511h-0.031c-0.396 0.063-0.709 0.396-0.719 0.813-0.010 0.594 0.083 1.126 0.208 1.626s0.292 0.969 0.469 1.438c0.146 0.375 0.292 0.698 0.542 1.105 0.042 0.073-0.021 0.146-0.104 0.125-1.167-0.365-2.304-0.907-3.461-1.845-1.23-0.99-1.762-1.584-2.814-2.835-0.146-0.177-0.365-0.302-0.615-0.323h-0.031c-1.908-0.188-3.805-0.479-5.629-0.98-1.814-0.5-3.565-1.199-5.055-2.22-0.74-0.511-1.407-1.105-1.97-1.772-0.563-0.678-1.022-1.418-1.355-2.231s-0.552-1.678-0.657-2.564-0.125-1.824-0.031-2.689c0.104-0.876 0.313-1.73 0.646-2.543 0.334-0.803 0.771-1.564 1.324-2.251 1.115-1.386 2.595-2.481 4.232-3.273 0.823-0.396 1.678-0.74 2.564-1.022s1.793-0.511 2.71-0.678c1.845-0.354 3.742-0.511 5.639-0.532 1.907-0.010 3.815 0.073 5.67 0.344 1.866 0.271 3.69 0.709 5.378 1.418 1.689 0.698 3.242 1.668 4.44 2.95 0.594 0.636 1.105 1.355 1.491 2.126s0.667 1.605 0.834 2.481c0.167 0.855 0.219 1.751 0.188 2.658z"></path>
                                </svg> -->
                            </td>
                        </tr>
                    </template>
                    <template v-else>
                        <tr>
                            <td class="h-cell">損益計画</td>
                            <CellLoader :order="num" v-for="num in cellloadNum"/>
                        </tr>
                    </template>
                    <template v-if="!loaderSettlement">
                        <tr v-for="(data, index) in settlementData">
                            <td class="h-cell">
                                <div class="flex gap-1 justify-end items-center">
                                    <div v-if="showAnyArrow(index) && canViewComment" class="flex" title="コメントを残してください">
                                        <svg fill="tomato" style="transform: rotate(180deg);" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 30 30">
                                            <path d="M14.978 0C6.735-.055-.129 6.931.002 15.153c-.028 8.166 6.815 14.939 14.976 14.811v-.04c.965.012 1.935-.068 2.889-.243 4.817-.861 9.056-4.274 10.937-8.8C32.986 11.04 25.688-.021 14.978 0m0 27.903C6.08 27.659-.075 18.755 3.433 10.373 7.813.292 22.129.294 26.49 10.385c3.512 8.225-2.605 17.404-11.512 17.518m-1.735-13.968c-.293 2.283-.156 4.58-.125 6.873l.166 2.289c.304 2.068 3.234 2.088 3.548 0 .186-1.523.193-3.051.205-4.58.028-1.53.044-3.058-.164-4.582-.334-2.082-3.284-2.104-3.63 0m-.344-4.565c.115.303.278.565.465.811.473.371 1.062.634 1.685.627 1.248.021 2.335-1.09 2.278-2.331-.015-.643-.308-1.218-.729-1.681-1.906-1.558-4.534.238-3.699 2.574"/>
                                        </svg>
                                    </div>
                                     
                                    <span>実績</span>
                                </div>
                            </td>
                            <td>
                                <div class="flex items-center gap-[5px]">
                                    <div class="inner-col"><span class="mobile">売上</span>{{ amountOfMoneyParser(data.sales)}}</div>
                                    <DeltaNumbers type="sales" :actual="data.sales" :planned="profitData[index].sales"/>
                                </div>
                                
                            </td>
                            <td>
                                <div class="flex items-center gap-[5px]">
                                    <div class="inner-col"><span class="mobile">販管費</span>{{ amountOfMoneyParser(data.expense)}}</div>
                                    <DeltaNumbers type="expense" :actual="data.expense" :planned="profitData[index].expense"/>
                                </div>
                            </td>
                            <td>
                                <div class="flex items-center gap-[5px]">
                                    <div class="inner-col"><span class="mobile">利益</span>{{ amountOfMoneyParser(data.profit) }}</div>
                                    <DeltaNumbers type="profit" :actual="data.profit" :planned="profitData[index].profit"/>
                                </div>    
                            </td>
                            <td>
                                <div class="flex items-center gap-[5px]">
                                    <div class="inner-col"><span class="mobile">利益率</span>{{ Number.isNaN(data.profit_rate) ? '-' : `${data.profit_rate}%` }}</div>
                                    <DeltaNumbers type="profit_rate" :actual="data.profit_rate" :planned="profitData[index].profit_rate"/>
                                </div>
                            </td>
                            <td v-if="canViewComment">
                                <div class="flex items-center gap-2 cursor-pointer" @click="commentView = true">
                                    <svg fill="var(--primary-color)" height="15" version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 33">
                                        <path d="M10.788 8.109c1.574-0.063 3.148-0.083 4.711-0.104l2.356-0.031 2.356-0.010 2.356 0.010c0.782 0 1.574 0.021 2.356 0.031 1.574 0.031 3.148 0.063 4.711 0.136 0.459 0.021 0.823 0.417 0.803 0.876-0.021 0.438-0.375 0.771-0.803 0.792-1.574 0.073-3.148 0.115-4.711 0.136-0.782 0.010-1.574 0.031-2.356 0.031l-2.345 0.021-2.356-0.010-2.356-0.031c-1.574-0.021-3.148-0.052-4.711-0.104-0.479-0.021-0.855-0.417-0.844-0.896 0.010-0.459 0.386-0.823 0.834-0.844zM10.788 13.050c1.574-0.052 3.148-0.083 4.711-0.104l2.356-0.031 2.356-0.010 2.356 0.010c0.782 0 1.574 0.021 2.356 0.031 1.574 0.031 3.148 0.063 4.711 0.136 0.459 0.021 0.823 0.417 0.803 0.876-0.021 0.438-0.375 0.771-0.803 0.792-1.574 0.073-3.148 0.115-4.711 0.136-0.782 0.010-1.574 0.031-2.356 0.031l-2.356 0.010-2.356-0.010-2.356-0.031c-1.574-0.021-3.148-0.052-4.711-0.104-0.479-0.021-0.855-0.417-0.844-0.907 0.021-0.438 0.396-0.803 0.844-0.823zM10.788 17.991c0.74-0.052 1.491-0.083 2.231-0.104l1.115-0.031c0.375-0.010 0.74-0.010 1.115-0.010 0.74 0 1.491 0.010 2.231 0.042 0.75 0.031 1.491 0.063 2.231 0.136 0.459 0.052 0.803 0.459 0.75 0.928-0.042 0.407-0.365 0.709-0.75 0.75-0.75 0.073-1.491 0.115-2.231 0.136-0.75 0.031-1.491 0.042-2.231 0.042-0.375 0-0.74 0-1.115-0.010l-1.115-0.031c-0.74-0.021-1.491-0.052-2.231-0.104-0.479-0.042-0.844-0.459-0.803-0.938 0.031-0.427 0.375-0.771 0.803-0.803z"></path><path d="M39.432 11.393c-0.188-1.063-0.521-2.116-0.99-3.106-0.479-0.99-1.105-1.897-1.835-2.71s-1.564-1.511-2.45-2.106c-0.886-0.594-1.835-1.084-2.794-1.501-1.939-0.813-3.95-1.313-5.973-1.605s-4.055-0.396-6.066-0.365c-2.022 0.042-4.055 0.219-6.066 0.605-2.012 0.396-4.013 1.001-5.889 1.949-0.938 0.479-1.845 1.042-2.679 1.699-0.834 0.667-1.616 1.428-2.272 2.293-0.667 0.855-1.209 1.824-1.605 2.835-0.396 1.021-0.636 2.095-0.74 3.169-0.052 0.532-0.052 1.084-0.042 1.605 0.010 0.532 0.052 1.053 0.125 1.584 0.146 1.053 0.417 2.116 0.844 3.117s1.011 1.939 1.72 2.762c0.709 0.823 1.532 1.532 2.418 2.126 1.772 1.188 3.44 1.824 5.41 2.356 1.803 0.49 3.867 0.782 5.681 0.876 0.146 0.010 0.281 0.073 0.386 0.177 0.459 0.5 0.938 1.074 1.449 1.511 0.667 0.584 1.407 1.126 2.178 1.584 0.761 0.448 1.564 0.803 2.387 1.115 0.865 0.313 2.21 0.605 2.929 0.657 0.698 0.052 0.782-0.479 0.563-0.938-0.229-0.469-0.281-0.552-0.375-0.761s-0.188-0.417-0.271-0.625-0.344-0.844-0.49-1.261c-0.115-0.344-0.292-0.938-0.386-1.407-0.031-0.167 0.083-0.323 0.25-0.344 1.626-0.229 3.242-0.552 4.847-1.032 0.98-0.292 1.939-0.657 2.877-1.094s1.855-0.98 2.7-1.626c0.844-0.646 1.626-1.418 2.272-2.293 0.323-0.438 0.615-0.907 0.865-1.397s0.459-0.99 0.636-1.511c0.344-1.032 0.532-2.106 0.594-3.169 0.021-1.032-0.021-2.106-0.208-3.169zM37.347 14.478c-0.031 0.896-0.167 1.782-0.427 2.616-0.125 0.417-0.292 0.823-0.479 1.22s-0.407 0.771-0.657 1.126c-0.5 0.719-1.115 1.365-1.814 1.928-1.397 1.126-3.106 1.928-4.899 2.522-0.896 0.302-1.814 0.542-2.752 0.75-0.928 0.208-1.876 0.375-2.835 0.511h-0.031c-0.396 0.063-0.709 0.396-0.719 0.813-0.010 0.594 0.083 1.126 0.208 1.626s0.292 0.969 0.469 1.438c0.146 0.375 0.292 0.698 0.542 1.105 0.042 0.073-0.021 0.146-0.104 0.125-1.167-0.365-2.304-0.907-3.461-1.845-1.23-0.99-1.762-1.584-2.814-2.835-0.146-0.177-0.365-0.302-0.615-0.323h-0.031c-1.908-0.188-3.805-0.479-5.629-0.98-1.814-0.5-3.565-1.199-5.055-2.22-0.74-0.511-1.407-1.105-1.97-1.772-0.563-0.678-1.022-1.418-1.355-2.231s-0.552-1.678-0.657-2.564-0.125-1.824-0.031-2.689c0.104-0.876 0.313-1.73 0.646-2.543 0.334-0.803 0.771-1.564 1.324-2.251 1.115-1.386 2.595-2.481 4.232-3.273 0.823-0.396 1.678-0.74 2.564-1.022s1.793-0.511 2.71-0.678c1.845-0.354 3.742-0.511 5.639-0.532 1.907-0.010 3.815 0.073 5.67 0.344 1.866 0.271 3.69 0.709 5.378 1.418 1.689 0.698 3.242 1.668 4.44 2.95 0.594 0.636 1.105 1.355 1.491 2.126s0.667 1.605 0.834 2.481c0.167 0.855 0.219 1.751 0.188 2.658z"></path>
                                    </svg>
                                    <span v-if="commentCount > 0" class="text-xs">{{ commentCount }}</span>
                                    <span class="side-notification" style="position: unset;" v-if="financeTotalBadge">{{ financeTotalBadge }}</span>
                                </div>
                            </td>
                        </tr>
                        <!-- <tr v-for="data in variancePct">
                            <td>

                            </td>
                            <td>
                                {{ fmtPct(data.sales) }}
                            </td>
                            <td>
                                {{ fmtPct(data.expense) }}
                            </td>
                            <td>
                                {{ fmtPct(data.profit) }}
                            </td>
                        </tr> -->
                    </template>
                    <template v-else>
                        <tr>
                            <td class="h-cell">実績</td>
                            <CellLoader :order="num" v-for="num in cellloadNum"/>
                        </tr>
                    </template>
                </tbody>
            </table>
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
        
        
        
        <!-- <YearlyBudget 
            v-else-if="activeTab === 'yearly'"
            :year="year"
            :selectedProjectName="selectedProject.name"
            :selectedProjectId="selectedProject.id"
        /> -->
        <!-- <MonthlyPlan 
            v-else-if="activeTab === 'monthly'"
            :period="normalizedPeriod" 
        />
        <ActualResult 
            v-else-if="activeTab === 'actual'"
            :period="normalizedPeriod" 
        /> -->
        <Transition name="smLoad">
            <CommentWindow 
                v-if="commentView"  
                type="実績"
                :currentProjectId="selectedProject.id"
                :period="`${year}-${String(month).padStart(2, '0')}`" 
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
import MonthPickerNew from '@/components/Global/MonthPickerNew.vue';
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
// import YearlyBudget from './Finance/YearlyBudget.vue';
// import MonthlyPlan from './Finance/MonthlyPlan.vue';
// import ActualResult from './Finance/ActualResult.vue';
import CaseCreate from './Finance/CaseCreate.vue';
import FloatButton from '@/components/Global/FloatButton.vue';
import AddIcon from '@/components/Form/AddIcon.vue';
import CaseConfirm from './Finance/CaseConfirm.vue';
const auth = useAuthUserStore()
const props = defineProps<{
    userList: any;
    mentionableUsers: User[];
    selectedProject: Project;
    hasPrivilage: boolean
}>();
const windowWidth = window.innerWidth;
const caseWindow = ref(false)
const caseRefreshKey = ref(0)
const commentView = ref(false)
const loaderYP = ref(true)
const loaderSettlement = ref(true)
const loaderProfit = ref(true)
const route = useRoute()
const router = useRouter()
const period = String(useRoute().query.period ?? '')
const year  = ref<number>(period ? Number(period.split('-')[0]) : DateTime.now().year)
const month = ref<MonthNumbers>(period ? Number(period.split('-')[1]) as MonthNumbers : DateTime.now().month as MonthNumbers)
const badge = useBadgeStore()
const api = useApi()

const handleCaseSaved = () => {
    caseWindow.value = false
    caseRefreshKey.value += 1
}
const commentCount = ref(0)
const metrics_list = ref<MetricDTO[]>([])
const selectedCaseId = ref<number | null>(null)
const viewCase = (id: number | null) => {
    selectedCaseId.value = id
    caseWindow.value = true
    selectedCaseId.value = null
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
    sales: number;
    expense: number;
    profit: number;
    profit_rate: number;
}

const yearlyPlanData = ref<BalanceColumn[]>([{sales: NaN, expense: NaN, profit: NaN, profit_rate: NaN}])

const settlementData = ref<BalanceColumn[]>([{sales: NaN, expense: NaN, profit: NaN, profit_rate: NaN}])

const profitData = ref<BalanceColumn[]>([{sales: NaN, expense: NaN, profit: NaN, profit_rate: NaN}])

const setTotalFinanceWindow = inject('setTotalFinanceWindow') as (flag: boolean) => void

const normalizedPeriod = computed(() => `${year.value}-${String(month.value).padStart(2, '0')}-01`)

const financeTotalBadge = computed(() => {
    return badge.financeCommentBadgeByFilter([{ by: 'project_id', value: Number(route.params.projectId)}]).length
})
const activeTab = ref<'check' | 'yearly' | 'monthly' | 'actual' | 'case'>('check')

const changeBetweenTabs = (which: 'check' | 'yearly' | 'monthly' | 'actual' | 'case') => {
  activeTab.value = which
}

onMounted(() => {
    getYearlyPlan();
    getSettlement();
    getProfit();
    getCommentCounts()
    getMetrics()
    
})
const cellloadNum = computed(() => {
    return canViewComment.value ? 5 : 4
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

const getVarPct = (i: number, k: Key): number | null => {
  const v = variancePct.value?.[i]?.[k];
  return Number.isFinite(v) ? (v as number) : null;
}

const showAnyArrow = (i: number): boolean => {
  return (['sales','expense','profit'] as Key[]).some(k => {
    const v = getVarPct(i, k);
    return v != null && Math.abs(v) >= THRESHOLD;
  });
}


const pct = (num: number | null, den: number | null) =>
  !Number.isNaN(num) && num != null && den != null && den !== 0 ? (num / den) * 100 : null
const achToVar = (ach: number | null): number | null =>
  ach == null ? null : ach - 100
const variancePct = computed(() =>
  settlementData.value.map((a, i) => {
    const p = profitData.value[i] ?? {}
    const salesAch   = pct(a?.sales,   p?.sales)
    const expenseAch = pct(a?.expense, p?.expense)
    const profitAch  = pct(a?.profit,  p?.profit)

    return {
      // variance% = (actual - plan) / plan * 100 = achievement% - 100
      sales:   achToVar(salesAch),
      expense: achToVar(expenseAch),
      profit:  achToVar(profitAch),

      // margin should usually be shown as percentage points (pp) elsewhere
      profit_rate: null
    }
  })
)
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
const canViewComment = computed(() => {
    return auth.activeUser.id === 610 || auth.user?.position_id && auth.user?.position_id < 6 || props.selectedProject.manager.some(ob => ob.id === auth.id)
})
const getYearlyPlan = async() => {
  
   
    yearlyPlanData.value = [{sales: NaN, expense: NaN, profit: NaN, profit_rate: NaN}]
    const response = await api.get('/get_yearly_plan', {
        project_id: route.params.projectId,
        month: month.value,
        year: year.value
    },{
        loadingRef: loaderYP
    });
    yearlyPlanData.value = response && Array.isArray(response) && response.length ? response : [{sales: NaN, expense: NaN, profit: NaN, profit_rate: NaN}]
   
}

const getSettlement = async() => {

    settlementData.value = [{sales: NaN, expense: NaN, profit: NaN, profit_rate: NaN}]
    const response = await api.get('/get_settlement', {
        project_id: route.params.projectId,
        month: month.value,
        year: year.value
    },{
        loadingRef: loaderSettlement
    });
    settlementData.value = response && Array.isArray(response) && response.length ? response : [{sales: NaN, expense: NaN, profit: NaN, profit_rate: NaN}]

}
const getProfit = async() => {

    profitData.value = [{sales: NaN, expense: NaN, profit: NaN, profit_rate: NaN}]
    const response = await api.get('/get_profit', {
        project_id: route.params.projectId,
        month: month.value,
        year: year.value
    }, {
        loadingRef: loaderProfit
    });
    profitData.value = response && Array.isArray(response) && response.length ? response : [{sales: NaN, expense: NaN, profit: NaN, profit_rate: NaN}]

}
const shiftMonth = (value: number) => {
    const instance = DateTime.fromObject({year: year.value, month: month.value})
    if(!instance.isValid) return
    const newDate = instance.plus({months: value})
    month.value = newDate.month
    year.value = newDate.year
    getYearlyPlan()
    getProfit()
    getSettlement()
    getCommentCounts()
    getMetrics()
}

const setDate = (date: {year:number, month: MonthNumbers}) => {
    year.value = date.year
    month.value = date.month
    getYearlyPlan()
    getProfit()
    getSettlement()
    getCommentCounts()
    getMetrics()
    // console.log('yearly:', yearlyPlanData.value)
    // console.log('settlement:', settlementData.value)
    // console.log('profit:', profitData.value)
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
    margin: 0 20px;
    width: calc(100% - 40px);
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
                border-left: none;
                span{
                    display: block;
                }
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
