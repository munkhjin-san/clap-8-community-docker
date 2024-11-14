<template>
    <DraftLayout>
        <template #main>
            <div class="si-box" style="margin-top: 0">
                <ShortInput 
                    v-if="portfolio?.status < 1"
                    :initialValue="portfolio ? portfolio.portfolio_title : title"
                    :key="`p_key_${portfolio && portfolio.portfolio_title ? portfolio.portfolio_title : 0}`"
                    ref="titleRef"
                    placeHolder="ディスカッション用ポートフォリオタイトル"
                    name="title"
                    rules="required"
                    v-model="title"
                />
                <p v-else><strong>ディスカッション用ポートフォリオタイトル<br></strong>{{ portfolio?.portfolio_title }}</p>
            </div>
            <div class="si-box">
                <LongInput
                    v-if="portfolio?.status < 1"
                    :placeHolder="`ディスカッション用ポートフォリオ内容`"
                    rules="required" 
                    ref="storyRef"
                    name="story"
                    v-model="storyContent"
                    :initialValue="portfolio ? portfolio.content : storyContent"   
                    :key="`${portfolio ? portfolio.content : 0}_${route.fullPath}_${portfolio?.updated_at}`"
                />
                <p v-else><strong>ディスカッション用ポートフォリオ内容<br></strong>{{ portfolio?.content }}</p>
            </div>
            <div class="si-box">
                <LongInput 
                    v-if="portfolio?.status < 1" 
                    :placeHolder="`ディスカッション用ポートフォリオエピソード`"
                    ref="episodeRef"
                    rules="required"
                    name="episode"
                    v-model="episodeContent"
                    :initialValue="portfolio ? portfolio.episode : episodeContent" 
                    :key="`${portfolio ? portfolio.episode : 0}_${route.fullPath}_${portfolio?.updated_at}`"
                />
                <p v-else><strong>ディスカッション用ポートフォリオエピソード<br></strong>{{ portfolio?.episode }}</p>
            </div>
            
            <OpenAiReview 
                v-if="selectedTopic && portfolio && selectedTopic.assistant_id" 
                :assistand-id="selectedTopic.assistant_id" 
                :soure-text="portfolio?.ai_review_pre" 
                :message="storyContent + episodeContent"
                :confirm-text="'発表用ポートフォリオは、研修テーマに沿った内容であり、発表時間が５分程度の内容にまとめられている。'"
                ref="reviewEl"
            />
            <div v-if="portfolio?.status < 1" style="display:flex; justify-content: center; gap:20px;flex-wrap: wrap;margin-top: 25px;">              
                <LoaderButton style="margin: 0" @triggered="finishReview(0)" :loading="loading[0]" :content="'一時保存'"/>
                <LoaderButton style="margin: 0" @triggered="finishReview(1)" :loading="loading[1]" :content="'次へ'"/>               
            </div>
        </template>
    </DraftLayout>
</template>
<script setup>
import OpenAiReview from '@/components/Global/OpenAiReview.vue';
import DraftLayout from '../DraftLayout.vue';
import LoaderButton from '@/components/Global/LoaderButton.vue';
import ShortInput from '@/components/Form/ShortInput.vue';
import LongInput from '@/components/Form/LongInput.vue';
import { inject, computed, ref } from 'vue';
import { useRoute } from 'vue-router';
const props = defineProps(['selectedTopic'])
const portfolio = inject('portfolio')
const route = useRoute()
const title = ref('')
const episodeContent = ref('')
const storyContent = ref('')
const titleRef = ref(null)
const episodeRef = ref(null)
const storyRef = ref(null)
const reviewEl = ref(null)
const { notify } = inject('dialog')
const { loading, saveItems } = inject('basicItem')
const targetRefs = computed(() => {
    return [titleRef.value, episodeRef.value, storyRef.value]
})
const params = computed(() => {
    return {
            params: {
                episode: episodeContent.value,
                content: storyContent.value, 
                portfolio_title: title.value,
                ai_review_pre: reviewEl.value?.reviewResultRaw,
            },
            theme_id: route.params.lessonThemeId,
        }
})
const finishReview = async(status) => {
    if(props.selectedTopic.assistant_id && !reviewEl.value?.reviewResultRaw){
        notify('基礎知識研修を完了する前、AI分析してください。')
        return
    }
    const valid = await reviewEl.value?.validate()
    if(props.selectedTopic.assistant_id && !valid){
        return
    }
    await saveItems('summary', status, targetRefs.value, params.value)
}
</script>