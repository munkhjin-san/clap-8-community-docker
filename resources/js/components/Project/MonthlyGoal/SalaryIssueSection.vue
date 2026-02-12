<template>
    <div>
        <div v-if="!creating">
            <IssueRecord 
                v-if="issue" 
                :issue="issue" 
                :goal="props.goal"
                @edit="(issue) => {creating = true; editData = issue }"
                @refresh="emit('refresh')"
            />
            <div v-else-if="canCreateIssue" >
                <p class="text-center">現在昇給課題はありません。</p>
                <LoaderButton 
                    class="mt-5"
                    :content="'昇給課題を作成する'" 
                    @triggered="creating = true"
                />
            </div>
            <div class="text-center" v-else>
                選択された成果目標は、昇給課題作成の要件を満たしていません。［期間］
            </div> 
        </div>
             
        <div v-if="creating">
            <IssueCreate 
                :editData="editData"
                :chosenGoal="props.goal"
                :selectedDate="selectedDate"
                @close="close"
            />
        </div>
    </div>
</template>
<script setup lang="ts">
import { ProjectGoal, SalaryIssue } from '@/interface/projectInterface';
import { computed, ref } from 'vue';
import IssueRecord from './SalaryIssue/IssueRecord.vue';
import { DateTime } from 'luxon';
import LoaderButton from '@/components/Global/LoaderButton.vue';
import IssueCreate from './SalaryIssue/IssueCreate.vue';

const props = defineProps<{
    goal: ProjectGoal
    selectedDate: string
}>()

const emit = defineEmits<{
    refresh: []
}>()

const creating = ref(false)

const editData = ref<SalaryIssue | null>(null)
const issue = computed(() => {
    return props.goal.salary_issue;
})

const canCreateIssue = computed(() => {
    const start = props.goal?.start_date ? DateTime.fromSQL(props.goal.start_date) : null;
    const end = props.goal?.end_date ? DateTime.fromSQL(props.goal.end_date) : null
    if (start?.isValid && end?.isValid) {
        const differenceInMonths = end.diff(start, 'months').as('months'); 
        const differenceInDays = end.diff(start, 'days').as('days');
        if (differenceInMonths >= 2.9 || differenceInDays >= 89) { 
            return true;
        }
    }
    return false
})

const close = (flag: boolean) => {
    creating.value = false
    editData.value = null
    if(flag){   
        emit('refresh')
    }
}
</script>