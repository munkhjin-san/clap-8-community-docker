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
            @saved="saveRedirect"
        />
    </router-view>
    <div class="h-full w-full flex items-center justify-center">
        <div v-if="!loading && !survey" class="text-[gray]">アンケートが見つかりません</div>
    </div>
</div>
</template>
<script setup lang="ts">
import { CustomForm } from '@/interface/customFormInterface';
import axios from 'axios';
import { onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';

const route = useRoute()
const router = useRouter()
onMounted(() => {
    if(route.params?.surveyId){
        getSurvey()
    }
})
const survey = ref<CustomForm | null>(null)
const loading = ref(true)
const getSurvey = async() => {
    try {
        loading.value = true
        survey.value = await axios.get('/get_survey', {params: {id: route.params?.surveyId}}).then( res => res.data)
        setTimeout(() => {
            loading.value = false
        }, 250);
    } catch (error) {
        loading.value = false
    }    
}
const saveRedirect = () => {
    router.back()
    getSurvey()
}
</script>