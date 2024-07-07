<template>
    <DraftLayout>
        <template #main>
            <div class="si-box" style="margin-top:0;">
                <p><strong>ポートフォリオ内容</strong></p>
                {{ portfolio?.content }}
            </div>
            <div class="si-box" style="margin:45px 0">
                <LoaderButton :loading="false" content="ポートフォリオ作成例を確認する" @triggered="viewPortfolios"/>
            </div>
            <div class="si-box">
                <ShortInput
                    v-if="portfolio?.status < 1"
                    :initialValue="portfolio ? portfolio.portfolio_title : title"
                    :key="`p_key_${portfolio && portfolio.portfolio_title ? portfolio.portfolio_title : 0}`"
                    ref="titleRef"
                    placeHolder="ポートフォリオタイトル"
                    name="title"
                    rules="required"
                    v-model="title"
                />
                <p v-else><strong>ポートフォリオタイトル<br></strong>{{ portfolio?.portfolio_title }}</p>
            </div>
            <div v-if="portfolio?.status < 1" style="display:flex; justify-content: center; gap:20px;flex-wrap: wrap;margin-top: 25px;">              
                <LoaderButton style="margin: 0" @triggered="saveItems('review', 0, [titleRef], params)" :loading="loading[0]" :content="'一時保存'"/>
                <LoaderButton style="margin: 0" @triggered="saveItems('review', 1, [titleRef], params)" :loading="loading[1]" :content="'次へ'"/>               
            </div>
        </template>
    </DraftLayout>
</template>
<script setup>
import LoaderButton from '@/components/Global/LoaderButton.vue';
import DraftLayout from '../DraftLayout.vue';
import ShortInput from '@/components/Form/ShortInput.vue';
import { inject, ref, computed } from 'vue';
import { useRoute } from 'vue-router';
const route = useRoute()
const portfolio = inject('portfolio')
const title = ref('')
const titleRef = ref(null)
const { loading, saveItems, viewPortfolios } = inject('basicItem')
const params = computed(() => {
    return {
            params: { 
                portfolio_title: title.value,
            },
            theme_id: route.params.lessonThemeId,
        }
})
</script>