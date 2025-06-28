<template>
    <div style="height: 100%;width: 100%;position: relative;overflow: hidden;">
        <div v-if="selectedTopic && selectedTopic.active == 1 && route.name == 'basic'" :style="{height: route.name == 'basic'  ? '100%' : '0'}">
            <div style="height: 100%; overflow: hidden auto;">
                <div v-if="headerMaterials.length" style="background: var(--background-color);padding: 30px;word-wrap: break-word;white-space: break-spaces;line-height: 1.8;display: flex;flex-direction: column;gap: 30px;margin: 0 20px;">
                    <!-- <div style="position:absolute; right: 50px; display: flex; gap: 10px;" v-if="ttsStore.active && ttsStore.id == selectedTopic.id">
                        <div style="position: static" class="lesson-play" @click="stopPlay(selectedTopic.id)">{{ ttsStore.play ? '一時停止' : '再開する' }}</div>
                        <div style="position: static" class="lesson-play" @click="endPlay">ストップ</div>
                    </div>
                    <div class="lesson-play" v-else @click="convertToSpeech(getTextContent(getAllContent()), selectedTopic.id)">読み上げる</div>               -->
                    <div v-if="ttsStore.active && ttsStore.id == selectedTopic.id" class="absolute bg-[var(--bg3)] shadow-me rounded-md top-0 left-auto right-[25px] w-min" >
                        <div class="flex items-center gap-[5px]">
                            <div v-if="ttsStore.play" class="h-[35px] w-[35px] min-w-[35px] flex items-center justify-center cursor-pointer" @click="stopPlay(selectedTopic.id)">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 50 50" height="18" width="18" fill="var(--primary-color)">
                                    <rect height="40" width="10" y="5" x="10"></rect>
                                    <rect height="40" width="10" y="5" x="30"></rect>
                                </svg>
                            </div>
                            <div v-else class="h-[35px] w-[35px] min-w-[35px] flex items-center justify-center cursor-pointer" @click="stopPlay(selectedTopic.id)">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 50 50" height="18" width="18" fill="var(--primary-color)">
                                    <polygon points="10,5 40,25 10,45"></polygon>
                                </svg>
                            </div>
                            <div @click="endPlay" class="h-[35px] w-[35px] min-w-[35px] flex items-center justify-center cursor-pointer">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 50 50" height="18" width="18" fill="tomato">
                                    <rect height="30" width="30" y="10" x="10"></rect>
                                </svg>
                            </div>
                        </div>
                    </div>
                    <div class="lesson-play" v-else @click="convertToSpeech(getTextContent(getAllContent()), selectedTopic.id)">
                        <svg fill="#fff" class="m-auto" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 32.57 26.53">
                            <path class="cls-1" d="M12.49,1.31l-3.5.03h-.03c-.4,0-.78.2-1.01.56l-1.71,2.6c-.46.7-.92,1.39-1.37,2.09-1.12.02-2.69.06-3.56.07-.48.03-.79.28-.95.59-.11.13-.18.29-.2.5-.1,1.79-.14,3.59-.14,5.38.01,1.76.03,3.58.17,5.33,0,.01,0,.02,0,.03,0,.63.49,1.15,1.12,1.16,1.18.02,2.37.04,3.55.05.56.84,3.09,4.67,3.09,4.67.22.33.6.55,1.03.56.02,0,3.5.02,3.52.03.72,0,1.3-.58,1.29-1.29,0-1.76.03-3.51.03-5.27.01-4.9.02-10.95-.03-15.83,0-.71-.58-1.28-1.29-1.27ZM6.48,17.82c-.22-.31-.57-.51-.98-.5-.87,0-2.35-.05-3.09-.09-.25-.01-.44-.16-.45-.48-.05-1.31-.08-5.53-.02-7.13.02-.42.31-.64.7-.66l2.86-.03c.38,0,.76-.18.98-.52.86-1.22,2-2.86,2.96-4.24.13-.19.35-.3.58-.3.25,0,.51,0,.87,0,.16,0,.29.13.29.3,0,1.23-.02,2.46-.02,3.69-.01,4.36-.01,9.63.01,14.17,0,.19-.16.35-.35.35h-.78c-.25,0-.48-.11-.62-.32-1.02-1.46-2.94-4.23-2.95-4.25Z"/>
                            <path class="cls-1" d="M30.96,5.82c-.65-1.41-1.51-2.73-2.5-3.93-.6-.71-1.23-1.41-2.08-1.83-.53-.27-1.11.31-.84.84.34.76.9,1.47,1.36,2.15.74,1.15,1.37,2.41,1.82,3.69,1.78,5.13,1.38,11.03-1.41,15.73-.57.95-1.25,1.82-1.93,2.72-.23.29-.24.71,0,1.01.28.36.8.43,1.16.15.98-.76,1.79-1.7,2.53-2.7,3.81-5.02,4.64-12.13,1.9-17.83Z"/>
                            <path class="cls-1" d="M25.26,8.18c-.46-.95-1.07-1.82-1.76-2.61-.42-.48-.86-.94-1.49-1.14-.51-.18-1.02.34-.84.84.13.54.48,1.01.77,1.46l.26.46c.98,1.82,1.42,3.9,1.42,5.94-.02,2.03-.43,4.12-1.45,5.92-.35.62-.78,1.18-1.17,1.8-.48.75.42,1.64,1.17,1.14.69-.47,1.25-1.1,1.76-1.77.51-.67.96-1.38,1.34-2.15,1.54-3.05,1.52-6.85,0-9.9Z"/>
                            <path class="cls-1" d="M17.66,8.79c-.41,0-.76.3-.82.71-.03.15-.04.34-.02.46.02.16.11.32.17.45.58,1.39.56,2.92.32,4.38-.1.68-.47,1.14-.69,1.78-.24.7.51,1.38,1.19,1.06.4-.19.7-.5.99-.83.28-.33.55-.69.77-1.08,1.17-1.96.79-4.63-.79-6.25-.33-.32-.59-.7-1.12-.69Z"/>
                        </svg>                        
                    </div>
                    <div class="lessons-topic" v-for="topic in headerMaterials">
                        <p v-html="filteredContent(topic.content)"></p>
                        <HasQuestion v-if="topic.has_question" :material="topic"/>
                    </div>  
                </div>
                <div class="topic-container" style="margin: 25px 0;">
                    <div @click="(basicStatus || section.material_type !== 'ケーススタディ') ? router.push({name: 'material', params: { materialId: section?.id}}) : ''" :class="['topic-item' , {'inactive-theme' : !basicStatus && section.material_type === 'ケーススタディ'}]" style="flex-direction: row;align-items: center;justify-content: start;gap:5px;" v-for="section in filteredMaterials">
                        <div v-if="sectionStatus(section.id) == 2 || section?.answer?.status == 2" style="background-color: rgb(100, 188, 68); width: 18px; height: 18px; display: flex; border-radius: 50%; margin: auto 3px; min-width: 18px;">
                            <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 38 32" style="fill: rgb(255, 255, 255); margin-left: 4px;"><path data-v-3c7a9f1f="" d="M36.486 0.324c-0.666-0.515-1.629-0.396-2.204 0.22l-3.039 3.271-3.060 3.328c-2.031 2.23-4.067 4.452-6.086 6.689-2.025 2.234-8.487 9.367-9.743 10.772-0.132 0.15-0.369 0.129-0.486-0.025-1.060-1.399-2.287-3.028-3.468-4.519-1.161-1.465-2.516-3.22-3.271-4.144-0.755-0.927-1.702-2.093-2.191-2.668-0.528-0.625-1.457-0.791-2.182-0.329-0.765 0.489-0.973 1.521-0.518 2.307 0.367 0.636 2.307 3.801 2.307 3.801 0.801 1.27 3.213 5.039 3.699 5.791 0.487 0.751 1.194 1.782 1.879 2.788 0.684 1.004 1.52 2.313 2.429 3.264s2.487 0.627 3.321-0.358c1.932-2.282 9.588-11.527 11.498-13.857 1.916-2.327 3.815-4.668 5.719-7.004l2.842-3.517 2.823-3.535c0.548-0.687 0.451-1.716-0.272-2.276z"></path></svg>
                        </div>
                        <div v-if="sectionStatus(section.id) == 1 || section?.answer?.status < 0" style="background-color: rgb(255, 165, 0); width: 18px; height: 18px; display: flex; border-radius: 50%; margin: auto 3px; min-width: 18px;">
                            <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 38 32" style="fill: rgb(255, 255, 255); margin-left: 4px;"><path data-v-3c7a9f1f="" d="M36.486 0.324c-0.666-0.515-1.629-0.396-2.204 0.22l-3.039 3.271-3.060 3.328c-2.031 2.23-4.067 4.452-6.086 6.689-2.025 2.234-8.487 9.367-9.743 10.772-0.132 0.15-0.369 0.129-0.486-0.025-1.060-1.399-2.287-3.028-3.468-4.519-1.161-1.465-2.516-3.22-3.271-4.144-0.755-0.927-1.702-2.093-2.191-2.668-0.528-0.625-1.457-0.791-2.182-0.329-0.765 0.489-0.973 1.521-0.518 2.307 0.367 0.636 2.307 3.801 2.307 3.801 0.801 1.27 3.213 5.039 3.699 5.791 0.487 0.751 1.194 1.782 1.879 2.788 0.684 1.004 1.52 2.313 2.429 3.264s2.487 0.627 3.321-0.358c1.932-2.282 9.588-11.527 11.498-13.857 1.916-2.327 3.815-4.668 5.719-7.004l2.842-3.517 2.823-3.535c0.548-0.687 0.451-1.716-0.272-2.276z"></path></svg>
                        </div>
                        <div class="flex flex-col">
                            <div style="text-wrap: wrap;line-height: 1.5;">{{section.title}}</div>         
                            <div class="text-xs" v-if="section?.answer?.status == 2 && section?.answer?.updated_at">完了日:{{ dateFormat(section?.answer?.updated_at) }}</div>
                        </div>
                    </div>
                    <div v-if="selectedTopic.portfolio == 1" @click="sectionsCompleted && portfolioStatus < 1 ? router.push({name: 'story'}) : sectionsCompleted ? router.push({name: 'summary'}) : ''" :class="['topic-item', {'inactive-theme' : !sectionsCompleted}]" style="flex-direction: row;align-items: center;justify-content: start;gap:5px;">
                        <div v-if="portfolioStatus >= 1" style="background-color: rgb(100, 188, 68); width: 18px; height: 18px; display: flex; border-radius: 50%; margin: auto 3px; min-width: 18px;">
                            <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 38 32" style="fill: rgb(255, 255, 255); margin-left: 4px;"><path data-v-3c7a9f1f="" d="M36.486 0.324c-0.666-0.515-1.629-0.396-2.204 0.22l-3.039 3.271-3.060 3.328c-2.031 2.23-4.067 4.452-6.086 6.689-2.025 2.234-8.487 9.367-9.743 10.772-0.132 0.15-0.369 0.129-0.486-0.025-1.060-1.399-2.287-3.028-3.468-4.519-1.161-1.465-2.516-3.22-3.271-4.144-0.755-0.927-1.702-2.093-2.191-2.668-0.528-0.625-1.457-0.791-2.182-0.329-0.765 0.489-0.973 1.521-0.518 2.307 0.367 0.636 2.307 3.801 2.307 3.801 0.801 1.27 3.213 5.039 3.699 5.791 0.487 0.751 1.194 1.782 1.879 2.788 0.684 1.004 1.52 2.313 2.429 3.264s2.487 0.627 3.321-0.358c1.932-2.282 9.588-11.527 11.498-13.857 1.916-2.327 3.815-4.668 5.719-7.004l2.842-3.517 2.823-3.535c0.548-0.687 0.451-1.716-0.272-2.276z"></path></svg>
                        </div>
                        <div v-else-if="sectionsCompleted" style="background-color: rgb(255, 165, 0); width: 18px; height: 18px; display: flex; border-radius: 50%; margin: auto 3px; min-width: 18px;">
                            <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 38 32" style="fill: rgb(255, 255, 255); margin-left: 4px;"><path data-v-3c7a9f1f="" d="M36.486 0.324c-0.666-0.515-1.629-0.396-2.204 0.22l-3.039 3.271-3.060 3.328c-2.031 2.23-4.067 4.452-6.086 6.689-2.025 2.234-8.487 9.367-9.743 10.772-0.132 0.15-0.369 0.129-0.486-0.025-1.060-1.399-2.287-3.028-3.468-4.519-1.161-1.465-2.516-3.22-3.271-4.144-0.755-0.927-1.702-2.093-2.191-2.668-0.528-0.625-1.457-0.791-2.182-0.329-0.765 0.489-0.973 1.521-0.518 2.307 0.367 0.636 2.307 3.801 2.307 3.801 0.801 1.27 3.213 5.039 3.699 5.791 0.487 0.751 1.194 1.782 1.879 2.788 0.684 1.004 1.52 2.313 2.429 3.264s2.487 0.627 3.321-0.358c1.932-2.282 9.588-11.527 11.498-13.857 1.916-2.327 3.815-4.668 5.719-7.004l2.842-3.517 2.823-3.535c0.548-0.687 0.451-1.716-0.272-2.276z"></path></svg>
                        </div>
                        <div style="text-wrap: wrap;line-height: 1.5;">ポートフォリオ作成</div>
                    </div>
                    <div v-if="selectedTopic.custom_form_id" :class="['topic-item' , {'inactive-theme' : !basicStatus || !caseStatus || !allSectionUnderstand}]" @click="basicStatus && caseStatus && allSectionUnderstand ? router.push(`/survey/${selectedTopic.custom_form_id}`) : ''" style="flex-direction: row;align-items: center;justify-content: start;gap:5px;">
                        <div v-if="selectedTopic.survey_completed" style="background-color: rgb(100, 188, 68); width: 18px; height: 18px; display: flex; border-radius: 50%; margin: auto 3px; min-width: 18px;">
                            <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 38 32" style="fill: rgb(255, 255, 255); margin-left: 4px;"><path data-v-3c7a9f1f="" d="M36.486 0.324c-0.666-0.515-1.629-0.396-2.204 0.22l-3.039 3.271-3.060 3.328c-2.031 2.23-4.067 4.452-6.086 6.689-2.025 2.234-8.487 9.367-9.743 10.772-0.132 0.15-0.369 0.129-0.486-0.025-1.060-1.399-2.287-3.028-3.468-4.519-1.161-1.465-2.516-3.22-3.271-4.144-0.755-0.927-1.702-2.093-2.191-2.668-0.528-0.625-1.457-0.791-2.182-0.329-0.765 0.489-0.973 1.521-0.518 2.307 0.367 0.636 2.307 3.801 2.307 3.801 0.801 1.27 3.213 5.039 3.699 5.791 0.487 0.751 1.194 1.782 1.879 2.788 0.684 1.004 1.52 2.313 2.429 3.264s2.487 0.627 3.321-0.358c1.932-2.282 9.588-11.527 11.498-13.857 1.916-2.327 3.815-4.668 5.719-7.004l2.842-3.517 2.823-3.535c0.548-0.687 0.451-1.716-0.272-2.276z"></path></svg>
                        </div>
                        <div class="flex flex-col">
                            <div style="text-wrap: wrap;line-height: 1.5;">チェックリスト</div>
                            <div class="text-xs" v-if="selectedTopic.survey_date">完了日:{{ dateFormat(selectedTopic.survey_date) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
      
        <router-view v-slot="{ Component }">
            <transition name="modalFade">
                <component :is="Component" 
                    :selectedTopic="selectedTopic"
                    :filteredMaterials="filteredMaterials"
                    :sections_status="sections_status"
                />
            </transition>
        </router-view>
    
    </div>  
</template>
<script setup>
import { useTtsStore } from '@/store/ttsStore';
import { convertToSpeech, endPlay, stopPlay } from '@/utils/tts';
import { computed, ref, inject, provide, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import HasQuestion from './HasQuestion.vue';
import { DateTime } from 'luxon';
import { useApi } from '@/composables/api';
    const router = useRouter()
    const route = useRoute()
    const ttsStore = useTtsStore()
    const props = defineProps(['selectedTopic', 'materials', 'sections_status', 'filteredMaterials', 'sectionsCompleted'])
    const loading = ref([false, false])
    const lesson = inject('getLessonPortfolios')
    const api = useApi()
    onMounted(() => {
        setTimeout(() => {
            console.log(props.filteredMaterials)

        }, 500)
    })
    const headerMaterials = computed(() => {
        return props.materials && props.materials.length ? props.materials.filter(ob => ob.priority == 0) : [] 
    })
    
    const portfolioStatus = computed(() => {
        return props.selectedTopic && props.selectedTopic.lesson_portfolio ? props.selectedTopic.lesson_portfolio.status >= 1 : false
    })

    const sectionStatus = (id) => {
        const record = props.sections_status.find(ob => ob.material_id == id)
        if(record){
            return record.status
        }
        return null
    }    
    const filteredContent = (value) => {
        
        return value.replace(/\[\[learning_video src="(.*?)" learning_video\]\]/g, (match, videoSrc) => {
            return `<video class="ls-video" controls="controls"><source src="${videoSrc}"></video>`;
        });
    }
    const getAllContent = () => {
        let contents = ''
        headerMaterials.value.forEach(element => {
            contents += element.content
        });
        return contents
    }
    const getTextContent = (html) => {
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = html;
        return tempDiv.textContent || tempDiv.innerText;
    }
    const saveItems = async(route_name, status, refs, params) => {
        let result = true
        for(const ref of refs){
            const val = await ref?.validate() || {valid: false}
            result = result * val.valid
        }
        if(!result) return
        loading.value[status] = true
        
    
        await api.post('/save_lesson_portfolio', params, {
            toast: '保存しました。'
        })
        loading.value[status] = false
        if(status == 1){
            router.push({name: route_name})
        }
        lesson()

    }
    const viewPortfolios = async() => {
        const url = `/learning/${props.selectedTopic.id}/portfolioview`
        window.open(url, '_blank').focus();
    } 
    provide('basicItem', {
        saveItems: (route, status, refs, params) => saveItems(route, status, refs, params),
        viewPortfolios: () => viewPortfolios(),
        loading: loading
    })
    const basicStatus = computed(() => {
        const filtered = props.materials.filter(ob => ob.material_type == '基礎知識' && ob.priority == 1 && ob.has_understand == 0)
        if (filtered.length) {
            return filtered.every(ob => ob.answer?.status == 2)
        }
        return true
    })
    const caseStatus = computed(() => {
        const filtered = props.materials.filter(ob => ob.material_type == 'ケーススタディ')
        if (filtered.length) {
            return filtered.every(ob => ob.answer?.status == 2)
        }
        return true
    })

    const allSectionUnderstand = computed(() => {
        const sectionsHasUnderstand = props.materials.filter(ob => ob.has_understand == 1 && ob.priority == 1)
        if(!sectionsHasUnderstand.length) return true
        const sectionAnswerData = props.sections_status
        const completedAnswerData = sectionAnswerData.filter(ob => ob.status == 2)
        return sectionsHasUnderstand.length == completedAnswerData.length
    })
    const dateFormat = (date) => {
        return DateTime.fromISO(date).toISODate()
    }
</script>