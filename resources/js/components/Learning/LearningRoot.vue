<template>
    <div class="post-root learning !bg-[var(--bg3)]">
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
        <div class="post-header">
            <HamBurger v-if="responsive.mobile"/>
            <div class="post-search-wrap">
                <div v-if="categories.length" class="learning__categories">
                    <button
                        type="button"
                        :class="['learning__category', { 'learning__category--selected': selectedCategoryId === null }]"
                        @click="selectCategory(null)"
                    >
                        すべて
                    </button>
                    <button
                        v-for="category in categories"
                        :key="category.id"
                        type="button"
                        :class="['learning__category', { 'learning__category--selected': selectedCategoryId === category.id }]"
                        @click="selectCategory(category.id)"
                    >
                        {{ category.name }}
                    </button>
                </div>
            </div>
        </div>
        <div class="post-container scrollable">
            <LearningThemeGrid :themes="topicList" @select="select" />
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, provide, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import HamBurger from '../Global/HamBurger.vue'
import { useResponsive } from '@/store/responsive'
import { useLearningApi } from '@/composables/learningApi'
import type { LearningTheme, LearningThemeCategory } from '@/types/learning'
import { isEnabled } from '@/utils/learningProgress'
import LearningThemeGrid from './shared/LearningThemeGrid.vue'

const route = useRoute()
const router = useRouter()
const responsive = useResponsive()

const themeRecords = ref<LearningTheme[]>([])
const categories = ref<LearningThemeCategory[]>([])
const learningApi = useLearningApi()

onMounted(() => {
    getThemes()
    getCategories()
})

const activeId = computed(() => {
    const param = route.params && route.params.lessonThemeId
        ? (Array.isArray(route.params.lessonThemeId) ? route.params.lessonThemeId[0] : route.params.lessonThemeId)
        : null
    return param ? parseInt(param) : null
})
// Global default category (admin-set) pre-selected on first load.
const defaultCategoryId = computed(() => categories.value.find(category => isEnabled(category.is_default))?.id ?? null)
const selectedCategoryId = computed(() => {
    const queryValue = Array.isArray(route.query.category) ? route.query.category[0] : route.query.category
    // Explicit "すべて" clears the filter; no param falls back to the default category.
    if (queryValue === 'all') return null
    if (queryValue) {
        const categoryId = Number(queryValue)
        return categories.value.some(category => category.id === categoryId) ? categoryId : defaultCategoryId.value
    }
    return defaultCategoryId.value
})
const topicList = computed(() => {
    if (!selectedCategoryId.value) {
        return themeRecords.value
    }

    return themeRecords.value.filter(theme => {
        return theme.categories?.some(category => category.id === selectedCategoryId.value)
    })
})
const selectedTopic = computed(() => {
    return activeId.value ? themeRecords.value.find(theme => theme.id === activeId.value) : null
})
const selectCategory = (categoryId: number | null) => {
    const query = { ...route.query }
    // "すべて" is explicit (=all) so it isn't re-defaulted back to the default category.
    query.category = categoryId ? String(categoryId) : 'all'

    router.push({ path: route.path, query })
}
const select = (topic: LearningTheme) => {
    const isActive = activeId.value && activeId.value === topic.id
    let path

    if (isEnabled(topic.has_case_study) && !isEnabled(topic.portfolio)) {
        path = isActive ? '/learning' : `/learning/${topic.id}/basic`
    } else if (isEnabled(topic.active)) {
        path = isActive ? '/learning' : `/learning/${topic.id}`
    }

    if (path) {
        router.push({ path, query: route.query })
    }
}

const getThemes = async() => {
    const data = await learningApi.getLearnerThemes()
    themeRecords.value = data
}
const getCategories = async() => {
    const data = await learningApi.getThemeCategories()
    categories.value = data
}

provide('getThemes', getThemes)
provide('providedMaterial', themeRecords)
</script>

<style>
.routeposition{
    position:absolute;
    left: 0;
    top: 0;
    height: 100%;
    width: 100%;
    z-index: 8;
    background: var(--bg3);
    color:var(--primary-color);
}

.learning__hint{
    color: gray;
}

/* The shared .post-search-wrap is 30% wide (for the Board search box); the learning
   category bar should use the full header width instead of clipping early. */
.learning .post-search-wrap{
    width: auto;
    flex: 1;
    min-width: 0;
    margin-right: 20px;
}

.learning__categories{
    display: flex;
    flex-wrap: nowrap;
    gap: 8px;
    overflow-x: auto;
    overflow-y: hidden;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
}
.learning__categories::-webkit-scrollbar{
    height: 0;
    width: 0;
}

.learning__category{
    flex: 0 0 auto;
    white-space: nowrap;
    background: var(--background-color);
    color: var(--primary-color);
    cursor: pointer;
    font-size: 12px;
    padding: 5px 15px;
    border-radius: 50px;
}

.learning__category--selected{
    background: var(--primary-color);
    color: var(--background-color);
}
.lessons-topic p:empty::after {
    content: "\00A0";
}
.topic-container{
    margin-top: 20px;
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
    line-height: normal;
}
.topic-item:hover{
  background-color: var(--primary-color);
  color: var(--background-color);
  /* box-shadow: rgba(0, 0, 0, 0.35) 0px 5px 15px; */
}
.topic-title{
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
.section-wrapper p:empty::after {
    content: "\00A0";
}
.lesson-play {
    position: absolute;
    background-color: var(--primary-button);
    color: #ffffff;
    padding: 10px;
    right: 50px;
    cursor: pointer;
    font-size: 13px;
    transition: transform 0.1s;
    display: flex;
}
@media screen and (max-width: 959px) {
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
