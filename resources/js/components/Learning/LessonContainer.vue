<template>
    <div style="height: 100%;width: 100%;overflow: hidden">     
        <Transition name="modalFade">
            <Explain @close="moreDetail = false" v-if="moreDetail"/>
        </Transition>
        <LearningLessonHeader
            :breadcrumbs="pathGenerator"
            @back="goBack"
            @navigate="goByName"
        />
        <div style="height: calc(100% - 50px);">    
            <div v-if="noData" style="line-height: 1.8;height:100%;display: flex;justify-content: center;align-items: center;">
                <p>現在データはありません。</p>
            </div>
            <div v-else-if="route.name == 'top' && selectedTopic && isEnabled(selectedTopic.active) && isEnabled(selectedTopic.portfolio)" style="height: 100%;overflow: hidden auto;">
                <LearningProgramIntro :show-evaluation-link="showEvaluationLink" />
                <LearningThemeHistory :theme-id="selectedTopic.id" @changed="refreshLessonView" />
            </div>
            <div v-else-if="route.name == 'top' && selectedTopic?.has_case_study">
                <LearningTopicMenu :items="caseStudyMenuItems" @select="selectCaseStudyByItem" />
            </div>
            <RouterView
                :selectedTopic="selectedTopic"
                v-if="selectedTopic"
                :materials="materials"
                :sections_status="sections_status"
                :filteredMaterials="filteredMaterials"
                :sectionsCompleted="sectionsCompleted"
                :examData="examData"
                :examAttempts="examAttempts"
                :examRemaining="examRemaining"
                :examLoading="loading"
                :refreshLessonView="refreshLessonView"
            />
        </div>
    </div>
</template>
<script setup lang="ts">
import { onBeforeRouteUpdate, useRoute, useRouter, type RouteLocationRaw } from 'vue-router';
import { computed, ref, inject, provide, defineAsyncComponent, watch  } from 'vue';
import { useLearningLessonView } from '@/composables/learningLessonView'
import { isEnabled } from '@/utils/learningProgress'
import LearningLessonHeader, { type LearningBreadcrumbItem } from '@/components/Learning/shared/LearningLessonHeader.vue'
import LearningProgramIntro from '@/components/Learning/shared/LearningProgramIntro.vue'
import LearningThemeHistory from '@/components/Learning/shared/LearningThemeHistory.vue'
import LearningTopicMenu, { type LearningTopicMenuChild, type LearningTopicMenuItem } from '@/components/Learning/shared/LearningTopicMenu.vue'
import { LESSON_ANSWER_STATUS, LESSON_PORTFOLIO_STATUS, LESSON_SECTION_STATUS } from '@/config/learning'
import { useAuthUserStore } from '@/store/auth'
import type { LearningMaterial, LearningTheme } from '@/types/learning'
    const auth = useAuthUserStore()
    const evaluationUserIds = new Set([610, 608, 799, 800, 829])
    const subtopics = [{val: 0, title:'知識研修'},{val: 1, title: 'グループディスカッション'},{val: 2, title: 'ポートフォリオ'}]
    const props = defineProps<{
        selectedTopic?: LearningTheme | null
    }>()
    const router = useRouter()
    const route = useRoute()
    const getThemes = inject<() => void | Promise<void>>('getThemes')
    const {
        materials,
        portfolio,
        filteredMaterials,
        sectionsStatus,
        sectionsCompleted,
        loading,
        examData,
        examAttempts,
        examRemaining,
        getLessons,
        getLessonPortfolios,
        refresh,
    } = useLearningLessonView()
    const sections_status = sectionsStatus
    const Explain = defineAsyncComponent(() =>
        import('./LessonExplain.vue')
    )
    const moreDetail = ref(false)
    const selectCaseStudy = (material: LearningMaterial) => {
        router.push({name: 'material', params: {materialId: material.id}})
    }
    const selectCaseStudyByItem = (item: LearningTopicMenuItem) => {
        const material = materials.value.find((entry) => entry.id === Number(item.id))
        if (material) selectCaseStudy(material)
    }
    const select = (topic: LearningTopicMenuItem) => {
        if(topic.value == 0){
            router.push({name: 'basic'})
        }else if(topic.value == 1 && topic.value <= workflowStatus.value){
            router.push({name: 'discussion'})
        }else if(topic.value == 2 && topic.value <= workflowStatus.value){
            router.push({name: 'portfolio'})
        }
    }
    const progress = computed(() => props.selectedTopic?.progress ?? null)
    const workflowStatus = computed(() => {
        return progress.value?.portfolio.status ?? 0
    })
    const topicMenuItems = computed<LearningTopicMenuItem[]>(() => {
        return subtopics.map((topic) => {
            const children: LearningTopicMenuChild[] = topic.val === 0
                ? sections_status.value.map((section) => ({
                    id: section.id,
                    title: section.lesson_material?.title ?? '',
                    tone: section.status === LESSON_SECTION_STATUS.COMPLETED
                        ? 'complete'
                        : section.status === LESSON_SECTION_STATUS.DRAFT
                            ? 'warning'
                            : undefined,
                }))
                : []

            if (topic.val === 0 && sectionsCompleted.value && portfolio.value) {
                children.push({
                    id: 'portfolio-create',
                    title: 'ポートフォリオ作成',
                    tone: Number(portfolio.value.status) >= LESSON_PORTFOLIO_STATUS.DISCUSSION_DRAFT_READY ? 'complete' : 'warning',
                })
            }

            return {
                id: topic.val,
                value: topic.val,
                title: topic.title,
                disabled: workflowStatus.value < topic.val,
                completed: workflowStatus.value > topic.val,
                children,
            }
        })
    })
    const caseStudyMenuItems = computed<LearningTopicMenuItem[]>(() => {
        return materials.value.map((material) => ({
            id: material.id,
            title: material.title ?? '',
            completed: isMaterialComplete(material),
        }))
    })
    const isMaterialComplete = (material: LearningMaterial) => {
        const section = sections_status.value.find((entry) => entry.material_id === material.id)
        return material.answer?.status === LESSON_ANSWER_STATUS.COMPLETED
            || section?.status === LESSON_SECTION_STATUS.COMPLETED
    }
    const noData = computed(() => {
        return props.selectedTopic && !isEnabled(props.selectedTopic.active)
    })
    const showEvaluationLink = computed(() => {
        return evaluationUserIds.has(Number(auth.activeUser?.id))
    })
    const refreshThemes = async() => {
        await getThemes?.()
    }
    const refreshLessons = async() => {
        await getLessons()
        await refreshThemes()
    }
    const refreshLessonPortfolios = async() => {
        await getLessonPortfolios()
        await refreshThemes()
    }
    const refreshLessonView = async() => {
        await refresh()
        await refreshThemes()
    }
    onBeforeRouteUpdate(async (to, from, next) => {
        // Re-fetch the current attempt's portfolio so each stage sees the latest
        // status (e.g. after 知識研修 / ディスカッション completes, the next stage
        // must know it's ready to accept input).
        try {
            await getLessonPortfolios()
        } catch {
            // non-fatal; fall through to navigation
        }
        refreshThemes()
        next();
    })
    const userNavigated = ref(false)
    const ensureDefaultView = (isUserAction = false) => {
        if(isUserAction){
            userNavigated.value = true
        }
        if(route.name === 'top' && props.selectedTopic && isEnabled(props.selectedTopic.active) && !isEnabled(props.selectedTopic.portfolio) && !userNavigated.value){
            router.replace({name: 'basic', params: {lessonThemeId: props.selectedTopic.id}})
        }
    }
    watch(() => props.selectedTopic, () => {
        ensureDefaultView()
    }, { immediate: true })
    const pathGenerator = computed<LearningBreadcrumbItem[]>(() => {
        const relatedRoutes = route.matched.filter(rt => !['learning', 'top'].includes(String(rt.name)))
        const items: LearningBreadcrumbItem[] = []
        const base = {
            label: props.selectedTopic?.title ?? '',
            route: {name: 'top'} as RouteLocationRaw,
        }
        items.push(base)
        
        relatedRoutes.forEach(rt => {
            const label = rt.name == 'material' ? materialTitle.value : rt.meta?.nameJp
            const params = Object.assign({}, route.params)
            items.push({
                label: String(label ?? ''),
                route: {name: rt.name, params} as RouteLocationRaw,
            })
        });
        return items
    })
    const materialTitle = computed(() => {
        const materialId = route.params?.materialId
        if(materialId && materials.value){
            const id = Array.isArray(materialId) ? materialId[0] : materialId
            const title = materials.value.find(ob => Number(ob.id) === Number(id))?.title
            return title
        }
        return ''
    })

    // Carry the theme-list filter (?category=…) through every in-lesson
    // navigation so returning to /learning keeps the selected category.
    const goByName = (targetRoute: RouteLocationRaw) => {
        if (
            props.selectedTopic
            && typeof targetRoute === 'object'
            && 'name' in targetRoute
            && targetRoute.name === 'top'
            && isEnabled(props.selectedTopic.has_case_study)
            && !isEnabled(props.selectedTopic.portfolio)
        ) {
            router.push({ name: 'learning', query: route.query })
        } else if (typeof targetRoute === 'object') {
            router.push({ ...targetRoute, query: route.query })
        } else {
            router.push(targetRoute)
        }
    }
    const goBack = () => {
        const { name, query } = route;
        const { has_case_study, portfolio } = props.selectedTopic || {};

        if (isEnabled(has_case_study) && !isEnabled(portfolio)) {
            if (name === 'basic') {
                router.push({ name: 'learning', query });
                return;
            }
            if (name === 'material') {
                router.push({ name: 'basic', query });
                return;
            }
        }

        switch (name) {
            case 'top':
                router.push({ name: 'learning', query });
                break;
            case 'basic':
                router.push({ name: 'top', query });
                break;
            case 'material':
                router.push({ name: 'basic', query });
                break;
            default:
                router.go(-1);
                break;
        }
    };
    provide('getLessonPortfolios', refreshLessonPortfolios)
    provide('portfolio', portfolio)
    provide('getLessons', refreshLessons)
</script>
