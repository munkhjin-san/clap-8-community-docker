<template>
    <div class="flex items-center justify-center h-full w-full">
        <div class="p-[20px] bg-[var(--background-color)] text-[var(--primary-color)] leading-normal flex flex-col gap-[20px] text-[14px]">   
            <div class="flex items-center gap-[5px]">
                <div style="background-color: rgb(100, 188, 68); width: 18px; height: 18px; display: flex; border-radius: 50%; margin: auto 3px; min-width: 18px;">
                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 38 32" style="fill: rgb(255, 255, 255); margin-left: 4px;">
                        <path data-v-3c7a9f1f="" d="M36.486 0.324c-0.666-0.515-1.629-0.396-2.204 0.22l-3.039 3.271-3.060 3.328c-2.031 2.23-4.067 4.452-6.086 6.689-2.025 2.234-8.487 9.367-9.743 10.772-0.132 0.15-0.369 0.129-0.486-0.025-1.060-1.399-2.287-3.028-3.468-4.519-1.161-1.465-2.516-3.22-3.271-4.144-0.755-0.927-1.702-2.093-2.191-2.668-0.528-0.625-1.457-0.791-2.182-0.329-0.765 0.489-0.973 1.521-0.518 2.307 0.367 0.636 2.307 3.801 2.307 3.801 0.801 1.27 3.213 5.039 3.699 5.791 0.487 0.751 1.194 1.782 1.879 2.788 0.684 1.004 1.52 2.313 2.429 3.264s2.487 0.627 3.321-0.358c1.932-2.282 9.588-11.527 11.498-13.857 1.916-2.327 3.815-4.668 5.719-7.004l2.842-3.517 2.823-3.535c0.548-0.687 0.451-1.716-0.272-2.276z"></path>
                    </svg>
                </div>
                <p>フォームの回答が完了しました。</p>
            </div>                 
            <div class="flex flex-col gap-[15px]">
                <!-- <router-link :to="{name: 'survey-answers-detail', query: route.query}">回答を確認する</router-link> -->
                <router-link :to="{name: 'survey-form', params: {surveyId: String(route.query.surveyId)}}">新しい回答を作成</router-link>
            </div>
        </div>
    </div>
</template>
<script setup lang="ts">
import { useRoute } from 'vue-router';

const route = useRoute()
</script>