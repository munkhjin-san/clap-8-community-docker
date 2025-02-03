<template>
    <div class="admin-window" style="overflow: visible;">
        <div style="display: flex;align-items: center;padding: 0 20px;position: absolute;right: 0;top: -45px;">            
            <div class="admin-button" style="width: fit-content;flex: 0;margin: 0 0 0 auto;" @click="downloadCSV">CSV出力</div>
        </div>
        <div class="records-wrapper scrollable">
            <div class="records-table">
                <div class="records-header">
                    <div class="header-row">                        
                        <div class="header-cell">氏名</div>
                        <div class="header-cell">ステータス</div>
                        <div class="header-cell">ケーススタディ答え</div>
                        <div class="header-cell">基礎知識理解不能</div>
                        <div class="header-cell">理解できない理由</div>
                        <!-- <div class="header-cell">アンケート</div>                    -->
                    </div>
                </div>
               
                <div class="records-body">
                    <div class="body-row" v-for="lesson in lessons">
                        <div class="body-cell border-none">{{ lesson?.user?.name }}</div>
                        <div class="body-cell border-none" @click.stop="menu.setMenu({ id: lesson.id, name: `status_control${lesson.id}`})">
                            <div v-if="lesson.basic_knowledge_completed" class="flex whitespace-nowrap flex-nowrap gap-[15px] justify-between" style="padding: 5px 0;">
                                <div>✅基礎知識</div>
                            </div>
                            <div v-else-if="lesson.basic_knowledge_uncompleted" class="flex whitespace-nowrap flex-nowrap gap-[15px] justify-between" style="padding: 5px 0;">
                                <div>❌基礎知識</div>
                            </div>
                            <div v-if="lesson.case_study_completed" class="flex whitespace-nowrap flex-nowrap gap-[15px] justify-between" style="padding: 5px 0;">
                                <div>✅ケーススタディ</div>
                            </div>
                            <div v-if="lesson.survey_completed" class="flex whitespace-nowrap flex-nowrap gap-[15px] justify-between" style="padding: 5px 0;">
                                <div>✅チェックリスト</div>
                            </div>
                        </div>
                        <div class="body-cell border-none" style="text-align: left;position: relative;">
                            <div class="pt-content">
                                <p @click.stop="menu.setMenu({ id: lesson.user.id, name: `pt_content${lesson.user.id}`})" style="max-height: 40px;overflow:hidden;white-space: break-spaces;word-break: break-all;">
                                    <div v-if="lesson.answers.length" v-for="answer in lesson.answers">
                                        <p>{{ answer.answer }}</p>
                                    </div>
                                </p>
                                <p v-if="menu.name == `pt_content${lesson.user.id}` && menu.id == lesson.user.id" :id="`pt_content${lesson.user.id}`" class="pt-popup shadow-me" style="left:0;right:auto">
                                    <div v-if="lesson.answers.length" v-for="(answer, index) in lesson.answers">
                                        <p>{{ answer.answer }}</p>
                                        <div v-if="index !== lesson.answers.length - 1" class="post-separetor mt-[30px]"></div>
                                    </div>
                                </p>
                            </div>
                        </div>
                        <div class="body-cell border-none" style="text-align: left;position: relative;">
                            <div class="pt-content">
                                <p @click.stop="menu.setMenu({ id: lesson.user.id, name: `pt_dt_und${lesson.user.id}`})" style="max-height: 40px;overflow:hidden;white-space: break-spaces;word-break: break-all;">
                                {{ lesson?.cant_understand }}
                                </p>
                                <p v-if="menu.name == `pt_dt_und${lesson.user.id}` && menu.id == lesson.user.id" :id="`pt_dt_und${lesson.user.id}`" class="pt-popup shadow-me" style="left:auto;right:0">
                                    {{ lesson?.cant_understand }}
                                </p>
                            </div>
                            
                        </div>
                        <div class="body-cell border-none" style="text-align: left;position: relative;">
                            <div class="pt-content">
                                <p @click.stop="menu.setMenu({ id: lesson.user.id, name: `reason_dt_und${lesson.user.id}`})" style="max-height: 40px;overflow:hidden;white-space: break-spaces;word-break: break-all;">
                                    {{ lesson?.reason_dnt_und }}
                                </p>
                                <p v-if="menu.name == `reason_dt_und${lesson.user.id}` && menu.id == lesson.user.id" :id="`reason_dt_und${lesson.user.id}`" class="pt-popup shadow-me" style="left:auto;right:0">
                                    {{ lesson?.reason_dnt_und }}
                                </p>
                            </div>
                        </div>
                        <!-- <div class="body-cell border-none" style="text-align: left;position: relative;">
                            <div class="pt-content">
                                <p @click.stop="menu.setMenu({ id: portfolio.id, name: `pt_content_public${portfolio.id}`})" style="max-height: 40px;overflow:hidden;white-space: break-spaces;word-break: break-all;">
                                    <p v-if="portfolio.public_title">{{ portfolio.public_title }}</p>
                                    <p>{{ portfolio.public_content }}</p>
                                </p>
                                <p v-if="menu.name == `pt_content_public${portfolio.id}` && menu.id == portfolio.id" :id="`pt_content_public${portfolio.id}`" class="pt-popup shadow-me">
                                    <p v-if="portfolio.public_title">{{ portfolio.public_title }}</p>
                                    <p>{{ portfolio.public_content }}</p>
                                </p>
                            </div>
                        </div>
                        <div class="body-cell border-none" style="text-align: left;position: relative;">
                            <div class="pt-content" v-if="portfolio?.lesson_form">
                                <p @click.stop="menu.setMenu({ id: portfolio.id, name: `pt_form${portfolio.id}`})" style="max-height: 40px;overflow:hidden;white-space: break-spaces;word-break: break-all;">
                       
                                    <p v-for="i in 3">{{ portfolio?.lesson_form[`answer${i}`] }}</p>
                                </p>
                                <p v-if="menu.name == `pt_form${portfolio.id}` && menu.id == portfolio.id" :id="`pt_form${portfolio.id}`" class="pt-popup shadow-me">
                                    <p v-for="i in 3" style="line-height:1.6">
                                        <p><strong>Q: </strong>{{ portfolio?.lesson_form[`question${i}`] }}</p>
                                        <p><strong>A: </strong> {{ portfolio?.lesson_form[`answer${i}`] }}</p>
                                    </p>
                                    <p v-if="portfolio?.lesson_form['content']"><strong>意見: </strong>{{ portfolio?.lesson_form['content'] }}</p>
                                </p>
                            </div>
                        </div> -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script setup>
import { inject, onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import { useMenuStore } from '@/store/menu';
import { mkConfig, generateCsv, download } from "export-to-csv";
const props = defineProps(['theme'])
const menu = useMenuStore()
const route = useRoute()
const portfolios = ref([])
const status_values = ['', '✅基礎知識', '✅ディスカッション', '✅ポートフォリオ']
const { info, notify } = inject('dialog')
const lessons = ref([])
onMounted(() => {
    getLessons()
})
const getLessons = async() => {
    lessons.value = await axios.get(`/get_material_list?lesson_theme_id=${props.theme.id}`).then(response => response.data)
}
const downloadCSV = () => {
    const csvConfig = mkConfig({ 
        useKeysAsHeaders: true, 
        filename: props.theme ? props.theme.title : 'CSVデータ'
    });

    const data = [];

    Object.values(lessons.value).forEach(item => {
        const basicStatus = item.basic_knowledge_completed 
            ? '✅基礎知識' 
            : item.basic_knowledge_uncompleted 
            ? '❌基礎知識' 
            : '';
            
        const caseStatus = item.case_study_completed ? '✅ケーススタディ' : '';
        const surveyStatus = props.theme?.survey_completed ? '✅チェックリスト' : '';
        
        let understand = '';
        item.answers?.forEach(answer => {
            understand += answer.answer + '\n';
        });

        const row = {
            "氏名": item.user ? item.user.name : '',
            "ステータス": `${basicStatus}\n${caseStatus}\n${surveyStatus}`,
            "ケーススタディ答え": understand,
        };
        
        data.push(row);
    });

    const csv = generateCsv(csvConfig)(data);
    download(csvConfig)(csv);
}
const statusUpdate = (value, id) => {
    axios.put(`/update_portfolio_status`, {id: id, value: value}).then(response => {
        info('保存しました。')
        getLessons()
    }).catch(function (error) {
        if (error.response) notify('エラーが発生しました。 ' + error.response.data.message)
        else if (error.request) notify('エラーが発生しました。')
        else notify('エラーが発生しました。 ' + error.message)                       
    });
}

</script>
<style scoped>
    .body-cell{
        line-height: 2;
    }
    .pt-content{
        max-height: 40px;
        line-height: 1.5;
        position: relative;
    }
    .pt-popup{
        position: absolute;
        top: 0px;
        left: auto;
        right: 0;
        white-space: break-spaces;
        line-height: 1.5;
        z-index: 5;
        background: var(--background-color);
        padding: 15px;
        text-align: left;
        width: max-content;
        max-width: 40vw;
        overflow: hidden;
        word-break: break-all;
        
    }
</style>