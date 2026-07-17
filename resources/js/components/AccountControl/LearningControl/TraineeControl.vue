<template>
    <div class="admin-window trainee-control">
        <div class="trainee-control__actions">
            <LoaderButton
                content="CSV出力"
                @triggered="downloadCSV"
            />
        </div>
        <PortfolioTraineeTable
            :portfolios="portfolios"
            @rollback-status="statusUpdate"
            @delete-portfolio="deletePortfolio"
        />
    </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { mkConfig, generateCsv, download } from 'export-to-csv'
import { useLearningApi } from '@/composables/learningApi'
import type { LearningPortfolio, LearningTheme } from '@/types/learning'
import { buildPortfolioCsvRows } from '@/utils/learningPortfolioCsv'
import PortfolioTraineeTable from './Participant/PortfolioTraineeTable.vue'
import LoaderButton from '@/components/Global/LoaderButton.vue'

const props = defineProps<{
    theme?: LearningTheme | null
}>()

const route = useRoute()
const portfolios = ref<LearningPortfolio[]>([])
const learningApi = useLearningApi()

onMounted(() => {
    getPortfolios()
})

const getPortfolios = async() => {
    const themeId = Array.isArray(route.params.themeId) ? route.params.themeId[0] : route.params.themeId
    if (!themeId) return

    portfolios.value = await learningApi.getAdminPortfolios(themeId)
}

const downloadCSV = () => {
    const csvConfig = mkConfig({
        useKeysAsHeaders: true,
        filename: props.theme?.title ?? 'CSVデータ',
    })
    const csv = generateCsv(csvConfig)(buildPortfolioCsvRows(portfolios.value))
    download(csvConfig)(csv)
}

const statusUpdate = async(id: number | undefined, value: number) => {
    if (!id) return

    await learningApi.updatePortfolioStatus(id, value)
    getPortfolios()
}

const deletePortfolio = async(id: number | undefined) => {
    if (!id) return

    await learningApi.deleteAdminPortfolio(id)
    getPortfolios()
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
