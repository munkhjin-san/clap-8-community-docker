<template>
    <div class="admin-window trainee-control">
        <div class="trainee-control__actions">
            <LoaderButton
                content="CSV出力"
                @triggered="downloadCSV"
            />
        </div>
        <ParticipantTable
            :rows="rows"
            @rollback-status="statusUpdate"
            @delete-portfolio="deletePortfolio"
            @delete-progress="deleteProgress"
        />
    </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { mkConfig, generateCsv, download } from 'export-to-csv'
import { useLearningApi } from '@/composables/learningApi'
import type {
    LearningParticipantProgress,
    LearningPortfolio,
    LearningTheme,
    ParticipantRow,
    PortfolioSectionExam,
    PortfolioSectionExamResult,
} from '@/types/learning'
import { isEnabled } from '@/utils/learningProgress'
import { buildPortfolioCsvRows } from '@/utils/learningPortfolioCsv'
import { buildCaseStudyParticipantCsvRows } from '@/utils/learningCaseStudyParticipants'
import { caseStudyParticipantRows, portfolioParticipantRows } from '@/utils/participantTable'
import ParticipantTable from './Participant/ParticipantTable.vue'
import LoaderButton from '@/components/Global/LoaderButton.vue'

const props = defineProps<{
    theme?: LearningTheme | null
}>()

const route = useRoute()
const learningApi = useLearningApi()

const isCaseStudy = computed(() => isEnabled(props.theme?.has_case_study))

const portfolios = ref<LearningPortfolio[]>([])
const sectionExams = ref<PortfolioSectionExam[]>([])
const examResults = ref<Record<number, Record<number, PortfolioSectionExamResult>>>({})
const participants = ref<LearningParticipantProgress[]>([])

const themeId = computed(() => {
    const id = Array.isArray(route.params.themeId) ? route.params.themeId[0] : route.params.themeId
    return id ?? null
})

// Both theme models normalise into the same unified table rows.
const rows = computed<ParticipantRow[]>(() => {
    return isCaseStudy.value
        ? caseStudyParticipantRows(participants.value)
        : portfolioParticipantRows(portfolios.value, sectionExams.value, examResults.value)
})

const getData = async() => {
    if (!themeId.value || !props.theme) return

    if (isCaseStudy.value) {
        participants.value = await learningApi.getMaterialProgressList(themeId.value)
        return
    }

    const progress = await learningApi.getPortfolioProgress(themeId.value)
    portfolios.value = progress.portfolios
    sectionExams.value = progress.sectionExams
    examResults.value = progress.examResults
}

watch(() => props.theme, getData, { immediate: true })

const downloadCSV = () => {
    const csvConfig = mkConfig({
        useKeysAsHeaders: true,
        filename: props.theme?.title ?? 'CSVデータ',
    })
    const csvRows = isCaseStudy.value
        ? buildCaseStudyParticipantCsvRows(participants.value)
        : buildPortfolioCsvRows(portfolios.value)
    const csv = generateCsv(csvConfig)(csvRows)
    download(csvConfig)(csv)
}

const statusUpdate = async(id: number | undefined, value: number) => {
    if (!id) return

    await learningApi.updatePortfolioStatus(id, value)
    getData()
}

const deletePortfolio = async(id: number | undefined) => {
    if (!id) return

    await learningApi.deleteAdminPortfolio(id)
    getData()
}

const deleteProgress = async(userId: number) => {
    if (!userId || !themeId.value) return

    await learningApi.deleteAdminThemeProgress(themeId.value, userId)
    getData()
}
</script>

<style scoped>
.trainee-control{
    overflow: visible;
}

.trainee-control__actions{
    display: flex;
    align-items: center;
    padding: 0 20px;
    position: absolute;
    right: 0;
    top: -45px;
}
</style>
