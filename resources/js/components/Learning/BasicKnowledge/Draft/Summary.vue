<template>
    <DraftLayout>
        <template #main>
            
            <div class="si-box" style="margin-top: 0;">
                <p><strong>ディスカッション用ポートフォリオタイトル</strong></p>
                <p>{{ portfolio?.portfolio_title }}</p>
            </div>
            <div class="si-box">
                <p><strong>ディスカッション用ポートフォリオ内容</strong></p>
                <p>{{ portfolio?.content }}</p>
            </div>
            <div class="si-box" v-if="portfolio?.status < 1">
                <p><strong>ディスカッション用ポートフォリオエピソード</strong></p>
                <p>{{ portfolio?.episode }}</p>
            </div>
            <div class="si-box" v-if="portfolio?.status < 1">
                <LoaderButton @triggered="finishPortfolio()" :loading="loading[0]" :content="'作成完了'"/>               
            </div>
            <div v-else class="si-box" style="margin:45px 0">
                <LoaderButton :loading="false" content="ポートフォリオ作成例を確認する" @triggered="viewPortfolios"/>
            </div>
        </template>
    </DraftLayout>
</template>
<script setup>
import {useRoute, useRouter} from 'vue-router'
import DraftLayout from '../DraftLayout.vue';
import LoaderButton from '@/components/Global/LoaderButton.vue';
import { computed, inject } from 'vue';
import { useDialog } from '@/composables/dialog';
const route = useRoute()
const router = useRouter()
const portfolio = inject('portfolio')
const { loading, saveItems, viewPortfolios } = inject('basicItem')
const lesson = inject('getLessonPortfolios')
const { ask } = useDialog()
const params = computed(() => {
    return  {
        params: {
            status : 1,
            content: `${portfolio.value?.content}\n\n${portfolio.value?.episode}` 
        },
        theme_id: route.params.lessonThemeId,
    }
})
const finishPortfolio = async() => {
    const answer = await ask('知識研修を完了にしますか。\n完了後は編集ができません。')
    if(!answer.value) return  
    await saveItems('summary', 0, [], params.value)
    setTimeout(() => {                    
        finishBasic()
    }, 1000); 
}

const finishBasic = async() => {
    const options = {
        answers: [{label: 'OK', value: true}]
    }
    const answer = await ask('知識研修完了しました。\nお疲れ様でした。', options)
    if(answer.value){
        loading.value[0] = false
        await lesson()                     
        router.push({name: 'top'})
    }        
} 
</script>