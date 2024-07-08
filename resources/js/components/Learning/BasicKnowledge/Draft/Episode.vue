<template>
    <DraftLayout>
        <template #main>
            <div v-html="selectedTopic?.episode_guidance"></div>
            <div class="si-box" style="margin:45px 0">
                <LoaderButton :loading="false" content="ポートフォリオ作成例を確認する" @triggered="viewPortfolios"/>
            </div>
            <div class="si-box">
                <LongInput
                    v-if="portfolio?.status < 1" 
                    :placeHolder="`ポートフォリオエピソード`"
                    ref="episodeRef"
                    rules="required"
                    name="episode"
                    v-model="episodeContent"
                    :initialValue="portfolio ? portfolio.episode : episodeContent" 
                    :key="`${portfolio ? portfolio.episode : 0}_${route.fullPath}_${portfolio?.updated_at}`"
                />
                <p v-else><strong>ディスカッション用ポートフォリオエピソード<br></strong>{{ portfolio?.episode }}</p>
            </div>
            <div v-if="portfolio?.status < 1" style="display:flex; justify-content: center; gap:20px;flex-wrap: wrap;margin-top: 25px;">              
                <LoaderButton style="margin: 0" @triggered="saveItems('title', 0, [episodeRef], params)" :loading="loading[0]" :content="'一時保存'"/>
                <LoaderButton style="margin: 0" @triggered="saveItems('title', 1, [episodeRef], params)" :loading="loading[1]" :content="'次へ'"/>               
            </div>
        </template>
    </DraftLayout>
</template>
<script setup>
import LongInput from '@/components/Form/LongInput.vue';
import LoaderButton from '@/components/Global/LoaderButton.vue';
import DraftLayout from '../DraftLayout.vue'
import { computed, inject, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
const router = useRouter()
const route = useRoute()
const props = defineProps(['selectedTopic'])
const portfolio = inject('portfolio')
const { loading, saveItems, viewPortfolios } = inject('basicItem')
const episodeRef = ref(null)
const episodeContent = ref('')
const params = computed(() => {
    return {
            params: { 
                episode: episodeContent.value,
            },
            theme_id: route.params.lessonThemeId,
        }
})
</script>