<template>
<div style="height: calc(100% - 50px);overflow: auto;width: 100%;">
    <div class="admin-button" style="width: fit-content;flex: 0;margin: 0 0 0 auto;position: fixed;right: 24px;top: 10px;" @click="downloadCSV">CSV出力</div>
    <div v-for="portfolio in portfolioList" :key="portfolio.id" class="ev-u-wrap">
        <div class="ev-u-box">
            <span><strong>氏名：</strong></span>
            <span>{{ portfolio?.user?.name }}</span>
        </div>
        <div class="ev-u-box">
            <span><strong>ステータス：</strong></span>
            <span>
                <span v-for="status in getPortfolioStatusLabels(portfolio.status)" :key="status.value" style="margin-right: 15px;">
                    ✅{{ status.label }}
                </span>
            </span>
        </div>
        <div class="ev-u-box">
            <span><strong>知識研修理解：</strong></span>
            <span>
                <div v-for="section in portfolio.lesson_sections ?? []" :key="section.id">
                    <div>
                        <div>{{ section.lesson_material?.title }}</div>
                        <div>{{ section.content }}</div>
                    </div>
                </div> 
            </span>
        </div>
        <div class="ev-u-box">
            <span><strong>ディスカッション用ポートフォリオ：</strong></span>
            <p v-if="portfolio.portfolio_title">{{ portfolio.portfolio_title }}</p>
            <p>{{ portfolio.content }}</p>
        </div>
        <div class="ev-u-box">
            <span><strong>ポジティブフィードバック：</strong></span>
            <span>{{ portfolio.positive_feedback }}</span>
        </div>
        <div class="ev-u-box">
            <span><strong>ネガティブフィードバック：</strong></span>
            <span>{{ portfolio.negative_feedback }}</span>
        </div>
        <div class="ev-u-box">
            <span><strong>フィードバックによる発見と成長：</strong></span>
            <span>{{ portfolio.noticed }}</span>
        </div>
        <div class="ev-u-box">
            <span><strong>本ポートフォリオ：</strong></span>
            <p v-if="portfolio.public_title">{{ portfolio.public_title }}</p>
            <p>{{ portfolio.public_content }}</p>
        </div>
    </div>
</div>

</template>
<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import { mkConfig, generateCsv, download } from "export-to-csv";
import { getPortfolioStatusLabel, getPortfolioStatusLabels } from '@/utils/learningProgress';
import { useLearningApi } from '@/composables/learningApi';
import type { LearningPortfolio } from '@/types/learning';
const route = useRoute()
const learningApi = useLearningApi()
const portfolioList = ref<LearningPortfolio[]>([])
onMounted(async() => {
    const themeId = Array.isArray(route.params.lessonThemeId) ? route.params.lessonThemeId[0] : route.params.lessonThemeId
    if (!themeId) return
    portfolioList.value = await learningApi.getPortfolios(themeId)
})
const downloadCSV = () => {
    const csvConfig = mkConfig({ useKeysAsHeaders: true, filename: '職能研修機関確認用書類'});
    const data: Record<string, string | null | undefined>[] = []
    portfolioList.value.forEach(item => {
        let understand = ''
        item.lesson_sections?.forEach((element) => {
            understand = understand + `${element.lesson_material ? element.lesson_material.title : ''}\n${element.content}\n\n`
        });

        const row = {
            "氏名" : item.user ? item.user.name : '',
            "ステータス" : getPortfolioStatusLabel(item.status),
            "知識研修理解" : understand,
            "ディスカッション用ポートフォリオ" : `${item.portfolio_title}\n${item.content}`,
            "ポジティブフィードバック" : item.positive_feedback,
            "ネガティブフィードバック" : item.negative_feedback,
            "フィードバックによる発見と成長" : item.noticed,
            "本ポートフォリオ" : `${item.public_title}\n${item.public_content}`,

        }
        data.push(row)
    });
    const csv = generateCsv(csvConfig)(data);
    download(csvConfig)(csv);
}
</script>
<style scoped>
.ev-u-wrap{
    padding: 20px;
    line-height: 1.6;
    font-size: 14px;
    margin: 20px;
    background: var(--background-color);
    margin: 0 20px 20px 20px;
}
.ev-u-box{
    margin: 15px 0;
    white-space: break-spaces;
    overflow: hidden;
    overflow-wrap: break-word;
}
.admin-button{
    background: #4b4b4b;
    color: #fff;
    font-size: 12px;
    white-space: nowrap;
    width: -moz-fit-content;
    width: fit-content;
    margin: auto;
    position: relative;
    min-width: auto;
    min-height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    padding: 0 15px;
    flex: 1 0 auto;
    z-index: 3;
}
</style>
