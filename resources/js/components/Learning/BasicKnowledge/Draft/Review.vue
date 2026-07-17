<template>
    <DraftLayout>
        <template #main>
            <div class="si-box" style="margin-top: 0">
                <ShortInput 
                    v-if="isDraftEditable"
                    :initialValue="portfolio?.portfolio_title ?? title"
                    :key="`p_key_${portfolio?.portfolio_title ?? 0}`"
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
                    v-if="isDraftEditable"
                    :placeHolder="`ディスカッション用ポートフォリオ内容`"
                    rules="required" 
                    ref="storyRef"
                    name="story"
                    v-model="storyContent"
                    :initialValue="portfolio?.content ?? storyContent"
                    :key="`${portfolio?.content ?? 0}_${route.fullPath}_${portfolio?.updated_at}`"
                />
                <p v-else><strong>ディスカッション用ポートフォリオ内容<br></strong>{{ portfolio?.content }}</p>
            </div>
            <div class="si-box">
                <LongInput 
                    v-if="isDraftEditable"
                    :placeHolder="`ディスカッション用ポートフォリオエピソード`"
                    ref="episodeRef"
                    rules="required"
                    name="episode"
                    v-model="episodeContent"
                    :initialValue="portfolio?.episode ?? episodeContent"
                    :key="`${portfolio?.episode ?? 0}_${route.fullPath}_${portfolio?.updated_at}`"
                />
                <p v-else><strong>ディスカッション用ポートフォリオエピソード<br></strong>{{ portfolio?.episode }}</p>
            </div>
            
            <OpenAiReview 
                v-if="selectedTopic && portfolio && hasBeforeDiscussionReview"
                :config-key="beforeDiscussionConfig?.config_key"
                :lesson-theme-id="selectedTopic.id"
                :source-text="portfolio?.ai_review_pre ?? undefined"
                :message="storyContent + episodeContent"
                :confirm-text="'発表用ポートフォリオは、研修テーマに沿った内容であり、発表時間が５分程度の内容にまとめられている。'"
                ref="reviewEl"
            />
            <div v-if="isDraftEditable" style="display:flex; justify-content: center; gap:20px;flex-wrap: wrap;margin-top: 25px;">
                <LoaderButton style="margin: 0" @triggered="finishReview(0)" :loading="loading[0]" :content="'一時保存'"/>
                <LoaderButton style="margin: 0" @triggered="finishReview(1)" :loading="loading[1]" :content="'次へ'"/>               
            </div>
        </template>
    </DraftLayout>
</template>
<script setup lang="ts">
import OpenAiReview from '@/components/Global/OpenAiReview.vue';
import DraftLayout from '../DraftLayout.vue';
import LoaderButton from '@/components/Global/LoaderButton.vue';
import ShortInput from '@/components/Form/ShortInput.vue';
import LongInput from '@/components/Form/LongInput.vue';
import { computed, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import { useDialog } from '@/composables/dialog';
import { useLearningDraftContext } from '@/composables/learningDraftContext';
import type { LearningTheme } from '@/types/learning';
const PORTFOLIO_BEFORE_DISCUSSION_CONFIG_KEY = 'portfolio_before_discussion'
const props = defineProps<{
    selectedTopic?: LearningTheme | null
}>()
const { portfolio, basicItem } = useLearningDraftContext()
const route = useRoute()
const title = ref('')
const episodeContent = ref('')
const storyContent = ref('')
const titleRef = ref<any>(null)
const episodeRef = ref<any>(null)
const storyRef = ref<any>(null)
const reviewEl = ref<any>(null)
const { ping } = useDialog()
const { loading, saveItems } = basicItem
const isDraftEditable = computed(() => Number(portfolio.value?.status ?? 0) < 1)
const beforeDiscussionConfig = computed(() => {
    return props.selectedTopic?.ai_configs?.find(config => config.config_key === PORTFOLIO_BEFORE_DISCUSSION_CONFIG_KEY) ?? null
})
const hasBeforeDiscussionReview = computed(() => Boolean(beforeDiscussionConfig.value))
watch(portfolio, (record) => {
    title.value = record?.portfolio_title ?? ''
    episodeContent.value = record?.episode ?? ''
    storyContent.value = record?.content ?? ''
}, { immediate: true })
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
const finishReview = async(status: number) => {
    if(hasBeforeDiscussionReview.value && !reviewEl.value?.reviewResultRaw){
        ping('知識研修を完了する前、AI分析してください。')
        return
    }
    const valid = await reviewEl.value?.validate()
    if(hasBeforeDiscussionReview.value && !valid){
        return
    }
    await saveItems('summary', status, targetRefs.value, params.value)
}
</script>
