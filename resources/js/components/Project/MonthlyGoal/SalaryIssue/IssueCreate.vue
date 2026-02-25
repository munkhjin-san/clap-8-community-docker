<template>

    <div class="kadai-root">
        <div>
            <div style="width: 100%;">
                <p>{{ selectedDateDate.name }}</p>
                <p>メンター: {{ evaluationData?.mentor?.name ?? '' }}</p>
            </div>
        </div>
        <Step2
            v-if="step === 1"
            :chosenGoal="chosenGoal"
            :getIssues="getIssues"
            :possibleThemes="completedLessonThemes"
            @next="next"
            @selectThemeConfirm="selectThemeConfirm"
        />
        <Step3
            v-if="step === 2 && selectedTheme"
            :chosenGoal="chosenGoal"
            :selectedTheme="selectedTheme"
            :editData="editData"
            :getIssues="getIssues"
            :selectedDate="selectedDateDate"
            @close="(flag) => emit('close', flag)"
            @goback="step = 1"
        />
    </div>

</template>
<script setup lang="ts">
import { useResponsive } from '@/store/responsive';
import { computed, onMounted, ref } from 'vue';
import { useAuthUserStore } from '@/store/auth';
import { useApi } from '@/composables/api';
import Step3 from './Step3.vue';
import Step2 from './Step2.vue';
import { ProjectGoal, SalaryIssue } from '@/interface/projectInterface';
import { useDashboardGoalsStore, issueThemes } from '@/store/dashboardGoals';
import { storeToRefs } from 'pinia';
const emit = defineEmits<{
    close: [flag: boolean]
}>()
const props = defineProps<{
    editData: SalaryIssue | null,
    selectedDate: string,
    chosenGoal: ProjectGoal,
}>()
const responsive = useResponsive()
const step = ref<number | null>(null)
const modalContent = ref<HTMLElement | null>(null)
const selectedTheme = ref<typeof issueThemes[0]['issues'][0] | null>(null)
const api = useApi()
const selectedDateDate = computed(() => {
    const [year, which_half] = props.selectedDate.split('-')
    return {
        year: Number(year),
        which_half: which_half,
        name: which_half === 'first' ? `${year}年上期` : `${year}年下期`
    }
})

const { evaluationData } = storeToRefs(useDashboardGoalsStore())
const next = (val: number) => {
    step.value = val
    if(modalContent.value){
        modalContent.value.scrollTop = 0;
    }
    if(val == 1){
        emit('close', false)
    }
    
}

const completedLessonThemes = ref<string[]>([])

const selectThemeConfirm = (level, theme) => {
    selectedTheme.value = getIssues(level, theme)[0]
    next(2)
}
const auth = useAuthUserStore()
onMounted(async() => {
    if(props.editData){
        editIssue(props.editData)
        step.value = 2
    }else{
        step.value = 1
    }
    completedLessonThemes.value = await api.get('/get_completed_lesson_themes')
})
const editIssue = (issue: SalaryIssue) => {

        
    let theme: typeof issueThemes[0]['issues'][0] | null = null;
    for (const item of issueThemes) {
        const topicData = item.issues.find((topic) => topic.title_full === issue.theme);
        if (topicData) {
            theme = topicData;
        }
    }
    if(theme){
        selectedTheme.value = theme
    }
    
}

const getIssues = (level, theme) => {
    if(issueThemes){
        const foundItem = issueThemes.find((item) => item.level === level);
        if (foundItem) {
            return foundItem.issues.filter((issue) => issue.theme === theme);
        }
    }
    return []
}


</script>
<style>
.undo-kadai{
    display: flex;
    align-items: center;
    cursor: pointer;
    gap: 10px;
    margin-bottom: 15px;
}

.issue-content{
    font-size: 12px;
    margin-top: 10px;
    white-space: break-spaces;
}

.levelTitle{
    height: 30px;
    line-height: 30px;
    /* background: var(--selected-background); */
    border: solid thin var(--formBorder);
}
.themeTitle{
    /* writing-mode: vertical-rl; */
    text-orientation: upright;
    /* background: var(--selected-background); */
    border: solid thin var(--formBorder);
    text-align: center;
    vertical-align: middle;
    width: 30px;
   
}
.selectable-theme{
    cursor: pointer;
    border-right: solid thin var(--formBorder);
    border-bottom: solid thin var(--formBorder);
    width: 32%;
}
.selectable-theme:hover{
    background: var(--bg2);
}
.selected-theme{
    padding: 10px 15px;
    background: var(--bg3);
}

</style>