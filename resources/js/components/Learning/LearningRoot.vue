<template>

    <div class="post-root learning">
        <div>
            <router-view v-slot="{ Component }">
                <transition name="lessonShift">
                    <component
                        class="routeposition" 
                        :is="Component"
                        :selectedTopic="selectedTopic"
                    />
                </transition>
            </router-view>
        </div>
        <div class="post-header" >
            <HamBurger v-if="responsive.mobile"/>
            <!-- <div class="post-search-wrap">
                <PostSearchBar className="newChatMemberSearch" customPlaceHolder="検索"/>
            </div> -->
            <div class="post-search-wrap">
                <p style="color:gray;">研修のテーマを選択してください。</p>
            </div>            
        </div>
        <div class="post-container scrollable">
            <div>
                <TransitionGroup name="t-list" class="topic-container" tag="div">
                <div 
                    :class="['topic-item', {'inactive-theme' : topic.active == 0}]" 
                    v-for="topic in topicList"
                    :key="topic.id"
                    @click="select(topic)"
                >                            
                    <div class="topic-title flex" style="gap:5px">
                        <div v-if="topic.lesson_portfolio?.status == 3 || (materialStatus(topic.materials) && !topic.portfolio)" style="background-color: rgb(100, 188, 68); width: 18px; height: 18px; display: flex; border-radius: 50%; margin: auto 3px; min-width: 18px;">
                            <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 38 32" style="fill: rgb(255, 255, 255); margin-left: 4px;"><path data-v-3c7a9f1f="" d="M36.486 0.324c-0.666-0.515-1.629-0.396-2.204 0.22l-3.039 3.271-3.060 3.328c-2.031 2.23-4.067 4.452-6.086 6.689-2.025 2.234-8.487 9.367-9.743 10.772-0.132 0.15-0.369 0.129-0.486-0.025-1.060-1.399-2.287-3.028-3.468-4.519-1.161-1.465-2.516-3.22-3.271-4.144-0.755-0.927-1.702-2.093-2.191-2.668-0.528-0.625-1.457-0.791-2.182-0.329-0.765 0.489-0.973 1.521-0.518 2.307 0.367 0.636 2.307 3.801 2.307 3.801 0.801 1.27 3.213 5.039 3.699 5.791 0.487 0.751 1.194 1.782 1.879 2.788 0.684 1.004 1.52 2.313 2.429 3.264s2.487 0.627 3.321-0.358c1.932-2.282 9.588-11.527 11.498-13.857 1.916-2.327 3.815-4.668 5.719-7.004l2.842-3.517 2.823-3.535c0.548-0.687 0.451-1.716-0.272-2.276z"></path></svg>
                        </div>
                        {{ topic.title }}
                    </div>
                    <div v-if="topic.lesson_portfolio" class="flex flex-col gap-2.5" style="margin-top: 10px;">
                        <div v-for="status in topic.lesson_portfolio.status" class="flex" style="gap: 5px;align-items: center;">
                            <div style="background-color: rgb(100, 188, 68); width: 18px; height: 18px; display: flex; border-radius: 50%; margin: auto 3px; min-width: 18px;">
                                <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 38 32" style="fill: rgb(255, 255, 255); margin-left: 4px;"><path data-v-3c7a9f1f="" d="M36.486 0.324c-0.666-0.515-1.629-0.396-2.204 0.22l-3.039 3.271-3.060 3.328c-2.031 2.23-4.067 4.452-6.086 6.689-2.025 2.234-8.487 9.367-9.743 10.772-0.132 0.15-0.369 0.129-0.486-0.025-1.060-1.399-2.287-3.028-3.468-4.519-1.161-1.465-2.516-3.22-3.271-4.144-0.755-0.927-1.702-2.093-2.191-2.668-0.528-0.625-1.457-0.791-2.182-0.329-0.765 0.489-0.973 1.521-0.518 2.307 0.367 0.636 2.307 3.801 2.307 3.801 0.801 1.27 3.213 5.039 3.699 5.791 0.487 0.751 1.194 1.782 1.879 2.788 0.684 1.004 1.52 2.313 2.429 3.264s2.487 0.627 3.321-0.358c1.932-2.282 9.588-11.527 11.498-13.857 1.916-2.327 3.815-4.668 5.719-7.004l2.842-3.517 2.823-3.535c0.548-0.687 0.451-1.716-0.272-2.276z"></path></svg>
                            </div>
                            {{ statusMap[status - 1] }}
                        </div>
                        <div v-if="topic.lesson_portfolio.status == 0" class="flex" style="gap: 5px;align-items: center;">
                            <div style="background-color: rgb(255, 165, 0); width: 18px; height: 18px; display: flex; border-radius: 50%; margin: auto 3px; min-width: 18px;">
                                <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 38 32" style="fill: rgb(255, 255, 255); margin-left: 4px;"><path data-v-3c7a9f1f="" d="M36.486 0.324c-0.666-0.515-1.629-0.396-2.204 0.22l-3.039 3.271-3.060 3.328c-2.031 2.23-4.067 4.452-6.086 6.689-2.025 2.234-8.487 9.367-9.743 10.772-0.132 0.15-0.369 0.129-0.486-0.025-1.060-1.399-2.287-3.028-3.468-4.519-1.161-1.465-2.516-3.22-3.271-4.144-0.755-0.927-1.702-2.093-2.191-2.668-0.528-0.625-1.457-0.791-2.182-0.329-0.765 0.489-0.973 1.521-0.518 2.307 0.367 0.636 2.307 3.801 2.307 3.801 0.801 1.27 3.213 5.039 3.699 5.791 0.487 0.751 1.194 1.782 1.879 2.788 0.684 1.004 1.52 2.313 2.429 3.264s2.487 0.627 3.321-0.358c1.932-2.282 9.588-11.527 11.498-13.857 1.916-2.327 3.815-4.668 5.719-7.004l2.842-3.517 2.823-3.535c0.548-0.687 0.451-1.716-0.272-2.276z"></path></svg>
                            </div>
                            基礎知識研修中
                        </div>
                    </div>
                    <div v-else-if="topic.has_case_study && (basicStatus(topic.materials) || caseStatus(topic.materials))" class="flex flex-col gap-2.5 mt-[10px]">
                        <div class="flex gap-[5px] items-center" v-if="basicStatus(topic.materials)">
                            <div style="background-color: rgb(100, 188, 68); width: 18px; height: 18px; display: flex; border-radius: 50%; margin: auto 3px; min-width: 18px;">
                                <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 38 32" style="fill: rgb(255, 255, 255); margin-left: 4px;"><path data-v-3c7a9f1f="" d="M36.486 0.324c-0.666-0.515-1.629-0.396-2.204 0.22l-3.039 3.271-3.060 3.328c-2.031 2.23-4.067 4.452-6.086 6.689-2.025 2.234-8.487 9.367-9.743 10.772-0.132 0.15-0.369 0.129-0.486-0.025-1.060-1.399-2.287-3.028-3.468-4.519-1.161-1.465-2.516-3.22-3.271-4.144-0.755-0.927-1.702-2.093-2.191-2.668-0.528-0.625-1.457-0.791-2.182-0.329-0.765 0.489-0.973 1.521-0.518 2.307 0.367 0.636 2.307 3.801 2.307 3.801 0.801 1.27 3.213 5.039 3.699 5.791 0.487 0.751 1.194 1.782 1.879 2.788 0.684 1.004 1.52 2.313 2.429 3.264s2.487 0.627 3.321-0.358c1.932-2.282 9.588-11.527 11.498-13.857 1.916-2.327 3.815-4.668 5.719-7.004l2.842-3.517 2.823-3.535c0.548-0.687 0.451-1.716-0.272-2.276z"></path></svg>
                            </div>
                            基礎知識
                        </div>
                        <div class="flex gap-[5px] items-center" v-if="caseStatus(topic.materials)">
                            <div style="background-color: rgb(100, 188, 68); width: 18px; height: 18px; display: flex; border-radius: 50%; margin: auto 3px; min-width: 18px;">
                                <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 38 32" style="fill: rgb(255, 255, 255); margin-left: 4px;"><path data-v-3c7a9f1f="" d="M36.486 0.324c-0.666-0.515-1.629-0.396-2.204 0.22l-3.039 3.271-3.060 3.328c-2.031 2.23-4.067 4.452-6.086 6.689-2.025 2.234-8.487 9.367-9.743 10.772-0.132 0.15-0.369 0.129-0.486-0.025-1.060-1.399-2.287-3.028-3.468-4.519-1.161-1.465-2.516-3.22-3.271-4.144-0.755-0.927-1.702-2.093-2.191-2.668-0.528-0.625-1.457-0.791-2.182-0.329-0.765 0.489-0.973 1.521-0.518 2.307 0.367 0.636 2.307 3.801 2.307 3.801 0.801 1.27 3.213 5.039 3.699 5.791 0.487 0.751 1.194 1.782 1.879 2.788 0.684 1.004 1.52 2.313 2.429 3.264s2.487 0.627 3.321-0.358c1.932-2.282 9.588-11.527 11.498-13.857 1.916-2.327 3.815-4.668 5.719-7.004l2.842-3.517 2.823-3.535c0.548-0.687 0.451-1.716-0.272-2.276z"></path></svg>
                            </div>
                            ケーススタディ
                        </div>
                        <div class="flex gap-[5px] items-center" v-if="topic.survey_completed">
                            <div style="background-color: rgb(100, 188, 68); width: 18px; height: 18px; display: flex; border-radius: 50%; margin: auto 3px; min-width: 18px;">
                                <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 38 32" style="fill: rgb(255, 255, 255); margin-left: 4px;"><path data-v-3c7a9f1f="" d="M36.486 0.324c-0.666-0.515-1.629-0.396-2.204 0.22l-3.039 3.271-3.060 3.328c-2.031 2.23-4.067 4.452-6.086 6.689-2.025 2.234-8.487 9.367-9.743 10.772-0.132 0.15-0.369 0.129-0.486-0.025-1.060-1.399-2.287-3.028-3.468-4.519-1.161-1.465-2.516-3.22-3.271-4.144-0.755-0.927-1.702-2.093-2.191-2.668-0.528-0.625-1.457-0.791-2.182-0.329-0.765 0.489-0.973 1.521-0.518 2.307 0.367 0.636 2.307 3.801 2.307 3.801 0.801 1.27 3.213 5.039 3.699 5.791 0.487 0.751 1.194 1.782 1.879 2.788 0.684 1.004 1.52 2.313 2.429 3.264s2.487 0.627 3.321-0.358c1.932-2.282 9.588-11.527 11.498-13.857 1.916-2.327 3.815-4.668 5.719-7.004l2.842-3.517 2.823-3.535c0.548-0.687 0.451-1.716-0.272-2.276z"></path></svg>
                            </div>
                            チェックリスト
                        </div>
                    </div>
                </div>
                </TransitionGroup>
                
            </div>
            <!-- <div style="padding: 0 20px 20px;">
                <div style="position: unset;margin-top: 30px;" class="no-comment-text" v-if="!activeId"></div>
                
            </div> -->
        </div>
        
        
        
    </div>
</template>
<script setup>
import { useRoute, useRouter } from 'vue-router';
import HamBurger from '../Global/HamBurger.vue';
import { ref, computed, onMounted, provide, inject } from 'vue'
import { useResponsive } from '@/store/responsive';
    const route = useRoute()
    const router = useRouter()
    const responsive = useResponsive()
    const themeRecords = ref([])
    const { notify } = inject('dialog')
    const statusMap = ['基礎知識','グループディスカッション','ポートフォリオ']
    onMounted(() => {
        getThemes()
    })
    
    
    const activeId = computed(() => {
            return route.params && route.params.lessonThemeId ? parseInt(route.params.lessonThemeId): null
    })
    const topicList = computed(() => {            
        return themeRecords.value            
    })
    const selectedTopic = computed(() => {
        return activeId.value ? themeRecords.value.find(ob => ob.id == activeId.value) : null
    })
    const materialStatus = (materials) => {
        return materials.every(ob => ob.answer?.status == 2)
    }
    const basicStatus = (materials) => {
        const filtered = materials.filter(ob => ob.material_type == '基礎知識')
        return filtered.length && filtered.every(ob => ob.answer?.status == 2)
    }
    const caseStatus = (materials) => {
        const filtered = materials.filter(ob => ob.material_type == 'ケーススタディ')
        return filtered.length && filtered.every(ob => ob.answer?.status == 2)
    }
    const select = (topic) => {
        const isActive = activeId.value && activeId.value === topic.id;
        let path;

        if (topic.has_case_study && !topic.portfolio) {
            path = isActive ? `/learning` : `/learning/${topic.id}/basic`;
        } else if (topic.active === 1) {
            path = isActive ? `/learning` : `/learning/${topic.id}`;
        }

        if (path) {
            router.push(path);
        }
    };

    const getThemes = () => {
        axios.get('/get_lesson_themes').then(res => {
                if(res.data){
                    themeRecords.value = res.data                   
                }
            }).catch(function (error) {
                if (error.response) notify('エラーが発生しました。 ' + error.response.data.message)
                else if (error.request) notify('エラーが発生しました。')
                else notify('エラーが発生しました。 ' + error.message)                       
            });
        }
    provide('getThemes', getThemes)

</script>
<style>
.routeposition{
    position:absolute;
    left: 0;
    top: 0;
    height: 100%;
    width: 100%;
    z-index: 6;
    background: var(--bg2);
    color:var(--primary-color);
}
.lessons-topic p:empty::after {
    content: "\00A0";
}
.topic-container{
    margin-top: 5px;
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(calc(33% - 30px), 1fr));
    padding: 0 20px;
    gap: 30px;
}
.topic-item{
    padding: 25px;
    background: var(--background-color);
    cursor: pointer;
    transition: all 0.1s;
    display: flex;
    gap: 15px;
    color: var(--primary-color);
    fill: var(--primary-color);
    white-space: nowrap;
    font-size: 14px;
    flex-direction: column;
    justify-content: center;
    min-height: 98px;
    overflow: hidden;
}
.topic-item:hover{
  background-color: var(--primary-color);
  color: var(--background-color);
  /* box-shadow: rgba(0, 0, 0, 0.35) 0px 5px 15px; */
}
.topic-title{
    font-size: 18px;
    font-weight: 600;
    white-space: normal;
    word-break: break-all;
}
.inactive-theme{
    background-color: var(--inactive-background);
    opacity: 0.7;
    cursor: not-allowed;
}
.inactive-theme:hover{
    background-color: var(--inactive-background);
    color: var(--primary-color);
}
.flex{
    display: flex;
}
.flex-col{
    flex-direction: column;
}
.align-center{
    align-items: center;
}
.selected-topic{
  background-color: var(--primary-color);
  color: var(--background-color);
  fill: var(--background-color);
}
.t-list-enter-active,
.t-list-leave-active {
  transition: all 0.3s ease;
}
.t-list-enter-from,
.t-list-leave-to {
  opacity: 0;
  transform: translateY(-30px);
}
.group-item{
    padding: 25px;
    background: var(--background-color);
    transition: all 0.1s;
    display: flex;
    flex-direction: column;
    gap: 15px;
    color: var(--primary-color);
    fill: var(--primary-color);
    white-space: nowrap;
    font-size: 14px;
    position: relative;
}
.group-member{
    display: flex;
    align-items: center;
    gap: 15px;
}
.lesson-breadcumb{
    font-size: 16px;
    cursor: pointer;
    white-space: nowrap;
    /* overflow: hidden; */
    /* text-overflow: ellipsis; */
    
}
.lesson-breadcumb:hover{
    font-weight: 600;

}
.lesson-nav-bar{
    display: flex;
    width: calc(100% - 50px);
    overflow: auto hidden;
    height: 100%;
    align-items: center;
}
.lesson-nav-bar::-webkit-scrollbar {
  width: 0.0; /* Adjust as needed */
  height: 0;
}

.lesson-nav-bar::-webkit-scrollbar-track {
  background-color: transparent; /* Make the track invisible */
}

.lesson-nav-bar::-webkit-scrollbar-thumb {
  background-color: transparent; /* Hide the thumb */
}
.section-wrapper{
    height: 100%;overflow: hidden auto;margin: 0;
    word-wrap: break-word;
    white-space: break-spaces;
    line-height: 1.8;
    display: flex;
    flex-direction: column;
    gap: 30px;
}
.section-inner{
    margin: 0 20px; 
    background: var(--background-color);
    padding: 30px;
}
.section-wrapper p:empty::after {
    content: "\00A0";
}
.lesson-play {
    position: absolute;
    background-color: var(--primary-button);
    color: #ffffff;
    padding: 5px;
    right: 50px;
    cursor: pointer;
    font-size: 13px;
    transition: transform 0.1s;
}
@media screen and (max-width: 959px) {
    .section-inner{
        padding: 20px;
    }
    .lesson-breadcumb{
        font-size: 14px;
        /* max-width: 190px; */
    }
    .topic-container{
        grid-template-columns: auto;
        padding: 0 20px;
        gap: 15px;
        padding-bottom: 20px;
    }
    .topic-item{
        padding: 15px;
        font-size: 14px;
    }
}
</style>