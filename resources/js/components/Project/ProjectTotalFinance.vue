<template>
<div class="overlay">
    <div class="projectModalInner" style="width: 100%;height: 100%;">
        <div class="projectModalMainHeader !bg-[var(--bg3)]">
            <p class="ml-[30px]">集計</p>
            <div class="flex items-center justify-center w-[60px] h-[60px] min-w-[60px] ml-auto cursor-pointer" @click="emit('close')">
                <CloseIcon size="13"/>
            </div>
        </div>
        <div class="projectModalContainer relative">      
            <div class="mobile px-[20px]">
                <LoaderButton :loading="false" content="プロジェクト選択" style="margin: 0;" @click.stop="menu.setMenu({parent: 'mb-p-select'})"/>
            </div>          
            <div class="projectModalSideMenu" id="mb-p-select" v-if="menu.parent == 'mb-p-select' || !responsive.mobile">
                <div class="sub-tab-container sticky top-0 z-[5] bg-[var(--bg3)]">
                    <button @click="leftTab = 'project'" :class="['sub-tab-item !bg-inherit', { 'selected-sub-tab': leftTab == 'project'}]">プロジェクト別</button>
                    <button @click="leftTab = 'manager'" :class="['sub-tab-item !bg-inherit', { 'selected-sub-tab': leftTab == 'manager'}]">管理者別</button>                  
                </div> 
                <div v-if="leftTab == 'project'" class="project-selector-left">
                    <label class="flex items-center gap-[15px] text-[14px] cursor-pointer">
                        <input type="checkbox" class="custom-f-checkbox" name="project-selector" @change="selectAllProjects" :checked="selectedProjects.length === projects.length">
                        <span>全て選択</span>
                    </label>
                    <label v-for="project in projects" class="flex items-center gap-[15px] text-[14px] cursor-pointer">
                        <input type="checkbox" class="custom-f-checkbox" name="project-selector" :value="project.id" v-model="selectedProjects">
                        <span>{{project.name}}</span>
                    </label>     
                </div>
                <div v-if="leftTab == 'manager'" class="project-selector-left">
                    <div v-for="manager in managers">
                        <label class="flex items-center gap-[15px] text-[14px] cursor-pointer">
                            <input type="checkbox" class="custom-f-checkbox" name="project-selector-by-manager" v-model="selectedManagers" :value="manager.id">
                            <UserPanel :user="manager" size="30" with-name disable-instant/>
                        </label>
                        <div v-if="selectedManagers.includes(manager.id)" class="project-selector-left">
                            <label class="flex items-center gap-[15px] text-[14px] cursor-pointer">
                                <input type="checkbox" class="custom-f-checkbox" name="project-selector" @change="selectedProjects = managersProjects(manager).map(p => p.id)">
                                <span>全て選択</span>
                            </label>
                            <label v-for="project in managersProjects(manager)" class="flex items-center gap-[15px] text-[14px] cursor-pointer">
                                <input type="checkbox" class="custom-f-checkbox" name="project-selector" :value="project.id" v-model="selectedProjects">
                                <span>{{project.name}}</span>
                            </label>
                            
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="projectModalContent relative">
                <div class="cal-month-loader" style="height: 100%; top: 0;" v-if="loader">
                    <div id="loaderMini">
                        <div class="spinner-mini" style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
                    </div>
                </div>
                <div class="sticky top-0 bg-[var(--background-color)] z-[7] min-h-[60px] flex justify-between items-center px-[20px] flex-wrap gap-[10px]">  
                    <div class="sub-tab-container">
                        <button @click="tab = 'table'" :class="['sub-tab-item', { 'selected-sub-tab': tab == 'table'}]">テーブル</button>              
                        <button @click="tab = 'pie'" :class="['sub-tab-item', { 'selected-sub-tab': tab == 'pie'}]">円グラフ</button>                
                        <button @click="tab = 'bar'" :class="['sub-tab-item', { 'selected-sub-tab': tab == 'bar'}]">棒グラフ</button>                
                    </div>        
                    <div class="flex items-center gap-[20px]">
                        
                        <MonthPickerNew
                            v-model:month="interval.startMonth"
                            v-model:year="interval.startYear"
                            :right="windowWidth < 425 ? 'auto' : '0'" 
                            @setDate="setStartDate"
                        />
                        <div>~</div>
                        <MonthPickerNew
                            v-model:month="interval.endMonth"
                            v-model:year="interval.endYear"
                            :right="windowWidth < 425 ? 'auto' : '0'" 
                            @setDate="setEndDate"
                        />
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
                                    <td><div class="inner-col"><span class="mobile">売上</span>{{ amountOfMoneyParser(data.yearly_plan.sales) }}</div></td>
                                    <td><div class="inner-col"><span class="mobile">販管費</span>{{ amountOfMoneyParser(data.yearly_plan.expense) }}</div></td>
                                    <td><div class="inner-col"><span class="mobile">利益</span>{{ amountOfMoneyParser(data.yearly_plan.sales - data.yearly_plan.expense) }}</div></td>
                                    <td><div class="inner-col"><span class="mobile">利益率</span>{{ percentizer(data.yearly_plan).display }}</div></td>
                                </tr>
                                <tr>
                                    
                                    <td class="sub-name">損益計画</td>
                                    <td>
                                        <div class="flex items-center gap-[5px]">
                                            <div class="inner-col"><span class="mobile">売上</span>{{ amountOfMoneyParser(data.profit.sales) }}</div>
                                            <DeltaNumbers type="sales" :planned="data.yearly_plan.sales" :actual="data.profit.sales" />
                                        </div>
                                    </td>
                                    <td>
                                        <div class="flex items-center gap-[5px]">
                                            <div class="inner-col"><span class="mobile">販管費</span>{{ amountOfMoneyParser(data.profit.expense) }}</div>
                                            <DeltaNumbers type="expense" :planned="data.yearly_plan.expense" :actual="data.profit.expense" />
                                        </div>
                                    </td>
                                    <td>
                                        <div class="flex items-center gap-[5px]">
                                            <div class="inner-col"><span class="mobile">利益</span>{{ amountOfMoneyParser(data.profit.sales - data.profit.expense) }}</div>
                                            <DeltaNumbers type="profit" :planned="data.yearly_plan.sales - data.yearly_plan.expense" :actual="data.profit.sales - data.profit.expense" />
                                        </div>
                                    </td>
                                    <td>
                                        <div class="flex items-center gap-[5px]">
                                            <div class="inner-col"><span class="mobile">利益率</span>{{ percentizer(data.profit).display }}</div>
                                            <DeltaNumbers type="profit_rate" :planned="percentizer(data.yearly_plan).value" :actual="percentizer(data.profit).value" />
                                        </div>
                                    </td>
                                </tr>                                
                                <tr>
                                    <td class="sub-name">実績</td>
                                    <td>
                                        <div class="flex items-center gap-[5px]">
                                            <div class="inner-col"><span class="mobile">売上</span>{{ amountOfMoneyParser(data.settlement.sales) }}</div>
                                            <DeltaNumbers type="sales" :planned="data.yearly_plan.sales" :actual="data.settlement.sales" />
                                        </div>
                                    </td>
                                    <td>
                                        <div class="flex items-center gap-[5px]">
                                            <div class="inner-col"><span class="mobile">販管費</span>{{ amountOfMoneyParser(data.settlement.expense) }}</div>
                                            <DeltaNumbers type="expense" :planned="data.yearly_plan.expense" :actual="data.settlement.expense" />
                                        </div>
                                    </td>
                                    <td>
                                        <div class="flex items-center gap-[5px]">
                                            <div class="inner-col"><span class="mobile">利益</span>{{ amountOfMoneyParser(data.settlement.sales - data.settlement.expense) }}</div>
                                            <DeltaNumbers type="profit" :planned="data.yearly_plan.sales - data.yearly_plan.expense" :actual="data.settlement.sales - data.settlement.expense" />
                                        </div>
                                    </td>
                                    <td>
                                        <div class="flex items-center gap-[5px]">
                                            <div class="inner-col"><span class="mobile">利益率</span>{{ percentizer(data.settlement).display }}</div>
                                            <DeltaNumbers type="profit_rate" :planned="percentizer(data.yearly_plan).value" :actual="percentizer(data.settlement).value" />
                                        </div>
                                    </td>
                                </tr>
                            </template>
                            <tr class="bg-[var(--bg3)]">
                                <td class="p-name" rowspan="3">集計</td>
                                <td class="sub-name">年度予算</td>
                                <td>
                                    <div class="flex items-center gap-[5px]">
                                        <div class="inner-col"><span class="mobile">売上</span>{{ amountOfMoneyParser(summarizeData.yearly_plan.sales) }}</div>
                                        <DeltaNumbers type="sales" :planned="summarizeData.yearly_plan.sales" :actual="summarizeData.yearly_plan.sales" />
                                    </div>
                                </td>
                                <td>
                                    <div class="flex items-center gap-[5px]">
                                        <div class="inner-col"><span class="mobile">販管費</span>{{ amountOfMoneyParser(summarizeData.yearly_plan.expense) }}</div>
                                        <DeltaNumbers type="expense" :planned="summarizeData.yearly_plan.expense" :actual="summarizeData.yearly_plan.expense" />
                                    </div>
                                </td>
                                <td>
                                    <div class="flex items-center gap-[5px]">
                                        <div class="inner-col"><span class="mobile">利益</span>{{ amountOfMoneyParser(summarizeData.yearly_plan.sales - summarizeData.yearly_plan.expense) }}</div>
                                        <DeltaNumbers type="profit" :planned="summarizeData.yearly_plan.sales - summarizeData.yearly_plan.expense" :actual="summarizeData.yearly_plan.sales - summarizeData.yearly_plan.expense" />
                                    </div>
                                </td>
                                <td>
                                    <div class="flex items-center gap-[5px]">
                                        <div class="inner-col"><span class="mobile">利益率</span>{{ percentizer(summarizeData.yearly_plan).display }}</div>
                                        <DeltaNumbers type="profit_rate" :planned="percentizer(summarizeData.yearly_plan).value" :actual="percentizer(summarizeData.yearly_plan).value" />
                                    </div>
                                </td>
                            </tr>
                            <tr class="bg-[var(--bg3)]">
                                <td class="sub-name">損益計画</td>
                                <td>
                                    <div class="flex items-center gap-[5px]">
                                        <div class="inner-col"><span class="mobile">売上</span>{{ amountOfMoneyParser(summarizeData.profit.sales) }}</div>
                                        <DeltaNumbers type="sales" :planned="summarizeData.yearly_plan.sales" :actual="summarizeData.profit.sales" />
                                    </div>
                                </td>
                                <td>
                                    <div class="flex items-center gap-[5px]">
                                        <div class="inner-col"><span class="mobile">販管費</span>{{ amountOfMoneyParser(summarizeData.profit.expense) }}</div>
                                        <DeltaNumbers type="expense" :planned="summarizeData.yearly_plan.expense" :actual="summarizeData.profit.expense" />
                                    </div>
                                </td>
                                <td>
                                    <div class="flex items gap-[5px]">
                                        <div class="inner-col"><span class="mobile">利益</span>{{ amountOfMoneyParser(summarizeData.profit.sales - summarizeData.profit.expense) }}</div>
                                        <DeltaNumbers type="profit" :planned="summarizeData.yearly_plan.sales - summarizeData.yearly_plan.expense" :actual="summarizeData.profit.sales - summarizeData.profit.expense" />
                                    </div>
                                </td>
                                <td>
                                    <div class="flex items gap-[5px]">
                                        <div class="inner-col"><span class="mobile">利益率</span>{{ percentizer(summarizeData.profit).display }}</div>
                                        <DeltaNumbers type="profit_rate" :planned="percentizer(summarizeData.yearly_plan).value" :actual="percentizer(summarizeData.profit).value" />
                                    </div>
                                </td>
                            </tr>
                            <tr class="bg-[var(--bg3)]">
                                <td class="sub-name">実績</td>
                                <td>
                                    <div class="flex items gap-[5px]">
                                        <div class="inner-col"><span class="mobile">売上</span>{{ amountOfMoneyParser(summarizeData.settlement.sales) }}</div>
                                        <DeltaNumbers type="sales" :planned="summarizeData.yearly_plan.sales" :actual="summarizeData.settlement.sales" />
                                    </div>
                                </td>
                                <td>
                                    <div class="flex items gap-[5px]">
                                        <div class="inner-col"><span class="mobile">販管費</span>{{ amountOfMoneyParser(summarizeData.settlement.expense) }}</div>
                                        <DeltaNumbers type="expense" :planned="summarizeData.yearly_plan.expense" :actual="summarizeData.settlement.expense" />
                                    </div>
                                </td>
                                <td>
                                    <div class="flex items gap-[5px]">
                                        <div class="inner-col"><span class="mobile">利益</span>{{ amountOfMoneyParser(summarizeData.settlement.sales - summarizeData.settlement.expense) }}</div>
                                        <DeltaNumbers type="profit" :planned="summarizeData.yearly_plan.sales - summarizeData.yearly_plan.expense" :actual="summarizeData.settlement.sales - summarizeData.settlement.expense" />
                                    </div>
                                </td>
                                <td>
                                    <div class="flex items gap-[5px]">
                                        <div class="inner-col"><span class="mobile">利益率</span>{{ percentizer(summarizeData.settlement).display }}</div>
                                        <DeltaNumbers type="profit_rate" :planned="percentizer(summarizeData.yearly_plan).value" :actual="percentizer(summarizeData.settlement).value" />
                                    </div>
                                </td>
                            </tr>

                        </tbody>
      
                            
                  
                    </table>

                </div>
                <div v-if="tab == 'pie' || tab == 'bar'">
                    <div class="px-[20px]">
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
                    </div>
                    
                
                    <div v-if="tab == 'pie'">
                        
                        <div class="p-pie-chart">
                            <PieChart :projectsData="financeData" :activeScenario="activeScenario" :activeType="activeType"/>
                        </div>
                        
                    </div>
                    <div v-if="tab == 'bar'">
                        <div>
                            <BarChart :projectsData="financeData" :activeView="activeType"/>
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
import { computed, inject, onMounted, reactive, ref, watch } from 'vue';
import 'styles/customForm.css'
import axios from 'axios';
import { MonthNumbers, DateTime } from 'luxon';
import MonthPickerNew from '../Global/MonthPickerNew.vue';
import { amountOfMoneyParser } from '@/utils/tools';
import { useRoute } from 'vue-router';
import LoaderButton from '../Global/LoaderButton.vue';
import { useResponsive } from '@/store/responsive';
import { useMenuStore } from '@/store/menu';
import { DialogMethods, User } from '@/interface/globalInterface';
import DeltaNumbers from '@/components/Project/ProjectTabs/Finance/DeltaNumbers.vue'
import UserPanel from '../Global/UserPanel.vue';
import BarChart from './ProjectTabs/Finance/BarChart.vue';
import PieChart from './ProjectTabs/Finance/PieChart.vue';



const props = defineProps<{
    projects: Project[]
}>()
const emit = defineEmits<{
    close:[]
}>()


interface UnitData {
    expense: number,
    sales: number,
}
const windowWidth = window.innerWidth;
const selectedProjects = ref<number[]>([])
const financeData = ref<YearlyFinancialData>({})
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
onMounted(() => {
    selectedProjects.value = route.params.projectId ? [Number(route.params.projectId)] : props.projects && props.projects.length ? [props.projects[0].id] : []
})
const { notify, info, confirm } = inject('dialog') as DialogMethods
const possibleTypes = [{value: 'sales', label: '売上'}, {value: 'expense', label: '販管費'}, {value: 'profit', label: '利益'}]
const possibleScenarios = [{value: 'yearly_plan', label: '年度予算'}, {value: 'profit', label: '損益計画'}, {value: 'settlement', label: '実績'}]
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
    if(target.checked){
        selectedProjects.value = props.projects.map(project => project.id)
    }else{
        selectedProjects.value = []
    }
}

const getTotalFinance = async() => {
    try{
        loader.value = true
        const res = await axios.get('/get_total_finance', {params: {projects: selectedProjects.value, interval: interval}})
        financeData.value = res.data.sumData
        summarizeData.value = res.data.summarizeData
    }catch(e){
        console.log(e)
        financeData.value = {}
        notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
    }finally{
        setTimeout(() => {
            loader.value = false
        }, 300);
    }
    

}


const setStartDate = (date: {year:number, month: MonthNumbers}) => {
    interval.startYear = date.year
    interval.startMonth = date.month
    getTotalFinance()
}
const setEndDate = (date: {year:number, month: MonthNumbers}) => {
    interval.endYear = date.year
    interval.endMonth = date.month
    getTotalFinance()
}
const percentizer = (data:UnitData) => {
    let ret = {
        value: 0,
        display: ''
    }
    if(data.sales === 0) return ret
    const percent = ((data.sales - data.expense) / data.sales * 100).toFixed(2)
    if(Number.isNaN(percent)) return ret
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
.p-pie-chart{
    width: 100%;
    display: flex;
    justify-content: center;
    margin: auto;
}
@media screen and (max-width: 959px) {
    .p-pie-chart{
        width: 80%;
    }
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