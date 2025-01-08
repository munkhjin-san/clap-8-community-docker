<template>
    <div class="post-container scrollable" style="height: calc(100% - 126px);">
        <div class="evaluation-date">
            <select name="locales" v-model="selectedDate" class="dropDownSelector cursor-pointer" style="width: fit-content; padding: 5px 10px;">
                <option :value="date" v-for="date in targetDates">{{ parseDate(date)}}</option>
            </select>
        </div>
        <div class="project-table">
            <div class="project-header-row">
                <div class="project-cell">メンバー</div>
                <div class="project-cell">雇用形態</div>
                <div class="project-cell">メンター</div>
                <div class="project-cell">職階</div>
                <div class="project-cell">職務評価基準</div>
                <div class="project-cell">等級</div>
                <div class="project-cell">現在の号俸</div>
                <div class="project-cell">異動後の号俸</div>
                <div class="project-cell">アクション</div>
            </div>
            <div class="project-cell-row" v-for="user in searchResults">
                <div class="project-cell">
                    <span>{{user.name}}</span>
                </div>
                <div class="project-cell">{{ user?.positions?.name }}</div>
                <div class="project-cell">{{ user?.evaluation?.mentor?.name }}</div>
                <div class="project-cell">{{ user?.evaluation?.general_position }}</div>
                <div class="project-cell">{{ user?.evaluation?.current_level }}</div>
                <div class="project-cell">{{ user?.evaluation?.grade }}</div>
                <div class="project-cell">{{ user?.evaluation?.current_salary_rank }}</div>
                <div class="project-cell">{{ user?.evaluation?.after_salary_rank }}</div>
                <div class="project-cell">
                    <div style="display: flex; gap: 10px;">
                        <CommandButton 
                            :buttons="[
                                { title: '追加', action: () => addEvaluation(user)},
                            ]"
                        />
                    </div>
                </div>
            </div>
        </div>
        <Transition name="modalFade">
            <EvaluationCreation 
                v-if="createWindow"
                :user="selectedUser"
                :selectedDate="selectedDate"
                :mentorList="mentorList"
                :salary_options="salary_options"
                :editData="editData"
                @close="createWindow = false"
            />
        </Transition>
    </div>
</template>
<script lang="ts" setup>
import { evaluationDateOptions, parseDate } from '@/utils/tools';
import { onMounted, ref, computed } from 'vue';
import CommandButton from '@/components/Global/CommandButton.vue';
import EvaluationCreation from './EvaluationCreation.vue'
import axios from 'axios';
import { Evaluation } from '@/interface/projectInterface';
const props = defineProps(['userList', 'mentorList', 'keywords'])

const targetDates = evaluationDateOptions()
const selectedDate = defineModel()
const createWindow = ref(false)
const selectedUser = ref(null)
const salary_options = ref([])
const editData = ref<Evaluation>()
onMounted(() => {
    getSalaryOptions()
})
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
    try {
        salary_options.value = await axios.get('/get_salary_options').then(res => res.data)
    } catch (e) {

    }
}

</script>

<style scoped lang="scss">
    .project-table {
        display: table;
        border-collapse: collapse;
        width: 100%;
        font-size: 13px;
        background-color: var(--background-color);
    }

    .project-cell {
        display: table-cell;
        border: 1px solid var(--calendarBorder);
        text-align: left;
        padding: 5px;
        line-height: normal;
        max-width: 250px;
        height: 25px;
        vertical-align: middle;
    }
    .text-wrap {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        word-break: break-word;
    }
    .project-header-row {

        display: table-row;
        position: sticky;
        top: -1px;
        background-color: var(--background-color);
        z-index: 1;
    }
    .project-cell-row {
        display: table-row;
    }
    .project-cell-row:hover{
        background-color: var(--bg3);
        cursor: pointer;
    }
    .evaluation-date {
        position: absolute;
        top: 75px;
        right: 20px;
    }
</style>