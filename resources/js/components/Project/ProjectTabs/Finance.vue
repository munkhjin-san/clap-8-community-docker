<template>
    <div class="h-full relative bg-[var(--background-color)]">
        <div class="flex justify-items-end">
            <div class="work-monthpicker mr-[20px] mt-[15px]">
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
        <div class="h-[calc(100%-60px)] mt-[15px] overflow-y-auto">
            <table>
                <thead>
                    <tr>
                        <th class="h-cell"></th>
                        <th>売上</th>
                        <th>販管費</th>
                        <th>利益</th>
                        <th>利益率</th>
                    </tr>
                </thead>
                <tbody>
                    <template v-if="!loader.yearlyPlan">
                        <tr v-for="data in yearlyPlanData">
                            <td class="h-cell">年間計画</td>
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
                        </tr>
                    </template>
                    <template v-else>
                        <tr>
                            <td class="h-cell">年間計画</td>
                            <CellLoader :order="num" v-for="num in 4"/>
                        </tr>
                    </template>
                    <template v-if="!loader.profit">
                        <tr v-for="data in profitData">                        
                            <td class="h-cell">損益</td>
                            <td>
                                <div class="flex items-center gap-[5px]">
                                    <div class="inner-col"><span class="mobile">売上</span>{{ amountOfMoneyParser(data.sales)}}</div>
                                    <div v-if="data.sales && yearlyPlanData[0].sales && data.sales - yearlyPlanData[0].sales !== 0" class="text-[11px] whitespace-nowrap ml-[5px]" :style="{
                                        color: data.sales - yearlyPlanData[0].sales < 0 ? 'tomato' : 'green'
                                    }">{{ `${data.sales - yearlyPlanData[0].sales > 0 ? '↑' : ' ↓ '}${amountOfMoneyParser(data.sales - (yearlyPlanData[0].sales || 0) )}` }}</div>
                                </div>
                                
                            </td>
                            <td>
                                <div class="flex items-center gap-[5px]">
                                    <div class="inner-col"><span class="mobile">販管費</span>{{ amountOfMoneyParser(data.expense)}}</div>
                                    <div v-if="data.expense && yearlyPlanData[0].expense && data.expense - yearlyPlanData[0].expense !== 0" class="text-[11px] whitespace-nowrap ml-[5px]" :style="{
                                        color: data.expense - yearlyPlanData[0].expense > 0 ? 'tomato' : 'green'
                                    }">{{ `${data.expense - yearlyPlanData[0].expense > 0 ? '↑' : ' ↓ '}${amountOfMoneyParser(data.expense - (yearlyPlanData[0].expense || 0) )}` }}</div>
                                </div>
                            </td>
                            <td>
                                <div class="flex items-center gap-[5px]">
                                    <div class="inner-col"><span class="mobile">利益</span>{{ amountOfMoneyParser(data.profit) }}</div>
                                    <div class="text-[11px] whitespace-nowrap ml-[5px]" v-if="data.profit && yearlyPlanData[0].profit && data.profit - yearlyPlanData[0].profit !== 0" :style="{
                                        color: data.profit - yearlyPlanData[0].profit > 0 ? 'green' : 'tomato'
                                    }">{{ `${data.profit - yearlyPlanData[0].profit > 0 ? '↑' : ' ↓ '}${amountOfMoneyParser(data.profit - (yearlyPlanData[0].profit || 0) )}` }}</div>
                                </div>    
                            </td>
                            <td>
                                <div class="flex items-center gap-[5px]">
                                    <div class="inner-col"><span class="mobile">利益率</span>{{ Number.isNaN(data.profit_rate) ? '-' : `${data.profit_rate}%` }}</div>
                                    <div class="text-[11px] whitespace-nowrap ml-[5px]" v-if="data.profit_rate && yearlyPlanData[0].profit_rate && data.profit_rate - yearlyPlanData[0].profit_rate !== 0" :style="{
                                        color: data.profit_rate - yearlyPlanData[0].profit_rate > 0 ? 'green' : 'tomato'
                                    }">{{ `${data.profit_rate - yearlyPlanData[0].profit_rate > 0 ? '↑' : ' ↓ '}${(data.profit_rate - (yearlyPlanData[0].profit_rate || 0)).toFixed(2)}` }}%</div>
                                </div>
                            </td>
                        </tr>
                    </template>
                    <template v-else>
                        <tr>
                            <td class="h-cell">損益</td>
                            <CellLoader :order="num" v-for="num in 4"/>
                        </tr>
                    </template>
                    <template v-if="!loader.settlement">
                        <tr v-for="data in settlementData">
                            <td class="h-cell">実績</td>
                            <td>
                                <div class="flex items-center gap-[5px]">
                                    <div class="inner-col"><span class="mobile">売上</span>{{ amountOfMoneyParser(data.sales)}}</div>
                                    <div v-if="data.sales && yearlyPlanData[0].sales && data.sales - yearlyPlanData[0].sales !== 0" class="text-[11px] whitespace-nowrap ml-[5px]" :style="{
                                        color: data.sales - yearlyPlanData[0].sales < 0 ? 'tomato' : 'green'
                                    }">{{ `${data.sales - yearlyPlanData[0].sales > 0 ? '↑' : ' ↓ '}${amountOfMoneyParser(data.sales - (yearlyPlanData[0].sales || 0) )}` }}</div>
                                </div>
                                
                            </td>
                            <td>
                                <div class="flex items-center gap-[5px]">
                                    <div class="inner-col"><span class="mobile">販管費</span>{{ amountOfMoneyParser(data.expense)}}</div>
                                    <div v-if="data.expense && yearlyPlanData[0].expense && data.expense - yearlyPlanData[0].expense !== 0" class="text-[11px] whitespace-nowrap ml-[5px]" :style="{
                                        color: data.expense - yearlyPlanData[0].expense > 0 ? 'tomato' : 'green'
                                    }">{{ `${data.expense - yearlyPlanData[0].expense > 0 ? '↑' : ' ↓ '}${amountOfMoneyParser(data.expense - (yearlyPlanData[0].expense || 0) )}` }}</div>
                                </div>
                            </td>
                            <td>
                                <div class="flex items-center gap-[5px]">
                                    <div class="inner-col"><span class="mobile">利益</span>{{ amountOfMoneyParser(data.profit) }}</div>
                                    <div class="text-[11px] whitespace-nowrap ml-[5px]" v-if="data.profit && yearlyPlanData[0].profit && data.profit - yearlyPlanData[0].profit !== 0" :style="{
                                        color: data.profit - yearlyPlanData[0].profit > 0 ? 'green' : 'tomato'
                                    }">{{ `${data.profit - yearlyPlanData[0].profit > 0 ? '↑' : ' ↓ '}${amountOfMoneyParser(data.profit - (yearlyPlanData[0].profit || 0) )}` }}</div>
                                </div>    
                            </td>
                            <td>
                                <div class="flex items-center gap-[5px]">
                                    <div class="inner-col"><span class="mobile">利益率</span>{{ Number.isNaN(data.profit_rate) ? '-' : `${data.profit_rate}%` }}</div>
                                    <div class="text-[11px] whitespace-nowrap ml-[5px]" v-if="data.profit_rate && yearlyPlanData[0].profit_rate && data.profit_rate - yearlyPlanData[0].profit_rate !== 0" :style="{
                                        color: data.profit_rate - yearlyPlanData[0].profit_rate > 0 ? 'green' : 'tomato'
                                    }">{{ `${data.profit_rate - yearlyPlanData[0].profit_rate > 0 ? '↑' : ' ↓ '}${(data.profit_rate - (yearlyPlanData[0].profit_rate || 0)).toFixed(2)}` }}%</div>
                                </div>
                            </td>
                        </tr>
                    </template>
                    <template v-else>
                        <tr>
                            <td class="h-cell">実績</td>
                            <CellLoader :order="num" v-for="num in 4"/>
                        </tr>
                    </template>
                </tbody>
            </table>

        </div>
    </div>
</template>
<script setup lang="ts">
import MonthPickerNew from '@/components/Global/MonthPickerNew.vue';
import Back from '@/components/Icons/Back.vue';
import { DialogMethods } from '@/interface/globalInterface';
import { Project } from '@/interface/projectInterface';
import axios from 'axios';
import { DateTime, MonthNumbers } from 'luxon';
import { inject, onMounted, reactive, ref } from 'vue';
import { amountOfMoneyParser } from '@/utils/tools';
import CellLoader from './Finance/CellLoader.vue';
import { useRoute } from 'vue-router';
const props = defineProps<{
    selectedProject: Project;
    userList: any;
}>();
const windowWidth = window.innerWidth;
const month = ref<MonthNumbers>(3)
const year = ref(2025)
const loader = reactive({
    yearlyPlan: true,
    settlement: true,
    profit: true
})
const route = useRoute()

interface BalanceColumn {
    sales: number;
    expense: number;
    profit: number;
    profit_rate: number;
}

const yearlyPlanData = ref<BalanceColumn[]>([{sales: NaN, expense: NaN, profit: NaN, profit_rate: NaN}])

const settlementData = ref<BalanceColumn[]>([{sales: NaN, expense: NaN, profit: NaN, profit_rate: NaN}])

const profitData = ref<BalanceColumn[]>([{sales: NaN, expense: NaN, profit: NaN, profit_rate: NaN}])

onMounted(() => {
    getYearlyPlan();
    getSettlement();
    getProfit();
})
const { notify, info, confirm } = inject('dialog') as DialogMethods
const getYearlyPlan = async() => {
    try{
        loader.yearlyPlan = true
        yearlyPlanData.value = [{sales: NaN, expense: NaN, profit: NaN, profit_rate: NaN}]
        const response = await axios.get('/get_yearly_plan', {params: {
            project_id: route.params.projectId,
            month: month.value,
            year: year.value
        }}).then(res => res.data);
        yearlyPlanData.value = response && Array.isArray(response) && response.length ? response : [{sales: NaN, expense: NaN, profit: NaN, profit_rate: NaN}]
    }catch(e){
        notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
    }finally{
        loader.yearlyPlan = false
    }
}

const getSettlement = async() => {
    try{
        loader.settlement = true
        settlementData.value = [{sales: NaN, expense: NaN, profit: NaN, profit_rate: NaN}]
        const response = await axios.get('/get_settlement', {params: {
            project_id: route.params.projectId,
            month: month.value,
            year: year.value
        }}).then(res => res.data);
        settlementData.value = response && Array.isArray(response) && response.length ? response : [{sales: NaN, expense: NaN, profit: NaN, profit_rate: NaN}]
    }catch(e){
        notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
    }finally{
        loader.settlement = false
    }
}
const getProfit = async() => {
    try{
        loader.profit = true
        profitData.value = [{sales: NaN, expense: NaN, profit: NaN, profit_rate: NaN}]
        const response = await axios.get('/get_profit', {params: {
            project_id: route.params.projectId,
            month: month.value,
            year: year.value
        }}).then(res => res.data);
        profitData.value = response && Array.isArray(response) && response.length ? response : [{sales: NaN, expense: NaN, profit: NaN, profit_rate: NaN}]
    }catch(e){
        notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
    }finally{
        loader.profit = false
    }
}
const resetData = () => {
    yearlyPlanData.value = []
    settlementData.value = []
    profitData.value = []
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
}

const setDate = (date: {year:number, month: MonthNumbers}) => {
    year.value = date.year
    month.value = date.month
    getYearlyPlan()
    getProfit()
    getSettlement()
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
</style>