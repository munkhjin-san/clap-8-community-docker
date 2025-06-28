<template>
    <div class="overlay">
        <div class="projectModalInner" style="width: 100%;height: 100%;">
            <div class="projectModalMainHeader !bg-[var(--bg3)]">
                <p class="ml-[30px]">集計</p>
                <div class="flex items-center justify-center w-[60px] h-[60px] min-w-[60px] ml-auto cursor-pointer"
                    @click="emit('close')">
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
                    <div class="sub-tab-container sticky top-0 z-[5] bg-[var(--bg3)]">
                        <button @click="leftTab = 'project'"
                            :class="['sub-tab-item !bg-inherit', { 'selected-sub-tab': leftTab == 'project' }]">プロジェクト別</button>
                        <button @click="leftTab = 'manager'"
                            :class="['sub-tab-item !bg-inherit', { 'selected-sub-tab': leftTab == 'manager' }]">管理者別</button>
                    </div>
                    <div v-if="leftTab == 'project'" class="project-selector-left">
                        <label class="flex items-center gap-[15px] text-[14px] cursor-pointer">
                            <input type="checkbox" class="custom-f-checkbox" name="project-selector"
                                @change="selectAllProjects" :checked="selectedProjects.length === projects.length">
                            <span>全て選択</span>
                        </label>
                        <label v-for="project in projects"
                            class="flex items-center gap-[15px] text-[14px] cursor-pointer">
                            <input type="checkbox" class="custom-f-checkbox" name="project-selector" :value="project.id"
                                v-model="selectedProjects">
                            <span>{{ project.name }}</span>
                        </label>
                    </div>
                    <div v-if="leftTab == 'manager'" class="project-selector-left">
                        <div v-for="manager in managers">
                            <label class="flex items-center gap-[15px] text-[14px] cursor-pointer">
                                <input type="checkbox" class="custom-f-checkbox" name="project-selector-by-manager"
                                    v-model="selectedManagers" :value="manager.id">
                                <UserPanel :user="manager" size="30" with-name disable-instant />
                            </label>
                            <div v-if="selectedManagers.includes(manager.id)" class="project-selector-left">
                                <label class="flex items-center gap-[15px] text-[14px] cursor-pointer">
                                    <input type="checkbox" class="custom-f-checkbox" name="project-selector"
                                        @change="toggleByManager($event, manager)" :checked="isChecked(manager)">
                                    <span>全て選択</span>
                                </label>
                                <label v-for="project in managersProjects(manager)"
                                    class="flex items-center gap-[15px] text-[14px] cursor-pointer">
                                    <input type="checkbox" class="custom-f-checkbox" name="project-selector"
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
                                                        amountOfMoneyParser(data.profit.sales - data.profit.expense) }}
                                                    </div>
                                                    <DeltaNumbers type="profit"
                                                        :planned="data.yearly_plan.sales - data.yearly_plan.expense"
                                                        :actual="data.profit.sales - data.profit.expense" />
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
                                        </tr>
                                        <tr>
                                            <td class="sub-name">実績</td>
                                            <td>
                                                <div class="flex items-center gap-[5px]">
                                                    <div class="inner-col"><span class="mobile">売上</span>{{
                                                        amountOfMoneyParser(data.settlement.sales) }}</div>
                                                    <DeltaNumbers type="sales" :planned="data.yearly_plan.sales"
                                                        :actual="data.settlement.sales" />
                                                </div>
                                            </td>
                                            <td>
                                                <div class="flex items-center gap-[5px]">
                                                    <div class="inner-col"><span class="mobile">販管費</span>{{
                                                        amountOfMoneyParser(data.settlement.expense) }}</div>
                                                    <DeltaNumbers type="expense" :planned="data.yearly_plan.expense"
                                                        :actual="data.settlement.expense" />
                                                </div>
                                            </td>
                                            <td>
                                                <div class="flex items-center gap-[5px]">
                                                    <div class="inner-col"><span class="mobile">利益</span>{{
                                                        amountOfMoneyParser(data.settlement.sales -
                                                        data.settlement.expense) }}</div>
                                                    <DeltaNumbers type="profit"
                                                        :planned="data.yearly_plan.sales - data.yearly_plan.expense"
                                                        :actual="data.settlement.sales - data.settlement.expense" />
                                                </div>
                                            </td>
                                            <td>
                                                <div class="flex items-center gap-[5px]">
                                                    <div class="inner-col"><span class="mobile">利益率</span>{{
                                                        percentizer(data.settlement).display }}</div>
                                                    <DeltaNumbers type="profit_rate"
                                                        :planned="percentizer(data.yearly_plan).value"
                                                        :actual="percentizer(data.settlement).value" />
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
                                                    amountOfMoneyParser(summarizeData.profit.sales -
                                                    summarizeData.profit.expense) }}</div>
                                                <DeltaNumbers type="profit"
                                                    :planned="summarizeData.yearly_plan.sales - summarizeData.yearly_plan.expense"
                                                    :actual="summarizeData.profit.sales - summarizeData.profit.expense" />
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
                                    </tr>
                                    <tr class="bg-[var(--bg3)]">
                                        <td class="sub-name">実績</td>
                                        <td>
                                            <div class="flex items gap-[5px]">
                                                <div class="inner-col"><span class="mobile">売上</span>{{
                                                    amountOfMoneyParser(summarizeData.settlement.sales)
                                                    }}</div>
                                                <DeltaNumbers type="sales" :planned="summarizeData.yearly_plan.sales"
                                                    :actual="summarizeData.settlement.sales" />
                                            </div>
                                        </td>
                                        <td>
                                            <div class="flex items gap-[5px]">
                                                <div class="inner-col"><span class="mobile">販管費</span>{{
                                                    amountOfMoneyParser(summarizeData.settlement.expense) }}</div>
                                                <DeltaNumbers type="expense"
                                                    :planned="summarizeData.yearly_plan.expense"
                                                    :actual="summarizeData.settlement.expense" />
                                            </div>
                                        </td>
                                        <td>
                                            <div class="flex items gap-[5px]">
                                                <div class="inner-col"><span class="mobile">利益</span>{{
                                                    amountOfMoneyParser(summarizeData.settlement.sales -
                                                    summarizeData.settlement.expense) }}</div>
                                                <DeltaNumbers type="profit"
                                                    :planned="summarizeData.yearly_plan.sales - summarizeData.yearly_plan.expense"
                                                    :actual="summarizeData.settlement.sales - summarizeData.settlement.expense" />
                                            </div>
                                        </td>
                                        <td>
                                            <div class="flex items gap-[5px]">
                                                <div class="inner-col"><span class="mobile">利益率</span>{{
                                                    percentizer(summarizeData.settlement).display }}
                                                </div>
                                                <DeltaNumbers type="profit_rate"
                                                    :planned="percentizer(summarizeData.yearly_plan).value"
                                                    :actual="percentizer(summarizeData.settlement).value" />
                                            </div>
                                        </td>
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
    </div>
</template>
<script setup lang="ts">
import { Project, YearlyFinancialData } from '@/interface/projectInterface';
import CloseIcon from '../Form/CloseIcon.vue';
import { computed, onMounted, reactive, ref, useTemplateRef, watch } from 'vue';
import 'styles/customForm.css'
import { MonthNumbers, DateTime } from 'luxon';
import { amountOfMoneyParser } from '@/utils/tools';
import { useRoute } from 'vue-router';
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



const props = defineProps<{
    projects: Project[]
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
interface UnitData {
    expense: number,
    sales: number,
}

const selectedProjects = ref<number[]>([])
const financeData = ref<YearlyFinancialData>({})
const theme = useTheme()
const summarizeData = ref<{
    profit: UnitData,
    yearly_plan: UnitData,
    settlement: UnitData
}>({
    profit: {
        expense: 0,
        sales: 0,
    },
    yearly_plan: {
        expense: 0,
        sales: 0,
    },
    settlement: {
        expense: 0,
        sales: 0,
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
const tab = ref('table')
const route = useRoute()
const responsive = useResponsive()
const menu = useMenuStore()
const leftTab = ref<'project' | 'manager'>('project')
const startYearRef = useTemplateRef('startYearRef')
const endYearRef = useTemplateRef('endYearRef')
const startMonthRef = useTemplateRef('startMonthRef')
const endMonthRef = useTemplateRef('endMonthRef')
const api = useApi()

onMounted(() => {
    selectedProjects.value = route.params.projectId ? [Number(route.params.projectId)] : props.projects && props.projects.length ? [props.projects[0].id] : []
})
const possibleTypes = [{ value: 'sales', label: '売上' }, { value: 'expense', label: '販管費' }, { value: 'profit', label: '利益' }]
const possibleScenarios = [{ value: 'yearly_plan', label: '年度予算' }, { value: 'profit', label: '損益計画' }, { value: 'settlement', label: '実績' }]
const activeType = ref('sales')
const activeScenario = ref('yearly_plan')

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

const percentizer = (data: UnitData) => {
    let ret = {
        value: 0,
        display: ''
    }
    if (data.sales === 0) return ret
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
    getTotalFinance()
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
}
</script>

<style scoped lang="scss">
.project-selector-left {
    display: flex;
    gap: 20px;
    flex-direction: column;
    padding: 20px;
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
    background: var(--bg3);
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