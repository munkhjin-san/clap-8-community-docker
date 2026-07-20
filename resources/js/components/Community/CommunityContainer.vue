<template>
    <div class="post-root">
        <div class="post-header">
            <div class="min-w-[20px]">
                <HamBurger v-if="responsive.mobile"/>
            </div>
            <div ref="selectorRef" class="community-selector">
                <button type="button" class="community-selector-trigger" :class="{ 'is-static': auth.communities.length <= 1 }" @click="toggleSelector">
                    <span class="community-selector-icon">
                        <img v-if="iconUrl(auth.activeCommunity)" :src="iconUrl(auth.activeCommunity)" :alt="communityTitle" loading="lazy">
                        <span v-else>{{ initial(auth.activeCommunity) }}</span>
                    </span>
                    <span class="community-selector-name">{{ communityTitle }}</span>
                    <svg v-if="auth.communities.length > 1" class="community-selector-caret" xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </button>
                <div v-if="selectorOpen" class="community-selector-menu">
                    <button v-for="community in auth.communities" :key="community.id" type="button" class="community-selector-item" :class="{ 'is-current': community.id === auth.activeCommunity?.id }" @click="selectCommunity(community.id)">
                        <span class="community-selector-icon">
                            <img v-if="iconUrl(community)" :src="iconUrl(community)" :alt="community.name" loading="lazy">
                            <span v-else>{{ initial(community) }}</span>
                        </span>
                        <span class="community-selector-name">{{ community.name }}</span>
                        <svg v-if="community.id === auth.activeCommunity?.id" class="community-selector-check" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                    </button>
                </div>
            </div>
            <div class="community-header-actions">
                <button type="button" class="community-create-trigger" title="コミュニティを作成" @click="createModalOpen = true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 32 32" style="fill: currentColor;">
                        <path d="M30.044 14.14c-2.402-0.231-4.804-0.341-7.206-0.422-1.535-0.058-3.071-0.079-4.606-0.090-0.326-0.002-0.587-0.265-0.588-0.591-0.004-1.537-0.018-3.074-0.078-4.613-0.092-2.4-0.218-4.802-0.542-7.205-0.084-0.612-0.565-1.119-1.205-1.206-0.769-0.103-1.477 0.437-1.582 1.206-0.324 2.401-0.449 4.804-0.542 7.205-0.059 1.536-0.074 3.071-0.078 4.606-0.001 0.325-0.263 0.59-0.59 0.59-1.534 0.005-3.068 0.020-4.602 0.078-2.404 0.094-4.805 0.219-7.207 0.543-0.612 0.081-1.119 0.564-1.205 1.205-0.103 0.769 0.436 1.477 1.205 1.58 2.402 0.324 4.804 0.449 7.207 0.543 1.536 0.059 3.074 0.073 4.612 0.078 0.325 0.001 0.587 0.262 0.59 0.587 0.011 1.536 0.033 3.070 0.090 4.606 0.080 2.402 0.192 4.805 0.423 7.207 0.066 0.699 0.622 1.278 1.349 1.348 0.823 0.079 1.556-0.524 1.633-1.348 0.231-2.402 0.342-4.805 0.423-7.207 0.057-1.538 0.079-3.077 0.090-4.615 0.002-0.324 0.263-0.583 0.587-0.586 1.538-0.011 3.077-0.034 4.615-0.090 2.402-0.080 4.804-0.193 7.206-0.423 0.7-0.066 1.279-0.622 1.349-1.349 0.076-0.823-0.528-1.557-1.351-1.634z"></path>
                    </svg>
                    <span class="community-create-trigger-text">コミュニティを作成</span>
                </button>
            </div>
        </div>
        <CommunityCreate v-if="createModalOpen" @close="createModalOpen = false"/>
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
import { ref, onMounted, onUnmounted, inject, computed } from 'vue';
import FloatButton from '../Global/FloatButton.vue';
import { useResponsive } from '@/store/responsive';
import HamBurger from '../Global/HamBurger.vue';
import CommunityCreate from './CommunityCreate.vue';
import { useAuthUserStore } from '@/store/auth';
import { useRoute, useRouter } from 'vue-router';
const responsive = useResponsive()
const tab = ref(0)
const router = useRouter()
const auth = useAuthUserStore()

const route = useRoute()

const isLoaded = ref(false)
const createModalOpen = ref(false)
const selectorOpen = ref(false)
const selectorRef = ref<HTMLElement | null>(null)

// Show the active community's name; fall back to a generic label when the user
// belongs to no community yet.
const communityTitle = computed(() => auth.activeCommunity?.name || 'コミュニティ')

const iconUrl = (community: { config?: { icon_path?: string | null } } | null) =>
    community?.config?.icon_path ? `/board_icon_thumbnail/${community.config.icon_path}/96` : ''
const initial = (community: { name?: string } | null) => (community?.name || 'コ').charAt(0).toUpperCase()

const toggleSelector = () => {
    if(auth.communities.length <= 1) return
    selectorOpen.value = !selectorOpen.value
}

const selectCommunity = async (communityId: number) => {
    selectorOpen.value = false
    if(communityId && communityId !== auth.activeCommunity?.id){
        await auth.switchCommunity(communityId)
        window.location.reload()
    }
}

const onDocumentClick = (event: MouseEvent) => {
    if(selectorOpen.value && selectorRef.value && !selectorRef.value.contains(event.target as Node)){
        selectorOpen.value = false
    }
}
onMounted(() => document.addEventListener('click', onDocumentClick))
onUnmounted(() => document.removeEventListener('click', onDocumentClick))



</script>
<style scoped>
.community-header-actions{
    margin-left: auto;
    display: flex;
    align-items: center;
    gap: 10px;
}
.community-selector{
    position: relative;
    min-width: 0;
    background: var(--background-color);
    border-radius: 5px;

}
.community-selector-trigger{
    display: flex;
    align-items: center;
    gap: 10px;
    max-width: 320px;
    height: 40px;
    padding: 5px;
    border: solid thin transparent;
    border-radius: 8px;
    background: transparent;
    color: var(--primary-color);
    cursor: pointer;
}

.community-selector-trigger.is-static{
    cursor: default;
}
.community-selector-icon{
    width: 28px;
    height: 28px;
    min-width: 28px;
    border-radius: 6px;
    overflow: hidden;
    background: var(--bg3);
    border: solid thin var(--formBorder);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--primary-color);
    font-size: 13px;
}
.community-selector-icon img{
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.community-selector-name{
    font-size: 15px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    min-width: 0;
}
.community-selector-caret{
    margin-left: 2px;
    color: gray;
    flex-shrink: 0;
}
.community-selector-menu{
    position: absolute;
    top: calc(100% + 4px);
    left: 0;
    z-index: 99;
    min-width: 240px;
    max-width: 320px;
    max-height: 60vh;
    overflow-y: auto;
    padding: 6px;
    background: var(--background-color);
    border: solid thin var(--formBorder);
    border-radius: 8px;
    box-shadow: #3c40434d 0 1px 2px, #3c404326 0 2px 6px 2px;
    display: flex;
    flex-direction: column;
    gap: 2px;
}
.community-selector-item{
    display: flex;
    align-items: center;
    gap: 10px;
    width: 100%;
    padding: 7px 8px;
    border: none;
    border-radius: 6px;
    background: transparent;
    color: var(--primary-color);
    font-size: 14px;
    text-align: left;
    cursor: pointer;
}
.community-selector-item:hover{
    background: var(--bg3);
}
.community-selector-item.is-current{
    background: var(--bg3);
}
.community-selector-item .community-selector-name{
    flex: 1;
}
.community-selector-check{
    color: var(--primary-color);
    flex-shrink: 0;
}
.community-create-trigger{
    height: 34px;
    padding: 5px;
    border: solid thin var(--formBorder);
    border-radius: 6px;
    background: var(--background-color);
    color: var(--primary-color);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    gap: 5px;
    margin-right: 20px;
}
</style>
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