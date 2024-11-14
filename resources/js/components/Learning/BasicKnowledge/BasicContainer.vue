<template>
    <div style="height: 100%;width: 100%;position: relative;overflow: hidden;">
        <div v-if="selectedTopic && selectedTopic.active == 1 && route.name == 'basic'" :style="{height: route.name == 'basic'  ? '100%' : '0'}">
            <div style="height: 100%; overflow: hidden auto;">
                <div style="background: var(--background-color);padding: 30px;word-wrap: break-word;white-space: break-spaces;line-height: 1.8;display: flex;flex-direction: column;gap: 30px;margin: 0 20px;">
                    <div class="lesson-play" v-if="ttsStore.active && ttsStore.id == selectedTopic.id" @click="stopPlay">ストップ</div>
                    <div class="lesson-play" v-else @click="convertToSpeech(getTextContent(getAllContent()), selectedTopic.id)">読み上げ</div>              
                    <div class="lessons-topic" v-for="topic in headerMaterials">
                        <p v-html="filteredContent(topic.content)"></p>
                        <div v-if="topic.has_question">
                            <div class="post-separetor"></div>
                            <LongInput
                                v-if="topic?.answer?.status < 2" 
                                :initialValue="topic?.answer?.answer ? topic?.answer?.answer : answer" 
                                :placeHolder="`質問に関する答え`"
                                ref="answerComment"
                                rules="required|max:2000"
                                name="recordBody"
                                label="answer"
                                style="position: relative;"
                                v-model="answer"
                            />
                            <p v-else><strong>質問に関する回答内容<br></strong>{{ topic?.answer?.answer }}</p>
                            <OpenAiReview 
                                :assistand-id="selectedTopic.assistant_id" 
                                :soure-text="topic?.answer?.ai_review" 
                                :message="answer"
                                :confirm-text="'業務リスク管理の基礎を効果的に理解し、実務で活用できる視点を身につけている。'"
                                :answer="true"
                                ref="reviewEl"
                            />
                            <div v-if="topic?.answer?.status < 2" style="display:flex; justify-content: center; gap:20px;flex-wrap: wrap;margin-top: 25px;">
                                <div>
                                    <LoaderButton @triggered="finish(1, topic)" :loading="processing[1]" :content="'一時保存'"/>
                                </div>
                                <div>
                                    <LoaderButton @triggered="finish(2, topic)" :loading="processing[2]" :content="'完了'"/>
                                </div>
                            </div>
                        </div>
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
                        <div style="text-wrap: wrap;line-height: 1.5;">{{section.title}}</div>         
                        
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
                </div>
            </div>
        </div>
        
      
        <router-view v-slot="{ Component }">
            <transition name="modalFade">
                <component :is="Component" 
                    :selectedTopic="selectedTopic"
                    :filteredMaterials="filteredMaterials"
                    :sections_status="sections_status"
                    @finish="finish"
                />
            </transition>
        </router-view>
    
    </div>  
</template>
<script setup>
    import { useTtsStore } from '@/store/ttsStore';
    import { convertToSpeech, stopPlay } from '@/utils/tts';
    import { computed, ref, inject, provide, useTemplateRef } from 'vue';
    import { useRoute, useRouter } from 'vue-router';
    import LongInput from '@/components/Form/LongInput.vue';
    import OpenAiReview from '@/components/Global/OpenAiReview.vue';
    import LoaderButton from '@/components/Global/LoaderButton.vue';
    import axios from 'axios';
    const router = useRouter()
    const route = useRoute()
    const ttsStore = useTtsStore()
    const props = defineProps(['selectedTopic', 'materials', 'sections_status', 'filteredMaterials', 'sectionsCompleted'])
    const loading = ref([false, false])
    const { info, notify, confirm } = inject('dialog')
    const answer = ref('')
    const lesson = inject('getLessonPortfolios')
    const processing = ref(['', false, false])
    const reviewEl = useTemplateRef('reviewEl')
    const answerComment = useTemplateRef('answerComment')
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
    
    const finish = async(status, material) => {
        if (status === 2) {
            if(props.selectedTopic.assistant_id && !reviewEl.value?.[0]?.reviewResultRaw){
                notify('基礎知識研修を完了する前、AI分析してください。')
                return
            }
            
        }
        let aiVal = true
        for(const target of reviewEl.value) {
            aiVal = await target?.validate()
        }
        let result = true 
        for(const target of answerComment.value) {
            const val = await target?.validate() || {valid: false}
            result = result * val.valid
        }
        if((props.selectedTopic.assistant_id && !aiVal) || !result){
            return
        }
        
        processing.value[status] = true
        const materialId = material?.id
        const answerId = material?.answer?.id
        const params = {     
            id: answerId,           
            params: {
                status: status,
                answer: answer.value,
                ai_review: reviewEl.value?.reviewResultRaw,
                material_id: materialId
            }
        }
        let decision
        if (status == 2) {
            decision = checkList()
        }
        try {
            axios.post('/update_lesson_answer', params)
            info('保存しました。')
            processing.value[status] = false
            if (decision) {
                window.open(
                'https://docs.google.com/forms/d/1ptWpZTLQiXzgKKnaDAUN8mEzFPwQk7Zveo_ZZZ-a_Tk/edit',
                '_blank'
                );
            }
            
        } catch (e) {
            notify(e)
        } finally {
            router.push({name : 'top'})
        }
    }
    const checkList = async() => {
        const options = {
            answers: [{ label: 'OK', value: true }]
        };
        const answer = await confirm("最後に業務リスク研修チェックリストの実施をお願い致します。", options);
        return answer
    }
</script>