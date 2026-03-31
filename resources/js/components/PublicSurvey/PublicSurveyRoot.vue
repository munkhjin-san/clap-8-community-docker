<template>
    <div class="w-full min-h-full overflow-y-auto bg-[var(--bg3)] text-[var(--primary-color)]">
        <div class="max-w-[920px] mx-auto px-[20px] py-[40px]">
            <div v-if="loading" class="min-h-[280px] flex items-center justify-center">
                <div id="loaderMini">
                    <div class="spinner-mini" style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
                </div>
            </div>
            <div v-else-if="submitted" class="bg-[var(--background-color)] p-[30px] leading-normal">
                <div class="text-[20px] mb-[10px]">回答を受け付けました</div>
                <div class="text-[14px] text-[gray]">送信ありがとうございます。</div>
            </div>
            <div v-else-if="errorMessage" class="bg-[var(--background-color)] p-[30px] leading-normal">
                <div class="text-[18px] mb-[10px]">フォームを表示できません</div>
                <div class="text-[14px] text-[gray]">{{ errorMessage }}</div>
            </div>
            <SurveyForm
                v-else-if="survey"
                :survey="survey"
                mode="all"
                :guest-mode="true"
                :submit-url="submitUrl"
                @saved="handleSaved"
            />
        </div>
    </div>
</template>

<script setup lang="ts">
import axios from 'axios'
import { computed, onMounted, ref } from 'vue'
import SurveyForm from '@/components/Survey/SurveyForm.vue'
import type { CustomForm } from '@/interface/customFormInterface'

const props = defineProps<{
    publicToken: string
}>()

const survey = ref<CustomForm | null>(null)
const loading = ref(true)
const submitted = ref(false)
const errorMessage = ref('')

const submitUrl = computed(() => `/public-surveys/${props.publicToken}/answers`)

const loadSurvey = async () => {
    loading.value = true
    errorMessage.value = ''

    try {
        const response = await axios.get(`/public-surveys/${props.publicToken}/data`)
        survey.value = response.data as CustomForm
    } catch (error: any) {
        errorMessage.value =
            error?.response?.data?.message
            || 'このフォームは公開されていないか、利用できません。'
    } finally {
        loading.value = false
    }
}

const handleSaved = () => {
    submitted.value = true
    window.scrollTo({ top: 0, behavior: 'smooth' })
}

onMounted(() => {
    loadSurvey()
})
</script>
