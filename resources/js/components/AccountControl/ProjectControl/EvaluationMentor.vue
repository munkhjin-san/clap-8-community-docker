<template>
    <div class="post-container scrollable" style="height: calc(100% - 126px);margin: 0 20px; width: calc(100% - 40px);">
        <div class="flex justify-between">
            <div class="evaluation-date">
                <select name="locales" v-model="selectedDate" class="dropDownSelector cursor-pointer" style="width: fit-content; padding: 5px 10px;">
                    <option :value="date" v-for="date in targetDates">{{ date.name}}</option>
                </select>
            </div>
            <div class="flex gap-5">
                <div class="admin-button" @click="generateEvaluationCsv">CSV出力</div>
                <!-- <div class="admin-button">
                <label class="cursor-pointer">
                    CSV入力
                    <input type="file" class="hidden" accept=".csv"  @change="uploadUploadCsv" />
                </label>
                </div> -->
            </div>
        </div>
        
        <div style="height: calc(100% - 50px);width: 100%;overflow: auto;">
            <table>
                <thead>
                    <tr>
                        <th>メンバー</th>
                        <th>雇用形態</th>
                        <th>メンター</th>
                        <th>職階</th>
                        <th>職務評価基準</th>
                        <th>昇格（職階）</th>
                        <th>現在の号俸</th>
                        <th>異動後の号俸</th>
                        <th>
                            <div class="flex gap-3 items-center">
                                <div class="flex flex-wrap">成果目標</div>
                                <div>
                                    <CommandButton :buttons="[{title: '集計', action: () => announce()}]"/>
                                </div>
                            </div>
                        </th>
                        <th>合計評価点</th>
                        <th>アクション</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="user in searchResults">
                        <td>
                            <span>{{user.name}}</span>
                        </td>
                        <td>{{ user?.positions?.name }}</td>
                        <td class="relative">
                            {{ user?.evaluation?.mentor?.name }}                               
                            <div class="w-[15px] cursor-pointer h-[15px] min-w-[15px] rounded-full absolute flex justify-center items-center right-1 top-1 z-[1]">
                                <Edit size="12" @click="setMentor(user)"/>
                            </div>
                            
                        </td>
                        <td>{{ user?.evaluation?.general_position }}</td>
                        <td>{{ user?.evaluation?.current_level }}</td>
                        <td>
                            <svg v-if="user?.evaluation?.candidate?.some(c => c.next_candidate == '昇格（職階）')" fill="var(--primary-color)" version="1.1" xmlns="http://www.w3.org/2000/svg" height="10" viewBox="0 0 38 32">
                                <path d="M36.486 0.324c-0.666-0.515-1.629-0.396-2.204 0.22l-3.039 3.271-3.060 3.328c-2.031 2.23-4.067 4.452-6.086 6.689-2.025 2.234-8.487 9.367-9.743 10.772-0.132 0.15-0.369 0.129-0.486-0.025-1.060-1.399-2.287-3.028-3.468-4.519-1.161-1.465-2.516-3.22-3.271-4.144-0.755-0.927-1.702-2.093-2.191-2.668-0.528-0.625-1.457-0.791-2.182-0.329-0.765 0.489-0.973 1.521-0.518 2.307 0.367 0.636 2.307 3.801 2.307 3.801 0.801 1.27 3.213 5.039 3.699 5.791 0.487 0.751 1.194 1.782 1.879 2.788 0.684 1.004 1.52 2.313 2.429 3.264s2.487 0.627 3.321-0.358c1.932-2.282 9.588-11.527 11.498-13.857 1.916-2.327 3.815-4.668 5.719-7.004l2.842-3.517 2.823-3.535c0.548-0.687 0.451-1.716-0.272-2.276z"></path>
                            </svg>
                        </td>
                        <!-- <td class="project-cell">{{ user?.evaluation?.grade }}</td> -->
                        <td>{{ user?.evaluation?.current_salary_rank }}</td>
                        <td>{{ user?.evaluation?.after_salary_rank }}</td>
                        <td>
                            <div>
                                <div>設定数：{{ user?.outcome_goals?.length || 0 }}</div>
                                <div class="mt-2" v-if="user?.outcome_goals && user.outcome_goals.length">
                                    <div v-for="goal in user.outcome_goals" :key="goal.id" class="max-w-[220px] relative flex flex-col gap-2 text-[12px] p-2 rounded bg-[var(--bg2)] mb-2">
                                        <router-link target="_blank" class="login-link jump-link" :to="{name: 'goal-more', params: { goalId: goal.id, span: `${goal.year}-${goal.which_half}`, memberId: user.id, projectId: goal.project_id}}" style="flex-grow: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ goal.title || 'タイトルなし' }}</router-link>
                                        <div class="absolute right-1 top-1 text-[11px] bg-[var(--background-color)] px-2 py-1">{{ overallScore(goal) }}点</div>
                                        <div class="text-[11px]">{{ statusDisplay(goal.status) }}</div>
                                    </div>

                                </div>
                            </div>
                        </td>
                        <td>{{ totalOverallScore(user.outcome_goals) }}/{{ user.outcome_goals.length * 100 }}</td>
                        <td>
                            <div style="display: flex; gap: 10px;">
                                <CommandButton 
                                    :buttons="[
                                        { title: '編集', action: () => addEvaluation(user)},
                                    ]"
                                />
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <Transition name="modalFade">
            <EvaluationPreCreation 
                v-if="createWindow"
                :user="selectedUser"
                :selectedDate="selectedDate"
                :mentorList="mentorList"
                :salary_options="salary_options"
                :editData="editData"
                @close="createWindow = false"
            />
        </Transition>
        <Transition name="modalFade">
            <MentorQuickSelector 
                v-if="mentorSelectorData.view"
                :data="mentorSelectorData"
                @close="resetMentorSelector"
            />
        </Transition>
        <Transition name="modalFade">
            <Modal v-if="announceWindow" @close="announceWindow = false">
                <template #title>

                </template>
                <template #content>
                    <div class="mb-5">
                        <p class="mb-2">対象メンバー</p>
                        <div class="flex flex-wrap gap-3">
                            <div v-for="position in positions" :key="position.id">
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" :value="position.id" v-model="selectedUserPositions" />
                                    <span class="text-sm">{{ position.name }}</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="flex gap-2 flex-col">                     
                        <label class="text-[14px]" for="wantedNumber">必須目標数</label>
                        <div class="flex items-center gap-3">
                            <input v-model="wantedNumber" class="p-2 bg-[var(--bg2)]" id="wantedNumber" type="number" />
                            <CommandButton :buttons="[{ title: '集計', action: () => findTargetUsers() }]" />
                        </div>
                        
                    </div>
                    <div v-if="copyTemplate" class="bg-[var(--bg2)] p-3 mt-3">
                        <div class="mt-3">
                            <CommandButton :buttons="[{ title: 'コピー', action: () => copy() }]" />
                        </div>
                        <div ref="copyTemplateRef" class="mt-3 leading-normal text-[14px]">                        
                            <div v-for="item in copyTemplate">
                                <div>
                                    {{ item.name }}→<span v-if="item.must_create">未作成{{ item.must_create }}件 </span>
                                    <span v-if="item.refused">、差戻中{{ item.refused }}件 </span>
                                    <span v-if="item.must_apply">、作成中{{ item.must_apply }}件 </span>
                                    <span v-if="item.must_approve">、申請中{{ item.must_approve }}件 </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                </template>

            </Modal>
        </Transition>
    </div>
</template>
<script lang="ts" setup>
import { detailedDateOptions } from '@/utils/tools';
import { onMounted, ref, computed, reactive, useTemplateRef } from 'vue';
import CommandButton from '@/components/Global/CommandButton.vue';
import EvaluationPreCreation from './EvaluationPreCreation.vue'
import { Evaluation } from '@/interface/projectInterface';
import MentorQuickSelector from './MentorQuickSelector.vue';
import { User } from '@/interface/globalInterface';
import { useApi } from '@/composables/api';
import { generateCsv, download, mkConfig } from 'export-to-csv';
import { useDialog } from '@/composables/dialog';
import Modal from '@/components/Global/Modal.vue';
import Edit from '@/components/Icons/Edit.vue';
const props = defineProps(['userList', 'mentorList', 'keywords'])

const targetDates = detailedDateOptions()
const selectedDate = defineModel<{ name: string, year: number, which_half: string }>()
const createWindow = ref(false)
const selectedUser = ref(null)
const salary_options = ref([])
const editData = ref<Evaluation>()
const api = useApi()
const announceWindow = ref(false)
const wantedNumber = ref(1)
const copyTemplate = ref<any>(null)
const copyTemplateRef = useTemplateRef('copyTemplateRef')
const selectedUserPositions = ref<Array<number>>([11,12])
const mentorSelectorData = reactive<{
        view: boolean,
        user: User | null,
        mentorList: User[],
        selectedMentor: User | null
        date: any
        editId: number | null
    }>
    ({
        view: false,
        user: null,
        mentorList: [],
        selectedMentor: null,
        date: null,
        editId: null
    })


onMounted(() => {
    getSalaryOptions()
})
const resetMentorSelector = (flag: boolean) => {
    mentorSelectorData.view = false
    mentorSelectorData.user = null
    mentorSelectorData.mentorList = []
    mentorSelectorData.selectedMentor = null
    mentorSelectorData.date = null
    mentorSelectorData.editId = null
}
const searchResults = computed(() => {
    if(props.keywords){
        const lowSearch = props.keywords.toLowerCase()
        const deepSearch = (obj) => {
            if (typeof obj === 'string' || typeof obj === 'number') {
                return String(obj).toLowerCase().includes(lowSearch);
            } else if (Array.isArray(obj)) {
                return obj.some(item => deepSearch(item));
            } else if (typeof obj === 'object' && obj !== null) {
                return Object.values(obj).some(val => deepSearch(val));
            }
            return false;
        }
        return props.userList.filter(project => deepSearch(project))
    }
    return props.userList 
})
const addEvaluation = (user) => {
    createWindow.value = true
    selectedUser.value = user
    editData.value = user?.evaluation
}

const getSalaryOptions = async() => {

    salary_options.value = await api.get('/get_salary_options')

}
const kpiCalculation = (steps: any) => {
    if(steps && steps.length){
        const totalProgress = steps.reduce((acc: number, step: any) => {
            return acc + step.progress
        }, 0)
        
        const maxProgress = steps.length * 100
        return Math.round((totalProgress / maxProgress) * 100)
    }
    return 0
}
const overallScore = (goal) => {
    if(!goal.steps || goal.steps.length === 0) return goal.achievement_rate
    const kpi = kpiCalculation(goal.steps)
    const kgi = goal.achievement_rate
    const sum = kpi + kgi
    return Math.round(sum / 2)
}
const totalOverallScore = (goals) => {
    if (!goals.length) return 0

    return goals.reduce((acc, goal) => {
        return acc + overallScore(goal)
    }, 0)
}
const setMentor = (user) => {    
    mentorSelectorData.view = true
    mentorSelectorData.user = user
    mentorSelectorData.mentorList = props.mentorList
    mentorSelectorData.selectedMentor = user?.evaluation?.mentor ?? null
    mentorSelectorData.editId = user?.evaluation?.id ?? null
    mentorSelectorData.date = selectedDate.value

}
const { ping, toast } = useDialog()
const generateEvaluationCsv = () => {
    const csvConfig = mkConfig({ useKeysAsHeaders: true, filename: `人事考課_${selectedDate.value?.name}`});
    const data: any[] = []
    props.userList.forEach(user => {
        data.push({
            "メンバーID": user.id,
            "メンバー": user.name,
            "雇用形態": user?.positions?.name,
            "メンター": user?.evaluation?.mentor?.name ?? '',
            "職階": user?.evaluation?.general_position ?? '',
            "職務評価基準": user?.evaluation?.current_level ?? '',
            "昇格_職階": user?.evaluation?.candidate?.some(c => c.next_candidate == '昇格（職階）') ? '〇' : '',
            "現在の号俸": user?.evaluation?.current_salary_rank ?? '',
            "異動後の号俸": user?.evaluation?.after_salary_rank ?? '',
            "合計評価点": `${totalOverallScore(user.outcome_goals)}/${user.outcome_goals.length * 100}`,
        })
    })
    if(data && data.length){
        const csv = generateCsv(csvConfig)(data)
        download(csvConfig)(csv);
    } else {
        ping('出力するデータはありません。')
        return
    }
}
const positions = computed(() => {
    const pos = props.userList.map(user => user?.positions).filter(pos => pos !== null && pos !== undefined)
    //make unique
    const uniquePositions = Array.from(new Set(pos.map(p => p.id))).map(id => {
        return pos.find(p => p.id === id)
    })
    return uniquePositions
    // const posSet = new Set<{id: number, name: string}>()
    // props.userList.forEach(user => {
    //     if(user?.positions?.name){
    //         posSet.add({
    //             id: user.positions.id,
    //             name: user.positions.name
    //         })
    //     }
    // })
    // return Array.from(posSet).map(position => {
    //     return {
    //         id: position.id,
    //         name: position.name
    //     }
    // })
})
const uploadUploadCsv = async(ev: Event) => {
    const input = ev.target as HTMLInputElement
    if (!input.files?.length) return
    const file = input.files[0]
    const fd = new FormData()
    fd.append('file', file)
    if (selectedDate.value) {
        fd.append('year', selectedDate.value.year.toString())
        fd.append('which_half', selectedDate.value.which_half)
    }
    const data = await api.post('/upload_evaluation_csv', fd, { toast: 'CSVをアップロードしました。' })
}
const statusDisplay = (status:number) => {
    const statuses = [
        '作成中（本人対応中）', 
        '目標を差戻中（本人対応中）', 
        '目標を上席者に申請中（上席者対応中）', 
        '目標を人事に申請中（人事対応中）', 
        '目標の変更申請中（人事対応中）', 
        '目標承認済み（本人対応中）', 
        '結果入力中（本人対応中）',
        '結果を上席者に申請中（上席者対応中）',
        '報告を差戻中（本人対応中）', 
        '結果を上席者承認済み（完了）'
    ]
    return statuses[status] ?? '不明なステータス'
}
const announce = () => {
    announceWindow.value = true
    copyTemplate.value = null
}
const findTargetUsers = async() => {
    if(wantedNumber.value <= 0){
        ping('必須目標数は1以上で入力してください。')
        return
    }
    let d: any[] = []
    const users = props.userList.filter(u => selectedUserPositions.value.includes(u?.position_id))
    console.log(users)
    users.forEach(user => {
        const goalCount = user?.outcome_goals?.length || 0
        let u = {
            name: `[To:${user.name}:]`,
            must_create: 0,
            refused: 0,
            must_apply: 0,
            must_approve: 0,

        }
        const validGoals = user?.outcome_goals?.filter(g => [2,3,4,5,6,7,9].includes(g.status)) || []
        if(validGoals.length < wantedNumber.value){
            if(goalCount < wantedNumber.value){
                u.must_create = wantedNumber.value - goalCount
            }
            const creatingGoals = user?.outcome_goals?.filter(g => g.status === 0 ) || []
            if(creatingGoals.length){
                u.must_apply = creatingGoals.length
            }
            const refusedGoals = user?.outcome_goals?.filter(g => g.status === 1 || g.status === 7 ) || []
            if(refusedGoals.length){
                u.refused = refusedGoals.length
            }
            const applyingGoals = user?.outcome_goals?.filter(g => g.status === 2 || g.status === 8 ) || []
            if(applyingGoals.length){
                u.must_approve = applyingGoals.length
            }
            if(u.must_create || u.refused || u.must_apply || u.must_approve){
                d.push(u)
            }
        }
        // if(goalCount < wantedNumber.value){
        //     u.must_create = wantedNumber.value - goalCount
        // }
        // const creatingGoals = user?.outcome_goals?.filter(g => g.status === 0 ) || []
        // if(creatingGoals.length){
        //     u.must_apply = creatingGoals.length
        // }
        // const refusedGoals = user?.outcome_goals?.filter(g => g.status === 1 || g.status === 7 ) || []
        // if(refusedGoals.length){
        //     u.refused = refusedGoals.length
        // }
        // const applyingGoals = user?.outcome_goals?.filter(g => g.status === 2 || g.status === 8 ) || []
        // if(applyingGoals.length){
        //     u.must_approve = applyingGoals.length
        // }
        // if(u.must_create || u.refused || u.must_apply || u.must_approve){
        //     d.push(u)
        // }
        // copyTemplate.value = d
    })
    copyTemplate.value = d

}
const copy = () => {
    if(copyTemplateRef.value){
        try {
            //use clipboard api
            const content = copyTemplateRef.value.innerText
            navigator.clipboard.writeText(content)
            toast('クリップボードにコピーしました。')
        } catch (error) {
            ping('クリップボードへのコピーに失敗しました。')    
        }
    }
}
</script>
<style scoped>
    table{
        width: 100%;
        border-collapse: collapse;
        background-color: var(--background-color);
        font-size: 14px;

    }
    th, td {
        border: 1px solid var(--calendarBorder);
        padding: 8px;
        text-align: left;
        font-size: 13px;
    }
    thead {
        background-color: var(--background-color);
        position: sticky;
        top: 0;
        z-index: 10;
    }
    .evaluation-date{
        font-size: 16px;
        font-weight: 600;
    }
</style>