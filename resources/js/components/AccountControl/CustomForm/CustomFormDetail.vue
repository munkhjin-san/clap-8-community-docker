<template>
<div class="h-full w-full" :class="{ 'absolute z-[7] left-0 top-0 bg-[var(--bg3)]': mode !== 'board' }">
    <div class="min-h-[60px] flex gap-[10px] items-center">
        <div v-if="mode !== 'board'" @click="router.push({name: 'custom-form-control'})" class="h-[60px] w-[60px] min-w-[60px] flex items-center justify-center cursor-pointer">
            <Back/>
        </div>
        <div v-if="mode !== 'board'" class="max-w-[calc(100%-200px)] text-[16px] overflow-hidden overflow-ellipsis">
            {{ form.title }}
        </div>        

        <div v-if="!isProjectCreationForm" class="ml-auto w-fit flex mr-[20px]">
            <LoaderButton @triggered="exportCSV" :loading="downloading" content="CSV出力" />
        </div>
        
    </div>
    <div :class="{'px-[20px] h-[calc(100%-60px)] overflow-auto': mode !== 'board'}">
        <div class="py-[20px]">
            <div v-if="form.usage === 'project_creation'" class="p-[20px] bg-[var(--background-color)] text-[14px] leading-normal">
                <div class="mb-[10px]">フォーム種別: 案件作成フォーム</div>
                <div class="mb-[10px]">プロジェクト種別: {{ form.projectType?.label ?? form.project_type?.label ?? '未設定' }}</div>
                <div class="mb-[20px]">紐づく案件数: {{ linkedProjects.length }}件</div>
                <div v-if="form.description" class="rich-wrapper mt-[20px]" v-html="form.description"></div>
                <div class="mt-[30px]">
                    <div class="text-[13px] text-[gray] mb-[10px]">このフォームを使って作成された案件</div>
                    <div v-if="projectLinksLoading" class="text-[13px] text-[gray]">読み込み中...</div>
                    <div v-else-if="!linkedProjects.length" class="text-[13px] text-[gray]">
                        まだこのフォームを利用した案件はありません。
                    </div>
                    <div v-else class="flex flex-col gap-[12px]">
                        <div
                            v-for="project in linkedProjects"
                            :key="project.id"
                            class="border border-solid border-[var(--calendarBorder)] px-[15px] py-[12px]"
                        >
                            <div class="flex flex-wrap items-center gap-[8px]">
                                <div class="text-[15px]">{{ project.name }}</div>
                                <div class="text-[11px] px-[8px] py-[2px] border border-solid border-[var(--calendarBorder)] rounded-full">
                                    {{ PROJECT_STATUS_LABEL[project.status] ?? '不明' }}
                                </div>
                            </div>
                            <div class="mt-[8px] text-[12px] text-[gray]">
                                <span>プロジェクト種別: {{ project.projectType?.label ?? project.project_type?.label ?? '未設定' }}</span>
                                <span class="ml-[12px]">期間: {{ formatProjectPeriod(project.date_start, project.date_end) }}</span>
                                <span v-if="project.specs?.updated_at" class="ml-[12px]">
                                    最終更新: {{ DateTime.fromISO(project.specs.updated_at).toFormat('yyyy/MM/dd HH:mm') }}
                                </span>
                            </div>
                            <div class="mt-[10px] flex items-center gap-[8px]">
                                <span class="text-[12px]">管理者:</span>
                                <div v-if="project.manager?.length" class="flex items-center">
                                    <UserPanel
                                        v-for="manager in project.manager.slice(0, 4)"
                                        :key="manager.id"
                                        :user="manager"
                                        size="20"
                                        disable-instant
                                    />
                                    <span v-if="project.manager.length > 4" class="ml-[5px] text-[12px] text-[gray]">
                                        ...({{ project.manager.length }}人)
                                    </span>
                                </div>
                                <span v-else class="text-[12px] text-[gray]">未設定</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <template v-else>
            <div class="text-[14px]">
                <template v-if="url">
                    公開フォームURL: <a target="_blank" :href="url">{{ url }}</a>
                </template>
                <template v-else>
                    公開フォームURL: <span class="text-[gray]">未公開</span>
                </template>
            </div>
            <div class="mt-[30px]">
                <div class="admin-command-bar">            
                    <div class="sub-tab-container">
                        <div @click="tab = 0; getSurveyAnswers()" :class="['sub-tab-item', { 'selected-sub-tab': tab == 0}]">メンバー別</div>
                        <div @click="tab = 1; getSurveyAnswers()" :class="['sub-tab-item', { 'selected-sub-tab': tab == 1}]">質問別</div>
                        <div @click="tab = 2; getSurveyAnswers()" :class="['sub-tab-item', { 'selected-sub-tab': tab == 2}]">要約</div>
                    </div>          
                </div>

                <div v-if="form.repeat_setting == 1">
                    <div
                        class="flex justify-between items-center px-[20px]">
                        <div class="flex items-center gap-[20px] relative w-full justify-end">
                            <span class="text-[13px]">対象月</span>
                            <button @click="adjustByOne(-1)" class="bg-inherit flex items-center justify-center h-[30px] w-[30px] min-w-[30px]">
                                <Back size="13"/>
                            </button>
                            <button @click.stop="menu.setMenu({parent: 'intervalPicker'})" class="bg-inherit cursor-pointer text-[15px]">
              
                                {{ `${selectedDate.year}年${selectedDate.month}月` }}
                 
                            </button>
                            <button @click="adjustByOne(1)" class="bg-inherit flex items-center justify-center h-[30px] w-[30px] min-w-[30px]">
                                <Back size="13" class="rotate-180"/>
                            </button>
                            <Transition name="slidePop">
                                <div v-if="menu.parent == 'intervalPicker'" id="intervalPicker" class="absolute top-[30px] right-0 shadow-me p-[20px] z-[5] bg-[var(--background-color)]">
                                    <div class="flex items-center gap-[20px]">
                                        <CommandButton :buttons="[
                                                {
                                                    title: '今月', action: () => {
                                                        selectedDate.year = DateTime.now().year
                                                        selectedDate.month = DateTime.now().month
                                                    }
                                                }
                                            ]" 
                                        />
                                    </div>
                                    <div class="flex flex-wrap mt-[20px] items-center w-max">
                                        <div class="flex items-center">
                                            <select ref="startYearRef" 
                                                v-model="selectedDate.year"
                                                class="appearance-none px-[10px] h-[30px] text-[13px] border border-solid border-[var(--primary-color)] cursor-pointer"
                                                :class="[{ 'date-color': theme.dark }]">
                                                <option
                                                    v-for="year in years"
                                                    :key="year.value" :value="year.value">
                                                    {{ year.label }}
                                                </option>
                                            </select>
                                            <select ref="startMonthRef"
                                                v-model="selectedDate.month"
                                                class="appearance-none px-[10px] h-[30px] text-[13px] border border-solid border-[var(--primary-color)] cursor-pointer ml-[-1px]"
                                                :class="[{ 'date-color': theme.dark }]">
                                                <option v-for="month in months" :key="month.value" :value="month.value">
                                                    {{ month.label }}
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="mt-[20px]">
                                        <CommandButton :buttons="[{title: '決定', action: () => {
                                            getSurveyAnswers()
                                            menu.close()
                                        }}]"/>
                                    </div>
                                </div>
                            </Transition>
                        </div>
                    </div>
                </div>
                <div v-if="tab == 0">
                    <div class="mt-[20px]">
                        <div class="mt-[10px] flex flex-col gap-[30px]">
                            <div class="flex flex-col gap-[10px] p-[20px] bg-[var(--background-color)]" :class="{'!bg-[var(--bg3)]' : mode == 'board'}" v-for="(answer, index) in answersByUser">
                                <label class="flex items-center">
                                    <UserPanel v-if="answer.user" :user="answer.user" with-name disable-instant/>
                                    <div v-else class="text-[14px]">{{ answer.respondent_label }}</div>
                                    <p class="jump-link ml-[15px] text-[13px]">表示・非表示</p>
                                    <input type="checkbox" v-model="openedUsers" :value="userRowKey(answer, index)" class="hidden"/>
                                    <p class="ml-auto text-[12px] text-[gray]">{{ DateTime.fromISO(answer.created_at).toLocaleString(DateTime.DATETIME_MED) }}</p>
                                </label>
                                <div v-if="openedUsers.includes(userRowKey(answer, index))" class="flex flex-col gap-[20px]">
                                    <div v-for="block in answer.data" >
                                        <div class="text-sm leading-normal">{{ block.question }}</div>
                                        <div class="ml-[10px] mt-[10px] leading-normal text-[13px]">
                                            <div v-for="ans in block.answers">
                                                <div>{{ ans.value }}</div>
                                                <div class="ml-[10px] text-[gray]">{{ ans.sub_text }}</div>
                                            </div>
                                        </div>
                                        <Files v-if="block.type === 'file'" :items="block.answers" :path="'survey_files'"/>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-if="tab == 1 && answersByBlock" >
                    <div class="mt-[10px] flex flex-col gap-[30px]">
                        <div class="flex flex-col gap-[10px] p-[20px] bg-[var(--background-color)]" :class="{'!bg-[var(--bg3)]' : mode == 'board'}" v-for="(block, index) in answersByBlock.blocks" >
                            <label class="flex items-center">
                                <div class="text-sm leading-normal">{{ block.question }}</div>
                                <p class="jump-link ml-[15px] text-[13px] whitespace-nowrap">表示・非表示</p>
                                <input type="checkbox" v-model="openedQuestions" :value="`by_block_${block.id}`" class="hidden"/>
                            </label>
                            <div class="ml-[10px] mt-[10px]" v-if="openedQuestions.includes(`by_block_${block.id}`)">
                                                               
                                <div v-if="simpleTypes.includes(block.type)">
                                    <div class="flex flex-col gap-[10px]">
                                        <div v-for="answer in block.answers" class="flex items-center gap-[10px]">
                                            <div>
                                                <UserPanel v-if="answer.user" size="25" :user="answer.user" disable-instant with-name>
                                                    <template v-if="answer.text_answer" #details>
                                                        <div class="text-[13px] mt-[5px] ml-[10px] color-[gray]">{{ answer.text_answer }}</div>
                                                    </template>
                                                </UserPanel>
                                            </div>
                                            <!-- <div class="ml-[10px] text-[13px]">{{ answer.text_answer }}</div> -->
                                            <Files v-if="block.type == 'file'" :items="answer.files" :path="'survey_files'"/>
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
                            <div class="w-[50%]">
                                <div class="mb-[30px] text-[16px]">{{ block.question }}</div>
                                <Pie :options="options" v-if="block.chartData" :data="block.chartData" />
                            </div>
                            <div class="flex flex-col gap-[20px] ml-[20px] text-sm">
                                <div v-for="element in block.elements" class="flex gap-[10px] flex-col">
                                    <div>{{ element.value }}</div>
                                    <div class="flex items-center cursor-pointer" v-if="element.answers" @click="setViewUsers({title: element.value, users: element.answers.map( a => a.user) as User[]})">
                                        <div v-for="el_answer in element.answers?.slice(0,3)" class="flex items-center gap-[10px]">
                                            <UserPanel v-if="el_answer.user" size="20" :user="el_answer.user" disable-instant/>
                                        </div>
                                        <p class="ml-[3px]" v-if="element.answers && element.answers?.length > 3">{{ `...(${element.answers?.length}人)` }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>                       
                        
                    </div>
                    <!--  -->
                </div>
            </div>
            </template>
        </div>
        
    </div>
    <Modal @close="setViewUsers({title: '', users: []})" v-if="viewUsers.users.length > 0">
        <template #title>
            <p>{{ viewUsers.title }}</p>
        </template>
        <template #content>
            <div class="flex flex-col">
                <div v-for="user in viewUsers.users" class="flex items-center p-[10px] hover:bg-[var(--bg3)]">
                    <UserPanel :user="user" size="30" with-name disable-instant/>
                </div>                    
            </div>
        </template>
    </Modal>
</div>
</template>
<script setup lang="ts">
import UserPanel from '@/components/Global/UserPanel.vue';
import { CustomForm, CustomFormBlock, SurveyAnswer } from '@/interface/customFormInterface';
import { User } from '@/interface/globalInterface';
import type { ProjectMember, ProjectType } from '@/interface/projectInterface';
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import { Chart as ChartJS, ArcElement, Tooltip, Legend, Colors  } from 'chart.js'
import { Pie } from 'vue-chartjs'
import { mkConfig, generateCsv, download } from "export-to-csv";
import { DateTime, MonthNumbers } from 'luxon';
import LoaderButton from '@/components/Global/LoaderButton.vue';
import Back from '@/components/Icons/Back.vue';
import Modal from '@/components/Global/Modal.vue';
import Files from '@/components/Global/Files.vue';
import CommandButton from '@/components/Global/CommandButton.vue';
import { useMenuStore } from '@/store/menu';
import { useTheme } from '@/store/theme';
import { useApi } from '@/composables/api';
import { PROJECT_STATUS_LABEL } from '@/utils/tools';
ChartJS.register(ArcElement, Tooltip, Legend, Colors )
const simpleTypes = ['multitext', 'singletext', 'date', 'time', 'select', 'file']
const props = defineProps<{
    form: CustomForm
    mode?: 'board' | 'control'
}>()

const menu = useMenuStore()
const theme = useTheme()
const api = useApi()
interface SimpleAnswer{
    user?: User | null,
    respondent_label: string,
    id?: number,
    created_at: string,
    data: {
        question: string,
        type: string,
        answers: { value: string, sub_text: string }[]
    }[]
}
interface LinkedProject {
    id: number
    name: string
    status: string
    date_start: string | null
    date_end: string | null
    manager: ProjectMember[]
    projectType?: ProjectType | null
    project_type?: ProjectType | null
    specs?: {
        updated_at?: string
    } | null
}
const isProjectCreationForm = computed(() => props.form.usage === 'project_creation')
const url = computed(() => {
    if (!props.form.is_public || !props.form.public_token) {
        return ''
    }

    return `${window.location.origin}/public-surveys/${props.form.public_token}`
})
const years = Array.from({ length: DateTime.now().year - 2024 + 2 }, (_, index) => {
    const year = 2024 + index;
    return {
        label: `${year}年`, // label as string (optional)
        value: year
    };
});
const months = Array.from({ length: 12 }, (_, index) => ({
    label: `${index + 1}月`,
    value: index + 1
}));
const selectedDate = reactive({
    year: DateTime.now().year,
    month: DateTime.now().month
});
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
const linkedProjects = ref<LinkedProject[]>([])
const projectLinksLoading = ref(false)
const viewUsers = ref<{title: string, users: User[]}>({title: '', users: []})

const openedQuestions = ref<string[]>([])
const openedUsers = ref<string[]>([])
onMounted(() => {
    loadDetail()
})
watch(() => props.form.id, () => {
    loadDetail()
})
const adjustByOne = (direction: number) => {
    const instance = DateTime.fromObject({
        year: selectedDate.year,
        month: selectedDate.month
    });
    const adjusted = instance.plus({ months: direction });
    selectedDate.year = adjusted.year;
    selectedDate.month = adjusted.month as MonthNumbers;
    getSurveyAnswers()
}
const setViewUsers = (payload: {title: string, users: User[]}) => {
    viewUsers.value = payload
}
const userRowKey = (answer: SimpleAnswer, index: number) => {
    if (answer.user?.id) {
        return `by_user_${answer.user.id}_${index}`
    }

    return `by_user_guest_${answer.id ?? index}`
}
const makePieColors = (n: number) =>
  Array.from({ length: n }, (_, i) => `hsl(${Math.round((360 * i) / n)}, 70%, 55%)`)

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
        elements.forEach(element => {
            pieData.labels.push(element.value.length > 15 ? element.value.slice(0, 15) + '...' : element.value)       
            numbers.push(element.answers?.length || 0)          
        });
        pieData.datasets[0].data = numbers
        pieData.datasets[0].backgroundColor = makePieColors(elements.length)
        block.chartData = pieData
        
    })
    return chartable
})
const formatProjectPeriod = (dateStart?: string | null, dateEnd?: string | null) => {
    if (!dateStart && !dateEnd) return '未設定'
    if (!dateStart) return `〜${DateTime.fromISO(dateEnd!).toFormat('yyyy/MM/dd')}`
    if (!dateEnd) return `${DateTime.fromISO(dateStart).toFormat('yyyy/MM/dd')}〜`
    return `${DateTime.fromISO(dateStart).toFormat('yyyy/MM/dd')}〜${DateTime.fromISO(dateEnd).toFormat('yyyy/MM/dd')}`
}
const getLinkedProjects = async() => {
    projectLinksLoading.value = true
    try {
        const data = await api.get(`/custom_forms/${props.form.id}/projects`)
        linkedProjects.value = Array.isArray(data) ? data as LinkedProject[] : []
    } finally {
        projectLinksLoading.value = false
    }
}
const loadDetail = () => {
    openedQuestions.value = []
    openedUsers.value = []

    if (isProjectCreationForm.value) {
        linkedProjects.value = []
        answersByUser.value = []
        answersByBlock.value = null
        getLinkedProjects()
        return
    }

    getSurveyAnswers()
}
const getSurveyAnswers = async() => {
    if (isProjectCreationForm.value) {
        return
    }
    try {
        const response = await api.get(`/get_survey_answers`, {    
            custom_form_id: props.form.id,
            sort: tab.value == 0 ? 'user' : 'block',
            repeat_setting: props.form.repeat_setting,
            year: selectedDate.year,
            month: selectedDate.month        
        })

        if(tab.value == 0){
            answersByUser.value = response
        }else if(tab.value == 1 || tab.value == 2){
            answersByBlock.value = response
        }
    } catch (error) {
        
    }
}
const exportCSV = async() => {
    if (isProjectCreationForm.value) {
        return
    }
    downloading.value = true
    const csvConfig = mkConfig({ useKeysAsHeaders: true, filename: `【${props.form.title}】回答【${DateTime.now().toISODate()}】`});
    const data:SimpleAnswer[] = await api.get(`/get_survey_answers/`, {
        custom_form_id: props.form.id,
        sort: 'user',
        repeat_setting: props.form.repeat_setting,
        year: selectedDate.year,
        month: selectedDate.month
    })
    const dataSet: any = []
    data.forEach(row => {
        const v = {
            "氏名" : row.user?.name ?? row.respondent_label
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
