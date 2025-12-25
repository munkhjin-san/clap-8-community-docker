<template>
    <div style="height: 100%;width: 100%;overflow: hidden">     
        <Transition name="modalFade">
            <Explain @close="moreDetail = false" v-if="moreDetail"/>
        </Transition>
        <div style="display: flex;align-items: center;height: 50px;position: sticky;top: 0;background: var(--bg2);z-index: 3;">
            <div style="height: 50px;width: 50px;min-width:50px;cursor: pointer;display: flex;justify-content: center;align-items: center;fill:var(--primary-color)" @click="goBack()">
                <Back />
            </div>
            <div class="lesson-nav-bar">
                <div v-for="(path, index) in pathGenerator">
                    <span class="lesson-breadcumb" @click="goByName(path.route)" style="margin-right: 5px;">{{ path.label }}</span>
                    <span v-if="index + 1 !== pathGenerator.length">／</span>
                </div>
            </div>   
        </div>
        <div style="height: calc(100% - 50px);">    
            <div v-if="noData" style="line-height: 1.8;height:100%;display: flex;justify-content: center;align-items: center;">
                <p>現在データはありません。</p>
            </div>
            <div v-else-if="route.name == 'top' && selectedTopic && selectedTopic.active == 1 && selectedTopic.portfolio == 1" style="height: 100%;overflow: hidden auto;">
                <div style="padding:20px;
                    white-space: break-spaces;
                    font-size: 14px;
                    line-height: 1.5;
                    color: var(--primary-color);
                    background: var(--background-color);
                    margin: 0 20px 20px;"
                >
                    <div style="margin-bottom: 20px;" v-if="[610, 608,799,800,829].includes(auth.activeUser.id)">
                        <router-link :to="{name: 'evaluate'}">職能研修機関確認用</router-link>
                    </div>
                    
                    <p style="font-size: 18px;margin-bottom: 15px;">研修プログラムについて<br></p>
                    <p style="line-height: 2.5;margin-bottom: 25px;">この研修プログラムは、社会活動で求められる9つの職能を学ぶためのものです。<br>まず、GLOWDのラーニングアプリを使用して基礎学習を行い、内容を理解します。<br>理解が困難な場合は、補足資料の確認やフォローアップ面談を通じてサポートを受けることができます。<br>次に、基礎学習を理解した参加者は、グループディスカッションのためのポートフォリオを作成します。<br>このディスカッションでは、ポートフォリオを発表し、ポジティブフィードバックとネガティブフィードバックを受けて、ポートフォリオを完成させます。<br>完成したポートフォリオは、各参加者のマイページのプロフィール欄に自動的に掲載されます。<br>基礎学習を完了し、ポートフォリオが完成した参加者は、1つのテーマの履修が完了したとみなされます。</p>
                    <p style="font-size: 18px;margin: 15px 0;">ポートフォリオとは<br></p>
                    <p style="line-height: 2.5;">ポートフォリオは、自分の学んだことや経験をまとめた記録です。<br>これには、研修で学んだ内容、過去に取り組んだプロジェクトやその成果、自分の強みや特性、自分の意見や考え方などを含めます。<br>ポートフォリオを作ることで、自分がどう成長したか、どのように考えているかを他の人に示すことができます。<br>また、フィードバックを受け入れて改善することで、さらに自分自身を深く理解し、発展させることができます。</p>
                    <!-- <a @click="moreDetail = true" style="cursor: pointer;">もっと詳しく知りたい</a> -->
                    <div class="video-grid">
                        <div class="video-item">
                            <p><strong>研修プログラムの説明</strong></p>
                            <video controls="controls" style="max-width: 100%;margin-top: 15px;">
                                <source src="/cdn/lesson_files/program-explaination.mp4">
                            </video>
                        </div>
                        <div class="video-item">
                            <p><strong>グループディスカッションの説明</strong></p>
                            <video controls="controls" style="max-width: 100%;margin-top: 15px;">
                                <source src="/cdn/lesson_files/discussion-explaination.mp4">
                            </video>
                        </div>
                        <div class="video-item">
                            <p><strong>ポートフォリオの説明</strong></p>
                            <video controls="controls" style="max-width: 100%;margin-top: 15px;">
                                <source src="/cdn/lesson_files/portfolio-explaination.mp4">
                            </video>
                        </div>
                        <div class="video-item"></div>
                        
                    </div>

                </div>
                <TransitionGroup name="t-list" class="topic-container" tag="div" style="padding-bottom: 20px;">
        
                <div v-for="topic in subtopics"
                    :key=topic.val :class="['topic-item' , {'inactive-theme' : status < topic.val}]" @click="select(topic)">
                    <div class="flex gap-2.5 flex-col">
                        <div class="flex align-center" style="gap:5px;">
                            <div v-if="status > topic.val" style="background-color: rgb(100, 188, 68); width: 18px; height: 18px; display: flex; border-radius: 50%; margin: auto 3px; min-width: 18px;">
                                <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 38 32" style="fill: rgb(255, 255, 255); margin-left: 4px;"><path data-v-3c7a9f1f="" d="M36.486 0.324c-0.666-0.515-1.629-0.396-2.204 0.22l-3.039 3.271-3.060 3.328c-2.031 2.23-4.067 4.452-6.086 6.689-2.025 2.234-8.487 9.367-9.743 10.772-0.132 0.15-0.369 0.129-0.486-0.025-1.060-1.399-2.287-3.028-3.468-4.519-1.161-1.465-2.516-3.22-3.271-4.144-0.755-0.927-1.702-2.093-2.191-2.668-0.528-0.625-1.457-0.791-2.182-0.329-0.765 0.489-0.973 1.521-0.518 2.307 0.367 0.636 2.307 3.801 2.307 3.801 0.801 1.27 3.213 5.039 3.699 5.791 0.487 0.751 1.194 1.782 1.879 2.788 0.684 1.004 1.52 2.313 2.429 3.264s2.487 0.627 3.321-0.358c1.932-2.282 9.588-11.527 11.498-13.857 1.916-2.327 3.815-4.668 5.719-7.004l2.842-3.517 2.823-3.535c0.548-0.687 0.451-1.716-0.272-2.276z"></path></svg>
                            </div>
                            <div class="topic-title">{{ topic.title }}</div>
                        </div>
                        
                        <div v-if="topic.val == 0 && sections_status.length" class="flex flex-col gap-2.5" style="margin-top: 10px;">
                            <div style="gap:5px;" class="flex align-center" v-for="section in sections_status" :key="section.id">
                                <div v-if="section.status == 2" style="background-color: rgb(100, 188, 68); width: 18px; height: 18px; display: flex; border-radius: 50%; margin: auto 3px; min-width: 18px;">
                                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 38 32" style="fill: rgb(255, 255, 255); margin-left: 4px;"><path data-v-3c7a9f1f="" d="M36.486 0.324c-0.666-0.515-1.629-0.396-2.204 0.22l-3.039 3.271-3.060 3.328c-2.031 2.23-4.067 4.452-6.086 6.689-2.025 2.234-8.487 9.367-9.743 10.772-0.132 0.15-0.369 0.129-0.486-0.025-1.060-1.399-2.287-3.028-3.468-4.519-1.161-1.465-2.516-3.22-3.271-4.144-0.755-0.927-1.702-2.093-2.191-2.668-0.528-0.625-1.457-0.791-2.182-0.329-0.765 0.489-0.973 1.521-0.518 2.307 0.367 0.636 2.307 3.801 2.307 3.801 0.801 1.27 3.213 5.039 3.699 5.791 0.487 0.751 1.194 1.782 1.879 2.788 0.684 1.004 1.52 2.313 2.429 3.264s2.487 0.627 3.321-0.358c1.932-2.282 9.588-11.527 11.498-13.857 1.916-2.327 3.815-4.668 5.719-7.004l2.842-3.517 2.823-3.535c0.548-0.687 0.451-1.716-0.272-2.276z"></path></svg>
                                </div>
                                <div v-else-if="section.status == 1" style="background-color: rgb(255, 165, 0); width: 18px; height: 18px; display: flex; border-radius: 50%; margin: auto 3px; min-width: 18px;">
                                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 38 32" style="fill: rgb(255, 255, 255); margin-left: 4px;"><path data-v-3c7a9f1f="" d="M36.486 0.324c-0.666-0.515-1.629-0.396-2.204 0.22l-3.039 3.271-3.060 3.328c-2.031 2.23-4.067 4.452-6.086 6.689-2.025 2.234-8.487 9.367-9.743 10.772-0.132 0.15-0.369 0.129-0.486-0.025-1.060-1.399-2.287-3.028-3.468-4.519-1.161-1.465-2.516-3.22-3.271-4.144-0.755-0.927-1.702-2.093-2.191-2.668-0.528-0.625-1.457-0.791-2.182-0.329-0.765 0.489-0.973 1.521-0.518 2.307 0.367 0.636 2.307 3.801 2.307 3.801 0.801 1.27 3.213 5.039 3.699 5.791 0.487 0.751 1.194 1.782 1.879 2.788 0.684 1.004 1.52 2.313 2.429 3.264s2.487 0.627 3.321-0.358c1.932-2.282 9.588-11.527 11.498-13.857 1.916-2.327 3.815-4.668 5.719-7.004l2.842-3.517 2.823-3.535c0.548-0.687 0.451-1.716-0.272-2.276z"></path></svg>
                                </div>
                                <div style="overflow: hidden;text-overflow: ellipsis;">{{ section?.lesson_material?.title }}</div>
                            </div>
                            <div v-if="sectionsCompleted" style="gap:5px;" class="flex align-center">
                                <div v-if="portfolio && portfolio.status >= 1" style="background-color: rgb(100, 188, 68); width: 18px; height: 18px; display: flex; border-radius: 50%; margin: auto 3px; min-width: 18px;">
                                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 38 32" style="fill: rgb(255, 255, 255); margin-left: 4px;"><path data-v-3c7a9f1f="" d="M36.486 0.324c-0.666-0.515-1.629-0.396-2.204 0.22l-3.039 3.271-3.060 3.328c-2.031 2.23-4.067 4.452-6.086 6.689-2.025 2.234-8.487 9.367-9.743 10.772-0.132 0.15-0.369 0.129-0.486-0.025-1.060-1.399-2.287-3.028-3.468-4.519-1.161-1.465-2.516-3.22-3.271-4.144-0.755-0.927-1.702-2.093-2.191-2.668-0.528-0.625-1.457-0.791-2.182-0.329-0.765 0.489-0.973 1.521-0.518 2.307 0.367 0.636 2.307 3.801 2.307 3.801 0.801 1.27 3.213 5.039 3.699 5.791 0.487 0.751 1.194 1.782 1.879 2.788 0.684 1.004 1.52 2.313 2.429 3.264s2.487 0.627 3.321-0.358c1.932-2.282 9.588-11.527 11.498-13.857 1.916-2.327 3.815-4.668 5.719-7.004l2.842-3.517 2.823-3.535c0.548-0.687 0.451-1.716-0.272-2.276z"></path></svg>
                                </div>
                                <div v-else-if="portfolio && portfolio.status == 0" style="background-color: rgb(255, 165, 0); width: 18px; height: 18px; display: flex; border-radius: 50%; margin: auto 3px; min-width: 18px;">
                                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 38 32" style="fill: rgb(255, 255, 255); margin-left: 4px;"><path data-v-3c7a9f1f="" d="M36.486 0.324c-0.666-0.515-1.629-0.396-2.204 0.22l-3.039 3.271-3.060 3.328c-2.031 2.23-4.067 4.452-6.086 6.689-2.025 2.234-8.487 9.367-9.743 10.772-0.132 0.15-0.369 0.129-0.486-0.025-1.060-1.399-2.287-3.028-3.468-4.519-1.161-1.465-2.516-3.22-3.271-4.144-0.755-0.927-1.702-2.093-2.191-2.668-0.528-0.625-1.457-0.791-2.182-0.329-0.765 0.489-0.973 1.521-0.518 2.307 0.367 0.636 2.307 3.801 2.307 3.801 0.801 1.27 3.213 5.039 3.699 5.791 0.487 0.751 1.194 1.782 1.879 2.788 0.684 1.004 1.52 2.313 2.429 3.264s2.487 0.627 3.321-0.358c1.932-2.282 9.588-11.527 11.498-13.857 1.916-2.327 3.815-4.668 5.719-7.004l2.842-3.517 2.823-3.535c0.548-0.687 0.451-1.716-0.272-2.276z"></path></svg>
                                </div>
                                <div style="overflow: hidden;text-overflow: ellipsis;">ポートフォリオ作成</div>
                            </div>
                        </div>
                    </div>
                    
                                    
                </div>
                </TransitionGroup>
            </div>
            <div v-else-if="route.name == 'top' && selectedTopic?.has_case_study">
                <TransitionGroup name="t-list" class="topic-container" tag="div" style="padding-bottom: 20px;">
                    <div v-for="material in materials"
                        :key=material.id :class="['topic-item']" @click="selectCaseStudy(material)">
                        <div class="flex gap-2.5 flex-col">
                            <div class="flex align-center" style="gap:5px;">
                                <div v-if="status > material.status" style="background-color: rgb(100, 188, 68); width: 18px; height: 18px; display: flex; border-radius: 50%; margin: auto 3px; min-width: 18px;">
                                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 38 32" style="fill: rgb(255, 255, 255); margin-left: 4px;"><path data-v-3c7a9f1f="" d="M36.486 0.324c-0.666-0.515-1.629-0.396-2.204 0.22l-3.039 3.271-3.060 3.328c-2.031 2.23-4.067 4.452-6.086 6.689-2.025 2.234-8.487 9.367-9.743 10.772-0.132 0.15-0.369 0.129-0.486-0.025-1.060-1.399-2.287-3.028-3.468-4.519-1.161-1.465-2.516-3.22-3.271-4.144-0.755-0.927-1.702-2.093-2.191-2.668-0.528-0.625-1.457-0.791-2.182-0.329-0.765 0.489-0.973 1.521-0.518 2.307 0.367 0.636 2.307 3.801 2.307 3.801 0.801 1.27 3.213 5.039 3.699 5.791 0.487 0.751 1.194 1.782 1.879 2.788 0.684 1.004 1.52 2.313 2.429 3.264s2.487 0.627 3.321-0.358c1.932-2.282 9.588-11.527 11.498-13.857 1.916-2.327 3.815-4.668 5.719-7.004l2.842-3.517 2.823-3.535c0.548-0.687 0.451-1.716-0.272-2.276z"></path></svg>
                                </div>
                                <div class="topic-title">{{ material.title }}</div>
                            </div>
                            
                            
                        </div>
                        
                                        
                    </div>
                </TransitionGroup>
                
            </div>
            <RouterView
                :selectedTopic="selectedTopic"
                :materials="materials"
                :sections_status="sections_status"
                :filteredMaterials="filteredMaterials"
                :sectionsCompleted="sectionsCompleted"
            />
        </div>
    </div>
</template>
<script setup>
import { onBeforeRouteUpdate, useRoute, useRouter } from 'vue-router';
import { computed, onMounted, ref, inject, provide, defineAsyncComponent, watch  } from 'vue';
import { useAuthUserStore } from '@/store/auth'
import Back from '../Icons/Back.vue';
import { useApi } from '@/composables/api';
    const auth = useAuthUserStore()
    const subtopics = [{val: 0, title:'知識研修'},{val: 1, title: 'グループディスカッション'},{val: 2, title: 'ポートフォリオ'}]
    const props = defineProps(['selectedTopic'])
    const router = useRouter()
    const route = useRoute()
    const getThemes = inject('getThemes')
    const materials = ref([])
    const portfolio = ref(null)
    const Explain = defineAsyncComponent(() =>
        import('./LessonExplain.vue')
    )
    const moreDetail = ref(false)
    const api = useApi()
    const selectCaseStudy = (material) => {
        router.push({name: 'material', params: {materialId: material.id}})
    }
    const select = (topic) => {
        if(topic.val == 0){
            router.push({name: 'basic'})
        }else if(topic.val == 1 && topic.val <= status.value){
            router.push({name: 'discussion'})
        }else if(topic.val == 2 && topic.val <= status.value){
            router.push({name: 'portfolio'})
        }
    }
    const filteredMaterials = computed(() => {
        return materials.value && materials.value.length ? materials.value.filter(ob => ob.priority == 1) : [] 
    })
    const sectionsCompleted = computed(() => {
        return sections_status.value && sections_status.value.length && filteredMaterials.value.length ? sections_status.value.filter(val => val.status === 2).length == filteredMaterials.value.length : false
    })
    const noData = computed(() => {
        return props.selectedTopic && props.selectedTopic.active == 0
    })
    const sections_status = computed(() => {
        return portfolio.value && portfolio.value.lesson_sections ? portfolio.value.lesson_sections : []
    })
    onBeforeRouteUpdate((to, from, next) => {
        getThemes()
        next();
    })
    const userNavigated = ref(false)
    const ensureDefaultView = (isUserAction = false) => {
        if(isUserAction){
            userNavigated.value = true
        }
        if(route.name === 'top' && props.selectedTopic && props.selectedTopic.active == 1 && !props.selectedTopic.portfolio && !userNavigated.value){
            router.replace({name: 'basic', params: {lessonThemeId: props.selectedTopic.id}})
        }
    }
    watch(() => props.selectedTopic, () => {
        ensureDefaultView()
    }, { immediate: true })
    onMounted(async() => {
        await getLessons()
        getLessonPortfolios()
        ensureDefaultView()
    })
    const pathGenerator = computed(() => {
        const relatedRoutes = route.matched.filter(rt => !['learning', 'top'].includes(rt.name))
        const items = []
        const base = {
            label: props.selectedTopic ? props.selectedTopic.title : '',
            route: {name: 'top'}
        }
        items.push(base)
        
        relatedRoutes.forEach(rt => {
            const label = rt.name == 'material' ? materialTitle.value : rt.meta?.nameJp
            const params = Object.assign({}, route.params)
            items.push({label: label, route: {name: rt.name, params}})
        });
        return items
    })
    const materialTitle = computed(() => {
        const materialId = route.params?.materialId
        if(materialId && materials.value){
            const title = materials.value.filter(ob => ob.id == materialId)[0]?.title
            return title
        }
        return ''
    })

    const status = computed(() => {
        if(props.selectedTopic){
            if(props.selectedTopic.lesson_portfolio && props.selectedTopic.lesson_portfolio.status != null){
                return props.selectedTopic.lesson_portfolio.status
            } else if(props.selectedTopic.materials.length) {
                return props.selectedTopic.materials[0]?.answer?.status == 2 ? props.selectedTopic.materials[0]?.answer?.status : 0
            }
            return 0
        }
    })
    const goByName = (route) => {
        if (props.selectedTopic?.has_case_study && route.name == 'top' && !props.selectedTopic?.portfolio) {
            router.push({name : 'learning'})
        } else {
            router.push(route)
        }
    }
    const goBack = () => {
        const { name } = route;
        const { has_case_study, portfolio } = props.selectedTopic || {};

        if (has_case_study && !portfolio) {
            if (name === 'basic') {
                router.push({ name: 'learning' });
                return;
            } 
            if (name === 'material') {
                router.push({ name: 'basic' });
                return;
            }
        }

        switch (name) {
            case 'top':
                router.push({ name: 'learning' });
                break;
            case 'basic':
                router.push({ name: 'top' });
                break;
            case 'material':
                router.push({ name: 'basic' });
                break;
            default:
                router.go(-1);
                break;
        }
    };
    const getLessons = async() => {
        const data = await api.get(`/get_lessons?lesson_theme_id=${route.params.lessonThemeId}`)
        data && (materials.value = data)              
    }
    const getLessonPortfolios = async() => {
        const data = await api.post('/get_lesson_portfolio', {lesson_theme_id: route.params.lessonThemeId})
        data && (portfolio.value = data)  
    }
    provide('getLessonPortfolios', getLessonPortfolios)
    provide('portfolio', portfolio)
    provide('getLessons', getLessons)
</script>
<style scoped>
.video-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 30px;
  margin-top: 20px;
}

@media screen and (max-width: 959px) {
  .video-grid {
    grid-template-columns: 1fr;
  }
}
</style>
