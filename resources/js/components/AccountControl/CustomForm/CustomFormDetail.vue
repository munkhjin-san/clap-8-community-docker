<template>
<div class="absolute z-[7] left-0 top-0 h-full w-full bg-[var(--bg3)]">
    <div class="min-h-[60px] flex gap-[10px] items-center">
        <div @click="router.push({name: 'custom-form-control'})" class="h-[60px] w-[60px] min-w-[60px] flex items-center justify-center cursor-pointer">
            <Back/>
        </div>
        <div class="max-w-[calc(100%-200px)] text-[16px] overflow-hidden overflow-ellipsis">
            {{ form.title }}
        </div>        

        <div class="ml-auto w-fit flex mr-[20px]">
            <LoaderButton @triggered="exportCSV" :loading="downloading" content="CSV出力" />
        </div>
        
    </div>
    <div class="px-[20px] h-[calc(100%-60px)] overflow-auto">
        <div class="py-[20px]">
            <div class="text-[14px]">
                アンケートのURL: <a target="_blank" :href="url">{{url}}</a>
            </div>
            <div class="mt-[30px]">
                <div class="admin-command-bar">            
                    <div class="sub-tab-container">
                        <div @click="tab = 0; getSurveyAnswers()" :class="['sub-tab-item', { 'selected-sub-tab': tab == 0}]">メンバー別</div>
                        <div @click="tab = 1; getSurveyAnswers()" :class="['sub-tab-item', { 'selected-sub-tab': tab == 1}]">質問別</div>
                        <div @click="tab = 2; getSurveyAnswers()" :class="['sub-tab-item', { 'selected-sub-tab': tab == 2}]">要約</div>
                    </div>          
                </div>
                <div v-if="tab == 0">
                    <div class="mt-[20px]">
                        <div class="mt-[10px] flex flex-col gap-[30px]">
                            <div class="flex flex-col gap-[10px] p-[20px] bg-[var(--background-color)]" v-for="answer in answersByUser">
                                <div class="flex items-center">
                                    <UserPanel v-if="answer.user" :user="answer.user" with-name disable-instant/>
                                </div>
                                <div class="flex flex-col gap-[20px]">
                                    <div v-for="block in answer.data" >
                                        <div class="text-[16px]">{{ block.question }}</div>
                                        <div class="ml-[10px] mt-[10px] leading-normal text-[13px]">
                                            <div v-for="ans in block.answers">
                                                <div>{{ ans.value }}</div>
                                                <div class="ml-[10px] text-[gray]">{{ ans.sub_text }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-if="tab == 1 && answersByBlock" >
                    <div class="mt-[10px] flex flex-col gap-[30px]">
                        <div class="flex flex-col gap-[10px] p-[20px] bg-[var(--background-color)]" v-for="block in answersByBlock.blocks" >
                            <div class="text-[16px]">{{ block.question }}</div>
                            <div class="ml-[10px] mt-[10px] ">
                                                               
                                <div v-if="simpleTypes.includes(block.type)">
                                    <div class="flex flex-col gap-[10px]">
                                        <div v-for="answer in block.answers" class="flex items-center gap-[10px]">
                                            <div><UserPanel v-if="answer.user" size="25" :user="answer.user" disable-instant with-name/></div>
                                            <div class="ml-[10px] text-[13px]">{{ answer.text_answer }}</div>
                                        </div>
                                    </div>
                                </div>
                                <div v-else>
                                    <div class="flex flex-col gap-[20px]">
                                        <div v-for="element in block.elements" class="flex flex-col gap-[10px]">
                                            <div>{{ element.value }}</div>
                                            <div class="m-[10px] ml-[10px] flex flex-col gap-[10px]">
                                                <div v-for="el_answer in element.answers" class="flex items-center gap-[10px] text-[13px]">
                                                   <UserPanel v-if="el_answer.user" size="25" :user="el_answer.user" disable-instant with-name>
                                                        <template v-if="el_answer.sub_text" #details>
                                                            <div class="text-[13px] mt-[5px] ml-[10px] color-[gray]">{{ el_answer.sub_text }}</div>
                                                        </template>
                                                   </UserPanel>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-if="tab == 2" class="flex flex-col gap-[20px] my-[20px]">
                    <div v-for="block in chartData" class="p-[20px] bg-[var(--background-color)]">
                        <div class="flex">
                            <div class="w-[50%] max-w-[300px]">
                                <div class="mb-[30px] text-[16px]">{{ block.question }}</div>
                                <Pie :options="options" v-if="block.chartData" :data="block.chartData" />
                            </div>
                            <div class="flex flex-col gap-[20px] ml-[20px] text-[13px]">
                                <div v-for="element in block.elements" class="flex gap-[10px] flex-col">
                                    <div>{{ element.value }}</div>
                                    <div class="flex">
                                        <div v-for="el_answer in element.answers" class="flex items-center gap-[10px]">
                                            <UserPanel v-if="el_answer.user" size="15" :user="el_answer.user" disable-instant/>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>                       
                        
                    </div>
                    <!--  -->
                </div>
            </div>
        </div>
        
    </div>
</div>
</template>
<script setup lang="ts">
import UserPanel from '@/components/Global/UserPanel.vue';
import { CustomForm, CustomFormBlock, SurveyAnswer } from '@/interface/customFormInterface';
import { User } from '@/interface/globalInterface';
import axios from 'axios';
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { Chart as ChartJS, ArcElement, Tooltip, Legend, Colors  } from 'chart.js'
import { Pie } from 'vue-chartjs'
import { mkConfig, generateCsv, download } from "export-to-csv";
import { DateTime } from 'luxon';
import LoaderButton from '@/components/Global/LoaderButton.vue';
import Back from '@/components/Icons/Back.vue';
ChartJS.register(ArcElement, Tooltip, Legend, Colors )
const simpleTypes = ['multitext', 'singletext', 'date', 'time', 'select']
const props = defineProps<{
    form: CustomForm
}>()
interface SimpleAnswer{
    user: User,
    created_at: string,
    data: {
        question: string,
        type: string,
        answers: { value: string, sub_text: string }[]
    }[]
}
const url = computed(() => {
    return `${window.location.origin}/survey/${props.form.id}`
})
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
    }
}
const downloading = ref(false)
const tab = ref(0)
const router = useRouter()
const answersByUser = ref<SimpleAnswer[]>([])
const answersByBlock = ref<CustomForm | null>(null)
onMounted(() => {
    getSurveyAnswers()
})

const chartData = computed(() => {
    if(!answersByBlock.value) return []
    interface PieData {
        labels: string[],
        datasets: [{ data: number[], backgroundColor?: string[] }]
    }
    interface BlockWithPieData extends CustomFormBlock{
        chartData?: PieData
    }
    const chartable = <BlockWithPieData[]>answersByBlock.value.blocks.filter( block => !simpleTypes.includes(block.type))

    chartable.map( block => {
        const pieData = <PieData>{labels: [], datasets: [{data: []}]} 
        const elements = block.elements
        const numbers = <number[]>[]
        const colors = <string[]>[]
        elements.forEach(element => {
            pieData.labels.push(element.value)
            if(element.answers && element.answers.length){
                const color = `#${Math.floor(Math.random() * 16777215).toString(16).padStart(6, '0')}`
                colors.push(color)
                numbers.push(element.answers.length)
                // pieData.datasets.push({
                //     data: [element.answers.length]
                // })
            }
           
        });
        pieData.datasets[0].data = numbers
        block.chartData = pieData
        
    })
    return chartable
    // return {
    //     labels: ['VueJs', 'EmberJs', 'ReactJs', 'AngularJs'],
    //     datasets: [
    //         {
    //         backgroundColor: ['#41B883', '#E46651', '#00D8FF', '#DD1B16'],
    //         data: [40, 20, 80, 10]
    //         }
    //     ]
    // }
})
const memberAnswer = (blockId: number, answer:SurveyAnswer,) => {
    const answerBlock = answer.block_answers.find(block => block.custom_form_block_id == blockId)
    if (!answerBlock) return null
    return answerBlock
}
const getSurveyAnswers = async() => {
    try {
        const response = await axios.get(`/get_survey_answers/`, {
            params: {
                custom_form_id: props.form.id,
                sort: tab.value == 0 ? 'user' : 'block'
            }
        }).then(res => res.data)

        if(tab.value == 0){
            answersByUser.value = response
        }else if(tab.value == 1 || tab.value == 2){
            answersByBlock.value = response
        }
    } catch (error) {
        
    }
}
const exportCSV = async() => {
    downloading.value = true
    const csvConfig = mkConfig({ useKeysAsHeaders: true, filename: `【${props.form.title}】回答【${DateTime.now().toISODate()}】`});
    const data:SimpleAnswer[] = await axios.get(`/get_survey_answers/`, {params: {
        custom_form_id: props.form.id,
        sort: 'user'
    }}).then(res => res.data)
    const dataSet: any = []
    data.forEach(row => {
        const v = {
            "氏名" : row.user?.name
        }
        row.data.forEach(ans => {
            v[ans.question] = ans.answers.map( a => a.value).join('\n') + '\n' + ans.answers.map( a => a.sub_text).join('\n')
        });
        v['日付'] = DateTime.fromISO(row.created_at).toLocaleString(DateTime.DATETIME_SHORT),
        dataSet.push(v)
    });
    const csv = generateCsv(csvConfig)(dataSet);
    
    download(csvConfig)(csv);
    downloading.value = false
}

</script>