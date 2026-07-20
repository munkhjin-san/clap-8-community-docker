<template>
    <div class="learning-control">
         
        <Transition name="modalFade">                              
            <ThemeCreate
                v-if="createThemeWindow"
                @closeModal="closeThemeCreate"
                :editTarget="editThemeTarget"
            />
            
        </Transition>      
        <div class="learning-control__content">
            <div v-if="isRootRoute" class="sub-tab-container learning-control__tabs">
                <button
                    type="button"
                    :class="['sub-tab-item learning-control__tab', { 'selected-sub-tab': route.name === 'learningcontrol' }]"
                    @click="router.push({ name: 'learningcontrol' })"
                >
                    テーマ
                </button>
                <button
                    type="button"
                    :class="['sub-tab-item learning-control__tab', { 'selected-sub-tab': route.name === 'learning-categories' }]"
                    @click="router.push({ name: 'learning-categories' })"
                >
                    カテゴリー
                </button>
            </div>
            <div v-if="route.name == 'learningcontrol'" class="h-[calc(100%-60px)]">
                <div class="learning-control__filters">
                    <button
                        v-for="option in archiveFilterOptions"
                        :key="option.value"
                        type="button"
                        :class="[
                            'learning-control__filter',
                            { 'learning-control__filter--active': archiveFilter === option.value },
                        ]"
                        @click="archiveFilter = option.value"
                    >
                        {{ option.label }}
                    </button>
                </div>
                <LearningThemeGrid
                    :themes="filteredThemes"
                    @open-theme="openTheme"
                    @edit-theme="editTheme"
                    @delete-theme="deleteThemeConfirm"
                />
                <div @click="createThemeWindow = true" class="createBoardButton fileNewButton" title="新規作成" id="boardCreate">
                    <svg class="learning-control__plus-icon" version="1.1" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 32 32"><path d="M30.044 14.14c-2.402-0.231-4.804-0.341-7.206-0.422-1.535-0.058-3.071-0.079-4.606-0.090-0.326-0.002-0.587-0.265-0.588-0.591-0.004-1.537-0.018-3.074-0.078-4.613-0.092-2.4-0.218-4.802-0.542-7.205-0.084-0.612-0.565-1.119-1.205-1.206-0.769-0.103-1.477 0.437-1.582 1.206-0.324 2.401-0.449 4.804-0.542 7.205-0.059 1.536-0.074 3.071-0.078 4.606-0.001 0.325-0.263 0.59-0.59 0.59-1.534 0.005-3.068 0.020-4.602 0.078-2.404 0.094-4.805 0.219-7.207 0.543-0.612 0.081-1.119 0.564-1.205 1.205-0.103 0.769 0.436 1.477 1.205 1.58 2.402 0.324 4.804 0.449 7.207 0.543 1.536 0.059 3.074 0.073 4.612 0.078 0.325 0.001 0.587 0.262 0.59 0.587 0.011 1.536 0.033 3.070 0.090 4.606 0.080 2.402 0.192 4.805 0.423 7.207 0.066 0.699 0.622 1.278 1.349 1.348 0.823 0.079 1.556-0.524 1.633-1.348 0.231-2.402 0.342-4.805 0.423-7.207 0.057-1.538 0.079-3.077 0.090-4.615 0.002-0.324 0.263-0.583 0.587-0.586 1.538-0.011 3.077-0.034 4.615-0.090 2.402-0.080 4.804-0.193 7.206-0.423 0.7-0.066 1.279-0.622 1.349-1.349 0.076-0.823-0.528-1.557-1.351-1.634z"></path></svg>
                </div>                
            </div>
            <router-view :themes="themeRecords"></router-view>
        </div>
        
    </div>
</template>
<script setup lang="ts">
import { computed, onMounted, ref, provide } from 'vue';
import ThemeCreate from './ThemeCreate.vue';
import { useRoute, useRouter } from 'vue-router';
import { useLearningApi } from '@/composables/learningApi';
import type { LearningTheme } from '@/types/learning';
import LearningThemeGrid from './Shell/LearningThemeGrid.vue';
    const router = useRouter()
    const route = useRoute()
    const createThemeWindow = ref(false)
    const editThemeTarget = ref<LearningTheme | null>(null)
    const themeRecords = ref<LearningTheme[]>([])
    const archiveFilter = ref<'all' | 'active' | 'archived'>('active')
    const learningApi = useLearningApi()
    onMounted(() => {
        getThemes()
    })

    const isRootRoute = computed(() => route.name === 'learningcontrol' || route.name === 'learning-categories')
    const archiveFilterOptions = [
        { label: 'すべて', value: 'all' },
        { label: '通常', value: 'active' },
        { label: 'アーカイブ', value: 'archived' },
    ] as const
    const isArchived = (theme: LearningTheme) => {
        return theme.archive === true || theme.archive === 1
    }
    const filteredThemes = computed(() => {
        if(archiveFilter.value === 'active'){
            return themeRecords.value.filter(theme => !isArchived(theme))
        }
        if(archiveFilter.value === 'archived'){
            return themeRecords.value.filter(theme => isArchived(theme))
        }
        return themeRecords.value
    })

    const editTheme = (theme: LearningTheme) => {
        editThemeTarget.value = theme
        createThemeWindow.value = true
    }
    const openTheme = (theme: LearningTheme) => {
        router.push({ name: 'content', params: { themeId: theme.id } })
    }
    const deleteThemeConfirm = async(id: number) => {
        const data = await learningApi.deleteTheme(id)
        data && getThemes()
    }
    const closeThemeCreate = (flag?: boolean) => {
        createThemeWindow.value = false
        editThemeTarget.value = null
        if(flag){
            getThemes()
        }
    }
    const getThemes = async() => {
        const data = await learningApi.getAdminThemes()
        data && (themeRecords.value = data)
    }
    provide('getThemes', getThemes)
</script>

<style scoped>
.learning-control{
    background: var(--bg3);
    gap: 0;
    height: 100%;
    flex: 1;
}

.learning-control__content{
    height: 100%;
}

.learning-control__tabs{
    margin: 0 20px;
    padding-top: 20px;
    
}
.learning-control__tab{
    background: var(--bg3);
}
.learning-control__filters{
    display: flex;
    gap: 8px;
    margin: 20px;
}

.learning-control__filter{
    background: transparent;
    border: 1px solid var(--primary-color);
    color: var(--primary-color);
    cursor: pointer;
    font-size: 12px;
    padding: 6px 12px;
}

.learning-control__filter--active{
    background: var(--primary-color);
    color: var(--background-color);
}

.learning-control__plus-icon{
    fill: rgb(0, 0, 0);
    margin: auto;
}
</style>
