<template>
    <DraftLayout>
        <template #main>
            <div v-html="selectedTopic?.episode_guidance"></div>
            <div class="si-box" style="margin:45px 0">
                <LoaderButton :loading="false" content="ポートフォリオ作成例を確認する" @triggered="viewPortfolios"/>
            </div>
            <div class="si-box">
                <LongInput
                    v-if="isDraftEditable" 
                    :placeHolder="`ポートフォリオエピソード`"
                    ref="episodeRef"
                    rules="required"
                    name="episode"
                    v-model="episodeContent"
                    :initialValue="portfolio?.episode ?? episodeContent" 
                    :key="`${portfolio?.episode ?? 0}_${route.fullPath}_${portfolio?.updated_at}`"
                />
                <p v-else><strong>ディスカッション用ポートフォリオエピソード<br></strong>{{ portfolio?.episode }}</p>
            </div>
            <div v-if="isDraftEditable" style="display:flex; justify-content: center; gap:20px;flex-wrap: wrap;margin-top: 25px;">              
                <LoaderButton style="margin: 0" @triggered="saveItems('title', 0, [episodeRef], params)" :loading="loading[0]" :content="'一時保存'"/>
                <LoaderButton style="margin: 0" @triggered="saveItems('title', 1, [episodeRef], params)" :loading="loading[1]" :content="'次へ'"/>               
            </div>
        </template>
    </DraftLayout>
</template>
<script setup lang="ts">
import LongInput from '@/components/Form/LongInput.vue';
import LoaderButton from '@/components/Global/LoaderButton.vue';
import DraftLayout from '../DraftLayout.vue'
import { computed, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import { useLearningDraftContext } from '@/composables/learningDraftContext';
import type { LearningTheme } from '@/types/learning';
const route = useRoute()
defineProps<{
    selectedTopic?: LearningTheme | null
}>()
const { portfolio, basicItem } = useLearningDraftContext()
const { loading, saveItems, viewPortfolios } = basicItem
const episodeRef = ref<any>(null)
const episodeContent = ref('')
const isDraftEditable = computed(() => Number(portfolio.value?.status ?? 0) < 1)
watch(portfolio, (record) => {
    episodeContent.value = record?.episode ?? ''
}, { immediate: true })
const params = computed(() => {
    return {
            params: { 
                episode: episodeContent.value,
            },
            theme_id: route.params.lessonThemeId,
        }
})
</script>
