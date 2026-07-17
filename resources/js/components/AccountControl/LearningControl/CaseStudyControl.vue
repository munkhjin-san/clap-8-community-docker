<template>
    <div class="admin-window case-study-control">
        <div class="case-study-control__actions">
            <button
                class="admin-button case-study-control__csv"
                type="button"
                @click="downloadCSV"
            >
                CSV出力
            </button>
        </div>
        <CaseStudyParticipantTable :participants="participants" />
    </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { mkConfig, generateCsv, download } from 'export-to-csv'
import { useLearningApi } from '@/composables/learningApi'
import type { LearningParticipantProgress, LearningTheme } from '@/types/learning'
import { buildCaseStudyParticipantCsvRows } from '@/utils/learningCaseStudyParticipants'
import CaseStudyParticipantTable from './Participant/CaseStudyParticipantTable.vue'

const props = defineProps<{
    theme: LearningTheme
}>()

const participants = ref<LearningParticipantProgress[]>([])
const learningApi = useLearningApi()

onMounted(() => {
    getParticipants()
})

const getParticipants = async() => {
    participants.value = await learningApi.getMaterialProgressList(props.theme.id)
}

const downloadCSV = () => {
    const csvConfig = mkConfig({
        useKeysAsHeaders: true,
        filename: props.theme?.title ?? 'CSVデータ',
    })

    const csv = generateCsv(csvConfig)(buildCaseStudyParticipantCsvRows(participants.value))
    download(csvConfig)(csv)
}
</script>

<style scoped>
.case-study-control{
    overflow: visible;
}

.case-study-control__actions{
    display: flex;
    align-items: center;
    padding: 0 20px;
    position: absolute;
    right: 0;
    top: -45px;
}

.case-study-control__csv{
    width: fit-content;
    flex: 0;
    margin: 0 0 0 auto;
}
</style>
