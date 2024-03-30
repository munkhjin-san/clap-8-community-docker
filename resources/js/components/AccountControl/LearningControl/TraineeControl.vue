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
                        <div class="header-cell">ディスカッション用ポートフォリオ</div>
                        <div class="header-cell">本ポートフォリオ</div>    
                        <div class="header-cell">アンケート</div>                   
                    </div>
                </div>
               
                <div class="records-body">
                    <div class="body-row" v-for="portfolio in portfolios">
                        <div class="body-cell border-none">{{ portfolio?.user.name }}</div>
                        <div class="body-cell border-none" @click.stop="menu.setMenu({ id: portfolio.id, name: `status_control${portfolio.id}`})">
                            <div v-for="status in portfolio.status" style="padding: 5px 0;display: flex;white-space: nowrap;flex-wrap: nowrap;gap: 15px;justify-content: space-between;">
                                <div>{{ status_values[status] }}</div>
                                <div>
                                    <button style="padding: 2px 10px;font-size: 10px;background: gray;" @click="statusUpdate(status - 1, portfolio.id)" class="commentEditButton">差し戻す</button>
                                </div>
                            </div>
                            
                        </div>
                        <div class="body-cell border-none" style="text-align: left;position: relative;">
                            <div class="pt-content">
                                <p @click.stop="menu.setMenu({ id: portfolio.id, name: `pt_content${portfolio.id}`})" style="max-height: 40px;overflow:hidden;white-space: break-spaces;word-break: break-all;">
                                    <p v-if="portfolio.portfolio_title">{{ portfolio.portfolio_title }}</p>
                                    <p>{{ portfolio.content }}</p>
                                </p>
                                <p v-if="menu.name == `pt_content${portfolio.id}` && menu.id == portfolio.id" :id="`pt_content${portfolio.id}`" class="pt-popup shadow-me" style="left:0;right:auto">
                                    <p v-if="portfolio.portfolio_title">{{ portfolio.portfolio_title }}</p>
                                    <p>{{ portfolio.content }}</p>
                                </p>
                            </div>
                        </div>
                        <div class="body-cell border-none" style="text-align: left;position: relative;">
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
                        </div>
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
onMounted(() => {
    getPortfolios()
})
const getPortfolios = async() => {
    portfolios.value = await axios.get(`/get_portfolios_list?theme_id=${route.params.themeId}`).then(response => response.data)
}
const downloadCSV = () => {
    const csvConfig = mkConfig({ useKeysAsHeaders: true, filename: props.theme ? props.theme.title : 'CSVデータ'});
    const data = []
    portfolios.value.forEach(item => {
        let understand = ''
        item.lesson_sections.forEach(element => {
            understand = understand + `${element.lesson_material ? element.lesson_material.title : ''}\n${element.content}\n\n`
        });
        let form = ''
        if(item.lesson_form){
            for (let index = 0; index < 2; index++) {
                const offset = index + 1
                const q = `Q: ${item.lesson_form[`question${offset}`]}\n`
                const a = `Q: ${item.lesson_form[`answer${offset}`]}\n`
                form = form + q
                form = form + a                
            }
            if(item.lesson_form.content){
                form = form + `意見: ${item.lesson_form.content}`
            }
        }
        const row = {
            "氏名" : item.user ? item.user.name : '',
            "ステータス" : status_values[item.status],
            "基礎知識理解" : understand,
            "ディスカッション用ポートフォリオ" : `${item.portfolio_title}\n${item.content}`,
            "ポジティブフィードバック" : item.positive_feedback,
            "ネガティブフィードバック" : item.negative_feedback,
            "フィードバックによる発見と成長" : item.noticed,
            "本ポートフォリオ" : `${item.public_title}\n${item.public_content}`,
            "アンケート" : form,
        }
        data.push(row)
    });
    const csv = generateCsv(csvConfig)(data);
    download(csvConfig)(csv);
}
const statusUpdate = (value, id) => {
    axios.put(`/update_portfolio_status`, {id: id, value: value}).then(response => {
        info('保存しました。')
        getPortfolios()
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