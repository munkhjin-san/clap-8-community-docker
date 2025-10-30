<template>
    <div class="post-root">
        <div class="post-header">
            <div class="min-w-[20px]">
                <HamBurger v-if="responsive.mobile"/>
            </div>  
            <div class="post-header-title text-[var(--primary-color)]">コミュニティ</div>   
        </div>
        <div class="post-container scrollable text-[var(--primary-color)]">
            
            <div class="sub-tab-container px-4">
                <div @click="router.push({name: 'members'})" :class="['sub-tab-item', { 'selected-sub-tab': route.path.includes('members')}]">コミュニティメンバー</div>
                <div @click="router.push({name: 'offices'})" :class="['sub-tab-item', { 'selected-sub-tab': route.path.includes('offices')}]">営業所</div>
            </div> 
            <router-view v-slot="{ Component }">
                <component
                    :fileAccess="true"
                    visibility="community_public"
                    :is="Component"
                />
            </router-view>
        </div>
    </div>
</template>
<script setup lang="ts">
import axios from 'axios';
import { ref, onMounted, inject, computed } from 'vue';
import FloatButton from '../Global/FloatButton.vue';
import { useResponsive } from '@/store/responsive';
import HamBurger from '../Global/HamBurger.vue';
import { useAuthUserStore } from '@/store/auth';
import { useRoute, useRouter } from 'vue-router';
const responsive = useResponsive()
const tab = ref(0)
const router = useRouter()

const route = useRoute()

const isLoaded = ref(false)



</script>
<style>
.c-selector {
    z-index: 99;
    width: -moz-fit-content;
    width: fit-content;
    box-shadow: #3c40434d 0 1px 2px, #3c404326 0 2px 6px 2px;
    background-color: var(--background-color);
    position: absolute;
    left: 0px;
    min-width: 100px;
    top: 30px;
    padding: 15px;
    display: flex;
    flex-direction: column;
    gap: 15px;
    white-space: nowrap;
    font-size: 13px;
}
</style>