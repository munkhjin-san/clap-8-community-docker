<template>

    <div class="post-root learning">
        <div>
            <router-view v-slot="{ Component }">
                <transition name="lessonShift">
                    <component 
                        :is="Component"
                        :selectedTopic="selectedTopic"
                    />
                </transition>
            </router-view>
        </div>
        <div class="post-header">
            <HamBurger v-if="$store.state.mobile"/>
            <!-- <div class="post-search-wrap">
                <PostSearchBar className="newChatMemberSearch" customPlaceHolder="検索"/>
            </div> -->
            <div class="post-search-wrap">
                <p style="color:gray;">テーマを選択してください。</p>
            </div>
        </div>
        <div class="post-container scrollable">
            <div>
                <TransitionGroup name="t-list" class="topic-container" tag="div">
                <div 
                    :class="[{'topic-item' : topic.active == 1}, {'selected-topic': activeId && activeId == topic.id}, {'inactive-theme' : topic.active == 0}]" 
                    v-for="topic in topicList"
                    :key="topic.id"
                    @click="select(topic)"
                >                            
                    <div class="topic-title">{{ topic.title }}</div>
                    <div v-if="topic.lesson_portfolio" class="flex flex-col gap-10">
                        <div v-for="status in topic.lesson_portfolio.status" class="flex" style="color:darkgray">
                            <div style="background-color: rgb(100, 188, 68); width: 15px; height: 15px; display: flex; border-radius: 50%; margin: auto 3px; min-width: 15px;">
                                <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 38 32" style="fill: rgb(255, 255, 255); margin: auto;"><path data-v-3c7a9f1f="" d="M36.486 0.324c-0.666-0.515-1.629-0.396-2.204 0.22l-3.039 3.271-3.060 3.328c-2.031 2.23-4.067 4.452-6.086 6.689-2.025 2.234-8.487 9.367-9.743 10.772-0.132 0.15-0.369 0.129-0.486-0.025-1.060-1.399-2.287-3.028-3.468-4.519-1.161-1.465-2.516-3.22-3.271-4.144-0.755-0.927-1.702-2.093-2.191-2.668-0.528-0.625-1.457-0.791-2.182-0.329-0.765 0.489-0.973 1.521-0.518 2.307 0.367 0.636 2.307 3.801 2.307 3.801 0.801 1.27 3.213 5.039 3.699 5.791 0.487 0.751 1.194 1.782 1.879 2.788 0.684 1.004 1.52 2.313 2.429 3.264s2.487 0.627 3.321-0.358c1.932-2.282 9.588-11.527 11.498-13.857 1.916-2.327 3.815-4.668 5.719-7.004l2.842-3.517 2.823-3.535c0.548-0.687 0.451-1.716-0.272-2.276z"></path></svg>
                            </div>
                            {{ statusMap[status - 1] }}
                        </div>
                        <div v-if="topic.lesson_portfolio.understand == 0" class="flex" style="color:darkgray">
                            <div style="background-color: rgb(255, 165, 0); width: 15px; height: 15px; display: flex; border-radius: 50%; margin: auto 3px; min-width: 15px;">
                                <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 38 32" style="fill: rgb(255, 255, 255); margin: auto;"><path data-v-3c7a9f1f="" d="M36.486 0.324c-0.666-0.515-1.629-0.396-2.204 0.22l-3.039 3.271-3.060 3.328c-2.031 2.23-4.067 4.452-6.086 6.689-2.025 2.234-8.487 9.367-9.743 10.772-0.132 0.15-0.369 0.129-0.486-0.025-1.060-1.399-2.287-3.028-3.468-4.519-1.161-1.465-2.516-3.22-3.271-4.144-0.755-0.927-1.702-2.093-2.191-2.668-0.528-0.625-1.457-0.791-2.182-0.329-0.765 0.489-0.973 1.521-0.518 2.307 0.367 0.636 2.307 3.801 2.307 3.801 0.801 1.27 3.213 5.039 3.699 5.791 0.487 0.751 1.194 1.782 1.879 2.788 0.684 1.004 1.52 2.313 2.429 3.264s2.487 0.627 3.321-0.358c1.932-2.282 9.588-11.527 11.498-13.857 1.916-2.327 3.815-4.668 5.719-7.004l2.842-3.517 2.823-3.535c0.548-0.687 0.451-1.716-0.272-2.276z"></path></svg>
                            </div>
                            保留
                        </div>
                    </div>
                </div>
                </TransitionGroup>
            </div>
            <!-- <div style="padding: 0 20px 20px;">
                <div style="position: unset;margin-top: 30px;" class="no-comment-text" v-if="!activeId">テーマを選択してください。</div>
                
            </div> -->
        </div>
        
        
        
    </div>
</template>
<script setup>
import { useRoute, useRouter } from 'vue-router';
import HamBurger from '../Global/HamBurger.vue';
import PostSearchBar from '../Post/PostSearchBar.vue';
import { ref, computed, onMounted, provide } from 'vue'
import axios from 'axios';
    const route = useRoute()
    const router = useRouter()
    const themeRecords = ref([])
    const processing = ref(false)
    const statusMap = ['基礎知識','グループディスカッション','ポートフォリオ']
    onMounted(() => {
        getThemes()
    })
    
    
    const activeId = computed(() => {
            return route.params && route.params.topicId ? parseInt(route.params.topicId): null
    })
    const topicList = computed(() => {            
        return themeRecords.value            
    })
    const selectedTopic = computed(() => {
        return activeId.value ? themeRecords.value.find(ob => ob.id == activeId.value) : null
    })
    const select = (topic) => {
            if(topic.active == 1){
                const path = activeId.value && activeId.value == topic.id ? `/learning` : `/learning/${topic.id}`
                router.push(path)
            }
        }
    const getThemes = () => {
        axios.get('/get_themes_portfolio').then(res => {
                if(res.data){
                    themeRecords.value = res.data                   
                }
            }).catch(function (error) {
                if (error.response) errorToast('エラーが発生しました。 ' + error.response.data.message)
                else if (error.request) errorToast('エラーが発生しました。')
                else errorToast('エラーが発生しました。 ' + error.message)                       
            });
        }
    
    const errorToast = (message) => {
            emitter.emit('setToast', {
                active: true,  
                type: 'info', 
                content: message,
                closeButton: false, 
                autoClose: false,
                answers: ['OK']

            })  
            processing.value = false
            
        }
    provide('getThemes', getThemes)

</script>
<style>
.lessons-topic p:empty::after {
    content: "\00A0";
}
.topic-container{
    margin-top: 5px;
    display: grid;
    grid-template-columns: repeat(3, 1fr);
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
}
.topic-item:hover{
  background-color: var(--primary-color);
  color: var(--background-color);
}
.topic-title{
    font-size: 18px;
    font-weight: 600;
    white-space: normal;
    word-break: break-all;
}
.inactive-theme{
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
    background-color: var(--inactive-background);
    opacity: 0.7;
    cursor: not-allowed;
}
.flex{
    display: flex;
}
.flex-col{
    flex-direction: column;
}
.gap-10{
    gap: 10px;
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
@media screen and (max-width: 959px) {
    .topic-container{
        grid-template-columns: auto;
        padding: 0 20px;
        gap: 15px;
        padding-bottom: 20px;
    }
    .topic-item{
        padding: 15px;
        font-size: 12px;
    }
}
</style>