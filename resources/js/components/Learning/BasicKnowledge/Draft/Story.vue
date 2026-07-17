<template>
    <DraftLayout>
        <template #main>
            <div>
                <div>
                    <div v-if="selectedTopic && selectedTopic.guidance" v-html="selectedTopic?.guidance"></div>
                </div>
                
                <div style="margin-top: 30px;" class="section-contents" >
                    <p style="margin-bottom: 10px;"><strong>重要だと理解した点</strong></p>
                    <div v-for="section in portfolio?.lesson_sections">
                        <p>{{ section?.lesson_material?.title }}</p>
                        <p>{{ section?.content }}</p>
                    </div>
                    
                </div>
            </div>
            <div class="si-box" style="margin:45px 0">
                <LoaderButton :loading="false" content="ポートフォリオ作成例を確認する" @triggered="viewPortfolios"/>
            </div>
            <div class="si-box">
                <LongInput
                    v-if="isDraftEditable"
                    :placeHolder="`ポートフォリオ内容`"
                    rules="required" 
                    ref="storyRef"
                    name="story"
                    v-model="storyContent"
                    :initialValue="portfolio?.content ?? storyContent"   
                    :key="`${portfolio?.content ?? 0}_${route.fullPath}_${portfolio?.updated_at}`"
                />
                <p v-else><strong>ディスカッション用ポートフォリオ内容<br></strong>{{ portfolio?.content }}</p>
            </div>
            <div v-if="isDraftEditable" style="display:flex; justify-content: center; gap:20px;flex-wrap: wrap;margin-top: 25px;">              
                <LoaderButton style="margin: 0" @triggered="saveItems('episode', 0, [storyRef], params)" :loading="loading[0]" :content="'一時保存'"/>
                <LoaderButton style="margin: 0" @triggered="saveItems('episode', 1, [storyRef], params)" :loading="loading[1]" :content="'次へ'"/>               
            </div>
        </template>
    </DraftLayout>
</template>
<script setup lang="ts">
import {useRoute} from 'vue-router'
import LoaderButton from '@/components/Global/LoaderButton.vue';
import LongInput from '@/components/Form/LongInput.vue';
import DraftLayout from '../DraftLayout.vue';
import { ref, computed, watch } from 'vue';
import { useLearningDraftContext } from '@/composables/learningDraftContext';
import type { LearningTheme } from '@/types/learning';
defineProps<{
    selectedTopic?: LearningTheme | null
}>()
const route = useRoute()
const { portfolio, basicItem } = useLearningDraftContext()
const { loading, saveItems, viewPortfolios } = basicItem
const storyContent = ref('')
const storyRef = ref<any>(null)
const isDraftEditable = computed(() => Number(portfolio.value?.status ?? 0) < 1)
watch(portfolio, (record) => {
    storyContent.value = record?.content ?? ''
}, { immediate: true })
const params = computed(() => {
    return {
            params: { 
                content: storyContent.value,
            },
            theme_id: route.params.lessonThemeId,
        }
})
</script>
