<template>
    <div class="post-container scrollable" style="height: calc(100% - 126px);margin: 0 20px">
        <div class="evaluation-date">
            <select name="locales" v-model="selectedDate" class="dropDownSelector cursor-pointer" style="width: fit-content; padding: 5px 10px;">
                <option :value="date" v-for="date in targetDates">{{ date.name}}</option>
            </select>
        </div>
        <div class="project-table-container" style="height: calc(100% - 50px);width: calc(100% - 40px);">
            <div class="project-table">
                <div class="project-header-row">
                    <div class="project-cell">メンバー</div>
                    <div class="project-cell">雇用形態</div>
                    <div class="project-cell">メンター</div>
                    <div class="project-cell">職階</div>
                    <div class="project-cell">職務評価基準</div>
                    <div class="project-cell">昇格（職階）</div>
                    <!-- <div class="project-cell">等級</div> -->
                    <div class="project-cell">現在の号俸</div>
                    <div class="project-cell">異動後の号俸</div>
                    <div class="project-cell">合計評価点</div>
                    <div class="project-cell">アクション</div>
                </div>
                <div class="project-cell-row" v-for="user in searchResults">
                    <div class="project-cell">
                        <span>{{user.name}}</span>
                    </div>
                    <div class="project-cell">{{ user?.positions?.name }}</div>
                    <div class="project-cell">
                        <div class="flex items-center gap-[10px]">
                            <div style="display: flex; gap: 10px;">
                                <CommandButton 
                                    :buttons="[
                                        { title: '設定', action: () => setMentor(user)},
                                    ]"
                                />
                            </div>
                            <div>
                                {{ user?.evaluation?.mentor?.name }}
                            </div>
                        </div>

                        
                    </div>
                    <div class="project-cell">{{ user?.evaluation?.general_position }}</div>
                    <div class="project-cell">{{ user?.evaluation?.current_level }}</div>
                    <div class="project-cell">
                        <svg v-if="user?.evaluation?.candidate?.some(c => c.next_candidate == '昇格（職階）')" fill="var(--primary-color)" version="1.1" xmlns="http://www.w3.org/2000/svg" height="10" viewBox="0 0 38 32">
                            <path d="M36.486 0.324c-0.666-0.515-1.629-0.396-2.204 0.22l-3.039 3.271-3.060 3.328c-2.031 2.23-4.067 4.452-6.086 6.689-2.025 2.234-8.487 9.367-9.743 10.772-0.132 0.15-0.369 0.129-0.486-0.025-1.060-1.399-2.287-3.028-3.468-4.519-1.161-1.465-2.516-3.22-3.271-4.144-0.755-0.927-1.702-2.093-2.191-2.668-0.528-0.625-1.457-0.791-2.182-0.329-0.765 0.489-0.973 1.521-0.518 2.307 0.367 0.636 2.307 3.801 2.307 3.801 0.801 1.27 3.213 5.039 3.699 5.791 0.487 0.751 1.194 1.782 1.879 2.788 0.684 1.004 1.52 2.313 2.429 3.264s2.487 0.627 3.321-0.358c1.932-2.282 9.588-11.527 11.498-13.857 1.916-2.327 3.815-4.668 5.719-7.004l2.842-3.517 2.823-3.535c0.548-0.687 0.451-1.716-0.272-2.276z"></path>
                        </svg>
                    </div>
                    <!-- <div class="project-cell">{{ user?.evaluation?.grade }}</div> -->
                    <div class="project-cell">{{ user?.evaluation?.current_salary_rank }}</div>
                    <div class="project-cell">{{ user?.evaluation?.after_salary_rank }}</div>
                    <div class="project-cell">{{ user?.outcome_goals_achievement_rate_total }}</div>
                    <div class="project-cell">
                        <div style="display: flex; gap: 10px;">
                            <CommandButton 
                                :buttons="[
                                    { title: '編集', action: () => addEvaluation(user)},
                                ]"
                            />
                        </div>
                    </div>
                </div>
            </div>
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
    </div>
</template>
<script lang="ts" setup>
import { detailedDateOptions } from '@/utils/tools';
import { onMounted, ref, computed, reactive } from 'vue';
import CommandButton from '@/components/Global/CommandButton.vue';
import EvaluationPreCreation from './EvaluationPreCreation.vue'
import { Evaluation } from '@/interface/projectInterface';
import MentorQuickSelector from './MentorQuickSelector.vue';
import { User } from '@/interface/globalInterface';
import { useApi } from '@/composables/api';
const props = defineProps(['userList', 'mentorList', 'keywords'])

const targetDates = detailedDateOptions()
const selectedDate = defineModel()
const createWindow = ref(false)
const selectedUser = ref(null)
const salary_options = ref([])
const editData = ref<Evaluation>()
const api = useApi()

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

const setMentor = (user) => {    
    mentorSelectorData.view = true
    mentorSelectorData.user = user
    mentorSelectorData.mentorList = props.mentorList
    mentorSelectorData.selectedMentor = user?.evaluation?.mentor ?? null
    mentorSelectorData.editId = user?.evaluation?.id ?? null
    mentorSelectorData.date = selectedDate.value

}

</script>
