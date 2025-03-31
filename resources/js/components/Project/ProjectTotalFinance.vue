<template>
<div class="overlay">
    <div class="projectModalInner" style="width: 100%;height: 100%;">
        <div class="projectModalMainHeader">
            <p class="ml-[30px]">事業部門総計</p>
            <div class="flex items-center justify-center w-[60px] h-[60px] min-w-[60px] ml-auto cursor-pointer" @click="emit('close')">
                <CloseIcon size="13"/>
            </div>
        </div>
        <div class="projectModalContainer relative">      
            <div class="mobile px-[20px]">
                <LoaderButton :loading="false" content="プロジェクト選択" style="margin: 0;" @click.stop="menu.setMenu({parent: 'mb-p-select'})"/>
            </div>          
            <div class="projectModalSideMenu" id="mb-p-select" v-if="menu.parent == 'mb-p-select' || !responsive.mobile">
                <div class="project-selector-left">
                    <label class="flex items-center gap-[15px] text-[14px] cursor-pointer">
                        <input type="checkbox" class="custom-f-checkbox" name="project-selector" @change="selectAllProjects" :checked="selectedProjects.length === projects.length">
                        <span>全て選択</span>
                    </label>
                    <label v-for="project in projects" class="flex items-center gap-[15px] text-[14px] cursor-pointer">
                        <input type="checkbox" class="custom-f-checkbox" name="project-selector" :value="project.id" v-model="selectedProjects">
                        <span>{{project.name}}</span>
                    </label>     
                </div>
            </div>
            
            <div class="projectModalContent relative">
                <div class="cal-month-loader" style="height: 100%; top: 0;" v-if="loader">
                    <div id="loaderMini">
                        <div class="spinner-mini" style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
                    </div>
                </div>
                <div class="sticky top-0 bg-[var(--background-color)] z-[7] h-[60px] flex justify-between items-center px-[20px]">  
                    <div class="sub-tab-container">
                        <button @click="tab = 'table'" :class="['sub-tab-item', { 'selected-sub-tab': tab == 'table'}]">テーブル</button>
                        <button @click="tab = 'graph'" :class="['sub-tab-item', { 'selected-sub-tab': tab == 'graph'}]">グラフ</button>                
                    </div>        
                    <div class="flex items-center" v-if="tab == 'table'">
                        <button @click="shiftMonth(-1)" class="w-[40px] min-w-[40px] h-[40px] flex items-center justify-center">
                            <Back size="13"/>
                        </button>
                        <MonthPickerNew
                            v-model:month="month"
                            v-model:year="year"
                            :right="windowWidth < 425 ? 'auto' : '0'" 
                            @setDate="setDate"
                        />
                        <button @click="shiftMonth(1)" class="w-[40px] min-w-[40px] h-[40px] flex items-center justify-center">
                            <Back size="13" class="rotate-180"/>
                        </button>
                    </div>        
                    <div class="flex items-center" v-if="tab == 'graph'">
                        <button @click="year--" class="w-[40px] min-w-[40px] h-[40px] flex items-center justify-center">
                            <Back size="13"/>
                        </button>
                        <div>{{ year }}年</div>
                        <button @click="year++" class="w-[40px] min-w-[40px] h-[40px] flex items-center justify-center">
                            <Back size="13" class="rotate-180"/>
                        </button>
                    </div>        
                </div>
                <div v-if="tab == 'table'">
                    <table>
                        <thead class="!top-[60px]">
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
                                    <td><div class="inner-col"><span class="mobile">売上</span>{{ amountOfMoneyParser(data[month].yearly_plan.sales) }}</div></td>
                                    <td><div class="inner-col"><span class="mobile">販管費</span>{{ amountOfMoneyParser(data[month].yearly_plan.expense) }}</div></td>
                                    <td><div class="inner-col"><span class="mobile">利益</span>{{ amountOfMoneyParser(data[month].yearly_plan.profit) }}</div></td>
                                    <td><div class="inner-col"><span class="mobile">利益率</span>{{ data[month].yearly_plan.profit_rate }}%</div></td>
                                </tr>
                                <tr>
                                    
                                    <td class="sub-name">損益計画</td>
                                    <td><div class="inner-col"><span class="mobile">売上</span>{{ amountOfMoneyParser(data[month].profit.sales) }}</div></td>
                                    <td><div class="inner-col"><span class="mobile">販管費</span>{{ amountOfMoneyParser(data[month].profit.expense) }}</div></td>
                                    <td><div class="inner-col"><span class="mobile">利益</span>{{ amountOfMoneyParser(data[month].profit.profit) }}</div></td>
                                    <td><div class="inner-col"><span class="mobile">利益率</span>{{ data[month].profit.profit_rate }}%</div></td>
                                </tr>                                
                                <tr>
                                    <td class="sub-name">実績</td>
                                    <td><div class="inner-col"><span class="mobile">売上</span>{{ amountOfMoneyParser(data[month].settlement.sales) }}</div></td>
                                    <td><div class="inner-col"><span class="mobile">販管費</span>{{ amountOfMoneyParser(data[month].settlement.expense) }}</div></td>
                                    <td><div class="inner-col"><span class="mobile">利益</span>{{ amountOfMoneyParser(data[month].settlement.profit) }}</div></td>
                                    <td><div class="inner-col"><span class="mobile">利益率</span>{{ data[month].settlement.profit_rate }}%</div></td>
                                </tr>
                            </template>
                            <tr class="bg-[var(--bg3)]">
                                <td class="p-name" rowspan="3">総計</td>
                                <td class="sub-name">年度予算</td>
                                <td><div class="inner-col"><span class="mobile">売上</span>{{ amountOfMoneyParser(thisMonthTotalCalculation.yearly_plan.sales) }}</div></td>
                                <td><div class="inner-col"><span class="mobile">販管費</span>{{ amountOfMoneyParser(thisMonthTotalCalculation.yearly_plan.expense) }}</div></td>
                                <td><div class="inner-col"><span class="mobile">利益</span>{{ amountOfMoneyParser(thisMonthTotalCalculation.yearly_plan.sales - thisMonthTotalCalculation.yearly_plan.expense) }}</div></td>
                                <td><div class="inner-col"><span class="mobile">利益率</span>{{ ((thisMonthTotalCalculation.yearly_plan.sales - thisMonthTotalCalculation.yearly_plan.expense) / thisMonthTotalCalculation.yearly_plan.sales * 100).toFixed(2) }}%</div></td>
                            </tr>
                            <tr class="bg-[var(--bg3)]">
                                <td class="sub-name">損益計画</td>
                                <td><div class="inner-col"><span class="mobile">売上</span>{{ amountOfMoneyParser(thisMonthTotalCalculation.profit.sales) }}</div></td>
                                <td><div class="inner-col"><span class="mobile">販管費</span>{{ amountOfMoneyParser(thisMonthTotalCalculation.profit.expense) }}</div></td>
                                <td><div class="inner-col"><span class="mobile">利益</span>{{ amountOfMoneyParser(thisMonthTotalCalculation.profit.sales - thisMonthTotalCalculation.profit.expense) }}</div></td>
                                <td><div class="inner-col"><span class="mobile">利益率</span>{{ ((thisMonthTotalCalculation.profit.sales - thisMonthTotalCalculation.profit.expense) / thisMonthTotalCalculation.profit.sales * 100).toFixed(2) }}%</div></td>
                            </tr>
                            <tr class="bg-[var(--bg3)]">
                                <td class="sub-name">実績</td>
                                <td><div class="inner-col"><span class="mobile">売上</span>{{ amountOfMoneyParser(thisMonthTotalCalculation.settlement.sales) }}</div></td>
                                <td><div class="inner-col"><span class="mobile">販管費</span>{{ amountOfMoneyParser(thisMonthTotalCalculation.settlement.expense) }}</div></td>
                                <td><div class="inner-col"><span class="mobile">利益</span>{{ amountOfMoneyParser(thisMonthTotalCalculation.settlement.sales - thisMonthTotalCalculation.settlement.expense) }}</div></td>
                                <td><div class="inner-col"><span class="mobile">利益率</span>{{ ((thisMonthTotalCalculation.settlement.sales - thisMonthTotalCalculation.settlement.expense) / thisMonthTotalCalculation.settlement.sales * 100).toFixed(2) }}%</div></td>
                            </tr>

                        </tbody>
      
                            
                  
                    </table>

                </div>
                <div v-if="tab == 'graph'" class="px-[20px] pb-[20px]">
                    <div class="flex gap-[15px] mt-[10px]">
                        <label v-for="item in possibleScenarios" class="flex items-center gap-[10px] text-[12px]">
                            <input type="radio" class="custom-f-radio" name="scenario" :value="item.value" v-model="activeScenario">
                            <span>{{ item.label }}</span>
                        </label>
                    </div>
                    <div class="flex my-[20px] gap-[15px]">
                        <label v-for="item in possibleTypes" class="flex items-center gap-[10px] text-[12px]">
                            <input type="radio" name="type" class="custom-f-radio" :value="item.value" v-model="activeType">
                            <span>{{ item.label }}</span>
                        </label>
                    </div>
                    
                    <Line :data="chartData" :options="options" />
                </div>
            </div>
        </div>
    </div>
</div>
</template>
<script setup lang="ts">
import { Project } from '@/interface/projectInterface';
import CloseIcon from '../Form/CloseIcon.vue';
import { computed, onMounted, ref, watch } from 'vue';
import 'styles/customForm.css'
import axios from 'axios';
import { MonthNumbers, DateTime } from 'luxon';
import MonthPickerNew from '../Global/MonthPickerNew.vue';
import Back from '../Icons/Back.vue';
import { amountOfMoneyParser } from '@/utils/tools';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend
} from 'chart.js'
import { Line } from 'vue-chartjs'
import { useRoute } from 'vue-router';
import { useTheme } from '@/store/theme';
import LoaderButton from '../Global/LoaderButton.vue';
import { useResponsive } from '@/store/responsive';
import { useMenuStore } from '@/store/menu';

ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend
)
interface FinancialData {
  sales: number;
  expense: number;
  profit: number;
  profit_rate: number;
}

interface MonthlyData {
  yearly_plan: FinancialData;
  profit: FinancialData;
  settlement: FinancialData;
}

interface YearlyFinancialData {
    [project_name: string]: MonthlyData;
}

const props = defineProps<{
    projects: Project[]
}>()
const emit = defineEmits<{
    close:[]
}>()

const options = {
    plugins: {
        legend: {
            labels: {
                color: 'gray',
                font: {
                    size: 14,
                    family: 'Noto Sans JP'
                }
            }
        }
    },
    responsive: true,
    scales: {
        x: {
            grid: {
                color: getComputedStyle(document.documentElement).getPropertyValue('--calendarBorder')
            }
            },
        y: {
            grid: {
                color: getComputedStyle(document.documentElement).getPropertyValue('--calendarBorder')
            }
        }
    }
}

const windowWidth = window.innerWidth;
const selectedProjects = ref<number[]>([])
const financeData = ref<YearlyFinancialData>({})
const month = ref<MonthNumbers>(3)
const year = ref(2025)
const loader = ref(true)
const tab = ref('table')
const route = useRoute()
const responsive = useResponsive()
const menu = useMenuStore()
onMounted(() => {
    selectedProjects.value = route.params.projectId ? [Number(route.params.projectId)] : props.projects && props.projects.length ? [props.projects[0].id] : []
    // getTotalFinance()
})

const possibleTypes = [{value: 'sales', label: '売上'}, {value: 'expense', label: '販管費'}, {value: 'profit', label: '利益'}]
const possibleScenarios = [{value: 'yearly_plan', label: '年度予算'}, {value: 'profit', label: '損益計画'}, {value: 'settlement', label: '実績'}]
const activeType = ref('sales')
const activeScenario = ref('yearly_plan')

const chartData = computed(() => {
    const data = {
        labels: ['1月', '2月', '3月', '4月', '5月', '6月', '7月', '8月', '9月', '10月', '11月', '12月'],
        datasets: <any[]>[]
    }
    for(const project in financeData.value){
        const dataSet = <any[]>[]
        for(const month in financeData.value[project]){
            dataSet.push(financeData.value[project][month][activeScenario.value][activeType.value])
        }
        data.datasets.push({
            label: `${project}`,
            data: dataSet,
            fill: false,
            borderColor: '#' + Math.floor(Math.random()*16777215).toString(16),
            tension: 0.4
        })

        
    }
    return data
})
const selectAllProjects = (event: Event) => {
    const target = event.target as HTMLInputElement
    if(target.checked){
        selectedProjects.value = props.projects.map(project => project.id)
    }else{
        selectedProjects.value = []
    }
}

const getTotalFinance = async() => {
    try{
        loader.value = true
        const res = await axios.get('/get_total_finance', {params: {projects: selectedProjects.value, year: year.value, month: month.value}})
        financeData.value = res.data.plan_res_data
    }catch(e){
        console.log(e)
        financeData.value = {}
    }finally{
        setTimeout(() => {
            loader.value = false
        }, 300);
    }
    

}

const shiftMonth = (value: number) => {
    const instance = DateTime.fromObject({year: year.value, month: month.value})
    if(!instance.isValid) return
    const newDate = instance.plus({months: value})
    month.value = newDate.month
    year.value = newDate.year
}

const setDate = (date: {year:number, month: MonthNumbers}) => {
    year.value = date.year
    month.value = date.month
}

const thisMonthTotalCalculation = computed(() => {
    const yearly_plan = {sales: 0, expense: 0, profit: 0}
    const profit = {sales: 0, expense: 0, profit: 0}
    const settlement = {sales: 0, expense: 0, profit: 0}
    for(const project in financeData.value){
        yearly_plan.sales += financeData.value[project][month.value].yearly_plan.sales
        yearly_plan.expense += financeData.value[project][month.value].yearly_plan.expense
        profit.sales += financeData.value[project][month.value].profit.sales
        profit.expense += financeData.value[project][month.value].profit.expense
        settlement.sales += financeData.value[project][month.value].settlement.sales
        settlement.expense += financeData.value[project][month.value].settlement.expense
    }
    return {yearly_plan, profit, settlement}
})

watch(() => [selectedProjects.value, year.value], () => {
    getTotalFinance()
})
</script>

<style scoped lang="scss">

.project-selector-left{
    display: flex;
    gap: 20px;
    flex-direction: column;
    padding: 20px;
    user-select: none;
    line-height: 1.5;
}
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
            td:last-of-type{
                border-right: solid thin var(--calendarBorder);
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
.p-name{
    max-width: 150px;
    border-right: solid thin var(--calendarBorder);
    border-left: solid thin var(--calendarBorder);
}
.projectModalSideMenu{
    overflow: auto;
    height: 100%;
    background: var(--bg3);
}
@media screen and (max-width: 959px) {
    .p-name{
        max-width: 100%;
        text-align: center;
        background: var(--bg3);
    }
    .sub-name{
        text-align: center;
    }
    .projectModalSideMenu{
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
    table{
        thead{
            display: none;
        }
        tbody{
            tr{
                display: block;
                margin-bottom: -1px;
                td{
                    display: block;
                    border-left: solid thin var(--calendarBorder);
                    border-right: solid thin var(--calendarBorder);
                    border-bottom: none;
                }
                td:last-of-type{
                    border-bottom: solid thin var(--calendarBorder);
                }
                td:first-of-type{
                    border-top: solid thin var(--calendarBorder);
                }
                
            }
            tr:nth-child(3n) {
                margin-bottom: 20px;
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
</style>