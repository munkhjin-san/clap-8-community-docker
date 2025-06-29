<template>
    <div ref="container" @scroll="handleScroll" class="w-full h-full bg-[var(--bg2)] overflow-x-hidden overflow-y-auto">
        <div class="mem-header-section" :style="{'transform': `translateY(${offset}px)`}">
            <div class="post-header">
                <HamBurger v-if="responsive.mobile"/>
                <div class="post-search-wrap">
                    <PostSearchBar @search-start="(word) => {keyword = word}" className="newChatMemberSearch" :customPlaceHolder="`コンタクト検索`"/>                
                </div>    
            </div>
            <div class="sub-tab-container mb-[20px] ml-[20px]">
                <div @click="router.push({name: 'tab1'})" :class="['sub-tab-item', { 'selected-sub-tab': route.name == 'tab1'}]">コミュニティメンバー</div>
                <div @click="router.push({name: 'tab2'})" :class="['sub-tab-item', { 'selected-sub-tab': route.name == 'tab2'}]">コンタクト</div>              
            </div> 
        </div>
        <div class="relative h-[calc(100%-115px)]">
            <router-view v-slot="{ Component }">
                <KeepAlive>
                    <component
                        :is="Component"
                        :keyword="keyword"
                    ></component>
                </KeepAlive>
            </router-view>
        </div>
    </div>
</template>
<script setup lang="ts">
import { useResponsive } from '@/store/responsive';
import { onActivated, ref, useTemplateRef } from 'vue';
import HamBurger from '@/components/Global/HamBurger.vue';
import PostSearchBar from '@/components/Post/PostSearchBar.vue';
import { useRoute, useRouter } from 'vue-router';
const responsive = useResponsive()
const keyword = ref('')
const route = useRoute()
const router = useRouter()
const prevScrollPosition = ref(0)
const container = useTemplateRef('container')
const offset = ref(0)
onActivated(() => {
    offset.value = 0
})
const handleScroll = () => {
    if(!container.value || keyword.value.length) return
    const currentScrollPosition = container.value.scrollTop
    offset.value = currentScrollPosition > prevScrollPosition.value ? -95 : 0
    prevScrollPosition.value = currentScrollPosition   
}

</script>