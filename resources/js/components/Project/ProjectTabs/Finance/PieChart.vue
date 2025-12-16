<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { Chart, registerables } from 'chart.js';
import { Pie } from 'vue-chartjs';
import { YearlyFinancialData } from '@/interface/projectInterface';

Chart.register(...registerables);
const pieOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'bottom' as const,
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

const props = defineProps<{
    projectsData: YearlyFinancialData;
    activeScenario: string
    activeType: string
}>();

const pieData = computed(() => {
    interface PieData {
        labels: string[],
        datasets: [{ data: number[], backgroundColor: string[] }]
    }
    const data:PieData = {
        labels: [],
        datasets: [{ data: [], backgroundColor: [] }]
    }
    for(const project in props.projectsData) {
        data.labels.push(project)
        data.datasets[0].data.push(props.projectsData[project][props.activeScenario][props.activeType])
        data.datasets[0].backgroundColor.push('#' + Math.floor(Math.random()*16777215).toString(16))

    }
    return data
})
const chartHeight = computed(() => {
    const baseHeight = 200;
    const heightPerProject = 40;
    return baseHeight + (Object.keys(props.projectsData).length * heightPerProject / 4);
});
</script>
<template>
    <div class="w-[80%]" :style="{ height: chartHeight + 'px' }">
        <Pie :options="pieOptions" :data="pieData" />
    </div>
    
</template>