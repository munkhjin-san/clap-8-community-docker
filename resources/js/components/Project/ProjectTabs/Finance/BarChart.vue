<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { Chart, registerables } from 'chart.js';
import { Bar } from 'vue-chartjs';
import { YearlyFinancialData } from '@/interface/projectInterface';

Chart.register(...registerables);


const props = defineProps<{
    projectsData: YearlyFinancialData;
    activeView: 'sales' | 'expense' | 'profit';
}>();



// Chart data computed property
const chartData = computed(() => {
    const labels = Object.keys(props.projectsData);
    const datasets = [
        {
            label: '年度予算',
            backgroundColor: '#4bc0c0',
            data: labels.map(label => props.activeView == 'profit' ? props.projectsData[label].yearly_plan.sales - props.projectsData[label].yearly_plan.expense : props.projectsData[label].yearly_plan[props.activeView])
        },
        {
            label: '損益計画',
            backgroundColor: '#ff9f40',
            data: labels.map(label => props.activeView == 'profit' ? props.projectsData[label].profit.sales - props.projectsData[label].profit.expense : props.projectsData[label].profit[props.activeView])
        },
        {
            label: '実績',
            backgroundColor: '#36a2eb',
            data: labels.map(label => props.activeView == 'profit' ? props.projectsData[label].settlement.sales - props.projectsData[label].settlement.expense : props.projectsData[label].settlement[props.activeView])
        }
    ];
    return {
        labels,
        datasets
    };
});

// Chart options
const chartOptions = computed(() => {
    return {
        responsive: true,
        maintainAspectRatio: false,
        indexAxis: 'y' as const, 
        plugins: {
            legend: {
                position: 'top' as const,
                labels: {
                    color: 'gray',
                    font: {
                        size: 14,
                        family: 'Noto Sans JP'
                    }
                }
            },
            tooltip: {
                callbacks: {
                    label: function (context: any) {
                        let label = context.dataset.label || '';
                        if (label) {
                            label += ': ';
                        }
                        if (context.parsed.x !== null) {
                            // Format as currency
                            label += new Intl.NumberFormat('ja-JP', {
                                style: 'currency',
                                currency: 'JPY',
                                maximumFractionDigits: 0
                            }).format(context.parsed.x);
                        }
                        return label;
                    }
                }
            }
        },
        scales: {
            x: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: '金額 (¥)'
                },
                ticks: {
                    callback: function (value: any) {
                        return new Intl.NumberFormat('ja-JP', {
                            style: 'currency',
                            currency: 'JPY',
                            notation: 'compact',
                            maximumFractionDigits: 1
                        }).format(value);
                    }
                }
            },
            y: {
                ticks: {
                    autoSkip: false
                }
            }
        }
    };
});

const chartHeight = computed(() => {
    const baseHeight = 200;
    const heightPerProject = 40;
    return baseHeight + (Object.keys(props.projectsData).length * heightPerProject);
});

</script>

<template>
    <div>      

        <div class="chart-container" :style="{ height: chartHeight + 'px' }">
            <Bar :data="chartData" :options="chartOptions" />
        </div>
    </div>
</template>

<style scoped>


.chart-container {
    position: relative;
}
</style>