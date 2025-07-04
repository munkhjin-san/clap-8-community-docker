<template>
<div class="w-full h-full overflow-hidden">
    <div class="h-full w-full flex items-center justify-center" v-if="loading">
        <div id="loaderMini">
            <div class="spinner-mini" style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
        </div>
    </div>
    <router-view v-slot="{ Component }">
        <component :is="Component" 
            v-if="survey && !loading"
            :survey="survey"
            :key="key"
            mode="all"
            @saved="saveRedirect"
        />
    </router-view>
    <div class="h-full w-full flex items-center justify-center">
        <div v-if="!loading && !survey" class="text-[gray]">アンケートが見つかりません</div>
    </div>
</div>
</template>
<script setup lang="ts">
import { useApi } from '@/composables/api';
import { CustomForm } from '@/interface/customFormInterface';
import { onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';

const route = useRoute()
const router = useRouter()
const key = ref(0)
onMounted(() => {
    if(route.params?.surveyId){
        getSurvey()
    }
})
const survey = ref<CustomForm | null>(null)
const loading = ref(true)
const api = useApi()
const getSurvey = async() => {

    survey.value = await api.get('/get_survey', {id: route.params?.surveyId}, {
        loadingRef: loading,
    })

}
const saveRedirect = async(status:number, id:number | null) => {
    // router.back()
    await getSurvey()
    setTimeout(() => {
        if(status == 1){
            router.push({
                name: 'survey-form',
                params: {
                    surveyId: route.params?.surveyId
                },
                query: {
                    answerId: id
                }
            })
        }else if(status == 2){
            router.push({
                name: 'completed-survey',
                query: {
                    answerId: id,
                    surveyId: route.params?.surveyId
                }
            })
        }
        key.value += 1
    }, 300);
    

}
</script>