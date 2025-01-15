<template>
    <div style="height: 100%;width: 100%;position: relative;overflow: hidden;">
        <div v-if="selectedTopic && selectedTopic.active == 1 && route.name == 'basic'" :style="{height: route.name == 'basic'  ? '100%' : '0'}">
            <div style="height: 100%; overflow: hidden auto;">
                <div v-if="headerMaterials.length" style="background: var(--background-color);padding: 30px;word-wrap: break-word;white-space: break-spaces;line-height: 1.8;display: flex;flex-direction: column;gap: 30px;margin: 0 20px;">
                    <div style="position:absolute; right: 50px; display: flex; gap: 10px;" v-if="ttsStore.active && ttsStore.id == selectedTopic.id">
                        <div style="position: static" class="lesson-play" @click="stopPlay(selectedTopic.id)">{{ ttsStore.play ? '一時停止' : '再開する' }}</div>
                        <div style="position: static" class="lesson-play" @click="endPlay">ストップ</div>
                    </div>
                    <div class="lesson-play" v-else @click="convertToSpeech(getTextContent(getAllContent()), selectedTopic.id)">読み上げる</div>              
                    <div class="lessons-topic" v-for="topic in headerMaterials">
                        <p v-html="filteredContent(topic.content)"></p>
                        <HasQuestion v-if="topic.has_question" :material="topic"/>
                    </div>  
                </div>
                <div class="topic-container" style="margin: 25px 0;">
                    <div @click="router.push({name: 'material', params: { materialId: section?.id}})" class="topic-item" style="flex-direction: row;align-items: center;justify-content: start;gap:5px;" v-for="section in filteredMaterials">
                        <div v-if="sectionStatus(section.id) == 2 || section?.answer?.status == 2" style="background-color: rgb(100, 188, 68); width: 18px; height: 18px; display: flex; border-radius: 50%; margin: auto 3px; min-width: 18px;">
                            <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 38 32" style="fill: rgb(255, 255, 255); margin-left: 4px;"><path data-v-3c7a9f1f="" d="M36.486 0.324c-0.666-0.515-1.629-0.396-2.204 0.22l-3.039 3.271-3.060 3.328c-2.031 2.23-4.067 4.452-6.086 6.689-2.025 2.234-8.487 9.367-9.743 10.772-0.132 0.15-0.369 0.129-0.486-0.025-1.060-1.399-2.287-3.028-3.468-4.519-1.161-1.465-2.516-3.22-3.271-4.144-0.755-0.927-1.702-2.093-2.191-2.668-0.528-0.625-1.457-0.791-2.182-0.329-0.765 0.489-0.973 1.521-0.518 2.307 0.367 0.636 2.307 3.801 2.307 3.801 0.801 1.27 3.213 5.039 3.699 5.791 0.487 0.751 1.194 1.782 1.879 2.788 0.684 1.004 1.52 2.313 2.429 3.264s2.487 0.627 3.321-0.358c1.932-2.282 9.588-11.527 11.498-13.857 1.916-2.327 3.815-4.668 5.719-7.004l2.842-3.517 2.823-3.535c0.548-0.687 0.451-1.716-0.272-2.276z"></path></svg>
                        </div>
                        <div v-if="sectionStatus(section.id) == 1" style="background-color: rgb(255, 165, 0); width: 18px; height: 18px; display: flex; border-radius: 50%; margin: auto 3px; min-width: 18px;">
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
                    <div v-if="selectedTopic.custom_form_id" :class="['topic-item' , {'inactive-theme' : !basicStatus || !caseStatus}]" @click="basicStatus && caseStatus ? router.push(`/survey/${selectedTopic.custom_form_id}`) : ''" style="flex-direction: row;align-items: center;justify-content: start;gap:5px;">
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
    import { computed, ref, inject, provide, useTemplateRef } from 'vue';
    import { useRoute, useRouter } from 'vue-router';
    import axios from 'axios';
    import HasQuestion from './HasQuestion.vue';
    import { DateTime } from 'luxon';
    const router = useRouter()
    const route = useRoute()
    const ttsStore = useTtsStore()
    const props = defineProps(['selectedTopic', 'materials', 'sections_status', 'filteredMaterials', 'sectionsCompleted'])
    const loading = ref([false, false])
    const { info, notify, confirm } = inject('dialog')
    const lesson = inject('getLessonPortfolios')
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
        
        try{
            await axios.post('/save_lesson_portfolio', params)
            
            info('保存しました。')
            loading.value[status] = false
            if(status == 1){
                router.push({name: route_name})
            }
            lesson()
        }catch (e){
            notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
        }
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
        const filtered = props.materials.filter(ob => ob.material_type == '基礎知識')
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
    const dateFormat = (date) => {
        return DateTime.fromISO(date).toISODate()
    }
</script>